<?php
/**
 * Aweber Settings.
 *
 * @package EverestForms\Pro\Addons\Aweber\Builder
 * @since   1.7.8
 */

namespace  EverestForms\Pro\Addons\Aweber\Builder;

use EverestForms\Pro\Addons\Aweber\API\API;
use EverestForms\Pro\Addons\Aweber\Settings\Settings as AweberIntegrations;

defined( 'ABSPATH' ) || exit;


/**
 * Aweber Integration.
 */
class Builder extends  \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'aweber';
		$this->name = __( 'AWeber', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Aweber/assets/images/aweber.webp', EFP_PLUGIN_FILE );

		parent::__construct();
		add_filter( 'everest_forms_aweber_add_account_html', array( $this, 'outputs_authentication_html' ) );
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
			// Before proceeding make sure required fields are configured.
			if ( empty( $connection['fields']['email'] ) ) {
				continue;
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
			$email_data = explode( '.', $connection['fields']['email'] );
			$email_id   = $email_data[0];
			$api        = $this->api_connect( $account_id );

			if ( is_wp_error( $api ) ) {
				continue;
			}

			// Email is required.
			if ( empty( $fields[ $email_id ]['value'] ) ) {
				continue;
			} else {
				$data['email'] = strtolower( $fields[ $email_id ]['value'] );
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

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				try {
					$list_id  = $connection['list_id'];
					$response = $this->api->add_subscriber( $list_id, $data, $form_data['id'], $entry_id );

					if ( isset( $custom_field_value ) && ! empty( $custom_field_value ) ) {
						$data['custom_fields'] = $custom_field_value;
					}

					if ( isset( $response['error'] ) ) {
						$logger = evf_get_logger();
						$logger->error(
							sprintf(
								esc_html__( 'AWeber Error: %s', 'everest-forms-pro' ),
								isset( $response['error']['message'] ) ? $response['error']['message'] : ''
							),
							array(
								'source' => 'aweber'
							)
						);
					}
				} catch ( Exception $ex ) {
					$logger = evf_get_logger();
					$logger->log(
						sprintf( esc_html__( 'AWeber error: ', 'everest-forms-pro' ) . $ex->getMessage() ),
						array( 'source' => 'aweber' )
					);
				}
			}

			endforeach;
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
		$connection = array(
			'client_id'     => trim( $data['client_id'] ),
			'client_secret' => trim( $data['client_secret'] ),
			'auth_code'     => trim( $data['auth_code'] ),
		);

		$client   = new API( $connection );
		$settings = $client->create_access_token( $connection['auth_code'], $connection );

		if ( ! is_wp_error( $settings ) ) {
			$settings['status']      = true;
			$settings['label']       = sanitize_text_field( $data['label'] );
			$settings['date']        = time();
			$api_key_to_be_connected = uniqid();
			$connected_accounts      = get_option( 'everest_forms_integrations', array() );
			$client->account_key     = $api_key_to_be_connected;
			$connected_accounts['aweber'][ $api_key_to_be_connected ] = $settings;
			update_option( 'everest_forms_integrations', $connected_accounts );

			return $api_key_to_be_connected;
		} else {
			return $this->error( __( 'Could not verify API key', 'everest-forms-pro' ) );
		}

	}

		/**
		 * Connect to API.
		 *
		 * @param  string $account_id  Account ID for AWeber Contact.
		 * @return mixed array or error object
		 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$connection    = $providers[ $this->id ][ $account_id ]['api'];
				$this->account = $account_id;
				$this->api     = new API( $connection, $account_id );
				return $this->api;
			} elseif ( ! empty( $providers[ $this->id ][ $account_id ]['refresh_token'] ) ) {
				$connection    = $providers[ $this->id ][ $account_id ];
				$this->account = $account_id;
				$this->api     = new API( $connection, $account_id );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
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
			if ( ! empty( $lists['entries'] ) ) {
				$list_array = array();
				foreach ( $lists['entries'] as $key => $list ) {
					if ( empty( $list ) ) {
						continue;
					}
					$list_array[ $list['id'] ] = array(
						'id'   => $list['id'],
						'name' => isset( $list['name'] ) ? trim( $list['name'] ) : __( 'Unknown List', 'everest-forms-pro' ),
					);
				}

				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'AWeber API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type'   => array( 'Integration', 'error' ),
					'source' => 'aweber'
				)
			);
		}

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
				'name'       => __( 'Name', 'everest-forms-pro' ),
				'req'        => false,
				'field_type' => 'name',
				'tag'        => 'name',
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
		return $this->error( esc_html__( 'AWeber won\'t support Groups.', 'everest-forms-pro' ) );
	}



	/**
	 * Fetch Integration account list fields.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 */
	public function api_custom_field( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );
		try {
			$fld   = array();
			$lists = $this->api->get_custom_fields( $list_id );
			if ( ! is_wp_error( $lists ) && ! empty( $lists ) ) {
				$exclude_fields = apply_filters( 'everest_forms_exclude_fields_in_aweber_list', array( 'email', 'firstname', 'lastname', 'phone' ) );
				foreach ( $lists as $key => $list ) {
					if ( in_array( $list['name'], $exclude_fields, true ) ) {
						continue;
					}

					$fld[ $list['name'] ] = array(
						'id'         => isset( $list['name'] ) ? $list['name'] : '',
						'name'       => isset( $list['name'] ) ? trim( $list['name'] ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'field_type' => 'string',
					);
				}
			}
			return $fld;
		} catch ( Exception $e ) {
			evf_get_logger(
				__( 'AWeber API field error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type'   => array( 'Integration', 'error' ),
					'source' => 'aweber'
				)
			);

		}
	}

	/**
	 * Output Authentication HTML.
	 *
	 * @param string $output Output HTML.
	 */
	public function outputs_authentication_html( $output ) {
		ob_start();
		?>
		<button type="submit" class="everest-forms-hidden everest-forms-btn everest-forms-btn-primary " data-source="<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'Connect to AWeber', 'everest-forms-pro' ); ?></button>
		<a href="<?php echo esc_url( filter_var( AweberIntegrations::create_auth_url(), FILTER_SANITIZE_URL ) ); ?>"" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window" data-source="<?php echo esc_attr( $this->id ); ?>">
			<?php esc_html_e( 'Get Access Code', 'everest-forms-pro' ); ?>
		</a>
		<?php
		$output = ob_get_clean();

		return $output;
	}

}
