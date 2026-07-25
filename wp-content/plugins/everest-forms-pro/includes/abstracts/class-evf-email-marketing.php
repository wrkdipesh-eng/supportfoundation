<?php
/**
 * Abstract EVF_Email_Marketing class.
 *
 * @package EverestForms/Classes
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Email_Marketing class.
 */
abstract class EVF_Email_Marketing {

	/**
	 * Integration ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Integration icon.
	 *
	 * @var mixed
	 */
	public $icon = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'everest_forms_available_integrations', array( $this, 'register_integration' ) );
		add_action( 'everest_forms_providers_panel_content', array( $this, 'output_panel_content' ) );
		add_action( 'everest_forms_integration_connections_' . $this->id, array( $this, 'output_connections_list' ) );
		add_action( 'everest_forms_process_complete', array( $this, 'process_feed' ), 5, 4 );

		// AJAX Events.
		$this->add_ajax_events();
	}

	/**
	 * Get form data
	 *
	 * @return array form data.
	 */
	private function form_data() {
		$form_data = array();

		if ( ! empty( $_GET['form_id'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$form_data = EVF()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return $form_data;
	}

	/**
	 * Get integration ID
	 *
	 * @return array Integration stored data.
	 */
	private function get_integration() {
		$integrations = get_option( 'everest_forms_integrations', array() );

		return in_array( $this->id, array_keys( $integrations ), true ) ? $integrations[ $this->id ] : array();
	}

	/**
	 * Register integration.
	 *
	 * @param  array $integrations List of integrations.
	 * @return array of registered integrations.
	 */
	public function register_integration( $integrations ) {
		$integrations[ $this->id ] = array(
			'id'   => $this->id,
			'icon' => $this->icon,
			'name' => $this->name,
		);

		return $integrations;
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public function add_ajax_events() {
		$ajax_events = array(
			'new_connection_add',
			'add_account_form',
			'account_select',
			'account_list_select',
			'airtable_schema',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_everest_forms_' . $ajax_event . '_' . $this->id, array( $this, $ajax_event ) );
		}
	}

	/**
	 * Handle request for airtable schema.
	 *
	 * @since 1.7.7
	 */
	public function airtable_schema() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		$integration            = $this->get_integration();
		$form_id                = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$source                 = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$list_id                = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '';
		$data                   = EVF()->form->get( absint( $form_id ), array( 'content_only' => true ) );
		$current_base_schema_id = isset( $_POST['schema_id'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_id'] ) ) : '';
		$connection             = array();

		if ( isset( $data['integrations'] ) && ! empty( $data['integrations'][ $source ] ) ) {
			foreach ( $data['integrations'][ $source ] as $connection_id => $connection_data ) {
				if ( $connection_data['list_id'] === $list_id ) {
					$connection = $connection_data;
				} else {
					$connection = array(
						'account_id'     => isset( $_POST['account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['account_id'] ) ) : '',
						'list_id'        => isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '',
						'base_schema_id' => isset( $_POST['schema_id'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_id'] ) ) : '',
					);
				}
			}
		}

		if ( isset( $connection['base_schema_id'] ) && $connection['base_schema_id'] != $current_base_schema_id ) {
			$connection['current_base_schema_id'] = $current_base_schema_id;
		}

		$fields = $this->output_account_fields(
			sanitize_text_field( $_POST['connection_id'] ),
			! empty( $connection ) ? $connection : array(
				'account_id'     => sanitize_text_field( $_POST['account_id'] ),
				'list_id'        => sanitize_text_field( $_POST['list_id'] ),
				'base_schema_id' => isset( $_POST['schema_id'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_id'] ) ) : '',
			),
			$_POST['form_id']
		);

		$conditional_logic = $this->conditional_logic(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
				'account_id' => sanitize_text_field( $_POST['account_id'] ),
				'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
		);

		wp_send_json_success(
			array(
				'html' => $fields . $conditional_logic,
			)
		);
	}

	/**
	 * AJAX Integration disconnect.
	 */
	public function new_connection_add() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) && ! isset( $_POST['name'], $_POST['id'] ) ) {
			wp_die( -1 );
		}

		$connection = $this->output_integration_connection( '', array( 'connection_name' => sanitize_text_field( $_POST['name'] ) ), sanitize_text_field( $_POST['id'] ) ); // @codingStandardsIgnoreLine

		wp_send_json_success(
			array(
				'html'          => $connection['html'],
				'connection_id' => $connection['connection_id'],
			)
		);
	}

	/**
	 * AJAX Add account form.
	 */
	public function add_account_form() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		$auth = $this->authorize_api( wp_parse_args( $_POST['data'], array() ), isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '' ); // @codingStandardsIgnoreLine

		if ( is_wp_error( $auth ) ) {
			wp_send_json_error(
				array(
					'error' => $auth->get_error_message(),
				)
			);
		} else {
			$connection_id = isset( $_POST['connection_id'] ) ? sanitize_text_field( wp_unslash( $_POST['connection_id'] ) ) : '';
			$accounts      = $this->output_connected_accounts(
				$connection_id,
				array(
					'account_id' => $auth,
				)
			);

			wp_send_json_success(
				array(
					'html' => $accounts,
				)
			);
		}
	}

	/**
	 * Account Select function - Outputs an array of account lists.
	 */
	public function account_select() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}

		$lists = $this->output_account_lists( sanitize_text_field( $_POST['connection_id'] ), array( 'account_id' => sanitize_text_field( $_POST['account_id'] ) ) ); // @codingStandardsIgnoreLine

		if ( is_wp_error( $lists ) ) {
			wp_send_json_error(
				array(
					'error' => $lists->get_error_message(),
				)
			);
		} else {
			wp_send_json_success(
				array(
					'html' => $lists,
				)
			);
		}
	}

	/**
	 * Account list Selection function
	 */
	public function account_list_select() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_die( -1 );
		}
		$integration = $this->get_integration();
		$form_id     = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$source      = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$list_id     = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '';
		$data        = EVF()->form->get( absint( $form_id ), array( 'content_only' => true ) );
		$connection  = array();

		if ( isset( $data['integrations'] ) && ! empty( $data['integrations'][ $source ] ) ) {
			foreach ( $data['integrations'][ $source ] as $connection_id => $connection_data ) {
				if ( isset( $connection_data['list_id'] ) && $connection_data['list_id'] === $list_id && $connection_data['account_id'] === $_POST['account_id'] ) {
					$connection = $connection_data;
				} else {
					$connection = array(
						'account_id' => isset( $_POST['account_id'] ) ? sanitize_text_field( wp_unslash( $_POST['account_id'] ) ) : '',
						'list_id'    => isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '',
					);
				}
			}
		}

		// @codingStandardsIgnoreStart
		$fields = $this->output_account_fields(
		sanitize_text_field( $_POST['connection_id'] ),
		! empty ($connection) ? $connection : array(
		'account_id' => sanitize_text_field( $_POST['account_id'] ),
		'list_id'    => sanitize_text_field( $_POST['list_id'] ),
		'base_schema_id' => isset( $_POST['schema_id'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_id'] ) ) : '',
		),
		$_POST['form_id']
		);

		if ( is_wp_error( $fields ) ) {
			wp_send_json_error(
			array(
			'error' => $fields->get_error_message(),
			)
		);
	} else {

			$organization = $this->output_account_organization(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$company = $this->output_contact_company(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$contact_status = $this->output_contact_status(
				sanitize_text_field( $_POST['connection_id'] ),
				array(
				'account_id' => sanitize_text_field( $_POST['account_id'] ),
				'list_id'    => sanitize_text_field( $_POST['list_id'] ),
				)
			);

			$contact_details = $this->output_contact_detail(
				sanitize_text_field( $_POST['connection_id'] ),
				array(
				'account_id' => sanitize_text_field( $_POST['account_id'] ),
				'list_id'    => sanitize_text_field( $_POST['list_id'] ),
				)
			);

			$contact_lead_source = $this->output_contact_lead_source(
				sanitize_text_field( $_POST['connection_id'] ),
				array(
				'account_id' => sanitize_text_field( $_POST['account_id'] ),
				'list_id'    => sanitize_text_field( $_POST['list_id'] ),
				)
			);

			$label = $this->output_account_person_label(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$visible = $this->output_account_visible(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$owner = $this->output_account_owner(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$currency = $this->output_account_currency(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$groups = $this->output_groups(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$custom_fields = $this->output_custom_fields(
			sanitize_text_field( $_POST['connection_id'] ),
			! empty ($connection) ? $connection : array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			),
			$_POST['form_id']
			);

			$tags = $this->output_tags(
			sanitize_text_field( $_POST['connection_id'] ),
			! empty ($connection) ? $connection : array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			),
			$_POST['form_id']
			);

			$note = $this->output_note(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			),
			$_POST['form_id']
			);

			$options = $this->output_options(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$conditional_logic = $this->conditional_logic(
			sanitize_text_field( $_POST['connection_id'] ),
			array(
			'account_id' => sanitize_text_field( $_POST['account_id'] ),
			'list_id'    => sanitize_text_field( $_POST['list_id'] ),
			)
			);

			$deal_status = $this->output_deal_status(
				sanitize_text_field( $_POST['connection_id'] ),
				array(
				'account_id' => sanitize_text_field( $_POST['account_id'] ),
				'list_id'    => sanitize_text_field( $_POST['list_id'] ),
				)
			);

			$output = '';
			if( 'trello' === $source){
				$lists    = $this->api_lists( sanitize_text_field( $_POST['connection_id'] ), sanitize_text_field( $_POST['account_id'] ), sanitize_text_field( $_POST['list_id'] ) );
				$providers = get_option( 'everest_forms_integrations' );

				if ( ! empty( $providers[ $source ][ sanitize_text_field( $_POST['account_id']) ]['api'] ) ) {
					$api_key       = $providers[ $source ][ sanitize_text_field( $_POST['account_id']) ]['api'];
				} else {
					return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
				}

				$render_selected_data 	= isset( $data['integrations']['trello'][sanitize_text_field( $_POST['connection_id'] )] ) ? $data['integrations']['trello'][sanitize_text_field( $_POST['connection_id'] )] : '';
				$board_list_selected 	= ! empty( $render_selected_data['board_list_id'] ) ? $render_selected_data['board_list_id'] : '';
				$board_label_selected 	= ! empty( $render_selected_data['board_label_id'] ) ? $render_selected_data['board_label_id'] : '';
				$board_member_selected 	= ! empty( $render_selected_data['board_member_id'] ) ? $render_selected_data['board_member_id'] : '';

					$output .= '<div class="evf-marketing-integration evf-connection-block"">';
					$output .= '<h4>Board List</h4>';
					$output      .= sprintf( '<select id="evf-trello-lists" name="integrations[%s][%s][board_list_id]">', $source, sanitize_text_field( $_POST['connection_id'] ) );

					foreach ( $lists['lists'] as $id => $list ) {
					$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $list['id'], $board_list_selected, false ),
					esc_attr( $list['name'] )
				);
					}
					$output .= '</select>';
					$output .= '<h4>Board Label</h4>';
					$output .= sprintf( '<select id="evf-trello-labels" name="integrations[%s][%s][board_label_id]">', $source, sanitize_text_field( $_POST['connection_id'] ) );

					foreach ( $lists['labels'] as $id => $list ) {
					$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $list['id'], $board_label_selected, false ),
					esc_attr( $list['name'] )
				);
					}
					$output .= '</select>';
					$output .= '<h4>Board Member</h4>';
					$output .= sprintf( '<select id="evf-trello-members" name="integrations[%s][%s][board_member_id]">', $source, sanitize_text_field( $_POST['connection_id'] ) );

					foreach ( $lists['members'] as $id => $list ) {
					$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $list['id'], $board_member_selected, false ),
					esc_attr( $list['name'] )
				);
					}
					$output .= '</select></div>';
				}

				if( 'airtable' === $source ){
				$lists    = $this->api_lists( sanitize_text_field( $_POST['connection_id'] ), sanitize_text_field( $_POST['account_id'] ), sanitize_text_field( $_POST['list_id'] ) );
				$providers = get_option( 'everest_forms_integrations' );

				$render_selected_data 	= isset( $data['integrations']['airtable'][sanitize_text_field( $_POST['connection_id'] )] ) ? $data['integrations']['airtable'][sanitize_text_field( $_POST['connection_id'] )] : '';
				$schema_selected 	= ! empty( $render_selected_data['base_schema_id'] ) ? $render_selected_data['base_schema_id'] : '';

				if ( ! empty( $providers[ $source ][ sanitize_text_field( $_POST['account_id']) ]['api'] ) ) {
					$api_key       = $providers[ $source ][ sanitize_text_field( $_POST['account_id']) ]['api'];
					} else {
					return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
					}

				$output .= '<div class="evf-marketing-integration evf-connection-block"">';
				$output .= '<h4>Select Schema</h4>';
				$output .= sprintf( '<select id="evf-airtable-schema" name="integrations[%s][%s][base_schema_id]">', $source, sanitize_text_field( $_POST['connection_id'] ) );
				foreach ( $lists['base_schema'] as $id => $list ) {
					$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $list['id'], $schema_selected, false ),
					esc_attr( $list['name'] )
				);
				}
				$output .= '</select></div>';

				}

				if ('amocrm' === $source) {
				$lists    = $this->api_lists( sanitize_text_field( $_POST['connection_id'] ), sanitize_text_field( $_POST['account_id'] ), sanitize_text_field( $_POST['list_id'] ) );
				$providers = get_option( 'everest_forms_integrations' );

				if ( ! empty( $providers[ $source ][ sanitize_text_field( $_POST['account_id']) ]['access_token'] ) ) {
						$access_token       = $providers[ $source ][ sanitize_text_field( $_POST['account_id']) ]['access_token'];
					} else {
					return $this->error( __( 'API connection error', 'everest-forms-pro' ) );
					}


				if ( sanitize_text_field( $_POST['list_id'] ) == 'tasks') {
					$output .= '<div class="evf-marketing-integration evf-connection-block"">';
					$output .= '<h4>Task Entity Type</h4>';

					$output .= sprintf( '<select id="evf-amocrm-entity-type" name="integrations[%s][%s][entity_type]">', $this->id, sanitize_text_field( $_POST['connection_id'] ) );

					$entity_type =array(
							'leads'     => 'Lead',
							'contacts'  => 'Contact',
							'companies' => 'Company',
							'customers' => 'Customer'
						);

					foreach ( $entity_type as $key => $value ) {
						$output .= sprintf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $key ),
							selected( isset( $_POST['entity_type'] ) ? sanitize_text_field( $_POST['entity_type'] ) : '', $value, false ),
							esc_attr( $value )
						);
					}
						$output .= '</select>';
						$output .= '</div>';
					}
				foreach ( $lists['custom_fields'] as $field ) {
					if ( ! isset( $field['options'] ) ) {
						continue;
						}

					$output .= '<div class="evf-marketing-integration evf-connection-block"">';

					$field_type = $field['api_list_type'] ;
					$saved_data =isset( $data['integrations']['amocrm'][  $_POST['connection_id'] ] ) ? $data['integrations']['amocrm'][ sanitize_text_field( $_POST['connection_id'] )] : '';

					$output .= '<h4>' . $field['name'] . '</h4>';
					$output .= sprintf( '<select id="evf-amocrm-%s" name="integrations[%s][%s][%s]">', strtolower( $field_type ), $this->id, sanitize_text_field( $_POST['connection_id'] ), $field_type );
					foreach ( $field['options'] as $key => $value ) {
						$output .= sprintf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $key ),
							selected( isset($saved_data[$field_type]) ? $saved_data[$field_type] : '', $value, false ),
							esc_attr( $value )
						);
					}
					$output .= '</select>';

					$output .= '</div>';
					}
					}

			wp_send_json_success(
			array(
			'html' =>  $output . $owner . $organization . $company . $contact_status . $contact_details . $deal_status . $contact_lead_source . $label . $currency . $visible . $groups . $fields . $custom_fields . $tags . $note . $options . $conditional_logic,
			)
			);
			}
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Outputs the connection lists on sidebar.
	 */
	public function output_connections_list() {
		$form_data   = $this->form_data();
		$integration = $this->get_integration();
		if ( empty( $form_data['integrations'][ $this->id ] ) || empty( $integration ) ) {
			$class = 'empty-list';
		} else {
			$class = '';
		}
		?>
			<div class="everest-forms-active-connections">
				<button class="everest-forms-btn everest-forms-btn-primary everest-forms-connections-add" data-form_id="<?php echo absint( $_GET['form_id'] ); /* @codingStandardsIgnoreLine */ ?>" data-source="<?php echo esc_attr( $this->id ); ?>" data-type="<?php echo ( 'google_sheets' === $this->id ? esc_attr( 'spreadsheet' ) : esc_attr( 'connection' ) ); ?>">
				<?php ( 'google_sheets' === $this->id ? esc_html_e( 'Connect New Spreadsheet', 'everest-forms-pro' ) : esc_html_e( 'Add New Connection', 'everest-forms-pro' ) ); ?>
				</button>
				<ul class="everest-forms-active-connections-list <?php echo esc_attr( $class ); ?>">
			<?php if ( ! empty( $form_data['integrations'][ $this->id ] ) && ! empty( $integration ) ) : ?>
					<h4><?php echo esc_html__( $this->name . ' connections', 'everest-forms-pro' ); /* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText */ ?> </h4>
					<?php
					endif;

			if ( ! empty( $form_data['integrations'][ $this->id ] ) && ! empty( $integration ) ) {
				foreach ( $form_data['integrations'][ $this->id ] as $connection_id => $connection_data ) {
					?>
							<li data-connection-id="<?php echo $connection_id; /* @codingStandardsIgnoreLine */ ?>">
							<a class="user-nickname" href="#"><?php echo esc_html( $connection_data['connection_name'] ); ?></a>
							<a href="#"><span class="toggle-remove">Remove</a>
							</li>
						<?php
				}
			}
			?>
					</ul>
			</div>
			<?php
	}

	/**
	 * Output builder panel content.
	 */
	public function output_panel_content() {
		$form_data   = $this->form_data();
		$integration = $this->get_integration();

		?>
		<div class="evf-panel-content-section evf-panel-content-section-<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>-provider">
			<div class="evf-content-section-title"><?php echo esc_html( $this->name ); ?></div>
			<div class="evf-provider-connections-wrap evf-clear">
				<div class="evf-provider-connections">
			<?php

			if ( ! empty( $form_data['integrations'][ $this->id ] ) && ! empty( $integration ) ) {
				foreach ( $form_data['integrations'][ $this->id ] as $connection_id => $connection ) {
					foreach ( $integration as $account_id => $connections ) {
						if ( ! empty( $connection['account_id'] ) && $account_id === $connection['account_id'] ) {
							$output = $this->output_integration_connection( $connection_id, $connection, $form_data );
							echo $output['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					}
				}
			} elseif ( 'google_sheets' !== $this->id ) {
					echo '<div class="everest-form-add-connection-notice">' . esc_html_e( 'Please add a Connection.', 'everest-forms-pro' ) . '</div>';
			} else {
				echo '<div class="everest-form-add-connection-notice">' . esc_html_e( 'Please connect a Spreadsheet.', 'everest-forms-pro' ) . '</div>';
			}
			?>
				</div>
			</div>
		</div>
			<?php
	}

	/**
	 * Output integration connection.
	 *
	 * @param  string $connection_id Connection ID.
	 * @param  array  $connection    Connection data.
	 * @param  array  $form_data     Form data.
	 */
	public function output_integration_connection( $connection_id, $connection = array(), $form_data = array() ) {
		$this->id      = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : $this->id; // @codingStandardsIgnoreLine
		$connection_id = empty( $connection_id ) ? 'connection_' . uniqid() : $connection_id;
		if ( empty( $connection ) || empty( $form_data ) ) {
			return;
		}

		$account_lists         = $this->output_account_lists( $connection_id, $connection );
		$acount_owner          = $this->output_account_owner( $connection_id, $connection );
		$account_organization  = $this->output_account_organization( $connection_id, $connection );
		$account_person_label  = $this->output_account_person_label( $connection_id, $connection );
		$account_visible       = $this->output_account_visible( $connection_id, $connection );
		$account_currency      = $this->output_account_currency( $connection_id, $connection );
		$group_lists           = $this->output_groups( $connection_id, $connection );
		$account_fields        = $this->output_account_fields( $connection_id, $connection, $form_data, $this->id );
		$account_custom_fields = $this->output_custom_fields( $connection_id, $connection, $form_data );
		$tags                  = $this->output_tags( $connection_id, $connection, $form_data );
		$note                  = $this->output_note( $connection_id, $connection, $form_data );
		$company               = $this->output_contact_company( $connection_id, $connection );
		$contact_status        = $this->output_contact_status( $connection_id, $connection );
		$contact_lead_source   = $this->output_contact_lead_source( $connection_id, $connection );
		$contact_details       = $this->output_contact_detail( $connection_id, $connection );
		$deal_status           = $this->output_deal_status( $connection_id, $connection );

		$output  = sprintf( '<div class="evf-provider-connection" data-provider="%s" data-connection_id="%s">', $this->id, $connection_id );
		$output .= '<div class="evf-provider-connection-header">';
		$output .= sprintf( '<input type="hidden" name="integrations[%s][%s][connection_name]" value="%s">', $this->id, $connection_id, esc_attr( $connection['connection_name'] ) );
		$output .= '</div>';
		$output .= $this->authentication_form();
		$output .= $this->output_connected_accounts( $connection_id, $connection );

		if ( ! is_wp_error( $account_lists ) ) {
			$output .= $account_lists;
		}

		if ( ! is_wp_error( $acount_owner ) ) {
			$output .= $acount_owner;
		}

		if ( ! is_wp_error( $account_organization ) ) {
			$output .= $account_organization;
		}

		if ( ! is_wp_error( $company ) ) {
			$output .= $company;
		}

		if ( ! is_wp_error( $contact_status ) ) {
			$output .= $contact_status;
		}

		if ( ! is_wp_error( $contact_lead_source ) ) {
			$output .= $contact_lead_source;
		}

		if ( ! is_wp_error( $contact_details ) ) {
			$output .= $contact_details;
		}

		if ( ! is_wp_error( $deal_status ) ) {
			$output .= $deal_status;
		}

		if ( ! is_wp_error( $account_person_label ) ) {
			$output .= $account_person_label;
		}

		if ( ! is_wp_error( $account_currency ) ) {
			$output .= $account_currency;
		}

		if ( ! is_wp_error( $account_visible ) ) {
			$output .= $account_visible;
		}

		if ( ! is_wp_error( $group_lists ) ) {
			$output .= $group_lists;
		}

		if ( ! is_wp_error( $account_fields ) ) {
			$output .= $account_fields;
		}

		if ( ! is_wp_error( $account_custom_fields ) ) {
			$output .= $account_custom_fields;
		}

		if ( ! is_wp_error( $tags ) ) {
			$output .= $tags;
		}

		if ( ! is_wp_error( $note ) ) {
			$output .= $note;
		}
		$output .= $this->output_options( $connection_id, $connection );
		$output .= $this->conditional_logic( $connection_id, $connection, $form_data );
		$output .= '</div>';

		return array(
			'html'          => $output,
			'connection_id' => $connection_id,
		);
	}

	/**
	 * Integration authentication form.
	 */
	public function authentication_form() {
		$this->id     = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : $this->id; // @codingStandardsIgnoreLine
		$this->name   = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : $this->name; // @codingStandardsIgnoreLine
		$integration  = $this->get_integration();
		$hidden_class = empty( $integration ) ? '' : ' hidden';
		$form_class   = '';

		if ( 'constant_contact' === $this->id ) {
			$form_class = 'constant-contact-connection-form';
		}

		if ( 'hubspot' === $this->id ) {
			$form_class = 'hubspot-connection-form';
		}

		if ( 'aweber' === $this->id ) {
			$form_class = 'aweber-connection-form';
		}

		/**
		 * Add from class to fix design in form builder for amoCRM.
		 *
		 * @since 1.7.9
		 */
		if ( 'amocrm' === $this->id ) {
			$form_class = 'amocrm-connection-form';
		}

		if ( 'clever-reach' === $this->id ) {
			$form_class = 'cleverreach-connection-form';
		}

		if ( 'getgist' === $this->id ) {
			$form_class = 'getgist-connection-form';
		}

		$output  = '<div class="everest-forms-source-account-add evf-connection-block' . esc_attr( $hidden_class ) . '">';
		$output .= '<h4 class="new-account-title">' . ( 'google_sheets' === $this->id ? __( 'Add New Spreadsheet', 'everest-forms-pro' ) : __( 'Add New Account', 'everest-forms-pro' ) ) . '</h4>';
		$output .= '<div class="evf-connection-form ' . $form_class . '">';
		if ( 'salesforce' === $this->id ) {
			$output .= '<input type="text" data-name="client_id" class="everest_forms_salesforce_consuemr_key" placeholder="' . esc_html__( 'Consumer ID', 'everest-forms-pro' ) . '">';
			$output .= '<input type="text" data-name="client_secret" class="everest_forms_salesforce_consumer_secret" placeholder="' . esc_html__( 'Consumer Secret', 'everest-forms-pro' ) . '">';
			$output .= '<input type="hidden" data-name="response_type" value="code">';
			$output .= '<input type="hidden" data-name="scope" value="api refresh_token">';
			$output .= '<input type="hidden" data-name="prompt" value="login consent">';
		} elseif ( 'google_sheets' === $this->id ) {
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="spreadsheetId" placeholder="' . esc_attr__( 'Google Spreadsheet ID', 'everest-forms-pro' ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="label" placeholder="' . esc_attr__( 'Google Spreadsheet Nickname', 'everest-forms-pro' ) . '" class="everest-forms-required">';
		} elseif ( 'activecampaign' === $this->id ) {
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apiurl" placeholder="' . sprintf( esc_attr__( '%s API URL', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apikey" placeholder="' . sprintf( esc_attr__( '%s API Key', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Nickname*/
			$output .= '<input type="text" data-name="label" placeholder="' . sprintf( esc_attr__( '%s Account Nickname', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
		} elseif ( 'campaign_monitor' === $this->id ) {
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apikey" placeholder="' . sprintf( esc_attr__( '%s API Key', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="client_id" placeholder="' . sprintf( esc_attr__( '%s Client ID', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Nickname*/
			$output .= '<input type="text" data-name="label" placeholder="' . sprintf( esc_attr__( '%s Account Nickname', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
		} elseif ( 'zoho' === $this->id ) {
			$output .= '<label><strong>' . esc_html__( 'Zoho Account URL: ', 'everest-forms-pro' ) . '</strong>';
			$output .= '<select data-name="account_url" class="everest_forms_zoho_account_url everest-forms-required">
				<option value="https://accounts.zoho.com">' . esc_html__( 'United States (US)', 'everest-forms-pro' ) . '</option>
				<option value="https://accounts.zoho.eu">' . esc_html__( 'Europe (EU)', 'everest-forms-pro' ) . '</option>
				<option value="https://accounts.zoho.com.au">' . esc_html__( 'Australia (AU)', 'everest-forms-pro' ) . '</option>
				<option value="https://accounts.zoho.com.cn">' . esc_html__( 'China (CN)', 'everest-forms-pro' ) . '</option>
				<option value="https://accounts.zoho.in">' . esc_html__( 'India (IN)', 'everest-forms-pro' ) . '</option>
				<option value="https://accounts.zoho.jp">' . esc_html__( 'Japan (JP)', 'everest-forms-pro' ) . '</option>
				</select></label> ';
			/* translators: %s Zoho API Client ID*/
			$output .= '<label><strong>' . esc_html__( 'Zoho Client ID: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="client_id" class="' . esc_attr( 'everest_forms_zoho_client_id everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( '%s Client ID', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '"></label>';
			/* translators: %s Zoho API Client Secret*/
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Zoho Client Secret: ', 'everest-forms-pro' ) . '</strong><input type="password" data-name="client_secret" class="' . esc_attr( 'everest_forms_zoho_client_secret everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( '%s Client Secret', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '"></label>';
			/* translators: %s Zoho Account Nick Name*/
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Zoho Account Nickname: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="label" class="' . esc_attr( 'everest_forms_zoho_label everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( '%s Account Nickname', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Zoho Access Code: ', 'everest-forms-zoho' ) . '</strong><input type="text" name="' . esc_attr( 'auth_code' ) . '" data-name="auth_code" class="' . esc_attr( 'everest_forms_zoho_auth_code everest-forms-required' ) . '" placeholder="' . esc_attr__( 'Enter Zoho Access Code', 'everest-forms-zoho' ) . '"></label>';
		} elseif ( 'constant_contact' === $this->id ) {
			$output .= '<label><strong>' . esc_html__( 'Constant Contact Client ID: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="client_id" class="' . esc_attr( 'everest_forms_constant_contact_client_id everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter  CC Client ID', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Constant Contact Client Secret: ', 'everest-forms-pro' ) . '</strong><input type="password" data-name="client_secret" class="' . esc_attr( 'everest_forms_constant_contact_client_secret everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter CC Client Secret', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Constant Contact Account Nickname: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="label" class="' . esc_attr( 'everest_forms_constant_contact_label everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter CC Account Nickname', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Constant Contact Access Code: ', 'everest-forms-pro' ) . '</strong><input type="text" name="' . esc_attr( 'auth_code' ) . '" data-name="auth_code" class="' . esc_attr( 'everest_forms_constant_contact_auth_code everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter CC Access Code', 'everest-forms-pro' ) ) . '"></label>';
		} elseif ( 'hubspot' === $this->id ) {
			$output .= '<label><strong>' . esc_html__( 'HubSpot Client ID: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="client_id" class="' . esc_attr( 'everest_forms_hubspot_client_id everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter  HubSpot Client ID', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'HubSpot Client Secret: ', 'everest-forms-pro' ) . '</strong><input type="password" data-name="client_secret" class="' . esc_attr( 'everest_forms_hubspot_client_secret everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter HubSpot Client Secret', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'HubSpot Account Nickname: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="label" class="' . esc_attr( 'everest_forms_hubspot_label everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter HubSpot Account Nickname', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'HubSpot Access Code: ', 'everest-forms-pro' ) . '</strong><input type="text" name="' . esc_attr( 'auth_code' ) . '" data-name="auth_code" class="' . esc_attr( 'everest_forms_hubspot_auth_code everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter HubSpot Access Code', 'everest-forms-pro' ) ) . '"></label>';
		} elseif ( 'aweber' === $this->id ) {
			$output .= '<label><strong>' . esc_html__( 'Aweber Client ID: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="client_id" class="' . esc_attr( 'everest_forms_aweber_client_id everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter  Aweber Client ID', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<div class="everest-forms-hidden">';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Aweber Client Secret: ', 'everest-forms-pro' ) . '</strong><input type="password" data-name="client_secret" class="' . esc_attr( 'everest_forms_aweber_client_secret everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter Aweber Client Secret', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Aweber Account Nickname: ', 'everest-forms-pro' ) . '</strong><input type="text" data-name="label" class="' . esc_attr( 'everest_forms_aweber_label everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter Aweber Account Nickname', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Aweber Access Code: ', 'everest-forms-pro' ) . '</strong><input type="text" name="' . esc_attr( 'auth_code' ) . '" data-name="auth_code" class="' . esc_attr( 'everest_forms_aweber_auth_code everest-forms-required' ) . '" placeholder="' . sprintf( esc_attr__( 'Enter Aweber Access Code', 'everest-forms-pro' ) ) . '"></label>';
			$output .= '</div>';
		} elseif ( 'amocrm' === $this->id ) {
			$output .= '<ol type="1">';
			$output .= '<li>' . sprintf(
				esc_html__( 'Create an account on %s.', 'everest-forms-pro' ),
				'<a href="https://www.kommo.com/" target="_blank">amoCRM</a>'
			) . '</li>';
			$output .= '<li><strong>' . esc_html__( 'Navigate to Settings > Integrations, then click on Create Integration in the top-right corner.', 'everest-forms-pro' ) . '</strong></li>';
			$output .= '<li>' . esc_html__( 'Set the redirect URL to the following:', 'everest-forms-pro' ) . '<br>';
			$output .= '<strong>' . esc_html( home_url('/wp-admin/?evf_amocrm_auth=true') ) . '</strong>';
			$output .= '<br>' . esc_html__( 'Make sure to check the Allow Access: All option.', 'everest-forms-pro' ) . '</li>';
			$output .= '<li>' . esc_html__( 'Enter a name for your integration and provide a short description, then save the settings.', 'everest-forms-pro' ) . '</li>';
			$output .= '<li>' . esc_html__( 'Under Private Integrations, find your new integration, click on it, and go to Keys and Scopes to retrieve the Secret Key and Integration ID.', 'everest-forms-pro' ) . '</li>';
			$output .= '</ol>';
			$output .= '<label><strong>' . esc_html__( 'Integration ID: ', 'everest-forms-pro' ) . '</strong><input type="text" name="client_id" placeholder="' . esc_attr__( 'Integration ID', 'everest-forms-pro' ) . '" class="everest_forms_amocrm_client_id" required></label>';
			$output .= '<div class="everest-forms-hidden">';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Secret Key: ', 'everest-forms-pro' ) . '</strong><input type="password" name="secret_key" placeholder="' . esc_attr__( 'Secret Key ', 'everest-forms-pro' ) . '" class="everest_forms_amocrm_secret_key everest-forms-required"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Account Nickname: ', 'everest-forms-pro' ) . '</strong><input type="text"  data-name="label" placeholder="' . esc_attr__( 'Nickname', 'everest-forms-pro' ) . '" class="' . esc_attr( 'evf-nickname everest-forms-required' ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Access Code: ', 'everest-forms-pro' ) . '</strong><input type="password" name="' . esc_attr( 'access_code' ) . '" class="' . esc_attr( 'everest_forms_amocrm_access_code everest-forms-required' ) . '" placeholder="' . esc_attr__( 'Access Code', 'everest-forms-pro' ) . '"></label>';
			$output .= '<label class="everest-forms-hidden"><strong>' . esc_html__( 'Referer Url: ', 'everest-forms-pro' ) . '</strong><input type="text" name="' . esc_attr( 'referer_url' ) . '" class="' . esc_attr( 'everest_forms_amocrm_referer_url everest-forms-required' ) . '" placeholder="' . esc_attr__( 'Referer Url', 'everest-forms-pro' ) . '"></label>';
			$output .= '</div>';
		} elseif ( 'trello' === $this->id ) {
			$output .= '<div><h4>To Authenticate Trello you need an access token.</h4>';
			$output .= '<input type="text" data-name="apikey" data-get_access_token_url="https://trello.com/1/authorize?expiration=never&name=EverestForms%20Pro&scope=read,write,account&response_type=token&key=" placeholder="Trello Api Key" class="evf-apikey evf-trello-get-url">';
			$output .= '<div class="evf-get-trello-token" style="display: inline-block; padding: 5px 10px; background-color: #007bff; color: #fff; border: 1px solid #007bff; border-radius: 4px; cursor: pointer;">Get Access Token</div></br>';
			$output .= '<input type="text" data-name="access_token" placeholder="Trello Access Token" class="evf-trello-access-token">';
			$output .= '<input type="text" data-name="label" placeholder="Trello Nick Name" class="evf-nickname">';
			$output .= '</div>';
		} elseif ( 'onepagecrm' === $this->id ) {
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apiuserid" placeholder="' . sprintf( esc_attr__( '%s API User ID', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apikey" placeholder="' . sprintf( esc_attr__( '%s API Key', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Nickname*/
			$output .= '<input type="text" data-name="label" placeholder="' . sprintf( esc_attr__( '%s Account Nickname', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
		} elseif ( 'icontact' === $this->id ) {
			$output .= '<div><p>Everest Forms iContact Add-On requires your Application ID, API username and API password. To obtain an application ID, follow the steps described below:<br/></p>';
			$output .= '<ol><li>Visit iContact\'s <a href="https://app.icontact.com/icp/core/registerapp/" target="_blank">application registration page</a></li>';
			$output .= '<li>Set an application name and description for your application.</li>';
			$output .= '<li>Choose to show information for API 2.0.</li>';
			$output .= '<li>Copy the provided API-AppId into the Application ID setting field below.</li>';
			$output .= '<li>Click "Enable this AppId for your account".</li>';
			$output .= '<li>Create a password for your application and click save.</li>';
			$output .= '<li>Enter your API password, along with your iContact account username, into the fields below.</li></ol></div>';

			$output .= '<input type="text" data-name="label" placeholder="Nickname" class="everest-forms-required">';
			$output .= '<input type="text" data-name="apikey" placeholder="Application Key" class="everest-forms-required">';
			$output .= '<input type="email" data-name="email" placeholder="Account Email Address" class="everest-forms-required">';
			$output .= '<input type="password" data-name="apipassword" placeholder="API Password" class="everest-forms-required">';
			$output .= '<input type="text" data-name="accountid" placeholder="Account ID" class="everest-forms-required">';
			$output .= '<input type="text" data-name="folderid" placeholder="Folder ID" class="everest-forms-required">';
		} elseif ( 'sendinblue' === $this->id ) {
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apikey" placeholder="' . esc_attr__( 'Brevo API Key', 'everest-forms-pro' ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Nickname*/
			$output .= '<input type="text" data-name="label" placeholder="' . esc_attr__( 'Brevo Account Nickname', 'everest-forms-pro' ) . '" class="everest-forms-required">';
		}
		else {
			/* translators: %s for Specific API Service Provider Name*/
			$output .= '<input type="text" data-name="apikey" placeholder="' . sprintf( esc_attr__( '%s API Key', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
			/* translators: %s for Specific API Service Provider Nickname*/
			$output .= '<input type="text" data-name="label" placeholder="' . sprintf( esc_attr__( '%s Account Nickname', 'everest-forms-pro' ), ucfirst( $this->name ) ) . '" class="everest-forms-required">';
		}
			$output .= '</div>';
		if ( 'google_sheets' === $this->id ) {
			$output .= '<button class="everest-forms-btn everest-forms-btn-primary" data-source="' . esc_attr( $this->id ) . '">' . __( 'Connect Google Spreadsheet', 'everest-forms-pro' ) . '</button>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		} elseif ( 'zoho' === $this->id ) {
			$output .= apply_filters( 'everest_forms_zoho_add_account_html', $output );
		} elseif ( 'salesforce' === $this->id ) {
			$output .= apply_filters( 'everest_forms_salesforce_add_account_html', $output );
		} elseif ( 'constant_contact' === $this->id ) {
			$output .= apply_filters( 'everest_forms_constant_contact_add_account_html', $output );
		} elseif ( 'hubspot' === $this->id ) {
			$output .= apply_filters( 'everest_forms_hubspot_add_account_html', $output );
		} elseif ( 'aweber' === $this->id ) {
			$output .= apply_filters( 'everest_forms_aweber_add_account_html', $output );
		} elseif ( 'amocrm' === $this->id ) {
			$output .= apply_filters( 'everest_forms_amocrm_add_account_html', $output );
		}elseif ( 'sendinblue' === $this->id ) {
			$output .= '<button class="everest-forms-btn everest-forms-btn-primary" data-source="' . esc_attr( $this->id ) . '">' . __( 'Connect To Brevo', 'everest-forms-pro' ) . '</button>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}else {
			$output .= '<button class="everest-forms-btn everest-forms-btn-primary" data-source="' . esc_attr( $this->id ) . '">' . __( 'Connect To ' . ucfirst( $this->name ), 'everest-forms-pro' ) . '</button>'; // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
			$output .= '</div>';

			return $output;
	}

	/**
	 * Output connected accounts.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return string
	 */
	public function output_connected_accounts( $connection_id = '', $connection = array() ) {
		$integration = $this->get_integration();

		if ( empty( $integration ) && ( empty( $connection_id ) || empty( $connection ) ) ) {
			return '';
		}

		$output  = '<div class="evf-provider-accounts evf-connection-block">';
		$output .= sprintf( '<h4>%s</h4>', 'google_sheets' === $this->id ? esc_html__( 'Select Spreadsheet', 'everest-forms-pro' ) : esc_html__( 'Select Account', 'everest-forms-pro' ) );

		$output .= sprintf( '<select name="integrations[%s][%s][account_id]">', $this->id, $connection_id );
		foreach ( $integration as $key => $integration_details ) {
			$selected = ! empty( $connection['account_id'] ) ? $connection['account_id'] : '';
			$output  .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $selected, $key, false ), esc_html( $integration_details['label'] ) );
		}
		$output .= sprintf( '<option value="">%s</a>', 'google_sheets' === $this->id ? esc_html__( 'Connect new Spreadsheet', 'everest-forms-pro' ) : esc_html__( 'Add new Account', 'everest-forms-pro' ) );
		$output .= '</select>';

		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account lists HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_lists( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}

		$list_id               = isset( $connection['list_id'] ) ? $connection['list_id'] : '';
		$lists                 = $this->api_lists( $connection_id, $connection['account_id'], $list_id );
		$selected              = ! empty( $connection['list_id'] ) ? $connection['list_id'] : '';
		$board_list_selected   = isset( $connection['board_list_id'] ) && ! empty( $connection['board_list_id'] ) ? $connection['board_list_id'] : '';
		$board_label_selected  = isset( $connection['board_label_id'] ) && ! empty( $connection['board_label_id'] ) ? $connection['board_label_id'] : '';
		$board_member_selected = isset( $connection['board_member_id'] ) && ! empty( $connection['board_member_id'] ) ? $connection['board_member_id'] : '';
		$schema_selected       = isset( $connection['base_schema_id'] ) && ! empty( $connection['base_schema_id'] ) ? $connection['base_schema_id'] : '';

		if ( is_wp_error( $lists ) ) {
			return $lists;
		}

		$output = '<div class="evf-provider-lists evf-connection-block">';
		if ( 'google_sheets' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Sheet', 'everest-forms-pro' ) );
		} elseif ( 'convertkit' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Form', 'everest-forms-pro' ) );
		} elseif ( 'zoho' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Action', 'everest-forms-pro' ) );
		} elseif ( 'pipedrive' === $this->id || 'onepagecrm' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Services', 'everest-forms-pro' ) );
		} elseif ( 'trello' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Trello Configuration', 'everest-forms-pro' ) );
		} elseif ( 'airtable' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Base', 'everest-forms-pro' ) );
		} elseif ( 'amocrm' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'amoCRM Services', 'everest-forms-pro' ) );
		} else {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select List', 'everest-forms-pro' ) );
		}
		$trello_board = 'trello' === $this->id ? 'yes' : 'no';

		$output .= sprintf( '<select data-is_terelo_board="%s" name="integrations[%s][%s][list_id]">', $trello_board, $this->id, $connection_id );

		if ( 'trello' === $this->id ) {

			foreach ( $lists['boards'] as $board ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $board['id'] ),
					selected( $selected, $board['id'], false ),
					esc_attr( $board['name'] )
				);
			}
		} elseif ( 'airtable' === $this->id ) {
			foreach ( $lists['lists'] as $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
		}
		/**
		 * For amoCRM.
		 *
		 * @since 1.7.9
		 */
		elseif ( 'amocrm' === $this->id ) {
			foreach ( $lists['lists'] as $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
		} elseif ( ! empty( $lists ) ) {
			foreach ( $lists as $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
		}

		$output .= '</select>';
		$output .= '</div>';
		if ( 'trello' === $this->id ) {
					$output .= '<div class="evf-marketing-integration evf-connection-block">';
					$output .= '<h4>Board List</h4>';
					$output .= sprintf( '<select id="evf-trello-lists" name="integrations[%s][%s][board_list_id]">', $this->id, $connection_id );

			foreach ( $lists['lists'] as $id => $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $board_list_selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
					$output .= '</select>';
					$output .= '<h4>Board Label</h4>';
					$output .= sprintf( '<select id="evf-trello-labels" name="integrations[%s][%s][board_label_id]">', $this->id, $connection_id );

			foreach ( $lists['labels'] as $id => $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $board_label_selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
					$output .= '</select>';
					$output .= '<h4>Board Member</h4>';
					$output .= sprintf( '<select id="evf-trello-members" name="integrations[%s][%s][board_member_id]">', $this->id, $connection_id );

			foreach ( $lists['members'] as $id => $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $board_member_selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
					$output .= '</select></div>';
		}

		if ( 'airtable' === $this->id ) {
			$output .= '<div class="evf-marketing-integration evf-connection-block"">';
			$output .= '<h4>Select Schema</h4>';
			$output .= sprintf( '<select id="evf-airtable-schema" name="integrations[%s][%s][base_schema_id]">', $this->id, $connection_id );
			foreach ( $lists['base_schema'] as $id => $list ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $list['id'] ),
					selected( $schema_selected, $list['id'], false ),
					esc_attr( $list['name'] )
				);
			}
			$output .= '</select></div>';
		}

		if ( 'amocrm' === $this->id ) {

			if ( $list_id == 'tasks' ) {
				$output .= '<div class="evf-marketing-integration evf-connection-block"">';
				$output .= '<h4>Task Entity Type</h4>';
				$output .= sprintf( '<select id="evf-amocrm-entity-type" name="integrations[%s][%s][entity_type]">', $this->id, $connection_id );

				$entity_type = array(
					'leads'     => 'Lead',
					'contacts'  => 'Contact',
					'companies' => 'Company',
					'customers' => 'Customer',
				);

				foreach ( $entity_type as $key => $value ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $key ),
						selected( isset( $connection['entity_type'] ) ? $connection['entity_type'] : '', $key, false ),
						esc_attr( $value )
					);
				}
				$output .= '</select>';
				$output .= '</div>';

			}
			foreach ( $lists['custom_fields'] as $field ) {
				if ( ! isset( $field['options'] ) ) {
					continue;
				}
				$output .= '<div class="evf-marketing-integration evf-connection-block"">';

				$field_type = $field['api_list_type'];
				$output    .= '<h4>' . $field['name'] . '</h4>';
				$output    .= sprintf( '<select id="evf-amocrm-%s" name="integrations[%s][%s][%s]">', strtolower( $field_type ), $this->id, $connection_id, $field_type );
				foreach ( $field['options'] as $key => $value ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $key ),
						selected( isset( $connection[ $field_type ] ) ? $connection[ $field_type ] : '', $value, false ),
						esc_attr( $value )
					);
				}
				$output .= '</select>';

				$output .= '</div>';
			}
		}
		return $output;
	}

	/**
	 * Integration account lists HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_organization( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'pipedrive' !== $this->id ) {
			return '';
		}
		$organization = $this->api_organization( $connection_id, $connection['account_id'] );

		$selected = ! empty( $connection['org_id'] ) ? $connection['org_id'] : '';

		if ( is_wp_error( $organization ) ) {
			return '';
		}

		$output  = '<div class="evf-marketing-integration evf-provider-oraganization-lists evf-connection-block">';
		$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Organization', 'everest-forms-pro' ) );
		$output .= sprintf( '<select name="integrations[%s][%s][org_id]">', $this->id, $connection_id );

		if ( ! empty( $organization ) ) {
			foreach ( $organization as $org ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $org['id'] ),
					selected( $selected, $org['id'], false ),
					esc_attr( $org['name'] )
				);
			}
		}

		$output .= '</select>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account label pipedrive HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_person_label( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'pipedrive' !== $this->id || 'lead' === $connection['list_id'] ) {
			return '';
		}

		$labels   = $this->api_person_label( $connection_id, $connection['account_id'] );
		$selected = ! empty( $connection['label'] ) ? $connection['label'] : '';

		if ( is_wp_error( $labels ) ) {
			return '';
		}

		$output = '<div class="evf-marketing-integration evf-provider-person-visible-to-lists evf-connection-block">';
		if ( 'pipedrive' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Label', 'everest-forms-pro' ) );
		}
		$output .= sprintf( '<select name="integrations[%s][%s][label]">', $this->id, $connection_id );

		if ( ! empty( $labels ) ) {
			foreach ( $labels as $label ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $label['id'] ),
					selected( $selected, $label['id'], false ),
					esc_attr( $label['name'] )
				);
			}
		}

		$output .= '</select>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account visible pipedrive HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_visible( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}

		if ( 'pipedrive' !== $this->id ) {
			return '';
		}

		$visible  = $this->api_visible( $connection_id, $connection['account_id'] );
		$selected = ! empty( $connection['owner_id'] ) ? $connection['visible_to'] : '';

		if ( is_wp_error( $visible ) ) {
			return '';
		}

		$output = '<div class="evf-marketing-integration evf-provider-person-label-lists evf-connection-block">';
		if ( 'pipedrive' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Visible to', 'everest-forms-pro' ) );
		}
		$output .= sprintf( '<select name="integrations[%s][%s][visible_to]">', $this->id, $connection_id );

		if ( ! empty( $visible ) ) {
			foreach ( $visible as $vis ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $vis['id'] ),
					selected( $selected, $vis['id'], false ),
					esc_attr( $vis['name'] )
				);
			}
		}

		$output .= '</select>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account visible pipedrive HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_owner( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}

		if ( 'pipedrive' !== $this->id ) {
			return '';
		}

		$owners   = $this->api_owner( $connection_id, $connection['account_id'] );
		$selected = ! empty( $connection['owner_id'] ) ? $connection['owner_id'] : '';

		if ( is_wp_error( $owners ) ) {
			return '';
		}

		$output = '<div class="evf-marketing-integration evf-provider-owner-lists evf-connection-block">';
		if ( 'pipedrive' === $this->id ) {
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Owner', 'everest-forms-pro' ) );
		}
		$output .= sprintf( '<select name="integrations[%s][%s][owner_id]">', $this->id, $connection_id );

		if ( ! empty( $owners ) ) {
			foreach ( $owners as $owner ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $owner['id'] ),
					selected( $selected, $owner['id'], false ),
					esc_attr( $owner['name'] )
				);
			}
		}

		$output .= '</select>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account visible pipedrive HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_currency( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}

		if ( 'pipedrive' !== $this->id || 'person' === $connection['list_id'] ) {
			return '';
		}

		$currencies = $this->api_currency( $connection_id, $connection['account_id'] );
		$selected   = ! empty( $connection['currency'] ) ? $connection['currency'] : '';

		if ( is_wp_error( $currencies ) ) {
			return '';
		}

		$output  = '<div class="evf-marketing-integration evf-provider-currency-lists evf-connection-block">';
		$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Currency Code', 'everest-forms-pro' ) );
		$output .= sprintf( '<select name="integrations[%s][%s][currency]">', $this->id, $connection_id );

		if ( ! empty( $currencies ) ) {
			foreach ( $currencies as $currency ) {
				$output .= sprintf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $currency['id'] ),
					selected( $selected, $currency['id'], false ),
					esc_attr( $currency['name'] )
				);
			}
		}

		$output .= '</select>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account lists groups HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_groups( $connection_id = '', $connection = array() ) {

		if ( empty( $connection_id ) || empty( $connection['account_id'] ) || empty( $connection['list_id'] ) ) {
			return '';
		}

		$groupsets = $this->api_groups( $connection_id, $connection['account_id'], $connection['list_id'] );

		if ( is_wp_error( $groupsets ) ) {
			return '';
		}

		$output = '<div class="evf-provider-groups evf-connection-block">';

		$output .= sprintf( '<label>%s<i class="dashicons dashicons-editor-help everest-forms-help-tooltip tooltipstered" title="%s"></i></label>', esc_html__( 'Select Groups', 'everest-forms-pro' ), esc_html__( 'There are multiple segments in your list. You may select specific list segments as per your needs.', 'everest-forms-pro' ) );

		$output .= '<div class="evf-provider-groups-list everest-forms-checklist">';

		foreach ( $groupsets as $groupset ) {

			if ( 'checkboxes' === $groupset['type'] ) {
				$groupset['type'] = 'checkbox';
			}
			$output .= '<div class="everest-forms-border-container">';
			$output .= sprintf( '<h4 class="everest-forms-border-container-title">%s</h4>', esc_html( $groupset['name'] ) );

			if ( 'dropdown' === $groupset['type'] || 'hidden' === $groupset['type'] ) {
				$output .= '<select name="integrations[' . $this->id . '][' . $connection_id . '][groups][' . $groupset['id'] . ']">';
				foreach ( $groupset['groups'] as $group ) {
					$selected = ! empty( $connection['groups'][ $groupset['id'] ] ) ? selected( $connection['groups'][ $groupset['id'] ], $group['id'], false ) : '';
					$output  .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $group['id'] ),
						$selected,
						esc_attr( $group['name'] )
					);
				}
				$output .= '</select>';
			} elseif ( 'radio' === $groupset['type'] || 'checkbox' === $groupset['type'] ) {
				$output .= '<ul>';
				foreach ( $groupset['groups'] as $group ) {
					if ( 'radio' === $groupset['type'] ) {
						$name     = sprintf(
							'integrations[%s][%s][groups][%s]',
							$this->id,
							$connection_id,
							$groupset['id']
						);
						$selected = ! empty( $connection['groups'] ) && ! empty( $connection['groups'][ $groupset['id'] ] ) && $connection['groups'][ $groupset['id'] ] === $group['id'] ? true : false;
					} else {
						$name     = sprintf(
							'integrations[%s][%s][groups][%s][%s]',
							$this->id,
							$connection_id,
							$groupset['id'],
							$group['id']
						);
						$selected = ! empty( $connection['groups'] ) && ! empty( $connection['groups'][ $groupset['id'] ] ) ? in_array( $group['id'], $connection['groups'][ $groupset['id'] ], true ) : false;
					}
					$output .= sprintf(
						'<li><input id="group_%s" type="%s" value="%s" name="%s" %s><label for="group_%s">%s</label></li>',
						esc_attr( $group['id'] ),
						esc_attr( $groupset['type'] ),
						esc_attr( $group['id'] ),
						$name,
						checked( $selected, true, false ),
						esc_attr( $group['id'] ),
						esc_attr( $group['name'] )
					);
				}
				$output .= '</ul>';
			}
			$output .= '</div>';
		}

		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Integration account custom list fields HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 * @param mixed  $form_data     Form data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_custom_fields( $connection_id = '', $connection = array(), $form_data = '' ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) || empty( $connection['list_id'] ) ) {
			return '';
		}

		$whitelist_fields = array(
			'first-name',
			'last-name',
			'text',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'email',
			'address',
			'country',
			'url',
			'name',
			'hidden',
			'date',
			'date-time',
			'phone',
			'number',
			'rating',
			'yes-no',
			'lookup',
			'scale-rating',
			'payment-single',
			'payment-multiple',
			'payment-checkbox',
			'payment-quantity',
			'payment-total',
			'payment-subtotal',
		);

		$form_fields = evf_get_form_fields( $form_data, apply_filters( 'everest_forms_email_marketing_whitelist_fields', $whitelist_fields ) );

		$custom_fields = $this->api_custom_field( $connection_id, $connection['account_id'], $connection['list_id'] );
		$output        = '';

		if ( ! empty( $custom_fields ) ) {
			$output  = '<div class="evf-provider-custom-fields evf-connection-block">';
			$output .= '<div class="everest-forms-field-map-table everest-forms-addable-list everest-forms-border-container everest-forms-panel-field-select">';
			$output .= sprintf( '<h4 class="everest-forms-border-container-title">%s</h4>', esc_html__( 'Custom Field', 'everest-forms-pro' ) );
			$output .= '<ul data-tax="custom_field" data-next-id="" data-source="' . $this->id . '" data-connection_id = ' . $connection_id . '>';

			// Get the list of custom fields that are currently mapped
			$custom_field_lists     = ! empty( $connection['custom_field'] ) ? $connection['custom_field'] : array( false );
			$new_custom_field_lists = ! empty( $form_data['integrations'][ $this->id ][ $connection_id ]['add_custom_field'] ) ? $form_data['integrations'][ $this->id ][ $connection_id ]['add_custom_field'] : array( false );

			$i = 1;
			foreach ( $custom_field_lists as $key => $list ) {
				$output .= '<li>';

				$output .= '<span class="key">';
				$output .= sprintf( '<select class="everest-forms-custom_field-map-select custom-field-select" name="integrations[%s][%s][custom_field][%s]">', $this->id, $connection_id, $i );
				$output .= '<option value="">' . esc_html__( '--- Select  a custom field ---', 'everest-forms-pro' ) . '</option>';

				foreach ( $custom_fields as $value ) {
					$selected = ( trim( $value['id'] ) === trim( $list ) ) ? 'selected="selected"' : '';
					$output  .= '<option value="' . $value['id'] . '" ' . $selected . '>' . $value['name'] . '</option>';
				}

				$output .= '</select></span>';

				$output .= '<span class="field">';
				$output .= sprintf( '<select class="everest-forms-custom_field-map-options custom_field-value-select" name="integrations[%s][%s][custom_field_value][%s]">', $this->id, $connection_id, $i );
				$output .= '<option value="">' . esc_html__( '--- Select Field ---', 'everest-forms-pro' ) . '</option>';

				$options = $this->get_form_field_select( $form_fields, $value['field_type'] );

				foreach ( $options as $option ) {
					$option_value = sprintf( '%s.%s.%s', $option['id'], 'value', $option['provider_type'] );

					$selected = ( isset( $connection['custom_field_value'][ $i ] ) && trim( $connection['custom_field_value'][ $i ] ) === trim( $option_value ) ) ? 'selected="selected"' : '';

					$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $option_value ), $selected, esc_html( $option['label'] ) );
				}

				$output .= '</select></span>';

				$output .= '<span class="actions"><a class="add" href="#"><i class="dashicons dashicons-plus"></i></a><a class="remove" href="#"><i class="dashicons dashicons-minus"></i></a></span></li>';

				++$i;
			}

			$output .= '</div></div>';
		}

		return $output;
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
		$tags   = $this->api_tags( $connection_id, $connection['account_id'], $connection['list_id'] );
		$output = '';

		if ( ! empty( $tags ) ) {
			$form_data_tags = array();
			$form_data_tags = isset( $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['add'] ) ? $form_data['integrations'][ $this->id ][ $connection_id ]['tags']['add'] : array();
			$output         = '<div class="evf-provider-tags evf-connection-block">';
			$output        .= sprintf( '<h4>%s</h4>', esc_html__( 'Tags', 'everest-forms-pro' ) );
			$output        .= '<div class="everest-forms-panel-field">';
			$output        .= sprintf( '<select class="evf-provider-tags-select" name="integrations[%s][%s][tags][add][]" multiple="multiple">', $this->id, $connection_id );
			$i              = 0;
			$api_tag_label  = array();
			foreach ( $tags as $tag ) {
				$id       = isset( $tag['id'] ) ? $tag['id'] : '';
				$tag      = isset( $tag['tag'] ) ? $tag['tag'] : '';
				$selected = '';
				if ( in_array( $tag, $form_data_tags ) ) {
					$selected = 'selected="selected"';
				}
				$output         .= '<option value="' . $tag . '" ' . $selected . '>' . $tag . '</option>';
				$api_tag_label[] = $tag;
				++$i;
			}
			foreach ( $form_data_tags as $new_tag ) {
				if ( ! in_array( $new_tag, $api_tag_label ) ) {
					$output .= '<option value="' . $new_tag . '" selected="selected">' . $new_tag . '</option>';
				}
			}
			$output .= '</select></div>';
			$output .= '<div class="abc"><h4>New Tags to Add</h4>';
			$output .= '<div class="input-section"><input type="text" class="widefat" name="integrations[' . $this->id . '][' . $connection_id . '][tags][new]"><p>Enter new taf name(s). Comma-seperated list of tags is accepted.</p></div></div>';
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Integration output note.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 * @param mixed  $form_data     Form data object.
	 */
	public function output_note( $connection_id = '', $connection = array(), $form_data = '' ) {}

	/**
	 * Integration account list fields HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 * @param mixed  $form_data     Form data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_account_fields( $connection_id = '', $connection = array(), $form_data = '', $source = '' ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) || ! isset( $connection['list_id'] ) || empty( $form_data ) ) {
			return '';
		}

		$whitelist_fields = array(
			'first-name',
			'last-name',
			'text',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'email',
			'address',
			'country',
			'url',
			'name',
			'hidden',
			'date',
			'date-time',
			'phone',
			'number',
			'rating',
			'yes-no',
			'lookup',
			'scale-rating',
			'payment-single',
			'payment-multiple',
			'payment-checkbox',
			'payment-quantity',
			'payment-total',
		);

		$form_fields = evf_get_form_fields( $form_data, apply_filters( 'everest_forms_email_marketing_whitelist_fields', $whitelist_fields ) );

		if ( ! empty( $connection['base_schema_id'] ) ) {
			$connection['list_id'] = $connection;
		}

		$account_fields = $this->fetch_api_fields( $connection_id, $connection['account_id'], $connection['list_id'] );
		$output         = '';
		if ( is_wp_error( $account_fields ) ) {
			return $account_fields;
		}

		if ( empty( $account_fields ) ) {
			$output .= "<p class='everest-forms-notice everest-forms-notice-info everest-forms-google-spread-sheet-message' style='margin-left: 20px; margin-top: -10px;'>";
			$output .= wp_kses_post(
				sprintf(
					__( 'Currently, your sheet is blank. Please create some fields in google sheets to start mapping form fields. Save and then refresh the page to view the changes..', 'everest-forms-pro' )
				)
			);
			$output .= '</p>';
		}

		if ( is_array( $account_fields ) ) {
			$output      = '<div class="evf-provider-fields evf-connection-block">';
			$output     .= sprintf( '<h4>%s</h4>', esc_html__( 'List Fields', 'everest-forms-pro' ) );
			$output     .= '<table class="wp-list-table widefat striped list-fields">';
				$output .= sprintf( '<thead><tr><th scope="col" class="column-lists">%s</th><th scope="col" class="column-form-fields">%s</th></thead>', esc_html__( 'List Fields', 'everest-forms-pro' ), esc_html__( 'Available Form Fields', 'everest-forms-pro' ) );
				$output .= '<tbody id="the-list">';
			foreach ( $account_fields as $account_field ) {
				$output     .= '<tr>';
				$output     .= '<td class="column-lists">';
					$output .= esc_html( $account_field['name'] );
				if ( ! empty( $account_field['req'] ) && '1' === $account_field['req'] ) {
					$output .= '<span class="required">*</span>';
				}
					$output .= '</td><td class="column-form-fields">';
					$output .= sprintf( '<select name="integrations[%s][%s][fields][%s]">', $this->id, $connection_id, esc_attr( $account_field['tag'] ) );

					$options = $this->get_form_field_select( $form_fields, $account_field['field_type'] );
					$output .= '<option value=""></option>';
				foreach ( $options as $option ) {
							$value = sprintf( '%s.%s.%s', $option['id'], $option['key'], $option['provider_type'] );
					if ( isset( $account_field['parent_id'] ) && isset( $connection['current_base_schema_id'] ) && ! empty( $connection['current_base_schema_id'] ) ) {
						if ( $connection['base_schema_id'] === $connection['current_base_schema_id'] ) {
							$selected = ! empty( $connection['fields'][ $account_field['tag'] ] ) ? selected( $connection['fields'][ $account_field['tag'] ], $value, false ) : '';
						} else {
							$selected = '';
						}
					} else {
						$selected = ! empty( $connection['fields'][ $account_field['tag'] ] ) ? selected( $connection['fields'][ $account_field['tag'] ], $value, false ) : '';
					}
							$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $value ), $selected, esc_html( $option['label'] ) );
				}
									$output .= '</select>';
									$output .= '</td>';
									$output .= '</tr>';
			}
				$output .= '</tbody>';
			$output     .= '</table>';

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Process data and submit entry to Integration.
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function process_feed( $fields, $entry, $form_data, $entry_id ) {
	}

	/**
	 * Authenticate with the Integration API.
	 *
	 * @param array $data     Data to be parsed.
	 * @param int   $form_id  Form Identifier.
	 *
	 * @return mixed id or error object
	 */
	public function authorize_api( $data = array(), $form_id = '' ) {
	}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_organization( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration account pipedrive person label.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_person_label( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration account pipedrive visible .
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_visible( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration account pipedrive owner .
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_owner( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration account pipedrive supported currency .
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_currency( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration group lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 *
	 * @return mixed array or error object
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		return array();
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
		return array();
	}

	/**
	 * Get Integration tag lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 *
	 * @return mixed array or error object
	 */
	public function api_tags( $connection_id = '', $account_id = '', $list_id = '' ) {
		return array();
	}

	/**
	 * Get Integration notes.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 *
	 * @return mixed array or error object
	 */
	public function api_note( $connection_id = '', $account_id = '', $list_id = '' ) {
		return array();
	}

	/**
	 * Get Integration contact company.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_contact_company( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration contact status.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_contact_status( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration contact details.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_contact_detail( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration contact lead source.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_contact_lead_source( $connection_id = '', $account_id = '' ) {}

	/**
	 * Get Integration account lists.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 *
	 * @return mixed array or error object
	 */
	public function api_deal_status( $connection_id = '', $account_id = '' ) {}

	/**
	 * Fetch Integration account list fields.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List id for fetching.
	 *
	 * @return mixed array or error object
	 */
	public function fetch_api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {}

	/**
	 * Integration account list options HTML.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param array  $connection    Connection data object.
	 *
	 * @return void
	 */
	public function output_options( $connection_id = '', $connection = array() ) {}

	/**
	 * Integration contact company HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_contact_company( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'onepagecrm' !== $this->id ) {
			return '';
		}

		$company = $this->api_contact_company( $connection_id, $connection['account_id'] );

		$selected = ! empty( $connection['com_id'] ) ? $connection['com_id'] : '';

		if ( is_wp_error( $company ) ) {
			return '';
		}

		if ( 'contact' !== $connection['list_id'] ) {
			return '';
		} else {
			$output  = '<div class="evf-marketing-integration evf-provider-company-lists evf-connection-block">';
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Company', 'everest-forms-pro' ) );
			$output .= sprintf( '<select name="integrations[%s][%s][com_id]">', $this->id, $connection_id );

			if ( ! empty( $company ) ) {
				foreach ( $company as $com ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $com['id'] ),
						selected( $selected, $com['id'], false ),
						esc_attr( $com['name'] )
					);
				}
			}

			$output .= '</select>';
			$output .= '</div>';

			return $output;
		}
	}

	/**
	 * Integration contact status HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_contact_status( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'onepagecrm' !== $this->id ) {
			return '';
		}

		$contact_status = $this->api_contact_status( $connection_id, $connection['account_id'] );

		$selected = ! empty( $connection['status_id'] ) ? $connection['status_id'] : '';

		if ( is_wp_error( $contact_status ) ) {
			return '';
		}

		if ( 'contact' !== $connection['list_id'] ) {
			return '';
		} else {
			$output  = '<div class="evf-marketing-integration evf-provider-status-lists evf-connection-block">';
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Contact Status', 'everest-forms-pro' ) );
			$output .= sprintf( '<select name="integrations[%s][%s][status_id]">', $this->id, $connection_id );

			if ( ! empty( $contact_status ) ) {
				foreach ( $contact_status as $status ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $status['id'] ),
						selected( $selected, $status['id'], false ),
						esc_attr( $status['name'] )
					);
				}
			}

			$output .= '</select>';
			$output .= '</div>';

			return $output;
		}
	}

	/**
	 * Integration contact details HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_contact_detail( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'onepagecrm' !== $this->id ) {
			return '';
		}

		$contact_details = $this->api_contact_detail( $connection_id, $connection['account_id'] );

		$selected = ! empty( $connection['contact_id'] ) ? $connection['contact_id'] : '';

		if ( is_wp_error( $contact_details ) ) {
			return '';
		}

		if ( 'deal' !== $connection['list_id'] ) {
			return '';
		} else {
			$output  = '<div class="evf-marketing-integration evf-provider-contact-lists evf-connection-block">';
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Contact', 'everest-forms-pro' ) );
			$output .= sprintf( '<select name="integrations[%s][%s][contact_id]">', $this->id, $connection_id );

			if ( ! empty( $contact_details ) ) {
				foreach ( $contact_details as $contact_detail ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $contact_detail['id'] ),
						selected( $selected, $contact_detail['id'], false ),
						esc_attr( $contact_detail['name'] )
					);
				}
			}

			$output .= '</select>';
			$output .= '</div>';

			return $output;
		}
	}

	/**
	 * Integration contact lead source HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_contact_lead_source( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'onepagecrm' !== $this->id ) {
			return '';
		}

		$contact_lead_source = $this->api_contact_lead_source( $connection_id, $connection['account_id'] );

		$selected = ! empty( $connection['contact_lead_id'] ) ? $connection['contact_lead_id'] : '';

		if ( is_wp_error( $contact_lead_source ) ) {
			return '';
		}

		if ( 'contact' !== $connection['list_id'] ) {
			return '';
		} else {
			$output  = '<div class="evf-marketing-integration evf-provider-lead-source-lists evf-connection-block">';
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Contact Lead Source', 'everest-forms-pro' ) );
			$output .= sprintf( '<select name="integrations[%s][%s][contact_lead_id]">', $this->id, $connection_id );

			if ( ! empty( $contact_lead_source ) ) {
				foreach ( $contact_lead_source as $lead_source ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $lead_source['id'] ),
						selected( $selected, $lead_source['id'], false ),
						esc_attr( $lead_source['name'] )
					);
				}
			}

			$output .= '</select>';
			$output .= '</div>';

			return $output;
		}
	}

	/**
	 * Integration deal status source HTML.
	 *
	 * @param string $connection_id Connection identifier for connected accounts.
	 * @param array  $connection    Connection data object.
	 *
	 * @return WP_Error|string
	 */
	public function output_deal_status( $connection_id = '', $connection = array() ) {
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}
		if ( 'onepagecrm' !== $this->id ) {
			return '';
		}

		$deal_status = $this->api_deal_status( $connection_id, $connection['account_id'] );

		$selected = ! empty( $connection['deal_status_id'] ) ? $connection['deal_status_id'] : '';

		if ( is_wp_error( $deal_status ) ) {
			return '';
		}

		if ( 'deal' !== $connection['list_id'] ) {
			return '';
		} else {
			$output  = '<div class="evf-marketing-integration evf-provider-deal-status-lists evf-connection-block">';
			$output .= sprintf( '<h4>%s</h4>', esc_html__( 'Select Deal Status', 'everest-forms-pro' ) );
			$output .= sprintf( '<select name="integrations[%s][%s][deal_status_id]">', $this->id, $connection_id );

			if ( ! empty( $deal_status ) ) {
				foreach ( $deal_status as $deal ) {
					$output .= sprintf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $deal['id'] ),
						selected( $selected, $deal['id'], false ),
						esc_attr( $deal['name'] )
					);
				}
			}

			$output .= '</select>';
			$output .= '</div>';

			return $output;
		}
	}

	/**
	 * Getting fields ready for select list options.
	 *
	 * @param array  $form_fields     Form's field array.
	 * @param string $form_field_type Field Type for the specfic form.
	 *
	 * @return array
	 */
	public function get_form_field_select( $form_fields = array(), $form_field_type = '' ) {
		if ( empty( $form_fields ) || empty( $form_field_type ) ) {
			return array();
		}

		$formatted = array();
		foreach ( $form_fields as $id => $form_field ) {
			if ( 'email' === $form_field_type && ! in_array( $form_field['type'], array( 'email' ), true ) ) {
				unset( $form_fields[ $id ] );
			}
			if ( 'vote' === $form_field_type && ! in_array( $form_field['type'], array( 'yes-no' ), true ) ) {
				unset( $form_fields[ $id ] );
			}
			if ( 'attachment' === $form_field_type && ! in_array( $form_field['type'], array( 'file-upload', 'image-upload' ), true ) ) {
				unset( $form_fields[ $id ] );
			}
			if ( 'ic_address' === $form_field_type && ! in_array( $form_field['type'], array( 'address' ), true ) ) {
				unset( $form_fields[ $id ] );
			}
		}
		foreach ( $form_fields as $id => $form_field ) {
			$formatted[] = array(
				'id'            => $form_field['id'],
				'key'           => 'value',
				'type'          => $form_field['type'],
				'subtype'       => '',
				'provider_type' => $form_field_type,
				'label'         => $form_field['label'],
			);
		}
		return $formatted;
	}

	/**
	 * Conditional Logic handler function.
	 *
	 * @param string $connection_id Connection Identifier.
	 * @param array  $connection    Connection data object.
	 * @param string $form_data     Form data object.
	 *
	 * @return array $output        Output to be rendered.
	 */
	public function conditional_logic( $connection_id = '', $connection = array(), $form_data = '' ) {
		$selected_logic           = ! empty( $connection['conditional_logic']['logic'] ) ? $connection['conditional_logic']['logic'] : '';
		$selected_field_select    = ! empty( $connection['conditional_logic']['field_select'] ) ? $connection['conditional_logic']['field_select'] : '';
		$selected_condition       = ! empty( $connection['conditional_logic']['condition'] ) ? $connection['conditional_logic']['condition'] : '';
		$selected_input_choice    = ! empty( $connection['conditional_logic']['input_choice'] ) ? $connection['conditional_logic']['input_choice'] : '';
		$selected_multiple_choice = ! empty( $connection['conditional_logic']['multiple_choice'] ) ? $connection['conditional_logic']['multiple_choice'] : '';
		$selected_country_choice  = ! empty( $connection['conditional_logic']['country_choice'] ) ? $connection['conditional_logic']['country_choice'] : '';

		$output              = '<div class="evf-provider-conditional evf-connection-block">';
		$output             .= sprintf(
			'<p><input id="%s_contional_logic" class="evf-enable-conditional-logic" type="checkbox" value="1" name="integrations[%s][%s][conditional_logic][status]" %s><label for="%s_conditional_logic">%s</label></p>',
			esc_attr( $connection_id ),
			esc_attr( $this->id ),
			esc_attr( $connection_id ),
			checked( ! empty( $connection['conditional_logic']['status'] ), true, false ),
			esc_attr( $connection_id ),
			__( 'Use conditional logic', 'everest-forms-pro' )
		);
		$output             .= '<div class="evf-conditional-container evf-conditional-container everest-forms-border-container" data-con_id="' . $connection_id . '" data-source="' . $this->id . '">';
			$output         .= '<h4 class="everest-forms-border-container-title">' . __( 'Conditional Rules', 'everest-forms-pro' ) . '</h4>';
			$output         .= '<div class="evf-logic"><p>Send data only if the following matches.</p>';
			$output         .= '</div>';
			$output         .= '<div class="evf-conditional-wrapper">';
				$output     .= sprintf( '<select class="evf-conditional-field-select" name="integrations[%s][%s][conditional_logic][field_select]">', $this->id, $connection_id );
				$output     .= '</select>';
				$output     .= sprintf( '<select class="evf-conditional-condition" name="integrations[%s][%s][conditional_logic][condition]">', $this->id, $connection_id );
					$output .= '<option value = "is"  ' . selected( $selected_condition, 'is', false ) . '> is </option>';
					$output .= '<option value = "is_not" ' . selected( $selected_condition, 'is_not', false ) . '> is not </option>';
				$output     .= '</select>';
			$output         .= '</div>';
		$output             .= '</div>';
		$output             .= '</div>';

		return $output;
	}

	/**
	 * Error wrapper for WP_Error.
	 *
	 * @param string $message message to be printed.
	 * @param string $parent  parent passed for error rendering.
	 *
	 * @return WP_Error
	 */
	public function error( $message = '', $parent = '0' ) {
		return new WP_Error( $this->id . '-error', $message );
	}
}
