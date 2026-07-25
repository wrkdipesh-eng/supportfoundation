<?php
/**
 * Everest Forms Frontend.
 *
 * @class    EVF_Network_Facebook
 * @version  1.0.0
 * @package  EverestForms/Pro/Addons/UserRegistration/Networks
 * @category Networks
 * @author   WPEverest
 */

namespace EverestForms\Pro\Addons\UserRegistration\Networks\Facebook;

use EverestForms\Pro\Addons\UserRegistration\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Network_Facebook Class.
 *
 * Handles Facebook social login integration.
 *
 * @since 1.7.9
 */
class EVF_Network_Facebook extends \EVF_Social_Networks {

	/**
	 * The redirect URI for the network.
	 *
	 * @since 1.7.9
	 * @var string
	 */
	private $redirect_uri;

	/**
	 * The encoded URL for redirection.
	 *
	 * @since 1.7.9
	 * @var string
	 */
	private $encoded_url;

	/**
	 * Initializes the request for Facebook authentication.
	 *
	 * @since 1.7.9
	 *
	 * @param string $api_key    Facebook API key.
	 * @param string $api_secret Facebook API secret.
	 *
	 * @return void
	 */
	public function request( $api_key, $api_secret ) {
		if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
			esc_html_e( 'The Facebook SDK requires PHP version 5.4 or higher. Please notify about this error to site admin.', 'everest-forms-pro' );
			die();
		}

		$this->api_key    = $api_key;
		$this->api_secret = $api_secret;
		$this->encoded_url = isset( $_GET['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ) : '';

		if ( isset( $this->encoded_url ) && ! empty( $this->encoded_url ) ) {
			$this->redirect_uri = $this->call_back_url() . 'everest_forms_social_login' . '=facebook&redirect_to=' . $this->encoded_url;
		} else {
			$this->redirect_uri = $this->call_back_url() . 'everest_forms_social_login' . '=facebook';
		}

		Helper::everest_forms_session_start();

		$response = $this->get_social_network_data();
		$response['network'] = 'facebook';

		$this->set_response( $response );
	}

	/**
	 * Retrieves Facebook network data.
	 *
	 * @since 1.7.9
	 *
	 * @return array Network response data.
	 */
	public function get_social_network_data() {
		$action               = isset( $_GET['evfur_action'] ) ? sanitize_text_field( wp_unslash( $_GET['evfur_action'] ) ) : '';
		$facebook_access_token = Helper::everest_forms_social_connect_get_session( 'facebook_access_token' );

		try {
			if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {

				throw  new \Exception( __( 'Empty some credential of facebook app.', 'everest-forms-pro' ) );
			}

			if ( 'login' === $action ) {
				$this->network_login();

			} elseif ( isset( $_GET['code'] ) ) { // Perform HTTP Request to OpenID server to validate key

				$this->set_access_token();
			} elseif ( $facebook_access_token && ! empty( $facebook_access_token ) ) {

				$this->set_network_response();

			} else { // User Canceled your Request

				throw  new \Exception( __( 'Facebook connection failed. Please contact website admin.', 'everest-forms-pro' ) );
			}
		} catch ( \Exception $e ) {
			$this->response['status']  = 'ERROR';
			$this->response['message'] = $e->getMessage();
		}

		return $this->response;
	}

	/**
	 * Redirects the user to Facebook for login.
	 *
	 * @since 1.7.9
	 *
	 * @return void
	 */
	public function network_login() {
		$fb       = $this->get_network_object();
		$helper   = $fb->getRedirectLoginHelper();
		$login_url = $helper->getLoginUrl( $this->redirect_uri, array( 'email', 'public_profile' ) );

		Helper::evfur_custom_redirect( $login_url );
		die();
	}

	/**
	 * Initializes the Facebook SDK object.
	 *
	 * @since 1.7.9
	 *
	 * @return \Facebook\Facebook Facebook SDK instance.
	 */
	private function get_network_object() {
		$config = array(
			'app_id'                  => $this->api_key,
			'app_secret'              => $this->api_secret,
			'default_graph_version'   => 'v2.10',
			'persistent_data_handler' => 'session',
		);

		return new \Facebook\Facebook( $config );
	}

	/**
	 * Sets access token in session.
	 *
	 * @since 1.7.9
	 *
	 * @return void
	 */
	public function set_access_token() {
		try {
			$fb     = $this->get_network_object();
			$helper = $fb->getRedirectLoginHelper();

			if ( isset( $_GET['state'] ) ) {
				$helper->getPersistentDataHandler()->set( 'state', sanitize_text_field( wp_unslash( $_GET['state'] ) ) );
			}

			Helper::everest_forms_connect_set_session( 'facebook_state', sanitize_text_field( wp_unslash( $_GET['state'] ) ) );
			$accessToken = $helper->getAccessToken();

			Helper::everest_forms_connect_set_session( 'facebook_access_token', $accessToken->getValue() );
		} catch ( \Exception $e ) {
			// Handle exception if needed.
		}

		Helper::evfur_custom_redirect( $this->redirect_uri );
		die();
	}

	/**
	 * Sets network response with user data.
	 *
	 * @since 1.7.9
	 *
	 * @return void
	 */
	public function set_network_response() {
		try {
			$facebook_access_token = Helper::everest_forms_social_connect_get_session( 'facebook_access_token' );

			if ( false === $facebook_access_token || empty( $facebook_access_token ) ) {

				throw  new \Exception( __( 'Token not found.', 'everest-forms-pro' ) );
			}

			$fb = $this->get_network_object();

			$user_profile = $fb->get( '/me?fields=email,name, first_name, last_name, picture.type(large), gender, link, about, birthday, education, hometown, languages, location, website', $facebook_access_token );

			$user_profile_body = (object) $user_profile->getDecodedBody();

			if ( empty( $user_profile_body ) ) {

				throw  new \Exception( __( 'INVALID AUTHORIZATION', 'everest-forms-pro' ) );
			}

			/* If HTTP response is 200 continue otherwise send to connect page to retry */
			if ( ! empty( $user_profile_body->id ) ) {

			$this->response['status']  = 'SUCCESS';
				$this->response['message'] = 'Successfully get data';
				$profile                   = 'https://facebook.com/' . $user_profile_body->id;
				$email                     = isset( $user_profile_body->email ) ? $user_profile_body->email : '';

				$this->response['data'] = array(
					'email'       => $email,
					'username'    => Helper::evfur_get_username(
						strtolower( trim( $user_profile_body->first_name ) . trim( $user_profile_body->last_name ) ),
						$email
					),
					'profile'     => $profile,
					'id'          => $user_profile_body->id,
					'profile_pic' => $user_profile_body->picture['data']['url'],
					'first_name'  => $user_profile_body->first_name,
					'last_name'   => $user_profile_body->last_name,
				);

			} else {

				$this->response['status']  = 'ERROR';
				$this->response['message'] = __( 'Could not connect to facebook, please contact site administrator.', 'everest-forms-pro' );

			}
		} catch ( \Exception $e ) {
			$this->response['status']  = 'ERROR';
			$this->response['message'] = $e->getMessage();
		}
	}
}
