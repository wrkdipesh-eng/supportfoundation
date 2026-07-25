<?php
/**
 * EverestForms Social Login Settings
 *
 * @package EverestForms\Admin
 * @version 1.7.9
 */

namespace EverestForms\Pro\Addons\UserRegistration\Settings;

use EverestForms\Pro\Addons\UserRegistration\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Settings_Social_Login.
 *
 * @since 1.7.9
 */
class EVF_Settings_Social_Login {

	/**
	 * Constructor.
	 *
	 * @since 1.7.9
	 */
	public function __construct() {
		$this->maybe_migrate_legacy_settings();
		add_action( 'admin_head', array( $this, 'output_initial_visibility_styles' ) );
	}

	/**
	 * Outputs an inline <style> block into <head> that hides elements.
	 *
	 * @since 1.7.9
	 */
	public function output_initial_visibility_styles() {

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

	/**
	 * Migrates legacy social login settings to the new accordion format.
	 *
	 * @since 1.7.9
	 */
	private function maybe_migrate_legacy_settings() {
		if ( get_option( 'everest_forms_social_login_migration_v2_complete', false ) ) {
			return;
		}

		$legacy_map = array(
			'google'   => 'everest_forms_social_setting_enable_google_connect',
			'facebook' => 'everest_forms_social_setting_enable_facebook_connect',
			'linkedin' => 'everest_forms_social_setting_enable_linkedin_connect',
		);

		foreach ( $legacy_map as $provider => $old_option ) {
			$old_value  = get_option( $old_option, '' );
			$is_enabled = ( 'yes' === $old_value || '1' === $old_value || true === $old_value ) ? 'yes' : 'no';
			update_option( 'everest_forms_social_login_' . $provider . '_accordion_enabled', $is_enabled );
		}

		update_option( 'everest_forms_social_login_migration_v2_complete', true );
	}

	/**
	 * Get settings array.
	 *
	 * @since 1.7.9
	 *
	 * @return array
	 */
	public function get_settings() {
		$google_enabled   = 'yes' === get_option( 'everest_forms_social_setting_enable_google_connect', '' );
		$facebook_enabled = 'yes' === get_option( 'everest_forms_social_setting_enable_facebook_connect', '' );
		$linkedin_enabled = 'yes' === get_option( 'everest_forms_social_setting_enable_linkedin_connect', '' );

		$accordion_items = array(
			array(
				'title'      => esc_html__( 'Google', 'everest-forms-pro' ),
				'icon'       => plugins_url( 'src/Addons/UserRegistration/assets/images/google.png', EFP_PLUGIN_FILE ),
				'is_enabled' => $google_enabled,
				'fields'     => array(
					array(
						'title'    => __( 'Enable Google Login', 'everest-forms-pro' ),
						'desc'     => __( 'Toggle to activate Google login.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_enable_google_connect',
						'default'  => '',
						'type'     => 'toggle',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Google Client Id', 'everest-forms-pro' ),
						'desc'     => __( 'Google client id.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_google_client_id',
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Google Client Secret', 'everest-forms-pro' ),
						'desc'     => __( 'Google client secret.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_google_client_secret',
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Login With Google Text', 'everest-forms-pro' ),
						'desc'     => __( 'Login with google string.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_login_with_google_text',
						'default'  => 'Login With Google',
						'type'     => 'text',
						'desc_tip' => true,
					),
				),
			),
			array(
				'title'      => esc_html__( 'Facebook', 'everest-forms-pro' ),
				'icon'       => plugins_url( 'src/Addons/UserRegistration/assets/images/facebook.png', EFP_PLUGIN_FILE ),
				'is_enabled' => $facebook_enabled,
				'fields'     => array(
					array(
						'title'    => __( 'Enable Facebook Login', 'everest-forms-pro' ),
						'desc'     => __( 'Toggle to activate Facebook login.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_enable_facebook_connect',
						'default'  => '',
						'type'     => 'toggle',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Facebook App Id', 'everest-forms-pro' ),
						'desc'     => __( 'Facebook app id.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_facebook_app_id',
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Facebook App Secret', 'everest-forms-pro' ),
						'desc'     => __( 'Facebook app secret.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_facebook_app_secret',
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Login With Facebook Text', 'everest-forms-pro' ),
						'desc'     => __( 'Login with facebook string.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_login_with_facebook_text',
						'default'  => 'Login With Facebook',
						'type'     => 'text',
						'desc_tip' => true,
					),
				),
			),
			array(
				'title'      => esc_html__( 'LinkedIn', 'everest-forms-pro' ),
				'icon'       => plugins_url( 'src/Addons/UserRegistration/assets/images/linkedin.png', EFP_PLUGIN_FILE ),
				'is_enabled' => $linkedin_enabled,
				'fields'     => array(
					array(
						'title'    => __( 'Enable LinkedIn Login', 'everest-forms-pro' ),
						'desc'     => __( 'Toggle to activate LinkedIn login.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_enable_linkedin_connect',
						'default'  => '',
						'type'     => 'toggle',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'LinkedIn Client Id', 'everest-forms-pro' ),
						'desc'     => __( 'LinkedIn client id.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_linkedin_client_id',
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'LinkedIn Client Secret', 'everest-forms-pro' ),
						'desc'     => __( 'LinkedIn client secret.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_setting_linkedin_client_secret',
						'default'  => '',
						'type'     => 'text',
						'desc_tip' => true,
					),
					array(
						'title'    => __( 'Login With LinkedIn Text', 'everest-forms-pro' ),
						'desc'     => __( 'Login with LinkedIn string.', 'everest-forms-pro' ),
						'id'       => 'everest_forms_social_login_with_linkedin_text',
						'default'  => 'Login With Linkedin',
						'type'     => 'text',
						'desc_tip' => true,
					),
				),
			),
		);

		return apply_filters(
			'everest_forms_user_registration_settings',
			array(
				array(
					'title' => esc_html__( 'User Registration', 'everest-forms-pro' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'social_login',
				),
				array(
					'title'    => __( 'Enable Social Login', 'everest-forms-pro' ),
					'desc'     => __( 'Toggle to activate social login.', 'everest-forms-pro' ),
					'id'       => 'everest_forms_social_setting_enable_social_registration',
					'default'  => '',
					'type'     => 'toggle',
					'desc_tip' => true,
				),
				array(
					'title'    => __( 'Enable Multi Social Login', 'everest-forms-pro' ),
					'desc'     => __( 'Tick here if you want to Register once and log in with any connected social media.', 'everest-forms-pro' ),
					'id'       => 'everest_forms_social_setting_multi_social_login',
					'default'  => '',
					'type'     => 'toggle',
					'desc_tip' => true,
				),
				array(
					'title'    => __( 'Default User Role', 'everest-forms-pro' ),
					'desc'     => __( 'This option lets you choose user role for social registration.', 'everest-forms-pro' ),
					'id'       => 'everest_forms_social_setting_default_user_role',
					'default'  => 'subscriber',
					'type'     => 'select',
					'class'    => 'evf-enhanced-select',
					'desc_tip' => true,
					'options'  => Helper::evfur_get_default_admin_roles(),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'social_login',
				),
				array(
					'type'  => 'accordion',
					'items' => $accordion_items,
				),
			)
		);
	}
}
