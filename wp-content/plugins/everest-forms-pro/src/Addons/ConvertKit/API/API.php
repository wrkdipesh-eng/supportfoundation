<?php

/**
 * iContact API.
 *
 * @package EverestForms\Pro\Addons\ConvertKit\API
 * @since   1.0.0
 * @version 1.0.0
 */

 namespace EverestForms\Pro\Addons\ConvertKit\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Api Class.
 *
 * @since 1.0.0
 */
class API {

	public function api_request( $endpoint, $args = array(), $request_body = null, $request_args = array() ) {
		// echo $endpoint;
		$endpoint    = ltrim( $endpoint, '/' );
		$request_url = "https://api.convertkit.com/v3/{$endpoint}";

		$request_args = array_merge(
			array(
				'body'    => $request_body,
				'headers' => array(
					'Accept' => 'application/json',
				),
				'method'  => 'GET',
				'timeout' => 5,
			),
			$request_args
		);

		$request_url = add_query_arg( $args, $request_url );
		$response    = wp_remote_request( $request_url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			if ( is_null( $response_data ) ) {
				return new \WP_Error( 'parse_failed', __( 'Could not parse response from ConvertKit', 'everest-forms-pro' ) );
			} elseif ( isset( $response_data['error'] ) && isset( $response_data['message'] ) ) {
				return new \WP_Error( $response_data['error'], $response_data['message'] );
			} else {
				return $response_data;
			}
		}
	}

	public function api_post( $endpoint, $request_body = null ) {
		$request_url = "https://api.convertkit.com/v3/{$endpoint}";

		$request_args = array(
			'body'    => $request_body,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
			'method'  => 'POST',
		);

		$response = wp_remote_request( $request_url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			if ( is_null( $response_data ) ) {
				return new \WP_Error( 'parse_failed', __( 'Could not parse response from ConvertKit', 'everest-forms-pro' ) );
			} elseif ( isset( $response_data['error'] ) && isset( $response_data['message'] ) ) {
				return new \WP_Error( $response_data['error'], $response_data['message'] );
			} else {
				return $response_data;
			}
		}
	}

	public function get_api_forms( $api_key = null ) {
		$query_args = is_null( $api_key ) ? array() : array(
			'api_key' => $api_key,
		);
		$response   = $this->api_request( 'forms', $query_args, null, array() );

		return is_wp_error( $response ) ? $response : ( isset( $response['forms'] ) ? array_combine( wp_list_pluck( $response['forms'], 'id' ), $response['forms'] ) : array() );
	}

	public function add_email_to_api( $form, $email, $name, $api_key = null, $fields = array() ) {
		$query_args = is_null( $api_key ) ? array() : array(
			'api_key' => $api_key,
		);

		$custom_fields = array();
		foreach ( $fields as $key => $value ) {
			$custom_fields[ $key ] = $value;
		}

		$request_body = array(
			'name'  => $name,
			'email' => $email,
		);

		if ( ! empty( $custom_fields ) ) {
			$request_body['fields'] = $custom_fields;
		}

		$request_args = array(
			'method' => 'POST',
		);

		$response = $this->api_request( sprintf( 'forms/%d/subscribe', $form ), $query_args, $request_body, $request_args );

		return $response;
	}

}
