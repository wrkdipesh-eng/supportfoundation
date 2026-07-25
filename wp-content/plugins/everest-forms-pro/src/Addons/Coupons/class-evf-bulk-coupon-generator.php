<?php
/**
 * Background bulk coupon code generator.
 *
 * @package EverestForms\Pro\Addons\Coupons
 * @since 3.0.5
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/libraries/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once dirname( EVF_PLUGIN_FILE ) . '/includes/libraries/wp-background-process.php';
}

/**
 * EVF_Bulk_Coupon_Generator Class.
 */
class EVF_Bulk_Coupon_Generator extends WP_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		$this->action = 'evf_bulk_coupon_generator';
		parent::__construct();
		add_action( 'admin_notices', array( $this, 'display_success_notice' ) );
	}

	/**
	 * Dispatch updater.
	 *
	 * Updater will still run via cron job if this fails for any reason.
	 *
	 * @since 3.0.5
	 */
	public function dispatch() {
		$dispatched = parent::dispatch();
		$logger     = evf_get_logger();

		if ( is_wp_error( $dispatched ) ) {
			$logger->error(
				sprintf( 'Unable to dispatch EVF_Bulk_Coupon_Generator: %s', $dispatched->get_error_message() ),
				array( 'source' => 'evf_bulk_coupon_generator' )
			);
		}
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 *
	 * @since 3.0.5
	 */
	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() ) {
			return;
		}

		if ( $this->is_queue_empty() ) {
			$this->clear_scheduled_event();
			return;
		}

		$this->handle();
	}

	/**
	 * Schedule fallback event.
	 *
	 * @since 3.0.5
	 */
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Is the updater running?
	 *
	 * @since 3.0.5
	 *
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param  array $item Data for the coupon.
	 *
	 * @since 3.0.5
	 *
	 * @return array|bool
	 */
	protected function task( $item ) {

		if ( isset( $item['data']['code'] ) && ! empty( $item['data']['code'] ) ) {
			$coupon_code = sanitize_text_field( $item['data']['code'] );
			$post_id     = wp_insert_post(
				array(
					'post_content' => wp_json_encode( $item['data'] ),
					'post_title'   => $coupon_code,
					'post_type'    => 'evf_coupons',
					'post_status'  => 'publish',
				)
			);

			if ( ! is_wp_error( $post_id ) ) {
				evf_get_logger()->info(
					sprintf( 'Coupon created successfully: %s', $coupon_code ),
					array( 'source' => 'evf_bulk_coupon_generator' )
				);
			} else {
				evf_get_logger()->error(
					sprintf( 'Failed to create coupon: %s', $coupon_code ),
					array( 'source' => 'evf_bulk_coupon_generator' )
				);
			}
		}

		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 *
	 * @since 3.0.5
	 */
	protected function complete() {
		set_transient( 'evf_bulk_coupon_complete', true, 300 );

		wp_redirect( $_SERVER['REQUEST_URI'] );
		exit;
	}

	/**
	 * Displays the success notice for bulk coupon code generation.
	 *
	 * @since 3.0.5
	 */
	public function display_success_notice() {
		if ( get_transient( 'evf_bulk_coupon_complete' ) ) {
			add_settings_error(
				'coupon_insert_action',
				'coupon_insert_action',
				esc_html__( 'All coupons have been created successfully.', 'everest-forms-pro' ),
				'updated'
			);

			delete_transient( 'evf_bulk_coupon_complete' );
		}
	}
}
