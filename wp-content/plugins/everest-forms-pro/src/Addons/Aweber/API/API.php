<?php
/**
 * Aweber Api.
 *
 * @package EverestForms\Pro\Addons\Aweber\Api
 * @since   1.7.8
 */

namespace EverestForms\Pro\Addons\Aweber\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class API.
 */
class API {
	/**
	 * Client ID.
	 *
	 * @since 1.7.8
	 * @var string
	 */
	private $client_id;

	/**
	 * Client Secret.
	 *
	 * @since 1.7.8
	 * @var string
	 */
	private $client_secret;

	/**
	 * Account URL.
	 *
	 * @since 1.7.8
	 * @var string
	 */
	private $account_url;

	/**
	 * Callback URL.
	 *
	 * @since 1.7.8
	 * @var string
	 */
	private $callback_url;

	/**
	 * Connection Datas.
	 *
	 * @since 1.7.8
	 * @var array
	 */
	private $connection = array();

	/**
	 * Account Key.
	 *
	 * @since 1.7.8
	 * @var string
	 */
	public $account_key;

	/**
	 * Endpoint for API.
	 *
	 * @since 1.7.8.
	 * @var string
	 */
	private $endpoint = 'https://api.aweber.com';

	/**
	 * Authorization URL.
	 *
	 * @since 1.7.8
	 * @var string
	 */
	private $auth_url = 'https://auth.aweber.com/oauth2';

	/**
	 * Time-out duration.
	 *
	 * @since 1.7.8
	 *
	 * @var integer.
	 */
	const TIMEOUT = 30;

	/**
	 * Verify if it is ssl or not.
	 *
	 * @since 1.7.8
	 * @var bool
	 */
	public $verify_ssl = true;

	/**
	 * API Constructor.
	 *
	 * @since 1.7.8
	 *
	 * @param  array $connection Connection Data.
	 * @param  bool  $account_key Checks the account key.
	 */
	public function __construct( $connection, $account_key = false ) {
		$api_key_method = get_option( 'everest_forms_integrations', array() );
		$api_key_exist  = array();

		if ( ! empty( $api_key_method ) && isset( $api_key_method['aweber'] ) ) {
			foreach ( $api_key_method['aweber'] as $key => $list ) {
				$api_key_exist = $list;
			}
		}

		if ( ! empty( $api_key_exist ) && array_key_exists( 'api', $api_key_exist ) ) {
			$this->connection = $connection;
		} else {
			$this->client_id     = $connection['client_id'];
			$this->client_secret = $connection['client_secret'];
			$this->connection    = $connection;
			$this->account_key   = $account_key;
			$this->callback_url  = admin_url( '?evf_aweber_auth=1' );
		}
	}

	/**
	 * Gets the authorization URL.
	 *
	 * @since 1.7.8
	 *
	 * @return string $url Authorization URL.
	 */
	public function get_authorization_url() {
		$url = add_query_arg(
			array(
				'response_type' => 'code',
				'client_id'     => $this->client_id,
				'redirect_uri'  => urlencode( $this->callback_url ),
				'scope'         => 'account.read+list.read+list.write+subscriber.read+subscriber.write+email.read+email.write+subscriber.read-extended',
				'state'         => evf_get_random_string(),
			),
			$this->auth_url . '/authorize'
		);

		return esc_url( $url );
	}

	/**
	 * Creates the access token.
	 *
	 * @since 1.7.8
	 *
	 * @param  string $code authorization code.
	 * @param  array  $connection Connection Data.
	 *
	 * @return array $connection Connection data with access token.
	 */
	public function create_access_token( $code, $connection ) {
		$raw_response = wp_remote_post(
			$this->auth_url . '/token',
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
				sprintf( esc_html__( 'AWeber error: ', 'everest-forms-pro' ) . $response['error'][''] ),
				array( 'source' => 'aweber' )
			);
		}

