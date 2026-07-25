<?php
/**
 * AmoCRM Settings.
 *
 * @package EverestForms\Pro\Addons\AmoCRM\Builder
 * @since   1.7.9
 */

namespace  EverestForms\Pro\Addons\AmoCRM\Builder;

use EverestForms\Pro\Addons\AmoCRM\API\API;
use EverestForms\Pro\Addons\AmoCRM\Settings\Settings as amoCRMIntegrations;

defined( 'ABSPATH' ) || exit;


/**
 * AmoCRM Integration.
 *
 * @since 1.7.9
 */
class Settings extends  \EVF_Email_Marketing {

	/**
	 * Constructor.
	 *
	 * @since 1.7.9
	 */
	public function __construct() {
		$this->id   = 'amocrm';
		$this->name = __( 'amoCRM', 'everest-forms-pro' );
		$this->icon = plugins_url( 'src/Addons/AmoCRM/assets/images/amoCRM.png', EFP_PLUGIN_FILE );

		parent::__construct();
		add_filter( 'everest_forms_amocrm_add_account_html', array( $this, 'outputs_authentication_html' ) );
	}

	/**
	 * Get Integration account lists.
	 *
	 * @since 1.7.9
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching the account lists.
	 */
	public function api_lists( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );

		try {
			$lists         = $this->api->get_lists();
			$settings      = $this->get_provider_settings( $account_id );
			$custom_fields = array();

			if ( ! empty( $list_id ) ) {
				switch ( $list_id ) {
					case 'leads':
						$url           = 'https://' . $settings['referer_url'] . '/api/v4/leads/custom_fields';
						$custom_fields = $this->api->get_custom_fields( $url, 'api_lists' );
						break;

					case 'companies':
						$url           = 'https://' . $settings['referer_url'] . '/api/v4/companies/custom_fields';
						$custom_fields = $this->api->get_custom_fields( $url, 'api_lists' );
						break;
					case 'contacts':
						$url           = 'https://' . $settings['referer_url'] . '/api/v4/contacts/custom_fields';
						$custom_fields = $this->api->get_custom_fields( $url, 'api_lists' );
						break;
					case 'customers':
						$url           = 'https://' . $settings['referer_url'] . '/api/v4/customers/custom_fields';
						$custom_fields = $this->api->get_custom_fields( $url, 'api_lists' );
						break;
					case 'tasks':
					case 'catalogs':
						break;
					default:
						$list_number = '';
						if ( strpos( $list_id, 'elements' ) !== false ) {
							$list_number = explode( '_', $list_id )[1];
						}

						$url           = 'https://' . $settings['referer_url'] . '/api/v4/catalogs/' . $list_number . '/custom_fields';
						$custom_fields = $this->api->get_custom_fields( $url, 'api_lists' );
						break;
				}
			}

			if ( ! empty( $lists ) ) {
				$list_array = array();
				foreach ( $lists as $key => $list ) {
					if ( empty( $list ) ) {
						continue;
					}
					$list_array[ $key ] = array(
						'id'   => $key,
						'name' => isset( $list ) ? trim( $list ) : __( 'Unknown List', 'everest-forms-pro' ),
					);
				}

				$list_array['lists']         = $list_array;
				$list_array['custom_fields'] = $custom_fields;

				return $list_array;
			} else {
				$error_msg = __( 'API list error: No lists found', 'everest-forms-pro' );
				return $this->error( $error_msg );
			}
		} catch ( \Exception $e ) {
			evf_get_logger(
				__( 'amoCRM API error', 'everest-forms-pro' ),
				$e->getMessage(),
				array(
					'type'   => array( 'Integration', 'error' ),
					'source' => 'amocrm'
				)
			);
		}
	}

	/**
	 * Connect to API.
	 *
	 * @since 1.7.9
	 *
	 * @param  string $account_id  Account ID for AWeber Contact.
	 * @return mixed array or error object
	 */
	public function api_connect( $account_id ) {
		if ( ! empty( $this->api ) && $account_id === $this->account ) {
			return $this->api;
		} else {
			$providers = get_option( 'everest_forms_integrations' );

			if ( ! empty( $providers[ $this->id ][ $account_id ]['api'] ) ) {
				$connection    = $providers[ $this->id ][ $account_id ]['api'];
				$this->account = $account_id;
				$this->api     = new API( $connection, $account_id );
				return $this->api;
			} elseif ( ! empty( $providers[ $this->id ][ $account_id ]['refresh_token'] ) ) {
				$connection    = $providers[ $this->id ][ $account_id ];
				$this->account = $account_id;
				$this->api     = new API( $connection, $this->account );
				return $this->api;
			} else {
				return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
			}
		}
	}

	/**
	 * Get provider settings.
	 *
	 * @since 1.7.9
	 *
	 * @param  number $account_id Account id.
	 */
	public function get_provider_settings( $account_id ) {
		$providers = get_option( 'everest_forms_integrations' );
		$settings  = array();

		if ( isset( $providers[ $this->id ][ $account_id ] ) ) {
			$settings = $providers[ $this->id ][ $account_id ];
		}

		return $settings;
	}

	/**
	 * Fetch API Groups.
	 *
	 * @since  1.7.9
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $list_id List ID.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return $this->error( esc_html__( 'amoCRM won\'t support Groups.', 'everest-forms-pro' ) );
	}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @since 1.7.9
	 *
	 * @param string $connection_id Connection ID for fetching the connection object.
	 * @param string $account_id    Account ID for fetching api fields.
	 * @param string $list_id       List id for fetching api fields.
	 *
	 * @return mixed array or error object
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		$this->api_connect( $account_id );
		$fld = $this->get_fields( $list_id, $account_id, 'fetch_api_fields' );
		return $fld;
	}

	/**
	 * Get fields for fetch_api_fields.
	 *
	 * @since 1.7.9
	 *
	 * @param  string $list_id      List id.
	 * @param  number $account_id   Account id.
	 */
	public function get_fields( $list_id, $account_id, $context = '' ) {
		$settings = $this->get_provider_settings( $account_id );
		$fld      = array();

		switch ( $list_id ) {
			case 'leads':
				$fld = array(
					array(
						'id'         => 'leads_name',
						'name'       => __( 'Lead Name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'name',
						'tag'        => 'lead_name',
					),
					array(
						'id'         => 'price',
						'name'       => __( 'Lead Sale', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'text',
						'tag'        => 'price',
					),
					array(
						'id'         => 'unix_created_at',
						'name'       => __( 'Created at', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'datetime',
						'tag'        => 'unix_created_at',
					),
					array(
						'id'         => 'unix_closed_at',
						'name'       => __( 'Closed at', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'datetime',
						'tag'        => 'unix_closed_at'
					),
				);

				$url           = 'https://' . $settings['referer_url'] . '/api/v4/leads/custom_fields';
				$custom_fields = $this->api->get_custom_fields( $url, $context );
				$fld           = array_merge( $fld, $custom_fields );
				break;

			case 'companies':
				$fld =
				array(
					array(
						'id'         => 'company_name',
						'name'       => __( 'Company Name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'company_name'
					),
					array(
						'id'         => 'unix_created_at',
						'name'       => __( 'Created at', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'datetime',
						'tag'        => 'unix_created_at'
					)
				);

				$url           = 'https://' . $settings['referer_url'] . '/api/v4/companies/custom_fields';
				$custom_fields = $this->api->get_custom_fields( $url, $context );
				$fld           = array_merge( $fld, $custom_fields );
				break;

			case 'contacts':
				$fld =
				array(
					array(
						'id'         => 'contacts_name',
						'name'       => __( 'Contacts Name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'contacts_name'
					),
					array(
						'id'         => 'first_name',
						'name'       => __( 'First Name', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'text',
						'tag'        => 'first_name'
					),
					array(
						'id'         => 'last_name',
						'name'       => __( 'Last Name', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'text',
						'tag'        => 'last_name'
					),
					array(
						'id'         => 'unix_created_at',
						'name'       => __( 'Created at', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'datetime',
						'tag'        => 'unix_created_at'
					)
				);

				$url           = 'https://' . $settings['referer_url'] . '/api/v4/contacts/custom_fields';
				$custom_fields = $this->api->get_custom_fields( $url, $context );
				$fld           = array_merge( $fld, $custom_fields );

				break;

			case 'catalogs':
				$fld =
				array(
					array(
						'id'         => 'catalogs_name',
						'name'       => __( 'List Name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'catalogs_name',
					),
					array(
						'id'         => 'can_add_elements',
						'name'       => __( 'Can add elements to the list?', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'vote',
						'tag'        => 'can_add_elements'
					),
					array(
						'id'         => 'can_link_multiple',
						'name'       => __( 'Can add multiple list?', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'vote',
						'tag'        => 'can_link_multiple'
					)
				);
				break;

			case 'tasks':
				$fld =
				array(
					array(
						'id'         => 'tasks_name',
						'name'       => __( 'Task Name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'tasks_name',
					),
					array(
						'id'         => 'text',
						'name'       => __( 'Task Details', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'text',
					),
					array(
						'id'         => 'tasks_result[text]',
						'name'       => __( 'Task Result Details', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'text',
						'tag'        => 'tasks_result[text]',
					),
					array(
						'id'         => 'is_completed',
						'name'       => __( 'Is Task Completed', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'vote',
						'tag'        => 'is_completed',
					),
					array(
						'id'         => 'unix_complete_till',
						'name'       => __( 'Complete At', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'datetime',
						'tag'        => 'unix_complete_till'
					)
				);
				break;

			case 'customers':
				$fld =
				array(
					array(
						'id'         => 'customers_name',
						'name'       => __( 'Customer Name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'customers_name',
					),
					array(
						'id'         => 'next_price',
						'name'       => __( 'Expected purchase value', 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => 'text',
						'tag'        => 'next_price',
					),
				);

				$url           = 'https://' . $settings['referer_url'] . '/api/v4/customers/custom_fields';
				$custom_fields = $this->api->get_custom_fields( $url, $context );
				$fld           = array_merge( $fld, $custom_fields );

				break;

			default:
				$fld =
				array(
					array(
						'id'         => 'elements_name',
						'name'       => __( 'List element name', 'everest-forms-pro' ),
						'req'        => true,
						'field_type' => 'text',
						'tag'        => 'elements_name'
					)
				);

				$list_number = '';
				if ( strpos( $list_id, 'elements' ) !== false ) {
					$list_number = explode( '_', $list_id )[1];
				}

				$url           = 'https://' . $settings['referer_url'] . '/api/v4/catalogs/' . $list_number . '/custom_fields';
				$custom_fields = $this->api->get_custom_fields( $url, $context );
				$fld           = array_merge( $fld, $custom_fields );

				break;
		}

		return $fld;
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @since 1.7.9
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

		$subscriber = array();

		// Fire for each connection.
		foreach ( $form_data['integrations'][ $this->id ] as $connection ) :
			// Check for conditional logic.
			$account_id = $connection['account_id'];
			$api        = $this->api_connect( $account_id );
			$list_id    = $connection['list_id'];

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

			$subscriber['list_id'] = $connection['list_id'];

			if ( strpos( $connection['list_id'], 'elements' ) !== false ) {
				$list_array            = explode( '_', $connection['list_id'] );
				$subscriber['type']    = $list_array[0];
				$subscriber['list_id'] = $list_array[1];
			}

			if ( $subscriber['list_id'] == 'tags' ) {
				$subscriber['entity_type'] = $connection['entity_type'];
				unset( $connection['entity_type'] );
			}

			$all_fields = $this->get_all_fields( $list_id, $account_id );

			$subscriber['attributes']['custom_fields_values'] = array();
			$enum_code                                        = '';

			foreach ( $all_fields as $crm_field ) {
				if ( in_array( $crm_field['id'], array_keys( $connection ) ) ) {

					if ( strpos( $crm_field['id'], 'custom*' ) !== false ) {
						$custom_field = explode( '*', $crm_field['id'] );
						$field_id     = $custom_field[1];
						$field_type   = $custom_field[2];
						$field_name   = $custom_field[3];

						if ( $field_type == 'select' ) {
							if ( $field_name == 'Unit' ) {
								$custom_fields = array(
									'field_id' => $field_id,
									'values'   => array(
										array(
											'value' => $connection[ $crm_field['id'] ]
										)
									)
								);
							}
						}
						array_push( $subscriber['attributes']['custom_fields_values'], $custom_fields );
					}

					continue;
				}

				// Setup merge fields.
				foreach ( $connection['fields'] as $name => $merge_field ) {

					// Check if merge vars are used.
					if ( empty( $merge_field ) ) {
						continue;
					}

					if ( $name != $crm_field['id'] ) {
						continue;
					}

					$merge_field = explode( '.', $merge_field );
					$id          = $merge_field[0]; // evf Field ID.
					$key         = ! empty( $merge_field[1] ) ? $merge_field[1] : 'value';
					$type        = ! empty( $merge_field[2] ) ? $merge_field[2] : 'text'; // MC merge field type.

					$crm_key = $crm_field['id'];
					if ( ! empty( $connection['fields'][ $crm_key ] ) ) {
						// Check if mapped form field has a value.
						if ( empty( $fields[ $id ][ $key ] ) ) {
							continue;
						}

						if ( 'payment-single' === $fields[ $id ]['type'] || 'payment-subtotal' === $fields[ $id ]['type'] || 'payment-total' === $fields[ $id ]['type'] ) {
							$value = $fields[ $id ]['amount'];
						} else {
							$value = $fields[ $id ][ $key ];
						}

						// Format edge cases pre-API call.
						if ( is_array( $value ) ) {
							switch ( $fields[ $id ]['type'] ) {
								case 'radio':
								case 'payment-radio':
									$value = ! empty( $value['label'] ) ? $value['label'] : '';
									break;

								case 'checkbox':
									$value = ! empty( $value['label'] ) && is_array( $value['label'] ) ? '||' . implode( '||', (array) $value['label'] ) . '||' : $value['label'];
									break;

								case 'address':
									$value = implode( ', ', $value );
									break;

								case 'payment-checkbox':
								case 'payment-multiple':
									$value = isset( $value['amount'] ) && ! empty( $value['amount'] ) ? $value['amount'] : 0;
									break;

								default:
									$value = implode( ' ', $value );
									break;
							}
						}

						if ( strpos( $crm_key, 'name' ) !== false ) {
							$subscriber['attributes']['name'] = $value;
						} elseif ( strpos( $crm_key, 'can_' ) !== false ) {
							if ( $value == 'yes' ) {
								$subscriber['attributes'][ $crm_key ] = true;
							} else {
								$subscriber['attributes'][ $crm_key ] = false;
							}
						} elseif ( strpos( $crm_key, 'is_' ) !== false ) {
							if ( $value == 'yes' ) {
								$subscriber['attributes'][ $crm_key ] = true;
							} else {
								$subscriber['attributes'][ $crm_key ] = false;
							}
						} elseif ( strpos( $crm_key, 'unix_' ) !== false ) {
							$date_field                                  = explode( 'unix_', $crm_key );
							$date_field_key                              = $date_field[1];
							$subscriber['attributes'][ $date_field_key ] = strtotime( $value );
						} elseif ( strpos( $crm_key, 'tasks_' ) !== false ) {
							$data_field                                  = explode( 'tasks_', $crm_key );
							$data_field_key                              = $data_field[1];
							$subscriber['attributes'][ $data_field_key ] = $value;
						} elseif ( strpos( $crm_key, 'custom*' ) !== false ) {
							$custom_field = explode( '*', $crm_key );
							$field_id     = $custom_field[1];
							$field_type   = $custom_field[2];
							$field_name   = $custom_field[3];

							if ( $field_type == 'select' ) {
								$enum_code = $value;
								continue;
							}

							if ( $field_type == 'text' ) {
								$custom_fields = array(
									'field_code' => $field_name,
									'values'     => array(
										array(
											'enum_code' => $connection[ $field_name ],
											'value'     => $value
										)
									)
								);
							}

							if ( $field_type == 'normal' ) {
								$custom_fields = array(
									'field_id' => $field_id,
									'values'   => array(
										array(
											'value' => $value
										)
									)
								);
							}

							if ( $field_type == 'checkbox' ) {
								if ( $value == 'yes' ) {
									$checkbox_value = true;
								} else {
									$checkbox_value = false;
								}

								$custom_fields = array(
									'field_id' => $field_id,
									'values'   => array(
										array(
											'value' => $checkbox_value
										)
									)
								);
							}

							array_push( $subscriber['attributes']['custom_fields_values'], $custom_fields );
						} else {
							$subscriber['attributes'][ $crm_key ] = $value;
						}
					}
				}
			}
			if ( empty( $subscriber['attributes']['custom_fields_values'] ) ) {
				unset( $subscriber['attributes']['custom_fields_values'] );
			}

			$response = $this->api->subscribe( $subscriber, $form_data['id'], $entry_id );
			$logger   = new \EVF_Logger();

			if ( is_wp_error( $response ) ) {
				$error_msg     = $response->get_error_message();
				$response_data = (array) $response;
				$logger->log( 'errors', esc_html__( 'amoCRM : ' . $error_msg, 'everest-forms-pro' ), array( 'source' => 'amocrm' ) );
			} else {
				$logger->log( 'info', esc_html__( 'amoCRM : Subscription successfully.', 'everest-forms-pro' ), array( 'source' => 'amocrm' ) );
			}

		endforeach;

	}

	/**
	 * Get all fields.
	 *
	 * @since 1.7.9
	 *
	 * @param  string $list_id      List id.
	 * @param  number $account_id   Account id.
	 */
	protected function get_all_fields( $list_id, $account_id ) {
		$fields = $this->get_fields( $list_id, $account_id, 'during_form_submission' );

		$all_fields = array();
		foreach ( $fields as $field ) {
			$key_data       = array();
			$key_data['id'] = $field['id'];
			if ( $field['req'] ) {
				$key_data['req'] = $field['req'];
				array_push( $all_fields, $key_data );
			} else {
				$key_data['req'] = 0;
				array_push( $all_fields, $key_data );
			}
		}

		return $all_fields;
	}

	/**
	 * Output Authentication HTML.
	 *
	 * @since 1.7.9
	 *
	 * @param string $output Output HTML.
	 */
	public function outputs_authentication_html( $output ) {
		ob_start();
		?>
		<button type="submit" class="everest-forms-hidden everest-forms-btn everest-forms-btn-primary " data-source="<?php echo esc_attr( $this->id ); ?>"><?php esc_html_e( 'Connect to amoCRM', 'everest-forms-pro' ); ?></button>
		<a href="<?php echo esc_url( filter_var( amoCRMIntegrations::create_auth_url(), FILTER_SANITIZE_URL ) ); ?>"" class="everest-forms-btn everest-forms-btn-primary everest-forms-integration-open-window" data-source="<?php echo esc_attr( $this->id ); ?>">
			<?php esc_html_e( 'Get Access Code', 'everest-forms-pro' ); ?>
		</a>
		<?php
		$output = ob_get_clean();

		return $output;
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @since 1.7.9
	 *
	 * @param array  $data    Data passed for API authorization.
	 * @param string $form_id Form ID.
	 * @throws \Exception Exception.
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
		try {
			$connection = array(
				'client_id'  => trim( $data['client_id'] ),
				'secret_key' => trim( $data['secret_key'] ),
				'auth_code'  => trim( $data['access_code'] ),
			);

			$client   = new API( $connection );
			$settings = $client->create_access_token( $connection['auth_code'], $data['referer_url'], $connection );

			if ( ! is_wp_error( $settings ) ) {
				$settings                = (array) $settings;
				$settings['status']      = true;
				$settings['label']       = sanitize_text_field( $data['label'] );
				$settings['date']        = time();
				$api_key_to_be_connected = uniqid();
				$connected_accounts      = get_option( 'everest_forms_integrations', array() );
				$connected_accounts['amocrm'][ $api_key_to_be_connected ] = $settings;

				update_option( 'everest_forms_integrations', $connected_accounts );

				return $api_key_to_be_connected;
			} else {
				$error_message = $settings->get_error_message();

				throw new \Exception( $error_message );
			}
		} catch ( \Exception $e ) {
			return $this->error( __( $e->getMessage(), 'everest-forms-pro' ) );
		}
	}
}
