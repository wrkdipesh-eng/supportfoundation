<?php
/**
 * Payment related functions.
 *
 * @package    Everest Forms Pro
 */

/**
 * Get supported currencies.
 *
 * @since 1.2.4
 *
 * @return array
 */
function evf_get_currencies() {
	$currencies = array(
		'USD' => array(
			'name'                => esc_html__( 'U.S. Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'GBP' => array(
			'name'                => esc_html__( 'Pound Sterling', 'everest-forms-pro' ),
			'symbol'              => '&pound;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'EUR' => array(
			'name'                => esc_html__( 'Euro', 'everest-forms-pro' ),
			'symbol'              => '&euro;',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'AUD' => array(
			'name'                => esc_html__( 'Australian Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'BRL' => array(
			'name'                => esc_html__( 'Brazilian Real', 'everest-forms-pro' ),
			'symbol'              => 'R$',
			'symbol_pos'          => 'left',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'CAD' => array(
			'name'                => esc_html__( 'Canadian Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'CZK' => array(
			'name'                => esc_html__( 'Czech Koruna', 'everest-forms-pro' ),
			'symbol'              => '&#75;&#269;',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'DKK' => array(
			'name'                => esc_html__( 'Danish Krone', 'everest-forms-pro' ),
			'symbol'              => 'kr.',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'HKD' => array(
			'name'                => esc_html__( 'Hong Kong Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'right',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'HUF' => array(
			'name'                => esc_html__( 'Hungarian Forint', 'everest-forms-pro' ),
			'symbol'              => 'Ft',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'ILS' => array(
			'name'                => esc_html__( 'Israeli New Sheqel', 'everest-forms-pro' ),
			'symbol'              => '&#8362;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'MYR' => array(
			'name'                => esc_html__( 'Malaysian Ringgit', 'everest-forms-pro' ),
			'symbol'              => '&#82;&#77;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'MXN' => array(
			'name'                => esc_html__( 'Mexican Peso', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'NOK' => array(
			'name'                => esc_html__( 'Norwegian Krone', 'everest-forms-pro' ),
			'symbol'              => 'Kr',
			'symbol_pos'          => 'left',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'NZD' => array(
			'name'                => esc_html__( 'New Zealand Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'PHP' => array(
			'name'                => esc_html__( 'Philippine Peso', 'everest-forms-pro' ),
			'symbol'              => 'Php',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'PLN' => array(
			'name'                => esc_html__( 'Polish Zloty', 'everest-forms-pro' ),
			'symbol'              => '&#122;&#322;',
			'symbol_pos'          => 'left',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'RUB' => array(
			'name'                => esc_html__( 'Russian Ruble', 'everest-forms-pro' ),
			'symbol'              => 'pyб',
			'symbol_pos'          => 'right',
			'thousands_separator' => ' ',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'SGD' => array(
			'name'                => esc_html__( 'Singapore Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'ZAR' => array(
			'name'                => esc_html__( 'South African Rand', 'everest-forms-pro' ),
			'symbol'              => 'R',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'SEK' => array(
			'name'                => esc_html__( 'Swedish Krona', 'everest-forms-pro' ),
			'symbol'              => 'Kr',
			'symbol_pos'          => 'right',
			'thousands_separator' => '.',
			'decimal_separator'   => ',',
			'decimals'            => 2,
		),
		'CHF' => array(
			'name'                => esc_html__( 'Swiss Franc', 'everest-forms-pro' ),
			'symbol'              => 'CHF',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'TWD' => array(
			'name'                => esc_html__( 'Taiwan New Dollar', 'everest-forms-pro' ),
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'THB' => array(
			'name'                => esc_html__( 'Thai Baht', 'everest-forms-pro' ),
			'symbol'              => '&#3647;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
		'JPY' => array(
			'name'                => esc_html__( 'Japanese yen', 'everest-forms-pro' ),
			'symbol'              => '&yen;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		),
	);

	return apply_filters( 'everest_forms_currencies', $currencies );
}

/**
 * Paypal supported currencies.
 *
 * @since 1.6.5
 * From https://developer.paypal.com/docs/reports/reference/paypal-supported-currencies/.
 *
 * @return array
 */
if ( ! function_exists( 'paypal_supported_currencies_list' ) ) {

	function paypal_supported_currencies_list() {

		return array(
			'AUD',
			'BRL',
			'CAD',
			'CNY',
			'CZK',
			'DKK',
			'EUR',
			'HKD',
			'HUF',
			'ILS',
			'JPY',
			'MYR',
			'MXN',
			'TWD',
			'NZD',
			'NOK',
			'PHP',
			'PLN',
			'GBP',
			'RUB',
			'SGD',
			'SEK',
			'CHF',
			'THB',
			'USD',

		);
	}
}

/**
 * Sanitize Amount.
 *
 * Returns a sanitized amount by stripping out thousands separators.
 *
 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/formatting.php#L24
 *
 * @param string $amount   Amount.
 * @param string $currency Currency usage.
 *
 * @return string $amount
 */
function evf_sanitize_amount( $amount, $currency = '' ) {
	if ( empty( $currency ) ) {
		$currency = get_option( 'everest_forms_currency', 'USD' );
	}

	$currency      = strtoupper( $currency );
	$currencies    = evf_get_currencies();
	$thousands_sep = isset( $currencies[ $currency ]['thousands_separator'] ) ? $currencies[ $currency ]['thousands_separator'] : ',';
	$decimal_sep   = isset( $currencies[ $currency ]['decimal_separator'] ) ? $currencies[ $currency ]['decimal_separator'] : '.';
	$is_negative   = false;

	// Sanitize the amount.
	// @codingStandardsIgnoreStart
	if ( is_string( $amount ) ) {
		if ( ',' === $decimal_sep && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
			if ( ( $thousands_sep === '.' || $thousands_sep === ' ' ) && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
				$amount = str_replace( $thousands_sep, '', $amount );
			} elseif ( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
				$amount = str_replace( '.', '', $amount );
			}
			$amount = str_replace( $decimal_sep, '.', $amount );
		} elseif ( $thousands_sep === ',' && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( $thousands_sep, '', $amount );
		}
	}
	// @codingStandardsIgnoreEnd

	$amount   = preg_replace( '/[^0-9\.]/', '', $amount );
	$decimals = apply_filters( 'evf_sanitize_amount_decimals', 2, $amount );
	$amount   = number_format( (float) $amount, $decimals, '.', '' );

	if ( 0 > $amount ) {
		$is_negative = true;
	}

	if ( $is_negative ) {
		$amount *= - 1;
	}

	return $amount;
}

/**
 * Returns a nicely formatted amount.
 *
 * @since 1.2.6
 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/formatting.php#L83
 *
 * @param string  $amount   Amount.
 * @param boolean $symbol   Symbol padding.
 * @param string  $currency Currency.
 *
 * @return string $amount Newly formatted amount or Price Not Available
 */
function evf_format_amount( $amount, $symbol = false, $currency = '' ) {
	if ( empty( $currency ) ) {
		$currency = get_option( 'everest_forms_currency', 'USD' );
	}

	$currency      = strtoupper( $currency );
	$currencies    = evf_get_currencies();
	$thousands_sep = $currencies[ $currency ]['thousands_separator'];
	$decimal_sep   = $currencies[ $currency ]['decimal_separator'];
	$sep_found     = ! empty( $decimal_sep ) ? strpos( $amount, $decimal_sep ) : false;

	// Format the amount.
	if ( ',' === $decimal_sep && false !== $sep_found ) {
		$whole  = substr( $amount, 0, $sep_found );
		$part   = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
		$amount = $whole . '.' . $part;
	}

	// Strip , from the amount (if set as the thousands separator).
	if ( ',' === $thousands_sep && false !== strpos( $amount, $thousands_sep ) ) {
		$amount = (float) floatval( str_replace( ',', '', $amount ) );
	}

	if ( empty( $amount ) ) {
		$amount = 0;
	}

	$decimals = apply_filters( 'evf_sanitize_amount_decimals', 2, $amount );
	$number   = number_format( (float) $amount, $decimals, $decimal_sep, $thousands_sep );

	if ( $symbol && isset( $currencies[ $currency ]['symbol_pos'] ) ) {
		$symbol_padding = apply_filters( 'evf_currency_symbol_padding', ' ' );
		if ( 'right' === $currencies[ $currency ]['symbol_pos'] ) {
			$number .= $symbol_padding . $currencies[ $currency ]['symbol'];
		} else {
			$number = $currencies[ $currency ]['symbol'] . $symbol_padding . $number;
		}
	}
	if ( 'EUR' === $currency ) {
		$number = str_replace( '.', '', $number );
	}

	return $number;
}

/**
 * Return recognized payment field types.
 *
 * @since 1.0.0
 * @return array
 */
function evf_payment_fields() {

	$fields = array(
		'payment-single',
		'payment-multiple',
		'payment-checkbox',
		'payment-quantity',
		'payment-subscription-plan',
		'payment-total',
		'range-slider',
	);

	return apply_filters( 'evf_payment_fields', $fields );
}

/**
 * Posted amount from a payment-total field when present (calculation / order total).
 *
 * @since 1.9.18
 *
 * @param array $fields      Formatted entry fields (pre–payment-items filter).
 * @param array $post_fields Posted field IDs.
 * @return string|null Sanitized amount or null when no payment-total is posted.
 */
function evf_get_payment_total_field_amount( $fields, $post_fields ) {
	if ( empty( $fields ) || ! is_array( $fields ) ) {
		return null;
	}

	foreach ( $fields as $field ) {
		if ( empty( $field['type'] ) || 'payment-total' !== $field['type'] ) {
			continue;
		}

		if ( ! empty( $post_fields ) && ! in_array( $field['id'], $post_fields, true ) ) {
			continue;
		}

		if ( empty( $field['amount_raw'] ) ) {
			continue;
		}

		$amount = evf_sanitize_amount( $field['amount_raw'] );

		if ( evf_sanitize_amount( '0' ) !== $amount ) {
			return $amount;
		}
	}

	return null;
}

/**
 * Check if form or entry contains payment
 *
 * @since 1.0.0
 *
 * @param string       $type Flag variable for form/entry.
 * @param array|string $data Data object.
 *
 * @return bool
 */
function evf_has_payment( $type = 'entry', $data = '' ) {
	$payment = false;
	$payment_fields = evf_payment_fields();

	if ( ! empty( $data['form_fields'] ) ) {
		$data = $data['form_fields'];
	}

	if ( empty( $data ) ) {
		return false;
	}

	foreach ( $data as $field ) {
		if ( isset( $field['type'] ) && in_array( $field['type'], $payment_fields, true ) ) {

			// For entries, only return true if the payment field has an amount.
			if (
				'form' === $type ||
				(
					'entry' === $type &&
					! empty( $field['item_price'] ) &&
					evf_sanitize_amount( '0' ) !== $field['item_price']
				)
			) {
				$payment = true;
				break;
			}
		}
	}

	return $payment;
}

/**
 * Get payment total amount from entry.
 *
 * @param array $fields    Field data object.
 * @param array $entry     Entry of payment.
 * @param array $form_data Form data object.
 * @return string Sanitized amount (e.g. "0" when there are no payment line items).
 */
function evf_get_total_payment( $fields = array(), $entry = array(), $form_data = array() ) {
	$post_fields    = isset( $_POST['everest_forms']['form_fields'] ) ? array_keys( $_POST['everest_forms']['form_fields'] ) : array(); //phpcs:ignore
	$payment_total  = evf_get_payment_total_field_amount( $fields, $post_fields );

	if ( null !== $payment_total ) {
		return apply_filters( 'everest_forms_payment_total_amount', $payment_total, $fields, $entry, $form_data );
	}

	$fields         = evf_get_payment_items( $fields, $entry, $form_data );
	$total          = 0;
	$quantity_price = array();
	$map_field      = array();
	if ( empty( $fields ) ) {
		return '0';
	}

	foreach ( $form_data['form_fields'] as $key => $field ) {
		if ( isset( $field['repeater-fields'] ) && 'yes' === $field['repeater-fields'] ) {
			if ( array_key_exists( $key, $fields ) ) {
				array_push( $post_fields, $key );
			}
		}
	}

	foreach ( $fields as $field ) {
		$map_field[] = isset( $form_data['form_fields'][ $field['id'] ]['map_field'] ) ? $form_data['form_fields'][ $field['id'] ]['map_field'] : '';
	}

	foreach ( $fields as $key => $field ) {

		if ( ! in_array( $field['id'], $post_fields, true ) ) {
			continue;
		}

		if ( ! empty( $field['amount_raw'] ) && ! in_array( $field['id'], $map_field, true ) ) {
				$amount = evf_sanitize_amount( $field['amount_raw'] );
				$total  = $total + $amount;
		}

		if ( 'payment-quantity' === $field['type'] && ! empty( $map_field ) ) {
			if ( ! empty( $field['value'] ) ) {
				foreach ( $map_field as $id ) {
					if ( $form_data['form_fields'][ $field['id'] ]['map_field'] === $id && isset( $fields[ $id ] ) ) {
						$quantity_price[] = evf_sanitize_amount( $fields[ $id ]['amount_raw'] ) * $field['value'];
					}
				}
			}
		}
	}

	foreach ( $quantity_price as $price ) {
		$total = $total + $price;
	}

	return apply_filters( 'everest_forms_payment_total_amount', evf_sanitize_amount( $total ), $fields, $entry, $form_data );
}

/**
 * Get payment fields in an entry.
 *
 * @param array $fields    Field Object Data.
 * @param array $entry     Payment Entry.
 * @param array $form_data Form Object.
 *
 * @return array|bool False if no fields provided, otherwise array.
 */
function evf_get_payment_items( $fields = array(), $entry = array(), $form_data = array() ) {
	if ( empty( $fields ) ) {
		return false;
	}

	$payment_fields = evf_payment_fields();

	foreach ( $fields as $id => $field ) {
		$field['amount_raw'] = isset( $field['amount_raw'] ) ? $field['amount_raw'] : '';
		if ( 'repeater-fields' === $field['type'] && ! empty( $field['value_raw'] ) ) {
			foreach ( $field['value_raw'] as $row_key => $row ) {
				foreach ( $row as $field_key => $field_value ) {
					$field_value['id']            = $field_key . '_' . $row_key;
					$fields[ $field_value['id'] ] = $field_value;
					if ( 'payment-total' === $field_value['type'] ) {
						unset( $fields[ $field_value['id'] ] );
						continue;
					}
					if ( ( ! in_array( $field_value['type'], $payment_fields, true ) || ( empty( $field_value['amount_raw'] ) && 'payment-quantity' !== $field_value['type'] ) || ( ! empty( $field_value['amount_raw'] ) && evf_sanitize_amount( '0' ) === $field_value['amount_raw'] && 'payment-quantity' !== $field_value['type'] ) ) || ( ! in_array( $field_value['type'], $payment_fields, true ) && ! apply_filters( 'everest_forms_visible_fields', true, $field_value, $entry, $form_data ) ) ) {
						 unset( $fields[ $field_value['id'] ] );
					}
				}
			}
		}
		if ( 'payment-total' === $field['type'] ) {
			unset( $fields[ $id ] );
			continue;
		}

		if ( ( ! in_array( $field['type'], $payment_fields, true ) || ( empty( $field['amount_raw'] ) && 'payment-quantity' !== $field['type'] ) || ( evf_sanitize_amount( '0' ) === $field['amount_raw'] && 'payment-quantity' !== $field['type'] ) ) || ( ! in_array( $field['type'], $payment_fields, true ) && ! apply_filters( 'everest_forms_visible_fields', true, $field, $entry, $form_data ) ) ) {
			// Remove all non-payment fields as well as payment fields with no amount.
			unset( $fields[ $id ] );

		}
	}
	return $fields;
}

/**
 * Human-readable labels for payment gateway slugs (selector field).
 *
 * @since 1.9.15
 *
 * @return array<string,string>
 */
function evf_payment_gateway_selector_labels() {
	return array(
		'stripe'        => esc_html__( 'Stripe', 'everest-forms-pro' ),
		'paypal'        => esc_html__( 'PayPal', 'everest-forms-pro' ),
		'square'        => esc_html__( 'Square', 'everest-forms-pro' ),
		'mollie'        => esc_html__( 'Mollie', 'everest-forms-pro' ),
		'razorpay'      => esc_html__( 'Razorpay', 'everest-forms-pro' ),
		'authorize_net' => esc_html__( 'Authorize.Net', 'everest-forms-pro' ),
	);
}

/**
 * Whether a gateway's global credentials are fully configured.
 *
 * Mirrors the connection_check logic used by the Settings accordion UI.
 * Returns true when at least one credential group (live OR test) is complete.
 *
 * @since 1.9.15
 *
 * @param string $slug Gateway slug.
 * @return bool
 */
function evf_payment_gateway_selector_is_connected( $slug ) {
	$credential_groups = array(
		'stripe'        => array(
			'live' => array( 'everest_forms_stripe_live_publishable_key', 'everest_forms_stripe_live_secret_key' ),
			'test' => array( 'everest_forms_stripe_test_publishable_key', 'everest_forms_stripe_test_secret_key' ),
		),
		'paypal'        => array(
			'any' => array( 'everest_forms_paypal_email' ),
		),
		'square'        => array(
			'live' => array( 'everest_forms_square_live_app_id', 'everest_forms_square_live_access_token', 'everest_forms_square_live_location_id' ),
			'test' => array( 'everest_forms_square_test_app_id', 'everest_forms_square_test_access_token', 'everest_forms_square_test_location_id' ),
		),
		'mollie'        => array(
			'live' => array( 'everest_forms_mollie_live_api_key' ),
			'test' => array( 'everest_forms_mollie_test_api_key' ),
		),
		'razorpay'      => array(
			'live' => array( 'everest_forms_razorpay_live_publishable_key', 'everest_forms_razorpay_live_secret_key' ),
			'test' => array( 'everest_forms_razorpay_test_publishable_key', 'everest_forms_razorpay_test_secret_key' ),
		),
		'authorize_net' => array(
			'live' => array( 'everest_forms_authorize_net_live_api_login_id', 'everest_forms_authorize_net_live_api_transaction_key' ),
			'test' => array( 'everest_forms_authorize_net_test_api_login_id', 'everest_forms_authorize_net_test_api_transaction_key' ),
		),
	);

	if ( ! isset( $credential_groups[ $slug ] ) ) {
		return false;
	}

	foreach ( $credential_groups[ $slug ] as $group_keys ) {
		$complete = true;
		foreach ( $group_keys as $option_key ) {
			if ( empty( get_option( $option_key, '' ) ) ) {
				$complete = false;
				break;
			}
		}
		if ( $complete ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether the gateway addon is active/available.
 *
 * Note: For some gateways, EVF Pro tracks "enabled features" rather than defining a plugin constant.
 *
 * @since 1.9.15
 *
 * @param string $slug Gateway slug.
 * @return bool
 */
function evf_payment_gateway_selector_is_addon_active( $slug ) {
	$slug             = sanitize_key( (string) $slug );
	$enabled_features = (array) get_option( 'everest_forms_enabled_features', array() );

	switch ( $slug ) {
		case 'stripe':
			return defined( 'EVF_STRIPE_PLUGIN_FILE' );
		case 'paypal':
			return defined( 'EVF_PAYPAL_STANDARD_PLUGIN_FILE' );
		case 'square':
			return in_array( 'everest-forms-square', $enabled_features, true );
		case 'mollie':
			return in_array( 'everest-forms-mollie', $enabled_features, true );
		case 'razorpay':
			return defined( 'EVF_RAZORPAY_PLUGIN_FILE' );
		case 'authorize_net':
			return defined( 'EVF_AUTHORIZE_NET_PLUGIN_FILE' );
	}

	return false;
}

/**
 * Gateway slugs whose addon is active and global credentials are configured.
 *
 * No longer depends on the per-form Payments tab enable toggle — a gateway is
 * selectable as long as its plugin is active and credentials are saved.
 *
 * @since 1.9.15
 *
 * @param array $form_data Form structure (kept for back-compat; unused).
 * @return string[]
 */
function evf_payment_gateway_selector_form_enabled_gateways( $form_data ) {
	$enabled = array();
	foreach ( array_keys( evf_payment_gateway_selector_labels() ) as $slug ) {
		if ( ! evf_payment_gateway_selector_is_addon_active( $slug ) ) {
			continue;
		}
		// PayPal supports per-form credentials — always enabled when addon active, no global creds required.
		if ( 'paypal' === $slug || evf_payment_gateway_selector_is_connected( $slug ) ) {
			$enabled[] = $slug;
		}
	}

	return $enabled;
}

/**
 * Whether a gateway slug is allowed by the Payment Gateway selector field on the form.
 *
 * When the selector has no saved allowlist (legacy forms without pgw_allowlist_sent),
 * any connected active gateway is treated as allowed.
 *
 * @since 1.9.15
 *
 * @param array $args Arguments with `form_data` (array) and `gateway` (string) keys.
 * @return bool
 */
function evf_is_gateway_in_selector_allowlist( $args ) {
	$form_data = isset( $args['form_data'] ) ? $args['form_data'] : array();
	$gateway   = isset( $args['gateway'] ) ? sanitize_key( (string) $args['gateway'] ) : '';

	if ( '' === $gateway || empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return false;
	}

	$selector_field = null;
	foreach ( $form_data['form_fields'] as $field ) {
		if ( ! empty( $field['type'] ) && 'payment-gateway-selector' === $field['type'] ) {
			$selector_field = $field;
			break;
		}
	}

	if ( null === $selector_field ) {
		return false;
	}

	if ( empty( $selector_field['allowed_gateways'] ) || ! is_array( $selector_field['allowed_gateways'] ) ) {
		// Legacy forms (no pgw_allowlist_sent): empty allowlist means "all connected gateways" on the selector.
		if ( empty( $selector_field['pgw_allowlist_sent'] ) ) {
			return evf_payment_gateway_selector_is_addon_active( $gateway ) && evf_payment_gateway_selector_is_connected( $gateway );
		}
		return false;
	}

	return in_array( $gateway, $selector_field['allowed_gateways'], true );
}

/**
 * Mark a form for Square subscription checkout on the frontend (PGW + subscription plan).
 *
 * @since 1.9.16
 *
 * @param int   $form_id   Form ID.
 * @param array $form_data Form structure.
 */
function evf_square_mark_recurring_form_script( $form_id, $form_data ) {
	$form_id = absint( $form_id );
	if ( ! $form_id || empty( $form_data ) || ! is_array( $form_data ) ) {
		return;
	}
	if ( ! function_exists( 'evf_form_has_subscription_plan_field' ) || ! evf_form_has_subscription_plan_field( $form_data ) ) {
		return;
	}
	if ( ! wp_script_is( 'everest-forms-pro-square-payment', 'registered' ) ) {
		return;
	}
	wp_add_inline_script(
		'everest-forms-pro-square-payment',
		sprintf(
			'window.evfSquareRecurringForms = window.evfSquareRecurringForms || {}; window.evfSquareRecurringForms["%s"] = true;',
			esc_js( (string) $form_id )
		),
		'after'
	);
}

/**
 * Whether the form includes a Subscription Plan field.
 *
 * @since 1.9.16
 *
 * @param array $form_data Form structure.
 * @return bool
 */
function evf_form_has_subscription_plan_field( $form_data ) {
	if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return false;
	}
	foreach ( $form_data['form_fields'] as $field ) {
		if ( ! empty( $field['type'] ) && 'payment-subscription-plan' === $field['type'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether the form has both Payment Gateway selector and Subscription Plan fields.
 *
 * Used so subscriptions can run via the selector flow without enabling
 * per-gateway "Enable recurring subscription payments" toggles in the Payments tab.
 *
 * @since 1.9.16
 *
 * @param array $form_data Form structure.
 * @return bool
 */
function evf_form_uses_subscription_with_payment_gateway_selector( $form_data ) {
	if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return false;
	}
	$has_selector = false;
	foreach ( $form_data['form_fields'] as $field ) {
		if ( empty( $field['type'] ) ) {
			continue;
		}
		if ( 'payment-gateway-selector' === $field['type'] ) {
			$has_selector = true;
			break;
		}
	}
	return $has_selector && evf_form_has_subscription_plan_field( $form_data );
}

/**
 * Selected subscription plan choice from entry data (choice index in form_fields).
 *
 * @since 1.9.16
 *
 * @param array $form_data Form structure.
 * @param array $entry     Entry array with form_fields.
 * @return array|null Choice row or null.
 */
function evf_get_subscription_plan_choice_from_entry( $form_data, $entry ) {
	$subscription_plan_field = evf_get_form_data_by_key( $form_data, 'payment-subscription-plan' );
	if ( empty( $subscription_plan_field[0]['id'] ) || empty( $subscription_plan_field[0]['choices'] ) || ! is_array( $subscription_plan_field[0]['choices'] ) ) {
		return null;
	}
	$field_id = $subscription_plan_field[0]['id'];
	if ( empty( $entry['form_fields'] ) || ! is_array( $entry['form_fields'] ) || ! isset( $entry['form_fields'][ $field_id ] ) ) {
		return null;
	}
	$cell = $entry['form_fields'][ $field_id ];
	$idx  = null;
	if ( is_array( $cell ) ) {
		if ( isset( $cell['value_raw'] ) ) {
			$idx = $cell['value_raw'];
		} elseif ( isset( $cell['value']['value'] ) ) {
			$idx = $cell['value']['value'];
		} elseif ( isset( $cell['value'] ) && is_scalar( $cell['value'] ) ) {
			$idx = $cell['value'];
		}
	} else {
		$idx = $cell;
	}
	if ( null === $idx || '' === $idx ) {
		return null;
	}
	if ( ! isset( $subscription_plan_field[0]['choices'][ $idx ] ) ) {
		return null;
	}
	return $subscription_plan_field[0]['choices'][ $idx ];
}

/**
 * Red required asterisk for payment gateway accordion / gateway mapping panel labels.
 *
 * @since 1.9.18
 *
 * @return string HTML suffix for everest_forms_panel_field() `before_tooltip` (before the help icon).
 */
function evf_pgw_panel_required_asterisk() {
	return apply_filters(
		'everest_forms_field_required_label',
		' <abbr class="required" title="' . esc_attr__( 'Required', 'everest-forms-pro' ) . '">*</abbr>'
	);
}

/**
 * Map subscription plan period to Mollie interval type and count.
 *
 * @since 1.9.16
 *
 * @param string $recurring_period day|week|month|year.
 * @param int    $interval_count   Interval multiplier from plan.
 * @return array{interval:string,interval_count:int}
 */
function evf_subscription_plan_period_to_mollie_interval( $recurring_period, $interval_count ) {
	$interval_count = max( 1, absint( $interval_count ) );
	$period         = sanitize_key( (string) $recurring_period );
	switch ( $period ) {
		case 'day':
			return array(
				'interval'       => 'DAYS',
				'interval_count' => $interval_count,
			);
		case 'week':
			return array(
				'interval'       => 'WEEKS',
				'interval_count' => $interval_count,
			);
		case 'year':
			return array(
				'interval'       => 'YEARS',
				'interval_count' => $interval_count,
			);
		case 'month':
		default:
			return array(
				'interval'       => 'MONTHS',
				'interval_count' => $interval_count,
			);
	}
}

/**
 * Posted gateway slug when the form includes a payment-gateway-selector field.
 *
 * @since 1.9.15
 *
 * @param array $form_data Form structure.
 * @return string|null Null = no selector field on form; empty string = not posted.
 */
function evf_payment_gateway_selector_get_posted_choice( $form_data ) {
	if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return null;
	}

	foreach ( $form_data['form_fields'] as $fid => $field ) {
		if ( empty( $field['type'] ) || 'payment-gateway-selector' !== $field['type'] ) {
			continue;
		}
		if ( ! isset( $_POST['everest_forms']['form_fields'][ $fid ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}
		$raw = wp_unslash( $_POST['everest_forms']['form_fields'][ $fid ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( is_array( $raw ) ) {
			return '';
		}
		if ( '' === $raw || null === $raw ) {
			return '';
		}
		return sanitize_key( (string) $raw );
	}

	return null;
}

/**
 * Whether the form includes a Payment Gateway selector field.
 *
 * @since 1.9.17
 *
 * @param array $form_data Form structure.
 * @return bool
 */
function evf_form_has_payment_gateway_selector_field( $form_data ) {
	if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return false;
	}
	foreach ( $form_data['form_fields'] as $field ) {
		if ( ! empty( $field['type'] ) && 'payment-gateway-selector' === $field['type'] ) {
			return true;
		}
	}
	return false;
}

/**
 * Gateway slugs with payment artifacts present in the current POST request.
 *
 * @since 1.9.17
 *
 * @return string[]
 */
function evf_detect_posted_payment_gateway_slugs() {
	$slugs = array();

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST['everest_form_stripe_payment_intent_id'] ) ) {
		$slugs[] = 'stripe';
	}

	if (
		( ! empty( $_POST['everest_form_razorpay_payment_intent_id'] ) && ! empty( $_POST['everest_form_razorpay_order_id'] ) )
		|| ! empty( $_POST['everest_form_razorpay_subscription_id'] )
	) {
		$slugs[] = 'razorpay';
	}

	if (
		isset( $_POST['everest_forms']['authorize_net']['opaque_data']['descriptor'], $_POST['everest_forms']['authorize_net']['opaque_data']['value'] )
		&& '' !== (string) $_POST['everest_forms']['authorize_net']['opaque_data']['descriptor']
		&& '' !== (string) $_POST['everest_forms']['authorize_net']['opaque_data']['value']
	) {
		$slugs[] = 'authorize_net';
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	return array_values( array_unique( $slugs ) );
}

/**
 * Whether a gateway is enabled for this form (selector allowlist or Payments tab).
 *
 * @since 1.9.17
 *
 * @param array  $form_data Form structure.
 * @param string $gateway   Gateway slug.
 * @return bool
 */
function evf_is_payment_gateway_enabled_for_form( $form_data, $gateway ) {
	$gateway = sanitize_key( (string) $gateway );
	if ( '' === $gateway ) {
		return false;
	}

	if ( evf_form_has_payment_gateway_selector_field( $form_data ) ) {
		if ( ! evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => $gateway ) ) ) {
			return false;
		}
		return evf_payment_gateway_selector_is_addon_active( $gateway ) && evf_payment_gateway_selector_is_connected( $gateway );
	}

	$enable_option_keys = array(
		'stripe'        => 'enable_stripe',
		'paypal'        => 'enable_paypal',
		'square'        => 'enable_square',
		'mollie'        => 'enable_mollie',
		'razorpay'      => 'enable_razorpay',
		'authorize_net' => 'enable_authorize_net',
	);

	if ( ! isset( $enable_option_keys[ $gateway ] ) ) {
		return false;
	}

	$option_key = $enable_option_keys[ $gateway ];
	if (
		isset( $form_data['payments'][ $gateway ][ $option_key ] )
		&& evf_string_to_bool( $form_data['payments'][ $gateway ][ $option_key ] )
	) {
		return true;
	}

	if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return false;
	}

	foreach ( $form_data['form_fields'] as $field ) {
		if ( empty( $field['type'] ) ) {
			continue;
		}
		if ( 'stripe' === $gateway && 'credit-card' === $field['type'] ) {
			return true;
		}
		if ( 'square' === $gateway && 'square-payment' === $field['type'] ) {
			return true;
		}
		if ( 'authorize_net' === $gateway && 'authorize-net' === $field['type'] ) {
			return true;
		}
	}

	return false;
}

/**
 * Validate posted gateway choice and payment tokens against form settings.
 *
 * Do not trust client-side data-evf-gateway or CSS visibility; use POST field values
 * and form configuration only.
 *
 * @since 1.9.17
 *
 * @param array $form_data Form structure.
 * @param array $entry     Posted entry (everest_forms).
 * @return true|WP_Error
 */
function evf_validate_submitted_payment_gateway( $form_data, $entry = array() ) {
	unset( $entry );

	if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
		return true;
	}

	$posted_slugs  = evf_detect_posted_payment_gateway_slugs();
	$has_selector  = evf_form_has_payment_gateway_selector_field( $form_data );
	$invalid_msg   = __( 'Please choose a valid payment method.', 'everest-forms-pro' );
	$disabled_msg  = __( 'The selected payment method is not available for this form.', 'everest-forms-pro' );
	$mismatch_msg  = __( 'Payment submission does not match the selected payment method.', 'everest-forms-pro' );

	if ( $has_selector ) {
		$choice = evf_payment_gateway_selector_get_posted_choice( $form_data );

		if ( null === $choice ) {
			return true;
		}

		if ( '' === $choice ) {
			return new WP_Error( 'evf_invalid_payment_gateway', $invalid_msg );
		}

		if ( ! evf_is_payment_gateway_enabled_for_form( $form_data, $choice ) ) {
			return new WP_Error( 'evf_invalid_payment_gateway', $disabled_msg );
		}

		foreach ( $posted_slugs as $slug ) {
			if ( $slug !== $choice ) {
				return new WP_Error( 'evf_payment_gateway_mismatch', $mismatch_msg );
			}
		}

		return true;
	}

	foreach ( $posted_slugs as $slug ) {
		if ( ! evf_is_payment_gateway_enabled_for_form( $form_data, $slug ) ) {
			return new WP_Error( 'evf_invalid_payment_gateway', $disabled_msg );
		}
	}

	if ( count( $posted_slugs ) > 1 ) {
		return new WP_Error( 'evf_payment_gateway_mismatch', $mismatch_msg );
	}

	return true;
}

/**
 * Restrict payment processors when a gateway selector choice is present.
 *
 * @since 1.9.15
 *
 * @param bool   $process     Whether to run processor.
 * @param array  $fields      Entry fields.
 * @param array  $form_data   Form data.
 * @param string $gateway_key Gateway key passed by processor.
 * @param string $connection  Connection id.
 * @return bool
 */
function evf_payment_gateway_selector_filter_payment_process( $process, $fields, $form_data, $gateway_key, $connection ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( ! $process ) {
		return false;
	}

	$choice = evf_payment_gateway_selector_get_posted_choice( $form_data );

	if ( null === $choice ) {
		return $process;
	}

	if ( '' === $choice ) {
		return false;
	}

	if ( ! evf_is_payment_gateway_enabled_for_form( $form_data, $choice ) ) {
		return false;
	}

	$map = array(
		'stripe'        => array( 'stripe', 'stripe_recurring' ),
		'paypal'        => array( 'paypal' ),
		'square'        => array( 'square' ),
		'mollie'        => array( 'mollie' ),
		'razorpay'      => array( 'razorpay' ),
		'authorize_net' => array( 'authorize_net' ),
	);

	if ( ! isset( $map[ $choice ] ) ) {
		return $process;
	}

	if ( ! in_array( $gateway_key, $map[ $choice ], true ) ) {
		return false;
	}

	return $process;
}

add_filter( 'everest_forms_entry_payment_process', 'evf_payment_gateway_selector_filter_payment_process', 5, 5 );

/**
 * Insert payement data into meta.
 *
 * @param string $entry_id   Entry id for paymment.
 * @param array  $entry_data The entry data for payment.
 * @param bool   $update     Flag for checking if the query ran with no problems.
 */
function evf_payment_entries( $entry_id, $entry_data, $update = false ) {
	global $wpdb;

	foreach ( $entry_data as $key => $data ) {
		if ( $update ) {
				$table_name = $wpdb->prefix . 'evf_entrymeta';

				// @codingStandardsIgnoreStart
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $table_name
								SET meta_key = %s , meta_value = %s
								WHERE entry_id = %d and meta_key = %s ",
						$key,
						$data,
						$entry_id,
						$key
					)
				);
				// @codingStandardsIgnoreEnd
		} else {
				// @codingStandardsIgnoreStart
				$wpdb->insert(
					$wpdb->prefix . 'evf_entrymeta',
					array(
						'entry_id'   => $entry_id,
						'meta_key'   => $key,
						'meta_value' => $data,
					),
					array( '%d', '%s', '%s' )
				);
				// @codingStandardsIgnoreEnd
		}
	}
}

/**
 * Entry status label for payment UI: show "Complete" instead of "Publish" for published entries.
 *
 * @param string $status Raw entry status.
 * @return string
 */
function evf_format_payment_entry_status_display( $status ) {

	return ucwords( sanitize_text_field( $status ) );
}
