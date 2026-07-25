<?php
/**
 * EverestFormsSocialConnect Frontend.
 *
 * @class    EVF_Network_Google
 * @version  1.7.9
 * @package  EverestFormsSocialConnect/Networks
 * @category Networks
 * @author   WPEverest
 */

namespace EverestForms\Pro\Addons\UserRegistration\Networks\Google;

use EverestForms\Pro\Addons\UserRegistration\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVF_Network_Google Class
 *
 * @since 1.7.9
 */
class EVF_Network_Google extends \EVF_Social_Networks {

	/**
	 * Redirect URI and Encoded URL for the network.
	 *
	 * @var string
	 * @since 1.7.9
	 */
	private $redirect_uri;
	private $encoded_url;

	/**
	 * Process Google authentication request.
	 *
	 * @param string $api_key    The Google API key.
	 * @param string $api_secret The Google API secret.
	 * @since 1.7.9
	 */
	public function request( $api_key, $api_secret ) {
		$this->api_key    = $api_key;
		$this->api_secret = $api_secret;

		$this->encoded_url = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '';

		if ( isset( $this->encoded_url ) && '' !== $this->encoded_url ) {
			$this->redirect_uri = $this->call_back_url() . 'everest_forms_social_login=google&redirect_to=' . $this->encoded_url;
		} else {
			$this->redirect_uri = $this->call_back_url() . 'everest_forms_social_login=google';
		}

		$response = $this->get_social_network_data();
		$response['network'] = 'google';

		$this->set_response( $response );
	}

	/**
	 * Retrieve Google social network data.
	 *
	 * @return array The response data.
	 * @since 1.7.9
	 */
	public function get_social_network_data() {
		$action              = isset( $_GET['evfur_action'] ) ? $_GET['evfur_action'] : '';
		$google_access_token = Helper::everest_forms_social_connect_get_session( 'google_access_token' );

		try {
			if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {
				throw new \Exception( __( 'Empty some credential of google app.', 'everest-forms-pro' ) );
			}

			if ( 'login' === $action ) {
				$this->network_login();
			} elseif ( isset( $_GET['code'] ) ) {
				$this->set_access_token();
			} elseif ( $google_access_token && ! empty( $google_access_token ) ) {
				$this->set_network_response();
			} else {
				throw new \Exception( __( 'Google connection failed. Please contact website admin.', 'everest-forms-pro' ) );
			}
		} catch ( \Exception $e ) {
			$this->response['status']  = 'ERROR';
			$this->response['message'] = $e->getMessage();
		}

		return $this->response;
	}

	/**
	 * Initiate Google network login.
	 *
	 * @since 1.7.9
	 */
	public function network_login() {
		$network_object = $this->get_network_object();

		Helper::everest_forms_social_connect_unset_session( 'google_access_token' );

		$auth_url = $network_object->createAuthUrl();
		Helper::evfur_custom_redirect( $auth_url );
		die();
	}

	/**
	 * Retrieve Google network object.
	 *
	 * @return \Google_Client Google client object.
	 * @since 1.7.9
	 */
	private function get_network_object() {
		$network_object = new \Google_Client();
		$network_object->setClientId( $this->api_key );
		$network_object->setClientSecret( $this->api_secret );
		$network_object->setRedirectUri( $this->redirect_uri );
		$network_object->addScope( 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email' );

		if ( isset( $this->encoded_url ) && '' !== $this->encoded_url ) {
			$network_object->setState( base64_encode( 'redirect_to=' . $this->encoded_url ) );
		}

		return $network_object;
	}

	/**
	 * Set Google access token.
	 *
	 * @since 1.7.9
	 */
	public function set_access_token() {
		$network_object = $this->get_network_object();

		$network_object->authenticate( $_GET['code'] );
		Helper::everest_forms_connect_set_session( 'google_access_token', $network_object->getAccessToken() );

		Helper::evfur_custom_redirect( $this->redirect_uri );
		die();
	}

	/**
	 * Set network response based on user data.
	 *
	 * @since 1.7.9
	 */
	public function set_network_response() {
		try {
			$google_access_token = Helper::everest_forms_social_connect_get_session( 'google_access_token' );

			if ( false === $google_access_token || empty( $google_access_token ) ) {
				throw new \Exception( __( 'Token not found.', 'everest-forms-pro' ) );
			}

			if ( ! class_exists( 'Google_Client', false ) ) {
				require_once EFP_ABSPATH . 'vendor/autoload.php';
			}

			if ( ! class_exists( 'Google_Service_Oauth2', false ) ) {
				require_once EFP_ABSPATH . 'vendor/autoload.php';
			}

			$network_object = new \Google\Client();
			$network_object->setClientId( $this->api_key );
			$network_object->setClientSecret( $this->api_secret );
			$network_object->setRedirectUri( $this->redirect_uri );
			$network_object->addScope( 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email' );

			if ( isset( $this->encoded_url ) && '' !== $this->encoded_url ) {
				$network_object->setState( base64_encode( 'redirect_to=' . $this->encoded_url ) );
			}

			$network_object->setAccessToken( $google_access_token );
			$google_service = new \Google_Service_Oauth2( $network_object );

			$user_profile = $google_service->userinfo->get();

			if ( empty( $user_profile ) ) {
				throw new \Exception( __( 'INVALID AUTHORIZATION', 'everest-forms-pro' ) );
			}

			/* If HTTP response is 200 continue otherwise send to connect page to retry */
			if ( ! empty( $user_profile->email ) ) {
				$this->response['status']  = 'SUCCESS';
				$this->response['message'] = 'Successfully get data';
				$profile                   = isset( $user_profile->link ) ? $user_profile->link : '';
				$email                     = isset( $user_profile->email ) ? $user_profile->email : '';
				$username                  = Helper::evfur_get_username( strtolower( $user_profile->givenName . '_' . $user_profile->familyName ), $email );
				$this->response['data']    = array(
					'email'       => $email,
					'username'    => $username,
					'profile'     => $profile,
					'id'          => isset( $user_profile->id ) ? $user_profile->id : '',
					'profile_pic' => isset( $user_profile->picture ) ? $user_profile->picture : '',
					'first_name'  => isset( $user_profile->givenName ) ? $user_profile->givenName : '',
					'last_name'   => isset( $user_profile->familyName ) ? $user_profile->familyName : '',
				);
			} else {
				$this->response['status']  = 'ERROR';
				$this->response['message'] = __( 'Could not connect to google, please contact site administrator.', 'everest-forms-pro' );
			}
		} catch ( \Exception $e ) {
			$this->response['status']  = 'ERROR';
			$this->response['message'] = $e->getMessage();
		}
	}
}
