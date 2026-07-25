<?php
/**
 * Coupon feature main class.
 *
 * @package EverestForms\Pro\Addons\Coupons.
 * @since   3.0.5
 */

namespace EverestForms\Pro\Addons\Coupons;

use EverestForms\Pro\Addons\Coupons\Process\Process;

/**
 * Coupon feature main class.
 *
 * @since 3.0.5
 */
class Coupons {
	/**
	 * Plugin Constructor.
	 *
	 * @since 3.0.5
	 */
	public function __construct() {
		// Enqueuing Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'frontend_enqueue_scripts' ) );


		$this->init();
	}

	/**
	 * Frontend Enqueue scripts.
	 */
	public function frontend_enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// Enqueue Scripts.
		wp_register_script( 'everest-forms-coupons-script', plugins_url( "src/Addons/Coupons/assets/js/frontend/everest-forms-coupons{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
		wp_localize_script(
			'everest-forms-coupons-script',
			'everest_forms_coupons_params',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'everest_forms_coupons_nonce' ),
				'error'    => esc_html__( 'Sorry, something went wrong. Please try again', 'everest-forms-pro' ),
				'i18n_already_applied' => esc_html__( 'Coupon is already applied', 'everest-forms-pro' ),
				'i18n_select_option_first' => esc_html__( 'Please select a payment option first', 'everest-forms-pro' ),
				'i18n_cannot_stack' => esc_html__( 'Another coupon cannot be applied because an applied coupon is not stackable.', 'everest-forms-pro' ),
				'i18n_not_stack_able' => esc_html__( 'This coupon cannot be applied with other coupons because it is not stackable', 'everest-forms-pro' ),
			)
		);
		wp_enqueue_script( 'everest-forms-coupons-script' );
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';



		// Register admin scripts.
		wp_register_script( 'everest-forms-coupons-builder', plugins_url( "src/Addons/Coupons/assets/js/admin/admin{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );

		wp_localize_script(
			'everest-forms-coupons-builder',
			'everest_forms_coupons_params',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php', 'relative' ),
				'ajax_nonce'           => wp_create_nonce( 'ajax_post_submission_nonce' ),
				'currency_info'        => $this->get_currency_info(),
				'i18n_disable_message' => esc_html__( 'Coupons is currently disabled. Please enable it to activate this feature with Everest Forms.', 'everest-forms-pro' ),
				'admin_url'            => admin_url(),
			)
		);

		// Admin scripts for EVF builder page.
		if ( 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-coupons-builder' );
			wp_enqueue_style( 'everest-forms-coupons-style', plugins_url( 'src/Addons/Coupons/assets/css/admin.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
		}

		// Admin scripts for EVF coupons page.
		if ( 'everest-forms_page_evf-coupons' === $screen_id ) {
			wp_enqueue_style( 'everest-forms-admin' );
			wp_enqueue_style( 'flatpickr' );

			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_script( 'flatpickr' );
			wp_enqueue_script( 'everest-forms-coupons-builder' );
			wp_enqueue_style( 'everest-forms-coupons-style', plugins_url( 'src/Addons/Coupons/assets/css/admin.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
		}
	}

	/**
	 * Get Currency Info.
	 *
	 * @return array
	 */
	public function get_currency_info() {
		$currency   = get_option( 'everest_forms_currency', 'USD' );
		$currencies = evf_get_currencies();

		$currency_info                        = array();
		$currency_info['currency']            = sanitize_text_field( $currency );
		$currency_info['currency_name']       = sanitize_text_field( $currencies[ $currency ]['name'] );
		$currency_info['currency_decimal']    = sanitize_text_field( $currencies[ $currency ]['decimal_separator'] );
		$currency_info['currency_thousands']  = sanitize_text_field( $currencies[ $currency ]['thousands_separator'] );
		$currency_info['currency_symbol']     = sanitize_text_field( $currencies[ $currency ]['symbol'] );
		$currency_info['currency_symbol_pos'] = sanitize_text_field( $currencies[ $currency ]['symbol_pos'] );

		return $currency_info;
	}

	public function init() {
		new Process();
	}
}
