<?php
/**
 * Payment subscriptions & payments summary block (frontend).
 *
 * @package EverestForms_Pro
 * @since   1.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block: user subscriptions and payment history for entries tied to the logged-in user.
 */
class EVF_Blocks_Payment_Subscriptions extends EVF_Blocks_Abstract {

	/**
	 * Block name (directory under dist/blocks).
	 *
	 * @var string
	 */
	protected $block_name = 'payment-subscriptions';

	/**
	 * Constructor — register assets before core registers the block type.
	 *
	 * @param string $block_name Optional block name.
	 */
	public function __construct( $block_name = '' ) {
		$this->register_assets();
		parent::__construct( $block_name );
		add_shortcode(
			apply_filters( 'everest_forms_payment_history_shortcode_tag', 'payment_history' ),
			array( $this, 'render_shortcode' )
		);
	}

	/**
	 * Metadata lives in the Pro plugin.
	 *
	 * @return string
	 */
	protected function get_metadata_base_dir() {
		return EFP_ABSPATH . 'dist/blocks';
	}

	/**
	 * Register editor script and shared stylesheet handles referenced in block.json.
	 *
	 * @return void
	 */
	protected function register_assets() {
		static $registered = false;
		if ( $registered ) {
			return;
		}
		$registered = true;
		$suffix     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script(
			'everest-forms-pro-payment-subscriptions-block-editor',
			plugins_url( "assets/js/blocks/payment-subscriptions-block{$suffix}.js", EFP_PLUGIN_FILE ),
			array(
				'wp-blocks',
				'wp-element',
				'wp-i18n',
				'wp-components',
				'wp-block-editor',
				'wp-server-side-render',
			),
			EFP_VERSION,
			true
		);

		$forms = array();
		if ( function_exists( 'evf' ) ) {
			$forms = evf()->form->get_multiple( array( 'order' => 'DESC' ) );
		}
		wp_localize_script(
			'everest-forms-pro-payment-subscriptions-block-editor',
			'_EVF_PS_BLOCK_',
			array(
				'forms' => $forms,
			)
		);

		wp_register_style(
			'everest-forms-pro-payment-subscriptions-block',
			plugins_url( 'assets/css/payment-subscriptions-block.css', EFP_PLUGIN_FILE ),
			array(),
			EFP_VERSION
		);

		wp_register_script(
			'everest-forms-pro-payment-subscriptions-block-frontend',
			plugins_url( "assets/js/frontend/payment-subscriptions-block-frontend{$suffix}.js", EFP_PLUGIN_FILE ),
			array(),
			EFP_VERSION,
			true
		);
		wp_localize_script(
			'everest-forms-pro-payment-subscriptions-block-frontend',
			'evfPsBlock',
			array(
				'restUrl' => esc_url_raw( rest_url() ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'loading'         => __( 'Loading…', 'everest-forms-pro' ),
					'error'           => __( 'Unable to load transaction.', 'everest-forms-pro' ),
					'transaction'     => __( 'Transaction', 'everest-forms-pro' ),
					'transactionHash' => __( 'Transaction #', 'everest-forms-pro' ),
					'date'            => __( 'Date', 'everest-forms-pro' ),
					'method'          => __( 'Payment Method', 'everest-forms-pro' ),
					'status'          => __( 'Payment Status', 'everest-forms-pro' ),
					'item'            => __( 'Item', 'everest-forms-pro' ),
					'qty'             => __( 'Quantity', 'everest-forms-pro' ),
					'price'           => __( 'Price', 'everest-forms-pro' ),
					'lineTotal'       => __( 'Line Total', 'everest-forms-pro' ),
					'subtotal'        => __( 'Sub-Total', 'everest-forms-pro' ),
					'total'           => __( 'Total', 'everest-forms-pro' ),
					'customerDetails' => __( 'Customer Details', 'everest-forms-pro' ),
					'customerName'    => __( 'Customer Name', 'everest-forms-pro' ),
					'customerEmail'       => __( 'Customer Email', 'everest-forms-pro' ),
					'viewRelatedPayments' => __( 'View Payments', 'everest-forms-pro' ),
					'hideRelatedPayments' => __( 'Hide Payments', 'everest-forms-pro' ),
				),
			)
		);
	}

