<?php
/**
 * OnePageCRM Api.
 *
 * @package EverestForms\OnePageCRM\Api
 * @since   1.0.0
 */

 namespace EverestForms\Pro\Addons\OnePageCrm\Api;

defined( 'ABSPATH' ) || exit;
/**
 * Class Api.
 */
class Api {
	/**
	 * API key.
	 *
	 * @var String
	 */
	private $api_key;

	/**
	 * OnePageCRM user ID.
	 *
	 * @since 1.0.0
	 * @var String
	 */
	private $api_user_id;

	/**
	 * OnePageCRM access token.
	 *
	 * @since 1.0.0
	 * @var String
	 */
	private $access_token;

	/**
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://app.onepagecrm.com/api/v3/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * Create a new instance
	 *
	 * @param string $api_key API Key.
	 * @param string $api_user_id API User ID.
	 */
	public function __construct( $api_key, $api_user_id ) {
		$this->api_user_id  = $api_user_id;
		$this->api_key      = $api_key;
		$this->access_token = 'Basic ' . base64_encode( $this->api_user_id . ':' . $this->api_key );
	}

	/**
	 * Checks the authorization.
	 *
	 * @since 1.0.0
	 *
	 * @return array Response Body.
	 */
	public function auth_test() {
		$resource = $this->endpoint . 'bootstrap';
		return $this->send_request( $resource, 'GET' );
	}

	/**
	 * Gets the access token.
	 *
	 * @since 1.0.0
	 *
	 * @return array access_token.
	 */
	public function get_access_token() {
		return array(
			'access_token' => $this->access_token,
		);
	}


	/**
	 * Performs the underlying HTTP request. Not very exciting.
	 *
	 * @param String $resource Resource.
	 * @param  Array  $request_body Assoc array of parameters to be passed.
	 * @param string $method The API method to be called.
	 */
	public function send_request( $resource, $request_body = array(), $method = 'GET' ) {
		$accessToken = $this->get_access_token();

		$response = array();
		if ( 'GET' === $method ) {
			$response = wp_remote_get(
				$resource,
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
						'Authorization' => $accessToken['access_token'],
					),
				)
			);
		}

		if ( 'POST' === $method ) {
			$response = wp_remote_post(
				$resource,
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Accept'        => 'application/json',
						'Authorization' => $accessToken['access_token'],
					),
					'body'    => $request_body,
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( $response['body'], true );
	}

	/**
	 * Get the service.
	 */
	public function get_service() {
		$list = array(
			'contact' => 'Contact',
			'deal'    => 'Deal',
		);
		return $list;
	}


	/**
	 * Get the companies fields.
	 */
	public function get_contact_companies() {
		$resource = $this->endpoint . 'companies';
		return $this->send_request( $resource, array(), 'GET' );
	}


	/**
	 * Get the contact status.
	 */
	public function get_contact_status() {
		$resource = $this->endpoint . 'statuses';
		return $this->send_request( $resource, array(), 'GET' );
	}

	/**
	 * Get the contact fields.
	 */
	public function get_contact_lead_sources() {
		$resource = $this->endpoint . 'lead_sources';
		return $this->send_request( $resource, array(), 'GET' );
	}

	/**
	 * Get the contact details.
	 *
	 * @since 1.0.0
	 */
	public function get_contact_details() {
		$resource = $this->endpoint . 'contacts';
		return $this->send_request( $resource, array(), 'GET' );
	}

	/**
	 * Get the deal status.
	 *
	 * @since 1.0.0
	 */
	public function get_deal_status() {
		$deal_status = array(
			'pending' => 'Pending',
			'won'     => 'Won',
			'lost'    => 'Lost',
		);
		return $deal_status;
	}

	/**
	 * Get the custom fields.
	 *
	 * @since 1.0.0
	 */
	public function get_custom_fields() {
		$resource = $this->endpoint . 'custom_fields';
		return $this->send_request( $resource, array(), 'GET' );
	}
}
