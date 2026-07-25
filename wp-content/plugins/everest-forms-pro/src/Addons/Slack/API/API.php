<?php
/**
 * Slack API class.
 *
 * @since 3.0.5
 *
 * @package EverestForms\Pro\Addons\Slack
 */

namespace EverestForms\Pro\Addons\Slack\API;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Slack API Class.
 *
 * @since 3.0.5
 */
class API {
	/**
	 * The Webhook for slack.
	 */
	private $slack_webhhook;

	/**
	 * Create a new instance
	 *
	 * @param string $slack_webhhook Slack Incoming Webhook.
	 */
	public function __construct( $slack_webhhook ) {
		$this->slack_webhhook = $slack_webhhook;
	}

	public function send_message( $request_body = array(), $method = 'POST', $form_id = '', $entry_id = '' ) {
		$incoming_webhook = $this->slack_webhhook;
		$args             = array(
			'method'      => 'POST',
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.0',
			'headers'     => array(),
			'body'        => $request_body,
			'cookies'     => array(),
		);
		$response         = wp_remote_post( $incoming_webhook, $args );

		/**
		 * Action to track the api after submission.
		 *
		 * @since 3.0.5
		 */
		do_action( 'evf_track_api_logs', $form_id, $entry_id, 'slack', $request_body, $response );

		if ( isset( $response_body['response']['code'] ) ? '200' !== $response_body['response']['code'] : '' ) {
			evf_get_logger()->notice( print_r( 'Slack error are as follows: ', true ) );
			isset( $response['response']['code'] ) ? evf_get_logger()->notice( print_r( 'Slack error: ' . $response['response']['code'], true ) ) : '';
			isset( $response['response']['message'] ) ? evf_get_logger()->notice( print_r( 'Slack error message: ' . $response['response']['message'], true ) ) : '';
		}

		if ( is_wp_error( $response ) ) {
			return '';
		}

		return $response;
	}
}