	/**
	 * Render callback: wrap output so alignment classes (alignwide, alignfull) are output.
	 *
	 * Dynamic blocks must call get_block_wrapper_attributes(); returning inner HTML only omits them.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content    Inner blocks content.
	 * @param \WP_Block|null $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		$this->attributes = $attributes;
		$this->block      = $block;
		$this->content    = $content;
		$inner            = apply_filters(
			"everest_forms_{$this->block_name}_content",
			$this->build_html( $this->content ),
			$this
		);
		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes(),
			$inner
		);
	}

	/**
	 * Shortcode [payment_history] — same content as the Payment History block (no editor alignment wrapper).
	 *
	 * Attributes: form_id — optional form ID to limit entries (0 = all forms).
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$shortcode_tag = apply_filters( 'everest_forms_payment_history_shortcode_tag', 'payment_history' );
		$atts          = shortcode_atts(
			array(
				'form_id' => 0,
			),
			$atts,
			$shortcode_tag
		);

		$this->attributes = array(
			'formId' => absint( $atts['form_id'] ),
		);
		$this->content    = '';
		$inner            = apply_filters(
			"everest_forms_{$this->block_name}_content",
			$this->build_html( $this->content ),
			$this
		);

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr(
				apply_filters(
					'everest_forms_payment_history_shortcode_wrapper_class',
					'wp-block-everest-forms-payment-subscriptions evf-payment-subscriptions-block--shortcode'
				)
			),
			$inner
		);
	}

	/**
	 * Build HTML for the block.
	 *
	 * @param string $content Original content (unused).
	 * @return string
	 */
	protected function build_html( $content ) {
		unset( $content );

		if ( ! is_user_logged_in() ) {
			return '<div class="evf-payment-subscriptions-block evf-payment-subscriptions-block--guest"><p>' . esc_html__( 'Please log in to view your subscriptions and payments.', 'everest-forms-pro' ) . '</p></div>';
		}

		$attr = $this->attributes;
		$form_id = isset( $attr['formId'] ) ? absint( $attr['formId'] ) : 0;

		wp_enqueue_style( 'everest-forms-pro-payment-subscriptions-block' );
		wp_enqueue_script( 'everest-forms-pro-payment-subscriptions-block-frontend' );

		$user_id = get_current_user_id();
		$data    = self::get_user_payment_data( $user_id, $form_id );

		ob_start();
		?>
		<div class="evf-payment-subscriptions-block" data-evf-user="<?php echo esc_attr( (string) $user_id ); ?>">
			<?php
			if ( empty( $data['subscriptions'] ) && empty( $data['payments'] ) ) {
				echo '<p class="evf-payment-subscriptions-block__empty">' . esc_html__( 'No payment activity found for your account.', 'everest-forms-pro' ) . '</p>';
			} else {
				if ( ! empty( $data['subscriptions'] ) ) {
					?>
					<div class="evf-payment-subscriptions-block__subscriptions">
						<h3 class="evf-payment-subscriptions-block__subscriptions-heading"><?php esc_html_e( 'Subscriptions', 'everest-forms-pro' ); ?></h3>
						<?php
						foreach ( $data['subscriptions'] as $sub ) {
							self::render_subscription_card( $sub, $data['payments_by_subscription'] );
						}
						?>
					</div>
					<?php
				}
				self::render_payments_table( $data['payments'] );
			}
			self::render_transaction_modal();
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Collect subscription and payment entries for a user.
	 *
	 * @param int $user_id User ID.
	 * @param int $form_id Optional form filter (0 = all).
	 * @return array{subscriptions: array, payments: array, payments_by_subscription: array} payments = one-time only (no payment_subscription meta).
	 */
	public static function get_user_payment_data( $user_id, $form_id = 0 ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array(
				'subscriptions'              => array(),
				'payments'                   => array(),
				'payments_by_subscription'   => array(),
			);
		}

		$sql = "SELECT e.entry_id FROM {$wpdb->prefix}evf_entries e
			INNER JOIN {$wpdb->prefix}evf_entrymeta em ON em.entry_id = e.entry_id AND em.meta_key = %s AND em.meta_value = %s
			WHERE e.user_id = %d";
		$params = array( 'type', 'payment', $user_id );
		if ( $form_id > 0 ) {
			$sql     .= ' AND e.form_id = %d';
			$params[] = $form_id;
		}
		$sql .= ' ORDER BY e.date_created DESC';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- built with placeholders above.
		$entry_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$params ) );

		$subscriptions            = array();
		$payments                 = array();
		$payments_by_subscription = array();

		foreach ( $entry_ids as $entry_id ) {
			$entry = evf_get_entry( absint( $entry_id ), false, array( 'cap' => false ) );
			if ( ! $entry || empty( $entry->meta['meta'] ) ) {
				continue;
			}

			$meta = json_decode( $entry->meta['meta'], true );
			if ( ! is_array( $meta ) ) {
				continue;
			}

			$form_data = evf()->form->get( absint( $entry->form_id ), array() );
			$form_title = $form_data && ! empty( $form_data->post_title ) ? $form_data->post_title : sprintf( /* translators: %d form id */ __( 'Form #%d', 'everest-forms-pro' ), $entry->form_id );

			$currency = ! empty( $meta['payment_currency'] ) ? $meta['payment_currency'] : get_option( 'everest_forms_currency', 'USD' );
			$total    = isset( $meta['payment_total'] ) ? self::format_amount_currency_code( evf_sanitize_amount( $meta['payment_total'], $currency ), $currency ) : '-';
			$status   = evf_format_payment_entry_status_display( $entry->meta['status']);
			$gateway  = ! empty( $meta['payment_gateway'] ) ? $meta['payment_gateway'] : '';
			$gateway_label = self::get_gateway_label( $gateway );

			$subscription_id = isset( $meta['payment_subscription'] ) ? (string) $meta['payment_subscription'] : '';
			$plan_name       = isset( $meta['payment_subscription_name'] ) ? (string) $meta['payment_subscription_name'] : '';
			$interval        = isset( $meta['payment_subscription_interval'] ) ? (string) $meta['payment_subscription_interval'] : '';

			$start_raw = null;
			if ( ! empty( $meta['payment_subscription_start_date']['date'] ) ) {
				$start_raw = $meta['payment_subscription_start_date']['date'];
			} elseif ( ! empty( $entry->date_created ) ) {
				$start_raw = $entry->date_created;
			}

			$start_display = $start_raw ? self::format_entry_datetime( $start_raw ) : '';

			$pay_row = array(
				'entry_id'       => absint( $entry->entry_id ),
				'amount'         => $total,
				'status'         => $status,
				'gateway'        => $gateway_label,
				'date'           => self::format_entry_datetime( $entry->date_created ),
				'raw_date'       => $entry->date_created,
				'subscription_id' => $subscription_id,
			);

			// Main "Payments" table lists one-time payments only; subscription charges appear under each card.
			if ( $subscription_id === '' ) {
				$payments[] = $pay_row;
			}

			if ( $subscription_id !== '' ) {
				if ( ! isset( $payments_by_subscription[ $subscription_id ] ) ) {
					$payments_by_subscription[ $subscription_id ] = array();
				}
				$payments_by_subscription[ $subscription_id ][] = $pay_row;
			}

			// One subscription card per gateway subscription id (newest entry first; merge older meta for labels).
			if ( $subscription_id !== '' ) {
				$key = $subscription_id;
				if ( ! isset( $subscriptions[ $key ] ) ) {
					$subscriptions[ $key ] = array(
						'subscription_id' => $subscription_id,
						'entry_id'        => absint( $entry->entry_id ),
						'form_id'         => absint( $entry->form_id ),
						'form_title'      => $form_title,
						'plan_name'       => $plan_name ? $plan_name : __( 'Subscription', 'everest-forms-pro' ),
						'interval'        => $interval,
						'amount'          => $total,
						'currency'        => $currency,
						'status'          => self::subscription_status_label( $meta, $subscription_id ),
						'start_display'   => $start_display,
						'cancel_url'      => apply_filters( 'everest_forms_payment_subscriptions_block_cancel_url', '', $subscription_id, $entry, $meta ),
					);
				} else {
					if ( ! empty( $plan_name ) ) {
						$subscriptions[ $key ]['plan_name'] = $plan_name;
					}
					if ( ! empty( $interval ) && empty( $subscriptions[ $key ]['interval'] ) ) {
						$subscriptions[ $key ]['interval'] = $interval;
					}
				}
			}
		}

		return array(
			'subscriptions'            => array_values( $subscriptions ),
			'payments'               => $payments,
			'payments_by_subscription' => $payments_by_subscription,
		);
	}

	/**
	 * Human-readable gateway name.
	 *
	 * @param string $gateway Gateway slug.
	 * @return string
	 */
	protected static function get_gateway_label( $gateway ) {
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
			default:
				return $gateway ? ucwords( str_replace( '_', ' ', $gateway ) ) : '—';
		}
	}

	/**
	 * Subscription status for badge (filterable; Stripe may enhance via addon).
	 *
	 * @param array  $meta            Payment meta.
	 * @param string $subscription_id Subscription id.
	 * @return string
	 */
	protected static function subscription_status_label( $meta, $subscription_id ) {
		$label = apply_filters( 'everest_forms_payment_subscriptions_block_status', '', $meta, $subscription_id );
		if ( $label ) {
			return $label;
		}
		return __( 'Active', 'everest-forms-pro' );
	}

	/**
	 * Amount with ISO currency code (e.g. 10.00 USD) instead of HTML symbol.
	 *
	 * @param float|string $amount   Sanitized amount.
	 * @param string       $currency Currency code.
	 * @return string
	 */
	protected static function format_amount_currency_code( $amount, $currency ) {
		$currency = strtoupper( (string) $currency );
		if ( '' === $currency ) {
			$currency = strtoupper( (string) get_option( 'everest_forms_currency', 'USD' ) );
		}
		$num = evf_format_amount( (string) $amount, false, $currency );
		return trim( $num . ' ' . $currency );
	}

	/**
	 * Format a datetime string for display.
	 *
	 * @param string $mysql_date MySQL datetime or date string.
	 * @return string
	 */
	protected static function format_entry_datetime( $mysql_date ) {
		$ts = strtotime( $mysql_date );
		if ( ! $ts ) {
			return '';
		}
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts );
	}

	/**
	 * Resolve payment status from entry meta before falling back to entry post status.
	 *
	 * Payment entries store the transaction lifecycle in entrymeta `status`
	 * (Pending/Failed/Complete); using $entry->status often returns publish.
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
	 * Render one subscription card + related payments.
	 *
	 * @param array $sub                         Subscription row.
	 * @param array $payments_by_subscription Map subscription id => payment rows.
	 * @return void
	 */
	protected static function render_subscription_card( $sub, $payments_by_subscription ) {
		$sid = $sub['subscription_id'];
		$related = isset( $payments_by_subscription[ $sid ] ) ? $payments_by_subscription[ $sid ] : array();
		// Newest first within related.
		usort(
			$related,
			function ( $a, $b ) {
				return strtotime( $b['raw_date'] ) <=> strtotime( $a['raw_date'] );
			}
		);

		$heading = sprintf(
			/* translators: 1: form title, 2: form id */
			__( '%1$s (#%2$d)', 'everest-forms-pro' ),
			esc_html( $sub['form_title'] ),
			absint( $sub['form_id'] )
		);

		$interval = $sub['interval'];
		$price_line = $sub['amount'];
		if ( $interval ) {
			$price_line .= ' /' . esc_html( $interval );
		}

		$payments_anchor = 'evf-related-payments-' . absint( $sub['entry_id'] );
		$panel_id        = $payments_anchor . '-panel';
		?>

		<section class="evf-subscription-card" aria-labelledby="<?php echo esc_attr( $payments_anchor ); ?>-title">
			<div class="evf-subscription-card__header">
				<div class="evf-subscription-card__primary">
					<h3 class="evf-subscription-card__title" id="<?php echo esc_attr( $payments_anchor ); ?>-title"><?php echo wp_kses_post( $heading ); ?></h3>
					<p class="evf-subscription-card__plan"><?php echo esc_html( $sub['plan_name'] ); ?></p>
					<p class="evf-subscription-card__price"><?php echo wp_kses_post( $price_line ); ?></p>
					<span class="evf-subscription-card__badge"><?php echo esc_html( $sub['status'] ); ?></span>
					<p class="evf-subscription-card__note"><?php esc_html_e( 'will be billed until cancelled', 'everest-forms-pro' ); ?></p>
				</div>
				<div class="evf-subscription-card__aside">
					<?php if ( ! empty( $sub['start_display'] ) ) : ?>
						<p class="evf-subscription-card__started">
							<?php
							printf(
								/* translators: %s — formatted date */
								esc_html__( 'Started: %s', 'everest-forms-pro' ),
								esc_html( $sub['start_display'] )
							);
							?>
						</p>
					<?php endif; ?>
					<div class="evf-subscription-card__actions">
						<button
							type="button"
							class="evf-subscription-card__btn evf-subscription-card__btn--ghost js-evf-toggle-related-payments"
							id="<?php echo esc_attr( $payments_anchor ); ?>-toggle"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						><?php esc_html_e( 'View Payments', 'everest-forms-pro' ); ?></button>
						<!-- <?php if ( ! empty( $sub['cancel_url'] ) ) : ?>
							<a class="evf-subscription-card__btn evf-subscription-card__btn--ghost" href="<?php echo esc_url( $sub['cancel_url'] ); ?>"><?php esc_html_e( 'Cancel', 'everest-forms-pro' ); ?></a>
						<?php else : ?>
							<span class="evf-subscription-card__btn evf-subscription-card__btn--ghost evf-subscription-card__btn--disabled" title="<?php esc_attr_e( 'Cancellation is not available for this subscription from this page.', 'everest-forms-pro' ); ?>"><?php esc_html_e( 'Cancel', 'everest-forms-pro' ); ?></span>
						<?php endif; ?> -->
					</div>
				</div>
			</div>
			<div class="evf-subscription-card__related" id="<?php echo esc_attr( $panel_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $payments_anchor ); ?>-related-heading" hidden>
				<h4 class="evf-subscription-card__related-title" id="<?php echo esc_attr( $payments_anchor ); ?>-related-heading"><?php esc_html_e( 'Related Payments', 'everest-forms-pro' ); ?></h4>
				<table class="evf-payment-table evf-payment-table--compact">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Amount', 'everest-forms-pro' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Date', 'everest-forms-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $related ) ) : ?>
							<tr><td colspan="2"><?php esc_html_e( 'No related payments yet.', 'everest-forms-pro' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $related as $row ) : ?>
								<tr>
									<td>
										<?php
										echo esc_html( wp_strip_all_tags( $row['amount'] ) . ' ' . $row['status'] );
										?>
									</td>
									<td><?php echo esc_html( $row['date'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</section>
		<?php
	}

	/**
	 * Modal shell for transaction detail (populated via REST + JS).
	 *
	 * @return void
	 */
	protected static function render_transaction_modal() {
		?>
		<div class="evf-ps-modal__backdrop" hidden aria-hidden="true"></div>
		<div class="evf-ps-modal" role="dialog" aria-modal="true" aria-labelledby="evf-ps-modal-title" hidden>
			<div class="evf-ps-modal__inner">
				<button type="button" class="evf-ps-modal__close" aria-label="<?php esc_attr_e( 'Close', 'everest-forms-pro' ); ?>">&times;</button>
				<div class="evf-ps-modal__body"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Full payments table (one-time payment entries only; subscription charges are under each card).
	 *
	 * @param array $payments Payment rows.
	 * @return void
	 */
	protected static function render_payments_table( $payments ) {
		if ( empty( $payments ) ) {
			return;
		}
		usort(
			$payments,
			function ( $a, $b ) {
				return strtotime( $b['raw_date'] ) <=> strtotime( $a['raw_date'] );
			}
		);
		?>
		<div class="evf-payment-subscriptions-block__payments" id="evf-user-payments-table">
			<h3 class="evf-payment-subscriptions-block__payments-heading"><?php esc_html_e( 'Payments', 'everest-forms-pro' ); ?></h3>
			<table class="evf-payment-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'ID', 'everest-forms-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Amount', 'everest-forms-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'everest-forms-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Payment Method', 'everest-forms-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'everest-forms-pro' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Action', 'everest-forms-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $payments as $row ) : ?>
						<tr>
							<td>#<?php echo esc_html( (string) $row['entry_id'] ); ?></td>
							<td><?php echo esc_html( $row['amount'] ); ?></td>
							<td><?php echo esc_html( $row['status'] ); ?></td>
							<td><?php echo esc_html( $row['gateway'] ); ?></td>
							<td><?php echo esc_html( $row['date'] ); ?></td>
							<td>
								<?php
								$view_url = apply_filters( 'everest_forms_payment_subscriptions_block_view_entry_url', '', $row['entry_id'] );
								if ( $view_url ) {
									echo '<a href="' . esc_url( $view_url ) . '">' . esc_html__( 'View', 'everest-forms-pro' ) . '</a>';
								} else {
									echo '<button type="button" class="evf-payment-table__view js-evf-ps-view" data-entry-id="' . esc_attr( (string) $row['entry_id'] ) . '">' . esc_html__( 'View', 'everest-forms-pro' ) . '</button>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
