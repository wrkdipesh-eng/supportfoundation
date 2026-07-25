<?php
/**
 * EverestForms Pro setup
 *
 * @package EverestForms_Pro
 * @since   1.3.5
 */

use EverestForms\Pro\Helper\Helper;

use function cli\err;

defined( 'ABSPATH' ) || exit;

/**
 * Main EverestForms Pro Class.
 *
 * @class EverestForms_Pro
 */
final class EverestForms_Pro {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'register_rewrite_rules' ) );

		if ( function_exists( 'evf' ) ) {
			evf()->utm_campaign = 'pro-version';
		}

		// Checks with Everest Forms is installed.
		if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '1.7.0', '>=' ) ) {
			$this->define_constants();
			$this->includes();

			// Validate as unique.
			$field_type_validate = array( 'email', 'first-name', 'last-name', 'text', 'url', 'phone' );
			foreach ( $field_type_validate as $field ) {
				add_filter( 'everest_forms_get_field_settings_' . $field, array( $this, 'field_settings_validate_as_unique' ) );
				add_action( 'everest_forms_process_validate_' . $field, array( $this, 'validate' ), 10, 3 );
			}
			$field_type_visibilty = array( 'email', 'first-name', 'last-name', 'checkbox', 'radio', 'text', 'date-time', 'select', 'url', 'textarea', 'country', 'address', 'number', 'phone' );
			foreach ( $field_type_visibilty as $field ) {
				add_filter( 'everest_forms_get_field_settings_' . $field, array( $this, 'field_visibilty_setting' ) );
			}
			/**
			 * Filter to add the google calendar for appointment scheduling field settings.
			 *
			 * @since 1.7.1
			 */
			add_filter( 'everest_forms_get_field_settings_date-time', array( $this, 'google_calendar_for_appt_sched_settings' ) );
			add_action( 'everest_forms_field_properties', array( $this, 'field_visibilty_field_properties' ), 10, 3 );

			// Hooks.
			add_action( 'everest_forms_init', array( $this, 'plugin_updater' ) );
			add_action( 'admin_menu', array( $this, 'analytics_menu' ), 10 );
			add_action( 'admin_menu', array( $this, 'payment_log_menu' ), 52 );
			add_filter( 'everest_forms_screen_ids', array( $this, 'register_payment_log_screen_id' ) );
			add_action( 'admin_init', array( $this, 'maybe_redirect_payment_log_tools_tab' ) );
			add_filter( 'everest_forms_get_builder_pages', array( $this, 'load_builder_pages' ) );
			add_filter( 'everest_forms_get_settings_pages', array( $this, 'load_settings_pages' ), 9, 1 );
			add_filter( 'everest_forms_get_settings_pages', array( $this, 'load_settings_license_pages' ), 999, 1 );
			add_filter( 'everest_forms_block_types', array( $this, 'register_payment_subscriptions_block' ) );
			add_action( 'admin_init', array( $this, 'maybe_correct_fresh_install_flag' ) );
			add_filter( 'everest_forms_fields', array( $this, 'maybe_hide_payment_fields_for_fresh_install' ), 1000, 1 );
			add_filter( 'everest_forms_builder_tabs_array', array( $this, 'maybe_hide_payments_builder_tab' ), 100 );
			add_filter( 'everest_forms_field_credit_card_enable', array( $this, 'maybe_disable_legacy_credit_card_sidebar' ), 20 );
			add_action( 'everest_forms_import_form', array( $this, 'flag_imported_form_payment_usage' ), 10, 1 );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );
			add_action( 'everest_forms_shortcode_scripts', array( $this, 'frontend_enqueue_scripts' ) );
			add_action( 'everest_forms_woocommerce_load_script', array( $this, 'frontend_enqueue_scripts' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
			add_action( 'everest_forms_shortcode_scripts', array( $this, 'shortcode_scripts' ) );
			add_filter( 'everest_forms_builder_strings', array( $this, 'form_builder_strings' ) );
			add_filter( 'everest_forms_get_script_data', array( $this, 'form_script_data' ), 10, 2 );
			add_filter( 'everest_forms_validation_settings', array( $this, 'validation_settings' ) );
			add_filter( 'everest_forms_entries_management_settings', array( $this, 'general_settings' ) );
			add_action( 'everest_forms_inline_email_settings', array( $this, 'apply_email_attacment_setting' ), 10, 2 );
			add_action( 'everest_forms_inline_advance_settings', array( $this, 'form_general_settings' ) );
			add_filter( 'everest_forms_security_general_settings', array( $this, 'misc_settings' ), 10, 1 );
			add_filter( 'everest_forms_general_miscellaneous_settings', array( $this, 'general_misc_settings' ), 10, 1 );

			add_filter( 'everest_forms_builder_fields_option', array( $this, 'field_settings_tooltip' ) );

			add_action( 'everest_forms_email_validation', array( $this, 'email_validation' ), 10, 3 );

			// AutoPopulate.
			add_filter( 'everest_forms_get_field_settings_email', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_first-name', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_last-name', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_checkbox', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_radio', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_text', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_number', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_date-time', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_select', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_textarea', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_get_field_settings_url', array( $this, 'field_settings_autopopulate' ) );
			add_filter( 'everest_forms_field_properties', array( $this, 'evf_auto_populate_form_field' ), 99, 3 );

			// WebHook.
			add_filter( 'everest_forms_process_complete', array( $this, 'evf_send_form_data_to_custom_url' ), 10, 4 );
			add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
			add_action( 'everest_forms_settings_panel_content', array( $this, 'output_webhook_settings' ) );

			// Admin Approval Entries.
			add_filter( 'everest_forms_process_complete', array( $this, 'evf_send_admin_approval_emails' ), 10, 4 );

			// Clears the expired admin approval entries.
			add_action( 'init', array( $this, 'evf_schedule_entry_cleanup' ) );
			add_action( 'everest_forms_cleanup_approval_expired_entries', array( $this, 'cleanup_approval_expired_entries' ) );

			// Whitelist Domain.
			add_filter( 'everest_forms_get_field_settings_email', array( $this, 'field_settings_whitelist_domain' ) );

			// Select All.
			add_filter( 'everest_forms_get_field_settings_select', array( $this, 'field_settings_select_all' ) );

			// Entires Sorting.
			add_filter( 'everest_forms_entries_table_columns', array( $this, 'get_customized_columns' ), 1000, 2 );
			add_filter( 'everest_forms_entries_table_extra_columns', array( $this, 'get_sn_column' ), 10, 3 );
			add_filter( 'everest_forms_entries_table_extra_columns_id', array( $this, 'get_entry_id_column' ), 10, 3 );

			// PopUp Form.
			add_action( 'everest_form_popup', array( $this, 'popup_form' ) );

			// Entry actions.
			add_action( 'wp_ajax_evf_resend_notification', array( $this, 'ajax_resend_notification' ) );
			add_action( 'admin_init', array( $this, 'process_actions' ) );
			add_action( 'admin_init', array( $this, 'export_entries' ) );
			add_filter( 'everest_forms_export_entry_default_columns', array( $this, 'filter_export_entry_column' ), 10, 2 );
			add_filter( 'everest_forms_entry_export_row_data', array( $this, 'filter_export_entry_date_range' ), 10, 3 );
			add_filter( 'everest_forms_entry_export_row_data', array( $this, 'filter_export_entry_column_search' ), 10, 3 );
			add_action( 'everest_forms_view_entries_notices', array( $this, 'entry_notices' ) );
			add_filter( 'everest_forms_entries_list_actions', array( $this, 'entries_list_actions' ) );
			add_action( 'everest_forms_custom_menu_count', array( $this, 'unread_menu_count' ) );
			add_action( 'everest_forms_process_complete', array( $this, 'invalidate_entries_cache' ) );
			add_action( 'everest_forms_entry_status_updated', array( $this, 'invalidate_entries_cache' ) );
			add_action( 'everest_forms_before_delete_entries', array( $this, 'invalidate_entries_cache' ) );
			add_filter( 'everest_forms_entry_table_actions', array( $this, 'entry_table_actions' ), 10, 2 );
			add_action( 'everest_forms_entry_details_sidebar_action', array( $this, 'display_action_button' ), 10, 2 );
			add_action( 'everest_forms_entries_table_views', array( $this, 'display_entries_table_views' ), 10, 3 );

			// Corn Job.
			if ( 'yes' === get_option( 'everest_forms_scheduled_entry_delete' ) ) {
				add_action( 'everest_forms_cleanup_old_entries', array( $this, 'cleanup_old_entries' ) );
			}
			/**
			 * Cleanup the old api logs.
			 *
			 * @since 1.7.8
			 */
			add_action( 'everest_forms_cleanup_old_api_logs', array( $this, 'cleanup_old_api_logs' ) );

			if ( defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '3.2.5', '<' ) ) {
				add_action( 'before_delete_post', array( $this, 'delete_entry_files_before_form_delete' ), 10, 1 );
				add_action( 'everest_forms_before_delete_entries', array( $this, 'delete_entry_files' ), 10, 1 );
			}

			// Entry processing and setup.
			add_filter( 'everest_forms_entry_statuses', array( $this, 'entry_statuses' ) );
			add_filter( 'everest_forms_entry_bulk_actions', array( $this, 'entry_bulk_actions' ) );
			add_filter( 'everest_forms_entries_table_columns', array( $this, 'entries_table_columns' ), 10, 2 );
			add_action( 'everest_forms_entry_table_column_value', array( $this, 'entries_table_column_value' ), 10, 3 );
			// add_action( 'everest_forms_after_entry_details_hndle', array( $this, 'add_starred_icon' ) );
			add_action( 'everest_forms_entry_details_sidebar_actions', array( $this, 'entry_details_actions' ), 10, 2 );
			add_filter( 'everest_forms_hidden_entry_fields', array( $this, 'entry_hidden_fields' ), 20 );
			add_action( 'everest_forms_after_entry_details', array( $this, 'payment_details_inside_entry' ), 10, 2 );
			add_action( 'everest_forms_process_validate_payment-single', array( $this, 'payment_single_validation' ), 10, 4 );
			add_action( 'everest_forms_process_validate_payment-multiple', array( $this, 'payment_multiple_validation' ), 10, 4 );

			// Entry Export in Tools.
			add_action( 'html_admin_page_export_entries', array( $this, 'html_admin_page_export_entries_html' ), 10, 0 );
			// Filter to add the tabs in the tool menu.
			add_filter( 'everest_forms_admin_status_tabs', array( $this, 'pro_tool_menu_tab_lists' ) );
			// Row meta.
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 20, 2 );

			// AJAX events.
			add_action( 'wp_ajax_everest_forms_entry_star', array( $this, 'ajax_entry_star' ) );
			add_action( 'wp_ajax_everest_forms_entry_read', array( $this, 'ajax_entry_read' ) );
			add_action( 'wp_ajax_everest_forms_entry_approved', array( $this, 'ajax_entry_approval' ) );
			add_action( 'wp_ajax_everest_forms_entry_denied', array( $this, 'ajax_entry_denial' ) );
			// add_action( 'wp_ajax_everest_forms_update_entry', array( $this, 'ajax_update_entry' ) );
			add_action( 'wp_ajax_everest_forms_get_columns', array( $this, 'get_columns' ) );
			add_action( 'wp_ajax_everest_forms_set_columns', array( $this, 'set_columns' ) );
			add_action( 'wp_ajax_everest_forms_export_entry_action', array( $this, 'export_entry_action' ) );
			add_action( 'wp_ajax_everest_forms_update_state_field', array( $this, 'populate_state_field' ) );
			add_action( 'wp_ajax_everest_forms_install_and_active_addons', array( $this, 'install_and_active_addons' ) );

			// Smart Tags.
			add_filter( 'everest_forms_process_smart_tags', array( $this, 'everest_forms_pro_process_smart_tags' ), 10, 4 );
			add_filter( 'everest_forms_smart_tags', array( $this, 'everest_forms_pro_smart_tags' ) );

			// Field tooltip (after label).
			add_action( 'everest_forms_display_field_before', array( $this, 'field_tooltip' ), 15, 2 );
			add_filter( 'everest_forms_field_properties', array( $this, 'field_tooltip_property' ), 5, 3 );

			// Form Field Icons.
			add_action( 'everest_forms_display_field_before', array( $this, 'output_field_icon_wrapper_html' ), 16, 2 );
			add_action( 'everest_forms_display_field_after', array( $this, 'output_field_icon_html' ), 2, 2 );

			// Row Options Toggle.
			add_action( 'everest_forms_builder_fields_tab', array( $this, 'row_options' ) );
			// Row Options Setting.
			add_action( 'everest_forms_builder_fields_tab_content', array( $this, 'builder_row_options' ) );
			add_action( 'everest_forms_builder_rows_options', array( $this, 'output_rows_options' ) );

			// Row Settings.
			add_action( 'wp_ajax_everest_forms_new_row', array( $this, 'new_row_options' ) );

			// Anti Spam and Security setting.
			add_action( 'everest_forms_inline_security_settings', array( $this, 'add_security_tab_setting' ) );
			add_filter( 'everest_forms_process_initial_errors', array( $this, 'block_ip' ), 10, 2 );
			// Uploaded file protection.
			add_action( 'admin_init', array( $this, 'evf_uploaded_file_protection' ) );
			// Blacklist Words.
			add_filter( 'everest_forms_process_initial_errors', array( $this, 'blacklist_words_validation' ), 10, 2 );

			add_action( 'everest_forms_frontend_listing_scripts', array( $this, 'frontend_enqueue_scripts' ) );

			// Deactivates the addons if it is enabled in old version for backward compatibility.
			add_action( 'admin_init', array( $this, 'evf_deactivate_addons' ) );

			// Hide the deactivated plugins.
			add_filter( 'all_plugins', array( $this, 'evf_hide_deactive_plugins' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'everest_forms_missing_notice' ) );
		}
	}


	/**
	 * Pro tool menu's tab list.
	 *
	 * @since 1.7.8
	 *
	 * @param  [type] $status_tab The tab list.
	 */
	public function pro_tool_menu_tab_lists( $status_tab ) {
		$status_tab['api_logs'] = esc_html__( 'Api Logs', 'everest-forms-pro' );

		return $status_tab;
	}

	/**
	 * Block form submissions from specific IP addresses.
	 *
	 * @param array  $errors    Form submit errors.
	 * @param object $form_data   An object containing settings for the form.
	 */
	public function block_ip( $errors, $form_data ) {
		global $wpdb;
		$user_ip  = evf_get_ip_address();
		$ip_block = isset( $form_data['settings']['ip_block'] ) ? $form_data['settings']['ip_block'] : '';
		if ( ! empty( $ip_block ) ) {
			/* translators: %s: ip */
			$block_ip_msg = sprintf( esc_html__( 'The ip "%s" is blocked, please contact the administrator', 'everest-forms-pro' ), $user_ip );
			$ips          = explode( ',', $ip_block );
			$ips          = array_map( 'trim', $ips );
			if ( in_array( $user_ip, $ips, true ) ) {
				$form_id                      = ! empty( $form_data['id'] ) ? $form_data['id'] : 0;
				$errors[ $form_id ]['header'] = $block_ip_msg;
			}
		}
		return $errors;
	}

	/**
	 *  Anti Spam and Security setting.
	 *
	 * @return void
	 */
	public function add_security_tab_setting() {
		$form_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Block IP and Email', 'everest-forms' ) . '</h4>';
		everest_forms_panel_field(
			'textarea',
			'settings',
			'ip_block',
			$form_data,
			esc_html__( 'IP Block', 'everest-forms' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $form_data['settings']['ip_block'] ) ? $form_data['settings']['ip_block'] : '',
				'tooltip'     => sprintf( esc_html__( 'Enter the ip (comma separated) to block the certain ip', 'everest-forms' ) ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'email_black_list',
			$form_data,
			esc_html__( 'Email Black List', 'everest-forms' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $form_data['settings']['email_black_list'] ) ? $form_data['settings']['email_black_list'] : '',
				'tooltip'     => sprintf( esc_html__( 'Enter the email (comma separated) to block the email', 'everest-forms' ) ),
			)
		);
		do_action( 'everest_forms_inline_honeypot_settings', $this, 'honeypot', 'connection_1' );
		echo '</div>';

		// For blacklist words.
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Blacklist Words', 'everest-forms' ) . '</h4>';
		// Words for whole form.
		everest_forms_panel_field(
			'textarea',
			'settings',
			'whole_form_black_list_words',
			$form_data,
			esc_html__( 'Blacklisted Words', 'everest-forms' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $form_data['settings']['whole_form_black_list_words'] ) ? $form_data['settings']['email_black_list'] : '',
				'tooltip'     => sprintf( esc_html__( 'Enter the words (comma separated) to blacklist the words.', 'everest-forms' ) ),
			)
		);
		echo '</div>';
	}

	/**
	 * New row options
	 *
	 * @return void
	 */
	public function new_row_options() {

		// Run a security check.
		check_ajax_referer( 'everest_forms_add_row', 'security' );

		// Check for form ID.
		if ( ! isset( $_POST['form_id'] ) || empty( $_POST['form_id'] ) || ! isset( $_POST['row_id'] ) || empty( $_POST['row_id'] ) ) {
			die( esc_html__( 'No form ID found', 'everest-forms' ) );
		}

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_edit_form', (int) $_POST['form_id'] ) ) {
			die( esc_html__( 'You don\'t have permission.', 'everest-forms' ) );
		}

		$form_id = (int) $_POST['form_id'];
		$row_id  = (int) $_POST['row_id'];

		ob_start();

		$this->output_rows_options( evf()->form->get( $form_id ), 'row_' . $row_id );

		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Otput_rows_options.
	 *
	 * @param mixed $form Form.
	 * @param mixed $key  Row Key.
	 * @return void
	 */
	public function output_rows_options( $form, $key = false ) {
		$form_data = is_object( $form ) ? evf_decode( $form->post_content ) : array();
		if ( empty( $form_data ) ) {
			return;
		}

		if ( ! empty( $key ) ) {

			$row_option_class = apply_filters(
				'everest_forms_builder_row_option_class',
				array(
					'everest-forms-row-option',
				),
				$key
			);

			?>
			<div class="<?php echo esc_attr( implode( ' ', $row_option_class ) ); ?>" id="everest-forms-row-option-<?php echo esc_attr( $key ); ?>" data-row-id="<?php echo esc_attr( $key ); ?>" >
				<?php do_action( 'everest_forms_rows_conditional_logic', $key, 'form_rows' ); ?>
			</div>
			<?php

			return;
		}

		$form_rows = array();
		$rows      = isset( $form_data['structure'] ) ? $form_data['structure'] : array( 'row_1' => array() );
		echo '<div class="everest-forms-row-option-group">';
		if ( ! empty( $rows ) ) {
			foreach ( $rows as $key => $row ) {

				$row_option_class = apply_filters(
					'everest_forms_builder_row_option_class',
					array(
						'everest-forms-row-option',
					),
					$row
				);

				?>
				<div class="<?php echo esc_attr( implode( ' ', $row_option_class ) ); ?>" id="everest-forms-row-option-<?php echo esc_attr( $key ); ?>" data-row-id="<?php echo esc_attr( $key ); ?>" >
					<?php do_action( 'everest_forms_rows_conditional_logic', $key, 'form_rows' ); ?>
				</div>
				<?php
			}
		} else {
			printf( '<p class="no-rows">%s</p>', esc_html__( 'You don\'t have any rows yet.', 'everest-forms' ) );
		}
		echo '</div>';
	}

	/**
	 * Builder Row Options.
	 *
	 * @param mixed $form Form.
	 * @return void
	 */
	public function builder_row_options( $form ) {
		echo '<div class="everest-forms-row-options">';
		do_action( 'everest_forms_builder_rows_options', $form );
		echo '</div>';
	}


	/**
	 * Row Options.
	 *
	 * @param mixed $form Form.
	 * @return void
	 */
	public function row_options( $form ) {
		echo '<a href="#" id="row-options" class="options">' . esc_html__( 'Row Options', 'everest-forms' ) . '</a>';
	}

	/**
	 * Popup Form Display
	 *
	 * @param array $atts Attributes.
	 */
	public function popup_form( $atts ) {
		$popup_id = $atts['id'];
		if ( isset( $atts['type'] ) && ( 'popup-button' === $atts['type'] || 'popup-link' === $atts['type'] ) ) {
			$display = 'display:none;';
		} else {
			$display = '';
		}
		if ( 'medium' === $atts['size'] ) {
			$popup_size = 'evf-medium';
		} elseif ( 'large' === $atts['size'] ) {
			$popup_size = 'evf-large';
		} else {
			$popup_size = 'evf-default';
		}
		?>
		<div class="everest-forms-modal everest-forms-modal-<?php echo esc_attr( $popup_id ); ?> " style="<?php echo esc_attr( $display ); ?>">
			<div class="evf-model <?php echo esc_attr( $popup_size ); ?>" >
				<div class="header-wrap">
					<h1 class="title"><?php echo isset( $atts['header_title'] ) ? $atts['header_title'] : ''; ?></h1>
					<p class="desc"> <?php echo isset( $atts['header_desc'] ) ? wp_kses( $atts['header_desc'], evf_get_allowed_html_tags( 'builder' ) ) : ''; ?></p>
				</div>
				<a href="javascript:void(0);" class="evf-close-popup evf-close-popup-<?php echo esc_attr( $popup_id ); ?>" >&times;</a>
				<?php echo do_shortcode( '[everest_form  id="' . $popup_id . '"]' ); ?>
				<div class="footer-wrap">
					<h1 class="title"><?php echo isset( $atts['footer_title'] ) ? $atts['footer_title'] : ''; ?></h1>
					<p class="desc"> <?php echo isset( $atts['footer_desc'] ) ? wp_kses( $atts['footer_desc'], evf_get_allowed_html_tags( 'builder' ) ) : ''; ?></p>

				</div>
			</div>
		</div>
		<?php
	}
	/**
	 * Field Setting
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return $settings Settings.
	 */
	public function field_visibilty_setting( $settings ) {
		$options   = $settings['advanced-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if (
				'css' === $option && ! in_array( 'field_visiblity', $new_array )
			) {
				$new_array [] = 'field_visiblity';
			}
		}
		$settings['advanced-options']['field_options'] = $new_array;
		return $settings;
	}

	/**
	 * Adding Field Visibility attr.
	 *
	 * @param array $field_properties Field properties array data.
	 * @param array $field Field data.
	 * @param array $form_data Form data.
	 */
	public function field_visibilty_field_properties( $field_properties, $field, $form_data ) {
		if ( ! empty( $field['readonly_field_visibility'] ) ) {
			// Calculation fields use readonly="readonly" (added by the Calculations addon) which
			// still submits the value with the form. Adding disabled="disabled" here would prevent
			// POST submission and cause the saved entry value to be empty.
			$is_calculation = ! empty( $field['enable_calculation'] ) && '1' === $field['enable_calculation'];

			if ( ! $is_calculation ) {
				$field_properties['inputs']['primary']['attr']['disabled']           = 'disabled';
				$field_properties['input_container']['attr']['disabled']             = 'disabled';
			}

			$field_properties['inputs']['primary']['attr']['data-field-visibilty'] = 'yes';
			$field_properties['input_container']['attr']['data-field-visibilty']   = 'yes';

			if ( ( 'checkbox' === $field['type'] || 'radio' === $field['type'] ) && ! empty( $field_properties['inputs'] ) ) {
				foreach ( $field_properties['inputs'] as $key => $choice ) {
					$choice['attr']['data-field-visibilty'] = 'yes';
					if ( ! $is_calculation ) {
						$choice['attr']['disabled'] = 'disabled';
					}
					$field_properties['inputs'][ $key ] = $choice;
				}
			}
		}
		if ( ! empty( $field['hidden_field_visibility'] ) ) {
			$field_properties['container']['attr']['style'] = 'display:none';
		}
		return $field_properties;
	}

	/**
	 * Validate as unique Field Setting
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return $settings Settings.
	 */
	public function field_settings_validate_as_unique( $settings ) {
		if ( ! isset( $settings['basic-options']['field_options'] ) ) {
			return $settings;
		}
		$options   = $settings['basic-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if (
				'required_field_message' === $option && ! in_array( 'no_duplicates', $new_array ) && ! in_array( 'validate_message', $new_array )
			) {
				$new_array[] = array_push( $new_array, 'no_duplicates', 'validate_message' );
			}
		}
		$settings['basic-options']['field_options'] = $new_array;
		return $settings;
	}

	/**
	 * Tooltip Field Setting
	 *
	 * @param mixed $settings Settings.
	 *
	 * @since 1.5.2
	 * @return $settings Settings.
	 */
	public function field_settings_tooltip( $settings ) {
		$options   = $settings['basic-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if (
				'required_field_message' === $option && ! in_array( 'no_duplicates', $new_array )
			) {
				$new_array [] = 'show_tooltip';
			}
		}
		$settings['basic-options']['field_options'] = $new_array;
		return $settings;
	}

	/**
	 * Display the tooltip for each field.
	 *
	 * @since 1.5.2
	 *
	 * @param array $field     Field data and settings.
	 * @param array $form_data Form data and settings.
	 */
	public function field_tooltip( $field, $form_data ) {
		if ( isset( $field['show_tooltip'] ) && ! empty( $field['tooltip_description'] ) ) {
			echo '<span class="dashicons dashicons-editor-help everest-forms-help-tooltip" title="' . htmlspecialchars( wp_kses( html_entity_decode( $field['tooltip_description'] ), 'post' ) ) . '" style="display:none"></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Email Validation.
	 *
	 * @param string $field_id Field ID.
	 * @param mixed  $field_submit Field Submit.
	 * @param mixed  $form_data Form Data.
	 * @return void
	 */
	public function email_validation( $field_id, $field_submit, $form_data ) {

		// Duplicates Check.
		if ( ! empty( $form_data['form_fields'][ $field_id ]['no_duplicates'] ) ) {
			$value     = sanitize_email( $field_submit['primary'] );
			$duplicate = $this->validate_as_unique(
				array(
					'form_id' => $form_data['id'],
					'search'  => $value,
				)
			);
			if ( ! empty( $duplicate ) ) {
				$invalid_email = esc_html__( 'Email already exists.', 'everest-forms-pro' );
				if ( empty( $form_data['form_fields'][ $field_id ]['confirmation'] ) ) {
					evf()->task->errors[ $form_data['id'] ][ $field_id ] = $invalid_email;
				} else {
					evf()->task->errors[ $form_data['id'] ][ $field_id ]['primary'] = $invalid_email;
				}
				update_option( 'evf_validation_error', 'yes' );

			}
		}

		// Allow or deny domains.
		$field  = $form_data['form_fields'][ $field_id ];
		$domain = isset( $field_submit['primary'] ) ? explode( '@', sanitize_email( $field_submit['primary'] ) )[1] : '';

		if ( $domain ) {
			/* translators: %s: Doamin */
			$invalid_domain_msg = sprintf( esc_html__( 'The domain "%s" is restricted, please use another email.', 'everest-forms-pro' ), $domain );
			// $domains for backward compatibility.
			$domains         = isset( $field['whitelist_domain'] ) ? sanitize_text_field( $field['whitelist_domain'] ) : '';
			$allowed_domains = explode( ',', isset( $field['allowed_domains'] ) ? sanitize_text_field( $field['allowed_domains'] ) : $domains );
			$denied_domains  = explode( ',', isset( $field['denied_domains'] ) ? sanitize_text_field( $field['denied_domains'] ) : $domains );
			$allowed_domains = array_map( 'trim', $allowed_domains );
			$denied_domains  = array_map( 'trim', $denied_domains );

			if ( isset( $field['select_whitelist'] ) && ( ! empty( $allowed_domains[0] ) || ! empty( $denied_domains[0] ) ) ) {
				if ( 'allow' === $field['select_whitelist'] ) {
					if ( ! in_array( $domain, $allowed_domains, true ) ) {
						evf()->task->errors[ $form_data['id'] ][ $field_id ] = $invalid_domain_msg;
						update_option( 'evf_validation_error', 'yes' );
					}
				}

				if ( 'deny' === $field['select_whitelist'] ) {
					if ( in_array( $domain, $denied_domains, true ) ) {
						evf()->task->errors[ $form_data['id'] ][ $field_id ] = $invalid_domain_msg;
						update_option( 'evf_validation_error', 'yes' );
					}
				}
			}
		}

		// Email Black List.
		$email = isset( $form_data['settings']['email_black_list'] ) ? $form_data['settings']['email_black_list'] : '';

		if ( ! empty( $email ) ) {
			/* translators: %s: email */
			$invalid_email_msg = sprintf( esc_html__( 'The email "%s" is blocked, please use another email.', 'everest-forms-pro' ), $field_submit['primary'] );
			$emails            = explode( ',', $email );
			$emails            = array_map( 'trim', $emails );
			if ( in_array( $field_submit['primary'], $emails, true ) ) {
				evf()->task->errors[ $form_data['id'] ][ $field_id ] = $invalid_email_msg;
				update_option( 'evf_validation_error', 'yes' );
			}
		}
	}
	/**
	 * Block form submissions from specific Blacklist words.
	 *
	 * @param array  $errors    Form submit errors.
	 * @param object $form_data   An object containing settings for the form.
	 */
	public function blacklist_words_validation( $errors, $form_data ) {
		// Getting whole form blacklist words.
		$whole_forms_words     = isset( $form_data['settings']['whole_form_black_list_words'] ) ? $form_data['settings']['whole_form_black_list_words'] : '';
		$whole_forms_words_arr = explode( ',', $whole_forms_words );
		$whole_forms_words_arr = array_map( 'trim', $whole_forms_words_arr );
		$whole_forms_words_arr = array_filter( $whole_forms_words_arr, 'trim' );
		// For whole form.
		if ( ! empty( $whole_forms_words_arr ) ) {
			/**
			 * Filter - evf_blacklist_words_validation - if the field wise validation is needed.
			 *
			 * @since 1.6.7.1
			 */
			$form_field_data = apply_filters( 'evf_blacklist_words_validation', $form_data['entry']['form_fields'], $form_data );
			foreach ( $form_data['entry']['form_fields'] as $field_key => $field_value ) {
				if ( is_array( $field_value ) ) {
					continue;
				}
				$matches = array();
				foreach ( $whole_forms_words_arr as $pattern ) {
					$pattern = '/\b' . preg_quote( $pattern, ' / ' ) . '\b/i';
					// Matching the blacklist words in the whole form data.
					preg_match( $pattern, $field_value, $match );
					if ( empty( $match ) ) {
						continue;
					}
					$matches = array_merge( $matches, $match );

				}
				$matches = array_unique( $matches );
				if ( empty( $matches ) ) {
					continue;
				}
				$err_msg = sprintf( '%s "%s" %s', esc_html__( 'The word', 'everest-forms' ), implode( ', ', $matches ), esc_html__( 'is blacklist words, Please remove this word. ', 'everest-forms' ) );

				if ( count( $matches ) > 1 ) {
					$err_msg = sprintf( '%s "%s" %s', esc_html__( 'The words', 'everest-forms' ), implode( ', ', $matches ), esc_html__( 'are blacklist words, Please remove those words. ', 'everest-forms' ) );
				}
				// Error in the header.
				$errors[ $form_data['id'] ][ $field_key ] = $err_msg;
			}
		}
		return $errors;
	}

	/**
	 *  Autopopulate Field Setting
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return $settings Settings.
	 */
	public function field_settings_autopopulate( $settings ) {
		$options   = $settings['advanced-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if (
				'css' === $option && ! in_array( 'enable_prepopulate', $new_array ) && ! in_array( 'parameter_name', $new_array )
			) {
				$new_array [] = array_push( $new_array, 'enable_prepopulate', 'parameter_name' );
			}
		}
		$settings['advanced-options']['field_options'] = $new_array;
		return $settings;
	}

	/**
	 * Auto Populate Form Field.
	 *
	 * @param string $properties Value.
	 * @param mixed  $field Field.
	 * @param mixed  $form_data Form Data.
	 * @return $properties Properties.
	 */
	public function evf_auto_populate_form_field( $properties, $field, $form_data ) {
		$get_url =	$get_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; //phpcs:ignore
		$get_url = parse_url( $get_url );
		if ( ! empty( $get_url['query'] ) ) {
			parse_str( $get_url['query'], $query_params );
			foreach ( $query_params as $key => $url ) {
				// populating form field value with query string paramter value.
				if ( isset( $field['enable_prepopulate'] ) && '1' === $field['enable_prepopulate'] ) {
					$param_name = $field['parameter_name'];
					if ( $param_name === $key ) {
						if ( 'checkbox' === $field['type'] || 'radio' === $field['type'] || 'select' === $field['type'] ) {
							foreach ( $field['choices'] as $key => $option_value ) {
								$selected  = ! empty( $option_value['default'] ) ? $option_value['default'] : '';
								$multi_val = explode( ',', $url );
								foreach ( $multi_val as $value ) {
									if ( ( $value === $option_value['label'] ) || ( preg_match( '/\b' . preg_quote( $value, '/' ) . '\b/', $option_value['label'] ) ) || ( ! empty( $option_value['value'] ) && $value === $option_value['value'] ) ) {
										$selected                                = 1;
										$properties['inputs'][ $key ]['default'] = $selected;
									}
								}
							}
						} else {
							$properties['inputs']['primary']['attr']['value'] = sanitize_text_field( $url );
						}
					}
				}
			}
		}
		return $properties;
	}

	/**
	 * Send Form Data to Custom URL.
	 *
	 * @since 1.4.7
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function evf_send_form_data_to_custom_url( $fields, $entry, $form_data, $entry_id ) {
		$logger = new \EVF_Logger();

		$form_settings = isset( $form_data['settings'] ) ? $form_data['settings'] : array();

		if ( ( isset( $form_settings['enable_webhook'] ) && '1' === $form_settings['enable_webhook'] ) ) {
			$webhooks = isset( $form_settings['webhooks'] ) ? $form_settings['webhooks'] : array();

			if ( empty( $webhooks ) ) {
				return;
			}
			foreach ( $webhooks as $key => $settings ) {
				if ( isset( $settings['webhook_url'] ) && empty( $settings['webhook_url'] ) ) {
					continue;
				}
				$url       = $settings['webhook_url'];
				$send_data = array();

				$parsedHeaders  = isset( $settings['webhook_headers'] ) && ! empty( $settings['webhook_headers'] ) ? json_decode( $settings['webhook_headers'], true ) : array();
				$requestHeaders = array();

				if ( isset( $settings['with_header'] ) && 'yes' === $settings['with_header'] ) {

					foreach ( $parsedHeaders as $header_key => $header_value ) {
						$requestHeaders[ str_replace( ' ', '-', trim( $header_key ) ) ] = $header_value;
					}
				}

				foreach ( $fields as $data ) {

					if ( 'password' === $data['type'] ) {
						continue;
					}

					if ( 'radio' === $data['type'] || 'checkbox' === $data['type'] || 'payment-checkbox' === $data['type'] || 'payment-multiple' === $data['type'] ) {
						$send_data[ $data['value']['name'] ] = isset( $data['value'] ) ? $data['value'] : '';
					} elseif ( 'email' === $data['type'] ) {
						$send_data['email'] = isset( $data['value'] ) ? $data['value'] : '';

					} else {
						$send_data[ $data['name'] ] = isset( $data['value'] ) ? $data['value'] : '';
					}
				}

				$send_data = apply_filters( 'everest_forms_form_data_to_custom_url', $send_data, $fields );
				$response  = '';
				if ( ( isset( $settings['webhook_method'] ) && in_array( $settings['webhook_method'], array( 'post', 'put', 'patch' ), true ) ) && ( isset( $settings['webhook_format'] ) && 'json' === $settings['webhook_format'] ) ) {
					$args = array(
						'headers' => array_merge(
							array( 'Content-Type' => 'application/json' ),
							$requestHeaders
						),
						'body'    => wp_json_encode( $send_data ),
					);

					if ( 'post' === $settings['webhook_method'] ) {
						$args['method'] = 'POST';
					}

					if ( 'put' === $settings['webhook_method'] ) {
						$args['method'] = 'PUT';
					}

					if ( 'patch' === $settings['webhook_method'] ) {
						$args['method'] = 'PATCH';
					}

					$response = wp_remote_request(
						$url,
						$args
					);

				} elseif ( isset( $settings['webhook_method'] ) && in_array( $settings['webhook_method'], array( 'get', 'delete' ), true ) ) {

					$args = array(
						'headers' => $requestHeaders,
					);

					if ( 'delete' === $settings['webhook_method'] ) {
						$args['method'] = 'DELETE';
					}

					$url = $url . '?' . http_build_query( $send_data );

					$response = wp_remote_request(
						$url,
						$args
					);

				} elseif ( isset( $settings['webhook_method'] ) && in_array( $settings['webhook_method'], array( 'post', 'put', 'patch' ), true ) ) {
					$args = array(
						'headers' => $requestHeaders,
						'body'    => $send_data,
					);

					if ( 'post' === $settings['webhook_method'] ) {
						$args['method'] = 'POST';
					}

					if ( 'put' === $settings['webhook_method'] ) {
						$args['method'] = 'PUT';
					}

					if ( 'patch' === $settings['webhook_method'] ) {
						$args['method'] = 'PATCH';
					}

					$response = wp_remote_request( $url, $args );
				}

				/**
				 * If any error occurs track error in log.
				 *
				 * @since 1.7.9
				 */
				if ( is_wp_error( $response ) ) {
					$error_msg = $response->get_error_message();

					$logger->log( 'errors', esc_html__( 'Webhook : ' . $error_msg, 'everest-forms-pro' ), array( 'source' => 'webhook' ) );
					continue;
				}
				if ( empty( $response ) || empty( $entry_id ) ) {
					continue;
				}
				/**
				 * Track the webhook logs.
				 *
				 * @since 1.7.8
				 */
				do_action( 'evf_track_api_logs', $form_data['id'], $entry_id, $key, $args, $response );

			}
		}
	}

	/**
	 * Send the Pending user email to admin for approval.
	 *
	 * @since 2.0.9
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function evf_send_admin_approval_emails( $fields, $entry, $form_data, $entry_id ) {
		// Grabbing the values from the form settings.
		$notifications                    = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();
		$email                            = $notifications['connection_1'];
		$evf_admin_approval_form_settings = isset( $form_data['settings']['enable_admin_approval_entries'] ) ? $form_data['settings']['enable_admin_approval_entries'] : '';

		// Grabbing values of the global settings of admin approval entries.
		$evf_admin_approval_settings_enable   = get_option( 'everest_forms_admin_approval_entries_enable', 'no' );
		$evf_admin_approval_subject           = get_option( 'everest_forms_admin_approval_entries_email_subject', esc_html__( 'Approval notification for new pending entry', 'everest-forms-pro' ) );
		$evf_admin_approval_message           = get_option( 'everest_forms_admin_approval_entries_email_body' );
		$evf_admin_email_type                 = get_option( 'everest_forms_admin_approval_entries_email_notification' );
		$evf_admin_email                      = get_option( 'admin_email' );
		$evf_admin_approval_custom_address    = get_option( 'everest_forms_admin_approval_entries_custom_email' );
		$evf_admin_approval_notification_type = get_option( 'everest_forms_admin_approval_entries_email_notification' );

		// Checks if the email needs to be sent on the custom email address.
		if ( 'custom_email' === $evf_admin_approval_notification_type ) {
			$evf_admin_approval_email = $evf_admin_approval_custom_address;
		} else {
			$evf_admin_approval_email = $evf_admin_email;
		}

		$emails = new EVF_Emails();
		$emails->__set( 'form_data', $form_data );
		$emails->__set( 'fields', $fields );
		$emails->__set( 'entry_id', $entry_id );
		$emails->__set( 'from_name', $email['evf_from_name'] );
		$emails->__set( 'from_address', $evf_admin_approval_email );
		$emails->__set( 'reply_to', $email['evf_reply_to'] );

		// Checking if settings is enabled in both form and global settings or not.
		if ( 'yes' === $evf_admin_approval_settings_enable && '1' === $evf_admin_approval_form_settings ) {
			// Send admin approval entry email.
			$emails->send( $evf_admin_approval_email, $evf_admin_approval_subject, $evf_admin_approval_message, '', '' );
		}
	}

	/**
	 * Register settings section.
	 *
	 * @since 1.4.7
	 *
	 * @param  array $sections Settings section.
	 *
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$new_sections = array(
			'webhook' => esc_html__( 'WebHook', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}

	/**
	 * Output webhook settings.
	 *
	 * @since 1.4.7
	 *
	 * @param object $object Form settings object.
	 */
	public function output_webhook_settings( $object ) {
		$settings                    = isset( $object->form_data['settings'] ) ? $object->form_data['settings'] : array();
		$webhook_settings            = isset( $settings['webhooks'] ) ? $settings['webhooks'] : array();
		$webhook_status              = isset( $settings['enable_webhook'] ) ? $settings['enable_webhook'] : '0';
		$hidden_class                = '1' != $webhook_status ? 'everest-forms-hidden' : '';
		$hidden_enable_setting_class = '1' == $webhook_status ? 'everest-forms-hidden' : '';
		$toggler_hide_class          = isset( $toggler_hide_class ) ? 'style=display:none;' : '';
		?>
		<div class="evf-content-section evf-content-webhook-settings">
			<div class="evf-content-section-title"><?php esc_html_e( 'WebHook', 'everest-forms-pro' ); ?>
				<div class="evf-enable-webhook-toggle <?php echo esc_attr( $hidden_enable_setting_class ); ?>"><img src="<?php echo esc_url( plugin_dir_url( EFP_PLUGIN_FILE ) . 'assets/img/webhook-settings-arrow.png' ); ?>" alt="<?php esc_attr_e( 'Click me to enable email settings', 'everest-forms' ); ?>"></div>
				<div class="evf-toggle-section">
					<span class="everest-forms-toggle-form">
						<input type="hidden" name="settings[enable_webhook]" value="0" class="widefat">
						<input id="everest-forms-panel-field-settings-enable_webhook" type="checkbox" name="settings[enable_webhook]" value="1" <?php echo checked( '1', $webhook_status, false ); ?> >
						<span class="slider round"></span>
					</span>
				</div></div>
			<?php
			if ( empty( $webhook_settings ) ) {
				if ( isset( $settings['webhook_url'] ) ) {
					$webhook_settings['webhook_1']['webhook_url'] = $settings['webhook_url'];
				}
				if ( isset( $settings['webhook_method'] ) ) {
					$webhook_settings['webhook_1']['webhook_method'] = $settings['webhook_method'];
				}
				if ( isset( $settings['webhook_format'] ) ) {
					$webhook_settings['webhook_1']['webhook_format'] = $settings['webhook_format'];
				}
				if ( isset( $settings['with_header'] ) ) {
					$webhook_settings['webhook_1']['with_header'] = $settings['with_header'];
				}
				if ( isset( $settings['webhook_headers'] ) ) {
					$webhook_settings['webhook_1']['webhook_headers'] = $settings['webhook_headers'];
				}
			}

			$output = '<div class="evf-section-webhooks-add-new"> <button class=" everest-forms-btn everest-forms-btn-primary evf-add-new-webhook"  type="button" id="evf-add-web-hooks">Add New Webhook</button> </div>';

			if ( ( count( $webhook_settings ) >= 2 ) && array_key_exists( 'Test', $webhook_settings ) && ( count( $webhook_settings['Test'] ) <= 1 ) ) {
				$First_key = key( $webhook_settings );
				$webhook_settings[ $First_key ]['webhook_headers'] = $webhook_settings['Test']['webhook_headers'];
				unset( $webhook_settings['Test'] );
			}

			foreach ( $webhook_settings as $id => $setting ) {
				$output .= $this->output_single_webhook_settings( $object, $id, $setting );
			}
			echo $output; // phpcs:ignore

			?>
		</div>
		<?php
	}
	/**
	 * Single setting output of webhook
	 *
	 * @since 1.7.8
	 * @param object  $object Form settings object.
	 * @param integer $id The weebhook id.
	 * @param  [type]  $settings The single settings.
	 */
	public function output_single_webhook_settings( $object, $id, $settings ) {
		$output  = '<div class="evf-webhook-section" id="evf_web_hook_' . $id . '" ><div class="everest-forms-panel-field evf-field-webhook-headers-container everest-forms-border-container"><div style="display:flex; justify-content:space-between;" class="evf-content-section-title">' . $id . '<a href="#" class="evf-remove-webhook" ><svg width="15px" xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">  <path fill-rule="evenodd" d="M21.582 4.439a1.429 1.429 0 1 0-2.02-2.02L12 9.978 4.439 2.42a1.429 1.429 0 0 0-2.02 2.02L9.978 12l-7.56 7.561a1.429 1.429 0 0 0 2.02 2.02L12 14.022l7.561 7.56a1.429 1.429 0 1 0 2.02-2.02L14.022 12l7.56-7.561Z" clip-rule="evenodd"/></svg></a></div>';
		$output .= everest_forms_panel_field(
			'text',
			'settings[webhooks][' . $id . ']',
			'webhook_url',
			$object->form_data,
			__( 'Request URL', 'everest-forms-pro' ),
			array(
				'default'     => isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '',
				'placeholder' => 'Webhook URL',
				'tooltip'     => esc_html__( 'Enter the URL to be used in the webhook request.', 'everest-forms-pro' ),
			),
			false
		);

		$output .= everest_forms_panel_field(
			'select',
			'settings[webhooks][' . $id . ']',
			'webhook_method',
			$object->form_data,
			esc_html__( 'Request Method', 'everest-forms-pro' ),
			array(
				'default' => isset( $settings['webhook_method'] ) ? $settings['webhook_method'] : '',
				'tooltip' => esc_html__( 'Select the HTTP method used for the webhook request.', 'everest-forms-pro' ),
				'options' => array(
					'get'    => esc_html__( 'GET', 'everest-forms-pro' ),
					'post'   => esc_html__( 'POST', 'everest-forms-pro' ),
					'put'    => esc_html__( 'PUT', 'everest-forms-pro' ),
					'patch'  => esc_html__( 'PATCH', 'everest-forms-pro' ),
					'delete' => esc_html__( 'DELETE', 'everest-forms-pro' ),
				),
			),
			false
		);

		$output .= everest_forms_panel_field(
			'select',
			'settings[webhooks][' . $id . ']',
			'webhook_format',
			$object->form_data,
			esc_html__( 'Request Format', 'everest-forms-pro' ),
			array(
				'default' => isset( $settings['webhook_format'] ) ? $settings['webhook_format'] : '',
				'tooltip' => esc_html__( 'Select the format for the webhook request.', 'everest-forms-pro' ),
				'options' => array(
					'form' => esc_html__( 'FORM', 'everest-forms-pro' ),
					'json' => esc_html__( 'JSON', 'everest-forms-pro' ),
				),
			),
			false
		);

		$output .= everest_forms_panel_field(
			'select',
			'settings[webhooks][' . $id . ']',
			'with_header',
			$object->form_data,
			esc_html__( 'Request Headers', 'everest-forms-pro' ),
			array(
				'default'     => isset( $settings['with_header'] ) ? $settings['with_header'] : '',
				'tooltip'     => esc_html__( 'Select with headers if any headers should be sent with the webhook request.', 'everest-forms-pro' ),
				'options'     => array(
					'no'  => esc_html__( 'No Headers', 'everest-forms-pro' ),
					'yes' => esc_html__( 'With Headers', 'everest-forms-pro' ),
				),
				'input_class' => 'evf-webhook-request-headers',
			),
			false
		);

		$headers = array(
			'accept'                => 'Accept',
			'accept-charset'        => 'Accept-Charset',
			'accept-encoding'       => 'Accept-Encoding',
			'accept-language'       => 'Accept-Language',
			'authorization'         => 'Authorization',
			'cache-control'         => 'Cache-Control',
			'cookie'                => 'Cookie',
			'content-length'        => 'Content-Length',
			'content-type'          => 'Content-Type',
			'forwarded'             => 'Forwarded',
			'expect'                => 'Expect',
			'from'                  => 'From',
			'if-match'              => 'If-Match',
			'if-none-match'         => 'If-None-Match',
			'if-unmodified-since'   => 'If-Unmodified-Since',
			'origin'                => 'Origin',
			'proxy-authorization'   => 'Proxy-Authorization',
			'range'                 => 'Range',
			'te'                    => 'TE',
			'user-agent'            => 'User-Agent',
			'via'                   => 'Via',
			'add-custom-header-key' => 'Add Custom Header',
		);

		$output .= '<div class="everest-forms-panel-field evf-field-webhook-headers-container everest-forms-border-container" data-id=' . $id . '>';
		$output .= '<h4 class="everest-forms-border-container-title">' . __( 'Request Headers', 'everest-forms-pro' ) . '</h4>';
		$output .= '<div class="evf-webhook-headers-wrapper">';

		$output .= '<div class="evf-webhook-header-template everest-forms-hidden" data-custom-header-key-placeholder="Enter Header Key"><select class="evf-webhook-header-key">';
		$output .= '<option value="" selected disabled>' . esc_html__( 'Select Header Key', 'everest-forms-pro' ) . '</option>';

		foreach ( $headers as $key => $value ) {
			$output .= '<option value="' . esc_attr( $key ) . '">';
			/* translators: %s: Header Name. */
			$output .= sprintf( esc_html__( '%s', 'everest-forms-pro' ), $value ); // phpcs:ignore
			$output .= '</option>';
		}
		$output .= '</select>';
		$output .= '<input type="text" placeholder="' . esc_attr__( 'Enter Header Value', 'everest-forms-pro' ) . '" class="evf-webhook-header-value" />';

		$output .= '<a href="javascript:;" class="evf-add-webhook-header-btn"><i class="dashicons dashicons-plus-alt"></i></a>';
		$output .= '<a href="javascript:;" class="evf-remove-webhook-header-btn"><i class="dashicons dashicons-dismiss"></i></a></div>';

		if ( isset( $settings['webhook_headers'] ) && ! empty( $settings['webhook_headers'] ) ) {

			$request_headers = json_decode( $settings['webhook_headers'], true );
			$counter         = 0;
			$hiden_class     = '';

			if ( 1 === count( $request_headers ) ) {
				$hiden_class = 'everest-forms-hidden';
			}

			foreach ( $request_headers as $header_key => $header_value ) {

				$output .= '<div class="evf-webhook-header">';

				if ( array_key_exists( $header_key, $headers ) ) {

					$output .= '<select class="evf-webhook-header-key">';
					$output .= '<option value="" selected disabled>' . esc_html__( 'Select Header Key', 'everest-forms-pro' ) . '</option>';
					foreach ( $headers as $key => $value ) {
						$output .= '<option value="' . esc_attr( $key ) . '"' . ( $header_key === $key ? 'selected' : '' ) . '>';
						/* translators: %s: Header Name. */
						$output .= sprintf( esc_html__( '%s', 'everest-forms-pro' ), $value ); // phpcs:ignore
						$output .= '</option>';
					}

					$output .= '</select>';
					$output .= '<input type="text" placeholder="' . esc_attr__( 'Enter Header Value', 'everest-forms-pro' ) . '" class="evf-webhook-header-value" value="' . $header_value . '"  />';

				} else {
					$output .= '<span class="evf-webhook-custom-header"><input type="text" value="' . $header_key . '" class="evf-webhook-header-key">';
					$output .= '<input type="text" placeholder="' . esc_attr__( 'Enter Header Value', 'everest-forms-pro' ) . '" class="evf-webhook-header-value" value="' . $header_value . '"  />';
				}
				$output .= '<a href="javascript:;" class="evf-add-webhook-header-btn"><i class="dashicons dashicons-plus-alt"></i></a>';
				$output .= '<a href="javascript:;" class="evf-remove-webhook-header-btn ' . $hiden_class . '"><i class="dashicons dashicons-dismiss"></i></a></div>';
			}
		} else {
			$hiden_class = 'everest-forms-hidden';
			$output     .= '<div class="evf-webhook-header"><select class="evf-webhook-header-key">';
			$output     .= '<option value="" selected disabled>' . esc_html__( 'Select Header Key', 'everest-forms-pro' ) . '</option>';

			foreach ( $headers as $key => $value ) {
				$output .= '<option value="' . esc_attr( $key ) . '">';
				/* translators: %s: Header Name. */
				$output .= sprintf( esc_html__( '%s', 'everest-forms-pro' ), $value ); // phpcs:ignore
				$output .= '</option>';
			}
			$output .= '</select>';
			$output .= '<input type="text" placeholder="' . esc_attr__( 'Enter Header Value', 'everest-forms-pro' ) . '" class="evf-webhook-header-value" />';
			$output .= '<a href="javascript:;" class="evf-add-webhook-header-btn"><i class="dashicons dashicons-plus-alt"></i></a>';
			$output .= '<a href="javascript:;" class="evf-remove-webhook-header-btn ' . $hiden_class . '"><i class="dashicons dashicons-dismiss"></i></a></div>';
		}

		$output .= '</div></div></div></div>';

		return $output;
	}
	/**
	 * Scheduled Expired Approvel Entries Task Deletion.
	 *
	 * @since 2.0.9
	 */
	public function cleanup_approval_expired_entries() {
		global $wpdb;
		$forms = evf()->form->get_multiple( array(), true );

		foreach ( $forms as $form ) {
			$expires = get_option( 'everest_forms_admin_approval_entries_waiting_days', '30' );
			$entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}evf_entries WHERE form_id = %s AND (status = %s OR status = %s)", $form['id'], 'pending', 'denied' ) );
			foreach ( $entries as $key => $entry ) {
				if ( strtotime( $entry->date_created . ' + ' . $expires . ' days' ) < time() ) {
					\EVF_Admin_Entries::remove_entry( $entry->entry_id );
				}
			}
		}
	}

	/**
	 * Schedule the entry cleanup task.
	 *
	 * @since 2.0.9
	 */
	public function evf_schedule_entry_cleanup() {
		if ( ! wp_next_scheduled( 'everest_forms_cleanup_approval_expired_entries' ) ) {
			wp_schedule_event( time(), 'daily', 'everest_forms_cleanup_approval_expired_entries' );
		}
	}



	/**
	 *  Whitelist Domain.
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return $settings Settings.
	 */
	public function field_settings_whitelist_domain( $settings ) {
		$options   = $settings['advanced-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if ( 'parameter_name' === $option && ! in_array( 'whitelist_domain', $new_array, true ) ) {
				$new_array [] = 'whitelist_domain';
			}
		}
		$settings['advanced-options']['field_options'] = $new_array;
		return $settings;
	}

	/**
	 *  Select All.
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return $settings Settings.
	 */
	public function field_settings_select_all( $settings ) {
		$options   = $settings['basic-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if ( 'enhanced_select' === $option && ! in_array( 'select_all', $new_array, true ) ) {
				$new_array [] = 'select_all';
			}
		}
		$settings['basic-options']['field_options'] = $new_array;
		return $settings;
	}

	/**
	 * Get customized columns.
	 *
	 * @param mixed $columns Columns.
	 * @param mixed $form_data Form data.
	 *
	 * @since 1.4.4
	 */
	public function get_customized_columns( $columns, $form_data ) {
		if ( 'on' === isset( $_SERVER['HTTPS'] ) && sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) ) {
			$url = 'https://';
		} else {
			$url = 'http://';
		}
		// Append the host(domain name, ip) to the URL.consta
		$url .= isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

		// Append the requested resource location to the URL.
		$url .= isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$urlArr = parse_url( $url );
		parse_str( $urlArr['query'], $queryArr );
		$newColumns = $columns;
		foreach ( $columns as $colKey => $col ) {
			$newQuery = $queryArr;
			if ( in_array( $colKey, array( 'indicators', 'cb', 'actions', 'date' ) ) ) {
				continue;
			}
			if ( isset( $queryArr['order'], $queryArr['orderby'] ) && $colKey === $queryArr['orderby'] && 'desc' === $queryArr['order'] ) {
				$newQuery['order'] = 'asc';
			} else {
				$newQuery['order'] = 'desc';
			}
			$newQuery['orderby']   = $colKey;
			$newColumns[ $colKey ] = '<a href="' . esc_url( preg_replace( '/\?.*$/', '?', $url ) . '&' . http_build_query( $newQuery ) ) . '" class="' . esc_attr( $newQuery['order'] ) . '">' . $col . ' <span class="sorting-indicator"></span></a>';
		}
		return $newColumns;
	}


	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/everest-forms-pro/everest-forms-pro-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/everest-forms-pro-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'everest-forms-pro' );

		load_textdomain( 'everest-forms-pro', WP_LANG_DIR . '/everest-forms-pro/everest-forms-pro-' . $locale . '.mo' );
		load_plugin_textdomain( 'everest-forms-pro', false, plugin_basename( dirname( EFP_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		/**
		 * Abstract classes.
		 */
		include_once EFP_ABSPATH . 'includes/abstracts/class-evf-form-integration.php';
		include_once EFP_ABSPATH . 'includes/abstracts/class-evf-email-marketing.php';
		include_once EFP_ABSPATH . 'includes/abstracts/class-evf-payments.php';
		include_once EFP_ABSPATH . 'includes/abstracts/class-evf-meta-boxes.php';
		include_once EFP_ABSPATH . 'includes/abstracts/class-evf-display-page.php';

		if ( $this->evf_is_efp_code_required() ) {
			include_once EFP_ABSPATH . 'includes/abstracts/class-evf-form-fields-upload.php';
		}

		/**
		 * Core classes.
		 */
		include_once EFP_ABSPATH . 'includes/payments/functions.php';
		include_once EFP_ABSPATH . 'includes/class-evf-conditional-logics.php';
		include_once EFP_ABSPATH . 'includes/class-evf-dashboard-widget.php';
		include_once EFP_ABSPATH . 'includes/class-evf-required-indicators.php';
		include_once EFP_ABSPATH . 'includes/class-everest-forms-pro-ajax.php';
		/**
		 * Rest Api
		 */
		include_once EFP_ABSPATH . 'includes/RestApi/class-evfp-rest-api.php';

		/**
		 * Additional fields.
		 */
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-phone.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-password.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-signature.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-range-slider.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-color.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-reset.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-progress.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-lookup.php';
		// Backward compatibility.
		if ( $this->evf_is_efp_code_required() ) {
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-address.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-html.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-hidden.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-divider.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-privacy-policy.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-wysiwyg.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-title.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-country.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-image-upload.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-rating.php';
			include_once EFP_ABSPATH . 'includes/fields/class-evf-field-file-upload.php';
		}

		/**
		 * Payment fields.
		 */
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-single.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-radio.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-checkbox.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-quantity.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-total.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-subtotal.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-summary.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-credit-card.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-gateway-selector.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-square.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-subscription-plan.php';
		include_once EFP_ABSPATH . 'includes/fields/class-evf-field-payment-coupon.php';

		/**
		 * Analytics.
		 */
		include_once EFP_ABSPATH . 'includes/analytics/class-everest-forms-analytics.php';

		/**
		 * Payment Logs.
		 */
		include_once EFP_ABSPATH . 'includes/tools/class-evf-pro-admin-tools.php';

		/**
		 * TG Tracking.
		 */
		if ( 'yes' !== get_option( 'everest_forms_allow_usage_tracking', 'no' ) ) {
			require_once EFP_ABSPATH . 'includes/stats/class-evf-pro-admin-stats.php';
		}

		require_once EFP_ABSPATH . 'includes/tools/class-evf-pro-api-logs.php';

		if ( class_exists( 'EVF_Blocks_Abstract' ) ) {
			include_once EFP_ABSPATH . 'includes/blocks/class-evf-blocks-payment-subscriptions.php';
		}
	}

	/**
	 * Plugin Updater.
	 */
	public function plugin_updater() {
		if ( class_exists( 'EVF_Plugin_Updater' ) ) {
			\EVF_Plugin_Updater::updates( EFP_PLUGIN_FILE, 3441, EFP_VERSION );
		}
	}

	/**
	 * Define EVF Constants.
	 */
	private function define_constants() {
		$this->define( 'EFP_ABSPATH', dirname( EFP_PLUGIN_FILE ) . '/' );
		$this->define( 'EFP_PLUGIN_BASENAME', plugin_basename( EFP_PLUGIN_FILE ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Whether this site is marked as a fresh install.
	 *
	 * @return bool
	 */
	private static function is_fresh_install() {
		$value = get_option( 'everest_form_fresh_install', false );

		return in_array( $value, array( true, 1, '1', 'yes', 'true' ), true );
	}

	/**
	 * Check if any form has any payment gateway enabled.
	 *
	 * Gateways checked: PayPal, Authorize.Net, Razorpay, Stripe, Square, Mollie.
	 *
	 * @return bool
	 */
	public static function is_any_payment_gateway_enabled_in_any_form() {
		$form_ids = get_posts(
			array(
				'post_type'              => 'everest_form',
				'post_status'            => array( 'publish', 'inactive', 'draft', 'pending', 'private', 'future' ),
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $form_ids ) ) {
			return false;
		}

		$checks = array(
			'paypal'        => 'enable_paypal',
			'authorize_net' => 'enable_authorize_net',
			'razorpay'      => 'enable_razorpay',
			'stripe'        => 'enable_stripe',
			'square'        => 'enable_square',
			'mollie'        => 'enable_mollie',
		);

		foreach ( $form_ids as $form_id ) {
			$form_obj  = evf()->form->get( absint( $form_id ) );
			$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : array();

			if ( empty( $form_data['payments'] ) || ! is_array( $form_data['payments'] ) ) {
				continue;
			}

			foreach ( $checks as $gateway => $flag ) {
				if ( ! isset( $form_data['payments'][ $gateway ][ $flag ] ) ) {
					continue;
				}

				$value = $form_data['payments'][ $gateway ][ $flag ];
				if ( in_array( $value, array( true, 1, '1', 'yes', 'true' ), true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if a specific form uses legacy Payments-tab / per-gateway field setup.
	 *
	 * New Payment Gateway field flow fields (single item, total, subscription plan,
	 * quantity, multiple/checkbox payment items, etc.) do not count here.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private static function form_uses_payments( $form_id ) {
		$form_id      = absint( $form_id );
		$flags_option = 'everest_form_uses_legacy_payments_flags';
		$flags        = array();

		if ( $form_id ) {
			$flags = get_option( $flags_option, array() );
			$flags = is_array( $flags ) ? $flags : array();

			if ( ! empty( $flags[ $form_id ] ) ) {
				return true;
			}

			// Drop stale flags from the old option key (payment-single incorrectly counted as legacy).
			$legacy_flags = get_option( 'everest_form_uses_payments_flags', array() );
			if ( is_array( $legacy_flags ) && ! empty( $legacy_flags[ $form_id ] ) ) {
				unset( $legacy_flags[ $form_id ] );
				update_option( 'everest_form_uses_payments_flags', $legacy_flags, false );
			}
		}

		$form_obj = $form_id ? evf()->form->get( $form_id ) : null;
		$data     = ( is_object( $form_obj ) && ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : array();

		// Legacy Payments tab: gateway toggles (enable_stripe, enable_paypal, etc.).
		$checks = array(
			'paypal'        => 'enable_paypal',
			'authorize_net' => 'enable_authorize_net',
			'razorpay'      => 'enable_razorpay',
			'stripe'        => 'enable_stripe',
			'square'        => 'enable_square',
			'mollie'        => 'enable_mollie',
		);

		if ( ! empty( $data['payments'] ) && is_array( $data['payments'] ) ) {
			foreach ( $checks as $gateway => $flag ) {
				if ( ! isset( $data['payments'][ $gateway ][ $flag ] ) ) {
					continue;
				}
				$value = $data['payments'][ $gateway ][ $flag ];
				if ( in_array( $value, array( true, 1, '1', 'yes', 'true' ), true ) ) {
					if ( $form_id ) {
						$flags[ $form_id ] = 1;
						update_option( $flags_option, $flags, false );
					}
					return true;
				}
			}
		}

		// Legacy per-gateway fields on the canvas only (not Payment Gateway selector or pricing fields).
		$legacy_field_types = array(
			'credit-card',
			'square-payment',
			'authorize-net',
		);

		if ( ! empty( $data['form_fields'] ) && is_array( $data['form_fields'] ) ) {
			foreach ( $data['form_fields'] as $field ) {
				if ( ! empty( $field['type'] ) && in_array( $field['type'], $legacy_field_types, true ) ) {
					if ( $form_id ) {
						$flags[ $form_id ] = 1;
						update_option( $flags_option, $flags, false );
					}
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether the form was just created and not yet saved in the builder.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private static function is_new_form( $form_id ) {
		$form_id = absint( $form_id );
		if ( ! $form_id ) {
			return false;
		}

		$form_obj = evf()->form->get( $form_id );
		$data     = ( is_object( $form_obj ) && ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : array();

		if ( ! isset( $data['is_new_form'] ) ) {
			return false;
		}

		return in_array( $data['is_new_form'], array( true, 1, '1', 'yes', 'true' ), true );
	}

	/**
	 * Correct fresh-install flag when the site already has payment-enabled forms.
	 *
	 * Upgrades from versions before everest_form_fresh_install could be marked fresh
	 * even though legacy payment forms already exist.
	 */
	public function maybe_correct_fresh_install_flag() {
		if ( ! self::is_fresh_install() ) {
			return;
		}

		if ( self::is_any_payment_gateway_enabled_in_any_form() ) {
			update_option( 'everest_form_fresh_install', false, false );
			return;
		}

		$form_ids = get_posts(
			array(
				'post_type'              => 'everest_form',
				'post_status'            => array( 'publish', 'inactive', 'draft', 'pending', 'private', 'future' ),
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $form_ids as $form_id ) {
			if ( self::form_uses_payments( absint( $form_id ) ) ) {
				update_option( 'everest_form_fresh_install', false, false );
				return;
			}
		}
	}

	/**
	 * Whether the Payments tab and legacy payment fields should appear in the builder.
	 *
	 * Non-fresh installs: any form that already uses payments (original behavior).
	 * Fresh installs: brand-new forms (explicit is_new_form) use Payment Gateway field only;
	 * existing/imported/legacy forms (no is_new_form key) keep the Payments tab.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private static function should_show_legacy_payment_builder_ui( $form_id ) {
		$form_id = absint( $form_id );
		if ( ! $form_id ) {
			return false;
		}

		// Unsaved brand-new / duplicated forms: Payment Gateway field flow only.
		if ( self::is_new_form( $form_id ) ) {
			return false;
		}

		if ( ! self::form_uses_payments( $form_id ) ) {
			return false;
		}

		if ( ! self::is_fresh_install() ) {
			return true;
		}

		$form_obj = evf()->form->get( $form_id );
		$data     = ( is_object( $form_obj ) && ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : array();

		// Pre-tracking legacy forms never stored is_new_form — keep Payments tab for them.
		if ( ! isset( $data['is_new_form'] ) ) {
			return true;
		}

		return self::is_imported_payment_form( $form_id );
	}

	/**
	 * Form ID for the form currently open in the builder (GET or POST save).
	 *
	 * @return int
	 */
	private static function get_builder_form_id() {
		$form_id = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $form_id && isset( $_POST['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_id = absint( wp_unslash( $_POST['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return $form_id;
	}

	/**
	 * Remove legacy payment field classes from the builder field registry.
	 *
	 * @param array $fields Registered field class names.
	 * @return array
	 */
	private static function filter_legacy_payment_builder_fields( $fields ) {
		$hide = array(
			'EVF_Field_Credit_Card',
			'EVF_Field_Payment_Square',
			'EVF_Field_Payment_Authorize_Net',
			'EverestForms\AuthorizeNet\Builder\AuthorizeNetField',
		);

		$hide_normalized = array_map(
			static function ( $class_name ) {
				return is_string( $class_name ) ? ltrim( $class_name, '\\' ) : $class_name;
			},
			$hide
		);

		return array_values(
			array_filter(
				$fields,
				static function ( $field ) use ( $hide_normalized ) {
					if ( ! is_string( $field ) ) {
						return true;
					}
					return ! in_array( ltrim( $field, '\\' ), $hide_normalized, true );
				}
			)
		);
	}

	/**
	 * Hide the Payments builder tab when legacy payment setup is not in use.
	 *
	 * @param array $pages Builder tabs.
	 * @return array
	 */
	public function maybe_hide_payments_builder_tab( $pages ) {
		$form_id = self::get_builder_form_id();
		if ( $form_id && ! self::should_show_legacy_payment_builder_ui( $form_id ) && isset( $pages['payments'] ) ) {
			unset( $pages['payments'] );
		}
		return $pages;
	}

	/**
	 * Keep Credit Card off the sidebar for new forms (Stripe enables it via filter at priority 10).
	 *
	 * @param bool $enabled Whether the legacy Credit Card field is available in the builder.
	 * @return bool
	 */
	public function maybe_disable_legacy_credit_card_sidebar( $enabled ) {
		$form_id = self::get_builder_form_id();
		if ( ! $form_id ) {
			return $enabled;
		}
		if ( self::is_new_form( $form_id ) || ! self::should_show_legacy_payment_builder_ui( $form_id ) ) {
			return false;
		}
		return $enabled;
	}

	/**
	 * Whether a form was imported with payment settings or legacy payment fields.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private static function is_imported_payment_form( $form_id ) {
		$form_id = absint( $form_id );
		if ( ! $form_id ) {
			return false;
		}

		$imported = get_option( 'everest_form_imported_payment_form_ids', array() );
		return is_array( $imported ) && ! empty( $imported[ $form_id ] );
	}

	/**
	 * Record imported forms that include payment configuration for fresh-install builder UI.
	 *
	 * @param int $form_id Imported form post ID.
	 */
	public function flag_imported_form_payment_usage( $form_id ) {
		$form_id = absint( $form_id );
		if ( ! $form_id || ! self::form_uses_payments( $form_id ) ) {
			return;
		}

		$imported = get_option( 'everest_form_imported_payment_form_ids', array() );
		$imported = is_array( $imported ) ? $imported : array();

		$imported[ $form_id ] = 1;
		update_option( 'everest_form_imported_payment_form_ids', $imported, false );
	}

	/**
	 * Hide payment gateway fields for fresh installs.
	 *
	 * @param  array $fields Registered form fields.
	 * @return array
	 */
	public function maybe_hide_payment_fields_for_fresh_install( $fields ) {
		$form_id = self::get_builder_form_id();

		if ( $form_id && self::is_new_form( $form_id ) ) {
			return self::filter_legacy_payment_builder_fields( $fields );
		}

		if ( self::should_show_legacy_payment_builder_ui( $form_id ) ) {
			return $fields;
		}

		return self::filter_legacy_payment_builder_fields( $fields );
	}

	/**
	 * Load builder page.
	 *
	 * @param  array $builder Builder page.
	 * @return array of builder page.
	 */
	public function load_builder_pages( $builder ) {
		$builder[] = include_once EFP_ABSPATH . 'includes/builder/class-evf-builder-integrations.php';
		$form_id              = isset( $_GET['form_id'] ) ? absint( wp_unslash( $_GET['form_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( self::should_show_legacy_payment_builder_ui( $form_id ) ) {
			$builder[] = include_once EFP_ABSPATH . 'includes/builder/class-evf-builder-payments.php';
		}


		return $builder;
	}

	/**
	 * Load settings page.
	 *
	 * @param  array $settings Settings page.
	 * @return array of settings page.
	 */
	public function load_settings_pages( $settings ) {
		$settings[] = include_once EFP_ABSPATH . 'includes/settings/class-evf-setting-payment.php';
		return $settings;
	}

	/**
	 * Load settings page.
	 *
	 * @param  array $settings Settings page.
	 * @return array of settings page.
	 */
	public function load_settings_license_pages( $settings ) {
		$settings[] = include_once EFP_ABSPATH . 'includes/settings/class-evf-setting-license.php';
		return $settings;
	}

	/**
	 * Register Payment History Gutenberg block class.
	 *
	 * @since 1.8.0
	 *
	 * @param array $blocks Block class names.
	 * @return array
	 */
	public function register_payment_subscriptions_block( $blocks ) {
		if ( class_exists( 'EVF_Blocks_Payment_Subscriptions' ) ) {
			$blocks[] = EVF_Blocks_Payment_Subscriptions::class;
		}
		return $blocks;
	}

	/**
	 * Admin Enqueue scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Load pro analytics on EVF dashboard page or dedicated analytics page.
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( in_array( $current_page, array( 'evf-dashboard', 'evf-analytics' ), true ) ) {
			wp_enqueue_style(
				'evf-pro-analytics-style',
				plugins_url( '/dist/analytics.css', EFP_PLUGIN_FILE ),
				array(),
				EFP_VERSION
			);

			wp_register_script(
				'evf-pro-analytics',
				plugins_url( '/dist/analytics.min.js', EFP_PLUGIN_FILE ),
				array( 'wp-hooks' ),
				EFP_VERSION,
				true
			);

			$evf_analytics_data = array(
				'install_date' => date( 'Y-m-d', (int) get_option( 'everest_forms_activated', time() ) ),
				'currency'     => get_option( 'everest_forms_currency', 'USD' ),
				'data_sets'    => array(
					'summary'       => array(
						array(
							'slug'  => 'total',
							'label' => __( 'Total Submissions', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'complete',
							'label' => __( 'Completed', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'incomplete',
							'label' => __( 'Incomplete', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'impressions',
							'label' => __( 'Impressions', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'convrate',
							'label' => __( 'Conversion Rate', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'bounce',
							'label' => __( 'Bounce Rate', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'abandonments',
							'label' => __( 'Abandoned', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'revenue',
							'label' => __( 'Revenue', 'everest-forms-pro' ),
						),
						array(
							'slug'  => 'transactions',
							'label' => __( 'Transactions', 'everest-forms-pro' ),
						),
					),
					'visualization' => array(
						array(
							'slug'    => 'total',
							'label'   => __( 'Total Submissions', 'everest-forms-pro' ),
							'metrics' => array(
								array(
									'slug'  => 'total',
									'label' => __( 'Total', 'everest-forms-pro' ),
								),
							),
						),
						array(
							'slug'    => 'complete',
							'label'   => __( 'Completed', 'everest-forms-pro' ),
							'metrics' => array(
								array(
									'slug'  => 'complete',
									'label' => __( 'Completed', 'everest-forms-pro' ),
								),
							),
						),
						array(
							'slug'    => 'incomplete',
							'label'   => __( 'Incomplete', 'everest-forms-pro' ),
							'metrics' => array(
								array(
									'slug'  => 'incomplete',
									'label' => __( 'Incomplete', 'everest-forms-pro' ),
								),
							),
						),
						array(
							'slug'    => 'revenue',
							'label'   => __( 'Revenue', 'everest-forms-pro' ),
							'metrics' => array(
								array(
									'slug'  => 'revenue',
									'label' => __( 'Revenue', 'everest-forms-pro' ),
								),
							),
						),
					),
				),
			);
			wp_add_inline_script(
				'evf-pro-analytics',
				'window.__EVF_ANALYTICS__ = ' . wp_json_encode( $evf_analytics_data ) . ';',
				'before'
			);

			if ( 'evf-analytics' === $current_page ) {
				// On dedicated analytics page, enqueue the script directly.
				wp_enqueue_script( 'evf-pro-analytics' );
			} else {
				// On dashboard page, print analytics script BEFORE free dashboard script.
				add_action( 'everest_forms_dashboard_scripts', array( $this, 'print_dashboard_analytics_script' ) );
			}
		}

		// Range Slider Scripts.
		wp_register_style( 'ion-range-slider', plugins_url( '/assets/css/rangeSlider.css', EFP_PLUGIN_FILE ), array(), '2.3.1' );
		wp_register_script( 'ion-range-slider', plugins_url( '/assets/js/ion-range-slider/ion.rangeSlider' . $suffix . '.js', EFP_PLUGIN_FILE ), array( 'jquery' ), '2.3.1', true );

		// Register admin scripts.
		$admin_css_path = plugin_dir_path( EFP_PLUGIN_FILE ) . 'assets/css/admin.css';
		wp_register_style( 'everest-forms-pro-admin', plugins_url( '/assets/css/admin.css', EFP_PLUGIN_FILE ), array(), file_exists( $admin_css_path ) ? (string) filemtime( $admin_css_path ) : EFP_VERSION );
		wp_register_script( 'everest-forms-entries-scripts', plugins_url( "/assets/js/admin/everest-forms-pro-entries{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'updates' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-builder-scripts', plugins_url( '/assets/js/admin/everest-forms-pro-builder.js', EFP_PLUGIN_FILE ), array( 'jquery', 'ion-range-slider' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-integrations-scripts', plugins_url( "/assets/js/admin/integration{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-general-scripts', plugins_url( "/assets/js/admin/general{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-conditionals-scripts', plugins_url( "/assets/js/admin/conditional-logic{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-entry-dashboard-scripts', plugins_url( "/assets/js/admin/dashboard-analytics{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-tools-export-entry-scripts', plugins_url( "/assets/js/admin/tools-export-entries{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-pro-square-admin', plugins_url( "/src/Addons/Square/assets/js/admin/evf-square-payment-admin{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );

		// Add RTL support for admin styles.
		wp_style_add_data( 'everest-forms-pro-admin', 'rtl', 'replace' );

		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-pro-square-admin' );
		}

		// Admin styles for EVF pages only.
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'everest-forms-pro-admin' );
			wp_localize_script(
				'everest-forms-payment-scripts',
				'evfpayment_payment_params',
				array(
					'i18n_payment_option_label' => __( 'Payment Options', 'everest-forms-pro' ),
					'i18n_only_paypal_gateway'  => __( 'Paypal is selected as payment gateway.', 'everest-forms-pro' ),
					'i18n_only_stripe_gateway'  => __( 'Stripe is selected as payment gateway.', 'everest-forms-pro' ),
					'i18n_empty_gateways'       => __( 'Please enable payment gateways.', 'everest-forms-pro' ),
				)
			);
			// Localize scripts for entries table for sortable modal box.
			wp_localize_script(
				'everest-forms-admin',
				'evf_entries_params',
				array(
					'i18n_adjust_entries_columns_title' => esc_html__( 'Select Entries Table Column', 'everest-forms-pro' ),
					'i18n_adjust_entries_columns_description' => esc_html__( 'Drag & Drop fields to visible columns to show the particular field column in the entries table.', 'everest-forms-pro' ),
					'i18n_entries_save'                 => esc_html__( 'Save', 'everest-forms-pro' ),
					'i18n_entries_cancel'               => esc_html__( 'Cancel', 'everest-forms-pro' ),
					'i18n_entries_active_column_name'   => esc_html__( 'Visible Columns(Change)', 'everest-forms-pro' ),
					'i18n_entries_inactive_column_name' => esc_html__( 'Hidden Columns(Change)', 'everest-forms-pro' ),
					'ajax_entries_nonce'                => wp_create_nonce( 'process-entries-ajax-nonce' ),
					'ajax_url'                          => admin_url( 'admin-ajax.php', 'relative' ),
				)
			);

			wp_enqueue_script( 'everest-forms-conditionals-scripts' );
			wp_enqueue_script( 'everest-forms-integrations-scripts' );
			wp_enqueue_script( 'everest-forms-general-scripts' );

			if ( 'everest-forms_page_evf-tools' === $screen_id ) {
				wp_enqueue_script( 'everest-forms-tools-export-entry-scripts' );
				wp_enqueue_script( 'flatpickr' );
				wp_localize_script(
					'everest-forms-tools-export-entry-scripts',
					'evf_tools_export_entries_params',
					array(
						'ajax_tool_export_entries_nonce' => wp_create_nonce( 'process-tools-export-entries-ajax-nonce' ),
					)
				);
			}

			if ( isset( $_GET['page'] ) && 'evf-entries' === $_GET['page'] && ! isset( $_GET['view-entry'] ) && ! isset( $_GET['edit-entry'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification
				$form_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : -1; //phpcs:ignore WordPress.Security.NonceVerification
				$form_obj  = EVF()->form->get( $form_id );
				$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

				if ( ! isset( $form_data['settings']['enable_entries_dashboard_analytics'] ) || '1' === $form_data['settings']['enable_entries_dashboard_analytics'] ) {
					// Flatpickr.
					wp_enqueue_style( 'flatpickr' );
					wp_enqueue_script( 'flatpickr' );

					// Jquery block UI.
					wp_enqueue_style( 'jquery-blockui' );
					wp_enqueue_script( 'jquery-blockui' );

					// Jquery date picker.
					wp_enqueue_script( 'jquery-ui-datepicker' );

					// Random color.
					wp_enqueue_script( 'randomcolor', plugins_url( '/assets/js/randomcolor/randomColor' . $suffix . '.js', EFP_PLUGIN_FILE ), array(), '0.5.4', true );

					// Moment.
					wp_enqueue_script( 'moment', plugins_url( '/assets/js/moment/moment-with-locales.min.js', EFP_PLUGIN_FILE ), array(), '2.27.0', true );

					// Chartjs.
					wp_enqueue_style( 'chartjs', plugins_url( '/assets/css/Chart.min.css', EFP_PLUGIN_FILE ), array(), '2.3.9' );
					wp_enqueue_script( 'chartjs', plugins_url( '/assets/js/chartjs/Chart.bundle.min.js', EFP_PLUGIN_FILE ), array(), '2.3.9', true );

					wp_enqueue_script( 'everest-forms-entry-dashboard-scripts' );
				}
			}

			wp_localize_script(
				'everest-forms-conditionals-scripts',
				'evf_conditional_rules',
				array(
					'i18n_remove_rule'         => esc_html__( 'Remove existing rules?', 'everest-forms-pro' ),
					'i18n_remove_rule_message' => esc_html__( 'Payment rule is mutually exclusive. Do you wish to remove other rules?', 'everest-forms-pro' ),
					'payment_gateway_labels'   => function_exists( 'evf_payment_gateway_selector_labels' ) ? evf_payment_gateway_selector_labels() : array(),
				)
			);

			wp_localize_script(
				'everest-forms-integrations-scripts',
				'evfp_params',
				array(
					'admin_url'           => admin_url(),
					'ajax_url'            => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_ok'             => esc_html__( 'OK', 'everest-forms-pro' ),
					'i18n_close'          => esc_html__( 'Close', 'everest-forms-pro' ),
					'i18n_cancel'         => esc_html__( 'Cancel', 'everest-forms-pro' ),
					'ajax_nonce'          => wp_create_nonce( 'process-ajax-nonce' ),
					'form_id'             => isset( $_GET['form_id'] ) ? wp_unslash( $_GET['form_id'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					'i18n_confirm_save'       => esc_html__( 'We need to save your progress to continue to the Marketing panel. Is that OK?', 'everest-forms-pro' ),
					'i18n_confirm_connection' => esc_html__( 'Are you sure you want to delete this connection?', 'everest-forms-pro' ),
					'i18n_prompt_connection'  => esc_html__( 'Enter a %type% nickname', 'everest-forms-pro' ),
					'i18n_prompt_placeholder' => esc_html__( 'Eg: Newsletter %type%', 'everest-forms-pro' ),
					'i18n_error_name'         => esc_html__( 'You must provide a %type% nickname', 'everest-forms-pro' ),
					'i18n_required_field'     => esc_html__( 'Field required', 'everest-forms-pro' ),
					'provider_auth_error'     => esc_html__( 'Could not authenticate with the provider.', 'everest-forms-pro' ),
					'required_field'          => esc_html__( 'Fields are required.', 'everest-forms-pro' ),
				)
			);

			if ( 'everest-forms_page_evf-entries' === $screen_id ) {
				wp_enqueue_script( 'everest-forms-entries-scripts' );
				wp_localize_script(
					'everest-forms-entries-scripts',
					'everest_forms_entries',
					array(
						'nonce'                   => wp_create_nonce( 'everest-forms-entry' ),
						'ajax_url'                => admin_url( 'admin-ajax.php', 'relative' ),
						'entry_star'              => esc_html__( 'Star entry', 'everest-forms-pro' ),
						'entry_unstar'            => esc_html__( 'Unstar entry', 'everest-forms-pro' ),
						'entry_read'              => esc_html__( 'Mark entry read', 'everest-forms-pro' ),
						'entry_unread'            => esc_html__( 'Mark entry unread', 'everest-forms-pro' ),
						'entry_approved'          => esc_html__( 'Mark entry approved', 'everest-forms-pro' ),
						'entry_denied'            => esc_html__( 'Mark entry denied', 'everest-forms-pro' ),
						'entry_update_nonce'      => wp_create_nonce( 'everest_forms_entry_update' ),
						'unload_confirmation_msg'      => esc_html__( 'The changes you made will be lost if you navigate away from this page.', 'everest-forms-pro' ),
						'entry_save_failed_msg'        => esc_html__( 'Entry could not be saved. Please try again.', 'everest-forms-pro' ),
						'resend_notification_success'  => esc_html__( 'Notifications were resent successfully!', 'everest-forms-pro' ),
						'resend_notification_error'    => esc_html__( 'Could not resend notifications. Please try again.', 'everest-forms-pro' ),
					)
				);

				// Load only on edit entry page.
				if ( isset( $_GET['form_id'], $_GET['edit-entry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					include_once EVF_ABSPATH . 'includes/class-evf-frontend-scripts.php';

					$atts = array(
						'id' => (int) $_GET['form_id'], // phpcs:ignore WordPress.Security.NonceVerification
					);

					// Load frontend scripts.
					$this->frontend_enqueue_scripts( $atts );
					EVF_Frontend_Scripts::load_scripts();

					// Load shortcode script.
					$this->shortcode_scripts( $atts, 'edit-entry' );

					// Load field scripts action.
					do_action( 'everest_forms_shortcode_scripts', $atts );
				}
			}

			// EverestForms builder pages.
			if ( in_array( $screen_id, array( 'everest-forms_page_evf-builder' ), true ) ) {
				wp_enqueue_style( 'ion-range-slider' );
				wp_enqueue_script( 'everest-forms-builder-scripts' );
				$pgw_logo_urls = array();
				foreach ( array(
							  'stripe'        => 'stripe.svg',
					          'paypal'        => 'paypal.svg',
					          'square'        => 'square_logo.svg',
					          'mollie'        => 'mollie.svg',
					          'razorpay'      => 'razorpay_logo.svg.svg',
					          'authorize_net' => 'authorize.net.svg',
						  ) as $slug => $file ) {
					$pgw_logo_urls[ $slug ] = esc_url( plugins_url( 'assets/img/payment/' . $file, EFP_PLUGIN_FILE ) );
				}

				$addons_url = admin_url( 'admin.php?page=evf-dashboard#/features?category=Payment%20Gateways' );
				wp_localize_script(
					'everest-forms-builder-scripts',
					'everest_forms_builder',
					array(
						'ajax_url'                 => admin_url( 'admin-ajax.php' ),
						'evf_lookup_field_nonce'   => wp_create_nonce( 'evf_lookup_field_nonce' ),
						'evf_webhook_nonce'        => wp_create_nonce( 'evf_webhook_nonce' ),
						'i18n_field_rating_greater_than_max_value_error' => esc_html__( 'Please enter in a value less than 100.', 'everest-forms-pro' ),
						'i18n_privacy_policy_consent_message' => esc_html__( 'I allow this website to collect and store the submitted data.', 'everest-forms-pro' ),
						'i18n_pgw_selector_choose_one' => esc_html__( 'Enable a payment gateway in the field options to start accepting payments.', 'everest-forms-pro' ),
						'i18n_pgw_selector_no_addon'   => sprintf(
							/* translators: %s: Addons page URL. */
							wp_kses_post( __( 'You haven\'t enabled any payment gateway add-ons yet. <a href="%s" target="_blank" rel="noopener noreferrer">Enable a payment gateway add-on</a> to get started.', 'everest-forms-pro' ) ),
							esc_url( $addons_url )
						),
						'i18n_required'            => esc_html__( '(Required)', 'everest-forms-pro' ),
						'isProFieldCodeRequired'   => $this->evf_is_efp_code_required(),
						'webhook_title_required'   => esc_html__( 'This is required', 'everest-forms-pro' ),
						'webhook_confirm_btn_text' => esc_html__( 'Continue', 'everest-forms-pro' ),
						'webhook_title'            => esc_html__( 'Webhook Title', 'everest-forms-pro' ),
						'pgw_logo_urls'            => $pgw_logo_urls,
						'pgw_connected_gateways'   => function_exists( 'evf_payment_gateway_selector_is_connected' )
							? array_values( array_filter( array_keys( function_exists( 'evf_payment_gateway_selector_labels' ) ? evf_payment_gateway_selector_labels() : array() ), 'evf_payment_gateway_selector_is_connected' ) )
							: array(),
						'pgw_addon_active_gateways' => function_exists( 'evf_payment_gateway_selector_is_addon_active' )
							? array_values( array_filter( array_keys( function_exists( 'evf_payment_gateway_selector_labels' ) ? evf_payment_gateway_selector_labels() : array() ), 'evf_payment_gateway_selector_is_addon_active' ) )
							: array(),
						'pgw_settings_urls'        => array(
							'stripe'        => esc_url_raw( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-stripe' ) ),
							'paypal'        => esc_url_raw( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-paypal' ) ),
							'square'        => esc_url_raw( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-square' ) ),
							'mollie'        => esc_url_raw( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-mollie' ) ),
							'razorpay'      => esc_url_raw( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-razorpay' ) ),
							'authorize_net' => esc_url_raw( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-authorize_net' ) ),
						),
						'i18n_pgw_fill_creds'      => esc_html__( 'Configure %s credentials to enable payments.', 'everest-forms-pro' ),
						'i18n_pgw_go_to_settings'  => esc_html__( 'Go to Payment Settings', 'everest-forms-pro' ),
					)
				);
			}
		}
	}

	/**
	 * Register Analytics submenu page (Pro version).
	 */
	public function analytics_menu() {
		add_submenu_page(
			'everest-forms',
			esc_html__( 'Analytics', 'everest-forms-pro' ),
			esc_html__( 'Analytics', 'everest-forms-pro' ),
			'manage_everest_forms',
			'evf-analytics',
			array( $this, 'analytics_page' )
		);
	}

	/**
	 * Register Payment Log as its own submenu (moved from Tools tabs).
	 */
	public function payment_log_menu() {
		global $wpdb;
		$count      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}evf_entrymeta` WHERE meta_key = 'type' AND meta_value = 'payment'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$menu_label = $count > 1
			? esc_html__( 'Payments', 'everest-forms-pro' )
			: esc_html__( 'Payment', 'everest-forms-pro' );
		add_submenu_page(
			'everest-forms',
			$menu_label,
			$menu_label,
			'manage_everest_forms',
			'evf-payment-log',
			array( $this, 'payment_log_page' )
		);
	}

	/**
	 * Payment Log page output.
	 */
	public function payment_log_page() {
		if ( ! class_exists( 'EVF_Pro_Admin_Tools' ) ) {
			return;
		}

		global $wpdb;
		$table_name   = $wpdb->prefix . 'evf_entrymeta';
		$has_payments = $wpdb->get_var( "SELECT COUNT(*) FROM `$table_name` WHERE meta_key = 'type' AND meta_value = 'payment'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		echo '<div id="evf-react-header-root"></div>';

		if ( ! $has_payments ) {
			include dirname( __FILE__ ) . '/views/html-admin-page-payment-log-empty.php';
			return;
		}

		echo '<div class="wrap everest-forms">';
		\EVF_Pro_Admin_Tools::payment_log();
		echo '</div>';
	}

	/**
	 * Include Payment Log screen in EVF admin screen IDs (styles/scripts).
	 *
	 * @param array $screen_ids Screen ids.
	 * @return array
	 */
	public function register_payment_log_screen_id( $screen_ids ) {
		$screen_ids[] = 'everest-forms_page_evf-payment-log';
		return $screen_ids;
	}

	/**
	 * Redirect old Tools &rarr; Payment Log tab URL to the submenu page.
	 */
	public function maybe_redirect_payment_log_tools_tab() {
		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['page'] ) || empty( $_GET['tab'] ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'evf-tools' === $_GET['page'] && 'payment_log' === $_GET['tab'] ) {
			wp_safe_redirect( admin_url( 'admin.php?page=evf-payment-log' ) );
			exit;
		}
	}

	/**
	 * Analytics page callback (Pro version).
	 */
	public function analytics_page() {
		echo '<div id="evf-react-header-root"></div><div class="wrap"><div id="evf-analytics-root"></div></div>';
	}

	/**
	 * Print dashboard analytics script.
	 * Called via 'everest_forms_dashboard_scripts' action hook.
	 * This ensures the pro analytics script loads BEFORE the free dashboard script.
	 *
	 * @since x.x.x
	 */
	public function print_dashboard_analytics_script() {
		wp_print_scripts( 'evf-pro-analytics' );
	}

	/**
	 * Load shortcode scripts.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $page Conditionally load script on certain pages.
	 */
	public function shortcode_scripts( $atts, $page = '' ) {
		$form_data = evf()->form->get( $atts['id'], array( 'content_only' => true ) );
		if ( ! empty( $form_data['form_fields'] ) ) {
			$is_phone = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'         => 'phone',
					'phone_format' => 'smart',
				)
			);

			$is_country = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'                => 'country',
					'enable_country_flag' => 1,
				)
			);

			$is_address = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'                => 'address',
					'enable_country_flag' => 1,
				)
			);

			$is_password = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'              => 'password',
					'password_strength' => 1,
				)
			);

			$validate_password = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'                => 'password',
					'password_validation' => 1,
				)
			);

			$is_file_upload = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type' => 'file-upload',
				)
			);

			$is_image_upload = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type' => 'image-upload',
				)
			);

			if ( ! empty( $is_phone ) || ! empty( $is_country ) || ! empty( $is_address ) ) {
				wp_enqueue_style( 'jquery-intl-tel-input' );

				if ( ! empty( $is_phone ) ) {
					wp_enqueue_script( 'jquery-intl-tel-input' );
				}

				if ( ! empty( $is_country ) || ! empty( $is_address ) ) {
					wp_enqueue_style( 'select2' );
					wp_enqueue_script( 'selectWoo' );
				}
			}

			if ( ! empty( $is_password ) ) {
				wp_enqueue_script( 'evf-password-strength-meter' );
			}

			if ( ! empty( $validate_password ) ) {
				wp_enqueue_script( 'evf-password-validation' );
			}

			if ( ! empty( $is_file_upload ) || ! empty( $is_image_upload ) ) {
				if ( $this->evf_is_efp_code_required() ) {
					wp_enqueue_script( 'everest-forms-file-upload' );
				}
			}

			// Load scripts for edit entry.
			if ( 'edit-entry' === $page ) {
				$is_email = wp_list_filter(
					$form_data['form_fields'],
					array(
						'type' => 'email',
					)
				);

				$is_datetime = wp_list_filter(
					$form_data['form_fields'],
					array(
						'type' => 'date-time',
					)
				);

				if ( ! empty( $is_phone ) || ! empty( $is_country ) || ! empty( $is_address ) ) {
					wp_enqueue_style( 'jquery-intl-tel-input' );

					if ( ! empty( $is_phone ) ) {
						wp_enqueue_script( 'jquery-intl-tel-input' );
					}

					if ( ! empty( $is_country ) || ! empty( $is_address ) ) {
						wp_enqueue_style( 'evf_select2' );
						wp_enqueue_script( 'selectWoo' );
					}
					if ( ! empty( $is_datetime ) ) {
						wp_enqueue_style( 'flatpickr' );
						wp_enqueue_script( 'flatpickr' );
					}
				}

				if ( ! empty( $is_email ) && (bool) apply_filters( 'everest_forms_mailcheck_enabled', true ) ) {
					wp_enqueue_script( 'mailcheck' );
				}

				// Load frontend scripts.
				wp_enqueue_script( 'everest-forms-pro' );

				// Load editable entry scripts.
				do_action( 'everest_forms_editable_entry_scripts', $form_data );
			}
		}
	}

	/**
	 * Append additional strings for form builder.
	 *
	 * @since 1.3.0
	 *
	 * @param array $strings List of strings.
	 *
	 * @return array
	 */
	public function form_builder_strings( $strings ) {
		$currency   = get_option( 'everest_forms_currency', 'USD' );
		$currencies = evf_get_currencies();

		$strings['currency']            = sanitize_text_field( $currency );
		$strings['currency_name']       = sanitize_text_field( $currencies[ $currency ]['name'] );
		$strings['currency_decimal']    = sanitize_text_field( $currencies[ $currency ]['decimal_separator'] );
		$strings['currency_thousands']  = sanitize_text_field( $currencies[ $currency ]['thousands_separator'] );
		$strings['currency_symbol']     = sanitize_text_field( $currencies[ $currency ]['symbol'] );
		$strings['currency_symbol_pos'] = sanitize_text_field( $currencies[ $currency ]['symbol_pos'] );

		return $strings;
	}

	/**
	 * Frontend Enqueue scripts.
	 */
	public function frontend_enqueue_scripts( $atts ) {

		if ( version_compare( EVF_VERSION, '1.8.4', '>=' ) && evf_is_amp() ) {
			return;
		}

		$strings = array();
		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'everest-forms-pro-frontend', plugins_url( '/assets/css/everest-forms-pro-frontend.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );

		wp_enqueue_script( 'everest-forms-pro', plugins_url( "/assets/js/frontend/everest-forms-pro{$suffix}.js", EFP_PLUGIN_FILE ), array( 'everest-forms' ), EFP_VERSION, true );
		wp_enqueue_script( 'conditional-logic-frontend', plugins_url( "/assets/js/frontend/conditional-logic-frontend{$suffix}.js", EFP_PLUGIN_FILE ), array( 'everest-forms' ), EFP_VERSION, true );

		wp_localize_script(
			'everest-forms-pro',
			'everest_forms_pro_params',
			array(
				'plugin_url'             => plugin_dir_url( EFP_PLUGIN_FILE ),
				'isProFieldCodeRequired' => $this->evf_is_efp_code_required(),
				'i18n_discount'			 => esc_html__( 'Discount', 'everest-froms-pro' ),
			)
		);

		$square_mode   = 'yes' === get_option( 'everest_forms_pro_square_test_mode' ) ? 'test' : 'live';
		$square_app_id = get_option( "everest_forms_square_{$square_mode}_app_id" );
		$location_id   = get_option( "everest_forms_square_{$square_mode}_location_id" );
		wp_register_script( 'everest-forms-pro-square-payment', plugins_url( 'src/Addons/Square/assets/js/frontend/evf-square-payment' . $suffix . '.js', EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION );
		wp_localize_script(
			'everest-forms-pro-square-payment',
			'evf_square_payment_obj',
			array(
				'ajax_url'                   => admin_url( 'admin-ajax.php' ),
				'security'                   => wp_create_nonce( 'evf_square_payment_nonce' ),
				'app_id'                     => $square_app_id,
				'location_id'               => $location_id,
				'i18n_payment_failed'        => __( 'Payment failed. Please try again.', 'everest-forms-pro' ),
				'i18n_payment_confirm_error' => __( 'Payment was processed but we could not confirm your order. Please contact support.', 'everest-forms-pro' ),
			)
		);

		// Range Slider Scripts.
		wp_register_style( 'ion-range-slider', plugins_url( '/assets/css/rangeSlider.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
		wp_register_script( 'ion-range-slider', plugins_url( "/assets/js/ion-range-slider/ion.rangeSlider{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), '2.3.1', true );

		// Signature pad scripts.
		wp_register_script( 'signature-pad', plugins_url( '/assets/js/signature_pad/signature_pad.umd.js', EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION, true );
		wp_register_script( 'everest-forms-signature', plugins_url( "/assets/js/frontend/signature{$suffix}.js", EFP_PLUGIN_FILE ), array( 'everest-forms', 'signature-pad' ), EFP_VERSION, true );

		// Smart phone field scripts.
		if ( $this->evf_is_efp_code_required() ) {
			wp_register_style( 'jquery-intl-tel-input', plugins_url( '/assets/css/intlTelInput.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
			wp_register_script( 'jquery-intl-tel-input', plugins_url( "/assets/js/intlTelInput/jquery.intlTelInput{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), '16.0.7', true );
		}
		// Password strength meter scripts.
		wp_register_script( 'evf-password-strength-meter', plugins_url( "/assets/js/frontend/password-strength-meter{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'password-strength-meter' ), EFP_VERSION, true );
		wp_register_script( 'evf-password-validation', plugins_url( "/assets/js/frontend/password-validation{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION, true );
		wp_localize_script(
			'evf-password-validation',
			'everest_forms_password_params',
			array(
				'one_number'            => esc_html__( 'One number', 'everest-forms-pro' ),
				'one_lowercase'         => esc_html__( 'One lowercase character', 'everest-forms-pro' ),
				'one_uppercase'         => esc_html__( 'One uppercase character', 'everest-forms-pro' ),
				'one_special_character' => esc_html__( 'One special character', 'everest-forms-pro' ),
				'min_length'            => esc_html__( 'Minimum 8 characters', 'everest-forms-pro' ),
				'strong'                => esc_html__( 'Your password is strong', 'everest-forms-pro' ),
				'weak'                  => esc_html__( 'Your password is weak', 'everest-forms-pro' ),
			)
		);
		if ( $this->evf_is_efp_code_required() ) {
			// File and image upload field scripts.
			wp_register_script( 'dropzone', plugins_url( "/assets/js/dropzone/dropzone{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), '5.5.0', true );
			wp_register_script( 'everest-forms-file-upload', plugins_url( "/assets/js/frontend/everest-forms-file-upload{$suffix}.js", EFP_PLUGIN_FILE ), array( 'dropzone', 'wp-util' ), EFP_VERSION, true );
			wp_localize_script(
				'everest-forms-file-upload',
				'everest_forms_upload_parms',
				array(
					'url'             => admin_url( 'admin-ajax.php' ),
					'errors'          => array(
						'file_not_uploaded' => esc_html__( 'This file was not uploaded.', 'everest-forms-pro' ),
						'file_limit'        => esc_html__( 'File limit has been reached ({fileLimit}).', 'everest-forms-pro' ),
						'file_extension'    => get_option( 'everest_forms_fileextension_validation' ),
						'file_size'         => get_option( 'everest_forms_filesize_validation', __( 'File exceeds max size allowed.', 'everest-forms-pro' ) ),
						'post_max_size'     => sprintf(
						/* translators: %s: Max upload size */
							esc_html__( 'File exceeds the upload limit allowed (%s).', 'everest-forms-pro' ),
							evf_max_upload()
						),
					),
					'max_timeout'     => apply_filters( 'evf_fileupload_max_timeout', absint( 30000 ) ),
					'loading_message' => esc_html__( 'Do not submit the form until the upload process is finished', 'everest-forms-pro' ),
				)
			);
		}

		// Dynamic state dropdown field script.
		wp_localize_script(
			'everest-forms-pro',
			'evf_state_drop_down_params',
			array(
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'ajax_everest_forms_state_nonce' => wp_create_nonce( 'everest_forms_state_nonce' ),
			)
		);

		// Lookup field script.
		wp_localize_script(
			'everest-forms-pro',
			'evf_lookup_field_params',
			array(
				'ajax_url'                        => admin_url( 'admin-ajax.php' ),
				'ajax_everest_forms_lookup_nonce' => wp_create_nonce( 'everest_forms_lookup_nonce' ),
			)
		);

		if ( function_exists( 'evf_get_currencies' ) ) {
			$currency                       = get_option( 'everest_forms_currency', 'USD' );
			$currencies                     = evf_get_currencies();
			$strings['currency_code']       = $currency;
			$strings['currency_thousands']  = $currencies[ $currency ]['thousands_separator'];
			$strings['currency_decimal']    = $currencies[ $currency ]['decimal_separator'];
			$strings['currency_symbol']     = $currencies[ $currency ]['symbol'];
			$strings['currency_symbol_pos'] = $currencies[ $currency ]['symbol_pos'];
		}
		$strings = apply_filters( 'everest_forms_frontend_strings', $strings );

		foreach ( (array) $strings as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$strings[ $key ] = html_entity_decode( (string) $value, ENT_QUOTES, 'UTF-8' );
		}
		if ( false === apply_filters( 'everest_forms_currency_setting', false ) ) {
			echo "<script type='text/javascript'>\n";
			echo "/* <![CDATA[ */\n";
			echo 'var evf_settings = ' . wp_json_encode( $strings ) . "\n";
			echo "/* ]]> */\n";
			echo "</script>\n";
		}

		$form_id     = isset( $atts['id'] ) ? absint( $atts['id'] ) : 0;
		$form_fields = evf_get_form_fields( $form_id );
		$form_data   = EVF()->form->get( $form_id, array( 'content_only' => true ) );
		$form_fields = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();

		foreach ( $form_fields as $form_field ) {
			if ( isset( $form_field['tooltip_description'] ) && '' !== $form_field['tooltip_description'] ) {
				// Register JS.
				wp_register_script(
					'tooltipster',
					plugins_url( 'assets/js/tooltipster/tooltipster.bundle.min.js', EVF_PLUGIN_FILE ),
					array( 'jquery', 'everest-forms' ),
					EVF_VERSION,
					true
				);

				// Enqueue Js.
				wp_enqueue_script(
					'everest-forms-tooltips',
					plugins_url( "assets/js/frontend/everest-forms-tooltips{$suffix}.js", EFP_PLUGIN_FILE ),
					array( 'jquery', 'everest-forms', 'tooltipster' ),
					EFP_VERSION,
					true
				);
			}
		}

		do_action( 'everest_forms_footer_end' );
	}

	/**
	 * Modify field name property for tooltip fields.
	 *
	 * @since 1.5.2
	 *
	 * @param array $properties List field properties.
	 * @param array $field Field data and settings.
	 * @param array $form_data Form data and settings.
	 *
	 * @return array Modified field properties.
	 */
	public function field_tooltip_property( $properties, $field, $form_data ) {
		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $fld ) {
				if ( isset( $fld['show_tooltip'] ) && ! empty( $fld['tooltip_description'] ) ) {
					$properties['label']['class'][] = 'everest-forms-tooltip';
				}
			}
		}
		return $properties;
	}

	/**
	 * Load Gutenberg block scripts.
	 *
	 * Payment gateway selector card styles are enqueued from
	 * EVF_Field_Payment_Gateway_Selector::enqueue_block_editor_styles().
	 */
	public function enqueue_block_editor_assets() {
		// Reserved for block-editor assets that apply to all Everest Forms blocks.
	}

	/**
	 * Add new settings to the validation settings page.
	 *
	 * @since  1.0.0
	 * @param  mixed $settings array of settings.
	 * @return mixed
	 */
	public function validation_settings( $settings ) {
		$new_settings = array(
			array(
				'title'    => __( 'Phone Number', 'everest-forms-pro' ),
				'desc'     => __( 'Enter the message for valid phone number.', 'everest-forms-pro' ),
				'default'  => __( 'Please enter a valid phone number.', 'everest-forms-pro' ),
				'css'      => 'min-width: 350px;',
				'id'       => 'everest_forms_phone_validation',
				'type'     => 'text',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'File Extension', 'everest-forms-pro' ),
				'desc'     => __( 'Enter the message for the allowed file extensions.', 'everest-forms-pro' ),
				'default'  => __( 'File type is not allowed.', 'everest-forms-pro' ),
				'css'      => 'min-width: 350px;',
				'id'       => 'everest_forms_fileextension_validation',
				'type'     => 'text',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'File Size', 'everest-forms-pro' ),
				'desc'     => __( 'Enter the message for the max file size allowed.', 'everest-forms-pro' ),
				'default'  => __( 'File exceeds max size allowed.', 'everest-forms-pro' ),
				'id'       => 'everest_forms_filesize_validation',
				'css'      => 'min-width: 350px;',
				'type'     => 'text',
				'desc_tip' => true,
			),
		);

		// Add new settings to the existing ones.
		foreach ( $settings as $key => $setting ) {
			if ( isset( $setting['id'] ) && 'everest_forms_number_validation' === $setting['id'] ) {
				array_splice( $settings, $key + 1, 0, $new_settings );
				break;
			}
		}

		return $settings;
	}

	/**
	 * Add uploaded file protection and load fonts locally settings to the misc settings page.
	 *
	 * @param array $settings List of settings.
	 * @return array Modified list of settings.
	 */
	public function general_misc_settings( $settings ) {
		$count         = count( $settings ) - 1;
		$misc_settings = array(
			array(
				'title'    => esc_html__( 'Load Fonts Locally', 'everest-forms' ),
				'desc'     => __( 'Load all the necessary fonts from local server for GDPR compliance.', 'everest-forms' ),
				'id'       => 'everest_forms_load_fonts_locally',
				'type'     => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
		);
		array_splice( $settings, $count, 0, $misc_settings );
		return $settings;
	}

	/**
	 * File Protection.
	 *
	 * @param $settings Misc settings.
	 */
	public function misc_settings( $settings ) {
		$count         = count( $settings ) - 1;
		$misc_settings = array(
			array(
				'title'    => esc_html__( 'Uploaded File Protection', 'everest-forms' ),
				'desc'     => sprintf(
					"%s <a href='%s' target='_blank'>%s</a>",
					esc_html__( 'Check to protect the uploaded file from direct access as well as being indexed and searchable by Google and other search engines.', 'everest-forms' ),
					esc_url( 'https://docs.everestforms.net/docs/how-to-protect-uploaded-files-from-direct-access-pro/' ),
					esc_html__( 'For more information', 'everest-forms' )
				),
				'id'       => 'everest_forms_upload_file_protection',
				'default'  => 'no',
				'type'     => 'toggle',
				'desc_tip' => true,
			),
		);
		array_splice( $settings, $count, 0, $misc_settings );
		return $settings;
	}
	/**
	 * Enable Auto delete entries.
	 *
	 * @param mixed $settings Settings.
	 */
	public function general_settings( $settings ) {
		$new_settings = array(
			array(
				'title'    => esc_html__( 'Enable scheduled entry delete', 'everest-forms' ),
				'desc'     => esc_html__( 'Enable entry delete on schedule time.', 'everest-forms' ),
				'id'       => 'everest_forms_scheduled_entry_delete',
				'default'  => 'no',
				'type'     => 'toggle',
				'desc_tip' => true,
			),
			array(
				'title'   => esc_html__( 'Delete entries older than', 'everest-forms' ),
				'type'    => 'select',
				'id'      => 'everest_forms_scheduled_entry_delete_time',
				'default' => '30',
				'options' => array(
					'1'   => 'day',
					'7'   => 'week',
					'30'  => 'month',
					'90'  => 'quarter',
					'180' => 'half year',
					'365' => 'year',
				),
			),
			array(
				'title'    => esc_html__( 'Enable admin approval entries', 'everest-forms' ),
				'id'       => 'everest_forms_admin_approval_entries_enable',
				'desc'     => esc_html__( 'Enable this to send the admin approval link to entries', 'everest-forms' ),
				'type'     => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'To Address', 'everest-forms' ),
				'desc'     => esc_html__( 'Choose the type of email address to send email notification', 'everest-forms' ),
				'id'       => 'everest_forms_admin_approval_entries_email_notification',
				'default'  => 'site_admin_email',
				'type'     => 'radio',
				'options'  => array(
					'site_admin_email' => 'Admin Email',
					'custom_email'     => 'Custom Email',
				),
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'Custom email address', 'everest-forms' ),
				'desc'     => esc_html__( 'Enter the email address if you want to send the entry notification to someone other than the site admin', 'everest-forms' ),
				'id'       => 'everest_forms_admin_approval_entries_custom_email',
				'type'     => 'text',
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'Email Subject', 'everest-forms' ),
				'desc'     => esc_html__( 'Enter the email subject for admin approval entries', 'everest-forms' ),
				'id'       => 'everest_forms_admin_approval_entries_email_subject',
				'default'  => esc_html__( 'Approval notification for new pending entry', 'everest-forms' ),
				'type'     => 'text',
				'desc_tip' => true,
			),
			array(
				'title'    => esc_html__( 'Email Message', 'everest-forms' ),
				'desc'     => '<p class="desc">' . sprintf( esc_html__( 'To display approval link, use the %1$s Smart Tag. Use %2$s Smart Tag for Denial link', 'everest-forms' ), '<code>{approval_link}</code>', '<code>{denial_link}</code>' ) . '</p>',
				'id'       => 'everest_forms_admin_approval_entries_email_body',
				'default'  => __( 'Hello admin, <br /> <p>A new entry has been made. Click here to approve entry {approval_link}<br /> Click here to deny the entry {denial_link}</p> <br/> <p>{all_fields}</p> <br/> <p>Best regrads, <br /> {site_name}</p>', 'everest-forms-pro' ),
				'desc_tip' => false,
				'type'     => 'tinymce',
			),
			array(
				'title'    => esc_html__( 'Enable automatic entry delete', 'everest-forms' ),
				'desc'     => esc_html__( 'Enable this toggle to delete the pending and denied entries according to the days specified', 'everest-forms' ),
				'id'       => 'everest_forms_admin_approval_entries_pending_delete',
				'type'     => 'toggle',
				'default'  => 'no',
				'desc_tip' => true,
			),
			array(
				'title'             => esc_html__( 'Admin entries approval and denied waiting days', 'everest-forms' ),
				'type'              => 'number',
				'id'                => 'everest_forms_admin_approval_entries_waiting_days',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
					'max'  => '183',
				),
				'default'           => '1',
				'desc_tip'          => false,
			),
		);

		array_splice( $settings, count( $settings ) - 1, 0, $new_settings );

		return $settings;
	}

	/**
	 * Add additional bulk actions.
	 *
	 * @param array $actions Bulk actions.
	 */
	public function entry_bulk_actions( $actions ) {
		return array_merge(
			array(
				'star'     => esc_html__( 'Star', 'everest-forms-pro' ),
				'unstar'   => esc_html__( 'Unstar', 'everest-forms-pro' ),
				'read'     => esc_html__( 'Mark Read', 'everest-forms-pro' ),
				'unread'   => esc_html__( 'Mark Unread', 'everest-forms-pro' ),
				'approved' => esc_html__( 'Approve Entry', 'everest-forms-pro' ),
				'denied'   => esc_html__( 'Deny Entry', 'everest-forms-pro' ),
			),
			$actions
		);
	}

	/**
	 * Add additional entry statues.
	 *
	 * @param array $statuses Entry statuses.
	 */
	public function entry_statuses( $statuses ) {
		$position     = array_search( 'trash', array_keys( $statuses ), true );
		$new_statuses = array(
			'unread'  => esc_html__( 'Unread', 'everest-forms-pro' ),
			'read'    => esc_html__( 'Read', 'everest-forms-pro' ),
			'starred' => esc_html__( 'Starred', 'everest-forms-pro' ),
			'pending' => esc_html__( 'Pending', 'everest-forms-pro' ),
			'denied'  => esc_html__( 'Denied', 'everest-forms-pro' ),
		);

		return array_merge( array_slice( $statuses, 0, $position ), $new_statuses, array_slice( $statuses, $position ) );
	}

	/**
	 * Entries table column.
	 *
	 * @param array $columns Columns.
	 * @param array $form_data Forms data.
	 */
	public function entries_table_columns( $columns, $form_data ) {
		global $entries_table_list;

		$new_columns = array();

		// Get current form_id
		$form_id = isset( $entries_table_list->form_id ) ? $entries_table_list->form_id : 0;

		if ( empty( $_GET['status'] ) || ( isset( $_GET['status'] ) && 'trash' !== $_GET['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$new_columns['indicators'] = '';
			$columns                   = array_merge( array_slice( $columns, 0, 1 ), $new_columns, array_slice( $columns, 1 ) );
		}

		$new_columns = array( 'status' => esc_html__( 'Status', 'everest-forms-pro' ) );

		$pos = array_search( 'date', array_keys( $columns ), true );
		if ( false !== $pos ) {
			$columns = array_merge( array_slice( $columns, 0, $pos ), $new_columns, array_slice( $columns, $pos ) );
		} else {
			$columns = array_merge( $columns, $new_columns );
		}

		$paypal_enabled        = ( isset( $form_data['payments']['paypal']['enable_paypal'] ) && '1' === $form_data['payments']['paypal']['enable_paypal'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'paypal' ) ) );
		$stripe_enabled        = ( isset( $form_data['payments']['stripe']['enable_stripe'] ) && '1' === $form_data['payments']['stripe']['enable_stripe'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'stripe' ) ) );
		$razorpay_enabled      = ( isset( $form_data['payments']['razorpay']['enable_razorpay'] ) && '1' === $form_data['payments']['razorpay']['enable_razorpay'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'razorpay' ) ) );
		$authorize_net_enabled = ( isset( $form_data['payments']['authorize_net']['enable_authorize_net'] ) && '1' === $form_data['payments']['authorize_net']['enable_authorize_net'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'authorize_net' ) ) );
		$mollie_enabled        = ( isset( $form_data['payments']['mollie']['enable_mollie'] ) && '1' === $form_data['payments']['mollie']['enable_mollie'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) ) );
		$square_enabled        = ( isset( $form_data['payments']['square']['enable_square'] ) && '1' === $form_data['payments']['square']['enable_square'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) ) );

		if ( ( $paypal_enabled || $stripe_enabled || $razorpay_enabled || $authorize_net_enabled || $square_enabled || $mollie_enabled ) && 0 !== $form_id ) {
			$new_columns = array( 'payment_status' => esc_html__( 'Payment Status', 'everest-forms-pro' ) );
			$pos         = array_search( 'date', array_keys( $columns ), true );
			if ( false !== $pos ) {
				$columns = array_merge( array_slice( $columns, 0, $pos ), $new_columns, array_slice( $columns, $pos ) );
			}
		}

		return $columns;
	}

	/**
	 * Renders the columns.
	 *
	 * @param  string $value Entry value.
	 * @param  object $entry Entry object.
	 * @param  string $column_name Column Name.
	 * @return string
	 */
	public function entries_table_column_value( $value, $entry, $column_name ) {

		if ( isset( $entry->meta ) ) {
			$payment_meta = $entry->meta;

			if ( isset( $payment_meta['meta'] ) ) {
				$payment_details = json_decode( $payment_meta['meta'] );
			}
		}
		switch ( $column_name ) {
			case 'total':
				if ( isset( $payment_details->payment_total ) ) {
					$amount = evf_sanitize_amount( $payment_details->payment_total, $payment_details->payment_currency );
					$total  = evf_format_amount( $amount, true, $payment_details->payment_currency );
					$value  = $total;
				} else {
					$value = '<span class="na">&mdash;</span>';
				}
				break;
			case 'payment_status':
				if ( ! empty( $payment_meta['status'] ) ) {
					$payment_status = strtolower( trim( $payment_meta['status'] ) );
					$cancel_aliases = array( 'canceled', 'cancelled', 'cancled', 'cancel' );

					if ( in_array( $payment_status, $cancel_aliases, true ) ) {
						$payment_status_class = 'cancelled';
					} elseif ( 'completed' === $payment_status ) {
						$payment_status_class = 'complete';
					} else {
						$payment_status_class = sanitize_html_class( $payment_status );
					}

					$inline_status_style = '';
					if ( 'cancelled' === $payment_status_class ) {
						$inline_status_style = ' style="color:#7e7d77;border:1px solid #7e7d77;"';
					}
					$dollar_icon          = plugins_url( '/assets/img/icon-dollar.png', EFP_PLUGIN_FILE );

					$status_label = ucfirst( $payment_meta['status'] );
					if ( 'cancelled' === $payment_status_class ) {
						$status_label = esc_html__( 'Cancelled', 'everest-forms-pro' );
					}

					$value = '<span class="payment_status ' . esc_attr( $payment_status_class ) . '"' . $inline_status_style . '>' . esc_html( $status_label ) . '<img src="' . esc_url( $dollar_icon ) . '" alt="' . esc_attr__( 'Payment', 'everest-forms-pro' ) . '"></span>';
				} else {
					$value = '-';
				}
				break;
			case 'status':
				if ( $entry->viewed ) {
					$value = '<span class="evf-badge evf-badge-read">' . esc_html__( 'Read', 'everest-forms-pro' ) . '</span>';
				} else {
					$value = '<span class="evf-badge evf-badge-unread">' . esc_html__( 'Unread', 'everest-forms-pro' ) . '</span>';
				}
				break;
			case 'indicators':
				// Stars.
				$star_action = empty( $entry->starred ) ? 'star' : 'unstar';
				$star_title  = empty( $entry->starred ) ? esc_html__( 'Star entry', 'everest-forms-pro' ) : esc_html__( 'Unstar entry', 'everest-forms-pro' );
				$value       = '<a href="#" class="indicator-star ' . $star_action . '" data-id="' . absint( $entry->entry_id ) . '" title="' . esc_attr( $star_title ) . '"><span class="dashicons dashicons-star-filled"></span></a>';
				break;

		}

		return $value;
	}

	/**
	 * Ajax handler to toggle entry read state.
	 *
	 * @since 1.6.0
	 */
	public function ajax_entry_read() {
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;

		check_ajax_referer( 'everest-forms-entry', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_view_entry', $entry_id ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['task'] ) ) {
			$is_success = EVF_Admin_Entries::update_status( $entry_id, sanitize_key( $_POST['task'] ) );

			if ( $is_success ) {
				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Ajax handler to toggle entry stars.
	 *
	 * @since 1.6.0
	 */
	public function ajax_entry_star() {
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;

		check_ajax_referer( 'everest-forms-entry', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_view_entry', $entry_id ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['task'] ) ) {
			$is_success = EVF_Admin_Entries::update_status( $entry_id, sanitize_key( $_POST['task'] ) );

			if ( $is_success ) {
				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Ajax handler to toggle entry approval.
	 *
	 * @since 2.0.9
	 */
	public function ajax_entry_approval() {
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;

		check_ajax_referer( 'everest-forms-entry', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_view_entry', $entry_id ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['task'] ) ) {
			$is_success = EVF_Admin_Entries::update_status( $entry_id, sanitize_key( $_POST['task'] ) );

			if ( $is_success ) {
				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Denies the entry.
	 *
	 * @since 2.0.9
	 */
	public function ajax_entry_denial() {
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;

		check_ajax_referer( 'everest-forms-entry', 'nonce' );

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_view_entry', $entry_id ) ) {
			wp_send_json_error();
		}

		if ( ! empty( $_POST['task'] ) ) {
			$is_success = EVF_Admin_Entries::update_status( $entry_id, sanitize_key( $_POST['task'] ) );

			if ( $is_success ) {
				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}

	/**
	 * Add starred icon if needed.
	 *
	 * @param object $entry Entry data.
	 */
	// public function add_starred_icon( $entry ) {
	// echo '1' === $entry->starred ? '<span class="dashicons dashicons-star-filled"></span>' : '';
	// }

	/**
	 * Entry details action metabox.
	 *
	 * @param object $entry      Submitted entry values.
	 * @param object $entry_meta Entry meta data.
	 * @param array  $form_data  Form data and settings.
	 */
	public function entry_details_actions( $entry, $form_data ) {
		$is_viewed    = false;
		$action_links = array();
		$entry_meta   = $entry->meta;

		// Marked entry as read.
		if ( '1' !== $entry->viewed && empty( $_GET['action'] ) && empty( $_GET['unread'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_viewed = EVF_Admin_Entries::update_status( absint( $entry->entry_id ), 'read' );
		}

		$base_url = add_query_arg(
			array(
				'page'     => 'evf-entries',
				'form_id'  => absint( $form_data['id'] ),
				'entry_id' => absint( $entry->entry_id ),
			),
			admin_url( 'admin.php' )
		);

		$action_links['star'] = array(
			'url'   => wp_nonce_url( add_query_arg( array( 'action' => '1' === $entry->starred ? 'unstar' : 'star' ), $base_url ), 'starred-entry' ),
			'icon'  => '1' === $entry->starred ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M9 .75a1.15 1.15 0 0 1 1.028.639l1.733 3.51a.84.84 0 0 0 .633.46l3.872.566c.684.1 1.017.937.553 1.39l-2.803 2.728a.84.84 0 0 0-.242.745l.661 3.851a1.147 1.147 0 0 1-1.67 1.213L9.39 14.6a.84.84 0 0 0-.782 0l-3.462 1.82a1.148 1.148 0 0 1-1.668-1.209l.661-3.854a.84.84 0 0 0-.242-.745L1.097 7.884a1.15 1.15 0 0 1 .636-1.95l3.87-.565a.84.84 0 0 0 .634-.46l1.733-3.51A1.15 1.15 0 0 1 9 .75" fill="#ffba00"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M9 .75a1.15 1.15 0 0 1 1.028.639l1.733 3.51a.84.84 0 0 0 .633.46l3.872.566c.16.023.312.08.447.164l.128.095.116.111q.106.119.176.263l.06.148.039.155a1.15 1.15 0 0 1-.328 1.022h-.001L14.1 10.61a.84.84 0 0 0-.242.745l.66 3.851h.001a1.147 1.147 0 0 1-1.67 1.213L9.39 14.6a.84.84 0 0 0-.782 0h.001l-3.462 1.82h.001a1.148 1.148 0 0 1-1.668-1.209l.661-3.854a.84.84 0 0 0-.242-.745L1.097 7.884l.001-.001a1.15 1.15 0 0 1-.294-1.177l.06-.148c.071-.144.17-.273.294-.376l.13-.095q.136-.085.29-.129l.158-.034 3.87-.565.1-.021a.85.85 0 0 0 .534-.44L7.97 1.39v-.001c.095-.192.242-.353.424-.466l.14-.075Q8.758.75 9 .75M7.585 5.563a2.34 2.34 0 0 1-1.763 1.279l-3.168.464 2.29 2.23a2.35 2.35 0 0 1 .676 2.075l-.541 3.15 2.832-1.488.128-.063a2.34 2.34 0 0 1 1.922 0l.127.063 2.833 1.489-.54-3.152v-.002a2.34 2.34 0 0 1 .673-2.07v-.002l2.29-2.23-3.168-.463a2.35 2.35 0 0 1-1.693-1.155l-.066-.125L9 2.693z"/></svg>',
			'label' => '1' === $entry->starred ? esc_html__( 'Unstar', 'everest-forms-pro' ) : esc_html__( 'Star', 'everest-forms-pro' ),
		);

		if ( '1' === $entry->viewed || $is_viewed ) {
			$action_links['read'] = array(
				'url'   => wp_nonce_url( add_query_arg( array( 'action' => 'unread' ), $base_url ), 'unread-entry' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><g clip-path="url(#a)"><path d="M7.961 3.062a8.81 8.81 0 0 1 9.042 5.06l.145.33.008.025a1.5 1.5 0 0 1-.01 1.07 8.8 8.8 0 0 1-1.183 2.04.75.75 0 1 1-1.185-.919c.397-.51.721-1.073.97-1.67a7.31 7.31 0 0 0-7.61-4.446.75.75 0 0 1-.177-1.49M6.842 6.915A.75.75 0 0 1 7.92 7.958a1.5 1.5 0 0 0 2.121 2.12.75.75 0 0 1 1.042 1.08 3 3 0 0 1-4.242-4.243"/><path d="M4.498 4.237a.75.75 0 0 1 .766 1.29A7.3 7.3 0 0 0 2.251 9a7.31 7.31 0 0 0 10.476 3.48.75.75 0 1 1 .765 1.29A8.815 8.815 0 0 1 .853 9.546l-.01-.025a1.5 1.5 0 0 1 0-1.044l.01-.025a8.8 8.8 0 0 1 3.645-4.216"/><path d="M.97.97a.75.75 0 0 1 1.06 0l15 15a.75.75 0 1 1-1.06 1.06l-15-15a.75.75 0 0 1 0-1.06"/></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h18v18H0z"/></clipPath></defs></svg>',
				'label' => esc_html__( 'Mark Unread', 'everest-forms-pro' ),
			);
		}

		$action_links['approved'] = array(
			'url'   => wp_nonce_url( add_query_arg( array( 'action' => 'approved' ), $base_url ), 'approve-entry' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M.75 15.75a6.75 6.75 0 0 1 11.215-5.063.75.75 0 0 1-.992 1.125A5.25 5.25 0 0 0 2.25 15.75a.75.75 0 0 1-1.5 0"/><path d="M10.5 6a3 3 0 1 0-6 0 3 3 0 0 0 6 0M12 6a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0m3.97 6.22a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-1.5-1.5a.75.75 0 1 1 1.06-1.06l.97.97z"/></svg>',
			'label' => esc_html__( 'Approve Entry', 'everest-forms-pro' ),
		);

		$action_links['denied'] = array(
			'url'   => wp_nonce_url( add_query_arg( array( 'action' => 'denied' ), $base_url ), 'deny-entry' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M.75 15.75a6.75 6.75 0 0 1 11.215-5.063.75.75 0 0 1-.992 1.125A5.251 5.251 0 0 0 2.25 15.75a.75.75 0 1 1-1.5 0"/><path d="M10.5 6a3 3 0 1 0-6 0 3 3 0 0 0 6 0M12 6a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0m4.5 7.5a.75.75 0 0 1 0 1.5H12a.75.75 0 0 1 0-1.5z"/></svg>',
			'label' => esc_html__( 'Deny Entry', 'everest-forms-pro' ),
		);

		$action_links['export'] = array(
			'url'   => wp_nonce_url( add_query_arg( array( 'action' => 'export_csv' ), $base_url ), 'export-entry' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M8.25 11.25v-9a.75.75 0 0 1 1.5 0v9a.75.75 0 0 1-1.5 0"/><path d="M1.5 14.25v-3a.75.75 0 0 1 1.5 0v3a.75.75 0 0 0 .75.75h10.5a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 1 1.5 0v3a2.25 2.25 0 0 1-2.25 2.25H3.75a2.25 2.25 0 0 1-2.25-2.25"/><path d="M12.22 6.97a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0L4.72 8.03a.75.75 0 1 1 1.06-1.06L9 10.19z"/></svg>',
			'label' => esc_html__( 'Export Entry (CSV)', 'everest-forms-pro' ),
		);

		$quiz_enabled = isset( $form_data['settings']['enable_quiz'] ) && '1' === $form_data['settings']['enable_quiz'] ? true : false;

		if ( true === $quiz_enabled ) {
			$action_links['export-quiz-report-csv'] = array(
				'url'   => wp_nonce_url( add_query_arg( array( 'action' => 'export_quiz_report_csv' ), $base_url ), 'export-quiz-report-csv' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M8.25 11.25v-9a.75.75 0 0 1 1.5 0v9a.75.75 0 0 1-1.5 0"/><path d="M1.5 14.25v-3a.75.75 0 0 1 1.5 0v3a.75.75 0 0 0 .75.75h10.5a.75.75 0 0 0 .75-.75v-3a.75.75 0 0 1 1.5 0v3a2.25 2.25 0 0 1-2.25 2.25H3.75a2.25 2.25 0 0 1-2.25-2.25"/><path d="M12.22 6.97a.75.75 0 1 1 1.06 1.06l-3.75 3.75a.75.75 0 0 1-1.06 0L4.72 8.03a.75.75 0 1 1 1.06-1.06L9 10.19z"/></svg>',
				'label' => esc_html__( 'Export Quiz Report (CSV)', 'everest-forms-pro' ),
			);
		}

		if ( ! empty( $entry->fields ) ) {
			$action_links['notifications'] = array(
				'url'   => '#',
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M16.097 4.617a.751.751 0 0 1 .806 1.266l-6.743 4.295-.026.015a2.25 2.25 0 0 1-2.286-.015l-6.75-4.295a.75.75 0 0 1 .804-1.266l6.728 4.28a.75.75 0 0 0 .75 0z"/><path d="M15.75 4.5a.75.75 0 0 0-.75-.75H3a.75.75 0 0 0-.75.75v9c0 .414.336.75.75.75h12a.75.75 0 0 0 .75-.75zm1.5 9A2.25 2.25 0 0 1 15 15.75H3A2.25 2.25 0 0 1 .75 13.5v-9A2.25 2.25 0 0 1 3 2.25h12a2.25 2.25 0 0 1 2.25 2.25z"/></svg>',
				'label' => esc_html__( 'Resend Notifications', 'everest-forms-pro' ),
				'class' => 'evf-resend-notification',
				'data'  => array(
					'entry-id' => absint( $entry->entry_id ),
					'form-id'  => absint( $form_data['id'] ),
					'nonce'    => wp_create_nonce( 'evf_resend_notification' ),
				),
			);
		}

		$action_links = apply_filters( 'everest_forms_entry_details_sidebar_actions_link', $action_links, $entry, $form_data );

		foreach ( $action_links as $slug => $action_link ) {
			$target      = ! empty( $action_link['target'] ) ? 'target="_blank" rel="noopener noreferrer"' : '';
			$extra_attrs = '';
			if ( ! empty( $action_link['class'] ) ) {
				$extra_attrs .= ' class="' . esc_attr( $action_link['class'] ) . '"';
			}
			if ( ! empty( $action_link['data'] ) && is_array( $action_link['data'] ) ) {
				foreach ( $action_link['data'] as $key => $value ) {
					$extra_attrs .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
				}
			}
			printf( '<p class="everest-forms-entry-%s">', esc_attr( $slug ) );
			printf( '<a href="%s" %s%s>', esc_url( $action_link['url'] ), $target, $extra_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_kses(
				$action_link['icon'],
				array(
					'svg'      => array(
						'xmlns'       => true,
						'viewBox'     => true,
						'class'       => true,
						'aria-hidden' => true,
						'role'        => true,
					),
					'path'     => array(
						'd'            => true,
						'fill-rule'    => true,
						'clip-rule'    => true,
						'fill'         => true,
						'stroke'       => true,
						'stroke-width' => true,
					),
					'g'        => array(
						'clip-path' => true,
						'fill'      => true,
					),
					'defs'     => array(),
					'clipPath' => array(
						'id' => true,
					),
				)
			);
			echo esc_html( $action_link['label'] );
			echo '</a>';
			echo '</p>';
		}

		// Print action
		?>
		<p>
			<a class="everest-forms-print-entry" href="javascript:;">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M12.75 10.625v-.375h-7.5v4.5h4.875a.75.75 0 0 1 0 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-4.5a1.5 1.5 0 0 1 1.5-1.5h7.5a1.5 1.5 0 0 1 1.5 1.5v.375a.75.75 0 0 1-1.5 0"/><path d="M15.97 11.22a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-1.5-1.5a.75.75 0 1 1 1.06-1.06l.97.97zm-.22-2.47v-1.5A.75.75 0 0 0 15 6.5H3a.75.75 0 0 0-.75.75V11a.75.75 0 0 0 .75.75h1.5a.75.75 0 0 1 0 1.5H3A2.25 2.25 0 0 1 .75 11V7.25A2.25 2.25 0 0 1 3 5h12a2.25 2.25 0 0 1 2.25 2.25v1.5a.75.75 0 0 1-1.5 0"/><path d="M12.75 5.5V2.25h-7.5V5.5a.75.75 0 0 1-1.5 0V2.25a1.5 1.5 0 0 1 1.5-1.5h7.5a1.5 1.5 0 0 1 1.5 1.5V5.5a.75.75 0 0 1-1.5 0"/></svg>
				<?php esc_html_e( 'Print', 'everest-forms-pro' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Entries admin actions.
	 */
	public function process_actions() {
		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['page'], $_GET['action'], $_GET['entry_id'] ) && 'evf-entries' === $_GET['page'] ) {
			$args     = array();
			$entry_id = absint( $_GET['entry_id'] );

			switch ( $_GET['action'] ) {
				case 'star':
				case 'unstar':
					check_admin_referer( 'starred-entry' );

					$starred = 'star' === $_GET['action'] ? 'starred' : 'unstarred';
					if ( EVF_Admin_Entries::update_status( $entry_id, sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) ) {
						$args[ $starred ] = 1;
					}
					break;
				case 'unread':
					check_admin_referer( 'unread-entry' );

					if ( EVF_Admin_Entries::update_status( $entry_id, sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) ) {
						$args['unread'] = 1;
					}
					break;

				case 'approved':
					check_admin_referer( 'approve-entry' );

					if ( EVF_Admin_Entries::update_status( $entry_id, sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) ) {
						$args['approved'] = 1;
					}
					break;

				case 'denied':
					check_admin_referer( 'deny-entry' );

					if ( EVF_Admin_Entries::update_status( $entry_id, sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) ) {
						$args['denied'] = 1;
					}
					break;

				case 'export_csv':
					check_admin_referer( 'export-entry' );

					$file_name = strtolower( get_the_title( $form_id . '-' . $entry_id ) );

					if ( $file_name ) {
						include_once EVF_ABSPATH . 'includes/export/class-evf-entry-csv-exporter.php';

						$exporter = new EVF_Entry_CSV_Exporter( $form_id, $entry_id );
						$exporter->set_filename( evf_get_entry_export_file_name( $file_name ) );
					}

					$exporter->export();
					break;

				case 'export_quiz_report_csv':
					check_admin_referer( 'export-quiz-report-csv' );

					$file_name = strtolower( get_the_title( $form_id . '-' . $entry_id ) );

					if ( $file_name ) {
						include_once EVF_ABSPATH . 'includes/export/class-evf-entry-csv-exporter.php';

						$exporter = new EVF_Entry_CSV_Exporter( $form_id, $entry_id );
						$exporter->set_filename( evf_get_entry_export_file_name( $file_name ) );
					}

					$exporter->export_quiz_report();
					break;

				case 'notification':
					check_admin_referer( 'resend-entry' );

					$entry = evf_get_entry( $entry_id );

					if ( ! empty( $entry->fields ) ) {
						$fields    = evf_decode( $entry->fields );
						$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );

						// Resend email notification.
						evf()->task->entry_email( $fields, array(), $form_data, $entry_id );
						$args['resend'] = 1;
					}
					break;
			}

			wp_safe_redirect(
				esc_url_raw(
					add_query_arg(
						array_merge(
							array(
								'form_id'    => $form_id,
								'view-entry' => $entry_id,
							),
							$args
						),
						admin_url( 'admin.php?page=evf-entries' )
					)
				)
			);
			exit();
		}
	}

	/**
	 * AJAX handler for resending entry email notifications.
	 */
	public function ajax_resend_notification() {
		check_ajax_referer( 'evf_resend_notification', 'nonce' );

		if ( ! current_user_can( 'manage_everest_forms' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'everest-forms-pro' ) ) );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$form_id  = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

		if ( ! $entry_id || ! $form_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid request.', 'everest-forms-pro' ) ) );
		}

		$entry = evf_get_entry( $entry_id );

		if ( empty( $entry->fields ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Entry not found.', 'everest-forms-pro' ) ) );
		}

		$fields    = evf_decode( $entry->fields );
		$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );

		evf()->task->entry_email( $fields, array(), $form_data, $entry_id );

		wp_send_json_success( array( 'message' => esc_html__( 'Notifications were resent successfully!', 'everest-forms-pro' ) ) );
	}

	/**
	 * Entry action notices.
	 */
	public function entry_notices() {
		$message = '';

		if ( isset( $_GET['starred'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$message = esc_html__( 'This entry has been starred.', 'everest-forms-pro' );
		} elseif ( isset( $_GET['unstarred'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$message = esc_html__( 'This entry has been unstarred.', 'everest-forms-pro' );
		} elseif ( isset( $_GET['unread'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$message = esc_html__( 'This entry has been marked unread.', 'everest-forms-pro' );
		} elseif ( isset( $_GET['resend'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$message = esc_html__( 'Notifications were resent!', 'everest-forms-pro' );
		} elseif ( isset( $_GET['approved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$message = esc_html__( 'This entry has been approved.', 'everest-forms-pro' );
		} elseif ( isset( $_GET['denied'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$message = esc_html__( 'This entry has been denied.', 'everest-forms-pro' );
		}

		if ( $message ) {
			$redirect = add_query_arg(
				array(
					'evf_toast'      => rawurlencode( base64_encode( $message ) ),
					'evf_toast_type' => 'success',
				),
				remove_query_arg( array( 'starred', 'unstarred', 'unread', 'resend', 'approved', 'denied' ) )
			);

			wp_safe_redirect( $redirect );
			exit;
		}
	}



	/**
	 * Add unread entry total count to menu.
	 */
	public function unread_menu_count() {
		global $submenu, $wpdb;
		$cache_group = 'evf_entries';
		$cache_key   = EVF_Cache_Helper::get_cache_prefix( $cache_group ) . 'unread_count';
		$count       = wp_cache_get( $cache_key, $cache_group );

		if ( false === $count ) {
			$allowed_form_ids = evf()->form->get(
				'',
				array(
					'fields' => 'ids',
					'cap'    => 'everest_forms_view_form_entries',
				)
			);

			if ( empty( $allowed_form_ids ) ) {
				$count = 0;
			} else {
				$id_list = implode( ',', array_map( 'intval', (array) $allowed_form_ids ) );

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$count = (int) $wpdb->get_var(
					"SELECT COUNT(*)
                 FROM {$wpdb->prefix}evf_entries
                 WHERE viewed  = 0
                   AND status NOT IN ('trash', 'spam', 'draft')
                   AND form_id IN ( {$id_list} )"
				);
			}

			wp_cache_set( $cache_key, $count, $cache_group );
		}

		foreach ( $submenu['everest-forms'] as $key => $menu_item ) {
			if ( 0 === strpos( $menu_item[0], _x( 'Entries', 'Admin menu name', 'everest-forms-pro' ) ) ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$submenu['everest-forms'][ $key ][0] .= ' <span class="awaiting-mod count-' . absint( $count ) . '"><span class="unread-count">' . number_format_i18n( $count ) . '</span></span>';
				break;
			}
		}
	}

	public function invalidate_entries_cache() {
		EVF_Cache_Helper::incr_cache_prefix( 'evf_entries' );
	}

	/**
	 * Edit Entry Form actions.
	 *
	 * @since 1.3.5
	 *
	 * @param bool $is_allowed True if allowed to execute. False if not.
	 */
	public function entries_list_actions( $is_allowed ) {
		if ( isset( $_GET['edit-entry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_action( 'everest_forms_entries_list_actions_execute', array( $this, 'display_edit_page' ) );
			add_action( 'everest_forms_entry_details_edit_content', array( $this, 'display_edit_form' ), 10, 2 );
			return true;
		}

		return $is_allowed;
	}

	/**
	 * Edit Entry page.
	 *
	 * @since 1.3.5
	 */
	public function display_edit_page() {
		include 'views/html-admin-page-entry-edit.php';
	}

	/**
	 * Edit entry form metabox.
	 *
	 * @since 1.3.5
	 *
	 * @param object $entry     Submitted entry values.
	 * @param array  $form_data Form data and settings.
	 */
	public function display_edit_form( $entry, $form_data ) {
		$hide_empty   = isset( $_COOKIE['everest_forms_entry_hide_empty'] ) && 'true' === $_COOKIE['everest_forms_entry_hide_empty'];
		$entry_fields = apply_filters( 'everest_forms_entry_single_data', evf_decode( $entry->fields ), $entry, $form_data );
		?>
		<!-- Edit Entry Form metabox -->
		<div id="everest-forms-entry-fields" class="postbox">
			<div class="postbox-header">
				<div class="hndle">
					<?php do_action( 'everest_forms_before_edit_entry_details_hndle', $entry ); ?>
					<span>
		<?php
		/* translators: %s: Entry ID */
		printf( esc_html__( '%1$s: Entry #%2$s', 'everest-forms-pro' ), esc_html( _draft_or_post_title( $form_data['id'] ) ), absint( $entry->entry_id ) );
		?>
					</span>
					<?php do_action( 'everest_forms_after_edit_entry_details_hndle', $entry ); ?>
					<a href="#"
					   class="everest-forms-empty-field-toggle password_preview dashicons <?php echo $hide_empty ? 'dashicons-hidden' : 'dashicons-visibility'; ?>"
					   title="<?php echo $hide_empty ? esc_attr__( 'Show password', 'everest-forms-pro' ) : esc_attr__( 'Hide password', 'everest-forms-pro' ); ?>">

						<?php
						echo $hide_empty
							? esc_html__( 'Show Empty Fields', 'everest-forms-pro' )
							: esc_html__( 'Hide Empty Fields', 'everest-forms-pro' );
						?>
					</a>
				</div>
			</div>
			<div class="inside evf-field-container">
				<input type="hidden" name="everest_forms[form_id]" value="<?php echo esc_attr( $form_data['id'] ); ?>">
				<input type="hidden" name="everest_forms[entry_id]" value="<?php echo esc_attr( $entry->entry_id ); ?>">
				<table class="fixed evf-frontend-row wp-list-table widefat striped posts">
					<tbody class="evf-frontend-grid evf-grid-1">
					<?php
					if ( ! current_user_can( 'everest_forms_edit_entry', $entry->entry_id ) ) {
						echo '<p class="no-access">' . esc_html__( 'You do not have permission to edit this entry.', 'everest-forms-pro' ) . '</p>';
					} elseif ( empty( $entry_fields ) ) {
						// Whoops, no fields! This shouldn't happen under normal use cases.
						echo '<p class="no-fields">' . esc_html__( 'This entry does not have any fields', 'everest-forms-pro' ) . '</p>';
					} else {
						// Display the fields and their editable values.
						$this->display_edit_form_fields( $entry, $entry_fields, $form_data, $hide_empty );
					}
					?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Edit entry form fields.
	 *
	 * @since 1.3.5
	 *
	 * @param object $entry        Entry data.
	 * @param array  $entry_fields Entry fields data.
	 * @param array  $form_data    Form data and settings.
	 * @param bool   $hide_empty   Flag to hide empty fields.
	 */
	private function display_edit_form_fields( $entry, $entry_fields, $form_data, $hide_empty ) {
		// Display message if form fields doesn't exists.
		if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
			echo '<p class="everest-forms-entry-field-value">';
			if ( current_user_can( 'manage_everest_forms' ) ) {
				printf(
					wp_kses( /* translators: %s - form edit URL. */
						__( 'Seems like you don\'t have any fields in this form. <a href="%s">Add form fields!</a>', 'everest-forms-pro' ),
						array(
							'a' => array(
								'href' => array(),
							),
						)
					),
					esc_url( admin_url( 'admin.php?page=evf-builder&amp;tab=fields&amp;form_id=' . $entry->form_id ) )
				);
			} else {
				esc_html_e( 'Seems like you don\'t have any fields in this form.', 'everest-forms-pro' );
			}
			echo '</p>';
			return;
		}

		foreach ( $form_data['form_fields'] as $field_id => $field ) {
			$repeater_fields = array_key_exists( 'repeater-fields', $field ) ? $field['repeater-fields'] : 'no';
			if ( 'no' === $repeater_fields || 'repeater-fields' === $field['type'] ) {
				$this->display_edit_form_field( $field_id, $field, $entry_fields, $form_data, $hide_empty );
			}
		}
	}

	/**
	 * Edit entry form field.
	 *
	 * @since 1.3.5
	 *
	 * @param int   $field_id     Field id.
	 * @param array $field        Field data.
	 * @param array $entry_fields Entry fields data.
	 * @param array $form_data    Form data and settings.
	 * @param bool  $hide_empty   Flag to hide empty fields.
	 */
	private function display_edit_form_field( $field_id, $field, $entry_fields, $form_data, $hide_empty ) {
		// Check if the field can be displayed.
		if ( ! empty( $field['type'] ) && in_array( $field['type'], (array) apply_filters( 'everest_forms_entries_edit_fields_hidden', array( 'title', 'html', 'reset' ) ), true ) ) {
			return;
		}

		$entry_field = ! empty( $entry_fields[ $field_id ] ) ? $entry_fields[ $field_id ] : $this->get_empty_entry_field_properties( $field );
		$field_value = ! empty( $entry_field['value'] ) ? $entry_field['value'] : '';
		$field_type  = ! empty( $field_value['type'] ) ? $field_value['type'] : '';
		$field_value = ( 'color' === $field_type ) ? $field_value['value'] : $field_value;

		if ( 'color' === $field_type ) {
			$field['default'] = $field_value;
		}

		if ( ! is_array( $field_value ) ) {
			$field_value = apply_filters( 'everest_forms_html_field_value', $field_value, $entry_field, $form_data, 'entry-single' );
		}

		$field_class = ( is_string( $field_value ) && ( '(empty)' === wp_strip_all_tags( $field_value ) || '' === $field_value ) ) ? ' empty' : '';
		$field_style = $hide_empty && empty( $entry_field['value'] ) ? 'display:none;' : '';

		echo '<tr class="everest-forms-edit-entry-field field-name' . esc_attr( $field_class ) . '" style="' . esc_attr( $field_style ) . '"><th>';

		// Field label.
		printf(
			'<strong>%s</strong> %s',
			/* translators: %d - field ID. */
			! empty( $field['label'] ) ? esc_html( wp_strip_all_tags( $field['label'] ) ) : sprintf( esc_html__( 'Field ID #%d', 'everest-forms-pro' ), (int) $field_id ),
			! empty( $field['required'] ) ? apply_filters( 'everest_forms_field_required_label', '<abbr class="required" title="' . esc_attr__( 'Required', 'everest-forms-pro' ) . '">*</abbr>' ) : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		echo '</th></tr>';

		// Add properties to the field.
		$field['properties'] = EVF_Shortcode_Form::get_field_properties( $field, $form_data );

		// Field value.
		echo '<tr class="everest-forms-edit-entry-field field-value' . esc_attr( $field_class ) . '" style="' . esc_attr( $field_style ) . '"><td>';

		// Display entry editable and non-editable form field.
		if ( apply_filters( 'everest_forms_admin_entries_edit_field_output_editable', Helper::is_field_entries_editable( $field['type'] ), $field ) ) {
			EVF_Shortcode_Form::wrapper_start( $field, $form_data );
			do_action( "everest_forms_display_edit_form_field_{$field['type']}", $entry_field, $field, $form_data );
		} else {
			// ✅ FIX START
			if ( isset( $field_value['label'] ) ) {
				if ( is_array( $field_value['label'] ) ) {
					// Escape each label value individually before joining.
					$field_value = implode( '<br />', array_map( 'esc_html', $field_value['label'] ) );
				} else {
					// Escape single label value.
					$field_value = esc_html( $field_value['label'] );
				}
			}

			if ( is_string( $field_value ) && ( '(empty)' === wp_strip_all_tags( $field_value ) || '' === $field_value ) ) {
				echo esc_html__( 'Empty', 'everest-forms-pro' );
			} elseif ( is_string( $field_value ) && $field_value !== wp_strip_all_tags( $field_value ) ) {
				echo wp_kses_post( $field_value );
			} else {
				if ( 'payment-checkbox' === $field['type'] ) {
					$allowed_tags = array(
						'br'   => array(),
						'span' => array(),
						'p'    => array(),
					);

					$field_value = nl2br( $field_value );
					echo wp_kses( $field_value, $allowed_tags );
				} else {
					echo nl2br( esc_html( $field_value ) );
				}
			}
		}

		echo '</td></tr>';
	}

	/**
	 * Get empty entry field properties.
	 *
	 * @since 1.3.5
	 *
	 * @param array $properties Field properties.
	 *
	 * @return array Empty entry field properties.
	 */
	public function get_empty_entry_field_properties( $properties ) {
		return array(
			'name'      => ! empty( $properties['label'] ) ? $properties['label'] : '',
			'value'     => '',
			'value_raw' => '',
			'id'        => ! empty( $properties['id'] ) ? $properties['id'] : '',
			'type'      => ! empty( $properties['type'] ) ? $properties['type'] : '',
		);
	}

	/**
	 * Entry table actions.
	 *
	 * @since 1.3.5
	 *
	 * @param  array  $actions Action links.
	 * @param  object $entry   Entry object.
	 * @return array Modified Actions array.
	 */
	public function entry_table_actions( $actions, $entry ) {

		if ( $entry->viewed ) {
			$read_unread_action = array(
				'mark_unread' => '<a href="#" class="evf-entry-read-unread unread" data-id="' . esc_attr( $entry->entry_id ) . '">' . esc_html__( 'Mark as Unread', 'everest-forms' ) . '</a>',
			);
		} else {
			$read_unread_action = array(
				'mark_read' => '<a href="#" class="evf-entry-read-unread read" data-id="' . esc_attr( $entry->entry_id ) . '">' . esc_html__( 'Mark as Read', 'everest-forms' ) . '</a>',
			);
		}
		array_splice( $actions, 1, 0, $read_unread_action );
		if ( 'trash' !== $entry->status && apply_filters( 'everest_forms_entry_view_enable', false ) ) {
			$edit_action = array(
				'edit' => '<a href="' . esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $entry->form_id . '&amp;edit-entry=' . $entry->entry_id ) ) . '">' . esc_html__( 'Edit', 'everest-forms-pro' ) . '</a>',
			);

			// Check edit permission and add edit action to the existing ones.
			if ( current_user_can( 'everest_forms_edit_entry', $entry->entry_id ) ) {
				array_splice( $actions, -1, 0, $edit_action );
			}
		}

		return $actions;
	}

	/**
	 * Entry Details action button on the single entry view/edit page.
	 *
	 * @since 1.3.5
	 *
	 * @param object $entry    Entry object.
	 * @param array  $form_data Forms data and settings.
	 */
	public function display_action_button( $entry, $form_data ) {
		if ( ! isset( $form_data['id'], $entry->entry_id ) || ! current_user_can( 'everest_forms_edit_entry', $entry->entry_id ) ) {
			return;
		}

		if ( isset( $_GET['page'], $_GET['view-entry'] ) && 'evf-entries' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$allowed_html = array(
				'a'    => array(
					'href'  => true,
					'class' => true,
				),
				'span' => array(
					'class'       => true,
					'aria-hidden' => true,
				),
				'svg'  => array(
					'xmlns'   => true,
					'viewBox' => true,
				),
				'path' => array(
					'd' => true,
				),
			);

			echo wp_kses(
				sprintf(
					'<a href="%s" class="everest-forms-edit-entry-update">
					<span class="evf-edit-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
							<path d="M11.54 14.667H3.193a1.86 1.86 0 0 1-1.86-1.86V4.46a1.86 1.86 0 0 1 1.86-1.86h4.174a.667.667 0 1 1 0 1.333H3.193a.527.527 0 0 0-.526.527v8.347a.527.527 0 0 0 .526.526h8.347a.527.527 0 0 0 .527-.526v-4.14a.666.666 0 1 1 1.333 0v4.173a1.86 1.86 0 0 1-1.86 1.827m-5.8-3.6 2.387-.594a.8.8 0 0 0 .306-.173L14.1 4.667a1.932 1.932 0 0 0-1.357-3.334 1.93 1.93 0 0 0-1.376.6L5.707 7.6a.7.7 0 0 0-.18.307l-.594 2.353a.67.67 0 0 0 .391.777.67.67 0 0 0 .416.03m6.573-8.227a.6.6 0 0 1 .847.847L7.62 9.22l-1.127.287.287-1.127z"/>
						</svg>
					</span>
					<span class="evf-edit-text">%s</span>
				</a>',
					esc_url(
						admin_url(
							'admin.php?page=evf-entries&form_id=' . $entry->form_id . '&edit-entry=' . $entry->entry_id
						)
					),
					esc_html__( 'Edit', 'everest-forms-pro' )
				),
				$allowed_html
			);
		}

		// Only show Save/Cancel buttons when NOT on the view-entry page (i.e., on edit-entry page)
		if ( ! isset( $_GET['view-entry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			printf(
				'<div id="publishing-action">
				<a href="%2$s" class="button button-secondary button-large everest-forms-cancel">%3$s</a>
				<button type="submit" class="button button-primary button-large everest-forms-submit-button" id="everest-forms-edit-entry-update">%1$s</button>
			</div>',
				esc_html__( 'Save', 'everest-forms-pro' ), // %1$s
				esc_url(
					admin_url(
						'admin.php?page=evf-entries&amp;form_id=' . $entry->form_id . '&amp;view-entry=' . $entry->entry_id
					)
				), // %2$s
				esc_html__( 'Cancel', 'everest-forms-pro' ) // %3$s
			);
		}
	}


	/**
	 * Adds entry views to the Everest Forms entries table.
	 *
	 * @param array $status_links Existing status links.
	 * @param array $num_entries  Entry counts.
	 * @param array $form_data    Form data array.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public function display_entries_table_views( $status_links, $num_entries, $form_data ) {

		$form_id = 0;
		if ( is_array( $form_data ) && isset( $form_data['id'] ) ) {
			$form_id = absint( $form_data['id'] );
		}

		$base_url = admin_url( 'admin.php?page=evf-entries' );

		$unread_entries = isset( $num_entries['unread'] ) ? (int) $num_entries['unread'] : 0;
		$read_entries   = isset( $num_entries['read'] ) ? (int) $num_entries['read'] : 0;

		$current_status = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : '';

		$unread_url   = add_query_arg( 'status', 'unread', $base_url );
		$unread_class = ( 'unread' === $current_status ) ? ' class="current"' : '';

		$read_url   = add_query_arg( 'status', 'read', $base_url );
		$read_class = ( 'read' === $current_status ) ? ' class="current"' : '';

		$unread_link = "<a href='" . esc_url( $unread_url ) . "'$unread_class>" .
			sprintf(
				_nx(
					'Unread <span class=\"count\">(%s)</span>',
					'Unread <span class=\"count\">(%s)</span>',
					$unread_entries,
					'entries',
					'everest-forms'
				),
				number_format_i18n( $unread_entries )
			) . '</a>';

		$read_link = "<a href='" . esc_url( $read_url ) . "'$read_class>" .
			sprintf(
				_nx(
					'Read <span class=\"count\">(%s)</span>',
					'Read <span class=\"count\">(%s)</span>',
					$read_entries,
					'entries',
					'everest-forms'
				),
				number_format_i18n( $read_entries )
			) . '</a>';

		$new_links = array();

		foreach ( $status_links as $key => $link ) {

			$new_links[ $key ] = $link;

			if ( 'all' === $key ) {
				$new_links['unread'] = $unread_link;
				$new_links['read']   = $read_link;
			}
		}

		return $new_links;
	}


	/**
	 * Delete Attachment after removing Entry.
	 *
	 * @param int $entry_id Entry ID for which file should be removed.
	 */
	public function delete_entry_files( $entry_id ) {
		$get_entry = evf_get_entry( $entry_id, 'meta' );
		if ( empty( $get_entry->meta ) ) {
			return;
		}

		// Get form configuration
		$form_id     = $get_entry->form_id;
		$form        = evf()->form->get( $form_id, array( 'content_only' => true ) );
		$form_fields = isset( $form['form_fields'] ) ? $form['form_fields'] : array();

		// Build field type lookup by meta-key
		$field_types = array();
		foreach ( $form_fields as $field_id => $field_config ) {
			if ( isset( $field_config['meta-key'] ) && ! empty( $field_config['meta-key'] ) ) {
				$field_types[ $field_config['meta-key'] ] = $field_config['type'];
			}
		}

		$uploads           = wp_upload_dir();
		$base_dir          = realpath( $uploads['basedir'] );
		$everest_forms_dir = $base_dir ? realpath( $base_dir . '/everest_forms_uploads' ) : false;

		foreach ( $get_entry->meta as $meta_key => $meta_value ) {
			if ( empty( $meta_value ) ) {
				continue;
			}

			$field_type = isset( $field_types[ $meta_key ] ) ? $field_types[ $meta_key ] : '';

			if ( preg_match( '/signature_/', $meta_key ) || $field_type === 'signature' ) {
				$this->safe_delete_file( $meta_value, $base_dir );
			} elseif ( 'file-upload' === $field_type || 'image-upload' === $field_type ) {
				$files = explode( "\n", $meta_value );
				foreach ( $files as $file ) {
					$path_from_url = wp_parse_url( $file, PHP_URL_PATH );
					if ( ! $path_from_url ) {
						continue;
					}

					$uploaded_file = $uploads['basedir'] . preg_replace(
							'/.*uploads/',
							'/everest_forms_uploads',
							$path_from_url
						);

					$this->safe_delete_file( $uploaded_file, $base_dir );
				}
			}
		}
	}

	/**
	 * Securely delete a file with path validation
	 *
	 * @param string $path File path to delete
	 * @param string $allowed_base Base directory path (must be realpath result)
	 */
	private function safe_delete_file( $path, $allowed_base ) {
		if ( ! $allowed_base || empty( $path ) ) {
			return;
		}
		$normalized_path = wp_normalize_path( $path );
		$resolved_path   = realpath( $normalized_path );
		// Validate path is within allowed directory
		if ( $resolved_path && strpos( $resolved_path, $allowed_base ) === 0 ) {
			if ( is_file( $resolved_path ) ) {
				wp_delete_file( $resolved_path );
			}
		}
	}

	/**
	 * Remove Files Attached to the Entry of the Form.
	 *
	 * @param int $form_id Form ID to get required form data and remove files.
	 */
	public function delete_entry_files_before_form_delete( $form_id ) {
		$entries = evf_get_entries_ids( $form_id );
		if ( ! empty( $entries ) ) {
			foreach ( $entries as $entry_id ) {
				$this->delete_entry_files( $entry_id );
			}
		}
	}

	/**
	 * Payment Details within Entry.
	 *
	 * @param object $entry     Entry Data.
	 * @param array  $form_data Form Data Object.
	 */
	public function payment_details_inside_entry( $entry, $form_data ) {
		$entry = evf_get_entry( $entry->entry_id );
		if ( empty( $entry->meta['type'] ) || 'payment' !== $entry->meta['type'] ) {
			return;
		}
		$entry_meta              = json_decode( $entry->meta['meta'], true );
		$status                  = ! empty( $entry->status ) ? ucwords( sanitize_text_field( $entry->status ) ) : esc_html__( 'Unknown', 'everest-forms-pro' );
		$currency                = ! empty( $entry_meta['payment_currency'] ) ? $entry_meta['payment_currency'] : get_option( 'everest_forms_currency', 'USD' );
		$total                   = isset( $entry_meta['payment_total'] ) ? evf_format_amount( evf_sanitize_amount( $entry_meta['payment_total'], $currency ), true, $currency ) : '-';
		$discount                = isset( $entry_meta['payment_discount'] ) ? evf_format_amount( evf_sanitize_amount( $entry_meta['payment_discount'], $currency ), true, $currency ) : '';
		$note                    = ! empty( $entry_meta['payment_note'] ) ? esc_html( $entry_meta['payment_note'] ) : '';
		$gateway                 = esc_html( apply_filters( 'evf_entry_details_payment_gateway', '-', $entry_meta, $entry, $form_data ) );
		$transaction             = esc_html( apply_filters( 'evf_entry_details_payment_transaction', '-', $entry_meta, $entry, $form_data ) );
		$subscription            = esc_html( apply_filters( 'evf_entry_details_payment_subscription', '-', $entry_meta, $entry, $form_data ) );
		$customer_id             = esc_html( apply_filters( 'evf_entry_details_payment_customer_id', '-', $entry_meta, $entry, $form_data ) );
		$subscription_interval   = esc_html( apply_filters( 'evf_entry_details_payment_subscription_interval', '-', $entry_meta, $entry, $form_data ) );
		$subscription_start_date = esc_html( apply_filters( 'evf_entry_details_payment_subscription_start_date', '-', $entry_meta, $entry, $form_data ) );
		$mode                    = ! empty( $entry_meta['payment_mode'] ) && 'test' === $entry_meta['payment_mode'] ? 'test' : 'production';

		switch ( $entry_meta['payment_gateway'] ) {
			case 'paypal_standard':
				$gateway = esc_html__( 'PayPal Standard', 'everest-forms-pro' );
				if ( ! empty( $entry_meta['payment_transaction'] ) ) {
					$type        = 'production' === $mode ? '' : 'sandbox.';
					$transaction = sprintf( '<a href="https://www.%spaypal.com/webscr?cmd=_history-details-from-hub&id=%s" target="_blank">%s</a>', $type, $entry_meta['payment_transaction'], $entry_meta['payment_transaction'] );
				}
				break;
			case 'stripe':
				$gateway = esc_html__( 'Stripe', 'everest-forms-pro' );
				if ( ! empty( $entry_meta['payment_transaction'] ) ) {
					$transaction = sprintf( '<a href="https://dashboard.stripe.com/payments/%s" target="_blank" rel="noopener noreferrer">%s</a>', $entry_meta['payment_transaction'], $entry_meta['payment_transaction'] );
				}
				if ( ! empty( $entry_meta['payment_customer'] ) ) {
					$customer = sprintf( '<a href="https://dashboard.stripe.com/customers/%s" target="_blank" rel="noopener noreferrer">%s</a>', $entry_meta['payment_customer'], $entry_meta['payment_customer'] );
				}
				if ( ! empty( $entry_meta['payment_interval'] ) ) {
					$total .= ' <span style="font-weight:400; color:#999; display:inline-block;margin-left:4px;"><span class="dashicons dashicons-controls-repeat"></span> ' . $entry_meta['payment_interval'] . '</span>';
				}
				break;
			case 'razorpay':
				$gateway = esc_html__( 'Razorpay', 'everest-forms-pro' );
				if ( ! empty( $entry_meta['payment_transaction'] ) ) {
					$transaction = sprintf( '<a href="https://dashboard.razorpay.com/app/payments/%s" target="_blank" rel="noopener noreferrer">%s</a>', $entry_meta['payment_transaction'], $entry_meta['payment_transaction'] );
				}
				break;
			case 'authorize_net':
				$type                    = 'test' === $mode ? 'sandbox' : 'account';
				$gateway                 = sprintf( '<a href="https://%s.authorize.net/" target="_blank">%s</a>', $type, esc_html__( 'Authorize.Net', 'everest-forms-pro' ) );
				$transaction             = isset( $entry_meta['payment_transaction'] ) ? esc_html( $entry_meta['payment_transaction'] ) : esc_html( '' );
				$subscription            = isset( $entry_meta['payment_subscription'] ) ? esc_html( $entry_meta['payment_subscription'] ) : esc_html( '' );
				$customer_id             = isset( $entry_meta['payment_customer_id'] ) ? esc_html( $entry_meta['payment_customer_id'] ) : esc_html( '' );
				$subscription_interval   = isset( $entry_meta['payment_subscription_interval'] ) ? esc_html( $entry_meta['payment_subscription_interval'] ) : esc_html( '' );
				$subscription_start_date = isset( $entry_meta['payment_subscription_start_date'] ) && isset( $entry_meta['payment_subscription_start_date']['date'] ) ? esc_html( gmdate( 'Y-m-d', strtotime( $entry_meta['payment_subscription_start_date']['date'] ) ) ) : esc_html( '' );
				break;

			case 'mollie':
				$gateway             = esc_html__( 'Mollie', 'everest-forms-pro' );
				$payment_details_url = isset( $entry_meta['payment_details_url'] ) ? $entry_meta['payment_details_url'] : '';
				$payment_transaction = isset( $entry_meta['payment_transaction'] ) ? $entry_meta['payment_transaction'] : '';
				if ( ! empty( $entry_meta['payment_transaction'] ) ) {
					$transaction = sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', $payment_details_url, $payment_transaction );
				}
				break;

			case 'square':
				$gateway = esc_html__( 'Square', 'everest-forms-pro' );
				if ( ! empty( $entry_meta['receipt_url'] ) ) {
					$transaction = sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', $entry_meta['receipt_url'], $entry_meta['transaction_id'] );
				}
				break;

		}

		?>
		<!-- Entry Payment details metabox -->
		<div id="everest-forms-entry-payment" class="stuffbox">
			<h2><?php esc_html_e( 'Payment Details', 'everest-forms-pro' ); ?></h2>
			<div class="inside">
				<div class="everest-forms-entry-payment-meta">
					<p class="everest-forms-entry-payment-status">
						<?php
						printf(
						/* translators: %s - entry payment status. */
							esc_html__( 'Status: %s', 'everest-forms-pro' ),
							'<strong>' . esc_html( $status ) . '</strong>'
						);
						?>
						<?php if ( ! empty( $discount ) ) { ?>
					<p class="everest-forms-entry-payment-discount">
						<?php
						printf(
						/* translators: %s - entry payment discount. */
							esc_html__( 'Discount: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $discount ) . '</strong>'
						);
						?>
					</p>
					<?php } ?>
					<p class="everest-forms-entry-payment-total">
						<?php
						printf(
						/* translators: %s - entry payment total. */
							esc_html__( 'Total: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $total ) . '</strong>'
						);
						?>
					</p>
					<p class="everest-forms-entry-payment-gateway">
						<?php
						if ( 'authorize_net' === $entry_meta['payment_gateway'] ) {
							printf(
							/* translators: %1$s - entry payment gateway. %2$s - pyament mode.*/
								esc_html__( 'Gateway: %1$s (%2$s)', 'everest-forms-pro' ),
								'<strong>' . wp_kses_post( $gateway ) . '</strong>',
								( 'test' === $mode ? 'Test' : 'Live' )
							);
						} else {
							printf(
							/* translators: %s - entry payment gateway. */
								esc_html__( 'Gateway: %s', 'everest-forms-pro' ),
								'<strong>' . esc_html( $gateway ) . '</strong>'
							);
							if ( 'test' === $mode ) {
								printf( ' (%s)', esc_html( _x( 'Test', 'Gateway mode', 'everest-forms-pro' ) ) );
							}
						}
						?>
					</p>
					<p class="everest-forms-entry-payment-gateway">
						<?php
						printf(
						/* translators: %s - entry payment transaction. */
							esc_html__( 'Transaction ID: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $transaction ) . '</strong>'
						);
						?>
					</p>
					<p class="everest-forms-entry-payment-transaction">
						<?php
						if ( 'authorize_net' === $entry_meta['payment_gateway'] && ! empty( $subscription ) ) :
						printf(
						/* translators: %s - entry payment subscritpion. */
							esc_html__( 'Subscription ID: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $subscription ) . '</strong>'
						);
						?>
					</p>
					<p class="everest-forms-entry-payment-transaction">
						<?php
						printf(
						/* translators: %s - customer ID. */
							esc_html__( 'Customer Profile ID: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $customer_id ) . '</strong>'
						);
						?>
					</p>
					<p class="everest-forms-entry-payment-transaction">
						<?php

						printf(
						/* translators: %s - subscription interval. */
							esc_html__( 'Subscription Interval: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $subscription_interval ) . '</strong>'
						);
						?>
					</p>
					<p class="everest-forms-entry-payment-transaction">
						<?php

						printf(
						/* translators: %s - subscription interval. */
							esc_html__( 'Subscription Start Date: %s', 'everest-forms-pro' ),
							'<strong>' . wp_kses_post( $subscription_start_date ) . '</strong>'
						);
						?>
					</p>
				<?php endif ?>
					<?php if ( ! empty( $customer ) ) : ?>
						<p class="everest-forms-entry-payment-customer">
							<?php
							printf(
							/* translators: %s - entry payment customer. */
								esc_html__( 'Customer ID: %s', 'everest-forms-pro' ),
								'<strong>' . $customer . '</strong>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Hide Payment entry field.
	 *
	 * @param  array $entry_fields Entry fields.
	 * @return array               List for fields.
	 */
	public function entry_hidden_fields( $entry_fields ) {
		return array_merge( $entry_fields, array( 'status', 'type', 'meta' ) );
	}

	/**
	 * Single Payment validation.
	 *
	 * @param string $field_id     Field ID.
	 * @param string $field_submit Field's submitted value.
	 * @param array  $form_data    Form data object.
	 * @param array  $field_type   Type of the field.
	 */
	public function payment_single_validation( $field_id, $field_submit, $form_data, $field_type ) {
		if ( isset( $form_data['form_fields'][ $field_id ]['required'] ) && empty( $field_submit ) && '0' !== $field_submit && 'user' === $form_data['form_fields'][ $field_id ]['item_type'] ) {
			$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please enter the desire amount.', 'everest-forms-pro' ) );
		}

		if ( isset( $validation_text ) ) {
			EVF()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Multiple Payment validation.
	 *
	 * @param string $field_id     Field ID.
	 * @param string $field_submit Field submit flag.
	 * @param array  $form_data    Form data object.
	 * @param array  $field_type   Type of the field.
	 */
	public function payment_multiple_validation( $field_id, $field_submit, $form_data, $field_type ) {
		if ( isset( $form_data['fields'][ $field_id ]['required'] ) && empty( $field_submit ) && '0' !== $field_submit ) {
			$validation_text = get_option( 'evf_' . $field_type . '_validation', __( 'Please choose one Item.', 'everest-forms-pro' ) );
		}
		if ( isset( $validation_text ) ) {
			EVF()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'everest_forms_type_validation', $validation_text );
			update_option( 'evf_validation_error', 'yes' );
		}
	}

	/**
	 * Output Export Entries HTML.
	 *
	 * @since 1.5.1
	 */
	public function html_admin_page_export_entries_html() {
		?>
		<div class="evf-tools-export-entries">
			<h3><?php esc_html_e( 'Export Entries', 'everest-forms-pro' ); ?></h3>
			<p><?php esc_html_e( 'To export entries, choose a form and then the fields you want to include. You can also use date filters to customize the list of entries you want to obtain even more.', 'everest-forms-pro' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=evf-tools&tab=export' ) ); ?>" id="evf-tools-entries-export">
				<?php
				$forms = evf_get_all_forms( false );
				if ( ! empty( $forms ) ) {
					echo '<select class="evf-enhanced-select evf-tools-export-entries-select" style="min-width: 350px;" name="form_id" data-placeholder="' . esc_attr__( 'Select Form', 'everest-forms-pro' ) . '"><option value="">' . esc_html__( 'Select a form', 'everest-forms-pro' ) . '</option>';
					foreach ( $forms as $id => $form ) {
						echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $form ) . '</option>';
					}
					echo '</select>';
				} else {
					echo '<p>' . esc_html__( 'You need to create a form before you can use form export.', 'everest-forms-pro' ) . '</p>';
				}
				?>
				<div class="evf-tools-export-entries-options everest-forms-hidden"></div>
			</form>
		</div>
		<?php
	}

	/**
	 * Output Export Entries Options HTML.
	 *
	 * @since 1.5.1
	 */
	public static function display_entries_export_options() {

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0; // phpcs:ignore

		// Return if form id is not set.
		if ( empty( $form_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid Form ID.', 'everest-forms-pro' ),
				)
			);
		}

		$get_post     = get_post( $form_id );
		$post_content = json_decode( $get_post->post_content, true );
		$form_fields  = isset( $post_content['form_fields'] ) ? $post_content['form_fields'] : array();
		$output       = '';
		ob_start();
		?>
		<section class="evf-tools-entries-export-options-fields">
			<h4><?php esc_html_e( 'Form Fields', 'everest-forms-pro' ); ?></h4>
			<div class="evf-tools-entries-export-options-fields-select-item">
				<?php
				if ( ! empty( $form_fields ) ) {
					$i = 0;
					echo '<select class="evf-select2-multiple evf-tools-entries-export-options-fields-select" name="fields[]" data-placeholder="' . esc_attr__( 'Select Field(s)', 'everest-forms-pro' ) . '" data-selected_msg="' . esc_attr__( 'Selected %qty% Field(s)', 'everest-forms-pro' ) . '" style="min-width: 350px;" multiple>';
					foreach ( $form_fields as $id => $field ) {
						if ( isset( $field['id'] ) && ! empty( $field['id'] ) ) {
							$name = ! empty( $field['label'] ) ?
								trim( wp_strip_all_tags( $field['label'] ) ) :
								sprintf( /* translators: %d - Field ID. */
									esc_html__( 'Field #%d', 'everest-forms-pro' ),
									(int) $i
								);
							echo '<option value="' . esc_attr( $field['id'] ) . '">' . esc_html( $name ) . '</option>';
							++$i;
						}
					}
					echo '</select>';
				} else {
					?>
					<p class="evf-empty-form-fields-message"><?php printf( esc_html__( 'The Form does not have any fields for export.', 'everest-forms-pro' ) ); ?></p>
					<?php
				}
				?>
			</div>
		</section>
		<?php
		if ( ! empty( $form_fields ) ) {
			$additional_info_fields = array(
				'entry_id'         => esc_html__( 'Entry ID', 'everest-forms-pro' ),
				'status'           => esc_html__( 'Entry Status', 'everest-forms-pro' ),
				'date_created'     => esc_html__( 'Date Created', 'everest-forms-pro' ),
				'date_created_gmt' => esc_html__( 'Date Created GMT', 'everest-forms-pro' ),
				'user_device'      => esc_html__( 'User Device', 'everest-forms-pro' ),
				'user_ip_address'  => esc_html__( 'User IP Address', 'everest-forms-pro' ),
			);

			$export_formats = array(
				'csv'  => 'Export as CSV',
				'json' => 'Export as JSON',
				'ods'  => 'Export as ODS',
				'xlsx' => 'Export as XLSX',
			);
			?>
			<section class="evf-tools-entries-export-options-additional-info">
				<h4><?php esc_html_e( 'Additional Informations', 'everest-forms-pro' ); ?></h4>
				<?php self::display_additional_info( $additional_info_fields ); ?>
			</section>

			<section class="evf-tools-entries-export-options-format">
				<h4><?php esc_html_e( 'Export Formats', 'everest-forms-pro' ); ?></h4>
				<?php self::display_export_format( $export_formats ); ?>
			</section>

			<section class="evf-tools-entries-export-options-date">
				<h4><?php esc_html_e( 'Date Range', 'everest-forms-pro' ); ?></h4>
				<input type="text" name="date_range" class="evf-tools-entries-export-date-range" id="evf-tools-entries-export-date-range" style="min-width: 350px;">
			</section>

			<section class="evf-tools-entries-export-options-search">
				<h4><?php esc_html_e( 'Search', 'everest-forms-pro' ); ?></h4>
				<div class="evf-tools-entries-export-options-search-options">
					<?php self::display_search_block( $form_fields, $additional_info_fields ); ?>
				</div>
			</section>

			<section class="evf-tools-entries-export-button publishing-action">
				<?php wp_nonce_field( 'everest_forms_entries_export_nonce', 'everest-forms-entries-export-nonce' ); ?>
				<button type="submit" class="everest-forms-btn everest-forms-btn-primary evf-tools-entries-export-submit" name="submit-entries-export">
					<?php esc_html_e( 'Export', 'everest-forms-pro' ); ?>
				</button>
			</section>
			<?php
		}
		$output = ob_get_clean();

		wp_send_json_success(
			array(
				'message' => esc_html__( 'Success.', 'everest-forms-pro' ),
				'html'    => $output,
			)
		);
	}

	/**
	 * Output Additional informations Option  HTML.
	 *
	 * @since 1.5.1
	 *
	 * @param  array $additional_info_fields Additional informations.
	 */
	public static function display_additional_info( $additional_info_fields ) {
		echo '<select class="evf-select2-multiple evf-tools-entries-export-options-additional-info-select" name="additional_info[]" data-placeholder="' . esc_attr__( 'Select Additional Information(s)', 'everest-forms-pro' ) . '" data-selected_msg="' . esc_attr__( 'Selected %qty% Additional Information(s)', 'everest-forms-pro' ) . '" style="min-width: 350px;" multiple>';
		foreach ( $additional_info_fields as $slug => $label ) {
			echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Output Export Formats Option  HTML.
	 *
	 * @since 1.5.1
	 *
	 * @param  array $export_formats Export Formats.
	 */
	public static function display_export_format( $export_formats ) {
		echo '<select class="evf-tools-entries-export-options-export-format-select" name="export_format" style="min-width: 350px;">';
		foreach ( $export_formats as $slug => $label ) {
			echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Output Search Option  HTML.
	 *
	 * @since 1.5.1
	 *
	 * @param  array $form_fields Form Fields.
	 * @param  array $additional_info_fields Additional Info Fields.
	 */
	public static function display_search_block( $form_fields, $additional_info_fields ) {
		echo '<select class="evf-tools-entries-export-options-search-fiels-select" name="search_field" data-placeholder="' . esc_attr__( 'Select Field', 'everest-forms-pro' ) . '">';
		echo '<optgroup label="' . esc_attr__( 'Form Fields', 'everest-forms-pro' ) . '">';
		echo '<option value="">' . esc_html__( 'Select Field', 'everest-forms-pro' ) . '</option>';
		$i = 0;
		foreach ( $form_fields as $id => $field ) {
			if ( isset( $field['id'] ) && ! empty( $field['id'] ) ) {
				$name = ! empty( $field['label'] ) ?
					trim( wp_strip_all_tags( $field['label'] ) ) :
					sprintf( /* translators: %d - Field ID. */
						esc_html__( 'Field #%d', 'everest-forms-pro' ),
						(int) $i
					);
				echo '<option value="' . esc_attr( $field['id'] ) . '">' . esc_html( $name ) . '</option>';
				++$i;
			}
		}
		echo '</option>';

		echo '<optgroup label="' . esc_attr__( 'Additional Info Fields', 'everest-forms-pro' ) . '">';
		unset( $additional_info_fields['date_created_gmt'] );
		foreach ( $additional_info_fields as $slug => $label ) {
			echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label ) . '</option>';
		}
		echo '</optgroup>';
		echo '</select>';

		echo '<select class="evf-tools-entries-export-options-search-comparison-select" name="search_comparison">';
		echo '<option value="contains">' . esc_html__( 'contains', 'everest-forms-pro' ) . '</option>';
		echo '<option value="contains_not">' . esc_html__( 'does not contains', 'everest-forms-pro' ) . '</option>';
		echo '<option value="is">' . esc_html__( 'is', 'everest-forms-pro' ) . '</option>';
		echo '<option value="is_not">' . esc_html__( 'is not', 'everest-forms-pro' ) . '</option>';
		echo '</select>';

		echo '<input type="text" name="search_value" class="evf-tools-entries-export-options-search-value" placeholder="' . esc_attr__( 'Enter Field Value', 'everest-forms-pro' ) . '" />';
	}
	/**
	 * Export Entries.
	 *
	 * @since 1.5.1
	 */
	public function export_entries() {
		// Check for non empty $_POST.
		if ( ! isset( $_POST['submit-entries-export'] ) || ! isset( $_POST['everest-forms-entries-export-nonce'] ) ) {
			return;
		}

		// Nonce check.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['everest-forms-entries-export-nonce'] ) ), 'everest_forms_entries_export_nonce' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms' ) );
		}

		if ( isset( $_POST['form_id'] ) && current_user_can( 'export' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_id      = absint( wp_unslash( $_POST['form_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$form_name    = strtolower( get_the_title( $form_id ) );
			$request_data = $_POST;
			$format       = isset( $request_data['export_format'] ) ? wp_unslash( $request_data['export_format'] ) : '';

			if ( $form_name ) {
				include_once EVF_ABSPATH . 'includes/export/class-evf-entry-csv-exporter.php';
				include_once EFP_ABSPATH . 'includes/export/class-evf-entry-exporter.php';

				$exporter     = new EVF_Entry_CSV_Exporter( $form_id, '', $request_data );
				$column_names = $exporter->get_default_column_names();
				$row_data     = $exporter->prepare_data_to_export();
				$row_data     = array_filter(
					$row_data,
					function ( $row ) {
						return count( $row );
					}
				);

				switch ( $format ) {
					case 'json':
						$this->export_as_json( $column_names, $row_data, evf_get_entry_export_file_name( $form_name, 'json' ) );
						break;
					case 'ods':
						EVF_Entry_Exporter::export( $column_names, $row_data, 'ods', evf_get_entry_export_file_name( $form_name, 'ods' ) );
						break;
					case 'xlsx':
						EVF_Entry_Exporter::export( $column_names, $row_data, 'xlsx', evf_get_entry_export_file_name( $form_name, 'xlsx' ) );
						break;
					default:
						EVF_Entry_Exporter::export( $column_names, $row_data, 'csv', evf_get_entry_export_file_name( $form_name, 'csv' ) );
				}
			}
		}
	}

	/**
	 * Export entries to JSON file.
	 *
	 * @since 1.5.1
	 *
	 * @param  array  $column_names Column Names.
	 * @param  array  $row_data Row Data.
	 * @param  string $file_name File Name.
	 */
	public function export_as_json( $column_names, $row_data, $file_name = 'evf-form-entry.json' ) {
		$export_data = array();

		if ( count( $row_data ) ) {
			foreach ( $row_data as $row ) {
				$export_row = array();
				foreach ( $row as $key => $value ) {
					if ( isset( $export_row[ $column_names[ $key ] ] ) && array_key_exists( $column_names[ $key ], $export_row ) ) {
						if ( is_array( $export_row[ $column_names[ $key ] ] ) ) {
							$export_row[ $column_names[ $key ] ] [] = $value;
						} else {
							$values    = array();
							$values [] = $export_row[ $column_names[ $key ] ];
							$values [] = $value;

							$export_row[ $column_names[ $key ] ] = $values;
						}
					} else {
						$export_row[ $column_names[ $key ] ] = $value;
					}
				}
				$export_data[] = $export_row;
			}
		}

		header( 'Content-type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( $file_name ) . '"' );
		echo wp_json_encode( $export_data );
		exit;
	}

	/**
	 * Filter Export Entry Based on Field.
	 *
	 * @since 1.5.1
	 *
	 * @param  array $columns Columns.
	 * @param  array $request_data Request Data.
	 * @return array
	 */
	public function filter_export_entry_column( $columns, $request_data ) {
		$export_fields = array();

		if ( isset( $request_data['fields'] ) && ! empty( $request_data['fields'] ) ) {
			$export_fields = array_merge( $export_fields, $request_data['fields'] );
		}

		if ( isset( $request_data['additional_info'] ) && ! empty( $request_data['additional_info'] ) ) {
			$export_fields = array_merge( $export_fields, $request_data['additional_info'] );
		}

		if ( isset( $request_data['fields'] ) ) {
			foreach ( $columns as $column_key => $column_name ) {

				if ( ! empty( $export_fields ) ) {
					$position = strpos( $column_key, '-likert-' );

					if ( ! in_array( $column_key, $export_fields, true ) && false === $position ) {
						unset( $columns[ $column_key ] );
					}
				}
			}
		}
		return $columns;
	}

	/**
	 * Filter Based on Date Range.
	 *
	 * @since 1.5.1
	 *
	 * @param  array  $row Row.
	 * @param  object $entry Entry object.
	 * @param  array  $request_data Request Data.
	 * @return array
	 */
	public function filter_export_entry_date_range( $row, $entry, $request_data ) {
		if ( isset( $request_data['date_range'] ) && ! empty( $request_data['date_range'] ) ) {
			$dates       = explode( 'to', sanitize_text_field( wp_unslash( $request_data['date_range'] ) ) );
			$dates_range = evf_date_range( $dates[0], $dates[1], '+1 day', 'Y-m-d' );
			$created_at  = date_i18n( 'Y-m-d', strtotime( $entry->date_created ) );

			if ( in_array( $created_at, $dates_range, true ) ) {
				return $row;
			} else {
				return array();
			}
		} else {
			return $row;
		}
	}

	/**
	 * Filter Based on field value.
	 *
	 * @since 1.5.1
	 *
	 * @param  array  $row Row.
	 * @param  object $entry Entry object.
	 * @param  array  $request_data Request Data.
	 * @return array
	 */
	public function filter_export_entry_column_search( $row, $entry, $request_data ) {
		if ( ( isset( $request_data['search_field'] ) && ! empty( $request_data['search_field'] ) ) && ( isset( $request_data['search_comparison'] ) && ! empty( $request_data['search_comparison'] ) ) && ( isset( $request_data['search_value'] ) && ! empty( $request_data['search_value'] ) ) ) {
			$field      = sanitize_text_field( wp_unslash( $request_data['search_field'] ) );
			$comparison = sanitize_text_field( wp_unslash( $request_data['search_comparison'] ) );
			$value      = strtolower( sanitize_text_field( wp_unslash( $request_data['search_value'] ) ) );
			$fld_value  = strtolower( $row[ $field ] );

			if ( array_key_exists( $field, $row ) ) {
				if ( 'contains' === $comparison ) {
					if ( false !== stripos( $fld_value, $value ) ) {
						return $row;
					} else {
						return array();
					}
				} elseif ( 'contains_not' === $comparison ) {
					if ( false === stripos( $fld_value, $value ) ) {
						return $row;
					} else {
						return array();
					}
				} elseif ( 'is' === $comparison ) {
					if ( $value === $fld_value ) {
						return $row;
					} else {
						return array();
					}
				} elseif ( 'is_not' === $comparison ) {
					if ( $value !== $fld_value ) {
						return $row;
					} else {
						return array();
					}
				}
			} else {
				return $row;
			}
		} else {
			return $row;
		}
	}

	/**
	 * Form script data.
	 *
	 * @param  array  $params Array of l10n data parameters.
	 * @param  string $handle Script handle the data will be attached to.
	 * @return array
	 */
	public function form_script_data( $params, $handle ) {
		if ( 'everest-forms' === $handle ) {
			$params = array_merge(
				$params,
				array(
					'i18n_no_countries'           => _x( 'No countries found', 'enhanced select', 'everest-forms-pro' ),
					'i18n_messages_phone'         => get_option( 'everest_forms_phone_validation', __( 'Please enter a valid phone number.', 'everest-forms-pro' ) ),
					'i18n_messages_fileextension' => get_option( 'everest_forms_fileextension_validation', __( 'File type is not allowed.', 'everest-forms-pro' ) ),
					'i18n_messages_filesize'      => get_option( 'everest_forms_filesize_validation', __( 'File exceeds max size allowed.', 'everest-forms-pro' ) ),
					'i18n_select_all'             => _x( 'Select All', 'enhanced select', 'everest-forms-pro' ),
					'i18n_unselect_all'           => _x( 'Unselect All', 'enhanced select', 'everest-forms-pro' ),
					'i18n_color_code'             => _x( 'Please enter a valid hex color code.', 'hex color code validation', 'everest-forms-pro' ),
				)
			);
		}

		return $params;
	}

	/**
	 * Display row meta in the Plugins list table.
	 *
	 * @param  array  $plugin_meta Plugin Row Meta.
	 * @param  string $plugin_file Plugin Base file.
	 * @return array
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( EFP_PLUGIN_FILE ) === $plugin_file ) {
			$new_plugin_meta = array(
				'docs' => '<a href="' . esc_url( 'https://docs.everestforms.net/docs-category/everest-forms-pro/' ) . '" aria-label="' . esc_attr__( 'View Everest Forms Pro documentation', 'everest-forms-pro' ) . '">' . esc_html__( 'Docs', 'everest-forms-pro' ) . '</a>',
			);

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
	}


	/**
	 * Form setting for admin and user.
	 *
	 * @param array   $setting       Connection setting for email attahcment.
	 * @param integer $connection_id Connection id for the attachment.
	 */
	public function apply_email_attacment_setting( $setting, $connection_id ) {
		$form_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		everest_forms_panel_field(
			'toggle',
			'settings[email][' . $connection_id . ']',
			'file-email-attachments',
			$form_data,
			esc_html__( 'Send File As Attachment', 'everest-forms-pro' ),
			array(
				'default' => ! empty( $setting->form_data['settings']['email'][ $connection_id ]['file-email-attachments'] ) ? $setting->form_data['settings']['email'][ $connection_id ]['file-email-attachments'] : '0',
				'class'   => 'everest-forms-file-email-attachments',
				'tooltip' => sprintf( 'Enable to send the file as attachement to email', 'everest-forms-pro' ),
			)
		);
		everest_forms_panel_field(
			'toggle',
			'settings[email][' . $connection_id . ']',
			'csv-file-email-attachments',
			$form_data,
			esc_html__( 'Send CSV File As Attachment', 'everest-forms-pro' ),
			array(
				'default' => ! empty( $setting->form_data['settings']['email'][ $connection_id ]['csv-file-email-attachments'] ) ? $setting->form_data['settings']['email'][ $connection_id ]['csv-file-email-attachments'] : '0',
				'class'   => 'everest-forms-csv-file-email-attachments',
				'tooltip' => sprintf( 'Enable to send the  csv file as attachement to email', 'everest-forms-pro' ),
			)
		);
	}

	/**
	 * Everest Forms fallback notice.
	 */
	public function everest_forms_missing_notice() {
		$all_plugins = get_plugins();
		if ( isset( $all_plugins['everest-forms/everest-forms.php'] ) ) {
			$plugin_path = 'everest-forms/everest-forms.php';
			$plugin_url  = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'activate',
						'plugin' => urlencode( $plugin_path ),
					),
					self_admin_url( 'plugins.php' )
				),
				'activate-plugin_' . $plugin_path
			);

			?>
			<div class="notice-warning notice">
				<p><?php esc_html_e( 'Everest Forms Pro requires the Everest Forms Plugin.', 'everest-forms-pro' ); ?></p>
				<p ><a href="<?php echo esc_url( $plugin_url ); ?>" class="button-primary"><?php esc_html_e( 'Click here to activate the plugin', 'everest-forms-pro' ); ?></a></p>
			</div>
			<?php

		} else {

			$plugin_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'install-plugin',
						'plugin' => 'everest-forms',
					),
					admin_url( 'update.php' )
				),
				'install-plugin_everest-forms'
			);

			?>
			<div class="notice-warning notice">
				<p><?php esc_html_e( 'Everest Forms Pro requires the Everest Forms Plugin to be installed.', 'everest-forms-pro' ); ?></p>
				<p ><a href="<?php echo esc_url( $plugin_url ); ?>" class="button-primary"><?php esc_html_e( 'Click here to install the plugin', 'everest-forms-pro' ); ?></a></p>
			</div>
			<?php
		}
	}

	/**
	 * Scheduled Task.
	 *
	 * @since 1.4.5
	 */
	public function cleanup_old_entries() {
		if ( 'yes' != get_option( 'everest_forms_scheduled_entry_delete' ) ) {
			return;
		}

		$forms = evf()->form->get_multiple( array(), true );

		foreach ( $forms as $form ) {
			$expires = get_option( 'everest_forms_scheduled_entry_delete_time', 30 );
			$entries = $this->validate_as_unique(
				array(
					'form_id' => $form['id'],
				)
			);

			foreach ( $entries as $key => $entry ) {
				$entry = evf_get_entry( $entry );
				if ( strtotime( $entry->date_created . ' + ' . $expires . ' days' ) < time() ) {
					EVF_Admin_Entries::remove_entry( $entry->entry_id );
				}
			}
		}
	}
	/**
	 * Cleanup the old api logs.
	 *
	 * @since 1.7.8
	 */
	public function cleanup_old_api_logs() {
		$model = new EVFP_Api_Logs_Model();

		$res = $model->get_api_logs( array() );
		/**
		 * Delete the api logs time.
		 *
		 * @since 1.7.8
		 */
		$expires = get_option( 'everest_forms_scheduled_api_delete_time', 60 );

		$api_logs = $res['result'];
		foreach ( $api_logs as $log ) {

			if ( strtotime( $log->created_date . ' + ' . $expires . ' days' ) < time() ) {
				$model->remove_log( $log->id );
			}
		}
	}
	/**
	 * Sortable column submission form ajax.
	 *
	 * @since 1.4.4
	 */
	public static function set_columns() {
		try {
			if ( ! isset( $_POST['evf_entries_active_columns'] ) ) {
				wp_die( -1 );
			}
			// Check nonce security of form after submission.
			check_ajax_referer( 'process-entries-ajax-nonce', 'security' );

			// Get form_id, form_data and column names.
			$form_id   = isset( $_POST['evf_entries_form_id'] ) ? absint( $_POST['evf_entries_form_id'] ) : 0;
			$form_data = evf()->form->get( absint( $form_id ), array( 'content_only' => true ) );

			// Get the column value to update in the field.
			$active_column_value = str_replace( 'evf_field_', '', array_flip( wp_unslash( $_POST['evf_entries_active_columns'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$active_column_value = array_keys( array_flip( $active_column_value ) );

			// Get post_content of the form for the column name updates.
			$result = 0;
			if ( $form_data ) {
				$form_data['meta']['entry_columns'] = $active_column_value;
				$result                             = evf()->form->update( $form_id, $form_data );
			}
			if ( $form_id === $result ) {
				wp_send_json_success(
					array(
						'message' => esc_html__( 'Columns has been updated Successfully.', 'everest-forms-pro' ),
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Export Tools Entries ajax.
	 *
	 * @since 1.5.1
	 */
	public function export_entry_action() {
		try {
			check_ajax_referer( 'process-tools-export-entries-ajax-nonce', 'security' );
			$this->display_entries_export_options();
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Populate the state field
	 *
	 * @since 1.5.9
	 */
	public function populate_state_field() {
		try {
			check_ajax_referer( 'everest_forms_state_nonce', 'security' );
			$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( empty( $form_id ) ) {
				return;
			}

			$form_data = evf()->form->get( absint( $form_id ), array( 'content_only' => true ) );

			foreach ( $form_data['form_fields'] as $id => $field ) {
				if ( isset( $field['autocomplete_address'] ) && '1' === $field['autocomplete_address'] ) {
					wp_send_json_error(
						array(
							'message' => 'Something went wrong, Please try again',
						)
					);
				}
			}
			$states_list = evf_get_states();
			$country     = isset( $_POST['country'] ) ? absint( $_POST['country'] ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Missing

			wp_send_json_success(
				array(
					'states'  => $states_list,
					'country' => $country,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Get extra columns
	 *
	 * @param array $columns columns.
	 * @param int   $form_id  form id.
	 * @param array $form_data form data.
	 *
	 * @return array $columns list of columns.
	 */
	public function get_sn_column( $columns, $form_id = 0, $form_data = array() ) {
		$column['sn'] = esc_html__( 'S.N.', 'everest-forms-pro' );
		$columns      = array_merge( $column, $columns );
		return $columns;
	}

	/**
	 * Get extra columns
	 *
	 * @param array $columns columns.
	 * @param int   $form_id  form id.
	 * @param array $form_data form data.
	 *
	 * @return array $columns list of columns.
	 */
	public function get_entry_id_column( $columns, $form_id = 0, $form_data = array() ) {
		$column['id'] = esc_html__( 'Entry ID', 'everest-forms-pro' );
		$columns      = array_merge( $column, $columns );
		return $columns;
	}


	/**
	 * Get entry columns.
	 *
	 * @since 1.4.4
	 */
	public static function get_columns() {
		try {
			// Check nonce security of form after submission.
			check_ajax_referer( 'process-entries-ajax-nonce', 'security' );
			// Get form_id, form_data and column names.
			$form_id   = isset( $_POST['evf_entries_form_id'] ) ? absint( $_POST['evf_entries_form_id'] ) : 0;
			$form_data = evf()->form->get( absint( $form_id ), array( 'content_only' => true ) );

			// Get the column list to show in the form.
			$columns = array();
			foreach ( $form_data['form_fields'] as $id => $field ) {
				if ( ! in_array( $field['type'], EVF_Admin_Entries_Table_List::get_columns_form_disallowed_fields(), true ) ) {
					$columns[ 'evf_field_' . $id ] = ! empty( $field['label'] ) ? wp_strip_all_tags( $field['label'] ) : esc_html__( 'Field', 'everest-forms-pro' );
				}
			}

			// Get all Columns.
			$all_columns = apply_filters( 'everest_forms_entries_table_extra_columns', $columns, $form_id, $form_data );
			$all_columns = apply_filters( 'everest_forms_entries_table_extra_columns_id', $all_columns, $form_id, $form_data );
			// Get active Columns.
			$entries_table            = new EVF_Admin_Entries_Table_List();
			$entries_table->form_id   = $form_id;
			$entries_table->form_data = $form_data;
			$active_columns           = apply_filters( 'everest_forms_entries_table_form_fields_columns', $entries_table->get_columns_form_fields(), $form_id, $form_data );

			// Get inactive Columns.
			$inactive_columns = array_diff( $all_columns, $active_columns );

			wp_send_json(
				array(
					'inactive_columns'    => array_filter( $inactive_columns ),
					'active_columns'      => array_filter( $active_columns ),
					'evf_entries_form_id' => $form_id,
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Everest Forms Pro smart tags.
	 *
	 * @param mixed $tags Smart Tags.
	 *
	 * @since 1.4.7
	 */
	public function everest_forms_pro_smart_tags( $tags ) {
		return array_merge(
			$tags,
			array(
				'current_data_and_time' => esc_html__( 'Current Date and Time', 'everest-forms-pro' ),
				'biographical_info'     => esc_html__( 'Biographical Info', 'everest-forms-pro' ),
				'user_role'             => esc_html__( 'User Role', 'everest-forms-pro' ),
				'unique_id'             => esc_html__( 'Unique ID', 'everest-forms-pro' ),
				'approval_link'         => esc_html__( 'Approval Link', 'everest-forms-pro' ),
				'denial_link'           => esc_html__( 'Denial Link', 'everest-forms-pro' ),
			)
		);
	}

	/**
	 * Process and parse smart tags.
	 *
	 * @param string $content The string to preprocess.
	 * @param array  $form_data Array of the form data.
	 * @param array  $fields Array of the form data.
	 * @param int    $entry_id id of the form data.
	 *
	 * @since 1.4.7
	 */
	public function everest_forms_pro_process_smart_tags( $content, $form_data, $fields = array(), $entry_id = 0 ) {
		preg_match_all( '/\{(.+?)\}/', $content, $tags );

		if ( ! empty( $tags[1] ) ) {

			foreach ( $tags[1] as $key => $tag ) {

				switch ( $tag ) {
					case 'current_data_and_time':
						$current_data_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
						$content           = str_replace( '{' . $tag . '}', $current_data_time, $content );
						break;
					case 'biographical_info':
						$user_bio = get_the_author_meta( 'description' );
						$content  = str_replace( '{' . $tag . '}', $user_bio, $content );
						break;
					case 'user_role':
						$user    = wp_get_current_user();
						$roles   = implode( ', ', (array) $user->roles );
						$content = str_replace( '{' . $tag . '}', $roles, $content );
						break;
					case 'unique_id':
						$unique_id = apply_filters( 'evf_unique_id_generation', uniqid( 'evf', true ) );
						$content   = str_replace( '{' . $tag . '}', $unique_id, $content );
						break;
					case 'approval_link':
						// Checks if the admin approval entries is enabled or not.
						$evf_admin_approval_entries_enable = get_option( 'everest_forms_admin_approval_entries_enable', 'no' );

						if ( empty( $evf_admin_approval_entries_enable ) ) {
							return;
						}

						// Grabs the token from the options table.
						$evf_admin_approval_entry_token_list = get_option( 'everest_forms_admin_entry_approval_token' );
						$evf_admin_approval_entry_token_key  = 'approval_token_' . $entry_id;
						$evf_admin_approval_entry_token      = $evf_admin_approval_entry_token_list[ "$evf_admin_approval_entry_token_key" ];

						$evf_admin_approval_entry_link = '<a class="everest-forms-btn everest-forms-save-button" href="' . admin_url( '/' ) . '?evf_admin_approval_entry_token=' . $evf_admin_approval_entry_token . '&form_id=' . $form_data['id'] . '&entry_id=' . $entry_id . '">' . esc_html__( 'Approve Now', 'everest-forms-pro' ) . '</a><br />';
						$content                       = str_replace( '{' . $tag . '}', $evf_admin_approval_entry_link, $content );
						break;

					case 'denial_link':
						// Checks if the admin approval entries is enabled or not.
						$evf_admin_approval_entries_enable = get_option( 'everest_forms_admin_approval_entries_enable', 'no' );

						if ( empty( $evf_admin_approval_entries_enable ) ) {
							return;
						}

						$evf_admin_denial_entry_token_list = get_option( 'everest_forms_admin_entry_approval_token', array() );
						$evf_admin_denial_token_key        = 'approval_token_' . $entry_id;
						$evf_admin_denial_entry_token      = $evf_admin_denial_entry_token_list[ "$evf_admin_denial_token_key" ];
						$evf_admin_denial_entry_link       = '<a class="everest-forms-btn everest-forms-btn-primary" href="' . admin_url( '/' ) . '?evf_admin_denial_entry_token=' . $evf_admin_denial_entry_token . '&form_id=' . $form_data['id'] . '&entry_id=' . $entry_id . '">' . esc_html__( 'Deny Now', 'everest-forms-pro' ) . '</a><br />';
						$content                           = str_replace( '{' . $tag . '}', $evf_admin_denial_entry_link, $content );
						break;
				}
			}
		}

		return $content;
	}

	/**
	 * Unique Field Validation.
	 *
	 * @param string $field_id Field ID.
	 * @param mixed  $field_submit Field Submit.
	 * @param mixed  $form_data Form Data.
	 * @return void
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		if ( ! empty( $form_data['form_fields'][ $field_id ]['no_duplicates'] ) ) {
			if ( is_array( $field_submit ) ) {
				$value = ! empty( $field_submit['primary'] ) ? $field_submit['primary'] : '';
			} else {
				$value = ! empty( $field_submit ) ? $field_submit : '';
			}

			$duplicate = $this->validate_as_unique(
				array(
					'form_id' => $form_data['id'],
					'search'  => $value,
				)
			);
			if ( ! empty( $duplicate ) && ! empty( $value ) ) {
				$validation_message = ! empty( $form_data['form_fields'][ $field_id ]['validate_message'] ) ? $form_data['form_fields'][ $field_id ]['validate_message'] : esc_html__( 'This field value need to be unique.', 'everest-forms-pro' );
				if ( empty( $form_data['form_fields'][ $field_id ]['confirmation'] ) ) {
					evf()->task->errors[ $form_data['id'] ][ $field_id ] = $validation_message;
				} else {
					evf()->task->errors[ $form_data['id'] ][ $field_id ]['primary'] = $validation_message;
				}
				update_option( 'evf_validation_error', 'yes' );
			}
		}
	}

	/**
	 * Search entries data for unique validation.
	 *
	 * @param  array $args Search arguments.
	 * @return array
	 */
	public function validate_as_unique( $args ) {
		global $wpdb;
		$args = wp_parse_args(
			$args,
			array(
				'limit'   => 10,
				'form_id' => 0,
				'offset'  => 0,
				'order'   => 'DESC',
				'orderby' => 'entry_id',
			)
		);

		$query   = array();
		$query[] = "SELECT DISTINCT {$wpdb->prefix}evf_entries.entry_id FROM {$wpdb->prefix}evf_entries INNER JOIN {$wpdb->prefix}evf_entrymeta WHERE {$wpdb->prefix}evf_entries.entry_id = {$wpdb->prefix}evf_entrymeta.entry_id";

		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$query[] = $wpdb->prepare( 'AND meta_value LIKE %s', $like );
		}
		if ( ! empty( $args['form_id'] ) ) {
			$query[] = $wpdb->prepare( 'AND form_id = %d', absint( $args['form_id'] ) );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( implode( ' ', $query ), ARRAY_A );
		$ids     = wp_list_pluck( $results, 'entry_id' );
		return $ids;
	}

	/**
	 * Form General Settings.
	 *
	 * @since 1.5.2
	 *
	 * @param object $obj Form object.
	 */
	public function form_general_settings( $obj ) {
		everest_forms_panel_field(
			'toggle',
			'settings',
			'enable_form_field_icon',
			$obj->form_data,
			esc_html__( 'Enable Form Field Icon', 'everest-forms-pro' ),
			array(
				'default' => isset( $settings['enable_form_field_icon'] ) ? $settings['enable_form_field_icon'] : 0,
				'tooltip' => esc_html__( 'Enable field icon in the form.', 'everest-forms-pro' ),
			)
		);

		everest_forms_panel_field(
			'toggle',
			'settings',
			'keyboard_friendly_form',
			$obj->form_data,
			esc_html__( 'Enable Keyboard Friendly Form', 'everest-forms-pro' ),
			array(
				'default' => isset( $settings['keyboard_friendly_form'] ) ? $settings['keyboard_friendly_form'] : 0,
				'tooltip' => esc_html__( 'Enable keyboard friendly form.', 'everest-forms-pro' ),
			)
		);

		$evf_global_settings_url         = esc_url( admin_url( 'admin.php?page=evf-settings' ) );
		$evf_admin_approval_entry_status = get_option( 'everest_forms_admin_approval_entries_enable' );
		if ( 'yes' === $evf_admin_approval_entry_status ) {
			everest_forms_panel_field(
				'toggle',
				'settings',
				'enable_admin_approval_entries',
				$obj->form_data,
				esc_html__( 'Enable Admin Approval Entries', 'everest-forms-pro' ),
				array(
					'default' => isset( $settings['enable_admin_approval_entries'] ) ? $settings['enable_admin_approval_entries'] : 0,
					/*translators: %s - global settings */
					'tooltip' => sprintf( __( 'You need to enable Admin Approval Entries option on <strong><a href="%s">Global Settings</a></strong> to send the approval link in email', 'everest-forms-pro' ), $evf_global_settings_url ),
				)
			);
		}
	}

	/**
	 * Output field icon wrapper HTML.
	 *
	 * @since 1.5.2
	 * @param  arary $field Field Data.
	 * @param  arary $form_data Form Data.
	 */
	public function output_field_icon_wrapper_html( $field, $form_data ) {
		$enable_field_icon = isset( $form_data['settings']['enable_form_field_icon'] ) && '1' === $form_data['settings']['enable_form_field_icon'];
		$icon              = $field['type'];
		$field_types       = array( 'text', 'first-name', 'last-name', 'email', 'url', 'number', 'date-time', 'phone', 'password', 'payment-quantity' );

		if ( true === $enable_field_icon && in_array( $field['type'], $field_types, true ) ) {
			printf( '<span class="input-wrapper">' );
		}
	}

	/**
	 * Output Field Icon HTML.
	 *
	 * @since 1.5.2
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 */
	public static function output_field_icon_html( $field, $form_data ) {
		$enable_field_icon = isset( $form_data['settings']['enable_form_field_icon'] ) && '1' === $form_data['settings']['enable_form_field_icon'];
		$icon              = $field['type'];
		$field_types       = array( 'text', 'first-name', 'last-name', 'email', 'url', 'number', 'date-time', 'password', 'payment-quantity' );

		if ( 'phone' === $field['type'] && 'smart' !== $field['phone_format'] ) {
			$field_types [] = 'phone';
		}

		if ( true === $enable_field_icon && in_array( $field['type'], $field_types, true ) ) {

			if ( 'url' === $field['type'] ) {
				$icon = 'website';
			} elseif ( 'date-time' === $field['type'] ) {
				$icon = 'calendar';
			} elseif ( 'payment-quantity' === $field['type'] ) {
				$icon = 'single-item';
			}
			printf( '<span class="evf-icon evf-icon-%s"></span>', esc_html( $icon ) );
			printf( '</span>' );

		}
	}

	/**
	 * Install and active addons
	 *
	 * @since 1.6.6
	 * @throws Exception If the field plan is empty.
	 */
	public function install_and_active_addons() {
		try {
			check_ajax_referer( 'install_and_active_nonce', 'security' );
			$field_plan = isset( $_POST['field_plan'] ) ? sanitize_text_field( wp_unslash( $_POST['field_plan'] ) ) : '';
			$field_type = isset( $_POST['field_type'] ) ? sanitize_text_field( wp_unslash( $_POST['field_type'] ) ) : '';
			$addon_slug = isset( $_POST['addon_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['addon_slug'] ) ) : '';
			if ( empty( $field_plan ) ) {
				throw new Exception( __( 'Field plan is empty', 'everest-forms-pro' ) );
			}
			$get_license = evf_get_license_plan();
			$addons      = EVF_Admin_Addons::get_extension_data();
			foreach ( $addons as $addon ) {
				if ( $addon_slug === $addon->slug ) {
					break;
				}
			}
			if ( in_array( $get_license, $addon->plan, true ) ) {
				if ( file_exists( WP_PLUGIN_DIR . '/' . $addon->slug . '/' . $addon->slug . '.php' ) ) {
					/* translators: %s: Add-on title */
					$aria_label  = sprintf( esc_html__( 'Activate %s now', 'everest-forms' ), $addon->title );
					$plugin_file = plugin_basename( $addon->slug . '/' . $addon->slug . '.php' );
					$url         = wp_nonce_url(
						add_query_arg(
							array(
								'page'   => 'evf-addons',
								'action' => 'activate',
								'plugin' => $plugin_file,
							),
							admin_url( 'admin.php' )
						),
						'activate-plugin_' . $plugin_file
					);
					$title       = esc_html__( 'Addon Activation Required', 'everest-forms' );
					$message     = sprintf( '%s <strong>%s</strong> %s', esc_html__( 'Please active ', 'everest-forms' ), $addon->name, esc_html__( 'addon to use this field', 'everest-forms' ) );
					$content     = sprintf( '<a class="button activate-now" href="%s" aria-label="%s" data-plugin="%s">%s</a>', esc_url( $url ), esc_attr( $aria_label ), esc_attr( $plugin_file ), esc_html__( 'Activate', 'everest-forms' ) );
				} else {
					/* translators: %s: Add-on title */
					$aria_label = sprintf( esc_html__( 'Install %s now', 'everest-forms' ), $addon->slug );
					$title      = esc_html__( 'Addon Installation Required', 'everest-forms' );
					$message    = sprintf( '%s <strong>%s</strong> %s', esc_html__( 'Please install ', 'everest-forms' ), $addon->name, esc_html__( 'addon to use this field', 'everest-forms' ) );
					$content    = sprintf( '<a href="#" class="button install-now install-from-builder" data-slug="%s" data-name="%s" aria-label="%s">%s</a>', esc_attr( $addon->slug ), esc_attr( $addon->name ), esc_attr( $aria_label ), esc_html__( 'Install Addon', 'everest-forms' ) );
					$url        = '';

				}
				wp_send_json_success(
					array(
						'url'     => $url,
						'content' => $content,
						'title'   => $title,
						'message' => $message,
					)
				);
			}
			wp_send_json_error( array( 'addon' => $addon ) );
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Uploaded File Protection.
	 */
	public function evf_uploaded_file_protection() {
		$server_software           = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$server_software           = explode( '/', $server_software )[0];
		$is_file_protection_enable = get_option( 'everest_forms_upload_file_protection', 'no' );

		if ( 'Apache' !== $server_software && evf_string_to_bool( $is_file_protection_enable ) ) {
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="notice notice-error is-dismissible">
						<p>
							<?php
							printf(
								'%s<strong><a href="%s" target="_blank">%s</a></strong>',
								esc_html__( 'This Feature is not supported by your system. Please follow the ', 'everest-forms-pro' ),
								esc_url( 'https://docs.everestforms.net/docs/how-to-protect-uploaded-files-from-direct-access-pro/' ),
								esc_html__( 'documentation', 'everest-forms' )
							);
							?>
						</p>
					</div>
					<?php
				}
			);

		}
		$uploads = wp_upload_dir();
		if ( evf_string_to_bool( $is_file_protection_enable ) ) {
			$evf_uploads_root = trailingslashit( $uploads['basedir'] ) . 'everest_forms_uploads';
			$htaccess_file    = trailingslashit( $evf_uploads_root ) . '/.htaccess';
			$access_rules     = '<FilesMatch "\.(jpg|jpeg|png|gif)$">
				Header set X-Robots-Tag "noindex"
				</FilesMatch>
		    	Options -Indexes';
			if ( wp_mkdir_p( $evf_uploads_root ) ) {
				if ( is_writable( $evf_uploads_root ) ) {
					file_put_contents( trailingslashit( $evf_uploads_root ) . '.htaccess', $access_rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				} else {
					add_action(
						'admin_notices',
						function () {
							?>
							<div class="notice notice-error is-dismissible">
								<p>
									<?php
									printf( '%s<strong><a href="%s" target="_blank">%s</a></strong>', esc_html__( '.htaccess file has not writable permission. Please follow the ', 'everest-forms-pro' ), esc_attr( '' ), esc_html__( 'documentation', 'everest-forms' ) );
									?>
								</p>
							</div>
							<?php
						}
					);
				}
			}
		} else {
			$evf_uploads_root = trailingslashit( $uploads['basedir'] ) . 'everest_forms_uploads';
			$htaccess_file    = trailingslashit( $evf_uploads_root ) . '/.htaccess';
			if ( ! file_exists( $htaccess_file ) ) {
				return;
			}

			if ( is_writable( $htaccess_file ) ) {
				file_put_contents( trailingslashit( $evf_uploads_root ) . '.htaccess', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
			} else {
				add_action(
					'admin_notices',
					function () {
						?>
						<div class="notice notice-error is-dismissible">
							<p>
								<?php
								printf( '%s<strong><a href="%s" target="_blank">%s</a></strong>', esc_html__( '.htaccess file has not writable permission. Please follow the ', 'everest-forms-pro' ), esc_attr( '' ), esc_html__( 'documentation', 'everest-forms' ) );
								?>
							</p>
						</div>
						<?php
					}
				);
			}
		}
	}

	/**
	 * Google calendar for appointment scheduling Field Setting.
	 *
	 * @since 1.7.1
	 *
	 * @param mixed $settings Settings.
	 *
	 * @return $settings Settings.
	 */
	public function google_calendar_for_appt_sched_settings( $settings ) {
		$options   = $settings['advanced-options']['field_options'];
		$new_array = array();
		foreach ( $options as $option ) {
			$new_array [] = $option;
			if (
				'datetime_options' === $option && ! in_array( 'google_calendar_for_appt_sched_option', $new_array )
			) {
				$new_array[] = array_push( $new_array, 'google_calendar_for_appt_sched_option' );
			}
		}
		$settings['advanced-options']['field_options'] = $new_array;

		return $settings;
	}
	/**
	 * Check the everest form version.
	 * Is the code of pro is required or not.
	 */
	public function evf_is_efp_code_required() {
		return defined( 'EVF_VERSION' ) && version_compare( EVF_VERSION, '3.0.0', '<' );
	}

	/**
	 * Deactivate the plugins for backward compatibility.
	 *
	 * @since 1.7.7.
	 */
	public function evf_deactivate_addons() {
		if ( defined( 'EFP_VERSION' ) && version_compare( EFP_VERSION, '1.7.7', '>=' ) ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = array(
				'EVF_SENDINBLUE_PLUGIN_FILE'        => 'everest-forms-brevo',
				'EVF_MAILERLITE_PLUGIN_FILE'        => 'everest-forms-mailerlite',
				'EVF_MAILCHIMP_PLUGIN_FILE'         => 'everest-forms-mailchimp',
				'EVF_GETRESPONSE_PLUGIN_FILE'       => 'everest-forms-getresponse',
				'EVF_DRIP_PLUGIN_FILE'              => 'everest-forms-drip',
				'EVF_CONVERTKIT_PLUGIN_FILE'        => 'everest-forms-convertkit',
				'EVF_CONSTANT_CONTACT_PLUGIN_FILE'  => 'everest-forms-constant_contact',
				'EVF_PIPEDRIVE_PLUGIN_FILE'         => 'everest-forms-pipedrive',
				'EVF_CALCULATIONS_PLUGIN_FILE'      => 'everest-forms-calculations',
				'EVF_USER_REGISTRATION_PLUGIN_FILE' => 'everest-forms-user-registration',
				'EVF_ACTIVECAMPAIGN_PLUGIN_FILE'    => 'everest-forms-activecampaign',
				'EVF_CAMPAIGN_MONITOR_PLUGIN_FILE'  => 'everest-forms-campaign-monitor',
				'EVF_COUPONS_PLUGIN_FILE'           => 'everest-forms-coupons',
				'EVF_SMS_NOTIFICATION_PLUGIN_FILE'  => 'everest-forms-sms-notifications',
				'EVF_STYLE_CUSTOMIZER_PLUGIN_FILE'  => 'everest-forms-style-customizer',
				'EVF_FORM_ANALYTICS_PLUGIN_FILE'    => 'everest-forms-form-analytics',
			);

			$plugins_to_deactivate = array();
			$active_plugin_keys    = array();

			foreach ( $plugins as $plugin_constant => $plugin_key ) {
				if ( defined( $plugin_constant ) ) {
					$plugin_file     = constant( $plugin_constant );
					$plugin_basename = plugin_basename( $plugin_file );

					if ( is_plugin_active( $plugin_basename ) ) {
						$plugins_to_deactivate[] = $plugin_file;
						$active_plugin_keys[]    = $plugin_key;
					}
				}
			}

			if ( ! empty( $plugins_to_deactivate ) ) {
				deactivate_plugins( $plugins_to_deactivate, true );
			}

			$module_activated   = get_option( 'everest_forms_enabled_features', array() );
			$active_plugin_keys = array_merge( $module_activated, $active_plugin_keys );
			update_option( 'everest_forms_enabled_features', $active_plugin_keys );
		}
	}




	/**
	 * Function to hide the deactive plugins.
	 *
	 * @since 1.7.7
	 *
	 * @param  array $plugins List of plugins.
	 *
	 * @return array $plugins List of plugins.
	 */
	public function evf_hide_deactive_plugins( $plugins ) {
		$plugins_to_hide = array( 'everest-forms-sendinblue', 'everest-forms-mailerlite', 'everest-forms-mailchimp', 'everest-forms-constant-contact', 'everest-forms-convertkit', 'everest-forms-getresponse', 'everest-forms-drip', 'everest-forms-pipedrive', 'everest-forms-calculations', 'everest-forms-user-registration', 'everest-forms-activecampaign', 'everest-forms-campaign-monitor', 'everest-forms-coupons', 'everest-forms-sms-notifications', 'everest-forms-style-customizer', 'everest-forms-form-analytics' );

		foreach ( $plugins_to_hide as $plugin_to_hide ) {
			$plugin_hide_path = $plugin_to_hide . '/' . $plugin_to_hide . '.php';
			if ( isset( $plugin_hide_path ) ) {
				unset( $plugins[ $plugin_hide_path ] );
			}
		}

		return $plugins;
	}

	/**
	 * Handles the activation of the Everest Forms plugin.
	 *
	 * @since 1.7.9
	 */
	public static function everest_forms_plugin_activation() {
		if ( null === get_option( 'everest_form_fresh_install', null ) ) {
			$is_fresh = ! self::is_any_payment_gateway_enabled_in_any_form();
			add_option( 'everest_form_fresh_install', $is_fresh, '', false );
		}

		self::flush_rewrite_rules();
	}

	/**
	 * Flushes rewrite rules by registering new rules.
	 *
	 * @since 1.7.9
	 */
	public static function flush_rewrite_rules() {
		self::register_rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Registers custom rewrite rules for the Everest Forms plugin.
	 *
	 * Adds a rewrite tag and rule for public links to the Everest Forms.
	 *
	 * @since 1.7.9
	 *
	 * @return void
	 */
	public static function register_rewrite_rules() {
		add_rewrite_tag( '%evf_public_link%', '([a-zA-Z0-9]+)' );
		add_rewrite_rule( '^everest-forms/([a-zA-Z0-9]+)/?', 'index.php?evf_public_link=$matches[1]', 'top' );
	}
}
