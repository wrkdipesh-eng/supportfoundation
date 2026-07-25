<?php
/**
 * EverestForms GetResponse API Class.
 *
 * @package EverestForms\GetResponse\API\API
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\GetResponse\API;

defined( 'ABSPATH' ) || exit;


/**
 * Class EVF_GetResponse.
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
	private $endpoint = 'https://api.getresponse.com/v3/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * SSL Verification
	 *
	 * @var boolean
	 */
	public $verify_ssl = true;

	/**
	 * Create a new instance
	 *
	 * @param string $api_key Your GetResponse API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Validate API Token.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function validate_api_key() {

		$is_valid_key = false;

		$account = $this->get_account();

		if ( isset( $account['accountId'] ) && ! empty( $account['accountId'] ) ) {
			$is_valid_key = true;
		}

		return $is_valid_key;
	}

	/**
	 * Get account.
	 *
	 * @since 1.0.0
	 */
	public function get_account() {
		return $this->make_request( 'accounts', array(), 'GET' );
	}

	/**
	 * Send request to GetResponse API.
	 *
	 * @param String  $path API Path.
	 * @param Array   $body Body.
	 * @param String  $method Method.
	 * @param  Integer $form_id The form id.
	 * @param Integer $entry_id The entry id.
	 */
	public function make_request( $path, $body, $method, $form_id = '', $entry_id = '' ) {

		$response = array();

		$api_url = $this->endpoint . $path;

		$args = array(
			'method'    => $method,
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
				'X-Auth-Token' => 'api-key ' . $this->api_key,
			),
			'body'      => empty( $body ) ? array() : wp_json_encode( $body ),
			'sslverify' => $this->verify_ssl,
			'timeout'   => self::TIMEOUT,
		);

		switch ( $method ) {
			case 'GET':
				$raw_response = wp_remote_get( $api_url, $args );
				break;
			case 'POST':
				$raw_response = wp_remote_post( $api_url, $args );

				/**
				 * Action to track the api after submission.
				 *
				 * @since 1.7.8
				 */
				do_action( 'evf_track_api_logs', $form_id, $entry_id, 'GetResponse', $body, $raw_response );
				break;
			default:
				$raw_response = wp_remote_request( $api_url, $args );
				break;
		}

		if ( is_wp_error( $response ) ) {
			return $raw_response;
		}

		return json_decode( wp_remote_retrieve_body( $raw_response ), true );
	}

	/**
	 * Get all lists.
	 *
	 * @since 1.0.0
	 */
	public function get_lists() {
		return $this->make_request( 'campaigns', array(), 'GET' );
	}

	/**
	 * Get all attributes.
	 *
	 * @since 1.0.0
	 */
	public function get_custom_fields() {
		return $this->make_request( 'custom-fields', array(), 'GET' );
	}

	/**
	 * Get all tags.
	 *
	 * @since 1.0.0
	 */
	public function get_tags() {
		return $this->make_request( 'tags', array(), 'GET' );
	}
}
