<?php
/**
 * Plugin Updater
 *
 * @package EverestForms_Pro/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Plugin_Updater Class.
 */
class EVF_Plugin_Updater {

	private $plugin_file = '';
	private $plugin_slug = '';
	private $plugin_data = array();
	private $errors      = array();
	private $api_key;

	/**
	 * @var bool
	 */
	private $addons_registered = false;

	public function __construct() {
		$this->plugin_file = EFP_PLUGIN_FILE;
		$this->plugin_slug = str_replace( '.php', '', basename( $this->plugin_file ) );
		$this->api_key     = get_option( $this->plugin_slug . '_license_key' );

		register_activation_hook( $this->plugin_file, array( $this, 'plugin_activation' ), 10 );
		register_deactivation_hook( $this->plugin_file, array( $this, 'plugin_deactivation' ), 10 );

		add_filter( 'block_local_requests', '__return_false' );

		include_once __DIR__ . '/updater/class-evf-updater-api.php';
		include_once __DIR__ . '/updater/class-evf-updater-key-api.php';

		add_action( 'plugins_loaded', array( $this, 'register_addon_updaters' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array( $this, 'check_all_addons_update' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'check_version_compatibility' ) );
	}

	/**
	 * Handles plugin updater.
	 *
	 * @param string  $plugin_file    Path to the plugin file.
	 * @param integer $item_id        Item ID to send with API calls.
	 * @param integer $plugin_version Plugin Version to send with API calls.
	 */
	public static function updates( $plugin_file, $item_id, $plugin_version ) {
		$license_key = trim( get_option( 'everest-forms-pro_license_key' ) );
		$plugin_data = array(
			'item_id' => $item_id,
			'version' => $plugin_version,
			'license' => $license_key,
			'author'  => 'WPEverest',
			'url'     => home_url(),
		);

		return new EVF_Updater_API( 'https://wpeverest.com/edd-sl-api/', $plugin_file, $plugin_data );
	}

	/**
	 * Register updater instances for all installed addons.
	 *
	 * Hooked to 'plugins_loaded' so all addon constants are available.
	 * Always registers all addons so EVF_Updater_API filter hooks remain
	 * active when WP rebuilds the update_plugins transient.
	 */
	public function register_addon_updaters() {
		if ( $this->addons_registered ) {
			return;
		}

		$this->addons_registered = true;

		foreach ( $this->get_installed_addons() as $addon ) {
			if ( ! defined( $addon['constant'] ) ) {
				continue;
			}
			self::updates( constant( $addon['constant'] ), $addon['item_id'], $addon['version'] );
		}
	}

	/**
	 * Centralized addon update cache management.
	 */
	public function check_all_addons_update() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$is_force_check = isset( $_GET['force-check'] ) && '1' === $_GET['force-check'] // phpcs:ignore WordPress.Security.NonceVerification
						&& isset( $GLOBALS['pagenow'] ) && 'update-core.php' === $GLOBALS['pagenow'];

		if ( $is_force_check ) {
			delete_transient( 'everest_forms_addon_updater' );
			delete_transient( 'everest_forms_addon_updater_lock' );
			$this->clear_addon_edd_caches();

			$this->addons_registered = false;
			$this->register_addon_updaters();
			return;
		}

		if ( get_transient( 'everest_forms_addon_updater' ) ) {
			return;
		}

		if ( get_transient( 'everest_forms_addon_updater_lock' ) ) {
			return;
		}

		set_transient( 'everest_forms_addon_updater_lock', true, 30 );
		$this->clear_addon_edd_caches();
		set_transient( 'everest_forms_addon_updater', true, DAY_IN_SECONDS );
		delete_transient( 'everest_forms_addon_updater_lock' );
	}

