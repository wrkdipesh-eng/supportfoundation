<?php
/**
 * MailChimp Marketting class.
 *
 * @package EverestForms_MailChimp\Admin
 * @version 1.0.0
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Mailchimp\Builder;

use EverestForms\Pro\Addons\Mailchimp\MailChimp_EVF;

defined( 'ABSPATH' ) || exit;

/**
 * MailChimp Marketing Builder class.
 */
class Builder extends \EVF_Email_Marketing {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'mailchimp';
		$this->name = __( 'MailChimp', 'everest-forms-pro' );
		$this->icon = plugins_url( '/src/Addons/Mailchimp/assets/img/mailchimp.png', EFP_PLUGIN_FILE );

		parent::__construct();
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
			if ( empty( $connection['fields']['EMAIL'] ) ) {
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
			$email_data = explode( '.', $connection['fields']['EMAIL'] );
			$email_id   = $email_data[0];
			$double     = isset( $connection['options']['doubleoptin'] );
			$api        = $this->api_connect( $account_id );
			$ip         = evf_get_ip_address();
			$data       = array(
				'email_type'    => 'html',
				'status'        => 'subscribed',
				'status_if_new' => $double ? 'pending' : 'subscribed',
				'ip_signup'     => $ip,
				'ip_opt'        => $ip,
			);

			// Bail if there is any sort of issues with the API connection.
			if ( is_wp_error( $api ) ) {
				continue;
			}

			// Email is required.
			if ( empty( $fields[ $email_id ]['value'] ) ) {
				continue;
			} else {
				$data['email_address'] = strtolower( $fields[ $email_id ]['value'] );
			}

			// Setup merge fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {

				// Don't include EMAIL merge fields.
				if ( 'EMAIL' === $name ) {
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

				// Special formatting for different types of data.
				switch ( $type ) {
					case 'birthday':
						if ( ! empty( $form_data['form_fields'][ $id ]['datetime_format'] ) && 'date' === $form_data['form_fields'][ $id ]['datetime_format'] ) {
							$date  = \DateTime::createFromFormat( $form_data['form_fields'][ $id ]['date_format'], $value );
							$value = $date->format( 'm/d' );

							if ( 'd/m/Y' === $form_data['form_fields'][ $id ]['date_format'] ) {
								$value = $date->format( 'd/m' );
							}
						}
						break;
				}

				$data['merge_fields'][ $name ] = $value;
			}

			// Setup segments.
			if ( ! empty( $connection['groups'] ) ) {
				$s = array();
				foreach ( $connection['groups'] as $id => $segments ) {
					if ( is_array( $segments ) ) {
						foreach ( $segments as $id => $segment ) {
							$s[ $id ] = true;
						}
					} else {
						$s[ $segments ] = true;
					}
				}
				if ( ! empty( $s ) ) {
					$data['interests'] = $s;
				}
			}

			// Send to API.
			$hash = md5( $data['email_address'] ); // In order to both insert or update, we have to PUT to the specific resource.
			$res  = $this->api->put( 'lists/' . $list_id . '/members/' . $hash, $data );

			if ( false === $res || isset( $res['status'] ) && $res['status'] >= 300 ) {
				$error_msg = ! empty( $res['detail'] ) ? $res['detail'] : __( 'Error creating subscription.', 'everest-forms-pro' );
				self::log( $error_msg, 'error' );
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
		if ( ! class_exists( 'MailChimp_EVF' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/src/Addons/Mailchimp/vendor/Mailchimp.php';
		}
		try {
			$api = new MailChimp_EVF( trim( $data['apikey'] ) );
		} catch ( Exception $e ) {
			return $this->error( $e->getMessage() );
		}

		$res = $api->get( '' );

		if ( empty( $res['account_id'] ) ) {

			$details = ! empty( $res['detail'] ) ? $res['detail'] : __( 'Could not verify API key', 'everest-forms-pro' );

			/* translators: %s: API Authentication Error. */
			EverestForms_MailChimp::log( sprintf( __( 'MailChimp API error: %s', 'everest-forms-pro' ), $res ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API auth error: %s', 'everest-forms-pro' ), $details );

			return $this->error( $error_msg );
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
	 * @param  string $account_id  Account ID for Mailchimp.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! class_exists( 'MailChimp_EVF' ) ) {
			require_once dirname( EFP_PLUGIN_FILE ) . '/src/Addons/Mailchimp/vendor/Mailchimp.php';
		}

		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );
			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$this->account = $account_id;
				$this->api     = new MailChimp_EVF( $providers[ $this->id ][ $account_id ]['api'] );
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
			$lists = $this->api->get(
				'lists',
				array(
					'count'  => 500,
					'fields' => 'lists.id,lists.name',
				)
			);

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
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( Exception $e ) {
			evf_log(
				__( 'MailChimp API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);

			/* translators: %s: API Authentication Error */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-pro' ), $e->getMessage() );

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

		$this->api_connect( $account_id );

		try {
			$segments = $this->api->get(
				'lists/' . $list_id . '/interest-categories',
				array(
					'count'  => 500,
					'fields' => 'categories.id,categories.title,categories.type',
				)
			);

			if ( ! empty( $segments['categories'] ) ) {
				$available_groups = array();
				foreach ( $segments['categories'] as $segment ) {
					if ( empty( $segment['id'] ) ) {
						continue;
					}
					$available_groups[ $segment['id'] ] = array(
						'id'   => $segment['id'],
						'name' => isset( $segment['title'] ) ? trim( $segment['title'] ) : esc_html__( 'Unknown Segment', 'everest-forms-pro' ),
						'type' => isset( $segment['type'] ) ? trim( $segment['type'] ) : 'checkbox',
					);

					$groups = $this->api->get(
						'lists/' . $list_id . '/interest-categories/' . $segment['id'] . '/interests',
						array(
							'count'  => 500,
							'fields' => 'interests.id,interests.name',
						)
					);

					if ( ! empty( $groups['interests'] ) ) {
						$available_groups[ $segment['id'] ]['groups'] = array();
						foreach ( $groups['interests'] as $i => $group ) {
							if ( empty( $group['id'] ) ) {
								continue;
							}
							$available_groups[ $segment['id'] ]['groups'][] = array(
								'id'   => $group['id'],
								'name' => isset( $group['name'] ) ? trim( $group['name'] ) : esc_html__( 'Unknown Group', 'everest-forms-pro' ),
							);
						}
					}
				}

				return $available_groups;
			} else {
				$error_msg = esc_html__( 'API groups error: No groups', 'everest-forms-pro' );

				return $this->error( $error_msg );
			}
		} catch ( Exception $e ) {
			evf_log(
				__( 'MailChimp API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integrations', 'error' ),
				)
			);

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API groups error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );

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

		try {
			$fields = $this->api->get(
				'lists/' . $list_id . '/merge-fields',
				array(
					'count' => 500,
				)
			);
			$fld[0] = array(
				'id'         => 0,
				'name'       => __( 'Email Address', 'everest-forms-pro' ),
				'req'        => true,
				'field_type' => 'email',
				'tag'        => 'EMAIL',
			);

			if ( ! empty( $fields['merge_fields'] ) ) {
				foreach ( $fields['merge_fields'] as $field ) {
					$fld[ $field['merge_id'] ] = array(
						'id'         => $field['merge_id'],
						'name'       => isset( $field['name'] ) ? trim( $field['name'] ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'req'        => $field['required'] ? '1' : '',
						'field_type' => $field['type'],
						'tag'        => $field['tag'],
					);
				}
			}

			return $fld;

		} catch ( Exception $e ) {

			evf_log(
				__( 'MailChimp API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integrations', 'error' ),
				)
			);

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

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

		$output      = '<div class="evf-provider-options evf-connection-block">';
			$output .= '<h4>' . __( 'Options', 'everest-forms-pro' ) . '</h4>';
			$output .= sprintf(
				'<p><input id="%s_options_doubleoptin" type="checkbox" value="1" name="integrations[%s][%s][options][doubleoptin]" %s><label for="%s_options_doubleoptin">%s</label></p>',
				esc_attr( $connection_id ),
				esc_attr( $this->id ),
				esc_attr( $connection_id ),
				checked( ! empty( $connection['options']['doubleoptin'] ), true, false ),
				esc_attr( $connection_id ),
				__( 'Use double opt-in', 'everest-forms-pro' )
			);
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
		self::$log->log( $level, $message, array( 'source' => 'mailchimp' ) );
	}
}
