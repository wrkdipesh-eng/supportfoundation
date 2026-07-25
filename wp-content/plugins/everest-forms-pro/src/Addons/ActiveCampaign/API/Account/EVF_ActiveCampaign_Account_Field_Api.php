<?php
/**
 * Active Campaign acccount contact association api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Account;

class EVF_ActiveCampaign_Account_Field_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

	/**
	 * Account id.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $account_id;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_url API URL.
	 * @param string $api_key API Key.
	 */
	public function __construct( $api_url, $api_key, $account_id ) {
		parent::__construct( $api_url, $api_key );
		$this->account_id = $account_id;
	}

	/**
	 * Create an association.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field account field object.
	 *
	 * @return WP_Error|array
	 */
	public function create( $field ) {
		$data['accountCustomFieldMetum'] = $field;

		$response = $this->request( 'POST', 'accountCustomFieldMeta', array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account_contact = isset( $response['accountCustomFieldMetum'] ) ? $response['accountCustomFieldMetum'] : array();
		return $account_contact;
	}

	/**
	 * Retrieve an association.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the field to retrieve.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "accountCustomFieldMeta/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account_contact = isset( $response['accountCustomFieldMetum'] ) ? $response['accountCustomFieldMetum'] : array();
		return $account_contact;
	}

	/**
	 * Update an association.
	 *
	 * @since 1.0.0
	 *
	 * @param int		$id		ID of the custom field to update.
	 * @param string	$field	Job Title of the contact at the account.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $field  ) {
		$data['accountCustomFieldMetum'] = $field;

		$response = $this->request( 'PUT', "accountCustomFieldMeta/{$id}", array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account_contact = isset( $response['accountCustomFieldMetum'] ) ? $response['accountCustomFieldMetum'] : array();
		return $account_contact;
	}

	/**
	 * Delete a custom field.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the field to delete.
	 *
	 * @return WP_Error|array
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "accountCustomFieldMeta/{$id}" );

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
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "accountCustomFieldMeta?{$params}" );
	}

	/**
	 * Account field values instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int $field_id Field id.
	 *
	 * @return EVF_ActiveCampaign_Account_Field_Value_Api
	 */
	public function values( $field_id ) {
		return new EVF_ActiveCampaign_Account_Field_Value_Api(
			$this->api_url,
			$this->api_key,
			$this->account_id,
			$field_id
		);
	}
}
