<?php
/**
 * Active Campaign acccount api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Account;

class EVF_ActiveCampaign_Account_Api extends EVF_Abstract_ActiveCampaign_Api
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
	 * Create an account.
	 *
	 * @since 1.0.0
	 *
	 * @param array $account account
	 *
	 * @return WP_Error|array
	 */
	public function create( $account ) {
		$data['account'] = $account;

		$response = $this->request( 'POST', 'accounts', array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account = isset( $response['account'] ) ? $response['account'] : array();
		return $account;
	}

	/**
	 * Retrieve a account.
	 *
	 * @since 1.0.0
	 *
	 * @param iny $id ID of the account.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$this->id = $id;

		$response = $this->request( 'GET', "accounts/$id" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account = isset( $response['account'] ) ? $response['account'] : array();
		return $account;
	}

	/**
	 * Update a account.
	 *
	 * #since 1.0.0
	 *
	 * @param int   $id   ID of the account.
	 * @param array $account account details.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $account ) {
		$data['account'] = $account;
		$response        = $this->request( 'PUT', "accounts/{$id}", array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account = isset( $response['account'] ) ? $response['account'] : array();
		return $account;
	}

	/**
	 * Delete a account.
	 *
	 * @since 1.0.0
	 *
d 	 * @param int $id ID of the account.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "accounts/{$id}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all accounts.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "accounts?{$params}" );
	}

	/**
	 * Account notes.
	 *
	 * @param int $account_id     Account's id to assign new note to
	 *
	 * @return EVF_ActiveCampaign_Account_Note_Api
	 */
	public function notes( $account_id ) {
		return new EVF_ActiveCampaign_Account_Note_Api( $this->api_url, $this->api_key, $account_id );
	}


	/**
	 * Account contact association.
	 *
	 * @param int $account_id     Account's id to assign new note to
	 *
	 * @return EVF_ActiveCampaign_Account_Contact_Association_Api
	 */
	public function contacts( $account_id ) {
		return new EVF_ActiveCampaign_Account_Contact_Association_Api( $this->api_url, $this->api_key, $account_id );
	}

	/**
	 * Account field..
	 *
	 * @param int $account_id Account's id to assign new note to
	 *
	 * @return EVF_ActiveCampaign_Account_Field_Api
	 */
	public function fields( $account_id = '' ) {
		return new EVF_ActiveCampaign_Account_Field_Api( $this->api_url, $this->api_key, $account_id );
	}
}