		if ( isset( $response['error'] ) ) {
			return new \WP_Error( 'AWeber - Invalid Client', $response['error'] );
		}

		$connection['access_token']  = $response['access_token'];
		$connection['refresh_token'] = $response['refresh_token'];
		$connection['expires_in']    = time() + intval( $response['expires_in'] );
		return $connection;
	}

	/**
	 * Gets the API Integration Details.
	 *
	 * @since 1.7.8
	 *
	 * @return array Refresh token data.
	 */
	protected function get_api_integration() {
		$this->refresh_token();

		if ( ! $this->connection['status'] || ! $this->connection['expires_in'] ) {
			return new \WP_Error( 'aweber - invalid', 'API key is invalid' );
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
	 * @since 1.7.8
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
				sprintf( esc_html__( 'AWeber Error: ', 'everest-forms-pro' ) . isset( $response['error']['error_description'] ) ? $response['error']['error_description'] : '' ),
				array(
					'source' => 'aweber'
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
				$providers['aweber'][ $this->account_key ] = $this->connection;
				update_option( 'everest_forms_integrations', $providers );
			}
		}
	}

	/**
	 * Sends the request to server according to request type.
	 *
	 * @since 1.7.8
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
		foreach ( $api_key_method['aweber'] as $key => $list ) {
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
					 * @since 1.7.8
					 */
					do_action( 'evf_track_api_logs', $form_id, $entry_id, 'AWeber', $body, $response );
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
	 * Test the authorization.
	 *
	 * @since 1.7.8
	 */
	public function auth_test() {
		return $this->send_request( 'accounts', array(), 'GET' );
	}

	/**
	 * Retrieves the existing lists in AWeber.
	 *
	 * @since 1.7.8
	 *
	 * @return array Lists from the AWeber.
	 */
	public function get_lists() {
		$account    = $this->send_request( '1.0/accounts', array(), 'GET' );
		$account_id = isset( $account['entries']['0']['id'] ) ? $account['entries']['0']['id'] : '';

		$list_path = '1.0/accounts/' . $account_id . '/lists';

		$response = $this->send_request( $list_path, array(), 'GET' );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		if ( ! empty( $response['entries'] ) ) {
			return $response;
		}
		return array();
	}

	/**
	 * Retrieves the custom fields from AWeber.
	 *
	 * @since 1.7.8
	 *
	 * @param  int $list_id List ID.
	 *
	 * @return array Custom fields.
	 */
	public function get_custom_fields( $list_id ) {
		$account    = $this->send_request( '1.0/accounts', array(), 'GET' );
		$account_id = isset( $account['entries']['0']['id'] ) ? $account['entries']['0']['id'] : '';

		$custom_fields = $this->send_request( "1.0/accounts/$account_id/lists/$list_id/custom_fields", array(), 'GET' );

		if ( ! empty( $custom_fields['entries'] ) ) {
			return $custom_fields['entries'];
		}
		return array();
	}

	/**
	 * Adds the subscriber to the AWeber subscribers list.
	 *
	 * @since 1.7.8
	 *
	 * @param  int   $list_id List ID.
	 * @param  array $subscriber_data Subscribes detail to add into AWeber.
	 * @param int   $form_id The form id.
	 * @param int   $entry_id The entry id.
	 *
	 * @return array Subscription Details.
	 */
	public function add_subscriber( $list_id, $subscriber_data, $form_id, $entry_id ) {
		$account    = $this->send_request( '1.0/accounts', array(), 'GET' );
		$account_id = isset( $account['entries']['0']['id'] ) ? $account['entries']['0']['id'] : '';

		$add_subscriber = $this->send_request( "1.0/accounts/$account_id/lists/$list_id/subscribers", $subscriber_data, 'POST', $form_id, $entry_id );

		if ( is_wp_error( $add_subscriber ) ) {
			return new \WP_Error( 'aweber_error', $add_subscriber->get_error_message() );
		}

		return $add_subscriber;
	}

}
