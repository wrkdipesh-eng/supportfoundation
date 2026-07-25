<?php
/**
 * REST: current user's payment entry detail (transaction view for Payment History block).
 *
 * @package EverestForms_Pro
 * @since   1.7.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVFP_User_Payment_Entry Class.
 */
class EVFP_User_Payment_Entry {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms-pro/v1';

	/**
	 * Base.
	 *
	 * @var string
	 */
	protected $rest_base = 'user-payment-entry';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<entry_id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_detail' ),
					'permission_callback' => array( __CLASS__, 'can_read_entry' ),
					'args'                => array(
						'entry_id' => array(
							'description' => __( 'Entry ID.', 'everest-forms-pro' ),
							'type'        => 'integer',
						),
					),
				),
			)
		);
	}

	/**
	 * Permission: logged-in user owns the entry.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public static function can_read_entry( $request ) {
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You must be logged in.', 'everest-forms-pro' ),
				array( 'status' => 401 )
			);
		}
		$entry_id = absint( $request['entry_id'] );
		$entry    = evf_get_entry( $entry_id, false, array( 'cap' => false ) );
		if ( ! $entry ) {
			return new \WP_Error(
				'rest_no_entry',
				__( 'Entry not found.', 'everest-forms-pro' ),
				array( 'status' => 404 )
			);
		}
		if ( (int) $entry->user_id !== get_current_user_id() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have access to this entry.', 'everest-forms-pro' ),
				array( 'status' => 403 )
			);
		}
		if ( empty( $entry->meta['type'] ) || 'payment' !== $entry->meta['type'] ) {
			return new \WP_Error(
				'rest_invalid_entry',
				__( 'This entry is not a payment.', 'everest-forms-pro' ),
				array( 'status' => 400 )
			);
		}
		return true;
	}

	/**
	 * Return transaction detail payload for modal.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_detail( $request ) {
		$entry_id = absint( $request['entry_id'] );
		// Use with_fields false so entrymeta (payment type, meta JSON) stays available.
		$entry    = evf_get_entry( $entry_id, false, array( 'cap' => false ) );

		$meta = array();
		if ( ! empty( $entry->meta['meta'] ) ) {
			$meta = json_decode( $entry->meta['meta'], true );
		}
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$form_data = array();
		$form_obj  = evf()->form->get( absint( $entry->form_id ), array() );
		if ( $form_obj && ! empty( $form_obj->post_content ) ) {
			$form_data = evf_decode( $form_obj->post_content );
		}

		$currency = ! empty( $meta['payment_currency'] ) ? $meta['payment_currency'] : get_option( 'everest_forms_currency', 'USD' );

		$line_items = self::collect_line_items( $entry, $form_data, $meta, $currency );
		$customer   = self::collect_customer( $entry );

		$subtotal_raw = 0;
		foreach ( $line_items as $row ) {
			$subtotal_raw += isset( $row['line_total_raw'] ) ? (float) $row['line_total_raw'] : 0;
		}
		if ( $subtotal_raw <= 0 && isset( $meta['payment_total'] ) ) {
			$subtotal_raw = (float) evf_sanitize_amount( $meta['payment_total'], $currency );
		}

		$total_raw = isset( $meta['payment_total'] ) ? (float) evf_sanitize_amount( $meta['payment_total'], $currency ) : $subtotal_raw;

		$discount_raw = $subtotal_raw - $total_raw;

		$gateway = isset( $meta['payment_gateway'] ) ? $meta['payment_gateway'] : '';
		$gateway_label = self::gateway_label( $gateway );

		$status   = evf_format_payment_entry_status_display( $entry->meta['status']);

		$data = array(
			'entry_id'        => $entry_id,
			'transaction_no'  => '#' . $entry_id,
			'date'            => self::format_date( $entry->date_created ),
			'payment_method'  => $gateway_label,
			'payment_status'  => $status,
			'currency'        => $currency,
			'line_items'      => array_map(
				function ( $row ) {
					unset( $row['line_total_raw'] );
					return $row;
				},
				$line_items
			),
			'subtotal'        => self::format_amount_code( $subtotal_raw, $currency ),
			'discount'        => $discount_raw > 0 ? self::format_amount_code( $discount_raw, $currency ) : null,
			'total'           => self::format_amount_code( $total_raw, $currency ),
			'customer_name'   => $customer['name'],
			'customer_email'  => $customer['email'],
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Format amount with ISO currency code (e.g. "10.00 USD") instead of HTML symbol (e.g. &#36;).
	 *
	 * @param float|string $amount   Amount.
	 * @param string       $currency Currency code.
	 * @return string
	 */
	protected static function format_amount_code( $amount, $currency ) {
		$currency = strtoupper( (string) $currency );
		if ( '' === $currency ) {
			$currency = strtoupper( (string) get_option( 'everest_forms_currency', 'USD' ) );
		}
		$num = evf_format_amount( (string) $amount, false, $currency );
		return trim( $num . ' ' . $currency );
	}

	/**
	 * Gateway label.
	 *
	 * @param string $gateway Gateway slug.
	 * @return string
	 */
	protected static function gateway_label( $gateway ) {
		switch ( $gateway ) {
			case 'stripe':
				return __( 'Stripe', 'everest-forms-pro' );
			case 'paypal_standard':
				return __( 'PayPal Standard', 'everest-forms-pro' );
			case 'razorpay':
				return __( 'Razorpay', 'everest-forms-pro' );
			case 'square':
				return __( 'Square', 'everest-forms-pro' );
			case 'authorize_net':
				return __( 'Authorize.Net', 'everest-forms-pro' );
			case 'mollie':
				return __( 'Mollie', 'everest-forms-pro' );
			default:
				return $gateway ? ucwords( str_replace( '_', ' ', $gateway ) ) : '—';
		}
	}

	/**
	 * Format MySQL datetime for display.
	 *
	 * @param string $mysql_date Date string.
	 * @return string
	 */
	protected static function format_date( $mysql_date ) {
		$ts = strtotime( $mysql_date );
		if ( ! $ts ) {
			return '';
		}
		return date_i18n( get_option( 'date_format' ), $ts );
	}

	/**
	 * Resolve payment status from entry meta before falling back to entry post status.
	 *
	 * @param object $entry Entry object.
	 * @return string
	 */
	protected static function get_entry_payment_status( $entry ) {
		$status = '';

		if ( isset( $entry->meta ) && is_array( $entry->meta ) && ! empty( $entry->meta['status'] ) ) {
			$status = (string) $entry->meta['status'];
		} elseif ( isset( $entry->status ) ) {
			$status = (string) $entry->status;
		}

		return evf_format_payment_entry_status_display( $status );
	}

	/**
	 * Customer name and email from entry fields.
	 *
	 * @param object $entry Entry object.
	 * @return array{name:string,email:string}
	 */
	protected static function collect_customer( $entry ) {
		$fields = evf_decode( $entry->fields );
		if ( ! is_array( $fields ) ) {
			return array(
				'name'  => '',
				'email' => '',
			);
		}
		$first = '';
		$last  = '';
		$email = '';
		foreach ( $fields as $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}
			switch ( $field['type'] ) {
				case 'first-name':
					$first = isset( $field['value'] ) ? sanitize_text_field( $field['value'] ) : '';
					break;
				case 'last-name':
					$last = isset( $field['value'] ) ? sanitize_text_field( $field['value'] ) : '';
					break;
				case 'email':
					$email = isset( $field['value'] ) ? sanitize_email( $field['value'] ) : '';
					break;
			}
		}
		$name = trim( $first . ' ' . $last );
		return array(
			'name'  => $name,
			'email' => $email,
		);
	}

	/**
	 * Build line items from saved field values.
	 *
	 * @param object $entry     Entry.
	 * @param array  $form_data Form data.
	 * @param array  $meta      Payment meta.
	 * @param string $currency  Currency code.
	 * @return array
	 */
	protected static function collect_line_items( $entry, $form_data, $meta, $currency ) {
		$fields = evf_decode( $entry->fields );
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$payment_types = array_merge(
			evf_payment_fields(),
			array( 'payment-subscription-plan' )
		);

		$lines       = array();
		$form_fields = isset( $form_data['form_fields'] ) && is_array( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();

		foreach ( $fields as $field ) {
			if ( empty( $field['type'] ) || empty( $field['id'] ) ) {
				continue;
			}
			$type = $field['type'];
			if ( in_array( $type, array( 'payment-total', 'payment-subtotal', 'payment-coupon', 'payment-quantity' ), true ) ) {
				continue;
			}
			if ( ! in_array( $type, $payment_types, true ) ) {
				continue;
			}

			$label = '';
			if ( ! empty( $field['name'] ) ) {
				$label = $field['name'];
			} elseif ( isset( $form_fields[ $field['id'] ]['label'] ) ) {
				$label = $form_fields[ $field['id'] ]['label'];
			} else {
				$label = (string) $field['id'];
			}

			$qty = 1;
			foreach ( $fields as $qf ) {
				if ( empty( $qf['type'] ) || 'payment-quantity' !== $qf['type'] ) {
					continue;
				}
				$map = isset( $form_fields[ $qf['id'] ]['map_field'] ) ? $form_fields[ $qf['id'] ]['map_field'] : '';
				if ( (string) $map === (string) $field['id'] && isset( $qf['value'] ) ) {
					$qty = floatval( $qf['value'] );
					break;
				}
			}

			$unit_raw = '';
			if ( isset( $field['amount_raw'] ) && '' !== $field['amount_raw'] ) {
				$unit_raw = $field['amount_raw'];
			} elseif ( isset( $field['item_price'] ) && '' !== $field['item_price'] ) {
				$unit_raw = $field['item_price'];
			}
			if ( '' === $unit_raw ) {
				continue;
			}

			$unit_amount  = (float) evf_sanitize_amount( $unit_raw, $currency );
			$line_total   = $unit_amount * $qty;
			$lines[]      = array(
				'item'           => wp_strip_all_tags( $label ),
				'quantity'       => $qty,
				'price'          => self::format_amount_code( $unit_amount, $currency ),
				'line_total'     => self::format_amount_code( $line_total, $currency ),
				'line_total_raw' => $line_total,
			);
		}

		if ( empty( $lines ) && ! empty( $meta['payment_total'] ) ) {
			$total_raw = (float) evf_sanitize_amount( $meta['payment_total'], $currency );
			$lines[]   = array(
				'item'           => __( 'Payments', 'everest-forms-pro' ),
				'quantity'       => 1,
				'price'          => self::format_amount_code( $total_raw, $currency ),
				'line_total'     => self::format_amount_code( $total_raw, $currency ),
				'line_total_raw' => $total_raw,
			);
		}

		$lines = apply_filters( 'everest_forms_user_payment_entry_line_items', $lines, $entry, $form_data, $meta );

		return is_array( $lines ) ? $lines : array();
	}
}
