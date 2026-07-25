<?php
/**
 * EverestForms GetGist API Class.
 *
 * @package EverestForms\Pro\Addons\GetGist\API\API
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\GetGist\API;

defined( 'ABSPATH' ) || exit;


/**
 * Class EVF_Sendinblue.
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
	private $endpoint = 'https://api.getgist.com/';

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
	 * @param string $api_key Your GetGist API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Validate API Token.
	 *
	 * @since 1.7.9
	 *
	 * @return bool
	 */
	public function validate_api_key() {

		$is_valid_key = false;

		$account = $this->get_account();

		if ( ! isset( $account['errors'] ) ) {
			$is_valid_key = true;
		}

		return $is_valid_key;
	}

	/**
	 * Get account.
	 *
	 * @since 1.7.9
	 */
	public function get_account() {
		return $this->make_request( 'leads/', array(), 'GET' );
	}

	/**
	 * Send request to GetGist API.
	 *
	 * @param String  $path API Path.
	 * @param Array   $body Body.
	 * @param String  $method Method.
	 * @param  Integer $form_id The form id.
	 * @param Integer $entry_id The entry id.
	 */
	public function make_request( $path, $body, $method, $form_id = '', $entry_id = '' ) {

		$response   = array();
		$query_args = array();
		$api_url    = $this->endpoint . $path;
		$args       = array(
			'method'    => $method,
			'headers'   => array(
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer $this->api_key",
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
				do_action( 'evf_track_api_logs', $form_id, $entry_id, 'GetGist', $body, $raw_response );
				break;
			default:
				$raw_response = wp_remote_request( $api_url, $args );
				break;
		}
		if ( is_wp_error( $raw_response ) ) {
			return $raw_response;
		}

		return json_decode( wp_remote_retrieve_body( $raw_response ), true );
	}

	/**
	 * Get all lists.
	 *
	 * @since 1.7.9
	 */
	public function get_lists() {
		$list = array(
			'getgist' => 'GetGist',
		);
		return $list;
	}
}
