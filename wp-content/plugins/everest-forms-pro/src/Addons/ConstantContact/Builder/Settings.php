<?php
/**
 * Constant Contact Marketing Class.
 *
 * @package EverestForms\Pro\Addons\ConstantContact\Builder
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\ConstantContact\Builder;

use EverestForms\Pro\Addons\ConstantContact\API\API;
use EverestForms\Pro\Addons\ConstantContact\Settings\Settings as Constant_Contact_Integration;

defined( 'ABSPATH' ) || exit;


/**
 * Settings class.
 */
class Settings extends \EVF_Email_Marketing {

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'constant_contact';
		$this->name = __( 'Constant Contact', 'everest-forms-constant-contact' );
		$this->icon = plugins_url( 'src/Addons/ConstantContact/assets/img/constant-contact.jpg', EFP_PLUGIN_FILE );

		parent::__construct();

		add_filter( 'everest_forms_constant_contact_add_account_html', array( $this, 'outputs_authentication_html' ) );
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
			if ( empty( $connection['fields']['email_address'] ) ) {
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

			// Setup basic data.
			$account_id = $connection['account_id'];
			$list_id    = $connection['list_id'];
			$email_data = explode( '.', $connection['fields']['email_address'] );
			$email_id   = $email_data[0];
			$api        = $this->api_connect( $account_id );
			$data       = array();

			// Bail if there is any sort of issues with the API connection.
			if ( is_wp_error( $api ) ) {
				continue;
			}

			// Email is required.
			if ( empty( $fields[ $email_id ]['value'] ) ) {
				continue;
			} else {
				$data['email_address'] = array(
					'address'            => strtolower( $fields[ $email_id ]['value'] ),
					'permission_to_send' => 'explicit',
				);
			}

			$street_addresses = array();

			// Setup list fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {

				// Don't include email merge fields.
				if ( 'email_address' === $name ) {
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
							$value = str_replace( '<br />', ', ', nl2br( $value ) );
							break;
						case 'country':
							$value = apply_filters( 'everest_forms_plaintext_field_value', $value['country_code'], $value, $form_data, 'email-plain' );
							break;
						default:
							$value = implode( ' ', $value );
							break;
					}
				}

