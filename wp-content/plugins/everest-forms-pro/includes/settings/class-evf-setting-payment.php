<?php
/**
 * EverestForms Payment Settings
 *
 * @package EverestForms\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Settings_Payment', false ) ) {
	return new EVF_Settings_Payment();
}

/**
 * EVF_Settings_Payment.
 */
class EVF_Settings_Payment extends EVF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'payment';
		$this->label = __( 'Payments', 'everest-forms-pro' );
		parent::__construct();
		add_action( 'everest_forms_sections_' . $this->id, array( $this, 'output_sections' ) );
	}

	/**
	 * Check if any payment gateways are available.
	 *
	 * @return bool
	 */
	private function has_payment_gateways() {
		$gateway_settings = apply_filters( 'everest_forms_payment_settings', array() );
		return ! empty( $gateway_settings );
	}

	/**
	 * Get sections for payment tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array();

		if ( $this->has_payment_gateways() ) {
			$sections['payment'] = __( 'Payment Method', 'everest-forms-pro' );
		}

		$sections['currency'] = __( 'Currency', 'everest-forms-pro' );

		return apply_filters( 'everest_forms_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output sections in navigation sidebar.
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
			return;
		}

		echo '<ul class="evf-subsections">';

		foreach ( $sections as $id => $label ) {
			$url = add_query_arg(
				array(
					'page'    => 'evf-settings',
					'tab'     => $this->id,
					'section' => sanitize_title( $id ),
				),
				admin_url( 'admin.php' )
			);

			// Default to currency if payment method is not available.
			$default_section = $this->has_payment_gateways() ? 'payment' : 'currency';
			$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : $default_section;

			echo '<li><a href="' . esc_url( $url ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a></li>';
		}

		echo '</ul>';
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		// Default to currency if payment method is not available.
		$default_section = $this->has_payment_gateways() ? 'payment' : 'currency';
		$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : $default_section;

		if ( 'currency' === $current_section ) {
			$settings = $this->get_currency_settings();
		} else {
			$settings = $this->get_payment_method_settings();
		}

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings, $current_section );
	}

	/**
	 * Get payment method settings (all payment gateways as accordions).
	 *
	 * @return array
	 */
	public function get_payment_method_settings() {
		$settings = array(
			array(
				'title' => __( 'Payment Method', 'everest-forms-pro' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'payment_method_options',
			),
		);

		$gateway_settings = apply_filters( 'everest_forms_payment_settings', array() );
		$settings         = array_merge( $settings, $gateway_settings );

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'payment_method_options',
		);

		return $settings;
	}

	/**
	 * Get currency settings (currency).
	 *
	 * @return array
	 */
	public function get_currency_settings() {
		$currencies       = evf_get_currencies();
		$currency_options = array();

		foreach ( $currencies as $code => $currency ) {
			$currency_options[ $code ] = sprintf( '%s (%s %s)', $currency['name'], $code, $currency['symbol'] );
		}

		$settings = array(
			array(
				'title' => __( 'Currency', 'everest-forms-pro' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'currency_options',
			),
			array(
				'title'    => __( 'Currency', 'everest-forms-pro' ),
				'desc'     => __( 'This controls which currency gateways will take payments in.', 'everest-forms-pro' ),
				'id'       => 'everest_forms_currency',
				'default'  => 'USD',
				'type'     => 'select',
				'class'    => 'evf-enhanced-select',
				'desc_tip' => true,
				'options'  => $currency_options,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'currency_options',
			),
		);

		return $settings;
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$settings       = $this->get_settings();
		$saved_currency = get_option( 'everest_forms_currency', 'USD' );

		if ( 'currency' === $current_section && class_exists( 'EverestForms_PayPal_Standard' ) && ! in_array( $saved_currency, paypal_supported_currencies_list() ) ) {
			$currency_url = 'https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/';
			echo '<div id="evf-currency-error" class="notice notice-warning is-dismissible"><p><strong>' . esc_html__( 'CURRENCY_NOT_SUPPORTED Currency Code :', 'everest-forms-pro' ) . '</strong> ' . esc_html( $saved_currency ) . esc_html__( ' is not currently supported by Paypal. Please Refer', 'everest-forms-pro' ) . ' <a href="' . esc_url( $currency_url ) . '" target="_blank">' . esc_html__( 'Paypal supported currencies', 'everest-forms-pro' ) . '</a></p></div>';
		}

		EVF_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		EVF_Admin_Settings::save_fields( $settings );
	}
}

return new EVF_Settings_Payment();
