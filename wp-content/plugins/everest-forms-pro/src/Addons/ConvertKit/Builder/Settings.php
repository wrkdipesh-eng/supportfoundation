<?php
/**
 * Package Title
 *
 * @package EverestForms\Pro\Addons\ConvertKit\Builder
 * @version 1.0.0
 * @since   1.0.0
 */

namespace  EverestForms\Pro\Addons\ConvertKit\Builder;

use EverestForms\Pro\Addons\ConvertKit\API\API;
use EverestForms\Pro\Addons\ConvertKit\Settings\Settings as ConvertKit_EVF;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms ConvertKit class
 */
class Settings extends \EVF_Email_Marketing {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'convertkit';
		$this->name = __( 'ConvertKit', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/ConvertKit/assets/img/convertkit.png', EFP_PLUGIN_FILE );

		parent::__construct();
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @param array $fields evf form array of fields.
	 * @param array $entry      Entry array of form.
	 * @param array $form_data  Form Data.
	 * @param int   $entry_id   Identifier for entry.
	 */
	public function process_feed( $fields, $entry, $form_data, $entry_id = 0 ) {

		if ( empty( $form_data['integrations'][ $this->id ] ) ) {
			return;
		}

		foreach ( $form_data['integrations'][ $this->id ] as $connection ) :

			if ( empty( $connection['fields']['EMAIL'] ) ) {
				continue;
			}

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
							if ( ! in_array( $con_field_choice, $entry['form_fields'][ $con_field_select ] ) ) {
								continue;
							}
						} else {
							if ( $entry['form_fields'][ $con_field_select ] != $con_field_choice ) {
								continue;
							}
						}
					} else {
						continue;
					}
				} elseif ( 'is_not' === $con_field_condition ) {
					if ( isset( $entry['form_fields'][ $con_field_select ] ) ) {
						if ( is_array( $entry['form_fields'][ $con_field_select ] ) ) {
							if ( in_array( $con_field_choice, $entry['form_fields'][ $con_field_select ] ) ) {
								continue;
							}
						} else {
							if ( $entry['form_fields'][ $con_field_select ] == $con_field_choice ) {
								continue;
							}
						}
					}
				}
			}
			$account_id = $connection['account_id'];
			$list_id    = $connection['list_id'];
			$email_data = explode( '.', $connection['fields']['EMAIL'] );
			$name_data  = explode( '.', $connection['fields']['FNAME'] );
			$tags_data  = explode( '.', $connection['fields']['evf-convertkit-tags'] );
			$email_id   = $email_data[0];
			$fname      = $name_data[0];
			$api        = $this->api_connect( $account_id );

			// Pulling up the tags data for publishing them.
			$tags       = isset( $tags_data[0] ) ? $tags_data[0] : '';
			$tags_key = isset( $tags_data[0] ) ? $tags_data[0] : null;
			$tags     = array();

			if ( null !== $tags_key && isset( $entry['form_fields'][ $tags_key ] ) ) {
				$value = $entry['form_fields'][ $tags_key ];
				$tags  = is_array( $value ) ? array_values( $value ) : array( $value );
			}
			$email_addr = isset( $entry['form_fields'][ $email_id ] ) ? $entry['form_fields'][ $email_id ] : null;

			if ( is_wp_error( $api ) ) {
				continue;
			}

			if ( empty( $fields[ $email_id ]['value'] ) ) {
				continue;
			} else {
				$data['email_address'] = strtolower( $fields[ $email_id ]['value'] );
			}

			if ( empty( $fields[ $fname ]['value'] ) ) {
				continue;
			} else {
				$data['fname'] = $fields[ $fname ]['value'];
			}

			foreach ( $connection['fields'] as $name => $custom_field ) {

				// Not including EMAIL AND FNAME.
				if ( 'EMAIL' === $name || 'FNAME' === $name ) {
					continue;
				}

				if ( empty( $custom_field ) ) {
					continue;
				}

				$custom_field = explode( '.', $custom_field );
				$id           = $custom_field[0]; // evf Field ID.
				$key          = ! empty( $custom_field[1] ) ? $custom_field[1] : 'value';
				$type         = ! empty( $custom_field[2] ) ? $custom_field[2] : 'text'; // MC merge field type.

				// Check mapped field.
				if ( empty( $fields[ $id ][ $key ] ) ) {
					continue;
				}

				$value           = $fields[ $id ][ $key ];
				$checkbox_fields = array( 'checkbox', 'payment-checkbox' );
				$multiple_fields = array( 'multiple', 'payment-multiple' );
				$field_type      = $fields[ $id ]['type'];

				if ( in_array( $field_type, $checkbox_fields, true ) && isset( $value['label'] ) ) {
					$value = implode( ', ', $value['label'] );
				} elseif ( in_array( $field_type, $multiple_fields, true ) && isset( $value['label'] ) ) {
					$value = $value['label'];
				} elseif ( is_array( $value ) ) {
					$value = implode( ' ', $value );
				}

				$data['custom_fields'][ $name ] = $value;
			}

			// Sending data to API.
			$api_detail = new API();
			$res        = $api_detail->add_email_to_api( $list_id, $data['email_address'], $data['fname'], $api['apiKey'], isset( $data['custom_fields'] ) ? $data['custom_fields'] : array() );

			if ( false === $res || isset( $res['status'] ) && $res['status'] >= 300 ) {
				$error_msg = ! empty( $res['detail'] ) ? $res['detail'] : __( 'Error creating subscription.', 'everest-forms-pro' );
				ConvertKit_EVF::log( $error_msg, 'error' );
			}

			// Preparing the tags, latching them to subscriptions.
			$tags_data = array();
			foreach ( $tags as $tags_tuple ) {
				array_push( $tags_data, array( 'name' => $tags_tuple ) );
			}
			try {
				$api_tags = new API();
				$api_tags->api_post(
					'tags',
					wp_json_encode(
						array(
							'api_key' => $this->api['apiKey'],
							'tag'     => $tags_data,
						)
					)
				);
				$response_tags = $api_tags->api_request(
					'tags',
					array(
						'api_key' => $this->api['apiKey'],
					)
				);
				foreach ( $response_tags['tags'] as $tags_tuple ) {
					if ( isset( $tags_tuple['id'], $tags_tuple['name'] ) && ( in_array( $tags_tuple['name'], $tags, true ) ) ) {
						$results = $api_tags->api_post(
							"tags/{$tags_tuple['id']}/subscribe",
							wp_json_encode(
								array(
									'api_key' => $this->api['apiKey'],
									'email'   => $email_addr,
								)
							)
						);
					}
				}
			} catch ( Exception $e ) {
				return $this->error( $e->getMessage() );
			}

		endforeach;
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @param array  $data    Data object for authorization.
	 * @param string $form_id Form identifier.
	 *
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		$api_detail = new API();

		try {
			$res['forms'] = $api_detail->get_api_forms( trim( $data['apikey'] ) );
		} catch ( Exception $e ) {
			return $this->error( $e->getMessage() );
		}

		if ( ! empty( $res['forms']->errors ) ) {
			$details = __( 'Could not verify API key', 'everest-forms-pro' );
			ConvertKit_EVF::log( sprintf( __( 'ConvertKit API error: Could not connect to api', 'everest-forms-pro' ) ), 'error' );
			/* translators: %s: Could not verify API key error. */
			$error_msg = sprintf( __( 'API authentication error: %s', 'everest-forms-pro' ), $details );
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
	 * @param  string $account_id   Account identifier.
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
				$api_details   = new API();
				$this->api     = $api_details->get_api_forms( $providers[ $this->id ][ $account_id ]['api'] );
				$this->api     = array(
					'lists'  => $this->api,
					'apiKey' => $api_key,
				);
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection ID for ConvertKit.
	 * @param string $account_id    Account ID for ConvertKit.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$this->api_connect( $account_id );
		try {

			$lists['forms'] = $this->api['lists'];

			if ( ! empty( $lists['forms'] ) ) {
				$list_array = array();
				foreach ( $lists['forms'] as $list ) {
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
			/* translators: %s: ConvertKit API Error. */
			evf_log(
				esc_html__( 'convertkit API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
			/* translators: %s: ConvertKit API Error, exception encountered. */
			$error_msg = sprintf( __( 'API list error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @param string $connection_id Connection ID for ConvertKit.
	 * @param string $account_id    Account ID for the ConvertKit account.
	 * @param string $list_id       List Identifier.
	 *
	 * @return mixed array or error object
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {
			$api_details = new API();
			$fields      = $api_details->api_request(
				'custom_fields',
				array(
					'api_key' => $this->api['apiKey'],
				)
			);

			$fld[0] = array(
				'id'         => 0,
				'name'       => __( 'Email Address', 'everest-forms-pro' ),
				'req'        => true,
				'field_type' => 'email',
				'tag'        => 'EMAIL',
			);
			$fld[1] = array(
				'id'         => 1,
				'name'       => __( 'First Name', 'everest-forms-pro' ),
				'req'        => false,
				'field_type' => 'first_name',
				'tag'        => 'FNAME',
			);
			$fld[2] = array(
				'id'         => 2,
				'name'       => __( 'ConvertKit Tags', 'everest-forms-pro' ),
				'req'        => false,
				'field_type' => 'everest_forms_tags_convert_kit',
				'tag'        => 'evf-convertkit-tags',
			);

			if ( ! isset( $fields->errors ) && ! empty( $fields['custom_fields'] ) ) {
				foreach ( $fields['custom_fields'] as $field ) {
					$fld[ $field['id'] ] = array(
						'id'         => $field['id'],
						'name'       => isset( $field['label'] ) ? trim( $field['label'] ) : __( 'Unknown Merge Variables', 'everest-forms-pro' ),
						'field_type' => $field['name'],
						'tag'        => $field['key'],
					);
				}
			}

			return $fld;

		} catch ( Exception $e ) {

			/* translators: %s: ConvertKit API Error */
			evf_log(
				__( 'convertkit API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integrations', 'error' ),
				)
			);
			/* translators: %s: ConvertKit API exception encountered. */
			$error_msg = sprintf( __( 'API fields error: %s', 'everest-forms-pro' ), $e->getMessage() );

			return $this->error( $error_msg );
		}
	}

}
