<?php
/**
 * Pipedrive Settings.
 *
 * @package EverestForms\Pro\Addons\PipeDrive\Builder
 * @since   1.0.0
 */

namespace  EverestForms\Pro\Addons\PipeDrive\Builder;

use EverestForms\Pro\Addons\PipeDrive\API\API;

defined( 'ABSPATH' ) || exit;


/**
 * Pipedrive Integration.
 */
class Settings extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'pipedrive';
		$this->name = __( 'Pipedrive', 'everest-forms-pipedrive' );
		$this->icon = plugins_url( '/src/Addons/PipeDrive/assets/images/pipedrive.png', EFP_PLUGIN_FILE );

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

			if ( 'person' === $connection['list_id'] ) {
				// Before proceeding make sure required fields are configured.
				if ( empty( $connection['fields']['email'] ) ) {
					continue;
				}
			}

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
			if ( 'person' === $connection['list_id'] ) {
				$email_data = explode( '.', $connection['fields']['email'] );
				$email_id   = $email_data[0];
			}
			$api = $this->api_connect( $account_id );

			if ( is_wp_error( $api ) ) {
				continue;
			}
			if ( 'person' === $connection['list_id'] ) {
				// Email is required.
				if ( empty( $fields[ $email_id ]['value'] ) ) {
					continue;
				} else {
					$data['email'] = strtolower( $fields[ $email_id ]['value'] );
				}
			}
			$connection['custom_field_value'] = isset( $connection['custom_field_value'] ) ? $connection['custom_field_value'] : array();

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

				$custom_field_value[ $connection['custom_field'][ $name ] ] = $value;
			}

			// Setup merge fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {

				// Don't include EMAIL merge fields.
				if ( 'email' === $name ) {
					continue;
				}

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
				$data = array_merge( $custom_field_value, $data );
			}

			if ( 'lead' === $connection['list_id'] ) {
				$person_data = array();

				$data['value']           = array(
					'amount'   => ! empty( $data['value'] ) ? intval( $data['value'] ) : 0,
					'currency' => $connection['currency'],
				);
				$data['owner_id']        = ! empty( $connection['owner_id'] ) ? intval( $connection['owner_id'] ) : 0;
				$data['organization_id'] = ! empty( $connection['org_id'] ) ? intval( $connection['org_id'] ) : 0;
				$data['visible_to']      = $connection['visible_to'];

				// Create lead through person_id if organization is not set.
				if ( 0 === $data['organization_id'] ) {
					$person_data['name']     = ! empty( $data['title'] ) ? $data['title'] : '';
					$person_data['owner_id'] = ! empty( $data['owner_id'] ) ? $data['owner_id'] : 0;
					$resource                = 'persons';
					if ( ! is_wp_error( $this->api ) ) {
						$person_response = $api->send_request( $resource, $person_data, 'POST' );
						$person_id       = ! empty( $person_response['data']['id'] ) ? $person_response['data']['id'] : 0;
						unset( $data['organization_id'] );
						$data['person_id'] = $person_id;
					}
				}

				// Create person as custom field is not supported by pipedrive API now.
				if ( ! empty( $data['person_name'] ) ) {
					$person_data['name'] = $data['person_name'];
				} else {
					$person_data['name'] = $data['title'];
				}

				if ( ! empty( $data['person_email'] ) ) {
					$person_data['email'] = $data['person_email'];
				}

				if ( ! empty( $data['person_phone'] ) ) {
					$person_data['phone'] = $data['person_phone'];
				}

				if ( isset( $data['person_id'] ) && ! empty( $data['person_id'] ) ) {
					$resource        = 'persons/' . $data['person_id'];
					$person_response = $api->send_request( $resource, $person_data, 'PUT' );
				} else {
					$resource               = 'persons';
					$person_create_response = $api->send_request( $resource, $person_data, 'POST' );
				}

				unset( $data['person_name'], $data['person_email'], $data['person_phone'] );
				$resource = 'leads';
			} else {
				$first_name         = ! empty( $data['first_name'] ) ? $data['first_name'] : '';
				$last_name          = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
				$data['name']       = $first_name . ' ' . $last_name;
				$data['owner_id']   = intval( $connection['owner_id'] );
				$data['org_id']     = ! empty( $connection['org_id'] ) ? intval( $connection['org_id'] ) : 0;
				$data['label']      = ! empty( $connection['label'] ) ? $connection['label'] : '';
				$data['visible_to'] = $connection['visible_to'];
				$resource           = 'persons';
			}

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				$response = $api->send_request( $resource, $data, 'POST' );
				return $response;
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
			$lists = $this->api->get_list();
			if ( ! empty( $lists ) ) {
				$list_array = array();
				foreach ( $lists as $key => $list ) {
					$list_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $list ) ? trim( $list ) : __( 'Unknown List', 'everest-forms-pipedrive' ),
					);
				}
				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pipedrive' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}


	/**
	 * Get Integration account organization for pipedrive.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_organization( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$organization = $this->api->get_organization();
			if ( ! empty( $organization['data'] ) ) {
				$org_array    = array();
				$org_array[0] = array(
					'id'   => 0,
					'name' => __( 'Select Organization', 'everest-forms-pipedrive' ),
				);

				foreach ( $organization['data'] as $org ) {
					if ( empty( $org['id'] ) ) {
						continue;
					}
					$org_array[ $org['id'] ] = array(
						'id'   => $org['id'],
						'name' => isset( $org['name'] ) ? trim( $org['name'] ) : __( 'Unknown Organization', 'everest-forms-pipedrive' ),
					);
				}

				return $org_array;
			} else {
				$error_msg = __( 'API list error: No Organization found', 'everest-forms-pipedrive' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Integration account label for pipedrive.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_person_label( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$labels = $this->api->get_person_fields();
			if ( ! empty( $labels['data'] ) ) {
				$label_array = array();
				foreach ( $labels['data'] as $key => $label ) {
					if ( 'label' === $label['key'] ) {
						$option_array = array();
						foreach ( $label['options'] as $option ) {
							$option_array[ $option['id'] ] = array(
								'id'   => $option['id'],
								'name' => isset( $option['label'] ) ? trim( $option['label'] ) : __( 'Unknown Label', 'everest-forms-pipedrive' ),
							);
						}
						$label_array = $option_array;
					}
				}
				return $label_array;
			} else {
				$error_msg = __( 'API list error: No Organization found', 'everest-forms-pipedrive' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}


	/**
	 * Get Integration account label for pipedrive.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_visible( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$visible = $this->api->get_person_fields();
			if ( ! empty( $visible['data'] ) ) {
				$visbile_array = array();
				foreach ( $visible['data'] as  $vis ) {
					if ( 'visible_to' === $vis['key'] ) {
						$vis_array = array();
						foreach ( $vis['options'] as $option ) {
							$vis_array[ $option['id'] ] = array(
								'id'   => $option['id'],
								'name' => isset( $option['label'] ) ? trim( $option['label'] ) : __( 'Unknown Visibile', 'everest-forms-pipedrive' ),
							);
						}
						$visbile_array = $vis_array;
					}
				}
				return $visbile_array;
			} else {
				$error_msg = __( 'API list error: No Organization found', 'everest-forms-pipedrive' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_owner( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$owners = $this->api->get_users();

			if ( ! empty( $owners['data'] ) ) {
				$owner_array = array();
				foreach ( $owners['data'] as $owner ) {
					if ( empty( $owner['id'] ) ) {
						continue;
					}
					$owner_array[ $owner['id'] ] = array(
						'id'   => $owner['id'],
						'name' => isset( $owner['name'] ) ? trim( $owner['name'] ) : __( 'Unknown Owner', 'everest-forms-pipedrive' ),
					);
				}

				return $owner_array;
			} else {
				$error_msg = __( 'API list error: No owners found', 'everest-forms-pipedrive' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Integration supported currencies.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_currency( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$currencies = $this->api->get_currencies();

			if ( ! empty( $currencies['data'] ) ) {
				$currency_array = array();
				foreach ( $currencies['data'] as $currency ) {
					if ( empty( $currency['id'] ) ) {
						continue;
					}
					$currency_array[ $currency['code'] ] = array(
						'id'   => $currency['code'],
						'name' => isset( $currency['name'] ) ? trim( $currency['name'] ) : __( 'Unknown Currency', 'everest-forms-pipedrive' ),
					);
				}

				return $currency_array;
			} else {
				$error_msg = __( 'API list error: No currencies found', 'everest-forms-pipedrive' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
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
	 * @param  string $account_id  Account ID for Hubspot.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$api_key       = $providers[ $this->id ][ $account_id ]['api'];
				$this->account = $account_id;
				$this->api     = new API( $api_key );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pipedrive' ) );
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
		$api = new API( trim( $data['apikey'] ) );

		if ( ! $api->auth_test() ) {

			return $this->error( __( 'Could not verify API key', 'everest-forms-pipedrive' ) );
		}

		$id           = uniqid();
		$integrations = get_option( 'everest_forms_integrations', array() );

		$integrations[ $this->id ][ $id ] = array(
			'api'   => trim( $data['apikey'] ),
			'label' => sanitize_text_field( $data['label'] ),
			'date'  => time(),
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
		if ( 'lead' === $list_id ) {
			$fld = array(
				array(
					'id'         => 0,
					'name'       => __( 'Title', 'everest-forms-pipedrive' ),
					'req'        => true,
					'field_type' => 'title',
					'tag'        => 'title',
				),
				array(
					'id'         => 1,
					'name'       => __( 'Lead Amount', 'everest-forms-pipedrive' ),
					'req'        => true,
					'field_type' => 'value',
					'tag'        => 'value',
				),
				array(
					'id'         => 2,
					'name'       => __( 'Expected Close Date', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'expected_close_date',
					'tag'        => 'expected_close_date',
				),
				array(
					'id'         => 3,
					'name'       => __( 'Person Name', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'person_name',
					'tag'        => 'person_name',
				),
				array(
					'id'         => 4,
					'name'       => __( 'Person Email', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'person_email',
					'tag'        => 'person_email',
				),
				array(
					'id'         => 5,
					'name'       => __( 'Person Phone', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'person_phone',
					'tag'        => 'person_phone',
				),
			);
		} else {
			$fld = array(
				array(
					'id'         => 0,
					'name'       => __( 'Email Address', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'email',
					'tag'        => 'email',
				),
				array(
					'id'         => 1,
					'name'       => __( 'First Name', 'everest-forms-pipedrive' ),
					'req'        => true,
					'field_type' => 'first_name',
					'tag'        => 'first_name',
				),
				array(
					'id'         => 2,
					'name'       => __( 'Last Name', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'last_name',
					'tag'        => 'last_name',
				),

				array(
					'id'         => 3,
					'name'       => __( 'Phone Number', 'everest-forms-pipedrive' ),
					'req'        => false,
					'field_type' => 'phone',
					'tag'        => 'phone',
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
		return $this->error( esc_html__( 'Pipedrive won\'t support Groups.', 'everest-forms-pipedrive' ) );
	}

	/**
	 * Pipedrive custom field lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 *
	 * @return mixed array or error object
	 */
	public function api_custom_field( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {
			if ( 'person' === $list_id ) {
				$custom_fields = $this->api->get_person_fields();
			} elseif ( 'lead' === $list_id ) {
				// TO DO: Commented out for now as pipedrive latest api does not support it needs to upgrade on it accordingly.
				// $custom_fields = $this->api->get_lead_fields();
				$custom_fields = array();
			}

			if ( is_wp_error( $custom_fields ) ) {
				return false;
			}
			$fld = array();
			if ( ! empty( $custom_fields['data'] ) ) {
				foreach ( $custom_fields['data'] as $key => $custom_field ) {
					$fld[ $custom_field['name'] ] = array(
						'id'         => $custom_field['key'],
						'name'       => isset( $custom_field['name'] ) ? trim( $custom_field['name'] ) : __( 'Unknown Merge Variables', 'everest-forms-pipedrive' ),
						'field_type' => $custom_field['field_type'],
					);
				}
			}
			return $fld;
		} catch ( \Exception $e ) {

			evf_get_logger(
				__( 'Pipedrive API error', 'everest-forms-pipedrive' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
	}
}
