<?php
/**
 * GetResponse Marketing Class.
 *
 * @package EverestForms\GetResponse\Settings\Setting
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\GetResponse\Builder;

use EverestForms\Pro\Addons\GetResponse\API\API;
use EverestForms\Pro\Addons\GetResponse\Settings\Settings;

defined( 'ABSPATH' ) || exit;


/**
 * GetResponse Marketting class.
 */
class GetResponseMarketing extends \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'getresponse';
		$this->name = __( 'GetResponse', 'everest-forms-getresponse' );
		$this->icon = plugins_url( 'src/Addons/GetResponse/assets/img/getresponse.jpg', EFP_PLUGIN_FILE );

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
			$ip         = evf_get_ip_address();
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

			$data['ipAddress'] = $ip;
			$data['campaign']  = array(
				'campaignId' => $list_id,
			);

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

				$data[ $name ] = $value;
			}

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
							case 'country':
								$value = apply_filters( 'everest_forms_plaintext_field_value', $fields[ $id ]['value']['country_code'], $fields[ $id ]['value'], $entry, 'email-plain' );
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
				$custom_field_values = array();
				foreach ( $custom_field_value as $custom_field ) {
					$custom_field_values[] = array(
						'customFieldId' => $custom_field['field'],
						'value'         => ! is_array( $custom_field['value'] ) ? (array) $custom_field['value'] : $custom_field['value'],
					);
				}

				$data['customFieldValues'] = $custom_field_values;
			}

			// Day of Cycle.
			$auto_responder_day = isset( $connection['auto_responder_day'] ) ? absint( $connection['auto_responder_day'] ) : 0;

			if ( 0 <= $auto_responder_day || 9999 >= $auto_responder_day ) {
				$data['dayOfCycle'] = $auto_responder_day;
			}

			// Setup tags.
			if ( isset( $connection['tags'] ) && ! empty( $connection['tags'] ) ) {

				$tags = $connection['tags']['add'];

				if ( ! empty( $tags ) ) {
					foreach ( $tags as $tag ) {
						$data['tags'][] = array( 'tagId' => $tag );
					}
				}
			}

			// Create contact.
			$response = $api->make_request( 'contacts', $data, 'POST', $form_data['id'], $entry_id );

			if ( is_wp_error( $response ) ) {
				return;
			}

			if ( isset( $response['message'] ) ) {
				$error_msg = ! empty( $response['message'] ) ? $response['message'] : __( 'Error while creating contact.', 'everest-forms-getresponse' );
				Settings::log( $error_msg, 'error' );
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
			return $this->error( __( 'Could not verify API key', 'everest-forms-getresponse' ) );
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
	 * @param  string $account_id  Account ID for GetResponse.
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
				return $this->error( __( 'API connection error', 'everest-forms-getresponse' ) );
			}
		}
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for GetResponse.
	 * @param string $account_id    Account ID for GetResponse.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {

			$lists = $this->api->get_lists();

			if ( isset( $lists[0]['campaignId'] ) ) {
				$list_array = array();
				foreach ( $lists as $list ) {
					if ( empty( $list['campaignId'] ) ) {
						continue;
					}
					$list_array[ $list['campaignId'] ] = array(
						'id'   => $list['campaignId'],
						'name' => isset( $list['name'] ) ? trim( $list['name'] ) : __( 'Unknown List', 'everest-forms-getresponse' ),
					);
				}

				return $list_array;
			} else {
				$error_msg = __( 'API form error: No lists found', 'everest-forms-getresponse' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			/* translators: %s: GetResponse API Error, exception encountered. */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-getresponse' ), $e->getMessage() );
			Settings::log( $error_msg, 'error' );

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
		return $this->error( esc_html__( 'GetResponse won\'t support Groups.', 'everest-forms-getresponse' ) );
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
				'name'       => __( 'Email Address', 'everest-forms-getresponse' ),
				'req'        => true,
				'field_type' => 'email',
				'tag'        => 'email_address',
			);

			$fld[1] = array(
				'id'         => 1,
				'name'       => __( 'Name', 'everest-forms-getresponse' ),
				'req'        => true,
				'field_type' => 'text',
				'tag'        => 'name',
			);

			return $fld;

		} catch ( \Exception $e ) {

			Settings::log( $$e->getMessage(), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-getresponse' ), $e->getMessage() );

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
			$custom_fields = $this->api->get_custom_fields();

			if ( is_wp_error( $custom_fields ) ) {
				return false;
			}

			$fld = array();

			if ( isset( $custom_fields[0]['customFieldId'] ) ) {

				foreach ( $custom_fields as $custom_field ) {

					$fld[ $custom_field['customFieldId'] ] = array(
						'id'         => $custom_field['customFieldId'],
						/* translators: %s: Field Label. */
						'name'       => sprintf( esc_html__( ' % s', 'everest-forms-getresponse' ), ucwords( str_replace( '_', ' ', $custom_field['name'] ) ) ),
						'req'        => false,
						'field_type' => $custom_field['fieldType'],
						'tag'        => $custom_field['name'],
					);
				}
			}

				return $fld;

		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			Settings::log( sprintf( __( 'GetResponse API attributes error: % s', 'everest-forms-getresponse' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'GetResponse API attributes error: % s', 'everest-forms-getresponse' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Fetch Integration tags.
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 */
	public function api_tags( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {
			$tags = $this->api->get_tags();

			if ( isset( $tags[0]['tagId'] ) ) {
				$formatted_tags = array();

				foreach ( $tags as $tag ) {
					$formatted_tags[ $tag['tagId'] ] = array(
						'id'  => $tag['tagId'],
						'tag' => $tag['name'],
					);
				}

				return $formatted_tags;
			}
		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			Settings::log( sprintf( __( 'GetResponse API error: %s', 'everest-forms-getresponse' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-getresponse' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Integration output tags.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 * @param mixed  $form_data     Form data object.
	 *
	 * @return string
	 */
	public function output_tags( $connection_id = '', $connection = array(), $form_data = '' ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) || empty( $connection['list_id'] ) ) {
			return '';
		}

		$tags           = $this->api_tags( $connection_id, $connection['account_id'], $connection['list_id'] );
		$form_data_tags = isset( $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['add'] ) ? $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['add'] : array();
		$output         = '';

		$output  = '<div class="evf-provider-tags evf-connection-block">';
		$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Tags', 'everest-forms-pro' ) );
		$output .= '<div class="everest-forms-panel-field">';
		$output .= sprintf( '<select class="evf-provider-tags-select" name="integrations[%s][%s][tags][add][]" data-placeholder="' . esc_attr__( 'Select Tag(s)', 'everest-forms-getresponse' ) . '" multiple>', $this->id, $connection_id );

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				$id       = isset( $tag['id'] ) ? $tag['id'] : '';
				$tag      = isset( $tag['tag'] ) ? $tag['tag'] : '';
				$selected = '';

				if ( ! empty( $form_data_tags ) && in_array( $id, $form_data_tags, true ) ) {
					$selected = 'selected="selected"';
				}

				$output .= '<option value="' . $id . '" ' . $selected . '>' . $tag . '</option>';
			}
		}

		$output .= '</select></div></div>';
		return $output;
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

		$output = '<div class="evf-provider-options evf-connection-block">';

		// Description.
		$auto_responder_day = isset( $connection['auto_responder_day'] ) ? absint( $connection['auto_responder_day'] ) : '';

		/* translators: %s: Description Label. */
		$output .= '<div class="everest-forms-panel-field"><h4>' . __( 'Autoresponder Day		', 'everest-forms-zoho' ) . '</h2>';
		$output .= sprintf(
			'<input id="%s_auto_responder_day" type="number" name="integrations[%s][%s][auto_responder_day]" value="%d" class="widefat short" min="0" max="9999" step="1" />',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			$auto_responder_day
		);
		$output .= '</div></div>';

		return $output;
	}
}
