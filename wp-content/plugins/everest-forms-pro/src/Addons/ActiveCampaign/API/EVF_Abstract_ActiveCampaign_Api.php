<?php
/**
 * Abstract class for ActiveCampaing API.
 *
 * @since 1.0.0
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API;

abstract class EVF_Abstract_ActiveCampaign_Api {
	/**
	 * API URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $api_url = null;

	/**
	 * API Key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $api_key = null;

	/**
	 * Contructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 */
	protected function __construct( $api_url, $api_key ) {
		$this->api_url = $api_url;
		$this->api_key = $api_key;
	}

	/**
	 * Make request with the API token.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $method    HTTP Method.
	 * @param string  $path      URL path.
	 * @param array   $headers    HTTP Headers.
	 * @param array   $body       Data send to the server.
	 * @param boolean $json     Whether the data is json or not.
	 *
	 * @return WP_Error| array
	 */
	protected function request( $method = 'GET', $path = '', $headers = array(), $body = array(), $json = false ) {
		$url = trailingslashit( $this->api_url ) . 'api/3/' . $path;

		$headers = array_merge(
			$headers,
			array(
				'Api-Token' => $this->api_key,
			)
		);

		/**
		 * Store api log preparation.
		 *
		 * @since 1.7.9
		 */
		$data = array();
		if ( $path == 'contacts' ) {
			$data = isset( $body['contact'] ) ? $body['contact'] : ( isset( $body['data'] ) ? $body['data'] : array() );
		}
		if ( $path == 'contactLists' ) {
			$data = isset( $body['contactList'] ) ? $body['contactList'] : ( isset( $body['data'] ) ? $body['data'] : array() );
		}
		if ( $path == 'notes' ) {
			$data = isset( $body['note'] ) ? $body['note'] : ( isset( $body['data'] ) ? $body['data'] : array() );
		}

		$form_id  = isset( $body['form_id'] ) ? $body['form_id'] : 0;
		$entry_id = isset( $body['entry_id'] ) ? $body['entry_id'] : 0;

		unset( $body['form_id'], $body['entry_id'] );

		// if the data is sent to be as json, encode it and set content type.
		if ( true === $json ) {
			$body    = wp_json_encode( $body );
			$headers = array_merge(
				$headers,
				array(
					'Content-Type' => 'application/json',
				)
			);
		}

		$query_args = array(
			'limit' => -1,
		);

		$query_string = http_build_query( $query_args );
		if ( 'fields?' === $path || 'lists?' === $path || 'tags?type=contact' === $path ) {
			$separator = 'tags?type=contact' === $path ? '&' : '';
			$url       = $url . $separator . $query_string;
		}

		$response = wp_remote_post(
			$url,
			array(
				'method'      => $method,
				'timeout'     => 45,
				'blocking'    => true,
				'headers'     => $headers,
				'body'        => $body,
				'data_format' => true === $json ? 'body' : '',
			)
		);

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		/**
		 * Action to track the api after submission.
		 *
		 * @since 1.7.9
		 */
		if ( ! empty( $data ) ) {
			do_action( 'evf_track_api_logs', $form_id, $entry_id, 'ActiveCampaign (' . $path . ')', $data, $response );
		}

		// Bail early if the request is succesfull.
		if ( 200 === $status_code || 201 === $status_code ) {
			return $body;
		}

		// Return error message if the entity is unprocessable.
		if ( 422 === $status_code && isset( $body['errors'] ) ) {
			$wp_error = new \WP_Error();
			foreach ( $body['errors'] as $error ) {
				if ( isset( $error['error'] ) ) {
					$wp_error->add( $error['error'], $error['title'], $error );
				} elseif ( isset( $error['code'] ) ) {
					$wp_error->add( $error['code'], $error['title'], $error );
				}
			}
			return $wp_error;
		}

		// Return error message for result not found.
		$message = isset( $body['message'] ) ? $body['message'] : esc_html__( 'Something went wrong, please try again', '' );
		return new \WP_Error( $status_code, $message );
	}

	/**
	 * Get API URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Get API Key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}
}
