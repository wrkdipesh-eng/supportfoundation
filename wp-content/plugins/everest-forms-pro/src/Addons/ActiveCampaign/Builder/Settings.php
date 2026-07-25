<?php
/**
 * Active Campaign Marketing class.
 *
 * @package EverestForms\Pro\Addons\ActiveCampaign\Builder
 * @version 1.0.0
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\ActiveCampaign\Builder;

use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_ActiveCampaign_Api;
use EverestForms\Pro\Addons\ActiveCampaign\Settings\Settings as Log;

defined( 'ABSPATH' ) || exit;

/**
 * Active Campaign Marketing class.
 */
class Settings extends \EVF_Email_Marketing {

	/**
	 * Logger Instance
	 *
	 * @var object
	 */
	public static $log = false;

	/**
	 * Account ID for current account.
	 *
	 * @var string
	 */
	public $account;

	/**
	 * API instance.
	 *
	 * @var EVF_ActiveCampaign_Api
	 */
	public $api;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'activecampaign';
		$this->name = __( 'ActiveCampaign', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/ActiveCampaign/assets/img/active-campaign.png', EFP_PLUGIN_FILE );

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

		if ( ! empty( $form_data['integrations'][ $this->id ] ) && 'activecampaign' === $this->id ) {
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
		foreach ( $form_data['integrations'][ $this->id ] as $connection_id => $connection ) :
			$data               = array();
			$custom_field_value = array();

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
						case 'country':
							$value = apply_filters( 'everest_forms_plaintext_field_value', $value['country_code'], $value, $form_data, 'email-plain' );
							break;

						case 'address':
							$value = implode( ', ', $value );
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

				$custom_field_value[] = array(
					'field' => $connection['custom_field'][ $name ],
					'value' => $value,
				);
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

						case 'country':
							$value = apply_filters( 'everest_forms_plaintext_field_value', $value['country_code'], $value, $form_data, 'email-plain' );
							break;

						case 'address':
							$value = implode( ', ', $value );
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

				$data[ $name ] = $value;
			}

			$form_id = isset( $form_data['id'] ) ? $form_data['id'] : 0;

			// send contact to api.
			if ( ! is_wp_error( $this->api ) ) {
				$data['data']     = $data;
				$data['form_id']  = $form_id;
				$data['entry_id'] = $entry_id;

				$contact = $this->api->contact->create( $data );

				if ( is_wp_error( $contact ) ) {
						$active_campaign_data = array(
							'contact' => $data,
						);
						$contact              = $this->api->contact->create_or_update( $active_campaign_data );
				}

				if ( is_wp_error( $contact ) ) {
					continue;
				}

				$contact_id = $contact['id'];

				$list_id = $connection['list_id'];

				if ( isset( $list_id ) && ! empty( $list_id ) ) {
					$this->api->contact->subscribe( $contact_id, $list_id );
				}

				// Sending custom field value to api.

				if ( isset( $custom_field_value ) && ! empty( $custom_field_value ) && is_array( $custom_field_value ) ) {
					foreach ( $custom_field_value as $field_data ) {
						$field_id    = $field_data['field'];
						$field_value = $field_data['value'];

						$this->api->contact->fields( $contact_id )->fieldValue( $field_id )->create( $field_value );
					}
				}

				// Sending tags to api.
				if ( isset( $connection['tags'] ) && ! empty( $connection['tags'] ) ) {
					foreach ( $connection['tags']['add'] as $value ) {
						$tag_id = $this->api_get_tag_id_by_name( $contact_id, $value );
						$this->api->contact->tags( $contact_id )->create( $tag_id );
					}
				}
				// Sending notes to api.
				if ( isset( $connection['note'] ) && ! empty( $connection['note'] ) ) {
					$note_data['data'] = array(
						'note'    => $connection['note'],
						'relid'   => $contact_id,
						'reltype' => 'Subscriber',
					);

					/**
					 * For api log form_id and entry_id is passed.
					 *
					 * @since 1.7.9
					 */
					$note_data['form_id']  = $form_id;
					$note_data['entry_id'] = $entry_id;

					$this->api->note->create( $note_data );
				}
			}
		endforeach;
	}

