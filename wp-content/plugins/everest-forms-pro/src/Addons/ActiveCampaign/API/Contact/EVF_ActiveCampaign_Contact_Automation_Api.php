<?php
/**
 * Active Campaign automation api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Contact;

use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Abstract_ActiveCampaign_Api;
use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Interface_ActiveCampaign_Api;

class EVF_ActiveCampaign_Contact_Automation_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

	/**
	 * Contact id.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $contact_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_url, $api_key, $contact_id ) {
		parent::__construct( $api_url, $api_key );
		$this->contact_id = $contact_id;
	}

	/**
	 * Add a contact to an automation
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Automation ID of the automation, to be linked to the contactAutomation.
	 *
	 * @return WP_Error|Array
	 */
	public function create( $id ) {
		$data['contactAutomation'] = array(
			'contact'    => $this->contact_id,
			'automation' => $id
		);

		return $this->request( 'POST', 'contactAutomations', array(), $data, true );
	}

	/**
	 * Retrieve an automation a contact is in.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Automation ID of the automation, to be linked to the contactAutomation.
	 *
	 * @return WP_Error|Array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "contactAutomations/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$contactAutomation = isset( $response['contactAutomation'] ) ? $response['contactAutomation'] : array();
		return $contactAutomation;
	}

	/**
	 * Remove a contact from an automation.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Automation ID of the automation, to be linked to the contactAutomation.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "contactAutomations/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update( $id, $campaign ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * List all automations a contact is in.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|bool
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'DELETE', "contactAutomations/{$params}" );
	}
}
