<?php

/**
 * Square Builder Payment Settings.
 *
 * @package EverestForms\Pro\Addons\Square\Builder
 * @since   1.0.0
 */


namespace  EverestForms\Pro\Addons\Square\Process;

use EverestForms\Pro\Addons\Square\Api\Api;
use EverestForms\Pro\Addons\Square\SubscriptionSchedule;

defined( 'ABSPATH' ) || exit;

/**
 * Payment process class.
 *
 * @since 1.0.0
 */
class Process {

	/**
	 * Last Square subscription plan error (for AJAX responses).
	 *
	 * @var string
	 */
	private $last_subscription_plan_error = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_handle_square_webhook' ), 19 );

		add_action( 'everest_forms_process_complete', array( $this, 'process_entry' ), 20, 4 );

		add_action( 'wp_ajax_everest_forms_square_payment_credit_card', array( $this,'payment_credit_card' ) );
		add_action( 'wp_ajax_nopriv_everest_forms_square_payment_credit_card', array( $this,'payment_credit_card' ) );

		// Ajax Square Status.
		add_action( 'wp_ajax_evf_square_update_entry_square_payment_status', array( $this, 'update_entry_square_status' ) );
		add_action( 'wp_ajax_nopriv_evf_square_update_entry_square_payment_status', array( $this, 'update_entry_square_status' ) );

		// Delete Entry.

		add_action( 'wp_ajax_everest_forms_square_payment_delete_entry_after_failed', array( $this, 'remove_entry_after_failed' ) );
		add_action( 'wp_ajax_nopriv_everest_forms_square_payment_delete_entry_after_failed', array( $this, 'remove_entry_after_failed' ) );

