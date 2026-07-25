<?php
/**
 * CleverReach Api.
 *
 * @package EverestForms\Pro\Addons\CleverReach\Api
 * @since   3.0.5
 */

namespace EverestForms\Pro\Addons\CleverReach\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class API.
 */
class API {
	/**
	 * Client ID.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	private $client_id;

	/**
	 * Client Secret.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	private $client_secret;

	/**
	 * Account URL.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	private $account_url;

	/**
	 * Callback URL.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	private $callback_url;

	/**
	 * Connection Datas.
	 *
	 * @since 3.0.5
	 * @var array
	 */
	private $connection = array();

	/**
	 * Account Key.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	public $account_key;

	/**
	 * Endpoint for API.
	 *
	 * @since 3.0.5.
	 * @var string
	 */
	private $endpoint = 'https://rest.cleverreach.com/v3';

	/**
	 * Authorization URL.
	 *
	 * @since 3.0.5
	 * @var string
	 */
	private $auth_url = 'https://rest.cleverreach.com/oauth';

	/**
	 * Time-out duration.
	 *
	 * @since 3.0.5
	 *
	 * @var integer.
	 */
	const TIMEOUT = 30;

	/**
	 * Verify if it is ssl or not.
	 *
	 * @since 3.0.5
	 * @var bool
	 */
	public $verify_ssl = true;

	/**
	 * API Constructor.
	 *
	 * @since 3.0.5
	 *
	 * @param  array $connection Connection Data.
	 * @param  bool  $account_key Checks the account key.
	 */
	public function __construct( $connection, $account_key = false ) {

		$this->client_id     = $connection['client_id'];
		$this->client_secret = $connection['client_secret'];
		$this->connection    = $connection;
		$this->account_key   = $account_key;
		$this->callback_url  = admin_url( '?evf_cleverreach_auth=1' );
	}

	/**
	 * Gets the authorization URL.
	 *
	 * @since 3.0.5
	 *
	 * @return string $url Authorization URL.
	 */
	public function get_authorization_url() {
		$url = add_query_arg(
			array(
				'response_type' => 'code',
				'client_id'     => $this->client_id,
				'redirect_uri'  => urlencode( $this->callback_url ),
				'grant'         => 'basic',
			),
			$this->auth_url . '/authorize.php'
		);

		return esc_url( $url );
	}

