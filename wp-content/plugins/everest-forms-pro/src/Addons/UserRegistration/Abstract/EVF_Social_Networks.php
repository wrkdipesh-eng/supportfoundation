<?php

use EverestForms\Pro\Addons\UserRegistration\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract EVF Field Setting Class.
 *
 * Provides an abstract class for social network functionality
 * in Everest Forms User Registration addon.
 *
 * @version  1.0.0
 * @package  EverestForms/Pro/Addon/UserRegistration/Abstract
 * @category Abstract Class
 * @author   WPEverest
 */
abstract class EVF_Social_Networks {

	/**
	 * Response data for social network operations.
	 *
	 * @var array
	 */
	protected $response;

	/**
	 * Social network API key.
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Social network API secret.
	 *
	 * @var string
	 */
	protected $api_secret;

	/**
	 * Retrieves user data by email address.
	 *
	 * @since 1.7.9
	 *
	 * @param string $email The email address to look up.
	 * @return array User data array if found, or empty array.
	 */
	public function get_user_by_email( $email ) {
		global $wpdb;

		$user_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE user_email=%s", $email ) );

		if ( isset( $user_data[0] ) ) {
			return (array) $user_data[0];
		}

		return array();
	}

	/**
	 * Constructs the callback URL for social network redirects.
	 *
	 * @since 1.7.9
	 *
	 * @return string The formatted callback URL.
	 */
	function call_back_url() {

		$url = ( ! empty( $_SERVER['HTTPS'] ) ) ? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$formatted_url = substr( $url, 0, strpos( $url, '?' ) );
		$url           = esc_url_raw( $formatted_url );

		if ( strpos( $url, '?' ) === false ) {
			$url .= '?';
		} else {
			$url .= '&';
		}

		return $url;
	}

	/**
	 * Initiates a request for the social network data.
	 *
	 * @since 1.7.9
	 *
	 * @param string $api_key    API key for the social network.
	 * @param string $api_secret API secret for the social network.
	 * @return mixed Social network data or response.
	 */
	abstract function request( $api_key, $api_secret );


	/**
	 * Retrieves social network data.
	 *
	 * @since 1.7.9
	 *
	 * @return mixed Social network data.
	 */
	abstract function get_social_network_data();

	/**
	 * Sets access token for the social network session.
	 *
	 * @since 1.7.9
	 *
	 * @return mixed Access token response.
	 */
	abstract function set_access_token();

	/**
	 * Sets the response from the social network.
	 *
	 * @since 1.7.9
	 *
	 * @return mixed Response data.
	 */
	abstract function set_network_response();


	/**
	 * Sets the response and stores it in the session.
	 *
	 * @since 1.7.9
	 *
	 * @param array $response Response data from the social network.
	 * @return void
	 */
	protected function set_response( $response ) {
		global $evfur_response_global;

		$evfur_response_global = $response;

		$response_json = json_encode( $response );

		if ( 'SUCCESS' === $response['status'] ) {
			Helper::everest_forms_connect_set_session( 'everest_forms_social_connect_network_response', $response );
		}
	}
}
