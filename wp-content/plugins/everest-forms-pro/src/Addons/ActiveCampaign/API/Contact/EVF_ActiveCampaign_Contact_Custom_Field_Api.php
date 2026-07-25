<?php
/**
 * Active Campaign custom field api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Contact;

use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Abstract_ActiveCampaign_Api;
use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Interface_ActiveCampaign_Api;

class EVF_ActiveCampaign_Contact_Custom_Field_Api extends EVF_Abstract_ActiveCampaign_Api
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
	 */
	public function __construct( $api_url, $api_key, $contact_id = '' ) {
		parent::__construct( $api_url, $api_key );
		$this->contact_id = $contact_id;
	}

	/**
	 * Create a custom field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field array.
	 *
	 * @return WP_Error|array
	 */
	public function create( $field ) {
		$data['field'] = $field;

		return $this->request( 'POST', 'fields', array(), $data, true );
	}

	/**
	 * Retrieve a custom field.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the field to retrieve
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		return $this->request( 'GET', "fields/{$id}" );
	}


	/**
	 * Update a custom field.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id     ID of the field to update
	 * @param array $field  Field data.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $field ) {
		$data['field'] = $field;

		$response = $this->request( 'PUT', "fields/{$id}", array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field = isset( $response['field'] ) ? $response['field'] : array();
		return $field;
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
		$response = $this->request( 'DELETE', "fields/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "fields?{$params}" );
	}

	/**
	 * Create a custom field relationship to list(s).
	 *
	 * @since 1.0.0
	 *
	 * @param int $field_id ID of the field to create the relationship.
	 * @param int $rel_id ID of the list to create the relationship (0 makes the field available on all lists).
	 *
	 * @return WP_Error|array
	 */
	public function create_relationship_to_list( $field_id, $rel_id ) {
		$data['fieldRel'] = array(
			'field' => $field_id,
			'relid' => $rel_id
		);

		$response = $this->request( 'POST', 'fieldRels', array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field_rel = isset( $response['fieldRel'] ) ? $response['fieldRel'] : array();
		return $field_rel;
	}

	/**
	 * Create custom field optins
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_rel Field relation.
	 *
	 * @return WP_Error|array
	 */
	public function create_options( $field_options ) {
		$data['fieldOptions'] = $field_options;
		$response             = $this->request( 'POST', 'fieldOptions/bulk', array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field_options = isset( $response['fieldOptions'] ) ? $response['fieldOptions'] : array();
		return $field_options;
	}

	/**
	 * Get field value instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int $field_id Field id.
	 *
	 * @return EVF_ActiveCampaign_Contact_Custom_Field_Value_Api
	 */
	public function fieldValue( $field_id ) {
		return new EVF_ActiveCampaign_Contact_Custom_Field_Value_Api(
			$this->api_url,
			$this->api_key,
			$this->contact_id,
			$field_id
		);
	}
}