				if ( 'street' === $name ) {
					$street_addresses['street'] = $value;
				} elseif ( 'city' === $name ) {
					$street_addresses['city'] = $value;
				} elseif ( 'state' === $name ) {
					$street_addresses['state'] = $value;
				} elseif ( 'postal_code' === $name ) {
					$street_addresses['postal_code'] = $value;
				} elseif ( 'country' === $name ) {
					$street_addresses['country'] = $value;
				} else {
					switch ( $name ) {
						case 'home_number':
						case 'mobile_number':
						case 'work_number':
							$data['phone_numbers'][] = array(
								'phone_number' => $value,
								'kind'         => str_replace( '_number', '', $name ),
							);
							break;
						default:
							$data[ $name ] = $value;
							break;
					}
				}
			}

			if ( ! empty( $street_addresses ) ) {
				$street_addresses['kind'] = 'home';
				$data['street_addresses'] = array( $street_addresses );
			}

			$data['list_memberships'] = array( $list_id );

			// Setup custom Fields.
			if ( isset( $connection['custom_field_value'] ) && ! empty( $connection['custom_field_value'] ) ) {
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
						'field' => $connection['custom_field'][ $name ],
						'value' => $value,
					);
				}
			}

			if ( isset( $custom_field_value ) && ! empty( $custom_field_value ) && is_array( $custom_field_value ) ) {
				foreach ( $custom_field_value as $custom_field ) {
					$data['custom_fields'][] = array(
						'custom_field_id' => $custom_field['field'],
						'value'           => $custom_field['value'],
					);
				}
			}

			$contact = $api->is_contact_exists( $data['email_address']['address'] );

			// Send to API, if contact already not exists.
			if ( ! isset( $contact['email_address']['address'] ) ) {
				// Create new contact.
				$data['create_source'] = 'Contact';
				$response              = $api->make_request( '/contacts', $data, 'POST' );
			} else {
				return;
			}

			if ( is_wp_error( $response ) ) {
				return;
			}

			if ( isset( $response[0]['error_message'] ) ) {
				$error_msg = ! empty( $response[0]['error_message'] ) ? $response[0]['error_message'] : __( 'Error creating contact.', 'everest-forms-constant-contact' );
				self::log( $error_msg, 'error' );
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

			$connected_accounts['constant_contact'][ $api_key_to_be_connected ] = $settings;
			update_option( 'everest_forms_integrations', $connected_accounts );

			return $api_key_to_be_connected;
		} else {
			return $this->error( __( 'Could not verify API key', 'everest-forms-constant-contact' ) );
		}
	}
	/**
	 * Connect to API.
	 *
	 * @param  string $account_id  Account ID for Constant Contact.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['refresh_token'] ) ) {
				$connection    = $providers[ $this->id ][ $account_id ];
				$this->account = $account_id;
				$this->api     = new API( $connection, $account_id );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-constant-contact' ) );
			}
		}
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for Constant Contact.
	 * @param string $account_id    Account ID for Constant Contact.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {

		$this->api_connect( $account_id );

		try {
			$lists = $this->get_lists();

			if ( ! empty( $lists ) ) {
				return $lists;
			} else {
				$error_msg = __( 'API form error: No list found', 'everest-forms-constant-contact' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			/* translators: %s: Constant Contact API Error, exception encountered. */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-constant-contact' ), $e->getMessage() );
			self::log( $error_msg, 'error' );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Fetch API Groups.
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $list_id List ID.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'Constant Contact won\'t support Groups.', 'everest-forms-constant-contact' ) );
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

			$fld[0]  = array(
				'id'         => 0,
				'name'       => __( 'Email Address', 'everest-forms-constant-contact' ),
				'req'        => true,
				'field_type' => 'email',
				'tag'        => 'email_address',
			);
			$fld[1]  = array(
				'id'         => 1,
				'name'       => __( 'First Name', 'everest-forms-constant-contact' ),
				'req'        => false,
				'field_type' => 'first',
				'tag'        => 'first_name',
			);
			$fld[2]  = array(
				'id'         => 2,
				'name'       => __( 'Last Name', 'everest-forms-constant-contact' ),
				'req'        => false,
				'field_type' => 'last',
				'tag'        => 'last_name',
			);
			$fld[3]  = array(
				'id'         => '3',
				'field_type' => 'text',
				'name'       => __( 'Job Title', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'job_title',
			);
			$fld[4]  = array(
				'id'         => '4',
				'field_type' => 'text',
				'name'       => __( 'Company Name', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'company_name',
			);
			$fld[5]  = array(
				'id'         => '5',
				'field_type' => 'phone',
				'name'       => __( 'Home Phone Number', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'home_number',
			);
			$fld[6]  = array(
				'id'         => '6',
				'field_type' => 'phone',
				'name'       => __( 'Mobile Phone Number', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'mobile_number',
			);
			$fld[7]  = array(
				'id'         => '7',
				'field_type' => 'phone',
				'name'       => __( 'Work Phone Number', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'work_number',
			);
			$fld[8]  = array(
				'id'         => '8',
				'field_type' => 'address',
				'name'       => __( 'Street', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'street',
			);
			$fld[9]  = array(
				'id'         => '9',
				'field_type' => 'address',
				'name'       => __( 'City', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'city',
			);
			$fld[10] = array(
				'id'         => '10',
				'field_type' => 'address',
				'name'       => __( 'State', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'state',
			);
			$fld[11] = array(
				'id'         => '11',
				'field_type' => 'address',
				'name'       => __( 'ZIP Code', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'postal_code',
			);
			$fld[12] = array(
				'id'         => '12',
				'field_type' => 'address',
				'name'       => __( 'Country', 'gravityformsconstantcontact' ),
				'required'   => false,
				'tag'        => 'country',
			);

			return $fld;

		} catch ( \Exception $e ) {

			self::log( $$e->getMessage(), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-constant-contact' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Get Integration custom field lists.
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
			$response = $this->api->get_custom_fields();

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$fld = array();

			if ( isset( $response['custom_fields'] ) && ! empty( $response['custom_fields'] ) ) {

				foreach ( $response['custom_fields'] as $field ) {

					$fld[ $field['custom_field_id'] ] = array(
						'id'         => $field['custom_field_id'],
						/* translators: %s: Field Label. */
						'name'       => sprintf( esc_html__( ' % s', 'everest-forms-constant-contact' ), $field['label'] ),
						'req'        => false,
						'field_type' => $field['type'],
						'tag'        => $field['name'],
					);
				}
			}

				return $fld;

		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			self::log( sprintf( __( 'Constant Contact API custom fields error: % s', 'everest-forms-constant-contact' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'Constant Contact API custom fields error: % s', 'everest-forms-constant-contact' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Get All contact lists.
	 */
	public function get_lists() {
		$response     = $this->api->get_contact_lists();
		$list_options = array();

		if ( is_wp_error( $response ) ) {
			return $list_options;
		}

		if ( isset( $response['lists'] ) && ! empty( $response['lists'] ) ) {
			$lists = $response['lists'];
			foreach ( $lists as $list ) {

				$list_options[ $list['list_id'] ] = array(
					'id'   => $list['list_id'],
					'name' => $list['name'],
				);
			}
		}
		return $list_options;
	}

	/**
	 * Output Authentication HTML.
	 *
	 * @param string $output Output HTML.
	 */
	public function outputs_authentication_html( $output ) {
		ob_start();
		?>
		<button type="submit" class="everest-forms-hidden everest-forms-btn everest-forms-btn-primary" data-source="<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'Connect to Constant Contact', 'everest-forms-constant-contact' ); ?></button>
		<a href="<?php echo esc_url( filter_var( Constant_Contact_Integration::create_auth_url(), FILTER_SANITIZE_URL ) ); ?>"" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window" data-source="<?php echo esc_attr( $this->id ); ?>">
			<?php esc_html_e( 'Get Access Code', 'everest-forms-constant-contact' ); ?>
		</a>
		<?php
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( empty( self::$log ) ) {
			self::$log = evf_get_logger();
		}
		self::$log->log( $level, $message, array( 'source' => 'constant-contact' ) );
	}
}
