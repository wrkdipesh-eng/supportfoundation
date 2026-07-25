<?php
/**
 * EverestForms Mollie processing class.
 *
 * @package EverestForms\Pro\Addons\Mollie\Process
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Mollie\Process;

use EverestForms\Pro\Addons\Mollie\Helpers;
use EverestForms\Pro\Addons\Mollie\SubscriptionSchedule;
use Mollie\Api;

/**
 * Mollie Post Processing Process.
 */
class Process {


	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_handle_mollie_webhook' ), 19 );
		add_action( 'init', array( $this, 'evf_mollie_payment_update' ), 20 );
		add_action( 'everest_forms_process_complete', array( $this, 'process_entry' ), 20, 4 );
		add_filter( 'evf_payment_fields', array( $this, 'add_subscription_plan_field' ) );
	}

	/**
	 * Handle Mollie webhook callbacks for status synchronization.
	 *
	 * Endpoint: /?everest_forms_mollie_webhook=webhook
	 *
	 * @return void
	 */
	public function maybe_handle_mollie_webhook() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['everest_forms_mollie_webhook'] ) ) {
			return;
		}

		$resource_id = '';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST['id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$resource_id = sanitize_text_field( wp_unslash( $_POST['id'] ) );
		}

		if ( '' === $resource_id ) {
			$raw = file_get_contents( 'php://input' );
			if ( is_string( $raw ) && '' !== trim( $raw ) ) {
				$decoded = json_decode( $raw, true );
				if ( is_array( $decoded ) && ! empty( $decoded['id'] ) ) {
					$resource_id = sanitize_text_field( (string) $decoded['id'] );
				}
			}
		}

		if ( '' === $resource_id ) {
			status_header( 200 );
			echo 'OK'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		$mollie = new \Mollie\Api\MollieApiClient();
		$mollie->setApiKey( Helpers::get_mollie_api_key() );

		try {
			$resource_status = '';
			$resource_data   = array();
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST['status'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$resource_status = sanitize_text_field( wp_unslash( $_POST['status'] ) );
			}
			if ( isset( $decoded ) && is_array( $decoded ) ) {
				$resource_data = $decoded;
				if ( '' === $resource_status && ! empty( $decoded['status'] ) ) {
					$resource_status = sanitize_text_field( (string) $decoded['status'] );
				}
			}

			if ( 0 === strpos( $resource_id, 'tr_' ) ) {
				$payment = $mollie->payments->get( $resource_id );
				$entry_id = 0;

				if ( isset( $payment->metadata ) && is_object( $payment->metadata ) && ! empty( $payment->metadata->entry_id ) ) {
					$entry_id = absint( $payment->metadata->entry_id );
				}
				if ( ! $entry_id ) {
					$entry_id = $this->find_entry_id_by_meta_fragment( $resource_id, 'mollie' );
				}

				if ( $entry_id ) {
					$this->update_mollie_entry_from_payment( $entry_id, $payment );
				}
			} elseif ( 0 === strpos( $resource_id, 'sub_' ) ) {
				$entry_id = $this->find_entry_id_by_meta_fragment( $resource_id, 'mollie' );
				if ( $entry_id ) {
					$this->update_mollie_entry_from_subscription( $entry_id, $resource_id, $resource_status, $resource_data );
				}
			}
		} catch ( \Exception $exception ) {
			if ( function_exists( 'evf_get_logger' ) ) {
				evf_get_logger()->error(
					'Mollie webhook processing failed.',
					array(
						'source'      => 'mollie',
						'resource_id' => $resource_id,
						'message'     => $exception->getMessage(),
					)
				);
			}
			status_header( 500 );
			echo 'Webhook error'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		status_header( 200 );
		echo 'OK'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Find payment entry by matching a payment/subscription id in meta JSON.
	 *
	 * @param string $needle  Id fragment.
	 * @param string $gateway Expected gateway slug.
	 * @return int
	 */
	private function find_entry_id_by_meta_fragment( $needle, $gateway ) {
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
		if ( ! is_array( $meta ) || empty( $meta['payment_gateway'] ) || $gateway !== (string) $meta['payment_gateway'] ) {
			return 0;
		}

		return $entry_id;
	}

	/**
	 * Map Mollie payment status to EVF status.
	 *
	 * @param string $mollie_status Mollie status.
	 * @return string
	 */
	private function map_mollie_payment_status_to_evf( $mollie_status ) {
		$status = evf_strtolower( trim( (string) $mollie_status ) );

		switch ( $status ) {
			case 'paid':
				return 'complete';
			case 'authorized':
			case 'open':
			case 'pending':
				return 'pending';
			case 'canceled':
			case 'cancelled':
			case 'expired':
				return 'cancelled';
			case 'refunded':
			case 'charged_back':
				return 'refunded';
			default:
				return 'failed';
		}
	}

	/**
	 * Update EVF entry from a Mollie payment object.
	 *
	 * @param int    $entry_id Entry id.
	 * @param object $payment  Mollie payment.
	 * @return void
	 */
	private function update_mollie_entry_from_payment( $entry_id, $payment ) {
		$entry = evf_get_entry( absint( $entry_id ) );
		if ( empty( $entry ) || empty( $entry->meta['meta'] ) ) {
			return;
		}

		$meta = evf_decode( $entry->meta['meta'] );
		$meta = is_array( $meta ) ? $meta : array();
		if ( empty( $meta['payment_gateway'] ) || 'mollie' !== (string) $meta['payment_gateway'] ) {
			return;
		}

		$mollie_status = isset( $payment->status ) ? (string) $payment->status : '';
		$new_status    = $this->map_mollie_payment_status_to_evf( $mollie_status );

		if ( ! empty( $payment->id ) ) {
			$meta['payment_transaction'] = sanitize_text_field( (string) $payment->id );
		}
		if ( isset( $payment->_links->dashboard->href ) ) {
			$meta['payment_details_url'] = esc_url_raw( (string) $payment->_links->dashboard->href );
		}
		$meta['mollie_payment_status'] = $mollie_status;

		evf_payment_entries(
			absint( $entry_id ),
			array(
				'status' => $new_status,
				'meta'   => wp_json_encode( $meta ),
			),
			true
		);
		wp_cache_delete( absint( $entry_id ), 'evf-entrymeta' );
		wp_cache_delete( absint( $entry_id ), 'evf-entry' );
	}

	/**
	 * Update EVF entry from a Mollie subscription status.
	 *
	 * @param int    $entry_id         Entry id.
	 * @param string $subscription_id  Mollie subscription id.
	 * @return void
	 */
	private function update_mollie_entry_from_subscription( $entry_id, $subscription_id, $subscription_status = '', $resource_data = array() ) {
		$entry = evf_get_entry( absint( $entry_id ) );
		if ( empty( $entry ) || empty( $entry->meta['meta'] ) ) {
			return;
		}

		$meta = evf_decode( $entry->meta['meta'] );
		$meta = is_array( $meta ) ? $meta : array();
		if ( empty( $meta['payment_gateway'] ) || 'mollie' !== (string) $meta['payment_gateway'] ) {
			return;
		}

		$entry_status = 'pending';
		$normalized   = evf_strtolower( trim( (string) $subscription_status ) );
		if ( '' !== $normalized ) {
			if ( in_array( $normalized, array( 'active', 'completed' ), true ) ) {
				$entry_status = 'complete';
			} elseif ( in_array( $normalized, array( 'canceled', 'cancelled', 'suspended' ), true ) ) {
				$entry_status = 'cancelled';
			} elseif ( in_array( $normalized, array( 'pending', 'open' ), true ) ) {
				$entry_status = 'pending';
			} elseif ( in_array( $normalized, array( 'failed', 'expired' ), true ) ) {
				$entry_status = 'failed';
			}
		} elseif ( ! empty( $meta['mollie_payment_status'] ) ) {
			$entry_status = $this->map_mollie_payment_status_to_evf( $meta['mollie_payment_status'] );
		}

		$meta['payment_subscription']        = sanitize_text_field( (string) $subscription_id );
		$meta['mollie_subscription_status']  = '' !== $normalized ? $normalized : 'webhook';
		if ( is_array( $resource_data ) && ! empty( $resource_data ) ) {
			$meta['mollie_subscription_webhook_payload'] = wp_json_encode( $resource_data );
		}

		evf_payment_entries(
			absint( $entry_id ),
			array(
				'status' => $entry_status,
				'meta'   => wp_json_encode( $meta ),
			),
			true
		);
		wp_cache_delete( absint( $entry_id ), 'evf-entrymeta' );
		wp_cache_delete( absint( $entry_id ), 'evf-entry' );
	}

	/**
	 * Include subscription plan field as a recognized payment field.
	 *
	 * @param array $fields Payment field types.
	 * @return array
	 */
	public function add_subscription_plan_field( $fields ) {
		$fields[] = 'payment-subscription-plan';
		return $fields;
	}

	/**
	 * Process entry for PayPal Standard.
	 *
	 * @param  array   $fields    Fields for the entry processing.
	 * @param  array   $entry     Entry object itself.
	 * @param  array   $form_data Form data for mapping purpose.
	 * @param  integer $entry_id  Entry id fetched from form task.
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id ) {
		if ( ! Helpers::is_mollie_enabled( $form_data ) ) {
			return false;
		}

		$data           = array();
		$payment_fields = evf_get_payment_items( $fields, $entry, $form_data );
		$total_amount   = evf_sanitize_amount( evf_get_total_payment( $fields, $entry, $form_data ) );
		$process        = apply_filters( 'everest_forms_entry_payment_process', true, $fields, $form_data, 'mollie', 'connection_1' );
		$discount       = apply_filters( 'everest_forms_coupon_discount', 0, $total_amount, $fields, $form_data );
		$total_amount  -= $discount;

		if ( $total_amount <= 0 || ! $process || empty( $entry_id ) || empty( $payment_fields ) || empty( $total_amount ) ) {

			return false;

		} else {
			// Update entry to include payment details.
			$entry_data = array(
				'status' => 'Pending',
				'type'   => 'payment',
				'meta'   => wp_json_encode(
					array(
						'payment_gateway'  => 'mollie',
						'payment_discount' => $discount,
						'payment_total'    => $total_amount,
						'payment_currency' => get_option( 'everest_forms_currency' ),
						'payment_mode'     => Helpers::get_mollie_mode(),
					)
				),
			);

			evf_payment_entries( $entry_id, $entry_data );
		}

		$mollie         = new \Mollie\Api\MollieApiClient();
		$mollie_api_key = Helpers::get_mollie_api_key();
		$mollie->setApiKey( $mollie_api_key );

		$webhook_url = Helpers::get_mollie_webhook_url( $form_data )
		? add_query_arg( array( 'everest_forms_mollie_webhook' => 'webhook' ), Helpers::get_mollie_webhook_url( $form_data ) )
		: '';

		// Build the return URL with hash.
		$query_args = 'form_id=' . $form_data['id'] . '&entry_id=' . $entry_id . '&hash=' . wp_hash( $form_data['id'] . ',' . $entry_id );
		if ( isset( $form_data['payments']['mollie']['redirect_url'] ) && ! empty( $form_data['payments']['mollie']['redirect_url'] ) ) {
			$return_url = $form_data['payments']['mollie']['redirect_url'];
			$return_url = esc_url_raw(
				add_query_arg(
					array(
						'everest_forms_return' => base64_encode( $query_args ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					),
					apply_filters( 'everest_forms_mollie_return', $return_url, $form_data )
				)
			);
		} else {
			$fallback_url = home_url( '/' );
			if ( ! empty( $form_data['settings']['ajax_form_submission'] ) && ! empty( $_SERVER['HTTP_REFERER'] ) ) {
				$fallback_url = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			}
			$return_url = esc_url_raw(
				add_query_arg(
					array(
						'everest_forms_return' => base64_encode( $query_args ),
					),
					apply_filters( 'everest_forms_mollie_return', $fallback_url, $form_data )
				)
			);
		}

		$mollie_recurring_toggle        = isset( $form_data['payments']['mollie']['enable_mollie_recurring'] ) && '1' === $form_data['payments']['mollie']['enable_mollie_recurring'];
		$mollie_selector_subscription   = function_exists( 'evf_form_uses_subscription_with_payment_gateway_selector' ) && evf_form_uses_subscription_with_payment_gateway_selector( $form_data ) && function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) );

		if ( $mollie_recurring_toggle || $mollie_selector_subscription ) {
			try {
				$this->hydrate_mollie_selector_subscription_defaults( $form_data, $entry );

				$form_title   = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '';
				$schedule     = $this->resolve_subscription_schedule( $form_data, $entry );
				$total_amount = number_format(
					(float) ( $schedule ? $schedule['first_checkout_amount'] : $total_amount ),
					2,
					'.',
					''
				);

				// Get the plan name.
				$evf_plan_name = isset( $form_data['payments']['mollie']['subscription_description'] ) ? $form_data['payments']['mollie']['subscription_description'] : $form_title . ' Subscription';

				// Extracting the names of the mapped fields.
				$customer_email_field       = isset( $form_data['payments']['mollie']['recurring']['email'] ) ? $form_data['payments']['mollie']['recurring']['email'] : '';
				$customer_first_name_field  = isset( $form_data['payments']['mollie']['recurring']['customer_first_name'] ) ? $form_data['payments']['mollie']['recurring']['customer_first_name'] : '';
				$customer_last_name_field   = isset( $form_data['payments']['mollie']['recurring']['customer_last_name'] ) ? $form_data['payments']['mollie']['recurring']['customer_last_name'] : '';

				// Assigning the values for the customer creation.
				$customer_email       = $this->get_entry_field_scalar( $entry, $customer_email_field );
				$customer_first_name  = $this->get_entry_field_scalar( $entry, $customer_first_name_field );
				$customer_last_name   = $this->get_entry_field_scalar( $entry, $customer_last_name_field );

				if ( ! is_email( $customer_email ) || '' === $customer_first_name || '' === $customer_last_name ) {
					$this->mark_mollie_payment_failed( $entry_id );
					throw new \Exception(
						(string) apply_filters(
							'everest_forms_mollie_subscription_customer_required_message',
							__( 'Mollie subscription requires a valid customer email, first name, and last name. Map Customer’s Email, First Name, and Last Name for Mollie in your payment settings (Payments tab or payment gateway field), and ensure those fields are completed.', 'everest-forms-pro' ),
							$form_data,
							$entry
						)
					);
				}

				$customer_name = trim( $customer_first_name . ' ' . $customer_last_name );

				// Create the customer in the mollie.
				$customer = $mollie->customers->create(
					array(
						'name'  => $customer_name,
						'email' => $customer_email,
					)
				);

				// Create a payment for mandate authorization.
				$payment = $mollie->payments->create(
					array(
						'amount'       => array(
							'currency' => get_option( 'everest_forms_currency', 'USD' ),
							'value'    => $total_amount,
						),
						'description'  => $evf_plan_name,
						'redirectUrl'  => $return_url,
						'webhookUrl'   => $webhook_url,
						'metadata'     => array(
							'entry_id' => $entry_id,
						),
						'sequenceType' => \Mollie\Api\Types\SequenceType::SEQUENCETYPE_FIRST,
						'customerId'   => $customer->id,
					)
				);

				$payment_id = isset( $payment->id ) ? sanitize_text_field( (string) $payment->id ) : '';
				if ( ! $this->is_valid_mollie_payment_id( $payment_id ) ) {
					throw new \Exception(
						__( 'Mollie did not return a valid payment ID for the subscription checkout.', 'everest-forms-pro' )
					);
				}

				$mollie_entry                      = evf_get_entry( $entry_id );
				$payment_meta                      = json_decode( $mollie_entry->meta['meta'] );
				$payment_meta->payment_transaction = $payment_id;
				if ( isset( $payment->_links->dashboard->href ) ) {
					$payment_meta->payment_details_url = esc_url_raw( $payment->_links->dashboard->href );
				}
				if ( $schedule ) {
					$payment_meta->mollie_subscription_schedule = $schedule;
				}
				$entry_data                        = array(
					'meta' => wp_json_encode( $payment_meta ),
				);

				evf_payment_entries( $entry_id, $entry_data, true );

				if ( $schedule && function_exists( 'evf_get_logger' ) ) {
					evf_get_logger()->info(
						'Mollie subscription checkout schedule',
						array( 'source' => 'mollie', 'schedule' => $schedule )
					);
				}

				// Redirect the customer to the payment page to authorize the mandate.
				wp_redirect( $payment->getCheckoutUrl() ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				exit();
			} catch ( \Mollie\Api\Exceptions\ApiException $exception ) {
				evf_get_logger()->notice( print_r( $exception->getMessage(), true ) );
				$this->mark_mollie_payment_failed( $entry_id );
				throw new \Exception(
					(string) apply_filters(
						'everest_forms_mollie_payment_api_error_message',
						__( 'Mollie could not start this payment. Please verify customer details and payment settings, then try again.', 'everest-forms-pro' ),
						$exception,
						$form_data,
						$entry
					)
				);
			}
		} else {
			try {
				$form_title   = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '';
				$total_amount = number_format( (float) $total_amount, 2, '.', '' );
				$response     = $mollie->payments->create(
					array(
						'amount'      => array(
							'currency' => get_option( 'everest_forms_currency', 'USD' ),
							'value'    => $total_amount,
						),
						'description' => $form_title,
						'method'      => 'creditcard',
						'redirectUrl' => $return_url,
						'webhookUrl'  => $webhook_url,
						'metadata'    => array(
							'entry_id' => $entry_id,
						),
					)
				);

				$payment_id = isset( $response->id ) ? sanitize_text_field( (string) $response->id ) : '';
				if ( ! $this->is_valid_mollie_payment_id( $payment_id ) ) {
					throw new \Exception(
						__( 'Mollie did not return a valid payment ID for this checkout.', 'everest-forms-pro' )
					);
				}

				$mollie_entry                      = evf_get_entry( $entry_id );
				$payment_meta                      = json_decode( $mollie_entry->meta['meta'] );
				$payment_meta->payment_transaction = $payment_id;
				if ( isset( $response->_links->dashboard->href ) ) {
					$payment_meta->payment_details_url = esc_url_raw( $response->_links->dashboard->href );
				}
				$entry_data                        = array(
					'meta' => wp_json_encode( $payment_meta ),
				);

				evf_payment_entries( $entry_id, $entry_data, true );

				// Redirect the customer to the payment page to authorize the mandate.
				wp_redirect( $response->getCheckoutUrl() ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				exit();
			} catch ( \Mollie\Api\Exceptions\ApiException $exception ) {
				evf_get_logger()->notice( print_r( $exception->getMessage(), true ) );
				$this->mark_mollie_payment_failed( $entry_id );
				throw new \Exception(
					(string) apply_filters(
						'everest_forms_mollie_payment_api_error_message',
						__( 'Mollie could not start this payment. Please verify customer details and payment settings, then try again.', 'everest-forms-pro' ),
						$exception,
						$form_data,
						$entry
					)
				);
			}
		}
	}

	/**
	 * Build trial/expiry/proration schedule from the selected subscription plan choice.
	 *
	 * @param array $form_data Form configuration.
	 * @param array $entry     Entry payload (form_fields).
	 * @return array|null Schedule array from SubscriptionSchedule::build(), or null.
	 */
	private function resolve_subscription_schedule( array $form_data, array $entry ) {
		if ( ! function_exists( 'evf_get_subscription_plan_choice_from_entry' ) ) {
			return null;
		}

		$plan_row = evf_get_subscription_plan_choice_from_entry( $form_data, $entry );
		if ( empty( $plan_row ) ) {
			return null;
		}

		$schedule = SubscriptionSchedule::build( $plan_row );

		return apply_filters( 'everest_forms_mollie_subscription_schedule', $schedule, $plan_row, $form_data, $entry );
	}

	/**
	 * Schedule stored on entry meta, or rebuilt from saved entry + form.
	 *
	 * @param object $payment_meta Decoded entry payment meta.
	 * @param array  $form_data    Form configuration.
	 * @param array  $entry_arr    Entry with form_fields.
	 * @return array|null
	 */
	private function get_subscription_schedule_for_return( $payment_meta, array $form_data, array $entry_arr ) {
		if ( ! empty( $payment_meta->mollie_subscription_schedule ) ) {
			$stored = (array) $payment_meta->mollie_subscription_schedule;
			if ( ! empty( $stored['interval'] ) && ! empty( $stored['subscription_amount'] ) ) {
				return $stored;
			}
		}

		return $this->resolve_subscription_schedule( $form_data, $entry_arr );
	}

	/**
	 * When using the payment gateway selector + subscription plan, fill Mollie recurring field IDs from the form if not mapped.
	 *
	 * @param array $form_data Form data (modified by reference).
	 * @param array $entry     Entry payload.
	 */
	private function hydrate_mollie_selector_subscription_defaults( array &$form_data, array $entry ) {
		$mollie_recurring_toggle      = isset( $form_data['payments']['mollie']['enable_mollie_recurring'] ) && '1' === $form_data['payments']['mollie']['enable_mollie_recurring'];
		$mollie_selector_subscription = function_exists( 'evf_form_uses_subscription_with_payment_gateway_selector' ) && evf_form_uses_subscription_with_payment_gateway_selector( $form_data ) && function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) );

		if ( ! $mollie_selector_subscription || $mollie_recurring_toggle ) {
			return;
		}

		if ( ! isset( $form_data['payments']['mollie']['recurring'] ) || ! is_array( $form_data['payments']['mollie']['recurring'] ) ) {
			$form_data['payments']['mollie']['recurring'] = array();
		}

		if ( function_exists( 'evf_get_subscription_plan_choice_from_entry' ) ) {
			$plan_row = evf_get_subscription_plan_choice_from_entry( $form_data, $entry );
			if ( ! empty( $plan_row['label'] ) ) {
				$form_data['payments']['mollie']['subscription_description'] = sanitize_text_field( (string) $plan_row['label'] );
			}
			if ( ! empty( $plan_row ) && function_exists( 'evf_subscription_plan_period_to_mollie_interval' ) ) {
				$iv = evf_subscription_plan_period_to_mollie_interval(
					isset( $plan_row['recurring_period'] ) ? (string) $plan_row['recurring_period'] : 'month',
					isset( $plan_row['interval_count'] ) ? absint( $plan_row['interval_count'] ) : 1
				);
				$form_data['payments']['mollie']['interval']        = $iv['interval'];
				$form_data['payments']['mollie']['interval_count'] = (string) $iv['interval_count'];
			}
		}

		$auto_map = array(
			'email'               => 'email',
			'customer_first_name' => 'first-name',
			'customer_last_name'  => 'last-name',
		);

		foreach ( $auto_map as $recurring_key => $field_type ) {
			if ( ! empty( $form_data['payments']['mollie']['recurring'][ $recurring_key ] ) ) {
				continue;
			}
			if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
				continue;
			}
			foreach ( $form_data['form_fields'] as $ff ) {
				if ( ! empty( $ff['type'] ) && $field_type === $ff['type'] && ! empty( $ff['id'] ) ) {
					$form_data['payments']['mollie']['recurring'][ $recurring_key ] = $ff['id'];
					break;
				}
			}
		}
	}

	/**
	 * Single-line text from an entry field id.
	 *
	 * @param array  $entry    Entry data.
	 * @param string $field_id Field id.
	 * @return string
	 */
	private function get_entry_field_scalar( array $entry, $field_id ) {
		$field_id = (string) $field_id;
		if ( '' === $field_id || empty( $entry['form_fields'] ) || ! is_array( $entry['form_fields'] ) ) {
			return '';
		}
		if ( ! isset( $entry['form_fields'][ $field_id ] ) ) {
			return '';
		}
		$value = $entry['form_fields'][ $field_id ];
		if ( is_scalar( $value ) ) {
			return trim( (string) $value );
		}
		if ( is_array( $value ) && isset( $value['primary'] ) && is_scalar( $value['primary'] ) ) {
			return trim( (string) $value['primary'] );
		}
		return '';
	}

	/**
	 * Whether a string is a Mollie payment resource ID (tr_…).
	 *
	 * @param string $payment_id Payment id.
	 * @return bool
	 */
	private function is_valid_mollie_payment_id( $payment_id ) {
		$payment_id = sanitize_text_field( (string) $payment_id );

		return '' !== $payment_id && 0 === strpos( $payment_id, 'tr_' );
	}

	/**
	 * Read payment_transaction from decoded entry meta (object or array).
	 *
	 * @param object|array|null $payment_meta Payment meta.
	 * @return string
	 */
	private function get_payment_transaction_id_from_meta( $payment_meta ) {
		if ( is_object( $payment_meta ) && isset( $payment_meta->payment_transaction ) ) {
			return sanitize_text_field( (string) $payment_meta->payment_transaction );
		}

		if ( is_array( $payment_meta ) && isset( $payment_meta['payment_transaction'] ) ) {
			return sanitize_text_field( (string) $payment_meta['payment_transaction'] );
		}

		return '';
	}

	/**
	 * Mark entry payment row failed when Mollie cannot proceed.
	 *
	 * @param int $entry_id Entry id.
	 */
	private function mark_mollie_payment_failed( $entry_id ) {
		if ( empty( $entry_id ) || ! function_exists( 'evf_payment_entries' ) ) {
			return;
		}
		evf_payment_entries(
			absint( $entry_id ),
			array(
				'status' => 'failed',
			),
			true
		);
		wp_cache_delete( absint( $entry_id ), 'evf-entrymeta' );
		wp_cache_delete( absint( $entry_id ), 'evf-entry' );
	}

	/**
	 * Updates the payment status accordingly.
	 *
	 * @since 1.7.7
	 */
	public function evf_mollie_payment_update() {
		if ( empty( $_GET['everest_forms_return'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return '';
		}

		$evf_mollie_data = wp_unslash( $_GET['everest_forms_return'] ); //phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$evf_mollie_data = base64_decode( $evf_mollie_data );
		if ( ! is_string( $evf_mollie_data ) || '' === $evf_mollie_data ) {
			return '';
		}

		wp_parse_str( $evf_mollie_data, $mollie_data );
		$form_id   = isset( $mollie_data['form_id'] ) ? absint( $mollie_data['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		$is_mollie_enabled = ( isset( $form_data['payments']['mollie']['enable_mollie'] ) && '1' === (string) $form_data['payments']['mollie']['enable_mollie'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) ) );

		if ( $is_mollie_enabled ) {
			$mollie_entry_id = isset( $mollie_data['entry_id'] ) ? absint( $mollie_data['entry_id'] ) : 0;
			if ( $mollie_entry_id <= 0 ) {
				return '';
			}

			wp_cache_delete( $mollie_entry_id, 'evf-entry' );
			wp_cache_delete( $mollie_entry_id, 'evf-entrymeta' );

			// Extracting the entry details to get payment_id.
			$mollie_details = evf_get_entry( $mollie_entry_id );
			if ( empty( $mollie_details ) || empty( $mollie_details->meta['meta'] ) ) {
				return '';
			}

			$payment_meta = json_decode( $mollie_details->meta['meta'] );
			if ( empty( $payment_meta ) ) {
				return '';
			}
			if ( is_array( $payment_meta ) ) {
				$payment_meta = (object) $payment_meta;
			}

			$payment_gateway = is_object( $payment_meta ) && isset( $payment_meta->payment_gateway )
				? (string) $payment_meta->payment_gateway
				: ( is_array( $payment_meta ) && isset( $payment_meta['payment_gateway'] ) ? (string) $payment_meta['payment_gateway'] : '' );

			if ( 'mollie' !== $payment_gateway ) {
				return '';
			}

			$payment_id = $this->get_payment_transaction_id_from_meta( $payment_meta );
			if ( ! $this->is_valid_mollie_payment_id( $payment_id ) ) {
				if ( function_exists( 'evf_get_logger' ) ) {
					evf_get_logger()->error(
						'Mollie return handler: missing or invalid payment transaction ID on entry.',
						array(
							'source'   => 'mollie',
							'entry_id' => $mollie_entry_id,
						)
					);
				}
				$this->mark_mollie_payment_failed( $mollie_entry_id );
				return '';
			}

			// Creating object to mollie to manipulate the data from mollie payment side.
			$mollie         = new \Mollie\Api\MollieApiClient();
			$mollie_api_key = Helpers::get_mollie_api_key();
			$mollie->setApiKey( $mollie_api_key );

			try {
				// Mollie payment response.
				$mollie_response = $mollie->payments->get( $payment_id );
			} catch ( \Mollie\Api\Exceptions\ApiException $exception ) {
				if ( function_exists( 'evf_get_logger' ) ) {
					evf_get_logger()->error(
						'Mollie return handler: could not load payment.',
						array(
							'source'     => 'mollie',
							'entry_id' => $mollie_entry_id,
							'payment_id' => $payment_id,
							'message'    => $exception->getMessage(),
						)
					);
				}
				$this->mark_mollie_payment_failed( $mollie_entry_id );
				return '';
			}

			$mollie_payment_status = $mollie_response->status;

			$mollie_active_recurring = ( isset( $form_data['payments']['mollie']['enable_mollie'] ) && '1' === $form_data['payments']['mollie']['enable_mollie'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) ) );
			$mollie_recurring_toggle  = isset( $form_data['payments']['mollie']['enable_mollie_recurring'] ) && '1' === (string) $form_data['payments']['mollie']['enable_mollie_recurring'];
			$selector_subscription   = function_exists( 'evf_form_uses_subscription_with_payment_gateway_selector' ) && evf_form_uses_subscription_with_payment_gateway_selector( $form_data ) && function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'mollie' ) );

			if ( $mollie_active_recurring && ( $mollie_recurring_toggle || $selector_subscription ) ) {
				if ( $selector_subscription && ! $mollie_recurring_toggle ) {
					$efields = isset( $mollie_details->fields ) ? evf_decode( $mollie_details->fields ) : array();
					$earr    = array( 'form_fields' => is_array( $efields ) ? $efields : array() );
					if ( function_exists( 'evf_get_subscription_plan_choice_from_entry' ) ) {
						$plan_row = evf_get_subscription_plan_choice_from_entry( $form_data, $earr );
						if ( ! empty( $plan_row['label'] ) ) {
							$form_data['payments']['mollie']['subscription_description'] = sanitize_text_field( (string) $plan_row['label'] );
						}
						if ( ! empty( $plan_row ) && function_exists( 'evf_subscription_plan_period_to_mollie_interval' ) ) {
							$iv = evf_subscription_plan_period_to_mollie_interval(
								isset( $plan_row['recurring_period'] ) ? (string) $plan_row['recurring_period'] : 'month',
								isset( $plan_row['interval_count'] ) ? absint( $plan_row['interval_count'] ) : 1
							);
							$form_data['payments']['mollie']['interval']       = $iv['interval'];
							$form_data['payments']['mollie']['interval_count'] = (string) $iv['interval_count'];
						}
					}
				}

				$form_title    = isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '';
				$transaction   = $mollie_response;
				$evf_plan_name = isset( $form_data['payments']['mollie']['subscription_description'] ) ? $form_data['payments']['mollie']['subscription_description'] : $form_title . ' Subscription';
				$efields        = isset( $mollie_details->fields ) ? evf_decode( $mollie_details->fields ) : array();
				$entry_arr      = array( 'form_fields' => is_array( $efields ) ? $efields : array() );
				$schedule       = $this->get_subscription_schedule_for_return( $payment_meta, $form_data, $entry_arr );
				$currency       = get_option( 'everest_forms_currency', 'USD' );

				if ( $schedule ) {
					$recurring_start_date    = $schedule['start_date'];
					$recurring_interval_type = $schedule['interval'];
					$recurring_time          = (int) $schedule['times'];
					$subscription_amount     = $schedule['subscription_amount'];

					if ( ! empty( $schedule['use_prorated_expiry'] ) ) {
						$evf_plan_name .= ' (' . __( 'Prorated', 'everest-forms-pro' ) . ')';
					}
				} else {
					$recurring_start_date    = gmdate( 'Y-m-d' );
					$recurring_payment_type  = isset( $form_data['payments']['mollie']['interval'] ) ? $form_data['payments']['mollie']['interval'] : 'YEARS';
					$recurring_duration      = isset( $form_data['payments']['mollie']['interval_count'] ) ? $form_data['payments']['mollie']['interval_count'] : '1';
					$recurring_interval_type = $recurring_duration . ' ' . evf_strtolower( $recurring_payment_type );
					$subscription_amount     = $mollie_response->amount->value;

					if ( empty( $recurring_payment_type ) ) {
						return '';
					}

					$recurring_time = SubscriptionSchedule::estimate_yearly_times(
						$recurring_payment_type,
						absint( $recurring_duration )
					);
				}

				$customer = $mollie->customers->get( $mollie_response->customerId );

				if ( $schedule && ! empty( $schedule['checkout_only'] ) ) {
					if ( function_exists( 'evf_get_logger' ) ) {
						evf_get_logger()->info(
							'Mollie subscription skipped (single prorated checkout payment).',
							array(
								'source'   => 'mollie',
								'schedule' => $schedule,
							)
						);
					}
				} else {
				try {
					$subscription_webhook_url = Helpers::get_mollie_webhook_url( $form_data )
						? add_query_arg( array( 'everest_forms_mollie_webhook' => 'webhook' ), Helpers::get_mollie_webhook_url( $form_data ) )
						: '';
					$customer_subscription = $customer->createSubscription(
						array(
							'amount'      => array(
								'currency' => $currency,
								'value'    => $subscription_amount,
							),
							'times'       => $recurring_time,
							'interval'    => $recurring_interval_type,
							'startDate'   => $recurring_start_date,
							'description' => $evf_plan_name,
							'webhookUrl'  => $subscription_webhook_url,
						)
					);

					if ( ! empty( $customer_subscription->id ) ) {
						$payment_meta->payment_subscription = $customer_subscription->id;
					}

					if ( $schedule && function_exists( 'evf_get_logger' ) ) {
						evf_get_logger()->info(
							'Mollie subscription created',
							array(
								'source'         => 'mollie',
								'subscriptionId' => $customer_subscription->id,
								'schedule'       => $schedule,
							)
						);
					}
				} catch ( \Mollie\Api\Exceptions\ApiException $exception ) {
					evf_get_logger()->notice( print_r( $exception->getMessage(), true ) );
				}
				}
			}
			$payment_details_url               = $mollie_response->_links->dashboard->href;
			$payment_meta->payment_details_url = $payment_details_url;
			$paid_entry_status = 'complete';
			if ( $mollie_active_recurring && ( $mollie_recurring_toggle || $selector_subscription ) ) {
				$efields_paid   = isset( $mollie_details->fields ) ? evf_decode( $mollie_details->fields ) : array();
				$schedule_paid  = $this->get_subscription_schedule_for_return(
					$payment_meta,
					$form_data,
					array( 'form_fields' => is_array( $efields_paid ) ? $efields_paid : array() )
				);
				if ( $schedule_paid && ! empty( $schedule_paid['entry_status'] ) ) {
					$paid_entry_status = sanitize_key( (string) $schedule_paid['entry_status'] );
				} elseif ( $schedule_paid && ! empty( $schedule_paid['trial_enabled'] ) ) {
					$paid_entry_status = 'Trialing';
				}
			}

			switch ( $mollie_payment_status ) {
				case 'paid':
					evf_payment_entries(
						$mollie_entry_id,
						array(
							'status' => $paid_entry_status,
							'meta'   => wp_json_encode( $payment_meta ),
						),
						true
					);
					break;
				case 'open':
					evf_payment_entries(
						$mollie_entry_id,
						array(
							'status' => 'pending',
						),
						true
					);
					break;
				default:
					evf_payment_entries(
						$mollie_entry_id,
						array(
							'status' => 'failed',
						),
						true
					);
					break;
			}
		} else {
			return '';
		}
	}
}
