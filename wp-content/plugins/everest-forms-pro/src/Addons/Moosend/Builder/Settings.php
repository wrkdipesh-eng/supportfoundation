<?php
/**
 * Moosend Settings.
 *
 * @package EverestForms\Moosend\Builder
 * @since   1.0.0
 */

 namespace  EverestForms\Pro\Addons\Moosend\Builder;

 use EverestForms\Pro\Addons\Moosend\Api\Api;

defined( 'ABSPATH' ) || exit;


/**
 * Moosend Integration.
 */
class Settings extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'moosend';
		$this->name = __( 'Moosend', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Moosend/assets/img/Moosend.png', EFP_PLUGIN_FILE );

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
			$email_data = explode( '.', $connection['fields']['email'] );
			$email_id   = $email_data[0];
			$api        = $this->api_connect( $account_id );

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

				// Check if the merge field is not email, first_name, or last_name. to create custom fields.
				if ( ! in_array( $name, array( 'email', 'first_name', 'last_name','phone' ) ) ) {
					$custom_data[ $name ] = $value;
				}
			}
			$data['customFields'] = isset( $custom_data ) ? $custom_data : '';
			$first_name           = ! empty( $data['first_name'] ) ? $data['first_name'] : '';
			$last_name            = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
			$data['name']         = $first_name . $last_name;
			$data['Mobile']       = ! empty( $data['phone'] ) ? $data['phone'] : '';
			$connection_list_id   = $connection['list_id'];
			$resource             = 'subscribers/' . $connection_list_id . '/subscribe.json';

			$this->api->add_subscriber( $connection_list_id, $data );

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
			$lists = $this->api->get_lists();
			if ( ! empty( $lists ) ) {
				$list_array = array();
				foreach ( $lists as $key => $list ) {
					$list_array[ $key ] = array(
						'id'   => $list['ID'],
						'name' => isset( $list['Name'] ) ? $list['Name'] : __( 'Unknown list', 'everest-forms-pro' ),
					);
				}
				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Moosend API error', 'everest-forms-pro' ),
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
		if ( isset( $valid['Code'] ) && 104 === $valid['Code'] ) {

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
		$this->api_connect( $account_id );
		try {
			$list_id = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : $list_id; //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$fields  = $this->api->get_list( $list_id );
			if ( ! empty( $fields ) ) {
				if ( ! empty( $fields['CustomFieldsDefinition'] ) ) {
					$custom_field_type = array(
						0 => 'text',
						1 => 'number',
						2 => 'date',
						3 => 'dropdown',
						5 => 'checkbox',
					);

					$custom_field_type = array_replace( $custom_field_type, array_fill_keys( array_keys( $custom_field_type, 'dropdown' ), 'select' ) );

					$custom_tag = array(
						'Text'     => 'Text',
						'Number'   => 'Number',
						'Date'     => 'Date',
						'Select'   => 'Select',
						'Checkbox' => 'Checkbox',
					);

					$fld = array(
						array(
							'id'         => 0,
							'name'       => __( 'Email Address', 'everest-forms-pro' ),
							'req'        => true,
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
							'name'       => __( 'Phone', 'everest-forms-pro' ),
							'req'        => false,
							'field_type' => 'phone',
							'tag'        => 'phone',
						),
					);

					foreach ( $fields['CustomFieldsDefinition'] as $index => $field ) {
						$fieldType = isset( $custom_field_type[ $field['Type'] ] ) ? $custom_field_type[ $field['Type'] ] : '';

						$tagName = lcfirst( $field['Name'] );

						$custom_fields[] = array(
							'id'         => $field['ID'],
							'name'       => $tagName,
							'req'        => $field['IsRequired'],
							'field_type' => $fieldType,
							'tag'        => $tagName,
						);
					}
					$fld = array_merge( $fld, $custom_fields );
				} else {
					$fld = array(
						array(
							'id'         => 0,
							'name'       => __( 'Email Address', 'everest-forms-pro' ),
							'req'        => true,
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
							'name'       => __( 'Phone', 'everest-forms-pro' ),
							'req'        => false,
							'field_type' => 'phone',
							'tag'        => 'phone',
						),
					);
				}
			} else {
				return $this->error( esc_html__( 'Unknown list ID', 'everest-forms-pro' ) );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Moosend API Data field error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
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
		return $this->error( esc_html__( 'Moosend won\'t support Groups.', 'everest-forms-pro' ) );
	}

}
