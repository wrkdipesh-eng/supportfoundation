<?php
/**
 * Active Campaign custom field value api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Contact;

use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Abstract_ActiveCampaign_Api;
use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Interface_ActiveCampaign_Api;

class EVF_ActiveCampaign_Contact_Tag_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

	/**
	 * Contact Id.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $contact_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 * @param string $contact_id Contact's id.
	 */
	public function __construct( $api_url, $api_key, $contact_id ) {
		parent::__construct( $api_url, $api_key );
		$this->contact_id = $contact_id;
	}

	/**
	 * Add a tag to contact.
	 *
	 * @since 1.0.0
	 *
	 * @param array $id Tag's id
	 *
	 * @return WP_Error|array
	 */
	public function create( $id ) {
		$data['contactTag'] = array(
			'contact' => $this->contact_id,
			'tag'     => $id
		);

		return $this->request( 'POST', 'contactTags', array(), $data, true );
	}

	/**
	 * Delete a custom field.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the field option to delete.
	 *
	 * @return WP_Error|array
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "contactTags/{$id}" );

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
	 * @param int $int Contact tag id.
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
	 * @param int   $int Contact tag id.
	 * @param array $value Contact tag value.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $value ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}

	/**
	 * There is not api right now.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );
	}
}
