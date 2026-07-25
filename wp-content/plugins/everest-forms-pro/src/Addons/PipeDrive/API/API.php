<?php
/**
 * Pipedrive API.
 *
 * @package EverestForms\Pro\Addons\PipeDrive\API
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\PipeDrive\API;

defined( 'ABSPATH' ) || exit;
/**
 * Class API.
 */
class API {
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
	private $endpoint = 'https://api.pipedrive.com/v1/';

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
		 * @param String $resource Resource.
		 * @param  Array  $request_body Assoc array of parameters to be passed.
		 * @param string $method The API method to be called.
		 */
	public function send_request( $resource, $request_body = array(), $method = 'GET', $start = 0, $limit = 500 ) {
		// Limit the limit parameter to a maximum value of 500
		$limit = min( $limit, 500 );

		$query_args = is_null( $this->api_key ) ? array() : array(
			'api_token' => $this->api_key,
			'start'     => $start,
			'limit'     => $limit
		);

		$query_string = http_build_query( $query_args );
		$request_url  = $this->endpoint . $resource . '?' . $query_string;

		$args = array(
			'timeout' => self::TIMEOUT,
			'body'    => wp_json_encode( $request_body ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		switch ( $method ) {
			case 'POST':
				$response = wp_remote_post( $request_url, $args );
				break;
			case 'GET':
				$response = wp_remote_get( $request_url );
				break;
			case 'PUT':
				$args['method'] = 'PUT';
				$response       = wp_remote_request( $request_url, $args );
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
	 * Get the Users details.
	 */
	public function get_users() {
		return $this->send_request( 'users', array(), 'GET' );
	}

	/**
	 * Get the organization details.
	 */
	public function get_organization() {
		return $this->send_request( 'organizations', array(), 'GET' );
	}
	/**
	 * Get the person Fields.
	 */
	public function get_person_fields() {
		return $this->send_request( 'personFields', array(), 'GET' );
	}

	/**
	 * Get the lead Fields.
	 */
	public function get_lead_fields() {
		return $this->send_request( 'leadFields', array(), 'GET' );
	}


	/**
	 * Get the Leads.
	 */
	public function get_leads() {
		return $this->send_request( 'leads', array(), 'GET' );
	}

	/**
	 * Get the Currencies.
	 */
	public function get_currencies() {
		return $this->send_request( 'currencies', array(), 'GET' );
	}
	/**
	 * Get the list.
	 */
	public function get_list() {
		$list = array(
			'person' => 'Person',
			'lead'   => 'Leads',
		);
		return $list;
	}
}
