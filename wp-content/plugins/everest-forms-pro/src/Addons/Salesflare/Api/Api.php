<?php
/**
 * Salesflare Api.
 *
 * @package EverestForms\Salesflare\Api
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Salesflare\Api;

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
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.salesflare.com/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * Create a new instance
	 *
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}


	/**
	 * Performs the underlying HTTP request. Not very exciting.
	 *
	 * @param String  $resource Resource.
	 * @param  Array   $request_body Assoc array of parameters to be passed.
	 * @param string  $method The API method to be called.
	 * @param  integer $form_id The form id.
	 * @param  integer $entry_id The posted data.
	 */
	public function send_request( $resource, $request_body = array(), $method = 'GET', $form_id = '', $entry_id = '' ) {
		/* Build request URL. */
		$request_url = $this->endpoint . $resource;

		$args = array(
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key,
			),
		);

		if ( ! empty( $request_body ) ) {
			$args['body'] = wp_json_encode( $request_body );
		}

		/* Execute request based on method. */
		switch ( $method ) {
			case 'POST':
				$response = wp_remote_post( $request_url, $args );
				/**
				 * Action to track the api after submission.
				 *
				 * @since 1.7.8
				 */
				do_action( 'evf_track_api_logs', $form_id, $entry_id, 'salesflare', $request_body, $response );

				$response_body = json_decode( $response['body'], true );

				if ( isset( $response_body['statusCode'] ) ? '200' !== $response_body['statusCode'] : '' ) {
					evf_get_logger()->notice( print_r( 'Salesflare error are as follows: ', true ) );
					isset( $response_body['error'] ) ? evf_get_logger()->notice( print_r( 'Salesflare error: ' . $response_body['error'], true ) ) : '';
					isset( $response_body['message'] ) ? evf_get_logger()->notice( print_r( 'Salesflare error message: ' . $response_body['message'], true ) ) : '';
				}

				break;
			case 'GET':
				$response = wp_remote_get( $request_url, $args );
				break;
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return json_decode( $response['body'], true );
	}


	/**
	 * Test the provided API credentials.
	 *
	 * @return bool
	 */
	public function auth_test() {
		return $this->send_request( 'persons', array(), 'GET' );
	}

	/**
	 * Retrieves the custom fields.
	 *
	 * @since 1.7.7
	 *
	 * @return array $custom fields|error.
	 */
	public function get_custom_fields() {
		return $this->send_request( 'customfields/contacts', '', 'GET' );
	}

	/**
	 * Get the list.
	 */
	public function get_list() {
		$list = array(
			'salesflare' => 'Salesflare',
		);
		return $list;
	}
}
