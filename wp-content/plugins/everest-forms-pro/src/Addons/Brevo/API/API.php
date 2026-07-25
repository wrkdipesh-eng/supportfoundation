<?php
/**
 * EverestForms Brevo API Class.
 *
 * @package EverestForms\Pro\Addons\Brevo\API\API
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Brevo\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class API.
 */
class API {
	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * API end point.
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.sendinblue.com/v3/';

	/**
	 * Timeout.
	 */
	const TIMEOUT = 30;

	/**
	 * SSL Verification
	 *
	 * @var boolean
	 */
	public $verify_ssl = true;

	/**
	 * Create a new instance
	 *
	 * @param string $api_key Your Brevo API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Validate API Token.
	 *
	 * @since 1.7.7
	 *
	 * @return true|string True on success, error message string on failure.
	 */
	public function validate_api_key() {

		$account = $this->get_account();

		if ( isset( $account['email'] ) && ! empty( $account['email'] ) ) {
			return true;
		}

		if ( isset( $account['message'] ) && ! empty( $account['message'] ) ) {
			return $account['message'];
		}

		return __( 'Could not authenticate with the provider.', 'everest-forms-pro' );
	}

	/**
	 * Get account.
	 *
	 * @since 1.7.7
	 */
	public function get_account() {
		return $this->make_request( 'account/', array(), 'GET' );
	}

	/**
	 * Send request to Brevo API.
	 *
	 * @param string  $path     API Path.
	 * @param array   $body     Body.
	 * @param string  $method   Method.
	 * @param integer $form_id  The form id.
	 * @param integer $entry_id The entry id.
	 *
	 * @return array
	 */
	public function make_request( $path, $body, $method, $form_id = '', $entry_id = '' ) {

		$query_string = http_build_query(
			array(
				'limit'  => 50,
				'offset' => 0,
			)
		);

		$api_url = $this->endpoint . $path . '?' . $query_string;

		$args = array(
			'method'    => $method,
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
				'api-key'      => $this->api_key,
			),
			'body'      => empty( $body ) ? array() : wp_json_encode( $body ),
			'sslverify' => $this->verify_ssl,
			'timeout'   => self::TIMEOUT,
		);

		switch ( $method ) {
			case 'GET':
				$raw_response = wp_remote_get( $api_url, $args );
				break;
			case 'POST':
				$raw_response = wp_remote_post( $api_url, $args );
				/**
				 * Action to track the api after submission.
				 *
				 * @since 1.7.8
				 */
				do_action( 'evf_track_api_logs', $form_id, $entry_id, 'brevo', $body, $raw_response );
				break;
			default:
				$raw_response = wp_remote_request( $api_url, $args );
				break;
		}

		if ( is_wp_error( $raw_response ) ) {
			return array();
		}

		$decoded = json_decode( wp_remote_retrieve_body( $raw_response ), true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Get all lists.
	 *
	 * @since 1.7.7
	 */
	public function get_lists() {
		return $this->make_request( 'contacts/lists', array(), 'GET' );
	}

	/**
	 * Get all attributes.
	 *
	 * @since 1.7.7
	 */
	public function get_attributes() {
		return $this->make_request( 'contacts/attributes', array(), 'GET' );
	}

	/**
	 * Get all SMTP templates.
	 *
	 * @since 1.0.1
	 */
	public function get_smtp_templates() {
		return $this->make_request( 'smtp/templates', array(), 'GET' );
	}
}
