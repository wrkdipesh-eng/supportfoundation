<?php
/**
 * AmoCRM API integration.
 *
 * This class provides methods for interacting with the AmoCRM API, handling authentication
 * and request management for CRM integration in EverestForms.
 *
 * @package EverestForms\Pro\Addons\AmoCRM\Api
 * @since   1.7.8
 */

namespace EverestForms\Pro\Addons\AmoCRM\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class API.
 *
 * Handles the authentication and communication with AmoCRM API.
 *
 * @since 1.7.8
 */
class API {

	/**
	 * Account Key.
	 *
	 * @since 1.7.9
	 *
	 * @var string
	 */
	public $account_key;

	/**
	 * Client ID used for OAuth authentication.
	 *
	 * @var string|null $client_id
	 */
	protected $client_id = null;

	/**
	 * Client secret used for OAuth authentication.
	 *
	 * @var string|null $client_secret
	 */
	protected $client_secret = null;

	/**
	 * The referer URL to use for API calls.
	 *
	 * @var string|null $referer_url
	 */
	protected $referer_url = null;

	/**
	 * The redirect URL for OAuth callbacks.
	 *
	 * @var string|null $redirect_url
	 */
	protected $redirect_url = null;

	/**
	 * Access token for AmoCRM API authentication.
	 *
	 * @var string|null $access_token
	 */
	protected $access_token = null;

	/**
	 * Additional settings for AmoCRM API requests.
	 *
	 * @var array $settings
	 */
	protected $settings = array();

	/**
	 * API constructor.
	 *
	 * Initializes the API object with the provided settings.
	 *
	 * @param array $settings An array of settings used for initializing API configuration.
	 */
	public function __construct( $settings, $account_key = false ) {
		$this->access_token  = isset( $settings['access_token'] ) ? $settings['access_token'] : '';
		$this->client_secret = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';
		$this->client_id     = $settings['client_id'];
		$this->redirect_url  = admin_url( '?evf_amocrm_auth=true' );
		$this->account_key   = $account_key;
		$this->settings      = $settings;
	}

	/**
	 * Get the authorization URL for AmoCRM OAuth.
	 *
	 * Constructs and returns the authorization URL to initiate the OAuth flow
	 * for AmoCRM using the client ID.
	 *
	 * @since 1.7.9
	 *
	 * @return string Escaped URL for the AmoCRM authorization endpoint.
	 */
	public function get_authorization_url() {
		$url = 'https://www.amocrm.com/oauth?client_id=' . $this->client_id . '&mode=post_message';

		return esc_url( $url );
	}

	/**
	 * Create and retrieve an access token from AmoCRM OAuth.
	 *
	 * This function exchanges the authorization code for an access token by
	 * making a POST request to the AmoCRM token endpoint. It returns the
	 * updated settings array with access and refresh tokens.
	 *
	 * @since 1.7.9
	 *
	 * @param string $code    The authorization code received after user consent.
	 * @param string $referer The referring URL used as part of the OAuth flow.
	 * @param array  $settings The settings array to store access token, refresh token, and expiry.
	 *
	 * @return array|\WP_Error Updated settings array containing the access token, refresh token,
	 *                         and expiry time, or WP_Error if the request fails.
	 */
	public function create_access_token( $code, $referer, $settings ) {
		$this->referer_url = $referer;

		$response = wp_remote_post(
			'https://' . $this->referer_url . '/oauth2/access_token',
			array(
				'body' => array(
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'grant_type'    => 'authorization_code',
					'redirect_uri'  => $this->redirect_url,
					'code'          => $code,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$body = \json_decode( $body, true );

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 400 && $code <= 403 ) {
			return new \WP_Error( $body['status'], $body['title'] . ': ' . $body['hint'] );
		}

		$settings['access_token']  = $body['access_token'];
		$settings['refresh_token'] = $body['refresh_token'];
		$settings['referer_url']   = $referer;
		$settings['expire_at']     = time() + intval( $body['expires_in'] );

		return $settings;
	}

	/**
	 * Get lists of available entities (leads, companies, etc.) from AmoCRM.
	 *
	 * This function retrieves a list of predefined entity types (leads, companies, contacts, catalogs, tasks) from AmoCRM.
	 * If the API request returns any custom catalogs, it will merge them with the predefined list.
	 *
	 * @return array|false The list of available entities (catalogs), or false if the request fails.
	 */
	public function get_lists() {
		$lists = array(
			'leads'     => 'Lead',
			'companies' => 'Company',
			'contacts'  => 'Contact',
			'catalogs'  => 'List',
			'tasks'     => 'Task'
		);

		$url = 'https://' . $this->settings['referer_url'] . '/api/v4/catalogs';

		try {
			$elements = $this->make_request( $url, null );
			if ( ! $elements ) {
				return $lists;
			}
		} catch ( \Exception $exception ) {
			return false;
		}

		if ( is_wp_error( $elements ) ) {
			$error = $elements->get_error_message();
			$code  = $elements->get_error_code();
			wp_send_json_error(
				array(
					'message' => __( $error, 'everest-forms-pro' ),
				),
				$code
			);
		}

		foreach ( $elements['_embedded']['catalogs'] as $catalog ) {
			$lists[ 'elements_' . $catalog['id'] ] = $catalog['name'];
		}

		return $lists;
	}

	/**
	 * Make an HTTP request to the AmoCRM API.
	 *
	 * This function handles making both GET and POST requests to the AmoCRM API,
	 * adding the necessary authorization headers and handling response errors.
	 *
	 * @param string $url  The endpoint URL to make the request to.
	 * @param array  $body The request body (for POST requests).
	 * @param string $type The HTTP request type (GET or POST). Default is GET.
	 *
	 * @return array|\WP_Error The API response body if the request is successful, or WP_Error on failure.
	 */
	public function make_request( $url, $body, $type = 'GET', $form_id = '', $entry_id = '' ) {
		$api_settings = $this->get_api_settings();

		if ( is_wp_error( $api_settings ) ) {
			$code    = $api_settings->get_error_code();
			$message = $api_settings->get_error_message();
			return new \WP_Error( $code, $message );
		}

		$this->access_token = $api_settings['access_token'];

		$request = array();
		if ( $type == 'GET' ) {
			$request = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $this->access_token,
					),
				)
			);
		}

