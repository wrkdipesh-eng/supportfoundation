<?php
/**
 * Main class for SmsNotification.
 *
 * @package EverestForms\Pro\Addons\SmsNotification
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\SmsNotification;

use EverestForms\Pro\Addons\SmsNotification\Admin\Settings;
use EverestForms\Pro\Addons\SmsNotification\Builder\Settings as Builder;
use EverestForms\Pro\Addons\SmsNotification\Process\Process;
/**
 * Main feature class.
 *
 * @since 1.7.9
 */
class SmsNotification {

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 *
	 * @since 1.7.9
	 */
	protected static $instance;

	/**
	 * SmsNotification Feature Constructor.
	 *
	 * @since 1.7.9
	 */
	public function __construct() {

		$this->sms_notification_init();

		// Enqueue Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_everest_forms_new_sms_notification_add', array( $this, 'everest_forms_new_sms_notification_add' ) );
		add_action( 'wp_ajax_nopriv_everest_forms_new_sms_notification_add', array( $this, 'everest_forms_new_sms_notification_add' ) );
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Register admin scripts.
			wp_register_script( 'everest-forms-sms-notifications-builder', plugins_url( "/src/Addons/SmsNotification/assets/js/admin/admin{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION, true );
			wp_localize_script(
				'everest-forms-sms-notifications-builder',
				'everest_forms_sms_notifications_params',
				array(
					'sms_notifications_disable_message'  => esc_html__( 'SMS Notification is currently disabled . Please enable it to activate this feature with Everest Forms . ', 'everest-forms-pro' ),
					'i18n_sms_notifications_connection'  => esc_html__( 'Enter a SMS Notifications nickname', 'everest-forms-pro' ),
					'i18n_sms_notifications_placeholder' => esc_html__( 'Eg: Support SMS Notifications', 'everest-forms-pro' ),
					'i18n_sms_notifications_error_name'  => esc_html__( 'You must provide a sms_notification nickname', 'everest-forms-pro' ),
					'i18n_sms_notifications_ok'          => esc_html__( 'OK', 'everest-forms-pro' ),
					'ajax_sms_notifications_nonce'       => wp_create_nonce( 'process-ajax-nonce' ),
					'ajax_url'                           => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_sms_notifications_cancel'      => esc_html__( 'Cancel', 'everest-forms-pro' ),
					'i18n_default_address'               => get_option( 'admin_sms_notifications' ),
				)
			);
			// Admin scripts for EVF builder page.
		if ( 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-sms-notifications-builder' );
		}
	}

	/**
	 * AJAX SMSNotification Add.
	 */
	public static function everest_forms_new_sms_notification_add() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		// Check permissions.
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		$connection_id = 'sms_notification_connection_' . uniqid();

		wp_send_json_success(
			array(
				'connection_id' => $connection_id,
			)
		);
	}

	/**
	 * Init SMSNotification - Logic begins.
	 */
	public function sms_notification_init() {
		if ( is_admin() ) {
			new Builder();
			new Settings();
		}
			new Process();
	}
}
