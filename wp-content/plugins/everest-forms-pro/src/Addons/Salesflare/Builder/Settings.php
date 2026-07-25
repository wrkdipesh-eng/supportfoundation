<?php
/**
 * Salesflare Settings.
 *
 * @package EverestForms\Salesflare\Builder
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Salesflare\Builder;

use EverestForms\Pro\Addons\Salesflare\Api\Api;

defined( 'ABSPATH' ) || exit;


/**
 * Salesflare Integration.
 */
class Settings extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'salesflare';
		$this->name = __( 'Salesflare', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Salesflare/assets/images/Salesflare.png', EFP_PLUGIN_FILE );

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

			$api = $this->api_connect( $account_id );

			if ( is_wp_error( $api ) ) {
				continue;
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
				if ( ! empty( $custom_field_value ) ) {
					$data['custom_field'] = $custom_field_value;
					$data                 = array_merge( $custom_field_value, $data );
				}
			}
				$first_name           = ! empty( $data['first_name'] ) ? $data['first_name'] : '';
				$last_name            = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
				$data['name']         = $first_name . ' ' . $last_name;
				$address              = isset( $data['address'] ) ? $data['address'] : '';
				$data['phone_number'] = isset( $data['phone'] ) ? $data['phone'] : '';

				$lines = explode( "\n", $address );

				$addressLine1 = ( isset( $lines[0] ) ) ? $lines[0] : '';
				$addressLine2 = ( isset( $lines[1] ) ) ? $lines[1] : '';
				$cityIndex    = array_search( 'City', $lines, true );
				$city         = ( ( false !== $cityIndex ) && isset( $lines[ $cityIndex + 1 ] ) ) ? $lines[ $cityIndex + 1 ] : '';
				$state        = ( ( false !== $cityIndex ) && isset( $lines[ $cityIndex + 2 ] ) ) ? $lines[ $cityIndex + 2 ] : '';
				$zip          = ( isset( $lines[ $cityIndex + 3 ] ) ) ? $lines[ $cityIndex + 3 ] : '';
				$country      = ( isset( $lines[ $cityIndex + 4 ] ) ) ? $lines[ $cityIndex + 4 ] : '';

				$custom_field_data   = array();
				$custom_fields_types = $this->api->get_custom_fields();
				$custom_fields_data  = array();

			foreach ( $custom_fields_types as $field ) {
				$id   = isset( $field['id'] ) ? $field['id'] : 0;
				$type = isset( $field['api_field'] ) ? $field['api_field'] : '';

				if ( isset( $data['custom_field'][ $id ] ) ) {
					$value                   = $data['custom_field'][ $id ];
					$data['custom'][ $type ] = $value;
				}
			}

			$data['address'] = array(
				array(
					'street'       => ! empty( $addressLine1 ) ? $addressLine1 : '',
					'city'         => ! empty( $city ) ? $city : '',
					'state_region' => ! empty( $state ) ? $state : '',
					'zip'          => ! empty( $zip ) ? $zip : '',
					'country'      => ! empty( $country ) ? $country : '',
				),
			);

			$data['address'] = (object) $data['address'][0];

			$resource = 'contacts';

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				$response = $api->send_request( $resource, $data, 'POST', $form_data['id'], $entry_id );
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
				__( 'Salesflare API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}


	/**
	 * Get Integration account organization for salesflare.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_organization( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$organization = $this->api->get_organization();
			if ( ! empty( $organization['data'] ) ) {
				$org_array = array();
				foreach ( $organization['data'] as $org ) {
					if ( empty( $org['id'] ) ) {
						continue;
					}
					$org_array[ $org['id'] ] = array(
						'id'   => $org['id'],
						'name' => isset( $org['name'] ) ? trim( $org['name'] ) : __( 'Unknown Organization', 'everest-forms-pro' ),
					);
				}

				return $org_array;
			} else {
				$error_msg = __( 'API list error: No Organization found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Salesflare API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}

	/**
	 * Get Integration account label for salesflare.
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
								'name' => isset( $option['label'] ) ? trim( $option['label'] ) : __( 'Unknown Label', 'everest-forms-pro' ),
							);
						}
						$label_array = $option_array;
					}
				}
				return $label_array;
			} else {
				$error_msg = __( 'API list error: No Organization found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Salesflare API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}

	}


	/**
	 * Get Integration account label for salesflare.
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
								'name' => isset( $option['label'] ) ? trim( $option['label'] ) : __( 'Unknown Visibile', 'everest-forms-pro' ),
							);
						}
						$visbile_array = $vis_array;
					}
				}
				return $visbile_array;
			} else {
				$error_msg = __( 'API list error: No Organization found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Salesflare API error', 'everest-forms-pro' ),
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
						'name' => isset( $owner['name'] ) ? trim( $owner['name'] ) : __( 'Unknown Owner', 'everest-forms-pro' ),
					);
				}

				return $owner_array;
			} else {
				$error_msg = __( 'API list error: No owners found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Salesflare API error', 'everest-forms-pro' ),
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
						'name' => isset( $currency['name'] ) ? trim( $currency['name'] ) : __( 'Unknown Currency', 'everest-forms-pro' ),
					);
				}

				return $currency_array;
			} else {
				$error_msg = __( 'API list error: No currencies found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Salesflare API error', 'everest-forms-pro' ),
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
	 * @param  string $account_id  Account ID for Salesflare.
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
				$this->api     = new Api( $api_key );
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
		$api   = new Api( trim( $data['apikey'] ) );
		$valid = $api->auth_test();

		if ( isset( $valid['statusCode'] ) && 401 === $valid['statusCode'] ) {
			return $this->error( __( 'Could not verify API key', 'everest-forms-pro' ) );
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

			$fld = array(
				array(
					'id'         => 0,
					'name'       => __( 'Email Address', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'email',
					'tag'        => 'email',
				),
				array(
					'id'         => 1,
					'name'       => __( 'First Name', 'everest-forms-pro' ),
					'req'        => true,
					'field_type' => 'first_name',
					'tag'        => 'first_name',
				),
				array(
					'id'         => 2,
					'name'       => __( 'Last Name', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'last_name',
					'tag'        => 'last_name',
				),
				array(
					'id'         => 3,
					'name'       => __( 'Phone Number', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'phone',
					'tag'        => 'phone',
				),
				array(
					'id'         => 3,
					'name'       => __( 'Address', 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => 'address',
					'tag'        => 'address',
				),
			);
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
		return $this->error( esc_html__( 'Salesflare won\'t support Groups.', 'everest-forms-pro' ) );
	}

	/**
	 * Salesflare custom field lists.
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
			$custom_fields = $this->api->get_custom_fields();

			if ( is_wp_error( $custom_fields ) ) {
				return false;
			}
			$fld = array();
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $key => $custom_field ) {
					$fld[ $custom_field['name'] ] = array(
						'id'         => $custom_field['id'],
						'name'       => isset( $custom_field['name'] ) ? trim( $custom_field['name'] ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'field_type' => $custom_field['type']['type'],
					);
				}
			}
			return $fld;
		} catch ( \Exception $e ) {

			evf_get_logger(
				__( 'Salesflare API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
	}
}
