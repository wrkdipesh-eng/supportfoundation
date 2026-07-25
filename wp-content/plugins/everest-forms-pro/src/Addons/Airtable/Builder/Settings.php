<?php
/**
 * Airtable Settings.
 *
 * @package EverestForms\Pro\Addons\Airtable\Builder
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Airtable\Builder;

use EverestForms\Pro\Addons\Airtable\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Airtable Integration.
 */
class Settings extends \EVF_Email_Marketing {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id   = 'airtable';
		$this->name = __( 'Airtable', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/Airtable/assets/img/Airtable.png', EFP_PLUGIN_FILE );

		parent::__construct();
	}

	/**
	 * Get Integration account lists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 * @param string $base_id       Base ID for fetching the base lists.
	 *
	 * @return array|WP_Error List array on success, WP_Error on failure.
	 */
	public function api_lists( $connection_id = '', $account_id = '', $base_id = '' ) {
		$api = $this->api_connect( $account_id );
		try {
			$lists = $api->get_workspace_list();

			$base_id         = ! empty( $base_id ) ? $base_id : ( ! empty( $lists['bases'] ) ? $lists['bases'][0]['id'] : '' );
			$base_schema     = $this->get_base_schema( $base_id, $account_id );
			$formatted_lists = $this->format_data( $lists['bases'] );

			if ( ! empty( $formatted_lists ) ) {
				$list_array                = array();
				$list_array['lists']       = $formatted_lists;
				$list_array['base_schema'] = $base_schema;
				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Airtable API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
	}

	/**
	 * Connect to API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id Account ID.
	 *
	 * @return API|WP_Error API object on success, WP_Error on failure.
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$api_key   = $providers[ $this->id ][ $account_id ]['api'];
				$this->api = new API( $api_key );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching API fields.
	 * @param string $list_id       List ID for fetching API fields.
	 *
	 * @return array List of fields.
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$api = $this->api_connect( $account_id );
		try {
			if ( ! is_array( $list_id ) ) {
				return array();
			}
			$base_id           = $list_id['list_id'];
			$schema_id         = isset( $list_id['current_base_schema_id'] ) && ! empty( $list_id['current_base_schema_id'] ) ? $list_id['current_base_schema_id'] : $list_id['base_schema_id'];
			$list_of_schema    = $api->get_base_schema( $base_id );
			$non_editable_type = array(
				'formula',
				'rollup',
				'lookup',
				'autoNumber',
				'createdTime',
				'lastModifiedTime',
				'count',
				'multipleRecordLinks',
				'button',
				'multipleLookupValues',
				'multipleAttachments',
				'singleSelect',
				'singleCollaborator',
				'multipleSelects',
				'currency',
				'percent',
				'duration',
				'number'
			);

			if ( ! empty( $list_of_schema['tables'] ) ) {
				foreach ( $list_of_schema['tables'] as $key => $lists ) {
					if ( $schema_id === $lists['id'] ) {
						$list_of_fields = $lists['fields'];
						break;
					}
				}
			}
			foreach ( $list_of_fields as $field ) {
				if ( in_array( $field['type'], $non_editable_type ) ) {
					continue;
				}
				$fld[] = array(
					'id'         => $field['id'],
					'name'       => $field['name'],
					'req'        => true,
					'field_type' => $field['type'],
					'tag'        => $field['name'],
					'parent_id'  => $schema_id
				);
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'Airtable API Data field error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type' => array( 'Integration', 'error' ),
				)
			);
		}
		return $fld;
	}

	/**
	 * Fetch API Groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id    Account ID.
	 * @param string $list_id       List ID.
	 *
	 * @return WP_Error
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'Airtable won\'t support Groups.', 'everest-forms-pro' ) );
	}

	/**
	 * Get base schema.
	 *
	 * @param string $base_id    Base ID.
	 * @param string $account_id Account ID.
	 *
	 * @return array Formatted base schema data.
	 */
	public function get_base_schema( $base_id, $account_id ) {
		$api = $this->api_connect( $account_id );

		if ( ! $api ) {
			return array();
		}
		$formatted_data = array();

		$base_schema = $api->get_base_schema( $base_id );

		if ( ! empty( $base_schema['tables'] ) ) {
			$formatted_data = $this->format_data( $base_schema['tables'] );
		}
		return $formatted_data;
	}

	/**
	 * Format data.
	 *
	 * @param array $datas Data to format.
	 *
	 * @return array Formatted data.
	 */
	public function format_data( $datas ) {
		$formatted_data = array();
		foreach ( $datas as $data ) {
			$formatted_data[ $data['id'] ] = array(
				'id'   => $data['id'],
				'name' => $data['name']
			);
		}
		return $formatted_data;
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 * @throws \Exception Exception.
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		$api    = new Api( trim( $data['apikey'] ) );
		$valid  = $api->get_workspace_list();
		$logger = new \EVF_Logger();
		if ( isset( $valid['error'] ) ) {
			$logger->log( 'error', esc_html__( 'Airtable API error: Could not connect to api ', 'everest-forms-pro' ) );
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
	 * Process and submit entry to provider.
	 *
	 * @since 1.0.0
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

		$data                 = array();
		$temp_airtable_fields = array();

		// Fire for each connection.
		foreach ( $form_data['integrations'][ $this->id ] as $connection ) :
			// Check for conditional logic.
			$account_id = $connection['account_id'];
			$api        = $this->api_connect( $account_id );

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
							$value = ! empty( $value['label'] ) && is_array( $value['label'] ) ? $value['name'] . ' : ||' . implode( '||', (array) $value['label'] ) . '||' : $value['label'];
							break;

						case 'address':
							$value = implode( ', ', $value );
							break;

						case 'select':
							$value = $fields[ $id ]['name'] . ' : ' . $value['0'];
							break;

						default:
							$value = implode( ' ', $value );
							break;
					}
				}

				switch ( $type ) {
					case 'checkbox':
						if ( 'yes-no' === $fields[ $id ]['type'] ) {
							$value = 'yes' === $value ? true : false;
							if ( ! empty( $value ) ) {
								$temp_airtable_fields[ $name ] = $value;
							}
						}
						break;

					case 'rating':
						if ( 'rating' === $fields[ $id ]['type'] ) {
							$value = $fields[ $id ]['value']['value'];
							if ( ! empty( $value ) ) {
								$temp_airtable_fields[ $name ] = $value;
							}
						}
						break;
					default:
						$temp_airtable_fields[ $name ] = $value;
						break;
				}
			}
		endforeach;
		$data     = array(
			'records' => array(
				array(
					'fields' => $temp_airtable_fields
				)
			)
		);
		$response = $api->create_records( $connection['list_id'], $connection['base_schema_id'], $data );

		$logger = new \EVF_Logger();
		if ( isset( $response['error'] ) ) {
			$logger->log( 'error', esc_html__( 'Airtable : ' . $response['error']['message'], 'everest-forms-pro' ), array( 'source' => 'airtable' ) );
		} else {
			$logger->log( 'info', esc_html__( 'Airtable : Record created successfully.', 'everest-forms-pro' ), array( 'source' => 'airtable' ) );
		}
	}
}
