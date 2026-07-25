<?php
/**
 * Authorize.Net Helper methods.
 *
 * @package EverestForms\AuthorizeNet.
 *
 * @since 1.0.0
 */

namespace EverestForms\Pro\Addons\Mollie;

/**
 * Helper methods for Authorize.Net.
 *
 * @since 1.0.0
 */
class Helpers {

	/**
	 * Get Authorize.Net mode for payment.
	 *
	 * @return string
	 */
	public static function get_mollie_mode() {
		return 'yes' === get_option( 'everest_forms_mollie_test_mode' ) ? 'test' : 'live';
	}

	/**
	 * Get Authorize.Net API login ID.
	 *
	 * @return string
	 */
	public static function get_mollie_api_key() {

		$mode = self::get_mollie_mode();

		return sanitize_text_field( get_option( "everest_forms_mollie_{$mode}_api_key" ) );
	}

	/**
	 * Check the Mollie is enable or not, if enabled return true otherwise false.
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public static function is_mollie_enabled( $form_data ) {
		$is_mollie_raw     = isset( $form_data['payments']['mollie']['enable_mollie'] ) ? $form_data['payments']['mollie']['enable_mollie'] : '0';
		$is_mollie_enabled = ( '1' === $is_mollie_raw || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) ) ) ) ? '1' : '0';
		$is_paypal_raw     = isset( $form_data['payments']['paypal']['enable_paypal'] ) ? $form_data['payments']['paypal']['enable_paypal'] : '0';
		$is_paypal_enabled = ( '1' === $is_paypal_raw || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'paypal' ) ) ) ) ? '1' : '0';
		$is_stripe_raw     = isset( $form_data['payments']['stripe']['enable_stripe'] ) ? $form_data['payments']['stripe']['enable_stripe'] : '0';
		$is_stripe_enabled = ( '1' === $is_stripe_raw || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'stripe' ) ) ) ) ? '1' : '0';
		$is_authorize_net_raw     = isset( $form_data['payments']['authorize_net']['enable_authorize_net'] ) ? $form_data['payments']['authorize_net']['enable_authorize_net'] : '0';
		$is_authorize_net_enabled = ( '1' === $is_authorize_net_raw || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'authorize_net' ) ) ) ) ? '1' : '0';
		$is_razorpay_raw          = isset( $form_data['payments']['razorpay']['enable_razorpay'] ) ? $form_data['payments']['razorpay']['enable_razorpay'] : '0';
		$is_razorpay_enabled      = ( '1' === $is_razorpay_raw || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'razorpay' ) ) ) ) ? '1' : '0';
		$is_square_raw     = isset( $form_data['payments']['square']['enable_square'] ) ? $form_data['payments']['square']['enable_square'] : '0';
		$is_square_enabled = ( '1' === $is_square_raw || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) ) ) ) ? '1' : '0';
		$gateway_choice           = function_exists( 'evf_payment_gateway_selector_get_posted_choice' ) ? evf_payment_gateway_selector_get_posted_choice( $form_data ) : null;

		if ( '1' !== $is_mollie_enabled ) {
			return false;
		}

		// If gateway selector is present and user selected a gateway, run Mollie only for Mollie choice.
		if ( null !== $gateway_choice && '' !== $gateway_choice ) {
			return 'mollie' === $gateway_choice;
		}

		if ( '1' === $is_mollie_enabled && '0' === $is_paypal_enabled && '0' === $is_stripe_enabled && '0' === $is_authorize_net_enabled && '0' === $is_razorpay_enabled && '0' === $is_square_enabled ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates the API credentials.
	 *
	 * @return bool True if the API credentials are valid, false otherwise.
	 */
	public static function validate_api_credentials() {

		return ! empty( self::get_mollie_api_key() );
	}

	/**
	 * Retrieves the redirection url.
	 *
	 * @param array $form_data Form Data.
	 *
	 * @return string redirection url.
	 */
	public static function get_mollie_redirection_url( $form_data ) {
		return ! empty( $form_data['payments']['mollie']['redirect_url'] ) ? sanitize_text_field( $form_data['payments']['mollie']['redirect_url'] ) : home_url();
	}

	/**
	 * Retrieves the webhook url.
	 *
	 * @param array $form_data Form Data.
	 *
	 * @return string webhook url.
	 */
	public static function get_mollie_webhook_url( $form_data ) {
		return isset( $form_data['payments']['mollie']['webhook_url'] ) ? sanitize_text_field( $form_data['payments']['mollie']['webhook_url'] ) : '';
	}
}
