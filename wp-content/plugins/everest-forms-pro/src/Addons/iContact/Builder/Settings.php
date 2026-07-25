<?php
/**
 * iContact Settings.
 *
 * @package EverestForms\iContact\Builder
 * @since   1.0.0
 */

 namespace  EverestForms\Pro\Addons\iContact\Builder;

 use EverestForms\Pro\Addons\iContact\Api\Api;

defined( 'ABSPATH' ) || exit;

/**
 * IContact Integration.
 */
class Settings extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'icontact';
		$this->name = __( 'iContact', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/iContact/assets/img/iContact.png', EFP_PLUGIN_FILE );

		parent::__construct();
	}

	/**
	 * Get Integration account lists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 * @param string $board_id      Board ID for fetching the Board lists.
	 */
	public function api_lists( $connection_id = '', $account_id = '', $board_id = '' ) {
		$api = $this->api_connect( $account_id );
		try {
			$lists = $api->get_lists();

			if ( ! empty( $lists ) ) {
				$list_array = array();
				foreach ( $lists as $key => $list ) {
					$list_array[ $key ] = array(
						'id'   => $list['listId'],
						'name' => isset( $list['name'] ) ? $list['name'] : __( 'Unknown list', 'everest-forms-pro' ),
					);
				}
				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'iContact API error', 'everest-forms-pro' ),
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
	 * @since 1.0.0
	 *
	 * @param  string $account_id  Account ID.
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$api_key   = $providers[ $this->id ][ $account_id ]['api'];
				$username  = $providers[ $this->id ][ $account_id ]['username'];
				$password  = $providers[ $this->id ][ $account_id ]['apipassword'];
				$folderid  = $providers[ $this->id ][ $account_id ]['folderid'];
				$accountid = $providers[ $this->id ][ $account_id ]['accountid'];

				$this->api = new Api( $api_key, $username, $password, $folderid, $accountid );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$api = $this->api_connect( $account_id );
		try {
			$list_id = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : $list_id; //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$fields  = $api->get_list( $list_id );
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
							'req'        => true,
							'field_type' => 'last_name',
							'tag'        => 'last_name',
						),
						array(
							'id'         => 3,
							'name'       => __( 'Address Line', 'everest-forms-pro' ),
							'req'        => true,
							'field_type' => 'ic_address',
							'tag'        => 'ic_address',
						),
						array(
							'id'         => 4,
							'name'       => __( 'Phone Number', 'everest-forms-pro' ),
							'req'        => true,
							'field_type' => 'phone',
							'tag'        => 'phone',
						),
						array(
							'id'         => 5,
							'name'       => __( 'Prefix', 'everest-forms-pro' ),
							'req'        => true,
							'field_type' => 'prefix',
							'tag'        => 'prefix',
						),
						array(
							'id'         => 6,
							'name'       => __( 'Suffix', 'everest-forms-pro' ),
							'req'        => true,
							'field_type' => 'suffix',
							'tag'        => 'suffix',
						),
					);
				}
			} else {
				return $this->error( esc_html__( 'Unknown list ID', 'everest-forms-pro' ) );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'iContact API Data field error', 'everest-forms-pro' ),
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
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $list_id List ID.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'iContact won\'t support Groups.', 'everest-forms-pro' ) );
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

				$value = 'ic_address' === $type ? $fields[ $id ] : $fields[ $id ][ $key ];

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
							$country            = evf_get_countries();
							$data['street']     = $value['address1'];
							$data['street2']    = $value['address2'];
							$data['city']       = $value['city'];
							$data['state']      = $country[ $value['country'] ];
							$data['postalCode'] = $value['postal'];
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}

				$name = 'first_name' === $name ? 'firstName' : $name;
				$name = 'last_name' === $name ? 'lastName' : $name;
				$name = 'phone_number' === $name ? 'phone' : $name;

				if ( 'ic_address' != $type ) {
					$data[ $name ] = $value;
				}
			}

			if ( ! empty( $custom_field_value ) ) {
				$data = array_merge( $custom_field_value, $data );
			}

			$connection_list_id = $connection['list_id'];
			$contact_id         = $this->contact_sync( $data, $account_id );

			if ( $contact_id ) {
				$this->add_subscription( $contact_id, $connection_list_id, $account_id );
			} else {
				return;
			}

			endforeach;
	}

	/**
	 * Subscribe the contact to the list and log the result.
	 *
	 * @param mixed $contactId  The ID of the contact.
	 * @param mixed $listId     The ID of the list to subscribe to.
	 * @param mixed $account_id Account ID for fetching the account lists.
	 */
	private function add_subscription( $contactId, $listId, $account_id ) {

		$logger       = new \EVF_Logger();

		try {
			$api = $this->api_connect( $account_id );

			/* Subscribe the contact to the list. */
			$subscription = $api->add_subscription( $contactId, $listId );
			return true;
		} catch ( \Exception $e ) {
			$logger->log( 'info', $e->getMessage() );
			return false;
		}

	}

	/**
	 * Synchronize a contact with the iContact API.
	 *
	 * This function checks if a contact with the given email exists in the iContact
	 * account. If it does not exist, a new contact is added. If it does exist, the
	 * contact is updated.
	 *
	 * @param array  $contact The contact data to be synchronized.
	 * @param string $account_id The ID of the iContact account.
	 */
	private function contact_sync( $contact, $account_id ) {

		$api = $this->api_connect( $account_id );

		/* Check to see if we're adding a new contact. */
		$find_contact = $api->get_contact_by_email( $contact['email'] );

		$is_new_contact = empty( $find_contact );

		$logger = new \EVF_Logger();

		if ( $is_new_contact ) {
			try {
				$response = $api->add_contact( $contact );
				return $response['contactId'];
			} catch ( \Exception $e ) {
				$logger->log( 'info', $e->getMessage() );
			}
		} else {
			try {
				$contact_id = $find_contact[0]['contactId'];
				/* Update the contact. */
				$api->update_contact( $contact_id, $contact );
				return $contact_id;
			} catch ( \Exception $e ) {
				$logger->log( 'info', $e->getMessage() );
			}
		}
	}

	/**
	 * iContact custom field lists.
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
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $custom_field ) {
					$fld[ $custom_field['publicName'] ] = array(
						'id'         => $custom_field['customFieldId'],
						'name'       => isset( $custom_field['publicName'] ) ? trim( $custom_field['publicName'] ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'field_type' => $custom_field['fieldType'],
					);
				}
			}
			return $fld;
		} catch ( \Exception $e ) {

			evf_get_logger(
				__( 'iContact API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		$api = new Api( trim( $data['apikey'] ), $data['email'], $data['apipassword'], $data['folderid'], $data['accountid'] );

		$response = $api->auth_test( 'test' );

		if ( ! $response || isset( $response['errors'] ) || isset( $response['warnings'] ) ) {
			return $this->error( __( isset( $response['errors'][0] ) ? $response['errors'][0] : $response['warnings'][0], 'everest-forms-pro' ) );
		}

		$id           = uniqid();
		$integrations = get_option( 'everest_forms_integrations', array() );

		$integrations[ $this->id ][ $id ] = array(
			'api'         => trim( $data['apikey'] ),
			'label'       => sanitize_text_field( $data['label'] ),
			'username'    => sanitize_email( $data['email'] ),
			'apipassword' => sanitize_text_field( $data['apipassword'] ),
			'folderid'    => sanitize_text_field( $data['folderid'] ),
			'accountid'   => sanitize_text_field( $data['accountid'] ),
			'date'        => time(),
		);

		update_option( 'everest_forms_integrations', $integrations );

		return $id;
	}

}
