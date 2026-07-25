<?php
/**
 * Twilio API.
 *
 * @package EverestForms\Pro\Addons\SmsNotification\API
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\SmsNotification\API;

defined( 'ABSPATH' ) || exit;
/**
 * Class API.
 */
class API {
	/**
	 * Account SID
	 *
	 * @since 1.7.9
	 *
	 * @var string $account_sid Account SID
	 */
	private $account_sid;

	/**
	 * ClickSend Endpoint.
	 *
	 * @since 1.7.9
	 *
	 * @var string $clicksend_endpoint ClickSend Endpoint.
	 */
	private $clicksend_endpoint = 'https://rest.clicksend.com/v3/';

	/**
	 * Account Auth Token
	 *
	 * @since 1.7.9
	 *
	 * @var string $auth_token Auth token
	 */
	private $auth_token;

	/**
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.twilio.com/2010-04-01/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * Create a new instance
	 *
	 * @param string $account_sid Account SID.
	 * @param string $auth_token Auth Token.
	 *
	 * @since 1.7.9
	 */
	public function __construct( $account_sid, $auth_token ) {
		$this->account_sid = $account_sid;
		$this->auth_token  = $auth_token;
	}


	/**
	 * Performs the underlying HTTP request.
	 *
	 * @param String $resource Resource.
	 * @param  Array  $request_body Assoc array of parameters to be passed.
	 * @param string $method The API method to be called.
	 *
	 * @since 1.7.9
	 */
	public function send_request( $method = 'GET', $resource = '', $request_body = array(), $form_id = '', $entry_id = '' ) {
		$request_url = $this->endpoint . 'Accounts/' . $this->account_sid;

		$basic_auth = 'Basic ' . base64_encode( $this->account_sid . ':' . $this->auth_token );
		$response   = array();

		// Send the request according to the method.
		switch ( strtoupper( $method ) ) {
			case 'POST':
				$request_url .= '/Messages.json';
				$args         = array(
					'headers' => array(
						'Content-Type'  => 'application/x-www-form-urlencoded',
						'Accept'        => 'application/json',
						'Authorization' => $basic_auth,
					),
					'body'    => http_build_query( $request_body ),
				);

				$response = wp_remote_post( $request_url, $args );

				/**
				 * Action to track the API Log.
				 *
				 * @since 1.7.9
				 */
				do_action( 'evf_track_api_logs', $form_id, $entry_id, 'Twilio', $request_body, $response );
				break;
			case 'GET':
				$request_url .= '.json';
				$args         = array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
						'Authorization' => $basic_auth,
					),
				);

				$response = wp_remote_get( $request_url, $args );
				break;
		}

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return isset( $response['body'] ) ? json_decode( $response['body'], true ) : $response;
	}

	/**
	 * Performs the underlying HTTP request for ClickSend.
	 *
	 * @param String $resource Resource.
	 * @param  Array  $request_body Assoc array of parameters to be passed.
	 * @param string $method The API method to be called.
	 *
	 * @since 1.7.9
	 *
	 * @return array Response  of the API.
	 */
	public function send_clicksend_request( $method = 'GET', $resource = '', $request_body = array(), $form_id = '', $entry_id = '' ) {
		$request_url = $this->clicksend_endpoint . $resource;
		$basic_auth  = 'Basic ' . base64_encode( $this->account_sid . ':' . $this->auth_token );

		$response = array();

		// Send the request according to the method.
		switch ( strtoupper( $method ) ) {
			case 'POST':
				$args = array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
						'Authorization' => $basic_auth,
					),
					'body'    => wp_json_encode( $request_body, true ),
				);

				$response = wp_remote_post( $request_url, $args );

				/**
				 * Action to track the API Log.
				 *
				 * @since 1.7.9
				 */
				do_action( 'evf_track_api_logs', $form_id, $entry_id, 'ClickSend', $request_body, $response );

				break;
			case 'GET':
				$args = array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
						'Authorization' => $basic_auth,
					),
				);

				$response = wp_remote_get( $request_url, $args );
				break;
		}

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return isset( $response['body'] ) ? json_decode( $response['body'], true ) : $response;
	}

	/**
	 * Test the provided API credentials.
	 *
	 * @since 1.7.9
	 *
	 * @return array Response of API.
	 */
	public function auth_test( $account_sid ) {
		return $this->send_request( 'GET', $account_sid );
	}

	/**
	 * Sends the SMS notification to specified phone_number.
	 *
	 * @since 1.7.9
	 *
	 * @param  array $request_body Request Body.
	 *
	 * @return array Response of Messages API.
	 */
	public function send_message( $request_body, $form_id, $entry_id ) {
		return $this->send_request( 'POST', 'Messages', $request_body, $form_id, $entry_id );
	}

	/**
	 * Tests the credentials of ClickSend.
	 *
	 * @since 1.7.9
	 *
	 * @param  string $clicksend_api ClickSend API Key.
	 *
	 * @param  string $clickSend_username ClickSend Username.
	 *
	 * @return array Response after validating the credentials.
	 */
	public function auth_clicksend( $clicksend_api, $clickSend_username ) {
		return $this->send_clicksend_request( 'GET', 'account' );
	}

	/**
	 * Retrieves the contact lists for ClickSend.
	 *
	 * @since 1.7.9
	 *
	 * @return array List of ClickSend contacts.
	 */
	public function get_click_send_contact_list() {
		return $this->send_clicksend_request( 'GET', 'lists' );
	}

	/**
	 * Sends the notification from ClickSend.
	 *
	 * @since 1.7.9
	 *
	 * @return array Response after sending notification.
	 */
	public function send_clicksend_notification( $request_body, $form_id, $entry_id ) {
		return $this->send_clicksend_request( 'POST', 'sms/send', $request_body, $form_id, $entry_id );
	}

	/**
	 * Sends the notification from ClickSend through SMS Campaign.
	 *
	 * @since 1.7.9
	 *
	 * @return array Response after sending notification.
	 */
	public function send_clicksend_sms_campaign_notification( $request_body, $form_id, $entry_id ) {
		return $this->send_clicksend_request( 'POST', 'sms-campaigns/send', $request_body, $form_id, $entry_id );
	}
}
