<?php
/**
 * Drip Marketing Class.
 *
 * @package EverestForms\Pro\Addons\Drip\Builder
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Drip\Builder;

use EverestForms\Pro\Addons\Drip\API\API;
use EverestForms\Pro\Addons\Drip\Settings\Settings;

defined( 'ABSPATH' ) || exit;


/**
 * Drip Marketting class.
 */
class DripMarketing extends \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'drip';
		$this->name = __( 'Drip', 'everest-forms-drip' );
		$this->icon = plugins_url( 'src/Addons/Drip/assets/img/drip.png', EFP_PLUGIN_FILE );

		add_filter( 'everest_forms_save_form_args', array( $this, 'save_form_args' ), 11, 3 );

		parent::__construct();
	}

	/**
	 * Preprocess integrations data before saving it in form_data when editing form.
	 *
	 * @param array $form Form array, usable with wp_update_post.
	 * @param array $data Data retrieved from $_POST and processed.
	 * @param array $args Empty by default, may have custom data not intended to be saved, but used for processing.
	 */
	public function save_form_args( $form, $data, $args ) {
		$form_data   = json_decode( stripslashes( $form['post_content'] ), true );
		$data_modify = false;

		if ( ! empty( $form_data['integrations'][ $this->id ] ) && 'drip' === $this->id ) {
			// Modify content as we need.
			foreach ( $form_data['integrations'][ $this->id ] as $connection_id => $connection ) {
				if ( ! empty( $form_data['integrations'][ $this->id ][ $connection_id ]['tags'] ) ) {
					$data_modify = true;
					$form_data['integrations'][ $this->id ][ $connection_id ]['tags'] = $this->sanitize_tags_post( $connection_id, $connection );
				}
			}

			if ( $data_modify ) {
				$form['post_content'] = evf_encode( $form_data );

			}
		}

		return $form;
	}

	/**
	 * Sanitize tags.
	 *
	 * @param mixed $connection_id connection ID.
	 * @param array $connection connection data.
	 */
	public function sanitize_tags_post( $connection_id, $connection ) {
		$tags = array(
			'add' => array(),
			'new' => array(),
		);

		$form_post     = ! empty( $_POST['form_data'] ) ? json_decode( stripslashes( $_POST['form_data'] ), true ) : []; // phpcs:ignore
		foreach ( $form_post as $item ) {
			if ( empty( $item['name'] ) ) {
				continue;
			}

			if ( "integrations[{$this->id}][{$connection_id}][tags][add][]" === $item['name'] ) {
				$tags['add'][] = sanitize_text_field( $item['value'] );
			}
		}

		$result = array();
		if ( ! empty( $connection['tags']['new'] ) ) {
			$tags_new = explode( ',', $connection['tags']['new'] );
			$result   = array_map( 'sanitize_text_field', $tags_new );
		}
		if ( ! empty( $result ) ) {
			$tags['add'] = array_unique( array_merge( $tags['add'], $result ) );
			$tags['new'] = 1;
		}

		return $tags;
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

			// Setup basic data.
			$account_id = $connection['account_id'];
			$list_id    = $connection['list_id'];
			$email_data = explode( '.', $connection['fields']['email'] );
			$email_id   = $email_data[0];
			$eu_consent = isset( $connection['options']['eu_consent'] ) ? $connection['options']['eu_consent'] : 'unknown';
			$api        = $this->api_connect( $account_id );
			$ip         = evf_get_ip_address();
			$data       = array(
				'status'     => 'active',
				'ip_address' => $ip,
				'eu_consent' => $eu_consent,
			);

			$drip_fields = array(
				'status',
				'email',
				'first_name',
				'last_name',
				'address1',
				'address2',
				'city',
				'state',
				'zip',
				'phone',
				'country',
				'time_zone',
				'utc_offset',
				'visitor_uuid',
				'created_at',
				'ip_address',
				'user_agent',
				'lifetime_value',
				'original_referrer',
				'landing_url',
				'prospect',
				'base_lead_score',
				'eu_consent',
				'sms_number',
				'sms_consent',
				'lead_score',
				'user_id',
			);

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

			// Setup merge fields.
			foreach ( $connection['fields'] as $name => $merge_field ) {

				// Don't include email merge fields.
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
						case 'country':
							$value = apply_filters( 'everest_forms_plaintext_field_value', $value['country_code'], $value, $form_data, 'email-plain' );
							break;
						default:
							$value = implode( ' ', $value );
							break;
					}
				}
				if ( in_array( $name, $drip_fields, true ) ) {
					$data[ $name ] = $value;
				} else {
					$data['custom_fields'][ $name ] = $value;
				}
			}

			// Setup custom fields.
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

			// Setup existing and new tags.
			if ( isset( $connection['tags'] ) && ! empty( $connection['tags'] ) ) {

				$tags = $connection['tags']['add'];

				if ( ! empty( $tags ) ) {
					$data['tags'] = $tags;
				}
			}

			// Setup custom Fields.
			if ( isset( $custom_field_value ) && ! empty( $custom_field_value ) && is_array( $custom_field_value ) ) {

				foreach ( $custom_field_value as $custom_field ) {
					if ( in_array( $custom_field['field'], $drip_fields, true ) ) {
						$data[ $custom_field['field'] ] = $custom_field['value'];
					} else {
						$data['custom_fields'][ $custom_field['field'] ] = $custom_field['value'];
					}
				}
			}

			// Send to API.
			$resource = "{$list_id}/subscribers";
			$add_data = array(
				'subscribers' => array( $data ),
			);

			if ( empty( $data['email'] ) ) {

				Settings::log( __( 'Email address is required.', 'everest-forms-drip' ), 'error' );

			} else {
				$response = $api->make_request( $resource, 'POST', $add_data );
				if ( ! $response['success'] ) {
					$error_msg = ! empty( $res['response']['errors'][0]['message'] ) ? $res['response']['errors'][0]['message'] : __( 'Error creating subscription.', 'everest-forms-drip' );
					Settings::log( $error_msg, 'error' );
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
		$api = new API( trim( $data['apikey'] ) );

		if ( ! $api->validate_api_key() ) {

			return $this->error( __( 'Could not verify API key', 'everest-forms-drip' ) );
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
	 * @param  string $account_id  Account ID for Drip.
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
				return $this->error( __( 'API connection error', 'everest-forms-drip' ) );
			}
		}
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for Drip.
	 * @param string $account_id    Account ID for Drip.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {

			$lists['lists'] = $this->api->get_accounts();

			if ( ! empty( $lists['lists']['accounts'] ) ) {
				$list_array = array();
				foreach ( $lists['lists']['accounts'] as $list ) {
					if ( empty( $list['id'] ) ) {
						continue;
					}
					$list_array[ $list['id'] ] = array(
						'id'   => $list['id'],
						'name' => isset( $list['name'] ) ? trim( $list['name'] ) : __( 'Unknown List', 'everest-forms-drip' ),
					);
				}

				return $list_array;
			} else {
				$error_msg = __( 'API form error: No lists found', 'everest-forms-drip' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			/* translators: %s: Drip API Error, exception encountered. */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-drip' ), $e->getMessage() );
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
		return $this->error( esc_html__( 'Drip won\'t support Groups.', 'everest-forms-drip' ) );
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
				'name'       => __( 'Email Address', 'everest-forms-drip' ),
				'req'        => true,
				'field_type' => 'email',
				'tag'        => 'email',
			);
			$fld[1] = array(
				'id'         => 1,
				'name'       => __( 'First Name', 'everest-forms-drip' ),
				'req'        => false,
				'field_type' => 'first_name',
				'tag'        => 'first_name',
			);
			$fld[2] = array(
				'id'         => 2,
				'name'       => __( 'Last Name', 'everest-forms-drip' ),
				'req'        => false,
				'field_type' => 'last_name',
				'tag'        => 'last_name',
			);
			$fld[3] = array(
				'id'         => 3,
				'name'       => __( 'Phone', 'everest-forms-drip' ),
				'req'        => false,
				'field_type' => 'phone',
				'tag'        => 'phone',
			);

			return $fld;

		} catch ( \Exception $e ) {

			Settings::log( $$e->getMessage(), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-drip' ), $e->getMessage() );

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
			$api_params    = array();
			$resource      = "{$list_id}/custom_field_identifiers";
			$custom_fields = $this->api->make_request( $resource, 'GET', $api_params );
			$fld           = array();

			$fields = array(
				'address1',
				'address2',
				'city',
				'state',
				'zip',
				'phone',
				'country',
				'time_zone',
				'utc_offset',
				'visitor_uuid',
				'user_agent',
				'lifetime_value',
				'original_referrer',
				'landing_url',
				'prospect',
				'base_lead_score',
				'sms_number',
				'sms_consent',
				'lead_score',
				'user_id                ',
			);

			if ( isset( $custom_fields['response']['custom_field_identifiers'] ) && ! empty( $custom_fields['response']['custom_field_identifiers'] ) ) {
				$fields = array_unique( array_merge( $fields, $custom_fields['response']['custom_field_identifiers'] ) );
			}

			$excluded_fields = array( 'first_name', 'last_name', 'phone' );
			foreach ( $fields as $field ) {

				if ( in_array( $field, $excluded_fields, true ) ) {
					continue;
				}

				if ( 'utc_offset' === $field ) {
					$label = 'UTC Offset';
				} elseif ( 'visitor_uuid' === $field ) {
					$label = 'Visitor UUID';
				} elseif ( 'landing_url' === $field ) {
					$label = 'Landing URL';
				} elseif ( 'user_id' === $field ) {
					$label = 'User ID';
				} else {
					$label = ucwords( str_replace( '_', ' ', $field ) );

				}

				$fld[ $field ] = array(
					'id'         => $field,
					/* translators: %s: Field Label. */
					'name'       => sprintf( esc_html__( '%s', 'everest-forms-drip' ), $label ),
					'req'        => false,
					'field_type' => $field,
					'tag'        => $field,
				);
			}

			return $fld;

		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			Settings::log( sprintf( __( 'Drip API error: %s', 'everest-forms-drip' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-drip' ), $e->getMessage() );

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
			$api_params = array();
			$resource   = "{$list_id}/tags";
			$tags       = $this->api->make_request( $resource, 'GET', $api_params );

			if ( ! empty( $tags['response']['tags'] ) ) {
				$formatted_tags = array();
				foreach ( $tags['response']['tags'] as $key => $value ) {
					$tag                    = array();
					$tag['id']              = esc_html( 'evf_drip_tag_' . $key );
					$tag['tag']             = esc_html( $value );
					$formatted_tags[ $key ] = $tag;
				}

				return $formatted_tags;
			}
		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			Settings::log( sprintf( __( 'Drip API error: %s', 'everest-forms-drip' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-drip' ), $e->getMessage() );

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
		$output         = '';
		$form_data_tags = array();
		$form_data_tags = isset( $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['add'] ) ? $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['add'] : array();
		$new_tags       = isset( $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['new'] ) ? $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['new'] : '';

		if ( ! empty( $form_data_tags ) && is_string( $form_data_tags ) ) {
			$form_data_tags = explode( ',', $form_data_tags );
		}

		$output = '<div class="evf-provider-tags evf-connection-block">';

		if ( ! empty( $tags ) || ! empty( $form_data_tags ) ) {
			$output       .= sprintf( '<h4>%s</h4>', esc_html__( 'Tags', 'everest-forms-pro' ) );
			$output       .= '<div class="everest-forms-panel-field">';
			$output       .= sprintf( '<select class="evf-provider-tags-select" name="integrations[%s][%s][tags][add][]" multiple="multiple">', $this->id, $connection_id );
			$i             = 0;
			$api_tag_label = array();

			if ( ! empty( $tags ) ) {
				foreach ( $tags as $tag ) {
					$id       = isset( $tag['id'] ) ? $tag['id'] : '';
					$tag      = isset( $tag['tag'] ) ? $tag['tag'] : '';
					$selected = '';

					if ( ! empty( $form_data_tags ) && in_array( $tag, $form_data_tags, true ) ) {
						$selected = 'selected="selected"';
					}

					$output         .= '<option value="' . $tag . '" ' . $selected . '>' . $tag . '</option>';
					$api_tag_label[] = $tag;
					$i++;
				}
			}

			if ( ! empty( $form_data_tags ) && 1 === $new_tags ) {
				foreach ( $form_data_tags as $new_tag ) {
					if ( ! in_array( $new_tag, $api_tag_label, true ) ) {
						$output .= '<option value="' . $new_tag . '" selected="selected">' . $new_tag . '</option>';
					}
				}
			}

			$output .= '</select></div>';
		}

		$output .= '<div class="abc"><h4>New Tags to Add</h4>';
		$output .= '<div class="input-section"><input type="text" class="widefat" name="integrations[' . $this->id . '][' . $connection_id . '][tags][new]"><p>Enter new tag name(s). Comma-seperated list of tags is accepted.</p></div></div>';
		$output .= '</div>';

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

		$output  = '<div class="evf-provider-options evf-connection-block">';
		$output .= '<div class="evf-drip-eu-consent"><h4>' . __( 'EU Consent', 'everest-forms-drip' ) . '</h4>';

		$output .= sprintf(
			'<label><input id="%s_options_eu_consent_default" type="radio" value="unknown" name="integrations[%s][%s][options][eu_consent]" %s>%s</label>',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			checked( ( isset( $connection['options']['eu_consent'] ) ? $connection['options']['eu_consent'] : 'unknown' ), 'unknown', false ),
			__( 'Default', 'everest-forms-drip' )
		);

		$output .= sprintf(
			'<label><input id="%s_options_eu_consent_denied" type="radio" value="denied" name="integrations[%s][%s][options][eu_consent]" %s>%s</label>',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			checked( isset( $connection['options']['eu_consent'] ) ? $connection['options']['eu_consent'] : 'unknown', 'denied', false ),
			__( 'Denied', 'everest-forms-drip' )
		);
		$output .= sprintf(
			'<label><input id="%s_options_eu_consent_granted" type="radio" value="granted" name="integrations[%s][%s][options][eu_consent]" %s>%s</label>',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			checked( isset( $connection['options']['eu_consent'] ) ? $connection['options']['eu_consent'] : 'unknown', 'granted', false ),
			__( 'Granted', 'everest-forms-drip' )
		);

		$output .= '</div></div>';

		return $output;
	}
}