	/**
	 * Creates the access token.
	 *
	 * @since 3.0.5
	 *
	 * @param string $code    The authorization code received after user consent.
	 * @param  array  $connection Connection Data.
	 *
	 * @return array $connection Connection data with access token.
	 */
	public function create_access_token( $code, $connection ) {
		$raw_response = wp_remote_post(
			$this->auth_url . '/token.php',
			array(
				'body'    => http_build_query(
					array(
						'client_id'     => $this->client_id,
						'client_secret' => $this->client_secret,
						'grant_type'    => 'authorization_code',
						'redirect_uri'  => $this->callback_url,
						'code'          => $code,
					)
				),
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			)
		);

		if ( is_wp_error( $raw_response ) ) {
			return $raw_response;
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		if ( isset( $response['error'] ) ) {
			$logger = evf_get_logger();
			$logger->log(
				sprintf( esc_html__( 'CleverReach error: ', 'everest-forms-pro' ) . $response['error'][''] ),
				array( 'source' => 'cleverreach' )
			);
		}

		if ( isset( $response['error'] ) ) {
			return new \WP_Error( 'CleverReach - Invalid Client', $response['error'] );
		}

		$connection['access_token']  = $response['access_token'];
		$connection['refresh_token'] = $response['refresh_token'];
		$connection['expires_in']    = time() + intval( $response['expires_in'] );
		return $connection;
	}

	/**
	 * Gets the API Integration Details.
	 *
	 * @since 3.0.5
	 *
	 * @return array Refresh token data.
	 */
	protected function get_api_integration() {
		$this->refresh_token();

		if ( ! $this->connection['status'] || ! $this->connection['expires_in'] ) {
			return new \WP_Error( 'CleverReach - invalid', 'API key is invalid' );
		}

		return array(
			'baseUrl'       => $this->endpoint,
			'version'       => 'OAuth2',
			'clientKey'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'callback'      => $this->callback_url,
			'access_token'  => $this->connection['access_token'],
			'refresh_token' => $this->connection['refresh_token'],
			'expires_in'    => $this->connection['expires_in'],
		);
	}

	/**
	 * Retrieves the refresh token.
	 *
	 * @since 3.0.5
	 */
	protected function refresh_token() {
		if ( $this->connection['expires_in'] && $this->connection['expires_in'] <= ( time() - 30 ) ) {
			$api_url = $this->auth_url . '/token';
		}

		$args = array(
			'body'    => http_build_query(
				array(
					'grant_type'    => 'refresh_token',
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'redirect_uri'  => $this->callback_url,
					'refresh_token' => $this->connection['refresh_token'],
				)
			),
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
		);

		$raw_response = wp_remote_post( $api_url, $args );

		if ( '' !== wp_remote_retrieve_body( $raw_response['error'] ) ) {
			$logger = evf_get_logger();
			$logger->error(
				sprintf( esc_html__( 'CleverReach Error: ', 'everest-forms-pro' ) . isset( $response['error']['error_description'] ) ? $response['error']['error_description'] : '' ),
				array(
					'source' => 'cleverreach',
				)
			);
		}

		if ( is_wp_error( $raw_response ) ) {
			$this->connection['status'] = false;
		}

		$response = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		if ( isset( $response['error_description'] ) ) {
			$this->connection['status'] = false;
		}

		if ( isset( $response['access_token'] ) && ! empty( $response['access_token'] ) ) {
			$this->connection['access_token']  = $response['access_token'];
			$this->connection['expires_in']    = time() + intval( $response['expires_in'] );
			$this->connection['refresh_token'] = $response['refresh_token'];

			if ( $this->account_key ) {
				$providers['cleverreach'][ $this->account_key ] = $this->connection;
				update_option( 'everest_forms_integrations', $providers );
			}
		}
	}

	/**
	 * Sends the request to server according to request type.
	 *
	 * @since 3.0.5
	 *
	 * @param  string  $resource Targeted resource to hit.
	 * @param  array   $body Additional request body param.
	 * @param  string  $method Request method type.
	 * @param  Integer $form_id The form id.
	 * @param Integer $entry_id The entry id.
	 */
	public function send_request( $resource, $body = array(), $method = 'GET', $form_id = '', $entry_id = '' ) {
		$api_key_method = get_option( 'everest_forms_integrations', array() );
		$api_key_exist  = array();
		foreach ( $api_key_method['cleverreach'] as $key => $list ) {
			$api_key_exist = $list;
		}

		if ( ! empty( $api_key_exist ) && array_key_exists( 'access_token', $api_key_exist ) ) {
			if ( $api_key_exist['expires_in'] < time() - 30 ) {
				$this->get_api_integration();
			}

			$token = $api_key_exist['access_token'];

			/* Build request URL. */
			$request_url = $this->endpoint . '/' . $resource;

			/* Execute request based on method. */
			switch ( $method ) {
				case 'POST':
					$args     = array(
						'timeout' => self::TIMEOUT,
						'body'    => wp_json_encode( $body ),
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $token,
						),
					);
					$response = wp_remote_post( $request_url, $args );
					/**
					 * Action to track the api after submission.
					 *
					 * @since 3.0.5
					 */
					do_action( 'evf_track_api_logs', $form_id, $entry_id, 'CleverReach', $body, $response );
					break;
				case 'GET':
					$args     = array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $token,
						),
					);
					$response = wp_remote_get( $request_url, $args );
					break;
				default:
					return new \WP_Error( 'invalid_method', 'Invalid HTTP method specified' );
			}

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

			return $response_body;
		}

		return new \WP_Error( 'no_access_token', 'No access token available' );
	}

	/**
	 * Retrieves the existing lists in CleverReach.
	 *
	 * @since 3.0.5
	 *
	 * @return array Lists from the CleverReach.
	 */
	public function get_lists() {

		$response = $this->send_request( 'groups', array(), 'GET' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		return $response;
	}

	/**
	 * Retrieves the custom fields from CleverReach.
	 *
	 * @since 3.0.5
	 *
	 * @param  int $list_id List ID.
	 *
	 * @return array Custom fields.
	 */
	public function get_custom_fields( $list_id ) {
		$list_id       = 0;
		$custom_fields = $this->send_request( 'attributes', array(), 'GET' );

		if ( ! empty( $custom_fields ) ) {
			return $custom_fields;
		}

		return array();
	}

	/**
	 * Adds the subscriber to the CleverReach subscribers list.
	 *
	 * @since 3.0.5
	 *
	 * @param  int   $list_id List ID.
	 * @param  array $subscriber_data Subscribes detail to add into CleverReach.
	 * @param int   $form_id The form id.
	 * @param int   $entry_id The entry id.
	 *
	 * @return array Subscription Details.
	 */
	public function add_subscriber( $list_id, $subscriber_data, $form_id, $entry_id ) {

		$add_subscriber = $this->send_request( "groups/$list_id/receivers", $subscriber_data, 'POST', $form_id, $entry_id );

		if ( is_wp_error( $add_subscriber ) ) {
			return new \WP_Error( 'cleverreach_error', $add_subscriber->get_error_message() );
		}

		return $add_subscriber;
	}
}
