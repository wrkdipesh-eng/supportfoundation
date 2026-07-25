<?php
/**
 * Payment Log Controller.
 *
 * @since 1.7.6
 *
 * @package  EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVFP_Payment_Log Class.
 */
class EVFP_Payment_Log {




	/**
	 * The namespace of this controller's route.
	 *
	 * @var string The namespace of this controller's route.
	 */
	protected $namespace = 'everest-forms-pro/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string The base of this controller's route.
	 */
	protected $rest_base = 'payment_log';

	/**
	 * Register routes.
	 *
	 * @since 1.7.6
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-form-list',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_form_list' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-active-gateways',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_active_gateways' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-payment-entry',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'get_payment_entry' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete-entries',
			array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'delete_payment_entries' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);
	}

	/**
	 * Delete payment entries.
	 *
	 * @param \WP_REST_Request $request Full detail about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function delete_payment_entries( $request ) {
		global $wpdb;

		$params = $request->get_json_params();
		$ids    = isset( $params['ids'] ) ? $params['ids'] : array();

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'No entry IDs provided.', 'everest-forms-pro' ),
				),
				200
			);
		}

		foreach ( $ids as $entry_id ) {
			$entry_id = absint( $entry_id );
			if ( ! $entry_id ) {
				continue;
			}
			do_action( 'everest_forms_before_delete_entries', $entry_id );
			$wpdb->delete( $wpdb->prefix . 'evf_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );
			if ( apply_filters( 'everest_forms_delete_entrymeta', true ) ) {
				$wpdb->delete( $wpdb->prefix . 'evf_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
			}
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	/**
	 * Get item.
	 *
	 * @param \WP_Rest_Request $request Full detail about the request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function get_form_list( $request ) {
		$forms = evf_get_all_forms();
		$data  = array();

		foreach ( $forms as $form_id => $form_title ) {
			$data[] = array(
				'value' => $form_id,
				'label' => $form_title
			);
		}

		return new \WP_REST_Response(
			array(
				'success'   => true,
				'form_list' => $data,
			),
			200
		);
	}

	/**
	 * Gateways that are enabled on at least one form the user can access (same scope as form list).
	 *
	 * @since 1.7.8
	 *
	 * @param \WP_Rest_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function get_active_gateways( $request ) {
		global $wpdb;

		$gateway_checks = array(
			'stripe'        => array( 'stripe', 'enable_stripe' ),
			'paypal'        => array( 'paypal', 'enable_paypal' ),
			'square'        => array( 'square', 'enable_square' ),
			'mollie'        => array( 'mollie', 'enable_mollie' ),
			'razorpay'      => array( 'razorpay', 'enable_razorpay' ),
			'authorize_net' => array( 'authorize_net', 'enable_authorize_net' ),
		);

		$labels = array(
			'stripe'        => esc_html__( 'Stripe', 'everest-forms-pro' ),
			'paypal'        => esc_html__( 'PayPal', 'everest-forms-pro' ),
			'square'        => esc_html__( 'Square', 'everest-forms-pro' ),
			'mollie'        => esc_html__( 'Mollie', 'everest-forms-pro' ),
			'razorpay'      => esc_html__( 'Razorpay', 'everest-forms-pro' ),
			'authorize_net' => esc_html__( 'Authorize.Net', 'everest-forms-pro' ),
		);

		$active_slugs = array();

		if ( is_null( evf()->form ) ) {
			return new \WP_REST_Response(
				array(
					'success'  => true,
					'gateways' => array(),
				),
				200
			);
		}

		$forms = evf_get_all_forms();

		foreach ( array_keys( $forms ) as $form_id ) {
			$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );

			if ( empty( $form_data ) || ! is_array( $form_data ) || empty( $form_data['payments'] ) || ! is_array( $form_data['payments'] ) ) {
				continue;
			}

			$payments = $form_data['payments'];

			foreach ( $gateway_checks as $slug => $keys ) {
				if ( isset( $active_slugs[ $slug ] ) ) {
					continue;
				}
				list( $gkey, $enable_key ) = $keys;
				if ( ! isset( $payments[ $gkey ][ $enable_key ] ) ) {
					continue;
				}
				if ( '1' === (string) $payments[ $gkey ][ $enable_key ] ) {
					$active_slugs[ $slug ] = true;
				}
			}
		}

		$table_name = $wpdb->prefix . 'evf_entrymeta';
		$meta_rows  = $wpdb->get_col(
			"SELECT meta_value FROM `{$table_name}` WHERE meta_key = 'meta' AND meta_value LIKE '%\"payment_gateway\"%'"
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $meta_rows ) && is_array( $meta_rows ) ) {
			foreach ( $meta_rows as $meta_json ) {
				$decoded_meta = json_decode( $meta_json, true );
				if ( ! is_array( $decoded_meta ) || empty( $decoded_meta['payment_gateway'] ) ) {
					continue;
				}
				$normalized = self::normalize_gateway_slug( $decoded_meta['payment_gateway'] );
				if ( isset( $gateway_checks[ $normalized ] ) ) {
					$active_slugs[ $normalized ] = true;
				}
			}
		}

		$data = array();
		foreach ( array_keys( $active_slugs ) as $slug ) {
			if ( isset( $labels[ $slug ] ) ) {
				$data[] = array(
					'value' => $slug,
					'label' => $labels[ $slug ],
				);
			}
		}

		usort(
			$data,
			function ( $a, $b ) {
				return strcmp( $a['label'], $b['label'] );
			}
		);

		$data = apply_filters( 'everest_forms_payment_log_active_gateways', $data, $request );

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'gateways' => $data,
			),
			200
		);
	}

	/**
	 * Get payment entry.
	 *
	 * @since 1.7.6
	 */
	public static function get_payment_entry( $request ) {
		if ( ! isset( $request['request'] ) || empty( $request['request'] ) ) {

			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => esc_html__( 'Request data not found.', 'easy-mail-smtp' ),
				),
				200
			);
		}

		$requested_data = $request['request'];
		$page_size      = isset( $requested_data['page_size'] ) ? absint( $requested_data['page_size'] ) : 5;
		$offset         = isset( $requested_data['offset'] ) ? $requested_data['offset'] : 0;
		$form_id        = isset( $requested_data['form_id'] ) && ! empty( $requested_data['form_id'] ) ? absint( $requested_data['form_id'] ) : 0;
		$status         = isset( $requested_data['payment_status'] ) && ! empty( $requested_data['payment_status'] ) ? ucfirst( $requested_data['payment_status'] ) : '';
		$searchQuery    = isset( $requested_data['search_query'] ) && ! empty( $requested_data['search_query'] ) ? $requested_data['search_query'] : '';
		$gateway        = isset( $requested_data['gateway'] ) && ! empty( $requested_data['gateway'] ) ? sanitize_key( $requested_data['gateway'] ) : '';
		$order_by       = isset( $requested_data['order_by'] ) ? sanitize_key( $requested_data['order_by'] ) : 'date_created';
		$order          = isset( $requested_data['order'] ) && 'asc' === $requested_data['order'] ? 'asc' : 'desc';

		$data        = array();
		$payment_log = self::evf_get_payment_log( $page_size, $offset, $form_id, $status, $searchQuery, $gateway, $order_by, $order );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'payment_log' => $payment_log,
			),
			200
		);
	}

	/**
	 * Get payment log.
	 *
	 * @since 1.7.6
	 *
	 * @param  array $entry Entry.
	 */
	public static function evf_get_payment_log( $page_size, $offset, $form_id, $status, $searchQuery, $gateway = '', $order_by = 'date_created', $order = 'desc' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'evf_entrymeta';
		$sql        = "SELECT entry_id FROM `$table_name` WHERE meta_key = 'type' AND meta_value = 'payment'";

		$entry_id = $wpdb->get_results( $sql, ARRAY_A );

		$data         = array();
		$payment_data = array();
		$data_to_send = array();
		$first_name   = '';
		$last_name    = '';

		if ( empty( $entry_id ) ) {
			return new \WP_REST_Response(
				array(
					'success'       => false,
					'type_of_error' => 'no_data',
					'message'       => 'No payment log available.'
				),
				200
			);
		}

		foreach ( $entry_id as $id ) {
			if ( '' === $searchQuery ) {
				$data[] = evf_get_entry( $id['entry_id'] );
			} else {
				$search_terms = array_filter( array_map( 'trim', explode( ' ', $searchQuery ) ) );

				if ( empty( $search_terms ) ) {
					$data[] = evf_get_entry( $id['entry_id'] );
					continue;
				}

				$match = true;
				foreach ( $search_terms as $term ) {
					$like_term = '%' . $wpdb->esc_like( $term ) . '%';
					$sql       = $wpdb->prepare( "SELECT entry_id FROM `$table_name` WHERE entry_id = %d AND meta_value LIKE %s LIMIT 1", $id['entry_id'], $like_term );
					$result    = $wpdb->get_var( $sql );

					if ( ! $result ) {
						$match = false;
						break;
					}
				}

				if ( $match ) {
					$data[] = evf_get_entry( $id['entry_id'] );
				}
			}
		}

		foreach ( $data as $value ) {
			$payment_data[] = array(
				'fields'       => $value->fields,
				'meta'         => $value->meta['meta'],
				'form_id'      => $value->form_id,
				'entry_id'     => $value->entry_id,
				'date_created' => isset( $value->date_created ) ? $value->date_created : '',
			);
		}

		foreach ( $payment_data as $payments ) {
			$decoded_fields = json_decode( $payments['fields'], true );
			$decoded_meta   = json_decode( $payments['meta'], true );
			if ( ! is_array( $decoded_meta ) ) {
				$decoded_meta = array();
			}
			if ( ! empty( $gateway ) ) {
				$entry_gateway = isset( $decoded_meta['payment_gateway'] ) ? self::normalize_gateway_slug( $decoded_meta['payment_gateway'] ) : '';
				if ( $entry_gateway !== self::normalize_gateway_slug( $gateway ) ) {
					continue;
				}
			}
			$payment_status = self::get_payment_status( $payments['entry_id'] );
			$temp_form_id   = absint( $payments['form_id'] );
			$temp_data      = array();
			foreach ( $decoded_fields as $fields ) {
				if ( ( 0 != $form_id && '' != $status ) || ( 0 != $form_id && '' != $status && '' != $searchQuery ) ) {
					if ( $form_id === $temp_form_id && $status === $payment_status ) {
						$field_type = isset( $fields['type'] ) ? $fields['type'] : '';
						$temp_data  = self::append_temp_data( $field_type, $temp_data, $fields );
					}
				} elseif ( '' != $status || ( '' != $status && '' != $searchQuery ) ) {
					if ( $status === $payment_status ) {
						$field_type = isset( $fields['type'] ) ? $fields['type'] : '';
						$temp_data  = self::append_temp_data( $field_type, $temp_data, $fields );
					}
				} elseif ( 0 != $form_id ) {
					if ( $form_id === $temp_form_id ) {
						$field_type = isset( $fields['type'] ) ? $fields['type'] : '';
						$temp_data  = self::append_temp_data( $field_type, $temp_data, $fields );
					}
				} else {
					$field_type = isset( $fields['type'] ) ? $fields['type'] : '';
					$temp_data  = self::append_temp_data( $field_type, $temp_data, $fields );
				}
			}

			if ( ( 0 != $form_id && '' != $status ) || ( 0 != $form_id && '' != $status && '' != $searchQuery ) ) {
				if ( $form_id === $temp_form_id && $status === $payment_status ) {
					$data_to_send = self::append_extra_data_to_temp_data( $temp_data, $decoded_meta, $payments, $payment_status, $data_to_send );
				}
			} elseif ( '' != $status || ( '' != $status && '' != $searchQuery ) ) {
				if ( $status === $payment_status ) {
					$data_to_send = self::append_extra_data_to_temp_data( $temp_data, $decoded_meta, $payments, $payment_status, $data_to_send );
				}
			} elseif ( 0 != $form_id ) {
				if ( $form_id === $temp_form_id ) {
					$data_to_send = self::append_extra_data_to_temp_data( $temp_data, $decoded_meta, $payments, $payment_status, $data_to_send );
				}
			} else {
				$data_to_send = self::append_extra_data_to_temp_data( $temp_data, $decoded_meta, $payments, $payment_status, $data_to_send );
			}
		}

		if ( 'date_created' === $order_by ) {
			usort(
				$data_to_send,
				function ( $a, $b ) use ( $order ) {
					$cmp = strcmp( $a['date_created'], $b['date_created'] );
					return 'asc' === $order ? $cmp : -$cmp;
				}
			);
		}

		$count = count( $data_to_send );
		return array(
			'total_count' => $count,
			'result'      => array_slice( $data_to_send, $offset, $page_size )
		);
	}

	/**
	 * Append data to temp.
	 *
	 * @since 1.7.6
	 *
	 * @param  string $field_type Field type.
	 * @param  array  $temp_data Temp data.
	 * @param  array  $fields Fields.
	 */
	protected static function append_temp_data( $field_type, $temp_data, $fields ) {
		switch ( $field_type ) {
			case 'first-name':
			case 'last-name':
				$temp_data[ $field_type ] = ! empty( $fields['value'] ) ? $fields['value'] : '';
				break;
			case 'email':
				$temp_data['email'] = ! empty( $fields['value'] ) ? sanitize_email( $fields['value'] ) : '';
				break;
			default:
				break;
		}
		return $temp_data;
	}

	/**
	 * Append extra data to temp_data.
	 *
	 * @since 1.7.6
	 *
	 * @param  array $temp_data Temp data.
	 * @param  array $decoded_meta Decoded meta data.
	 * @param  array $payments Payments data.
	 * @param  array $payment_status Payment status.
	 * @param  array $data_to_send Data to send.
	 */
	protected static function append_extra_data_to_temp_data( $temp_data, $decoded_meta, $payments, $payment_status, $data_to_send ) {
		$first_name          = isset( $temp_data['first-name'] ) ? ucfirst( $temp_data['first-name'] ) : '';
		$last_name           = isset( $temp_data['last-name'] ) ? ucfirst( $temp_data['last-name'] ) : '';
		$payment_transaction = self::resolve_payment_transaction_id( $decoded_meta );

		$payment_currency                 = ! empty( $decoded_meta['payment_currency'] ) ? $decoded_meta['payment_currency'] : 'USD';
		$payment_total                    = isset( $decoded_meta['payment_total'] ) ? $decoded_meta['payment_total'] : '';
		$formatted_total                  = $payment_total;

		if ( '' !== $payment_total && function_exists( 'evf_format_amount' ) ) {
			// Decode entities like &#36; so React table displays symbols directly.
			$formatted_total = html_entity_decode( evf_format_amount( $payment_total, true, $payment_currency ), ENT_QUOTES, 'UTF-8' );
		}

		$temp_data['customer']            = trim( $first_name . ' ' . $last_name );
		$temp_data['customer_email']      = isset( $temp_data['email'] ) ? $temp_data['email'] : '';
		$temp_data['total_amount']        = $formatted_total;
		$temp_data['payment_gateway']     = isset( $decoded_meta['payment_gateway'] ) ? ucfirst( $decoded_meta['payment_gateway'] ) : '';
		$temp_data['payment_currency']    = $payment_currency;
		$temp_data['submission_id']       = isset( $payments['entry_id'] ) ? $payments['entry_id'] : '';
		$temp_data['status']              = $payment_status;
		$temp_data['form']                = self::evf_get_form_title( $payments['form_id'] );
		$temp_data['payment_transaction'] = $payment_transaction;
		$temp_data['date_created']        = isset( $payments['date_created'] ) ? $payments['date_created'] : '';

		$temp_data['receipt_url'] = self::get_payment_receipt_url( $decoded_meta, $payment_transaction );

		$data_to_send[] = $temp_data;
		return $data_to_send;
	}

	/**
	 * Get payment status.
	 *
	 * @since 1.7.6
	 *
	 * @param  int $entry_id Entry Id.
	 */
	public static function get_payment_status( $entry_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'evf_entrymeta';

		$sql = $wpdb->prepare(
			"SELECT meta_value FROM {$table_name} WHERE entry_id = %d AND meta_key = %s",
			$entry_id,
			'status'
		);

		$status = $wpdb->get_results( $sql, ARRAY_A );

		return ucfirst( $status[0]['meta_value'] );
	}

	/**
	 * Resolve a gateway transaction ID for the payment log (never a status label).
	 *
	 * @since 1.7.8
	 *
	 * @param array $decoded_meta Payment entry meta.
	 * @return string
	 */
	protected static function resolve_payment_transaction_id( $decoded_meta ) {
		if ( ! is_array( $decoded_meta ) ) {
			return '';
		}

		$transaction  = isset( $decoded_meta['payment_transaction'] ) ? trim( (string) $decoded_meta['payment_transaction'] ) : '';
		$subscription = isset( $decoded_meta['payment_subscription'] ) ? trim( (string) $decoded_meta['payment_subscription'] ) : '';
		$invalid      = array( 'active', 'succeeded', 'trialing', 'pending', 'failed', 'open', 'paid', 'complete', 'canceled', 'cancelled' );

		if ( in_array( strtolower( $transaction ), $invalid, true ) ) {
			$transaction = '';
		}

		if ( '' !== $transaction && preg_match( '/^(pi|ch|in|py|sub|tr)_/i', $transaction ) ) {
			return $transaction;
		}

		$gateway = ! empty( $decoded_meta['payment_gateway'] ) ? self::normalize_gateway_slug( $decoded_meta['payment_gateway'] ) : '';

		if ( 'stripe' === $gateway && '' !== $subscription && preg_match( '/^sub_/i', $subscription ) ) {
			return $subscription;
		}

		// Square subscriptions use UUID subscription IDs (no sub_ prefix).
		if ( 'square' === $gateway && '' === $transaction && '' !== $subscription ) {
			return $subscription;
		}

		return $transaction;
	}

	/**
	 * Whether Stripe entries were created in test mode.
	 *
	 * @since 1.7.8
	 *
	 * @param array $decoded_meta Payment entry meta.
	 * @return bool
	 */
	protected static function is_stripe_test_mode( $decoded_meta ) {
		if ( is_array( $decoded_meta ) && ! empty( $decoded_meta['payment_mode'] ) ) {
			$mode = strtolower( (string) $decoded_meta['payment_mode'] );

			if ( in_array( $mode, array( 'test', 'sandbox' ), true ) ) {
				return true;
			}

			if ( in_array( $mode, array( 'live', 'production' ), true ) ) {
				return false;
			}
		}

		return 'yes' === get_option( 'everest_forms_stripe_test_mode' );
	}

	/**
	 * Stripe Dashboard URL for a payment or subscription ID.
	 *
	 * @since 1.7.8
	 *
	 * @param string $transaction_id Stripe resource ID.
	 * @param bool   $test_mode      Test mode flag.
	 * @return string
	 */
	protected static function get_stripe_dashboard_url( $transaction_id, $test_mode ) {
		$transaction_id = trim( (string) $transaction_id );

		if ( '' === $transaction_id ) {
			return '';
		}

		$base = $test_mode ? 'https://dashboard.stripe.com/test' : 'https://dashboard.stripe.com';

		if ( preg_match( '/^sub_/i', $transaction_id ) ) {
			return $base . '/subscriptions/' . rawurlencode( $transaction_id );
		}

		if ( preg_match( '/^in_/i', $transaction_id ) ) {
			return $base . '/invoices/' . rawurlencode( $transaction_id );
		}

		return $base . '/payments/' . rawurlencode( $transaction_id );
	}

	/**
	 * Mollie Dashboard URL for a payment or subscription ID.
	 *
	 * @since 1.7.8
	 *
	 * @param array  $decoded_meta         Payment entry meta.
	 * @param string $payment_transaction  Mollie payment or subscription ID.
	 * @return string
	 */
	protected static function get_mollie_dashboard_url( $decoded_meta, $payment_transaction ) {
		if ( is_array( $decoded_meta ) && ! empty( $decoded_meta['payment_details_url'] ) ) {
			$url = trim( (string) $decoded_meta['payment_details_url'] );

			if ( '' !== $url && wp_http_validate_url( $url ) ) {
				return $url;
			}
		}

		$payment_transaction = trim( (string) $payment_transaction );

		if ( '' === $payment_transaction ) {
			return '';
		}

		if ( preg_match( '/^tr_/i', $payment_transaction ) ) {
			return 'https://my.mollie.com/dashboard/payments/' . rawurlencode( $payment_transaction );
		}

		if ( preg_match( '/^sub_/i', $payment_transaction ) ) {
			return 'https://my.mollie.com/dashboard/subscriptions/' . rawurlencode( $payment_transaction );
		}

		return '';
	}

	/**
	 * Dashboard or receipt URL for a payment transaction, when available.
	 *
	 * @since 1.7.8
	 *
	 * @param array  $decoded_meta         Payment entry meta.
	 * @param string $payment_transaction  Transaction ID.
	 * @return string
	 */
	protected static function get_payment_receipt_url( $decoded_meta, $payment_transaction ) {
		$payment_transaction = trim( (string) $payment_transaction );

		if ( '' === $payment_transaction || ! is_array( $decoded_meta ) || empty( $decoded_meta['payment_gateway'] ) ) {
			return '';
		}

		$gateway = self::normalize_gateway_slug( $decoded_meta['payment_gateway'] );
		$mode    = ! empty( $decoded_meta['payment_mode'] ) && 'test' === strtolower( (string) $decoded_meta['payment_mode'] ) ? 'test' : 'production';
		$url     = '';

		switch ( $gateway ) {
			case 'stripe':
				$url = self::get_stripe_dashboard_url( $payment_transaction, self::is_stripe_test_mode( $decoded_meta ) );
				break;
			case 'razorpay':
				$url = 'https://dashboard.razorpay.com/app/payments/' . rawurlencode( $payment_transaction );
				break;
			case 'paypal':
			case 'paypal_standard':
				$host = 'test' === $mode ? 'sandbox.' : '';
				$url  = 'https://www.' . $host . 'paypal.com/webscr?cmd=_history-details-from-hub&id=' . rawurlencode( $payment_transaction );
				break;
			case 'mollie':
				$url = self::get_mollie_dashboard_url( $decoded_meta, $payment_transaction );
				break;
			case 'square':
				if ( ! empty( $decoded_meta['receipt_url'] ) ) {
					$url = $decoded_meta['receipt_url'];
				}
				break;
			default:
				break;
		}

		$url = is_string( $url ) ? trim( $url ) : '';

		if ( '' !== $url && ! wp_http_validate_url( $url ) ) {
			$url = '';
		}

		/**
		 * Filter payment log receipt / dashboard URL for a transaction.
		 *
		 * @since 1.7.8
		 *
		 * @param string $url                 Receipt or dashboard URL.
		 * @param array  $decoded_meta        Payment entry meta.
		 * @param string $payment_transaction Transaction ID.
		 */
		return apply_filters( 'everest_forms_payment_log_receipt_url', $url, $decoded_meta, $payment_transaction );
	}

	/**
	 * Normalize payment gateway slug values from multiple sources.
	 *
	 * @since 1.7.8
	 *
	 * @param string $gateway Raw gateway value.
	 * @return string
	 */
	protected static function normalize_gateway_slug( $gateway ) {
		$gateway = strtolower( sanitize_key( (string) $gateway ) );

		$map = array(
			'square_payment' => 'square',
			'squarepayment'  => 'square',
			'square-payment' => 'square',
			'authorize-net'  => 'authorize_net',
			'authorizenet'   => 'authorize_net',
		);

		return isset( $map[ $gateway ] ) ? $map[ $gateway ] : $gateway;
	}

	/**
	 * Get form title.
	 *
	 * @since 1.7.6
	 *
	 * @param  int $form_id Form Id.
	 */
	protected static function evf_get_form_title( $form_id ) {
		if ( empty( $form_id ) ) {
			return;
		}

		return ucfirst( get_post( $form_id )->post_title );
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_everest_forms' );
	}
}