		/**
		 * Get api tag ID by tag name.
		 *
		 * @param int   $contact_id contact ID.
		 * @param mixed $tag Tags value.
		 * @param bool  $create_new new tags to be created or not.
		 */
	public function api_get_tag_id_by_name( $contact_id, $tag, $create_new = true ) {
		$tags = $this->api->tag->list( array( 'type' => 'contact' ) );
		if ( ! is_wp_error( $tags ) && is_array( $tags ) && ! empty( $tags ) ) {
			$tags   = wp_list_pluck( $tags, 'tag', 'id' );
			$tag_id = array_search( $tag, $tags, true );

			if ( false !== $tag_id ) {
				return absint( $tag_id );
			}
		}
		// No need to create a tag if it does not exist.
		if ( ! $create_new ) {
			return false;
		}
		// API call - create a new tag.
		$response = $this->api->tag->create(
			array(
				'tag'     => $tag,
				'tagType' => 'contact',
			)
		);
		return absint( $response['id'] );
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
		try {
			$api = EVF_ActiveCampaign_Api::get_instance( $data['apiurl'], $data['apikey'] );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'error'     => esc_html__( 'Could not connect to the provider.', 'everest-forms-pro' ),
					'error_msg' => $e->getMessage(),
				)
			);
		}

		$auth = $api->authenticate->authenticate();

		if ( is_wp_error( $auth ) ) {
			$details = true === $auth ? $auth : __( 'Could not verify API URL', 'everest-forms-pro' );
			/*
			translators: %s: Error thrown by API authentication issues. */
			Log::log( sprintf( __( 'ActiveCampaign API error: %s', 'everest-forms-pro' ), $details ), 'error' );
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

		$connected_accounts['activecampaign'][ $to_be_stored_id ] = array(
			'api'   => trim( $data['apikey'] ),
			'url'   => trim( $data['apiurl'] ),
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
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );
			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$this->account = $account_id;
				$this->api     = EVF_ActiveCampaign_Api::get_instance( $providers[ $this->id ][ $account_id ]['url'], $providers[ $this->id ][ $account_id ]['api'] );
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
			$lists = $this->api->list->list();
			if ( ! is_wp_error( $lists ) && ! empty( $lists['lists'] ) ) {
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
		} catch ( \Exception $e ) {
			/*
			translators: %s: API Authentication Error. */
			Log::log( sprintf( __( 'ActiveCampaign API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

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
		 *
		 * @return mixed array or error object
		 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
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
				'req'        => false,
				'field_type' => 'first_name',
				'tag'        => 'firstName',
			),
			array(
				'id'         => 2,
				'name'       => __( 'Last Name', 'everest-forms-pro' ),
				'req'        => false,
				'field_type' => 'last_name',
				'tag'        => 'lastName',
			),
			array(
				'id'         => 3,
				'name'       => __( 'Phone Number', 'everest-forms-pro' ),
				'req'        => false,
				'field_type' => 'phone',
				'tag'        => 'phone',
			),
			array(
				'id'         => 4,
				'name'       => __( 'Organization Name', 'everest-forms-pro' ),
				'req'        => false,
				'field_type' => 'orgname',
				'tag'        => 'orgid',
			),
		);

		return $fld;
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
			$fields = $this->api->contact->fields()->list();
			$fld    = array();
			if ( ! is_wp_error( $fields ) && ! empty( $fields['fields'] ) ) {
				foreach ( $fields['fields'] as $field ) {
					$fld[ $field['id'] ] = array(
						'id'         => $field['id'],
						'name'       => isset( $field['title'] ) ? trim( $field['title'] ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'req'        => $field['isrequired'] ? '1' : '',
						'field_type' => $field['type'],
						'tag'        => $field['perstag'],
					);
				}
			}

			return $fld;

		} catch ( Exception $e ) {

			/*
			translators: %s: API Authentication Error. */
			Log::log( sprintf( __( 'ActiveCampaign API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

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
			$tags = $this->api->tag->list( array( 'type' => 'contact' ) );
			return $tags;

		} catch ( \Exception $e ) {

			/*
			translators: %s: API Authentication Error. */
			Log::log( sprintf( __( 'ActiveCampaign API error: %s', 'everest-forms-pro' ), $e->getMessage() ), 'error' );

			/* translators: %s: API Authentication Error. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

		/**
		 * Output Integration note.
		 *
		 * @param string $connection_id     Connection ID for fetching the connection object.
		 * @param array  $connection        Connections List.
		 * @param string $form_data         Form Fields.
		 */
	public function output_note( $connection_id = '', $connection = array(), $form_data = '' ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) || empty( $connection['list_id'] ) ) {
			return '';
		}

		$value = isset( $connection['note'] ) ? $connection['note'] : '';

		$output  = '<div class="evf-provider-notes evf-connection-block">';
		$output .= '<h4>' . __( 'Note', 'everest-forms-pro' ) . '</h4>';
		$output .= sprintf(
			'<textarea id="%s_note" name="integrations[%s][%s][note]" class="widefat short">%s</textarea>',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			$value
		);
		$output .= '</div>';

		return $output;
	}
}