	/**
	 * Clear EDD SL option caches for all installed addons.
	 */
	private function clear_addon_edd_caches() {
		$license = trim( get_option( 'everest-forms-pro_license_key' ) );
		foreach ( $this->get_installed_addons() as $addon ) {
			if ( ! defined( $addon['constant'] ) ) {
				continue;
			}
			$slug      = basename( constant( $addon['constant'] ), '.php' );
			$cache_key = 'edd_sl_' . md5( serialize( $slug . $license . false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			delete_option( $cache_key );
		}
	}

	/**
	 * Returns list of all installed addons with verified EDD product IDs.
	 *
	 * @return array
	 */
	private function get_installed_addons() {
		return array(
			array(
				'constant' => 'EVF_PAYPAL_STANDARD_PLUGIN_FILE',
				'plugin'   => 'everest-forms-paypal-standard/everest-forms-paypal-standard.php',
				'item_id'  => 3437,
				'version'  => defined( 'EVF_PAYPALL_STANDARD_VERSION' ) ? EVF_PAYPALL_STANDARD_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_STRIPE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-stripe/everest-forms-stripe.php',
				'item_id'  => 16855,
				'version'  => defined( 'EVF_STRIPE_VERSION' ) ? EVF_STRIPE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_RAZORPAY_PLUGIN_FILE',
				'plugin'   => 'everest-forms-razorpay/everest-forms-razorpay.php',
				'item_id'  => 205578,
				'version'  => defined( 'EVF_RAZORPAY_VERSION' ) ? EVF_RAZORPAY_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_MAILCHIMP_PLUGIN_FILE',
				'plugin'   => 'everest-forms-mailchimp/everest-forms-mailchimp.php',
				'item_id'  => 3432,
				'version'  => defined( 'EVF_MAILCHIMP_VERSION' ) ? EVF_MAILCHIMP_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_ACTIVECAMPAIGN_PLUGIN_FILE',
				'plugin'   => 'everest-forms-activecampaign/everest-forms-activecampaign.php',
				'item_id'  => 61754,
				'version'  => defined( 'EVF_ACTIVE_CAMPAIGN_VERSION' ) ? EVF_ACTIVE_CAMPAIGN_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CAMPAIGN_MONITOR_PLUGIN_FILE',
				'plugin'   => 'everest-forms-campaign-monitor/everest-forms-campaign-monitor.php',
				'item_id'  => 76871,
				'version'  => defined( 'EVF_CAMPAIGN_MONITOR_VERSION' ) ? EVF_CAMPAIGN_MONITOR_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CONVERTKIT_PLUGIN_FILE',
				'plugin'   => 'everest-forms-convertkit/everest-forms-convertkit.php',
				'item_id'  => 3435,
				'version'  => defined( 'EVF_CONVERTKIT_VERSION' ) ? EVF_CONVERTKIT_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_DRIP_PLUGIN_FILE',
				'plugin'   => 'everest-forms-drip/everest-forms-drip.php',
				'item_id'  => 211428,
				'version'  => defined( 'EVF_DRIP_VERSION' ) ? EVF_DRIP_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_GETRESPONSE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-getresponse/everest-forms-getresponse.php',
				'item_id'  => 230163,
				'version'  => defined( 'EVF_GETRESPONSE_VERSION' ) ? EVF_GETRESPONSE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_MAILERLITE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-mailerlite/everest-forms-mailerlite.php',
				'item_id'  => 61756,
				'version'  => defined( 'EVF_MAILERLITE_VERSION' ) ? EVF_MAILERLITE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_SENDINBLUE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-sendinblue/everest-forms-sendinblue.php',
				'item_id'  => 230165,
				'version'  => defined( 'EVF_SENDINBLUE_VERSION' ) ? EVF_SENDINBLUE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_ZAPIER_PLUGIN_FILE',
				'plugin'   => 'everest-forms-zapier/everest-forms-zapier.php',
				'item_id'  => 18350,
				'version'  => defined( 'EVF_ZAPIER_VERSION' ) ? EVF_ZAPIER_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_ZOHO_PLUGIN_FILE',
				'plugin'   => 'everest-forms-zoho/everest-forms-zoho.php',
				'item_id'  => 219736,
				'version'  => defined( 'EVF_ZOHO_VERSION' ) ? EVF_ZOHO_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_PIPEDRIVE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-pipedrive/everest-forms-pipedrive.php',
				'item_id'  => 211433,
				'version'  => defined( 'EVF_PIPEDRIVE_VERSION' ) ? EVF_PIPEDRIVE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_USER_REGISTRATION_PLUGIN_FILE',
				'plugin'   => 'everest-forms-user-registration/everest-forms-user-registration.php',
				'item_id'  => 22439,
				'version'  => defined( 'EVF_USER_REGISTRATION_VERSION' ) ? EVF_USER_REGISTRATION_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CALCULATIONS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-calculations/everest-forms-calculations.php',
				'item_id'  => 193220,
				'version'  => defined( 'EVF_CALCULATIONS_VERSION' ) ? EVF_CALCULATIONS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_COUPONS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-coupons/everest-forms-coupons.php',
				'item_id'  => 211435,
				'version'  => defined( 'EVF_COUPONS_VERSION' ) ? EVF_COUPONS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_SMS_NOTIFICATION_PLUGIN_FILE',
				'plugin'   => 'everest-forms-sms-notifications/everest-forms-sms-notifications.php',
				'item_id'  => 205580,
				'version'  => defined( 'EVF_SMS_NOTIFICATION_VERSION' ) ? EVF_SMS_NOTIFICATION_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_STYLE_CUSTOMIZER_PLUGIN_FILE',
				'plugin'   => 'everest-forms-style-customizer/everest-forms-style-customizer.php',
				'item_id'  => 16166,
				'version'  => defined( 'EVF_STYLE_CUSTOMIZER_VERSION' ) ? EVF_STYLE_CUSTOMIZER_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CONSTANT_CONTACT_PLUGIN_FILE',
				'plugin'   => 'everest-forms-constant-contact/everest-forms-constant-contact.php',
				'item_id'  => 226646,
				'version'  => defined( 'EVF_CONSTANT_CONTACT_VERSION' ) ? EVF_CONSTANT_CONTACT_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_AUTHORIZE_NET_PLUGIN_FILE',
				'plugin'   => 'everest-forms-authorize-net/everest-forms-authorize-net.php',
				'item_id'  => 249228,
				'version'  => defined( 'EVF_AUTHORIZE_NET_VERSION' ) ? EVF_AUTHORIZE_NET_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_MOLLIE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-mollie/everest-forms-mollie.php',
				'item_id'  => 3464,
				'version'  => defined( 'EVF_MOLLIE_VERSION' ) ? EVF_MOLLIE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_SQUARE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-square/everest-forms-square.php',
				'item_id'  => 3465,
				'version'  => defined( 'EVF_SQUARE_VERSION' ) ? EVF_SQUARE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_REPEATER_FIELDS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-repeater-fields/everest-forms-repeater-fields.php',
				'item_id'  => 157038,
				'version'  => defined( 'EVF_REPEATER_FIELDS_VERSION' ) ? EVF_REPEATER_FIELDS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_SURVEY_POLLS_QUIZ_PLUGIN_FILE',
				'plugin'   => 'everest-forms-survey-polls-quiz/everest-forms-survey-polls-quiz.php',
				'item_id'  => 16165,
				'version'  => defined( 'EVF_SURVEY_POLLS_QUIZ_VERSION' ) ? EVF_SURVEY_POLLS_QUIZ_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CAPTCHA_PLUGIN_FILE',
				'plugin'   => 'everest-forms-captcha/everest-forms-captcha.php',
				'item_id'  => 22441,
				'version'  => defined( 'EVF_CAPTCHA_VERSION' ) ? EVF_CAPTCHA_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CLOUD_STORAGE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-cloud-storage/everest-forms-cloud-storage.php',
				'item_id'  => 226644,
				'version'  => defined( 'EVF_CLOUD_STORAGE_VERSION' ) ? EVF_CLOUD_STORAGE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_CONVERSATIONAL_FORMS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-conversational-forms/everest-forms-conversational-forms.php',
				'item_id'  => 232580,
				'version'  => defined( 'EVF_CONVERSATIONAL_FORMS_VERSION' ) ? EVF_CONVERSATIONAL_FORMS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_EMAIL_TEMPLATES_PLUGIN_FILE',
				'plugin'   => 'everest-forms-email-templates/everest-forms-email-templates.php',
				'item_id'  => 72043,
				'version'  => defined( 'EVF_EMAIL_TEMPLATES_VERSION' ) ? EVF_EMAIL_TEMPLATES_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_FORM_ANALYTICS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-form-analytics/everest-forms-form-analytics.php',
				'item_id'  => 252609,
				'version'  => defined( 'EVF_FORM_ANALYTICS_VERSION' ) ? EVF_FORM_ANALYTICS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_FORM_RESTRICTION_PLUGIN_FILE',
				'plugin'   => 'everest-forms-form-restriction/everest-forms-form-restriction.php',
				'item_id'  => 61758,
				'version'  => defined( 'EVF_FORM_RESTRICTION_VERSION' ) ? EVF_FORM_RESTRICTION_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_FRONTEND_LISTING_PLUGIN_FILE',
				'plugin'   => 'everest-forms-frontend-listing/everest-forms-frontend-listing.php',
				'item_id'  => 211437,
				'version'  => defined( 'EVF_FRONTEND_LISTING_VERSION' ) ? EVF_FRONTEND_LISTING_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_GEOLOCATION_PLUGIN_FILE',
				'plugin'   => 'everest-forms-geolocation/everest-forms-geolocation.php',
				'item_id'  => 3632,
				'version'  => defined( 'EVF_GEOLOCATION_VERSION' ) ? EVF_GEOLOCATION_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_GOOGLE_SHEETS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-google-sheets/everest-forms-google-sheets.php',
				'item_id'  => 72041,
				'version'  => defined( 'EVF_GOOGLE_SHEETS_VERSION' ) ? EVF_GOOGLE_SHEETS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_HUBSPOT_PLUGIN_FILE',
				'plugin'   => 'everest-forms-hubspot/everest-forms-hubspot.php',
				'item_id'  => 211431,
				'version'  => defined( 'EVF_HUBSPOT_VERSION' ) ? EVF_HUBSPOT_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_MULTI_PART_PLUGIN_FILE',
				'plugin'   => 'everest-forms-multi-part/everest-forms-multi-part.php',
				'item_id'  => 5422,
				'version'  => defined( 'EVF_MULTI_PART_VERSION' ) ? EVF_MULTI_PART_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_PDF_SUBMISSION_PLUGIN_FILE',
				'plugin'   => 'everest-forms-pdf-submission/everest-forms-pdf-submission.php',
				'item_id'  => 3439,
				'version'  => defined( 'EVF_PDF_SUBMISSION_VERSION' ) ? EVF_PDF_SUBMISSION_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_POST_SUBMISSIONS_PLUGIN_FILE',
				'plugin'   => 'everest-forms-post-submission/everest-forms-post-submission.php',
				'item_id'  => 22436,
				'version'  => defined( 'EVF_POST_SUBMISSIONS_VERSION' ) ? EVF_POST_SUBMISSIONS_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_SALESFLARE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-salesflare/everest-forms-salesflare.php',
				'item_id'  => 72049,
				'version'  => defined( 'EVF_SALESFLARE_VERSION' ) ? EVF_SALESFLARE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_SAVE_AND_CONTINUE_PLUGIN_FILE',
				'plugin'   => 'everest-forms-save-and-continue/everest-forms-save-and-continue.php',
				'item_id'  => 148284,
				'version'  => defined( 'EVF_SAVE_AND_CONTINUE_VERSION' ) ? EVF_SAVE_AND_CONTINUE_VERSION : '1.0.0',
			),
			array(
				'constant' => 'EVF_AWEBER_PLUGIN_FILE',
				'plugin'   => 'everest-forms-aweber/everest-forms-aweber.php',
				'item_id'  => 3446,
				'version'  => defined( 'EVF_AWEBER_VERSION' ) ? EVF_AWEBER_VERSION : '1.0.0',
			),
		);
	}

	/**
	 * Run on admin init.
	 */
	public function admin_init() {
		$this->load_errors();

		add_action( 'shutdown', array( $this, 'store_errors' ) );

		$this->plugin_data = get_plugin_data( $this->plugin_file );

		if ( current_user_can( 'update_plugins' ) ) {
			$this->plugin_requests();
			$this->plugin_license_view();
		}
	}

	/**
	 * Check the version compatibility for form confirmation.
	 */
	public function check_version_compatibility() {
		if ( ! defined( 'EVF_VERSION' ) ) {
			return;
		}

		if ( version_compare( EVF_VERSION, '3.3.0', '<' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error is-dismissible">';
					echo '<p>' . esc_html__( 'Everest Forms Pro requires Everest Forms v3.3.0 or higher. Please update Everest Forms to avoid compatibility issues.', 'everest-forms-pro' ) . '</p>';
					echo '</div>';
				}
			);
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		wp_register_style( 'everest-forms-license', plugins_url( '/assets/css/license.css', EFP_PLUGIN_FILE ), array(), '1.0.0' );
		wp_style_add_data( 'everest-forms-license', 'rtl', 'replace' );

		if ( isset( $screen->id ) && in_array( $screen->id, array( 'plugins' ), true ) ) {
			wp_enqueue_style( 'everest-forms-license' );
		}
	}

	/**
	 * Process plugin requests.
	 */
	private function plugin_requests() {
		// @codingStandardsIgnoreStart
		if ( ! empty( $_POST[ $this->plugin_slug . '_license_key' ] ) ) {
			$this->activate_license_request();
		} elseif ( ! empty( $_GET[ $this->plugin_slug . '_deactivate_license' ] ) ) {
			$this->deactivate_license_request();
		} elseif ( ! empty( $_GET[ 'dismiss-' . sanitize_title( $this->plugin_slug ) ] ) ) {
			update_option( $this->plugin_slug . '_hide_key_notice', 1 );
		} elseif ( ! empty( $_GET['activated_license'] ) && $_GET['activated_license'] === $this->plugin_slug ) {
			$this->add_notice( array( $this, 'activated_key_notice' ) );
		} elseif ( ! empty( $_GET['deactivated_license'] ) && $_GET['deactivated_license'] === $this->plugin_slug ) {
			$this->add_notice( array( $this, 'deactivated_key_notice' ) );
		}
		// @codingStandardsIgnoreEnd
	}

	private function activate_license_request() {
		$license_key = sanitize_text_field( wp_unslash( $_POST[ $this->plugin_slug . '_license_key' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( $this->activate_license( $license_key ) ) {
			wp_safe_redirect( remove_query_arg( array( 'deactivated_license', $this->plugin_slug . '_deactivate_license' ), add_query_arg( 'activated_license', $this->plugin_slug ) ) );
			exit;
		} else {
			wp_safe_redirect( remove_query_arg( array( 'activated_license', 'deactivated_license', $this->plugin_slug . '_deactivate_license' ) ) );
			exit;
		}
	}

	private function deactivate_license_request() {
		$this->deactivate_license();
		delete_transient( 'evf_pro_license_plan' );
		wp_safe_redirect( remove_query_arg( array( 'activated_license', $this->plugin_slug . '_deactivate_license' ), add_query_arg( 'deactivated_license', $this->plugin_slug ) ) );
		exit;
	}

	private function plugin_license_view() {
		$this->add_notice( array( $this, 'key_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'plugin_action_links' ) );
		add_action( 'admin_notices', array( $this, 'error_notices' ) );
	}

	private function add_notice( $callback ) {
		add_action( 'admin_notices', $callback );
		add_action( 'network_admin_notices', $callback );
	}

	public function add_error( $message, $type = '' ) {
		if ( $type ) {
			$this->errors[ $type ] = $message;
		} else {
			$this->errors[] = $message;
		}
	}

	public function load_errors() {
		$this->errors = get_option( $this->plugin_slug . '_errors', array() );
	}

	public function store_errors() {
		if ( count( $this->errors ) > 0 ) {
			update_option( $this->plugin_slug . '_errors', $this->errors );
		} else {
			delete_option( $this->plugin_slug . '_errors' );
		}
	}

	public function error_notices() {
		if ( ! empty( $this->errors ) ) {
			foreach ( $this->errors as $key => $error ) {
				include __DIR__ . '/updater/views/html-notice-error.php';
				if ( 'invalid_key' !== $key && did_action( 'all_admin_notices' ) ) {
					unset( $this->errors[ $key ] );
				}
			}
		}
	}

	public function plugin_activation() {
		delete_option( $this->plugin_slug . '_hide_key_notice' );

		wp_clear_scheduled_hook( 'everest_forms_cleanup_old_entries' );
		wp_clear_scheduled_hook( 'everest_forms_cleanup_old_api_logs' );

		wp_schedule_event( time() + ( 24 * HOUR_IN_SECONDS ), 'daily', 'everest_forms_cleanup_old_entries' );
		wp_schedule_event( time() + ( 24 * HOUR_IN_SECONDS ), 'daily', 'everest_forms_cleanup_old_api_logs' );
	}

	public function plugin_deactivation() {
		wp_clear_scheduled_hook( 'everest_forms_cleanup_old_entries' );
		wp_clear_scheduled_hook( 'everest_forms_cleanup_old_api_logs' );
	}

	public function plugin_action_links( $actions ) {
		$new_actions = array(
			'evf_settings_page' => '<a href="' . esc_url( admin_url( 'admin.php?page=evf-settings' ) ) . '">' . __( 'Settings', 'everest-forms-pro' ) . '</a>',
		);

		if ( ! $this->api_key ) {
			$new_actions['activate_license_settings'] = '<a href="' . esc_url( admin_url( 'admin.php?page=evf-settings&tab=license' ) ) . '">' . __( 'Activate License', 'everest-forms-pro' ) . '</a>';
		} else {
			$new_actions['deactivate_license'] = '<a href="' . remove_query_arg( array( 'deactivated_license', 'activated_license' ), add_query_arg( $this->plugin_slug . '_deactivate_license', 1 ) ) . '" class="deactivate-license" style="color: #a00;" title="' . esc_attr( __( 'Deactivate License Key', 'everest-forms-pro' ) ) . '">' . __( 'Deactivate License', 'everest-forms-pro' ) . '</a>';
		}

		return array_merge( $actions, $new_actions );
	}

	public function activate_license( $license_key ) {
		try {
			if ( empty( $license_key ) ) {
				throw new Exception( 'Please enter your license key' );
			}

			$activate_results = json_decode(
				EVF_Updater_Key_API::activate(
					array(
						'license' => $license_key,
					)
				)
			);

			update_option( $this->plugin_slug . '_license_active', $activate_results );

			if ( ! empty( $activate_results ) && is_object( $activate_results ) ) {
				if ( isset( $activate_results->error_code ) ) {
					throw new Exception( $activate_results->error );
				} elseif ( false === $activate_results->success ) {
					switch ( $activate_results->error ) {
						case 'expired':
							$error_msg = wp_kses_post( sprintf( __( 'The provided license key expired on %1$s. Please <a href="%2$s" target="_blank">renew your license key</a>.', 'everest-forms-pro' ), date_i18n( get_option( 'date_format' ), strtotime( $activate_results->expires, time() ) ), 'https://wpeverest.com/checkout/?edd_license_key=' . $license_key . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired' ) );
							break;
						case 'revoked':
							$error_msg = wp_kses_post( sprintf( __( 'The provided license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'everest-forms-pro' ), 'https://everestforms.net/contact/?utm_campaign=admin&utm_source=licenses&utm_medium=revoked' ) );
							break;
						case 'missing':
							$error_msg = wp_kses_post( sprintf( __( 'The provided license is invalid. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'everest-forms-pro' ), 'https://wpeverest.com/my-account?utm_campaign=admin&utm_source=licenses&utm_medium=missing' ) );
							break;
						case 'invalid':
						case 'site_inactive':
							$error_msg = wp_kses_post( sprintf( __( 'The provided license is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'everest-forms-pro' ), 'https://wpeverest.com/my-account?utm_campaign=admin&utm_source=licenses&utm_medium=missing' ) );
							break;
						case 'invalid_item_id':
						case 'item_name_mismatch':
							$error_msg = wp_kses_post( sprintf( __( 'This appears to be an invalid license key for <strong>%1$s</strong>.', 'everest-forms-pro' ), $this->plugin_data['Name'] ) );
							break;
						case 'no_activations_left':
							$error_msg = wp_kses_post( sprintf( __( 'The provided license key has reached its activation limit. Please <a href="%1$s" target="_blank">View possible upgrades</a> now.', 'everest-forms-pro' ), 'https://wpeverest.com/my-account/' ) );
							break;
						case 'license_not_activable':
							$error_msg = esc_html__( 'The key you entered belongs to a bundle, please use the product specific license key.', 'everest-forms-pro' );
							break;
						default:
							$error_msg = wp_kses_post( sprintf( __( 'The provided license key could not be found. Please <a href="%s" target="_blank">contact support</a> for more information.', 'everest-forms-pro' ), 'https://everestforms.net/contact/' ) );
							break;
					}

					throw new Exception( wp_kses_post( sprintf( __( '<strong>Activation error:</strong> %1$s', 'everest-forms-pro' ), $error_msg ) ) );

				} elseif ( 'valid' === $activate_results->license ) {
					$this->api_key = $license_key;
					$this->errors  = array();

					update_option( $this->plugin_slug . '_license_key', $this->api_key );
					delete_option( $this->plugin_slug . '_errors' );

					delete_transient( 'everest_forms_addon_updater' );
					delete_transient( 'everest_forms_addon_updater_lock' );

					return true;
				}

				throw new Exception( esc_html__( 'License could not activate. Please contact support.', 'everest-forms-pro' ) );
			} else {
				throw new Exception( esc_html__( 'Connection failed to the License Key API server - possible server issue.', 'everest-forms-pro' ) );
			}
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return false;
		}
	}

	public function deactivate_license() {
		EVF_Updater_Key_API::deactivate(
			array(
				'license' => $this->api_key,
			)
		);

		delete_option( $this->plugin_slug . '_errors' );
		delete_option( $this->plugin_slug . '_license_key' );
		delete_option( $this->plugin_slug . '_license_active' );
		delete_option( 'everest_forms_license_activation_shown' );

		delete_transient( 'everest_forms_addon_updater' );
		delete_transient( 'everest_forms_addon_updater_lock' );

		$this->errors  = array();
		$this->api_key = '';
	}

	public function key_notice() {
		if ( count( $this->errors ) === 0 && empty( get_option( 'everest-forms-pro_license_key', '' ) ) ) {
			include __DIR__ . '/updater/views/html-notice-key-unvalidated.php';
		}
	}

	public function activated_key_notice() {
		$message_shown = get_option( 'everest_forms_license_activation_shown' );

		if ( 'yes' !== $message_shown ) {
			include __DIR__ . '/updater/views/html-notice-key-activated.php';
			update_option( 'everest_forms_license_activation_shown', 'yes' );
		}
	}

	public function deactivated_key_notice() {
		include __DIR__ . '/updater/views/html-notice-key-deactivated.php';
	}
}

if ( ! isset( $GLOBALS['evf_plugin_updater'] ) ) {
	$GLOBALS['evf_plugin_updater'] = new EVF_Plugin_Updater();
}
