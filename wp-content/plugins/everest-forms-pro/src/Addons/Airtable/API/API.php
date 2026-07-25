<?php

/**
 * Airtable API.
 *
 * @package EverestForms\Pro\Addons\Airtable\API
 * @since   1.0.0
 * @version 1.0.0
 */

namespace EverestForms\Pro\Addons\Airtable\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * API Class.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * The base endpoint for the Airtable API.
	 *
	 * @var string
	 */
	protected $end_point = 'https://api.airtable.com/v0/';

	/**
	 * The API key for authentication.
	 *
	 * @var string
	 */
	protected $api_key = '';

	/**
	 * Constructor to initialize the API class.
	 *
	 * @param string $api_key The API key for authentication.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Makes a request to the Airtable API.
	 *
	 * @param string $url        The endpoint URL for the API request.
	 * @param string $method     The HTTP method to use ('GET' or 'POST'). Default is 'GET'.
	 * @param array  $table_data The data to send with a POST request. Default is an empty array.
	 *
	 * @return array The response body decoded from JSON.
	 */
	public function make_request( $url, $method = 'GET', $table_data = array() ) {
		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key,
			),
		);

		if ( 'POST' === $method ) {
			$args['body'] = json_encode( $table_data );
		}

		$request = ( 'GET' === $method ) ? wp_remote_get( $url, $args ) : wp_remote_post( $url, $args );

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( is_wp_error( $request ) ) {
			return $body;
		}

		return $body;
	}

	/**
	 * Retrieves the list of workspaces from Airtable.
	 *
	 * @return array The list of workspaces.
	 */
	public function get_workspace_list() {
		$url = 'meta/bases';
		return $this->make_request( $this->get_url( $url ) );
	}

	/**
	 * Constructs the full URL with the base endpoint.
	 *
	 * @param string $url The endpoint URL.
	 *
	 * @return string The full URL.
	 */
	public function get_url( $url ) {
		return $this->end_point . $url;
	}

	/**
	 * Retrieves the schema of the specified base.
	 *
	 * @param string $list_id The ID of the Airtable base.
	 *
	 * @return array The schema of the base.
	 */
	public function get_base_schema( $list_id ) {
		$url = 'meta/bases/' . $list_id . '/tables';
		return $this->make_request( $this->get_url( $url ) );
	}

	/**
	 * Creates records in the specified base and schema.
	 *
	 * @param string $base_id   The ID of the base.
	 * @param string $schema_id The ID of the schema.
	 * @param array  $data      The data to create records.
	 *
	 * @return array The response from the API.
	 */
	public function create_records( $base_id, $schema_id, $data ) {
		$url = $base_id . '/' . $schema_id;
		return $this->make_request( $this->get_url( $url ), 'POST', $data );
	}

	/**
	 * Retrieves the list of records from the specified base and schema.
	 *
	 * @param string $base_id   The ID of the base.
	 * @param string $schema_id The ID of the schema.
	 *
	 * @return array The list of records.
	 */
	public function get_list_records( $base_id, $schema_id ) {
		$url = $base_id . '/' . $schema_id;
		return $this->make_request( $this->get_url( $url ) );
	}
}
