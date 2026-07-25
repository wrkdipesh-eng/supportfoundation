<?php
/**
 * Api Logs.
 *
 * @package EverestForms_Pro/Admin/Tools/Api-logs
 * @version 1.7.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Pro_Api_Logs Class.
 */
class EVF_Pro_Api_Logs {
	/**
	 * Init.
	 *
	 * @since 1.7.8
	 */
	public function __construct() {
		$this->includes();
		add_action( 'evf_track_api_logs', array( $this, 'track_api_logs' ), 10, 5 );

	}

	/**
	 * Includes the api logs file.
	 *
	 * @since 1.7.8
	 */
	public function includes() {
		require_once EFP_ABSPATH . 'includes/tools/api-logs/class-evf-pro-api-logs-schema.php';

	}

	/**
	 * Track the api logs.
	 *
	 * @since 1.7.8
	 *
	 * @param  [int]    $form_id The form id.
	 * @param [int]    $entry_id The entry id.
	 * @param  [string] $source The source type.
	 * @param  [array]  $posted_data The posted data.
	 * @param  [array]  $response The response data.
	 */
	public function track_api_logs( $form_id, $entry_id, $source, $posted_data, $response ) {
		if ( empty( $form_id ) || empty( $entry_id ) ) {
			return;
		}

		// Initialize default values
		$response_body    = '';
		$response_code    = '';
		$response_message = '';

		// Check if response is a WP_Error object
		if ( is_wp_error( $response ) ) {
			$response_code    = 'error';
			$response_message = $response->get_error_message();
		} else {
			$response_body    = wp_remote_retrieve_body( $response );
			$response_code    = isset( $response['http_status_code'] ) ? $response['http_status_code'] : wp_remote_retrieve_response_code( $response );
			$respose_body_arr = json_decode( $response_body, true );
			$response_message = isset( $respose_body_arr['message'] ) ? $respose_body_arr['message'] : '';
		}

		/**
		 * Filter for Api log data before save.
		 *
		 * @since 1.7.8
		 */
		$api_log_data = apply_filters(
			'evf_api_log_data',
			array(
				'form_id'  => $form_id,
				'entry_id' => $entry_id,
				'request'  => wp_json_encode( $posted_data ),
				'response' => wp_json_encode( $response_body ),
				'status'   => $response_code,
				'source'   => $source,
				'log'      => $response_message,
			)
		);

		$this->save_api_logs( $api_log_data );
	}


	/**
	 * Save api logs.
	 *
	 * @since 1.7.8
	 *
	 * @param  [array] $api_log_data The api log data.
	 */
	private function save_api_logs( $api_log_data ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'evfp_api_logs', $api_log_data );
	}
}

new EVF_Pro_Api_Logs();
