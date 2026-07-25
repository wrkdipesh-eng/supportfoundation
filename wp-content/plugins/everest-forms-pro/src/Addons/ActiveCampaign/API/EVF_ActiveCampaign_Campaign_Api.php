<?php
/**
 * Active Campaign campaign api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API;

class EVF_ActiveCampaign_Campaign_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api{

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $api_url
	 * @param [type] $api_key
	 */
	public function __construct( $api_url, $api_key ) {
		parent::__construct( $api_url, $api_key );
	}

	/**
	 * List all campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $orderby Order campaigns by send date
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$args = array_merge( $args, array(
			'orders[sdate]' => 'ASC'
		) );

		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "campaigns?{$params}" );
	}

	/**
	 * Retrieve a campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of campaign to retrieve.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "campaigns/{$id}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$campaign = isset( $response['campaign'] ) ? $response['campaign'] : array();

 		return $campaign;
	}

	/**
	 * Retrieve links associated to campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of campaign to retrieve Links for.
	 * @param array $args Offeset, limit parametrs.
	 *
	 * @return WP_Error|array
	 */
	public function get_links( $id, $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		$response = $this->request( 'GET', "campaigns/{$id}/links?{$params}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$links = isset( $response['links'] ) ? $response['links'] : array();

 		return $links;
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $int Campaign Id.
	 * @param array $campaign Campaign details.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $campaign ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Campaign id.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param array $campaign Campaign details.
	 *
	 * @return WP_Error|array
	 */
	public function create( $campaign ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}
}
