<?php
/**
 * MailerLite Marketing class.
 *
 * @package EverestForms\Pro\Addons\Mailerlite\Builder
 * @version 1.0.0
 * @since   1.7.7
 */
namespace EverestForms\Pro\Addons\Mailerlite\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * MailerLite Marketting class.
 */
class Builder extends \EVF_Email_Marketing {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'mailerlite';
		$this->name = __( 'MailerLite', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Mailerlite/assets/img/mailerlite.png', EFP_PLUGIN_FILE );

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
							$value = str_replace( '<br />', ', ', nl2br( $value ) );
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}

				// Special formatting for different types of data.
				switch ( $type ) {
					case 'birthday':
						if ( ! empty( $form_data['form_fields'][ $id ]['datetime_format'] ) && 'date' === $form_data['form_fields'][ $id ]['datetime_format'] ) {
							$date  = DateTime::createFromFormat( $form_data['form_fields'][ $id ]['date_format'], $value );
							$value = $date->format( 'm/d' );

							if ( 'd/m/Y' === $form_data['form_fields'][ $id ]['date_format'] ) {
								$value = $date->format( 'd/m' );
							}
						}
						break;
				}

				$data['fields'][ $name ] = $value;
			}
			$data = (object) $data;

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				$this->api->groups()->addSubscriber( $connection['list_id'], $data );
			}
		endforeach;
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 *
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		if ( ! class_exists( '\MailerLiteApi\MailerLite' ) ) {
			require_once dirname( EVF_MAILERLITE_PLUGIN_FILE ) . '/includes/vendor/autoload.php';
		}
		try {
			$auth = new \MailerLiteApi\MailerLite( trim( $data['apikey'] ) );
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}
		if ( is_wp_error( $auth ) ) {
			$details = true === $auth ? $auth : __( 'Could not verify API URL', 'everest-forms-pro' );
			/* translators: %s: Error thrown by API authentication issues. */
			EverestForms_MailerLite::log( sprintf( __( 'MailerLite API error: %s', 'everest-forms-pro' ), $details ), 'error' );
			/* translators: %s: Error thrown by API authentication issues. */
			$error_msg = sprintf( __( 'API auth error: %s', 'everest-forms-pro' ), $details );
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $error_msg,
				)
			);
		}

		$to_be_stored_id    = uniqid();
		$connected_accounts = get_option( 'everest_forms_integrations', array() );

		$connected_accounts['mailerlite'][ $to_be_stored_id ] = array(
			'api'   => trim( $data['apikey'] ),
			'label' => sanitize_text_field( $data['label'] ),
			'date'  => time(),
		);
		update_option( 'everest_forms_integrations', $connected_accounts );

		return $id;
	}

	/**
	 * Connect to API.
	 *
	 * @param  string $account_id  Account ID for ActiveCampaign.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! class_exists( '\MailerLiteApi\MailerLite' ) ) {
			require_once dirname( EVF_MAILERLITE_PLUGIN_FILE ) . '/includes/vendor/autoload.php';
		}
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );
			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$this->account = $account_id;
				$this->api     = new \MailerLiteApi\MailerLite( $providers[ $this->id ][ $account_id ]['api'] );
				if ( is_wp_error( $this->api ) ) {
					return;
				}
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
		if ( is_wp_error( $this->api ) ) {
			return;
		}
		try {
			$lists = $this->api->groups()->get();
			if ( ! is_wp_error( $lists ) ) {
				$list_array = array();
				foreach ( $lists as $list ) {
					if ( empty( $list->id ) ) {
						continue;
					}
					$list_array[ $list->id ] = array(
						'id'   => $list->id,
						'name' => isset( $list->name ) ? trim( $list->name ) : __( 'Unknown List', 'everest-forms-pro' ),
					);
				}

				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( Exception $e ) {
			/* translators: %s: API Authentication Error. */
			EverestForms_MailerLite::log( sprintf( __( 'ActiveCampaign API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {
			$fields = $this->api->fields()->get();
			$fld    = array();
			if ( ! is_wp_error( $fields ) ) {
				foreach ( $fields as $field ) {
					$fld[ $field->id ] = array(
						'id'         => $field->id,
						'name'       => isset( $field->title ) ? trim( $field->title ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'req'        => '',
						'field_type' => $field->type,
						'tag'        => $field->key,
					);
				}
			}

			return $fld;

		} catch ( Exception $e ) {

			/* translators: %s: API Authentication Error. */
			EverestForms_MailerLite::log( sprintf( __( 'MailerLite API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}
}
