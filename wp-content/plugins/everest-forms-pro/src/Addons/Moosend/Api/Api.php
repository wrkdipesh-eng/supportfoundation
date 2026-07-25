<?php
/**
 * Moosend Api.
 *
 * @package EverestForms\Moosend\Api
 * @since   1.0.0
 */

 namespace EverestForms\Pro\Addons\Moosend\Api;

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
	private $endpoint = 'https://api.moosend.com/v3/';

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
	public function send_request( $resource, $request_body = array(), $method = 'GET' ) {
		$query_args = is_null( $this->api_key ) ? array() : array(
			'apikey' => $this->api_key,
		);

		$query_string = http_build_query( $query_args );

		/* Build request URL. */
		$request_url = $this->endpoint . $resource . '?' . $query_string;

		$args = array(
			'timeout' => self::TIMEOUT,
			'body'    => wp_json_encode( $request_body ),
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		/* Execute request based on method. */
		switch ( $method ) {
			case 'POST':
				$response = wp_remote_post( $request_url, $args );
				break;
			case 'GET':
				$response = wp_remote_get( $request_url );
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
		return $this->send_request(
			'lists.json',
			array(
				'WithStatistics' => false,
				'ShortBy'        => 'CreatedOn',
				'SortMethod'     => 'ASC',
				'PageSize'       => 1,
			),
			'GET'
		);
	}

	/**
	 * Get the Form Lists.
	 *
	 * @since 1.0.0
	 * @param string $listId List ID of the Form.
	 * @return bool false.
	 */
	public function get_list( $listId ) {
		$response = $this->send_request(
			'lists/' . $listId . '/details.json',
			array(
				'WithStatistics' => false,
			),
			'GET'
		);

		if ( empty( $response['Error'] ) ) {
			return $response['Context'];
		}
		return false;
	}

	/**
	 * Get all Forms in the system.
	 *
	 * @return array Form Lists.
	 */
	public function get_lists() {
		$response = $this->send_request(
			'lists.json',
			array(
				'WithStatistics' => false,
				'ShortBy'        => 'CreatedOn',
				'SortMethod'     => 'DESC',
				'PageSize'       => 999,
			),
			'GET'
		);

		if ( empty( $response['Error'] ) && ! empty( $response['Context']['MailingLists'] ) ) {
			return $response['Context']['MailingLists'];
		}
		return false;
	}

	/**
	 * Adds the subscriber to the list.
	 *
	 * @since 1.0.0
	 *
	 * @param  int   $connection_list_id Connection List ID of the Mailing List.
	 * @param  array $data Data to add in the form list.
	 */
	public function add_subscriber( $connection_list_id, $data ) {

		if ( ! empty( $data['customFields'] ) ) {
			$customFields = $data['customFields'];
			$fieldPairs   = array();
			foreach ( $customFields as $key => $value ) {
				if ( ! $value ) {
					continue;
				}
				$fieldPairs[] = $key . '=' . $value;
			}
			if ( $fieldPairs ) {
				$data['customFields'] = $fieldPairs;
			}
		}

		$resource = 'subscribers/' . $connection_list_id . '/subscribe.json';

		$response = $this->send_request( $resource, $data, 'POST' );

		if ( $response && is_wp_error( $response ) ) {
			return new \WP_Error( 'request_error', $response->get_error_messages() );
		}
	}

}
