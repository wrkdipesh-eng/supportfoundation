<?php
/**
 * Active Campaign authenticate api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API;

class EVF_ActiveCampaign_Authenticate_Api extends EVF_Abstract_ActiveCampaign_Api {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_url, $api_key ) {
		parent::__construct( $api_url, $api_key );
	}

	/**
	 * Authenticate.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|bool
	 */
	public function authenticate() {
		$response = $this->request( 'GET', 'contacts' );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $response;
	}
}
