<?php
/**
 * Sendinblue Marketing Class.
 *
 * @package EverestForms\Pro\Addons\Brevo\Settings\Setting
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Brevo\Builder;

use EverestForms\Pro\Addons\Brevo\API\API;

defined( 'ABSPATH' ) || exit;


/**
 * Sendinblue Marketting class.
 */
class SendinblueMarketing extends \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'sendinblue';
		$this->name = __( 'Brevo', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Brevo/assets/img/brevo.png', EFP_PLUGIN_FILE );

		parent::__construct();

		// Hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );
		add_action( 'everest_forms_save_form', array( $this, 'validate_form' ), 10, 4 );
	}

	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_register_script( 'everest-forms-brevo-scripts', plugins_url( 'src/Addons/Brevo/assets/js/admin/admin' . $suffix . '.js', EFP_PLUGIN_FILE ), array( 'jquery' ), '2.3.1', true );
			wp_enqueue_script( 'everest-forms-brevo-scripts' );
		}
	}

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

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
						} elseif ( $entry['form_fields'][ $con_field_select ] !== $con_field_choice ) {
								continue;
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
						} elseif ( $entry['form_fields'][ $con_field_select ] === $con_field_choice ) {
								continue;
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
				$data['email'] = strtolower( $fields[ $email_id ]['value'] );
			}

			$data['listIds'] = array( absint( $list_id ) );

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
				$attributes = new \stdClass();

				foreach ( $custom_field_value as $custom_field ) {
					$attributes->{$custom_field['field']} = $custom_field['value'];

				}

				$data['attributes'] = $attributes;
			}

			if ( checked( ! empty( $connection['options']['double_optin'] ), true, false ) ) {
				$data['includeListIds'] = array( absint( $list_id ) );
				$data['templateId']     = ! empty( $connection['double_optin_template_id'] ) ? apply_filters( 'everest_forms_brevo_doubleoptin_template', (int) $connection['double_optin_template_id'] ) : 0;
				$data['redirectionUrl'] = ! empty( $connection['double_optin_redirection_url'] ) ? apply_filters( 'everest_forms_brevo_redirectionUrl', $connection['double_optin_redirection_url'] ) : home_url();
				// Create contact with double optin confirmation.
				$response = $api->make_request( 'contacts/doubleOptinConfirmation', $data, 'POST', $form_data['id'], $entry_id );
			} else {
				// Create contact.
				$email          = isset( $data['email'] ) ? $data['email'] : '';
				$path           = 'contacts/' . $email;
				$email_response = $api->make_request( $path, array(), 'GET' );

				if ( isset( $email_response['code'] ) && 'document_not_found' === $email_response['code'] ) {
					// Create contact.
					$response = $api->make_request( 'contacts', $data, 'POST', $form_data['id'], $entry_id );
				} else {
					// Update the detail if exists.
					$response = $api->make_request( $path, $data, 'PUT', $form_data['id'], $entry_id );
				}
			}

			if ( is_wp_error( $response ) ) {
				return;
			}

			if ( isset( $response['message'] ) ) {
				$error_msg = ! empty( $response['message'] ) ? $response['message'] : __( 'Error while creating contact.', 'everest-forms-pro' );
				self::log( $error_msg, 'error' );
				return;
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
		$api = new API( trim( $data['apikey'] ) );

		if ( ! $api->validate_api_key() ) {
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
	 * Connect to API.
	 *
	 * @param  string $account_id  Account ID for Sendinblue.
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
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for Sendinblue.
	 * @param string $account_id    Account ID for Sendinblue.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {

			$lists = $this->api->get_lists();

			if ( ! empty( $lists['lists'] ) ) {
				$list_array = array();
				foreach ( $lists['lists'] as $list ) {
					if ( empty( $list['id'] ) ) {
						continue;
					}
					$list_array[ $list['id'] ] = array(
						'id'   => $list['id'],
						'name' => isset( $list['name'] ) ? trim( $list['name'] ) : __( 'Unknown List', 'everest-forms-pro' ),
					);
				}

				return $list_array;
			} else {
				$error_msg = __( 'API form error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			/* translators: %s: Sendinblue API Error, exception encountered. */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-pro' ), $e->getMessage() );
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
		return $this->error( esc_html__( 'Brevo won\'t support Groups.', 'everest-forms-pro' ) );
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

			$fld[0] = array(
				'id'         => 0,
				'name'       => __( 'Email Address', 'everest-forms-pro' ),
				'req'        => true,
				'field_type' => 'email',
				'tag'        => 'email_address',
			);

			return $fld;

		} catch ( \Exception $e ) {

			self::log( $$e->getMessage(), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

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
			$response = $this->api->get_attributes();

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$fld = array();

			if ( isset( $response['attributes'] ) && ! empty( $response['attributes'] ) ) {

				foreach ( $response['attributes'] as $field ) {

					$fld[ $field['name'] ] = array(
						'id'         => $field['name'],
						/* translators: %s: Field Label. */
						'name'       => sprintf( esc_html__( ' % s', 'everest-forms-pro' ), $field['name'] ),
						'req'        => false,
						'field_type' => isset( $field['type'] ) ? $field['type'] : '',
						'tag'        => $field['name'],
					);
				}
			}

			return $fld;

		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			self::log( sprintf( __( 'Brevo API attributes error: % s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'Brevo API attributes error: % s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Integration account list options HTML.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param array  $connection    Connection Object for account list rendering.
	 *
	 * @return string
	 */
	public function output_options( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) || empty( $connection['list_id'] ) ) {
			return '';
		}
		$response                     = $this->api->get_smtp_templates();
		$double_optin_template_id     = isset( $connection['double_optin_template_id'] ) ? (int) $connection['double_optin_template_id'] : 0;
		$double_optin_redirection_url = isset( $connection['double_optin_redirection_url'] ) ? $connection['double_optin_redirection_url'] : '';
		$output                       = '<div class="evf-provider-options everest-forms-addable-list evf-connection-block">';
		$output                      .= '<h4>' . __( 'Options', 'everest-forms-pro' ) . '</h4>';
		$output                      .= sprintf(
			'<p><input id="%s_options_doubleoptin double-optin-options" type="checkbox" value="1" name="integrations[%s][%s][options][double_optin]" class="evf-brevo-double-optin" %s><label for="%s_options_doubleoptin">%s</label></p>',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			checked( ! empty( $connection['options']['double_optin'] ), true, false ),
			esc_attr( $connection_id ),
			__( 'Enable Double Optin', 'everest-forms-pro' )
		);
		// check if the double optin is checked or not.
			$output .= '<div class="everest-forms-panel-field double-optin__wrapper everest-forms-hidden">';
			$output .= '<div class="everest-forms-panel-field double-optin-template"><h4>' . __( 'Double Optin Template', 'everest-forms-pro' ) . '</h2>';

		if ( ! isset( $response['templates'] ) ) {
			$output .= '<span>' . __( 'You don\'t have any opt-in template in Brevo yet.', 'everest-forms-pro' ) . '</span></div>';
		} else {
			$output .= sprintf( '<select class="everest-forms-sendinblue-field-choices-select" name="integrations[%s][%s][double_optin_template_id]">', $this->id, $connection_id );
			foreach ( $response['templates'] as $templates => $template ) {
				if ( isset( $template['id'], $template['name'], $template['isActive'], $template['tag'] ) ) {
					$id       = $template['id'];
					$name     = $template['name'];
					$isActive = $template['isActive'];
					$tag      = $template['tag'];
					$selected = '';
					if ( $double_optin_template_id === $id ) {
						$selected = 'selected="selected"';
					}

					if ( $isActive == 1 && $tag == 'optin' ) {
						$output .= '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
					}
				}
			}

			$output .= '</select></div>';
		}
			$output .= '<div class="everest-forms-panel-field double-optin-redirection-url"><h4>' . __( 'Redirection URL', 'everest-forms-pro' ) . '</h2>';
			$output .= sprintf(
				'<p><input id="%s_options_doubleoptin " class="widefat short" type="url" value="%s" name="integrations[%s][%s][double_optin_redirection_url]" ></p>',
				esc_attr( $connection_id ),
				esc_url( $double_optin_redirection_url ),
				esc_attr( $this->id ),
				esc_attr( $connection_id ),
			);
			$output .= '</div></div>';
		$output     .= '</div>';

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
		self::$log->log( $level, $message, array( 'source' => 'sendinblue' ) );
	}

	/**
	 * Return when redirection url is not valid in Brevo redirection url.
	 *
	 * @since 1.9.3
	 */
	public function validate_form( $form_id, $data, $array, $form_styles ) {
		$logger = evf_get_logger();

		if ( empty( $data['integrations']['sendinblue'] ) ) {
			return;
		}

		$url_pattern = '/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}([\/\?#][^\s]*)?$/';

		foreach ( $data['integrations']['sendinblue'] as $value ) {
			if ( ! empty( $value['options']['double_optin'] ) && ! empty( $value['double_optin_redirection_url'] ) ) {
				if ( ! preg_match( $url_pattern, $value['double_optin_redirection_url'] ) ) {
					$logger->error( __( 'Invalid URL', 'everest-forms' ), array( 'source' => 'form-save' ) );

					wp_send_json_error(
						array(
							'errorTitle'   => esc_html__( 'Invalid URL', 'everest-forms' ),
							'errorMessage' => esc_html__( 'Please add a valid URL on Brevo redirection URL', 'everest-forms' ),
						)
					);
				}
			}
		}
	}
}