		if ( $type == 'POST' ) {
			$request = wp_remote_post(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $this->access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => $body,
				)
			);

			/**
			 * Action to track the api after submission.
			 *
			 * @since 1.7.9
			 */
			do_action( 'evf_track_api_logs', $form_id, $entry_id, 'amoCRM', $body, $request );
		}

		if ( is_wp_error( $request ) ) {
			$code    = $request->get_error_code();
			$message = $request->get_error_message();
			return new \WP_Error( $code, $message );
		}

		$body = wp_remote_retrieve_body( $request );
		$body = \json_decode( $body, true );
		$code = wp_remote_retrieve_response_code( $request );

		if ( $code >= 200 && $code <= 299 ) {
			return $body;
		} elseif ( $code == 401 || $code == 403 ) {
			return new \WP_Error( $code, $body['title'] . ': ' . $body['detail'] );
		} else {
			$errors = $body['validation-errors'][0]['errors'][0];
			return new \WP_Error( $code, $errors['path'] . ' is ' . $errors['code'] . '. Hint: ' . $errors['detail'] );
		}
	}

	/**
	 * Retrieve API settings including access token and referer URL.
	 *
	 * This function checks if the access token has expired and refreshes it if necessary.
	 * It returns the updated API settings or a WP_Error if the settings are invalid.
	 *
	 * @return array|\WP_Error An array of API settings or a WP_Error if there is an issue.
	 */
	protected function get_api_settings() {
		$refresh = $this->maybe_update_access_token_and_refresh_token();

		if ( is_wp_error( $refresh ) ) {
			$code    = $refresh->get_error_code();
			$message = $refresh->get_error_message();
			return new \WP_Error( $code, $message );
		}

		$api_settings = $this->settings;

		if ( ! $api_settings['status'] || ! $api_settings['expire_at'] ) {
			return new \WP_Error( 'Invalid', __( 'API key is invalid', 'everest-forms-pro' ) );
		}

		return array(
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'redirect_url'  => $this->redirect_url,
			'access_token'  => $this->settings['access_token'],
			'refresh_token' => $this->settings['refresh_token'],
			'referer_url'   => $this->settings['referer_url'],
		);
	}

	/**
	 * Update access token and refresh token if they have expired.
	 *
	 * This function checks if the access token has expired and updates it by
	 * using the refresh token. If the token is updated, the new tokens are stored.
	 *
	 * @return true|\WP_Error True if the token is updated, or WP_Error if the update fails.
	 */
	protected function maybe_update_access_token_and_refresh_token() {
		$settings  = $this->settings;
		$expire_at = $settings['expire_at'];

		if ( $expire_at && $expire_at <= ( time() - 30 ) ) {
			$response = wp_remote_post(
				'https://' . $settings['referer_url'] . '/oauth2/access_token',
				array(
					'body' => array(
						'client_id'     => $this->client_id,
						'client_secret' => $this->client_secret,
						'grant_type'    => 'refresh_token',
						'refresh_token' => $this->settings['refresh_token'],
						'redirect_uri'  => $this->redirect_url,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = wp_remote_retrieve_body( $response );
			$body = \json_decode( $body, true );
			$code = wp_remote_retrieve_response_code( $response );

			if ( $code >= 400 && $code <= 403 ) {
				return new \WP_Error( $body['status'], $body['title'] . ': ' . $body['detail'] );
			}
			if ( isset( $body['access_token'] ) && ! empty( $body['access_token'] ) ) {
				$this->settings['access_token']  = $body['access_token'];
				$this->settings['expires_in']    = time() + intval( $body['expires_in'] );
				$this->settings['refresh_token'] = $body['refresh_token'];
				if ( $this->account_key ) {
					$providers                                 = get_option( 'everest_forms_integrations' );
					$providers['amocrm'][ $this->account_key ] = $this->settings;
					update_option( 'everest_forms_integrations', $providers );
				}
			}
		}

		return true;
	}

	/**
	 * Retrieve custom fields from a given URL.
	 *
	 * @param string $url The API endpoint to request the custom fields from.
	 * @param string $context Optional. The context or field type to filter fields by.
	 *
	 * @since 1.7.9
	 *
	 * @return array|false Array of custom fields or false on failure.
	 */
	public function get_custom_fields( $url, $context = '' ) {
		try {
			$lists = $this->make_request( $url, null );
			if ( ! $lists ) {
				return array();
			}
		} catch ( \Exception $exception ) {
			return false;
		}

		if ( is_wp_error( $lists ) ) {
			$error = $lists->get_error_message();
			$code  = $lists->get_error_code();
			wp_send_json_error(
				array(
					'message' => __( $error, 'everest-forms-pro' )
				),
				$code
			);
		}

		$custom_fields = array();

		foreach ( $lists['_embedded']['custom_fields'] as $field ) {
			$enums = $field['enums'] ?? null;

			if ( isset( $field['nested'] ) && ! empty( $field['nested'] ) ) {
				continue;
			}

			if ( $enums && ! empty( $context ) ) {
				if ( $context != 'fetch_api_fields' ) {
					$data    = array(
						'id'            => 'custom*' . $field['id'] . '*select*' . $field['name'],
						'name'          => __( $field['code'] . ' TYPE', 'everest-forms-pro' ),
						'api_list_type' => $field['code'],
						'req'           => false,
						'field_type'    => $field['name'],
						'tag'           => 'custom*' . $field['id'] . '*select*' . $field['name'],
					);
					$options = array();
					foreach ( $enums as $option ) {
						$options[ $option['value'] ] = $option['value'];
					}
					$data['options'] = $options;
					array_push( $custom_fields, $data );
				}

				if ( $field['code'] == 'PHONE' || $field['code'] == 'EMAIL' ) {
					$data = array(
						'id'         => 'custom*' . $field['id'] . '*text*' . $field['code'],
						'name'       => __( $field['code'], 'everest-forms-pro' ),
						'req'        => false,
						'field_type' => $field['name'],
						'tag'        => 'custom*' . $field['id'] . '*text*' . $field['code']
					);
					array_push( $custom_fields, $data );
				}
			} else {
				$data = array(
					'id'         => 'custom*' . $field['id'] . '*normal*' . $field['name'],
					'name'       => __( $field['code'], 'everest-forms-pro' ),
					'req'        => false,
					'field_type' => $field['name'],
					'tag'        => 'custom*' . $field['id'] . '*normal*' . $field['name'],
				);
				array_push( $custom_fields, $data );
			}
		}

		return $custom_fields;
	}

	/**
	 * Subscribe a user to a list or catalog in amoCRM.
	 *
	 * @since 1.7.9
	 *
	 * @param array $subscriber An associative array containing the subscriber's details.
	 * @return mixed The result of the makeRequest call.
	 */
	public function subscribe( $subscriber, $form_id = '', $entry_id = '' ) {
		$url = 'https://' . $this->settings['referer_url'] . '/api/v4/' . $subscriber['list_id'];

		if ( isset( $subscriber['type'] ) ) {
			$url = 'https://' . $this->settings['referer_url'] . '/api/v4/catalogs/' . $subscriber['list_id'] . '/' . $subscriber['type'];
		}

		if ( isset( $subscriber['entity_type'] ) ) {
			$url = 'https://' . $this->settings['referer_url'] . '/api/v4/' . $subscriber['entity_type'] . '/tags';
		}

		$post = json_encode( array( $subscriber['attributes'] ), JSON_NUMERIC_CHECK );

		return $this->make_request( $url, $post, 'POST', $form_id, $entry_id );
	}
}
