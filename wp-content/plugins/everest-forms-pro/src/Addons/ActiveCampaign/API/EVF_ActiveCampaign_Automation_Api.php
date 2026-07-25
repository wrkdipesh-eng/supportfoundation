<?php
/**
 * Active Campaign automation api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API;

class EVF_ActiveCampaign_Automation_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

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
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Automation id.
	 *
	 * @return WP_Error|array
	 */
	public function create( $id ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Automation id.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Automation id.
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
	 * @param int $id Automation id.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $campaign ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * Automations.
	 *
	 * Automations allow you to automate marketing communications to your contacts,
	 * as well as business processes like deals moving between stages, tags being
	 * added/removed from contacts, notes being added/removed to
	 * deals/contacts, etc. At this time, it is not possible to create, edit,
	 *  update, or delete automations via API.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "automations?{$params}" );
	}
}
