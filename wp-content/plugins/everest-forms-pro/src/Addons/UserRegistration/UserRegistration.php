<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\UserRegistration
 *
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\UserRegistration;

use EverestForms\Pro\Addons\UserRegistration\Builder\EVFUR_Form_Settings;
use EverestForms\Pro\Addons\UserRegistration\Frontend\Everest_Forms_Login_Shortcode;
use EverestForms\Pro\Addons\UserRegistration\Frontend\EVF_User_Activation;
use EverestForms\Pro\Addons\UserRegistration\Process\EVF_User_Registration_Process;

/**
 * Main plugin class.
 *
 * @since 1.7.9
 */
class UserRegistration {

	public function __construct() {
		$this->init();
		$this->hook_inits();
	}

	public function init() {
		if ( is_admin() ) {
			new EVFUR_Form_Settings();
		}

		new EVF_User_Activation();
		new EVF_User_Registration_Process();
		new Everest_Forms_Login_Shortcode();
		$this->load_abstract_classes();
	}

	public function hook_inits() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'admin_head', array( $this, 'output_initial_visibility_styles' ) );
			add_filter( 'everest_forms_misc_settings', array( $this, 'general_settings' ), 10, 1 );
			add_filter( 'everest_forms_utilities_sections', array( $this, 'register_utilities_section' ) );
			add_filter( 'everest_forms_utilities_settings_user_registration', array( $this, 'get_utilities_settings' ) );
		}
		add_action( 'everest_forms_frontend_output', array( $this, 'append_social_login_button_for_user_registration' ), 99 );
	}

	public function output_initial_visibility_styles() {
		$screen = get_current_screen();

		if ( ! $screen || 'everest-forms_page_evf-settings' !== $screen->id ) {
			return;
		}

		$social_enabled   = 'yes' === get_option( 'everest_forms_social_setting_enable_social_registration', '' );
		$google_enabled   = 'yes' === get_option( 'everest_forms_social_setting_enable_google_connect', '' );
		$facebook_enabled = 'yes' === get_option( 'everest_forms_social_setting_enable_facebook_connect', '' );
		$linkedin_enabled = 'yes' === get_option( 'everest_forms_social_setting_enable_linkedin_connect', '' );

		$rules = array();

		if ( ! $social_enabled ) {
			$rules[] = '.everest-forms-settings-title_social_login ~ .everest-forms-card .everest-forms-global-settings:has(#everest_forms_social_setting_multi_social_login),
						.everest-forms-settings-title_social_login ~ .everest-forms-card .everest-forms-global-settings:has(#everest_forms_social_setting_default_user_role),
						.everest-forms-settings-title_social_login ~ .everest-forms-accordion-wrapper { display: none; }';
		}

		if ( ! $google_enabled ) {
			$rules[] = '.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_setting_google_client_id),
						.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_setting_google_client_secret),
						.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_login_with_google_text) { display: none; }';
		}

		if ( ! $facebook_enabled ) {
			$rules[] = '.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_setting_facebook_app_id),
						.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_setting_facebook_app_secret),
						.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_login_with_facebook_text) { display: none; }';
		}

		if ( ! $linkedin_enabled ) {
			$rules[] = '.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_setting_linkedin_client_id),
						.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_setting_linkedin_client_secret),
						.everest-forms-accordion-wrapper .everest-forms-global-settings:has(#everest_forms_social_login_with_linkedin_text) { display: none; }';
		}

		if ( empty( $rules ) ) {
			return;
		}

		echo '<style id="evf-social-login-init-visibility">' . implode( "\n", $rules ) . '</style>';
	}

	public function register_utilities_section( $sections ) {
		$sections['user_registration'] = __( 'User Registration', 'everest-forms-pro' );
		return $sections;
	}

	public function get_utilities_settings( $settings ) {
		include_once __DIR__ . '/Settings/EVF_Settings_Social_Login.php';
		$social_login = new \EverestForms\Pro\Addons\UserRegistration\Settings\EVF_Settings_Social_Login();
		return $social_login->get_settings();
	}

	public function load_abstract_classes() {
		include_once __DIR__ . '/Abstract/EVF_Social_Networks.php';
	}

	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'everest-forms-user-registration-builder', plugins_url( "src/Addons/UserRegistration/assets/js/admin/admin{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-ui-sortable' ), EFP_VERSION, true );
		wp_localize_script(
			'everest-forms-user-registration-builder',
			'evf_user_registration_params',
			array(
				'i18n_disabled_message' => esc_html__( 'User registration is currently disabled. Please enable it to register users with Everest Forms.', 'everest-forms-user-registration' ),
			)
		);

		if ( 'everest-forms_page_evf-builder' === $screen_id || 'everest-forms_page_evf-settings' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-user-registration-builder' );
		}
	}

	public function general_settings( $settings ) {
		$new_settings = array(
			array(
				'title'    => esc_html__( 'Enable Forgot Password.', 'everest-forms' ),
				'desc'     => esc_html__( 'Enable forgot password.', 'everest-forms' ),
				'id'       => 'everest_forms_lost_password',
				'default'  => 'no',
				'type'     => 'toggle',
				'desc_tip' => true,
			),
		);

		// Insert before the last 'sectionend' item
		array_splice( $settings, count( $settings ) - 1, 0, $new_settings );

		return $settings;
	}

	public function append_social_login_button_for_user_registration( $form_data ) {
		$value = isset( $form_data['settings']['enable_user_registration'] ) ? $form_data['settings']['enable_user_registration'] : 0;

		if ( $value ) {
			$social_networks  = Helper::everest_forms_social_networks();
			$enabled_networks = array();

			foreach ( $social_networks as $network_key => $network_data ) {
				if ( 'yes' === get_option( 'everest_forms_social_setting_enable_social_registration' ) && 'yes' === get_option( $network_data['enable_id'] ) ) {
					$enabled_networks[ $network_key ] = $network_data;
				}
			}

			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';

			$url = ( ! empty( $_SERVER['HTTPS'] ) ) ? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			$formatted_url = substr( $url, 0, strpos( $url, '?' ) );

			$encoded_url = '';

			echo '<div class="everest-forms-social-connect-networks">';
			if ( count( $enabled_networks ) > 0 ) {
				?>
				<ul class="evfur-network-lists evfur_theme_4">
				<?php
				foreach ( $enabled_networks as $network_key => $network_data ) {
					$social_login_network_select = isset( $form_data['settings']['social_login_network_select'] ) ? $form_data['settings']['social_login_network_select'] : array();
					if ( empty( $social_login_network_select ) ) {
						continue;
					}
					if ( ! in_array( $network_key, $social_login_network_select ) ) {
						continue;
					}
					?>
					<li class="evfur-login-media evfur-login-media--<?php echo $network_key; ?>">
						<a href="<?php echo $formatted_url; ?>?everest_forms_social_login=<?php echo $network_key; ?>&evfur_action=login<?php echo $encoded_url ? '&state=' . base64_encode( "redirect_to=$encoded_url" ) : ''; ?>" title='
						<?php
						_e( 'Login with', 'everest-forms-pro' );
						echo ' ' . $network_key;
						?>
						'>
							<span class="evfur-icon-block icon-<?php echo $network_key; ?> evfur-login-with-<?php echo $network_key; ?>"></span>
							<span class="evfur-login-text"><?php echo esc_html( get_option( $network_data['login_text'] ) ); ?></span>
						</a>
					</li>
					<?php
				}
				?>
				</ul>
				<?php
				echo '</div>';
			}
		}
	}
}
