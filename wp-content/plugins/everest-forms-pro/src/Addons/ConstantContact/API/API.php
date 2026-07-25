<?php
/**
 * EverestForms Constant Contact API Class.
 *
 * @package EverestForms\Pro\Addons\ConstantContact\API
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\ConstantContact\API;

defined( 'ABSPATH' ) || exit;


/**
 * Class API.
 */
class API {

	/**
	 * Client ID.
	 *
	 * @var String
	 */
	private $client_id;

	/**
	 * Client Secret.
	 *
	 * @var String
	 */
	private $client_secret;

	/**
	 * Callback URL.
	 *
	 * @var String
	 */
	private $callback_url;

	/**
	 * Connection Array.
	 *
	 * @var array
	 */
	private $connection = array();

	/**
	 * Account ID.
	 *
	 * @var string
	 */
	public $account_key;

	/**
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://authz.constantcontact.com/oauth2/default/v1';

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
	 * @param string $connection Connection Array.
	 * @param string $account_key Account ID.
	 */
	public function __construct( $connection, $account_key = false ) {
		$this->client_id     = $connection['client_id'];
		$this->client_secret = $connection['client_secret'];
		$this->connection    = $connection;
		$this->account_key   = $account_key;
		$this->callback_url  = admin_url( '?evf_constant_contact_auth=1' );
	}

	/**
	 * Get Authorization URL.
	 *
	 * @since 1.0.0
	 */
	public function get_authorization_url() {

		$url = add_query_arg(
			array(
				'client_id'     => $this->client_id,
				'scope'         => 'contact_data+offline_access',
				'response_type' => 'code',
				'state'         => evf_get_random_string(),
				'redirect_uri'  => $this->callback_url,
			),
			$this->endpoint . '/authorize'
		);

		return $url;
	}

	/**
	 * Generate Access Token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code Grant Access Token.
	 * @param array  $connection Connection Array.
	 */
	public function create_access_token( $code, $connection ) {
		$api_url     = $this->endpoint . '/token';
		$auth        = $this->client_id . ':' . $this->client_secret;
		$credentials = base64_encode( $auth ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$args = array(
			'headers'   => array(
				'Authorization' => 'Basic ' . $credentials,
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'sslverify' => $this->verify_ssl,
			'timeout'   => self::TIMEOUT,
		);

		$args['body'] = array(
			'grant_type'   => 'authorization_code',
			'redirect_uri' => $this->callback_url,
			'code'         => $code,
		);

		$raw_response = wp_remote_post( $api_url, $args );
		$response     = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		if ( ! is_wp_error( $raw_response ) || ( in_array( wp_remote_retrieve_response_code( $raw_response ), array( 200, 201, 204 ), true ) ) ) {

			if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {

				$connection['access_token']  = $response['access_token'];
				$connection['refresh_token'] = $response['refresh_token'];
				$connection['expire_at']     = time() + intval( $response['expires_in'] );
				return $connection;

			}
		}

		return $response;
	}

	/**
	 * Get API Connection Data.
	 *
	 * @since 1.0.0
	 */
	public function get_api_integration() {
		$this->refresh_token();

		if ( ! $this->connection['status'] || ! $this->connection['expire_at'] ) {
			return new \WP_Error( 'Constant Contact - invalid', 'API key is invalid' );
		}

		return array(
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'callback_url'  => $this->callback_url,
			'access_token'  => $this->connection['access_token'],
			'refresh_token' => $this->connection['refresh_token'],
			'expire_at'     => $this->connection['expire_at'],
		);
	}

	/**
	 * Refresh Token.
	 *
	 * @since 1.0.0
	 */
	protected function refresh_token() {
		if ( $this->connection['expire_at'] && $this->connection['expire_at'] <= ( time() - 30 ) ) {
			$api_url     = $this->endpoint . '/token';
			$auth        = $this->connection['client_id'] . ':' . $this->connection['client_secret'];
			$credentials = base64_encode( $auth ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			$args = array(
				'headers'   => array(
					'Authorization' => 'Basic ' . $credentials,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'sslverify' => $this->verify_ssl,
				'timeout'   => self::TIMEOUT,
			);

			$args['body'] = array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->connection['refresh_token'],
			);

			$raw_response = wp_remote_post( $api_url, $args );

			if ( is_wp_error( $raw_response ) ) {
				$this->connection['status'] = false;
			}

			$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

			if ( isset( $response['error_description'] ) ) {
				$this->connection['status'] = false;
			}

			if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
				$this->connection['access_token']  = $response['access_token'];
				$this->connection['expire_at']     = time() + intval( $response['expires_in'] );
				$this->connection['refresh_token'] = $response['refresh_token'];

				if ( $this->account_key ) {
					$providers['constant_contact'][ $this->account_key ] = $this->connection;
					update_option( 'everest_forms_integrations', $providers );
				}
			}
		}
	}

	/**
	 * Send Request to Constant Contact API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $resource Resource.
	 * @param array  $body Data.
	 * @param string $method Method.
	 */
	public function make_request( $resource, $body = array(), $method = 'GET' ) {
		$connection = $this->get_api_integration();

		if ( is_wp_error( $connection ) ) {
			return $connection;
		}

		$response = array();

		$this->endpoint = 'https://api.cc.email/v3';

		$api_url = $this->endpoint . $resource;

		$args = array(
			'headers'   => array(
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $connection['access_token'],
				'Content-Type'  => 'application/json',
			),
			'sslverify' => $this->verify_ssl,
			'timeout'   => self::TIMEOUT,
		);

		switch ( $method ) {

			case 'GET':
				$raw_response = wp_remote_get( add_query_arg( $body, $api_url ), $args );
				break;
			case 'POST':
				$args['body'] = wp_json_encode( $body );
				$raw_response = wp_remote_post( $api_url, $args );
				break;
			default:
				$args['body'] = wp_json_encode( $body );
				$raw_response = wp_remote_request( $api_url, array_merge( array( 'method' => $method ), $args ) );
				break;
		}

		if ( is_wp_error( $raw_response ) ) {
			return $raw_response;
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		return $response;
	}

	/**
	 * Get All contact lists.
	 *
	 * @since 1.0.0
	 */
	public function get_contact_lists() {
		return $this->make_request( '/contact_lists', array( 'include_count' => true ), 'GET' );
	}

	/**
	 * Get contact custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error Results.
	 */
	public function get_custom_fields() {
		return $this->make_request( '/contact_custom_fields', array(), 'GET' );
	}

	/**
	 * Check whether a contact exists already or not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email Email.
	 * @param string $include Specify which contact sub-resources to include in the response.
	 *
	 * @return bool|array|WP_Error  Return false if not contact exists otherwise return array of contact details.
	 */
	public function is_contact_exists( $email = '', $include = 'list_memberships' ) {

		if ( ! is_email( $email ) ) {
			return false;
		}

		$response = $this->get_contacts(
			array(
				'email'   => $email,
				'include' => $include,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response ) ) {
			return $response[0];
		}

		return false;
	}

	/**
	 * Get contacts.
	 *
	 * @since  1.0.0
	 *
	 * @param array $options API options.
	 *
	 * @return array|WP_Error
	 */
	public function get_contacts( $options = array() ) {
		$response = $this->make_request( '/contacts', $options );
		return ( ! is_wp_error( $response ) && isset( $response['contacts'] ) ) ? $response['contacts'] : $response;
	}
}
