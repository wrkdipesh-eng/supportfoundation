<?php
/**
 * Active Campaign custom field value api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Contact;

use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Abstract_ActiveCampaign_Api;
use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Interface_ActiveCampaign_Api;

class EVF_ActiveCampaign_Contact_Custom_Field_Value_Api extends EVF_Abstract_ActiveCampaign_Api
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
	 * Field Id.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $field_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_url, $api_key, $contact_id, $field_id ) {
		parent::__construct( $api_url, $api_key );
		$this->contact_id = $contact_id;
		$this->field_id   = $field_id;
	}

	/**
	 * Create a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_value Field value array.
	 *
	 * @return WP_Error|array
	 */
	public function create( $field_value ) {
		$data['fieldValue'] = array(
			'contact' => $this->contact_id,
			'field'   => $this->field_id,
			'value'   => $field_value
		);

		return $this->request( 'POST', 'fieldValues', array(), $data, true );
	}

	/**
	 * Retrieve a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the field to retrieve
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "fieldValues/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$fieldValue = isset( $response['fieldValue'] ) ? $response['fieldValue'] : array();
		return $fieldValue;
	}


	/**
	 * Update a custom field value for contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id     ID of the field to update
	 * @param array $fieldValue Field data.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $field_value ) {
		$data['fieldValue'] = array(
			'contact' => $this->contact_id,
			'field'   => $this->field_id,
			'value'   => $field_value
		);

		$response = $this->request( 'PUT', "fieldValues/{$id}", array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$fieldValue = isset( $response['fieldValue'] ) ? $response['fieldValue'] : array();
		return $fieldValue;
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
		$response = $this->request( 'DELETE', "fieldValues/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all custom fieldValues.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "fieldValues?{$params}" );
	}
}
