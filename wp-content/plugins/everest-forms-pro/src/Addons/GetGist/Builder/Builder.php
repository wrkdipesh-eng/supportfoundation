<?php
/**
 * GetGist Marketing Class.
 *
 * @package EverestForms\Pro\Addons\GetGist\Settings\Setting
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\GetGist\Builder;

use EverestForms\Pro\Addons\GetGist\API\API;

defined( 'ABSPATH' ) || exit;


/**
 * GetGist Marketting class.
 */
class Builder extends \EVF_Email_Marketing {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'getgist';
		$this->name = __( 'GetGist', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/GetGist/assets/img/getgist.png', EFP_PLUGIN_FILE );

		add_filter( 'everest_forms_save_form_args', array( $this, 'save_form_args' ), 11, 3 );

		parent::__construct();
	}

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

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

		if ( ! empty( $form_data['integrations'][ $this->id ] ) && 'getgist' === $this->id ) {
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

			$api = $this->api_connect( $account_id );

			if ( is_wp_error( $api ) ) {
				continue;
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
			}

			$first_name = ! empty( $data['first_name'] ) ? $data['first_name'] : '';
			$last_name  = ! empty( $data['last_name'] ) ? $data['last_name'] : '';
			// Setup fullname.
			$data['full_name'] = $first_name . ' ' . $last_name;

			// Setup existing and new tags.
			if ( isset( $connection['tags'] ) && ! empty( $connection['tags'] ) ) {

				$tags = $connection['tags']['add'];

				if ( ! empty( $tags ) ) {
					foreach ( $tags as $tag ) {
						$data['tags'][] = $tag;
					}
				}
			}

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				$response = $api->make_request( 'leads', $data, 'POST', $form_data['id'], $entry_id );
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
	 * @param  string $account_id  Account ID for GetGist.
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
	 * @param string $connection_id Connection ID for GetGist.
	 * @param string $account_id    Account ID for GetGist.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {

			$lists = $this->api->get_lists();

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
			/* translators: %s: GetGist API Error, exception encountered. */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-pro' ), $e->getMessage() );
			self::log( $error_msg, 'error' );

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
					++$i;
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
	 * Fetch API Groups.
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $list_id List ID.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'GetGist won\'t support Groups.', 'everest-forms-pro' ) );
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
			$tags       = $this->api->make_request( 'tags', $api_params, 'GET' );
			if ( ! empty( $tags['tags'] ) ) {
				$formatted_tags = array();
				foreach ( $tags['tags'] as $key => $value ) {
					$tag                    = array();
					$tag['id']              = esc_html( $value['id'] );
					$tag['tag']             = esc_html( $value['name'] );
					$formatted_tags[ $key ] = $tag;
				}

				return $formatted_tags;
			}
		} catch ( \Exception $e ) {

			/* translators: %s: API Authentication Error. */
			self::log( sprintf( __( 'GetGist API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
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
		self::$log->log( $level, $message, array( 'source' => 'getgist' ) );
	}
}