		// Subscription.
		add_action( 'everest_forms_save_form', array( $this, 'sync_square_plans' ), 10, 2 );
		add_action( 'wp_ajax_evf_square_create_subscription', array( $this, 'create_square_subscription' ) );
		add_action( 'wp_ajax_nopriv_evf_square_create_subscription', array( $this, 'create_square_subscription' ) );
	}

	/**
	 * Handle Square webhook callbacks for payment/subscription status sync.
	 *
	 * Endpoint: /?evf-square-webhook=1
	 *
	 * @return void
	 */
	public function maybe_handle_square_webhook() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['evf-square-webhook'] ) ) {
			return;
		}

		$payload   = file_get_contents( 'php://input' );
		$mode      = ( 'yes' === get_option( 'everest_forms_pro_square_test_mode' ) ) ? 'test' : 'live';

		if ( ! is_string( $payload ) || '' === trim( $payload ) ) {
			status_header( 400 );
			echo 'Missing payload'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) ) {
			status_header( 400 );
			echo 'Invalid payload'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		$event_type = isset( $event['type'] ) ? (string) $event['type'] : '';
		$entity     = isset( $event['data']['object'] ) && is_array( $event['data']['object'] ) ? $event['data']['object'] : array();

		// Square webhooks may provide either wrapped entities (payment/subscription keys)
		// or direct resource objects.
		$payment_data = isset( $entity['payment'] ) && is_array( $entity['payment'] ) ? $entity['payment'] : $entity;
		$sub_data     = isset( $entity['subscription'] ) && is_array( $entity['subscription'] ) ? $entity['subscription'] : $entity;

		$payment_id = isset( $payment_data['id'] ) ? (string) $payment_data['id'] : '';
		$sub_id     = isset( $sub_data['id'] ) ? (string) $sub_data['id'] : '';

		$entry_id = 0;
		if ( '' !== $payment_id ) {
			$entry_id = $this->find_square_entry_id_by_meta_fragment( $payment_id );
		}
		if ( ! $entry_id && '' !== $sub_id ) {
			$entry_id = $this->find_square_entry_id_by_meta_fragment( $sub_id );
		}

		if ( $entry_id ) {
			$status = $this->map_square_event_to_status(
				$event_type,
				array(
					'payment'      => $payment_data,
					'subscription' => $sub_data,
				)
			);
			$this->update_square_entry_from_webhook( $entry_id, $status, $payment_id, $sub_id, $event_type );
		}

		status_header( 200 );
		echo 'OK'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Map Square event to EVF status.
	 *
	 * @param string $event_type Event type.
	 * @param array  $entity     Event object payload.
	 * @return string|null
	 */
	private function map_square_event_to_status( $event_type, $entity ) {
		$payment_status = isset( $entity['payment']['status'] ) ? strtoupper( (string) $entity['payment']['status'] ) : '';
		$sub_status     = isset( $entity['subscription']['status'] ) ? strtoupper( (string) $entity['subscription']['status'] ) : '';
		$sub_data       = isset( $entity['subscription'] ) && is_array( $entity['subscription'] ) ? $entity['subscription'] : array();
		$is_cancelled   = ! empty( $sub_data['canceled_date'] ) || ! empty( $sub_data['cancelled_date'] ) || ! empty( $sub_data['canceled_at'] ) || ! empty( $sub_data['cancelled_at'] );

		if ( in_array( $event_type, array( 'payment.created', 'payment.updated' ), true ) ) {
			if ( 'COMPLETED' === $payment_status ) {
				return 'complete';
			}
			if ( in_array( $payment_status, array( 'PENDING', 'APPROVED' ), true ) ) {
				return 'pending';
			}
			if ( in_array( $payment_status, array( 'CANCELED', 'FAILED' ), true ) ) {
				return 'failed';
			}
		}

		if ( 0 === strpos( $event_type, 'subscription.' ) ) {
			if ( $is_cancelled ) {
				return 'cancelled';
			}
			if ( in_array( $sub_status, array( 'ACTIVE' ), true ) ) {
				return 'complete';
			}
			if ( in_array( $sub_status, array( 'PENDING' ), true ) ) {
				return 'pending';
			}
			if ( in_array( $sub_status, array( 'CANCELED', 'CANCELLED', 'DEACTIVATED' ), true ) ) {
				return 'cancelled';
			}
		}

		return null;
	}

	/**
	 * Find square entry by payment/subscription id in meta JSON.
	 *
	 * @param string $needle Fragment.
	 * @return int
	 */
	private function find_square_entry_id_by_meta_fragment( $needle ) {
		global $wpdb;

		$needle = trim( (string) $needle );
		if ( '' === $needle ) {
			return 0;
		}
		$table = $wpdb->prefix . 'evf_entrymeta';
		$like  = '%' . $wpdb->esc_like( $needle ) . '%';
		$sql   = $wpdb->prepare(
			"SELECT entry_id FROM {$table} WHERE meta_key = %s AND meta_value LIKE %s ORDER BY entry_id DESC LIMIT 1",
			'meta',
			$like
		);
		$entry_id = (int) $wpdb->get_var( $sql );
		if ( ! $entry_id ) {
			return 0;
		}
		$entry = evf_get_entry( $entry_id );
		if ( empty( $entry ) || empty( $entry->meta['meta'] ) ) {
			return 0;
		}
		$meta = evf_decode( $entry->meta['meta'] );
		if ( ! is_array( $meta ) || empty( $meta['payment_gateway'] ) || 'square' !== (string) $meta['payment_gateway'] ) {
			return 0;
		}
		return $entry_id;
	}

	/**
	 * Update square payment entry from webhook.
	 *
	 * @param int         $entry_id    Entry id.
	 * @param string|null $status      Status.
	 * @param string      $payment_id  Payment id.
	 * @param string      $sub_id      Subscription id.
	 * @param string      $event_type  Event type.
	 * @return void
	 */
	private function update_square_entry_from_webhook( $entry_id, $status, $payment_id, $sub_id, $event_type ) {
		$entry = evf_get_entry( absint( $entry_id ) );
		if ( empty( $entry ) || empty( $entry->meta['meta'] ) ) {
			return;
		}
		$meta = evf_decode( $entry->meta['meta'] );
		$meta = is_array( $meta ) ? $meta : array();
		if ( empty( $meta['payment_gateway'] ) || 'square' !== (string) $meta['payment_gateway'] ) {
			return;
		}

		$current_status = '';
		if ( isset( $entry->meta['status'] ) ) {
			$current_status = strtolower( trim( (string) $entry->meta['status'] ) );
		}
		if ( '' !== $payment_id ) {
			$meta['payment_transaction'] = sanitize_text_field( $payment_id );
		}
		if ( '' !== $sub_id ) {
			$meta['payment_subscription'] = sanitize_text_field( $sub_id );
		}
		$meta['square_webhook_event'] = sanitize_text_field( $event_type );

		$update = array( 'meta' => wp_json_encode( $meta ) );
		if ( null !== $status && '' !== $status ) {
			// Keep an entry in trialing while subscription is ACTIVE on Square trial periods.
			if ( 'trialing' === $current_status && 'complete' === strtolower( (string) $status ) && 0 === strpos( (string) $event_type, 'subscription.' ) ) {
				$status = 'trialing';
			}
			$update['status'] = $status;
		}
		evf_payment_entries( absint( $entry_id ), $update, true );
		wp_cache_delete( absint( $entry_id ), 'evf-entrymeta' );
		wp_cache_delete( absint( $entry_id ), 'evf-entry' );
	}

	/**
	 * Payment through credit card.
	 *
	 * @since 1.0.0
	 */
	public function payment_credit_card() {
		check_ajax_referer( 'evf_square_payment_nonce', 'security' );

		if ( empty( $_POST ) ) {
			return;
		}

		if ( ! isset( $_POST['source_id'] ) || empty( $_POST['source_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid card token. Please try again.', 'everest-forms-pro' ) ) );
			return;
		}

		try {
			Api::create_payment( $_POST );
		} catch ( \Throwable $e ) {
			$logger = evf_get_logger();
			$logger->error(
				'Square payment exception: ' . $e->getMessage(),
				array( 'source' => 'square-payment' )
			);
			wp_send_json_error( array( 'message' => __( 'Payment processing failed. Please try again.', 'everest-forms-pro' ) ) );
		}
	}

	/**
	 * Process entry for square payment
	 *
	 * @param array  $fields Fields Data.
	 * @param array  $entry  Entry Data.
	 * @param array  $form_data Form Data.
	 * @param string $entry_id Entry Id.
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id ) {
		if ( ! $this->is_square_enabled( $form_data ) ) {
			return false;
		}

		$process = apply_filters( 'everest_forms_entry_payment_process', true, $fields, $form_data, 'square', 'connection_1' );
		if ( ! $process ) {
			return false;
		}

		$payment_fields = evf_get_payment_items( $fields, $entry, $form_data );
		$total_amount   = evf_sanitize_amount( evf_get_total_payment( $fields, $entry, $form_data ) );
		$discount       = apply_filters( 'everest_forms_coupon_discount', 0, $total_amount, $fields, $form_data );
		$total_amount  -= $discount;

		if ( $total_amount <= 0 ) {
			return;
		}

		$square_active = ! empty( $form_data['payments']['square']['enable_square'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) ) );
		if ( empty( $entry_id ) || ! $square_active || empty( $payment_fields ) || empty( $total_amount ) ) {
			return;
		} else {
			if ( 'no' === get_option( 'everest_forms_pro_square_test_mode' ) ) {
				$payment_mode = __( 'Live', 'everest-forms-pro' );
			} else {
				$payment_mode = __( 'Test', 'everest-forms-pro' );
			}

			// Update entry to include payment details.
			$entry_data = array(
				'status' => 'Pending',
				'type'   => 'payment',
				'meta'   => wp_json_encode(
					array(
						'payment_gateway'     => 'square',
						'payment_discount'    => $discount,
						'payment_total'       => $total_amount,
						'payment_currency'    => get_option( 'everest_forms_currency' ),
						'payment_mode'        => $payment_mode,
						'payment_transaction' => '',
					)
				),
			);

			evf_payment_entries( $entry_id, $entry_data );
		}
	}

	/**
	 * Update entry stripe status.
	 *
	 * @return void
	 */
	public function update_entry_square_status() {
		check_ajax_referer( 'evf_square_payment_nonce', 'security' );

		if ( empty( $_POST ) ) {
			return;
		}

		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['entry_id'] ) ) ) && ! empty( $_POST ) ) {
			$transaction_id              = isset( $_POST['payment_data']['payment']['id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_data']['payment']['id'] ) ) : '';
			$receipt_url                 = isset( $_POST['payment_data']['payment']['receipt_url'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_data']['payment']['receipt_url'] ) ) : '';
			$payment_status              = isset( $_POST['payment_data']['payment']['status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_data']['payment']['status'] ) ) : '';
			$entry_id                    = sanitize_text_field( wp_unslash( $_POST['entry_id'] ) );
			$entry                       = evf_get_entry( $entry_id );
			$form_data                   = evf()->form->get( $entry->form_id, array( 'content_only' => true ) );
			$meta                        = evf_decode( $entry->meta['meta'] );
			$meta['receipt_url']         = $receipt_url;
			$meta['transaction_id']      = $transaction_id;
			$meta['payment_transaction'] = $transaction_id;

			if ( 'COMPLETED' === $payment_status ) {
				$entry_update = array(
					'status' => 'Complete',
					'meta'   => wp_json_encode( $meta ),
				);
			} else {
				$entry_update = array(
					'status' => 'Failed',
					'meta'   => wp_json_encode( $meta ),
				);
			}

			// Update payment info based on stripe server response.
			evf_payment_entries( $entry_id, $entry_update, true );

			// Sync the transaction ID into the square-payment form field value so it appears in entry logs.
			if ( ! empty( $transaction_id ) ) {
				global $wpdb;
				$raw_fields = $wpdb->get_var( $wpdb->prepare( "SELECT fields FROM {$wpdb->prefix}evf_entries WHERE entry_id = %d", $entry_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				if ( $raw_fields ) {
					$decoded_fields = json_decode( $raw_fields, true );
					if ( is_array( $decoded_fields ) ) {
						foreach ( $decoded_fields as $field_key => $field_data ) {
							if ( isset( $field_data['type'] ) && 'square-payment' === $field_data['type'] ) {
								$decoded_fields[ $field_key ]['value'] = $transaction_id;
								break;
							}
						}
						$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
							$wpdb->prefix . 'evf_entries',
							array( 'fields' => wp_json_encode( $decoded_fields ) ),
							array( 'entry_id' => absint( $entry_id ) )
						);
					}
				}
			}

			$fields                 = json_decode( $entry->fields, true );
			$entry                  = array();
			$entry['form_fields']   = array();
			$entry['hp']            = '';
			$entry['id']            = $entry_id;
			$entry['author']        = '';
			$data['entry']          = $entry;
			$data['fields']         = $fields;
			$data['payment_status'] = isset( $entry_update['status'] ) ? $entry_update['status'] : 'Failed';

			// After Square Payment Complete.
			do_action( 'everest_forms_square_process_complete', $fields, $form_data, $entry_id, $data );

			wp_send_json_success(
				array( 'message' => esc_html__( 'Payment made successfully', 'everest-forms-pro' ) )
			);
		}
	}

	/**
	 * Remove the entry after payment failed.
	 *
	 * @since 1.7.7
	 */
	public function remove_entry_after_failed() {
		check_ajax_referer( 'evf_square_payment_nonce', 'security' );

		if ( isset( $_POST['entry_id'], $_POST['action'] ) && 'everest_forms_square_payment_delete_entry_after_failed' === $_POST['action'] ) {
			$result = false;
			if ( class_exists( 'EVF_Admin_Entries' ) ) {
				$form_id  = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
				$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
				$result   = \EVF_Admin_Entries::remove_entry( $entry_id, $form_id );
			}
			if ( $result ) {
				wp_send_json_success( array( 'message' => esc_html__( 'Entry removed.', 'everest-forms-pro' ) ) );
			} else {
				wp_send_json_error( array( 'message' => esc_html__( 'Failed to remove entry.', 'everest-forms-pro' ) ) );
			}
		}
		wp_send_json_error(
			array( 'message' => esc_html__( 'Bad request.', 'everest-forms-pro' ) )
		);
	}

	/**
	 * Check the Square is enable or not, if enabled return true otherwise false.
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function is_square_enabled( $form_data ) {
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

		if ( '1' !== $is_square_enabled ) {
			return false;
		}

		// When payment-gateway-selector exists on the form, respect explicit user choice.
		if ( null !== $gateway_choice ) {
			return 'square' === $gateway_choice;
		}

		if ( '0' === $is_paypal_enabled && '0' === $is_stripe_enabled && '0' === $is_authorize_net_enabled && '0' === $is_razorpay_enabled ) {
			return true;
		}

		return false;
	}

	/**
	 * Sync payment-subscription-plan choices to Square Catalog on form save.
	 *
	 * @param int   $form_id   Saved form ID.
	 * @param array $form_data Freshly saved form data.
	 */
	public function sync_square_plans( $form_id, $form_data = array() ) {
		if ( empty( $form_data ) ) {
			$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );
		}

		if ( empty( $form_data ) ) {
			return;
		}

		$payments = isset( $form_data['payments'] ) ? $form_data['payments'] : array();

		$square_active = isset( $payments['square']['enable_square'] ) && '1' === $payments['square']['enable_square'];
		if ( ! $square_active && ! ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) ) ) ) {
			return;
		}

		$subscription_plan_field = evf_get_form_data_by_key( $form_data, 'payment-subscription-plan' );
		if ( empty( $subscription_plan_field ) ) {
			return;
		}

		$plan_choices = isset( $subscription_plan_field[0]['choices'] ) ? $subscription_plan_field[0]['choices'] : array();
		if ( empty( $plan_choices ) ) {
			return;
		}

		$existing_plans = get_post_meta( $form_id, '_square_subscription_plans', true );
		$existing_plans = is_array( $existing_plans ) ? $existing_plans : array();
		$updated_plans  = array();

		foreach ( $plan_choices as $choice_key => $choice ) {
			$plan_label     = isset( $choice['label'] ) ? $choice['label'] : '';
			$plan_amount    = isset( $choice['value'] ) ? evf_sanitize_amount( $choice['value'] ) : 0;
			$period         = isset( $choice['recurring_period'] ) ? $choice['recurring_period'] : 'month';
			$interval_count = isset( $choice['interval_count'] ) ? max( 1, absint( $choice['interval_count'] ) ) : 1;

			if ( empty( $plan_label ) || empty( $plan_amount ) ) {
				continue;
			}

			$schedule          = SubscriptionSchedule::build( $choice );
			$plan_args         = Api::plan_args_from_schedule( $schedule, $choice );
			$config_hash       = Api::get_plan_config_hash( $plan_label, $plan_amount, $period, $interval_count, $plan_args );
			$plan_variation_id = '';
			$needs_upsert      = true;

			foreach ( $existing_plans as $ep ) {
				if ( ! empty( $ep['choice_key'] ) && (string) $ep['choice_key'] === (string) $choice_key ) {
					$plan_variation_id = isset( $ep['plan_variation_id'] ) ? $ep['plan_variation_id'] : '';
					$stored_trial      = ! empty( $ep['trial_enabled'] );
					$current_trial     = ! empty( $plan_args['trial_enabled'] );
					$stored_prorated   = ! empty( $ep['use_prorated_expiry'] );
					$current_prorated  = ! empty( $plan_args['use_prorated_expiry'] );
					if (
						! empty( $ep['plan_config_hash'] )
						&& $ep['plan_config_hash'] === $config_hash
						&& ! empty( $plan_variation_id )
						&& $stored_trial === $current_trial
						&& $stored_prorated === $current_prorated
					) {
						$needs_upsert = false;
					}
					break;
				}
			}

			if ( $needs_upsert ) {
				try {
					$plan_variation_id = Api::upsert_subscription_plan(
						$plan_label,
						$plan_amount,
						$period,
						$interval_count,
						$plan_args
					);
				} catch ( \Exception $e ) {
					evf_get_logger()->critical( $e->getMessage(), array( 'source' => 'square-subscription' ) );
					continue;
				}
			}

			if ( empty( $plan_variation_id ) ) {
				continue;
			}

			$updated_plans[] = $this->build_square_plan_meta_row( $choice_key, $plan_label, $plan_variation_id, $period, $interval_count, $choice );
		}

		update_post_meta( $form_id, '_square_subscription_plans', $updated_plans );
	}

	/**
	 * Build stored plan meta row for _square_subscription_plans.
	 *
	 * @param string $choice_key        Choice index.
	 * @param string $plan_label        Plan label.
	 * @param string $plan_variation_id Square variation ID.
	 * @param string $period            Recurring period key.
	 * @param int    $interval_count    Interval count.
	 * @param array  $choice            Full choice array from form_fields.
	 * @return array
	 */
	private function build_square_plan_meta_row( $choice_key, $plan_label, $plan_variation_id, $period, $interval_count, $choice ) {
		$schedule  = SubscriptionSchedule::build( $choice );
		$plan_args = Api::plan_args_from_schedule( $schedule, $choice );

		return array_merge(
			array(
				'choice_key'        => (string) $choice_key,
				'plan_name'         => $plan_label,
				'plan_variation_id' => $plan_variation_id,
				'period'            => $period,
				'interval_count'    => $interval_count,
				'plan_config_hash'  => Api::get_plan_config_hash( $plan_label, isset( $choice['value'] ) ? evf_sanitize_amount( $choice['value'] ) : 0, $period, $interval_count, $plan_args ),
			),
			$plan_args
		);
	}

	/**
	 * Resolve or create Square plan variation ID for a subscription choice.
	 *
	 * @param int    $form_id           Form ID.
	 * @param array  $stored_plans      Existing meta rows.
	 * @param string $choice_key        Selected choice key.
	 * @param string $plan_label        Plan label.
	 * @param array  $choice            Choice configuration.
	 * @return string plan_variation_id or empty string.
	 */
	private function resolve_square_plan_variation_id( $form_id, $stored_plans, $choice_key, $plan_label, $choice ) {
		$this->last_subscription_plan_error = '';

		$plan_amount    = isset( $choice['value'] ) ? evf_sanitize_amount( $choice['value'] ) : 0;
		$period         = isset( $choice['recurring_period'] ) ? $choice['recurring_period'] : 'month';
		$interval_count = isset( $choice['interval_count'] ) ? max( 1, absint( $choice['interval_count'] ) ) : 1;
		$schedule       = SubscriptionSchedule::build( $choice );
		$plan_args      = Api::plan_args_from_schedule( $schedule, $choice );
		$config_hash    = Api::get_plan_config_hash( $plan_label, $plan_amount, $period, $interval_count, $plan_args );
		$plan_variation_id = '';

		foreach ( $stored_plans as $plan ) {
			if ( ! empty( $plan['choice_key'] ) && (string) $plan['choice_key'] === (string) $choice_key ) {
				$plan_variation_id = isset( $plan['plan_variation_id'] ) ? $plan['plan_variation_id'] : '';
				$stored_trial      = ! empty( $plan['trial_enabled'] );
				$current_trial     = ! empty( $plan_args['trial_enabled'] );
				$stored_prorated   = ! empty( $plan['use_prorated_expiry'] );
				$current_prorated  = ! empty( $plan_args['use_prorated_expiry'] );
				if (
					! empty( $plan['plan_config_hash'] )
					&& $plan['plan_config_hash'] === $config_hash
					&& ! empty( $plan_variation_id )
					&& $stored_trial === $current_trial
					&& $stored_prorated === $current_prorated
				) {
					return $plan_variation_id;
				}
				break;
			}
		}

		if ( empty( $plan_variation_id ) ) {
			foreach ( $stored_plans as $plan ) {
				if ( isset( $plan['plan_name'] ) && $plan['plan_name'] === $plan_label && ! empty( $plan['plan_variation_id'] ) ) {
					if ( ! empty( $plan['plan_config_hash'] ) && $plan['plan_config_hash'] === $config_hash ) {
						return $plan['plan_variation_id'];
					}
					$plan_variation_id = $plan['plan_variation_id'];
					break;
				}
			}
		}

		try {
			$plan_variation_id = Api::upsert_subscription_plan(
				$plan_label,
				$plan_amount,
				$period,
				$interval_count,
				$plan_args
			);
		} catch ( \Exception $e ) {
			$this->last_subscription_plan_error = $e->getMessage();
			evf_get_logger()->critical( $e->getMessage(), array( 'source' => 'square-subscription' ) );
			return '';
		}

		$updated = false;
		foreach ( $stored_plans as $index => $plan ) {
			if ( ! empty( $plan['choice_key'] ) && (string) $plan['choice_key'] === (string) $choice_key ) {
				$stored_plans[ $index ] = $this->build_square_plan_meta_row( $choice_key, $plan_label, $plan_variation_id, $period, $interval_count, $choice );
				$updated                = true;
				break;
			}
		}

		if ( ! $updated ) {
			$stored_plans[] = $this->build_square_plan_meta_row( $choice_key, $plan_label, $plan_variation_id, $period, $interval_count, $choice );
		}

		update_post_meta( $form_id, '_square_subscription_plans', $stored_plans );

		return $plan_variation_id;
	}

	/**
	 * AJAX: create a Square subscription for the selected plan.
	 *
	 * Called before form submission. Returns subscription_id to JS which
	 * injects it as a hidden input before native form submit.
	 */
	public function create_square_subscription() {
		check_ajax_referer( 'evf_square_payment_nonce', 'security' );

		Api::$defer_trial_to_start_date = false;

		try {
			$form_id         = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$entry_id        = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$field_id        = isset( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$choice_key      = isset( $_POST['choice_key'] ) ? sanitize_text_field( wp_unslash( $_POST['choice_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$source_id       = isset( $_POST['source_id'] ) ? sanitize_text_field( wp_unslash( $_POST['source_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$location_id = isset( $_POST['location_id'] ) ? sanitize_text_field( wp_unslash( $_POST['location_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( ! $form_id || ! $source_id || ! $location_id ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Missing required parameters.', 'everest-forms-pro' ) ) );
			}

			// Fresh keys per request — Square rejects reused keys when source_id/body changes (common on subscription checkout).
			$card_idempotency_key = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'evf_sq_card_', true );
			$sub_idempotency_key  = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'evf_sq_sub_', true );

			$form      = EVF()->form->get( $form_id );
			$form_data = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : array();
			$payments  = isset( $form_data['payments'] ) ? $form_data['payments'] : array();

			// Customer fields extracted directly from DOM by JS — no meta_key matching needed.
			$customer_given_name  = isset( $_POST['customer_given_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_given_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$customer_family_name = isset( $_POST['customer_family_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_family_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$customer_email       = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$customer_phone       = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			$sub_field  = isset( $form_data['form_fields'][ $field_id ] ) ? $form_data['form_fields'][ $field_id ] : array();
			$plan_label = isset( $sub_field['choices'][ $choice_key ]['label'] ) ? $sub_field['choices'][ $choice_key ]['label'] : '';
			if ( empty( $plan_label ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Plan not found.', 'everest-forms-pro' ) ) );
			}

			// Find existing customer by email or create new one.
			$customer_id   = '';
			$square_client = \EverestForms\Pro\Addons\Square\Api\Api::get_client();

			if ( ! empty( $customer_email ) ) {
				$customers_list = $square_client->getCustomersApi()->listCustomers();
				if ( $customers_list->isSuccess() && $customers_list->getResult()->getCustomers() ) {
					foreach ( $customers_list->getResult()->getCustomers() as $existing_customer ) {
						if ( $existing_customer->getEmailAddress() === $customer_email ) {
							$customer_id = $existing_customer->getId();
							break;
						}
					}
				}
			}

			if ( empty( $customer_id ) ) {
				$customer_body = new \Square\Models\CreateCustomerRequest();
				if ( ! empty( $customer_given_name ) ) {
					$customer_body->setGivenName( $customer_given_name );
				}
				if ( ! empty( $customer_family_name ) ) {
					$customer_body->setFamilyName( $customer_family_name );
				}
				if ( ! empty( $customer_email ) ) {
					$customer_body->setEmailAddress( $customer_email );
				}
				if ( ! empty( $customer_phone ) ) {
					$phone_e164 = preg_replace( '/[^\d+]/', '', $customer_phone );
					if ( ! empty( $phone_e164 ) ) {
						$customer_body->setPhoneNumber( $phone_e164 );
					}
				}

				$create_customer_response = $square_client->getCustomersApi()->createCustomer( $customer_body );

				if ( $create_customer_response->isSuccess() ) {
					$customer_id = $create_customer_response->getResult()->getCustomer()->getId();
				} else {
					$errors = $create_customer_response->getErrors();
					$detail = ! empty( $errors[0] ) ? $errors[0]->getDetail() : 'Customer creation failed';
					wp_send_json_error( array( 'message' => esc_html( $detail ) ) );
					return;
				}
			}

			$card_id = \EverestForms\Pro\Addons\Square\Api\Api::save_customer_card(
				$customer_id,
				$source_id,
				$card_idempotency_key
			);

			$choice         = isset( $sub_field['choices'][ $choice_key ] ) ? $sub_field['choices'][ $choice_key ] : array();
			$schedule       = SubscriptionSchedule::build( $choice );
			$plan_args      = Api::plan_args_from_schedule( $schedule, $choice );
			$stored_plans   = get_post_meta( $form_id, '_square_subscription_plans', true );
			$stored_plans   = is_array( $stored_plans ) ? $stored_plans : array();
			$plan_variation_id = $this->resolve_square_plan_variation_id( $form_id, $stored_plans, $choice_key, $plan_label, $choice );

			if ( empty( $plan_variation_id ) ) {
				$message = $this->last_subscription_plan_error
					? $this->last_subscription_plan_error
					: __( 'Unable to prepare subscription plan. Please save the form and try again.', 'everest-forms-pro' );
				wp_send_json_error( array( 'message' => esc_html( $message ) ) );
			}

			$client = Api::get_client();

			$subscription_request = new \Square\Models\CreateSubscriptionRequest( $location_id, $customer_id );
			$subscription_request->setPlanVariationId( $plan_variation_id );
			$subscription_request->setCardId( $card_id );
			$subscription_request->setIdempotencyKey( $sub_idempotency_key );
			Api::apply_subscription_expiry( $subscription_request, $plan_args );
			Api::apply_subscription_billing_start( $subscription_request, $plan_args );
			if ( Api::$defer_trial_to_start_date ) {
				Api::apply_subscription_trial_start( $subscription_request, $plan_args );
			}

			$subscription_response = $client->getSubscriptionsApi()->createSubscription( $subscription_request );

			if ( $subscription_response->isSuccess() ) {
				$subscription      = $subscription_response->getResult()->getSubscription();
				$subscription_id   = $subscription->getId();
				$entry_status      = ! empty( $schedule['entry_trialing'] ) ? 'Trialing' : 'Complete';

				if ( $entry_id ) {
					$entry      = evf_get_entry( $entry_id );
					$entry_meta = evf_decode( $entry->meta['meta'] );

					$entry_meta['payment_subscription'] = $subscription_id;
					$entry_meta['payment_transaction']  = '';

					evf_payment_entries(
						$entry_id,
						array(
							'status' => $entry_status,
							'meta'   => wp_json_encode( $entry_meta ),
						),
						true
					);
				}

				wp_send_json_success( array( 'subscription_id' => $subscription_id ) );
			} else {
				$errors = $subscription_response->getErrors();
				$detail = ! empty( $errors[0] ) ? $errors[0]->getDetail() : 'Subscription creation failed';
				wp_send_json_error( array( 'message' => esc_html( $detail ) ) );
			}
		} catch ( \Exception $e ) {
			evf_get_logger()->critical( $e->getMessage(), array( 'source' => 'square-subscription' ) );
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}
}
