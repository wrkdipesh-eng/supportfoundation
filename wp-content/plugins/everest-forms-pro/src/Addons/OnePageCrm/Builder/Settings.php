<?php
/**
 * OnePageCRM Settings.
 *
 * @package EverestForms\OnePageCRM\Builder
 * @since   1.0.0
 */

 namespace  EverestForms\Pro\Addons\OnePageCrm\Builder;

 use EverestForms\Pro\Addons\OnePageCrm\Api\Api;

defined( 'ABSPATH' ) || exit;


/**
 * OnePageCRM Integration.
 */
class Settings extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'onepagecrm';
		$this->name = __( 'OnePageCRM', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/OnePageCrm/assets/img/OnePageCRM.png', EFP_PLUGIN_FILE );

		parent::__construct();
	}


	/**
	 * Process and submit entry to provider.
	 *
	 * @param array $fields    List of form fields.
	 * @param array $entry     User submitted data.
	 * @param array $form_data Prepared form settings.
	 * @param int   $entry_id  Entry ID.
	 */
	public function process_feed( $fields, $entry, $form_data, $entry_id = 0 ) {

		// Only run if this form has connections for this provider.
		if ( empty( $form_data['integrations'][ $this->id ] ) ) {
			return;
		}

		// Fire for each connection.
		foreach ( $form_data['integrations'][ $this->id ] as $connection ) :
			// Check for conditional logic.
			if ( isset( $connection['conditional_logic']['status'] ) ) {
				$con_field_select          = isset( $connection['conditional_logic']['field_select'] ) ? $connection['conditional_logic']['field_select'] : '';
				$con_field_condition       = isset( $connection['conditional_logic']['condition'] ) ? $connection['conditional_logic']['condition'] : '';
				$con_field_input_choice    = isset( $connection['conditional_logic']['input_choice'] ) ? $connection['conditional_logic']['input_choice'] : '';
				$con_field_multiple_choice = isset( $connection['conditional_logic']['multiple_choice'] ) ? $connection['conditional_logic']['multiple_choice'] : '';
				$con_field_country_choice  = isset( $connection['conditional_logic']['country_choice'] ) ? $connection['conditional_logic']['country_choice'] : '';

				if ( ! empty( $con_field_input_choice ) ) {
					$con_field_choice = $con_field_input_choice;
				} elseif ( ! empty( $con_field_multiple_choice ) ) {
					$con_field_choice = $con_field_multiple_choice;
				} elseif ( ! empty( $con_field_country_choice ) ) {
					$con_field_choice = $con_field_country_choice;
				} else {
					$con_field_choice = '';
				}

				if ( 'is' === $con_field_condition ) {
					if ( isset( $entry['form_fields'][ $con_field_select ] ) ) {
						if ( is_array( $entry['form_fields'][ $con_field_select ] ) ) {
							if ( ! in_array( $con_field_choice, $entry['form_fields'][ $con_field_select ], true ) ) {
								continue;
							}
						} else {
							if ( $entry['form_fields'][ $con_field_select ] !== $con_field_choice ) {
								continue;
							}
						}
					} else {
						continue;
					}
				} elseif ( 'is_not' === $con_field_condition ) {
					if ( isset( $entry['form_fields'][ $con_field_select ] ) ) {
						if ( is_array( $entry['form_fields'][ $con_field_select ] ) ) {
							if ( in_array( $con_field_choice, $entry['form_fields'][ $con_field_select ], true ) ) {
								continue;
							}
						} else {
							if ( $entry['form_fields'][ $con_field_select ] === $con_field_choice ) {
								continue;
							}
						}
					}
				}
			}

			$account_id = $connection['account_id'];
			$api        = $this->api_connect( $account_id );

			if ( is_wp_error( $api ) ) {
				continue;
			}

			$connection['custom_field_value'] = isset( $connection['custom_field_value'] ) ? $connection['custom_field_value'] : array();
			$custom_field_value               = array();

			// Setup merge custom fields.
			foreach ( $connection['custom_field_value'] as $name => $custom_field ) {
				$custom_field = explode( '.', $custom_field );
				$id           = $custom_field[0]; // evf Field ID.
				$key          = ! empty( $custom_field[1] ) ? $custom_field[1] : 'value';
				$type         = ! empty( $custom_field[2] ) ? $custom_field[2] : 'text'; // MC merge field type.

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				$value = $fields[ $id ][ $key ];

				// Format edge cases pre-API call.
				if ( is_array( $value ) ) {
					switch ( $fields[ $id ]['type'] ) {
						case 'radio':
						case 'payment-radio':
							$value = ! empty( $value['label'] ) ? $value['label'] : '';
							break;

						case 'checkbox':
						case 'payment-checkbox':
							$value = ! empty( $value['label'] ) && is_array( $value['label'] ) ? '||' . implode( '||', (array) $value['label'] ) . '||' : $value['label'];
							break;

						case 'address':
							$value = implode( ', ', $value );
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}
				$custom_field_value[] = array(
					'id'    => $connection['custom_field'][ $name ],
					'value' => $value
				);
			}

			// Setup merge fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {
				// Check if merge vars are used.
				if ( empty( $merge_field ) ) {
					continue;
				}

				$merge_field = explode( '.', $merge_field );
				$id          = $merge_field[0]; // evf Field ID.
				$key         = ! empty( $merge_field[1] ) ? $merge_field[1] : 'value';
				$type        = ! empty( $merge_field[2] ) ? $merge_field[2] : 'text'; // MC merge field type.

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				$value = $fields[ $id ][ $key ];

				// Format edge cases pre-API call.
				if ( is_array( $value ) ) {
					switch ( $fields[ $id ]['type'] ) {
						case 'radio':
						case 'payment-radio':
							$value = ! empty( $value['label'] ) ? $value['label'] : '';
							break;

						case 'checkbox':
						case 'payment-checkbox':
							$value = ! empty( $value['label'] ) && is_array( $value['label'] ) ? implode( ', ', (array) $value['label'] ) : $value['label'];
							break;

						case 'address':
							$value = implode( ', ', $value );
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}
				$data[ $name ] = $value;
			}

			if ( ! empty( $custom_field_value ) ) {
				$data = array_merge( array( 'custom_fields' => $custom_field_value ), $data );
			}

			if ( 'contact' === $connection['list_id'] ) {
				$account_id               = $connection['account_id'];
				$company_id               = $connection['com_id'];
				$status_id                = $connection['status_id'];
				$lead_source_id           = $connection['contact_lead_id'];
				$company                  = $this->api->get_contact_companies();
				$status                   = $this->api->get_contact_status();
				$lead_source              = $this->api->get_contact_lead_sources();
				$contact_status_id        = $status['data'][ $status_id ]['status']['id'];
				$contact_company_id       = $company['data']['companies'][ $company_id ]['company']['id'];
				$contact_company_name     = $company['data']['companies'][ $company_id ]['company']['name'];
				$contact_lead_source_id   = $lead_source['data'][ $lead_source_id ]['id'];
				$contact_lead_source_name = $lead_source['data'][ $lead_source_id ]['text'];
				$contact_integration      = get_option( 'everest_forms_integrations' );
				$contact_owner_id         = $contact_integration['onepagecrm'][ $account_id ]['apiuserid'];
				$data['company_id']       = $contact_company_id;
				$data['company_name']     = $contact_company_name;
				$data['status_id']        = $contact_status_id;
				$data['lead_source_id']   = $contact_lead_source_id;
				$data['lead_source']      = $contact_lead_source_name;
				$data['owner_id']         = $contact_owner_id;
				$data['phones']           = array(
					'value' => isset( $data['phone'] ) ? $data['phone'] : '',
				);
				$data['urls']             = array(
					'value' => isset( $data['url'] ) ? $data['url'] : '',
				);
				$data['emails']           = array(
					'value' => isset( $data['email'] ) ? $data['email'] : '',
				);

				$address = isset( $data['address'] ) ? $data['address'] : '';

				$address_lines = explode( "\n", $address );

				$data['address_list'] = array(
					array(
						'address'      => ! empty( $address_lines[0] ) ? $address_lines[0] : '',
						'city'         => ! empty( $address_lines[1] ) ? $address_lines[1] : '',
						'state'        => ! empty( $address_lines[2] ) ? $address_lines[2] : '',
						'zip_code'     => ! empty( $address_lines[3] ) ? $address_lines[3] : '',
						'country_code' => ! empty( $address_lines[4] ) ? $address_lines[4] : '',
					),
				);

				$endpoint_url = 'https://app.onepagecrm.com/api/v3/';
				$resource     = $endpoint_url . 'contacts';
			} else {
				$contact_integration = get_option( 'everest_forms_integrations' );
				$owner_id            = $contact_integration['onepagecrm'][ $account_id ]['apiuserid'];
				$contact             = $this->api->get_contact_details();
				$contact_id          = $connection['contact_id'];
				$contact_detail_id   = $contact['data']['contacts'][ $contact_id ]['contact']['id'];
				$data['contact_id']  = $contact_detail_id;
				$data['owner_id']    = $owner_id;
				$data['status']      = $connection['deal_status_id'];
				$endpoint_url        = 'https://app.onepagecrm.com/api/v3/';
				$resource            = $endpoint_url . 'deals';
			}

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				try {
					$data     = wp_json_encode( $data, true );
					$response = $api->send_request( $resource, $data, 'POST' );
				} catch ( \Exception $e ) {
					evf_get_logger(
						__( 'OnePageCRM API error', 'everest-forms-pro' ),
						$e->getMessage(),
						array(
							'type' => array( 'Integration', 'error' ),
						)
					);
				}
			}

			endforeach;
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$lists = $this->api->get_service();
			if ( ! empty( $lists ) ) {
				$list_array = array();
				foreach ( $lists as $key => $list ) {
					$list_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $list ) ? trim( $list ) : __( 'Unknown List', 'everest-forms-pro' ),
					);
				}
				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'OnePageCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}


	/**
	 * Get companies lists.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_contact_company( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$contact_companies = $this->api->get_contact_companies();
			$companies         = $contact_companies['data']['companies'];

			if ( ! empty( $companies ) ) {
				$contact_company_array = array();
				foreach ( $companies as $key => $contact_company ) {
					$contact_company_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $contact_company['company']['name'] ) ? trim( $contact_company['company']['name'] ) : __( 'Unknown Contact Company', 'everest-forms-pro' ),
					);
				}
				return $contact_company_array;
			} else {
				$error_msg = __( 'Contact company error: No Companies found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'OnePageCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Contact status.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_contact_status( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$contact_statuses = $this->api->get_contact_status();
			$statuses         = $contact_statuses['data'];

			if ( ! empty( $statuses ) ) {
				$contact_status_array = array();
				foreach ( $statuses as $key => $contact_status ) {
					$contact_status_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $contact_status['status']['text'] ) ? trim( $contact_status['status']['text'] ) : __( 'Unknown Contact Status', 'everest-forms-pro' ),
					);
				}
				return $contact_status_array;
			} else {
				$error_msg = __( 'Contact status error: No Status found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'OnePageCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Contact details.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_contact_detail( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$contacts_details = $this->api->get_contact_details();
			$contact_detail   = $contacts_details['data']['contacts'];

			if ( ! empty( $contact_detail ) ) {
				$contact_array = array();
				foreach ( $contact_detail as $key => $contact ) {
					$first_name = $contact['contact']['first_name'];
					$last_name  = $contact['contact']['last_name'];

					$name                  = $first_name . ' ' . $last_name;
					$contact_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $name ) ? trim( $name ) : __( 'Unknown Contact', 'everest-forms-pro' ),
					);
				}
				return $contact_array;
			} else {
				$error_msg = __( 'Contact error: No contact found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'OnePageCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get companies lists.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_contact_lead_source( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$contact_lead_source = $this->api->get_contact_lead_sources();
			$lead_sources        = $contact_lead_source['data'];

			if ( ! empty( $lead_sources ) ) {
				$contact_lead_source_array = array();
				foreach ( $lead_sources as $key => $lead_source ) {
					$contact_lead_source_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $lead_source['text'] ) ? trim( $lead_source['text'] ) : __( 'Unknown Contact Lead Source', 'everest-forms-pro' ),
					);
				}
				return $contact_lead_source_array;
			} else {
				$error_msg = __( 'Contact lead source error: No Lead source found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'OnePageCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Deal status.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_deal_status( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$deals = $this->api->get_deal_status();
			if ( ! empty( $deals ) ) {
				$deal_array = array();
				foreach ( $deals as $key => $deal ) {
					$deal_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $deal ) ? trim( $deal ) : __( 'Unknown Deal Status', 'everest-forms-pro' ),
					);
				}
				return $deal_array;
			} else {
				$error_msg = __( 'API deal status error: No deal status found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'OnePageCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Connect to API.
	 *
	 * @param  string $account_id  Account ID for OnePageCRM.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) && ! empty( $providers[ $this->id ][ $account_id ]['apiuserid'] ) ) {
				$api_key       = $providers[ $this->id ][ $account_id ]['api'];
				$api_user_id   = $providers[ $this->id ][ $account_id ]['apiuserid'];
				$this->account = $account_id;
				$this->api     = new Api( $api_key, $api_user_id );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}


	/**
	 * Authenticate with the Integration API.
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 * @throws \Exception Exception.
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		$api   = new Api( trim( $data['apikey'] ), trim( $data['apiuserid'] ) );
		$valid = $api->auth_test();

		if ( isset( $valid['status'] ) && 401 === $valid['status'] ) {
			return $this->error( __( $error_message, 'everest-forms-pro' ) );
		}

		$id           = uniqid();
		$integrations = get_option( 'everest_forms_integrations', array() );

		$integrations[ $this->id ][ $id ] = array(
			'apiuserid' => trim( $data['apiuserid'] ),
			'api'       => trim( $data['apikey'] ),
			'label'     => sanitize_text_field( $data['label'] ),
			'date'      => time(),
		);
		update_option( 'everest_forms_integrations', $integrations );

		return $id;
	}


	/**
	 * Fetch Integration account list fields.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 *
	 * @return mixed array or error object
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		if ( 'contact' === $list_id ) {
			$fld = array(
				array(
					'id'         => 0,
					'name'       => __( 'First Name', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'first_name',
					'tag'        => 'first_name',
				),
				array(
					'id'         => 1,
					'name'       => __( 'Last Name', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'last_name',
					'tag'        => 'last_name',
				),
				array(
					'id'         => 2,
					'name'       => __( 'Job Title', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'text',
					'tag'        => 'job_title',
				),
				array(
					'id'         => 3,
					'name'       => __( 'Phone', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'phone',
					'tag'        => 'phone',
				),
				array(
					'id'         => 4,
					'name'       => __( 'Email Address', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'email',
					'tag'        => 'email',
				),
				array(
					'id'         => 5,
					'name'       => __( 'URL', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'url',
					'tag'        => 'url',
				),
				array(
					'id'         => 6,
					'name'       => __( 'Address', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'address',
					'tag'        => 'address',
				),
				array(
					'id'         => 7,
					'name'       => __( 'Background', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'textarea',
					'tag'        => 'background',
				),
			);
		} else {
			$fld = array(
				array(
					'id'         => 0,
					'name'       => __( 'Name', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'text',
					'tag'        => 'name',
				),
				array(
					'id'         => 1,
					'name'       => __( 'Deal Description', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'textarea',
					'tag'        => 'text',
				),
				array(
					'id'         => 2,
					'name'       => __( 'Expected Close Date', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'date',
					'tag'        => 'expected_close_date',
				),
				array(
					'id'         => 3,
					'name'       => __( 'Close Date', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'date',
					'tag'        => 'close_date',
				),
				array(
					'id'         => 4,
					'name'       => __( 'Creation Date of the Deal', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'date',
					'tag'        => 'date',
				),
				array(
					'id'         => 5,
					'name'       => __( 'Deal Amount', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'number',
					'tag'        => 'amount',
				),
				array(
					'id'         => 6,
					'name'       => __( 'Deal Cost', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'number',
					'tag'        => 'amount',
				),
				array(
					'id'         => 7,
					'name'       => __( 'Deal Cost', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'number',
					'tag'        => 'amount',
				),
			);
		}
		return $fld;
	}

	/**
	 * Fetch API Groups.
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $list_id List ID.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'OnePageCRM won\'t support Groups.', 'everest-forms-pro' ) );
	}

	/**
	 * OnePageCrm custom field lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 */
	public function api_custom_field( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {

			$custom_fields = $this->api->get_custom_fields();

			if ( is_wp_error( $custom_fields ) ) {
				return false;
			}
			$fld = array();
			if ( ! empty( $custom_fields['data']['custom_fields'] ) ) {
				foreach ( $custom_fields['data']['custom_fields'] as $custom_field ) {
					$fld[ $custom_field['custom_field']['name'] ] = array(
						'id'         => $custom_field['custom_field']['id'],
						'name'       => isset( $custom_field['custom_field']['name'] ) ? trim( $custom_field['custom_field']['name'] ) : __( 'Custom Fields', 'everest-forms-pro' ),
						'field_type' => isset( $custom_field['custom_field']['type'] ) ? $custom_field['custom_field']['type'] : '',
					);
				}
			}
			return $fld;
		} catch ( \Exception $e ) {

			evf_get_logger(
				__( 'OnePageCrm API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
	}


}
