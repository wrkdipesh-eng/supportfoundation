<?php
/**
 * Active Campaign acccount contact association api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Account;

class EVF_ActiveCampaign_Account_Contact_Association_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

	/**
	 * Account id.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $account_id;

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
	 * @param int $contact_id Contact id.
	 * @param string $job_title Job Title of the contact at the account.
	 *
	 * @return WP_Error|array
	 */
	public function create( $contact_id, $job_title = '' ) {
		$data['accountContact'] = array(
			'account'  => $this->account_id,
			'contact'  => $contact_id,
			'jobTitle' => $job_title
		);

		$response = $this->request( 'POST', 'accountContacts', array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account_contact = isset( $response['accountContact'] ) ? $response['accountContact'] : array();
		return $account_contact;
	}

	/**
	 * Retrieve an association.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Association's ID
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "accountContacts/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account_contact = isset( $response['accountContact'] ) ? $response['accountContact'] : array();
		return $account_contact;
	}

	/**
	 * Update an association.
	 *
	 * @since 1.0.0
	 *
	 * @param int		$id			Association's ID
	 * @param int		$contact_id Contact ID
	 * @param string	$job_title	Job Title of the contact at the account
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $contact_id, $job_title = '' ) {
		$data['accountContact'] = array(
			'account'  => $this->account_id,
			'contact'  => $contact_id,
			'jobTitle' => $job_title
		);

		$response = $this->request( 'PUT', "accountContacts/{$id}", array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account_contact = isset( $response['accountContact'] ) ? $response['accountContact'] : array();
		return $account_contact;
	}

	/**
	 * Delete an association.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id Association's ID
	 *
	 * @return WP_Error|array
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "accountContacts/{$id}" );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all associations.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "accountContacts?{$params}" );
	}
}
