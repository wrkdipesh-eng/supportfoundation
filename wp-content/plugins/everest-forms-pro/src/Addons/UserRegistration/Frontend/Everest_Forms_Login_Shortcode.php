<?php
/**
 * Everest Forms Login shortcode
 *
 * @since   1.0.0
 * @package EverestFormsUserRegistration\Admin
 */

namespace EverestForms\Pro\Addons\UserRegistration\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Login Shortcode class.
 */
class Everest_Forms_Login_Shortcode {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'load_shortcode' ), 99 );
		add_filter( 'evf_get_template', array( $this, 'template_location' ), 99, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
	}

	/**
	 * Frontend script load.
	 *
	 * @since 1.7.9
	 */
	public function frontend_scripts() {
		wp_enqueue_style( 'everest-forms-user-registration-frontend', plugins_url( 'src\Addons\UserRegistration\assets\css\frontend\everest-forms-user-registration.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
	}

	/**
	 * Load shortcode.
	 *
	 * @since 1.0.0
	 */
	public function load_shortcode() {
		add_shortcode( apply_filters( 'everest_forms_user_login_shortcode_tag', 'everest_forms_user_login' ), array( $this, 'user_login' ) );
	}

	/**
	 * User Login.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attr Login attributes.
	 */
	public function user_login( $attr ) {
		ob_start();

		if ( is_user_logged_in() ) {
			echo '<p class="everest-forms-notice everest-forms-notice--error">' . esc_html__( 'You are already logged in. Please logout to register New User.', 'everest-forms-user-registration' ) . ' <a href="' . esc_url_raw( wp_logout_url( get_permalink() ) ) . '">' . esc_html__( 'Logout', 'everest-forms-user-registration' ) . '</a></p>';
		} else {

			$nonce = wp_create_nonce( 'evf-user-login' );

			$redirect_url = get_home_url();

			if ( isset( $attr['redirect_url'] ) ) {
				if ( filter_var( $attr['redirect_url'], FILTER_VALIDATE_URL ) ) {
					$redirect_url = $attr['redirect_url'];

				} else {
					$redirect_url = home_url() . '/' . ltrim( $attr['redirect_url'], '/' );
				}
			}

			$recaptcha = $this->get_recaptcha_node( isset( $attr['recaptcha'] ) ? $attr['recaptcha'] : false );

			evf_get_template(
				'user-login.php',
				array(
					'nonce'            => $nonce,
					'redirect_url'     => $redirect_url,
					'recaptcha_status' => isset( $attr['recaptcha'] ) ? $attr['recaptcha'] : false,
					'recaptcha_node'   => $recaptcha,
				)
			);
		}
		$output = ob_get_clean();
		return $output;
	}


	/**
	 * Change Template Location.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $located path to template.
	 * @param mixed $template_name name of template.
	 */
	public function template_location( $located, $template_name ) {
		if ( 'user-login.php' === $template_name ) {
			$located = plugin_dir_path( EFP_PLUGIN_FILE ) . 'src/Addons/UserRegistration/templates/user-login.php';
		}
		return $located;
	}

	/**
	 * Recaptcha for login form.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $enable_recaptcha recaptcha status.
	 */
	public function get_recaptcha_node( $enable_recaptcha ) {
		$recaptcha_node = '';

		if ( ! $enable_recaptcha ) {
			return $recaptcha_node;
		}
		$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );

		$enable_recaptcha = true;

		if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
		} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_invisible_secret_key' );
		} elseif ( 'v3' === $recaptcha_type ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
		} elseif ( 'hcaptcha' === $recaptcha_type ) {
			$site_key   = get_option( 'everest_forms_recaptcha_hcaptcha_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_hcaptcha_secret_key' );
		}

		if ( ! $site_key || ! $secret_key ) {
			return '';
		}

		if ( $enable_recaptcha ) {
			$data = array(
				'sitekey' => trim( sanitize_text_field( $site_key ) ),
			);

			// Load reCAPTCHA support if form supports it.
			if ( $site_key && $secret_key ) {

				if ( 'v2' === $recaptcha_type ) {
					$recaptcha_api = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?onload=EVFRecaptchaLoad&render=explicit', $recaptcha_type );

					if ( 'yes' === $invisible_recaptcha ) {
						$data['size']     = 'invisible';
						$recaptcha_inline = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID = grecaptcha.render(el,{},true); grecaptcha.execute(recaptchaID);});};';
					} else {
						$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID =  grecaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);jQuery(el).attr( "data-recaptcha-id", recaptchaID);});};';
						$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".evf-recaptcha-hidden").val("1").trigger("change").valid();};';
					}
				} elseif ( 'v3' === $recaptcha_type ) {
					$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, $recaptcha_type );
					$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){grecaptcha.execute("' . esc_html( $site_key ) . '",{action:"everest_form"}).then(function(token){var f=document.getElementsByName("everest_forms[recaptcha]");for(var i=0;i<f.length;i++){f[i].value = token;}});};grecaptcha.ready(EVFRecaptchaLoad);setInterval(EVFRecaptchaLoad, 110000);';
					$recaptcha_inline .= 'grecaptcha.ready(function(){grecaptcha.execute("' . esc_html( $site_key ) . '",{action:"everest_form"}).then(function(token){var f=document.getElementsByName("everest_forms[recaptcha]");for(var i=0;i<f.length;i++){f[i].value = token;}});});';
				} elseif ( 'hcaptcha' === $recaptcha_type ) {
					$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://hcaptcha.com/1/api.js??onload=EVFRecaptchaLoad&render=explicit', $recaptcha_type );
					$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID =  hcaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);jQuery(el).attr( "data-recaptcha-id", recaptchaID);});};';
					$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".evf-recaptcha-hidden").val("1").trigger("change").valid();};';
				}

				// Enqueue reCaptcha scripts.
				wp_enqueue_script(
					'evf-recaptcha',
					$recaptcha_api,
					'v3' === $recaptcha_type ? array() : array( 'jquery' ),
					'v3' === $recaptcha_type ? '3.0.0' : '2.0.0',
					true
				);

				// Load reCaptcha callback once.
				static $count = 1;
				if ( 1 === $count ) {
					wp_add_inline_script( 'evf-recaptcha', $recaptcha_inline );
					++$count;
				}

				// Output the reCAPTCHA container.
				$class = ( 'v3' === $recaptcha_type || ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) ) ? 'recaptcha-hidden' : '';
				if ( 'v2' === $recaptcha_type || 'hcaptcha' === $recaptcha_type ) {
					$recaptcha_node .= '<div class="evf-recaptcha-container ' . esc_attr( $class ) . '">';
					$recaptcha_node .= '<div ' . evf_html_attributes( '', array( 'g-recaptcha' ), $data ) . '></div>';

					if ( 'hcaptcha' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
						$recaptcha_node .= '<input type="text" name="g-recaptcha-hidden" class="evf-recaptcha-hidden" style="position:absolute!important;clip:rect(0,0,0,0)!important;height:1px!important;width:1px!important;border:0!important;overflow:hidden!important;padding:0!important;margin:0!important;" required>';
					}

					$recaptcha_node .= '</div>';
				} else {
					$recaptcha_node .= '<div class="evf-recaptcha-container ' . esc_attr( $class ) . '">';
					$recaptcha_node .= '<input type="hidden" name="everest_forms[recaptcha]" value="">';
					$recaptcha_node .= '</div>';
				}
			}
		}

		return $recaptcha_node;
	}
}
