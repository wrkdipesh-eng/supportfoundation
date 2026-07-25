<?php
/**
 * EverestForms Drip API Class.
 *
 * @package EverestForms\Pro\Addons\Drip\API\API
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Drip\API;

defined( 'ABSPATH' ) || exit;


/**
 * Class EVF_Drip.
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
	private $endpoint = 'https://api.getdrip.com/v2/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * SSL Verification
	 * Read before disabling:
	 * http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/
	 *
	 * @var boolean
	 */
	public $verify_ssl = true;

	/**
	 * User agent.
	 *
	 * @var string
	 */
	private $user_agent = 'Everest Forms Drip Add-On (getdrip.com)';

	/**
	 * Create a new instance
	 *
	 * @param string $api_key Your Drip API key.
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

		$accounts = $this->get_accounts();

		if ( isset( $accounts['accounts'] ) && ! empty( $accounts['accounts'] ) ) {
			$is_valid_key = true;
		}

		return $is_valid_key;
	}

	/**
	 * List all accounts.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_accounts() {

		$accounts   = array();
		$api_params = array();

		$response = $this->make_request( 'accounts', 'GET', $api_params );

		if ( $response['success'] ) {

			$accounts = $response['response'];

		}

		return $accounts;
	}

	/**
	 * Send request to Drip API.
	 *
	 * @param String $resource Resource.
	 * @param String $method Method.
	 * @param Array  $body Body.
	 */
	public function make_request( $resource, $method, $body ) {

		$response = array();
		$success  = false;

		if ( ! empty( $this->api_key ) ) {

			$api_url = $this->endpoint . $resource;

			$args = array(
				'headers'   => array(
					'Authorization' => 'Basic ' . base64_encode( "{$this->api_key}:" ),
					'Content-Type'  => 'application/vnd.api+json',
					'User-Agent'    => $this->user_agent,
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

					break;

				default:
					$raw_response = wp_remote_request( $api_url, array_merge( array( 'method' => $method ), $args ) );

					break;
			}

			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

			if ( ! is_wp_error( $raw_response ) || ( in_array( wp_remote_retrieve_response_code( $raw_response ), array( 200, 201, 204 ), true ) ) ) {

				if ( empty( $response ) ) {

					$response = $raw_response['response']['message'];

				}

				$success = true;

			}
		}

		return array(
			'success'  => $success,
			'response' => $response,
		);

	}
}
