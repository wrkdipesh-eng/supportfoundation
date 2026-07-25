<?php

namespace EverestForms\Pro\Addons\Trello\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Api Class.
 *
 * @since 1.0.0
 */
class Api {

	/**
	 * Trello Application Key.
	 *
	 * This property holds the Trello application key used for authentication.
	 *
	 * @var string|null $app_key The Trello application key. Default value is null.
	 */
	protected $app_key = null;

	/**
	 * Access Token for Trello API.
	 *
	 * This property holds the access token used for authenticating requests to the Trello API.
	 *
	 * @var string|null $access_token The access token for the Trello API. Default value is null.
	 */
	public $access_token = null;

	/**
	 * Trello API Base URL.
	 *
	 * This property holds the base URL for Trello API requests.
	 *
	 * @var string $api_url The base URL for Trello API. Default value is 'https://api.trello.com/1/'.
	 */
	public $api_url = 'https://api.trello.com/1/';

	/**
	 * Class Constructor for Trello API Wrapper.
	 *
	 * Initializes a new instance of the Trello API Wrapper class.
	 *
	 * @param string|null $app_key      The Trello application key. If provided, it will be stored in the instance.
	 * @param string|null $access_token The access token for authenticating requests to the Trello API. If provided, it will be stored in the instance.
	 */
	public function __construct( $app_key = null, $access_token = null ) {
		$this->access_token = $access_token;
		$this->app_key      = $app_key;
	}

	/**
	 * Get api url.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $resource Resources.
	 * @param  array  $data     Data.
	 */
	private function get_api_url( $resource, $data = array() ) {

		$parameters = array(
			'key'   => $this->app_key,
			'token' => $this->access_token,
		);

		if ( $data ) {
			$parameters = wp_parse_args( $parameters, $data );
		}

		$paramString = http_build_query( $parameters );

		return $this->api_url . $resource . '?' . $paramString;
	}

	/**
	 * Make a request to the API.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $resource  The API resource to access.
	 * @param array  $data      Additional data for the request.
	 * @param string $method    The HTTP method to use (default: 'GET').
	 */
	public function make_request( $resource, $data, $method = 'GET' ) {
		$requestApi = $this->get_api_url( $resource, $data );

		if ( 'GET' === $method ) {
			$response = wp_remote_get( $requestApi );
		} elseif ( 'POST' === $method ) {
			$response = wp_remote_post( $requestApi );
		} else {
			return array( 'error' => 'Request method could not be found' );
		}

		if ( 200 !== $response['response']['code'] ) {
			return array( 'error' => $response['body'] );
		}

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response['body'] );
		}

		return json_decode( $response['body'], true );
	}

	/**
	 * Test the provided API credentials.
	 *
	 * @since 1.0.0
	 */
	public function auth_test() {
		return $this->make_request( 'members/me', array(), 'GET' );
	}


	/**
	 * Retrieves the lists associated with a specific board.
	 *
	 * @since 1.0.0
	 */
	public function get_boards() {
		return $this->make_request( 'members/my/boards', array(), 'GET' );
	}

	/**
	 * Retrieves the lists associated with a specific board.
	 *
	 * @since 1.0.0
	 *
	 * @param int $board_id The ID of the board.
	 */
	public function get_lists( $board_id ) {
		return $this->make_request( 'boards/' . $board_id . '/lists', array(), 'GET' );
	}


	/**
	 * Retrieves the labels associated with a specific board.
	 *
	 * @since 1.0.0
	 *
	 * @param int $board_id The ID of the board.
	 */
	public function get_labels( $board_id ) {
		return $this->make_request( 'boards/' . $board_id . '/labels', array(), 'GET' );
	}


	/**
	 * Retrieves the members associated with a specific board.
	 *
	 * @since 1.0.0
	 *
	 * @param int $board_id The ID of the board.
	 */
	public function get_members( $board_id ) {
		return $this->make_request( 'boards/' . $board_id . '/members', 'GET' );
	}


	/**
	 * Adds a card to the trello board.
	 *
	 * @since 1.0.0
	 *
	 * @param array $card The card data to be added.
	 */
	public function add_card( $card ) {
		return $this->make_request( 'cards', $card, 'POST' );
	}

}
