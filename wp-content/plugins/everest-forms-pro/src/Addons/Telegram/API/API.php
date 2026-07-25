<?php
/**
 * API Class of Telegram.
 *
 * @since 1.7.7
 *
 * @package EverestForms\Pro\Telegram
 */

namespace EverestForms\Pro\Addons\Telegram\API;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms API Class.
 *
 * @since 1.7.7
 */
class API {
	/**
	 * API key.
	 *
	 * @var String
	 */
	private $api_key;

	/**
	 * Chat ID key.
	 *
	 * @var String
	 */
	private $chat_id;

	/**
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.telegram.org/bot';

	/**
	 * Create a new instance
	 *
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Gets the full url for processing.
	 *
	 * @since 1.7.7
	 *
	 * @return string Telegram base
	 */
	public function get_telegram_base_url() {
			$endpoint = is_array( $this->endpoint ) ? implode( '', $this->endpoint ) : $this->endpoint;
			$api_key  = is_array( $this->api_key ) ? implode( '', $this->api_key ) : $this->api_key;

			return $endpoint . $api_key;
	}

	/**
	 * Performs the HTTP request on the given resource.
	 *
	 * @since 1.7.7
	 *
	 * @param  string $resource Resource point.
	 * @param  array  $request_param Request Params.
	 */
	public function send_request( $resource, $request_param = array() ) {
		$request_url = add_query_arg( $request_param, $this->get_telegram_base_url() . $resource );

		$response = wp_remote_get( $request_url );

		return $response;
	}

	/**
	 * Test the provided API credentials.
	 *
	 * @return bool
	 */
	public function auth_test() {
		return $this->send_request(
			'/getMe'
		);
	}

	/**
	 * Send the message to the telegram channel.
	 *
	 * @since 1.7.7
	 *
	 * @param  string $message Telegram message.
	 * @param  string $parse_mode Message Parsing mode.
	 * @param string $chat_id Channel ID to send the message.
	 */
	public function send_message( $message, $parse_mode, $chat_id ) {
		if ( ! $parse_mode ) {
			$parse_mode = $this->parse_mode;
		}

		if ( 'none' === $parse_mode ) {
			$message = $this->clearText( $message );
		}

		return $this->send_request(
			'/sendMessage',
			array(
				'chat_id'    => $chat_id,
				'parse_mode' => $parse_mode,
				'text'       => rawurlencode( $message )
			)
		);
	}

}
