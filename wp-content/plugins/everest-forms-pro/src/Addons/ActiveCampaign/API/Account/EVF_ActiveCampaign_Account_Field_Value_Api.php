<?php
/**
 * Active Campaign acccount contact association api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Account;

class EVF_ActiveCampaign_Account_Field_Value_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

	/**
	 * Account id.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	private $account_id;

	/**
	 * Custom field id.
	 *
	 * @since 1.0.0
	 *
	 * @var int
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
	public function __construct( $api_url, $api_key, $account_id, $field_id ) {
		parent::__construct( $api_url, $api_key );
		$this->account_id = $account_id;
		$this->field_id = $field_id;
	}

	/**
	 * Create an association.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_value Values for the field. (For currency field only, this needs to be in cents: eg, 10050 = 100.5)
	 * @param string $field_currency Currency code for the money value.
	 *
	 * @return WP_Error|array
	 */
	public function create( $field_value, $field_currency = '') {
		$data['accountCustomFieldDatum'] = array(
			'customerAccountId' => $this->account_id,
			'customFieldId'     => $this->field_id,
			'fieldValue'        => $field_value,
			'fieldCurrency'     => $field_currency
		);

		$response = $this->request( 'POST', 'accountCustomFieldData', array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field_values = isset( $response['accountCustomFieldDatum'] ) ? $response['accountCustomFieldDatum'] : array();
		return $field_values;
	}

	/**
	 * Bulk create a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_value Field value object.
	 *
	 * @return WP_Error|array
	 */
	public function create_bulk( $field_values ) {
		$account_id = $this->account_id;
		$field_id   = $this->field_id;

		$data = array_map( function( $field_value ) use( $account_id, $field_id ) {
			return array_merge( $field_value, array(
				'accountId'     => $account_id,
				'customFieldId' => $field_id
			) );
		}, $field_values );

		$response = $this->request( 'POST', 'accountCustomFieldData/bulkCreate', array(), $field_values, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field_values = isset( $response['accountCustomFieldDatum'] ) ? $response['accountCustomFieldDatum'] : array();
		return $field_values;
	}

	/**
	 *
	 * Retrieve a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the dealCustomFieldData to retrieve.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "accountCustomFieldData/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field_values = isset( $response['accountCustomFieldDatum'] ) ? $response['accountCustomFieldDatum'] : array();
		return $field_values;
	}

	/**
	 * Update a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param int	 $id				ID of the custom fields value to update.
	 * @param string $field_value		Values for the field. (For currency field only, this needs to be in cents: eg, 10050 = 100.5)
	 * @param string $field_currency	Currency code for the money value.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $field_value = '', $field_currency = ''  ) {
		$data['accountCustomFieldDatum'] = array(
			'fieldValue'    => $field_value,
			'fieldCurrency' => $field_currency
		);

		$response = $this->request( 'PUT', "accountCustomFieldData/{$id}", array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$field_values = isset( $response['accountCustomFieldDatum'] ) ? $response['accountCustomFieldDatum'] : array();
		return $field_values;
	}

		/**
	 * Update a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field_values Field values.
	 *
	 * @return WP_Error|array
	 */
	public function update_bulk( $field_values  ) {
		$response = $this->request( 'PUT', 'accountCustomFieldData/bulkUpdate', array(), $field_values, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$message = isset( $response['message'] ) ? $response['message'] : '';
		return $message;
	}

	/**
	 * Delete a custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the dealCustomFieldData to retrieve.
	 *
	 * @return WP_Error|array
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "accountCustomFieldData/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all custom field values.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "accountCustomFieldData?{$params}" );
	}
}
