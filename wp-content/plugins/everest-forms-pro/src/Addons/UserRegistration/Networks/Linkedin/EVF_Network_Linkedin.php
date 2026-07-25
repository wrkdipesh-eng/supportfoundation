<?php
/**
 * UserRegistrationSocialConnect Frontend.
 *
 * @class    EVFUR_Network_Linkedin
 * @version  1.0.0
 * @package  UserRegistrationSocialConnect/Networks
 * @category Networks
 * @author   WPEverest
 */
namespace EverestForms\Pro\Addons\UserRegistration\Networks\Linkedin;

use EverestForms\Pro\Addons\UserRegistration\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Network_Linkedin Class
 *
 * Handles LinkedIn integration for User Registration Social Connect.
 *
 * @since 1.0.0
 */
class EVF_Network_Linkedin extends \EVF_Social_Networks {

	/**
	 * The redirect URI for LinkedIn.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $redirect_uri;

	/**
	 * The encoded URL for redirect.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $encoded_url;

	/**
	 * Send request to LinkedIn API with API key and secret.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key    The LinkedIn API key.
	 * @param string $api_secret The LinkedIn API secret.
	 */
	public function request( $api_key, $api_secret ) {
		if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
			_e( 'The linkedin SDK requires PHP version 5.4 or higher. Please notify about this error to site admin.', 'everest-fo' );
			die();
		}
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->encoded_url = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '';

		if ( isset( $this->encoded_url ) && ! empty( $this->encoded_url ) ) {
			$this->redirect_uri = $this->call_back_url() . 'everest_forms_social_login' . '=linkedin&redirect_uri=' . $this->encoded_url;
		} else {
			$this->redirect_uri = $this->call_back_url() . 'everest_forms_social_login' . '=linkedin';
		}

		$response = $this->get_social_network_data();
		$response['network'] = 'linkedin';

		$this->set_response( $response );
	}

	/**
	 * Retrieve social network data from LinkedIn.
	 *
	 * @since 1.0.0
	 *
	 * @return array Response data from LinkedIn.
	 */
	public function get_social_network_data() {
		$action = isset( $_GET['evfur_action'] ) ? $_GET['evfur_action'] : '';
		$linkedin_access_token = Helper::everest_forms_social_connect_get_session( 'linkedin_access_token' );

		try {
			if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {
				throw new \Exception( __( 'Empty some credential of linkedin app.', 'everest-fo' ) );
			}

			if ( $action == 'login' ) {
				$this->network_login();
			} elseif ( isset( $_GET['code'] ) ) { // Perform HTTP Request to OpenID server to validate key.
				$this->set_access_token();
			} elseif ( $linkedin_access_token && ! empty( $linkedin_access_token ) ) {
				$this->set_network_response();
			} else { // User Canceled your Request.
				throw new \Exception( __( 'Linkedin connection failed. Please contact website admin.', 'everest-fo' ) );
			}
		} catch ( \Exception $e ) {
			$this->response['status'] = 'ERROR';
			$this->response['message'] = $e->getMessage();
		}

		return $this->response;
	}

	/**
	 * Redirect to LinkedIn login URL.
	 *
	 * @since 1.0.0
	 */
	public function network_login() {
		$state = md5( time() );
		Helper::everest_forms_connect_set_session( 'linkedin_state', $state );

		$login_url = "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id={$this->api_key}&redirect_uri={$this->redirect_uri}&state={$state}&scope=openid profile email";

		Helper::evfur_custom_redirect( $login_url );
		die();
	}

	/**
	 * Set the access token from LinkedIn response.
	 *
	 * @since 1.0.0
	 */
	public function set_access_token() {
		try {
			$url = 'https://www.linkedin.com/oauth/v2/accessToken';
			$params = array(
				'method'   => 'POST',
				'blocking' => true,
				'body'     => array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'redirect_uri'  => $this->redirect_uri,
					'client_id'     => $this->api_key,
					'client_secret' => $this->api_secret,
				),
				'headers'  => array( 'Content-type' => 'application/x-www-form-urlencoded' ),
			);

			$linkedin_response = wp_remote_post( $url, $params ); // Request for access token.
			$access_token = '';

			if ( isset( $linkedin_response['body'] ) ) {
				$linkedin_response_decode = json_decode( $linkedin_response['body'], true );

				if ( isset( $linkedin_response_decode['access_token'] ) ) {
					$access_token = $linkedin_response_decode['access_token'];
				}
			}

			Helper::everest_forms_connect_set_session( 'linkedin_access_token', $access_token );
		} catch ( \Exception $e ) {
			// Handle exception.
		}

		Helper::evfur_custom_redirect( $this->redirect_uri );
		die();
	}

	/**
	 * Set the response data from LinkedIn API.
	 *
	 * @since 1.0.0
	 */
	public function set_network_response() {
		try {
			$linkedin_access_token = Helper::everest_forms_social_connect_get_session( 'linkedin_access_token' );

			if ( false === $linkedin_access_token || empty( $linkedin_access_token ) ) {
				throw new \Exception( __( 'Token not found.', 'everest-fo' ) );
			}

			$profile_url = 'https://api.linkedin.com/v2/userinfo';
			$params = array(
				'method'   => 'GET',
				'blocking' => true,
				'body'     => array(),
				'headers'  => array(
					'cache-control'             => 'no-cache',
					'Authorization'             => 'Bearer ' . $linkedin_access_token,
					'X-Restli-Protocol-Version' => '2.0.0',
				),
			);

			$linkedin_response = wp_remote_get( $profile_url, $params ); // Request for access token.
			$user_email_body = array();

			if ( isset( $linkedin_response['body'] ) ) {
				$user_profile_body = json_decode( $linkedin_response['body'] );
			}

			if ( empty( $user_profile_body ) ) {
				throw new \Exception( __( 'INVALID AUTHORIZATION', 'everest-fo' ) );
			}
			if ( isset( $user_profile_body->email_verified ) && $user_profile_body->email_verified ) {
				$this->response['status']  = 'SUCCESS';
				$this->response['message'] = 'Successfully get data';
				$profile                   = isset( $user_profile_body->picture ) ? $user_profile_body->picture : '';
				$email                     = isset( $user_profile_body->email ) ? $user_profile_body->email : '';
				$this->response['data']    = array(
					'email'       => $email,
					'username'    => Helper::evfur_get_username( explode( '@', $email )[0], $email ),
					'profile'     => $profile,
					'profile_pic' => $profile,
					'first_name'  => isset( $user_profile_body->given_name ) ? $user_profile_body->given_name : '',
					'last_name'   => isset( $user_profile_body->family_name ) ? $user_profile_body->family_name : '',
				);

			} else {
				$this->response['status']  = 'ERROR';
				$this->response['message'] = __( 'Could not connect to linkedin, please contact site administrator.', 'everest-fo' );
			}
		} catch ( \Exception $e ) {
			$this->response['status']  = 'ERROR';
			$this->response['message'] = $e->getMessage();
		}
	}
}
