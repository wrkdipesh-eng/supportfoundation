<?php
/**
 * Active Campaign acccount note api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Account;

class EVF_ActiveCampaign_Account_Note_Api extends EVF_Abstract_ActiveCampaign_Api
	implements EVF_Interface_ActiveCampaign_Api {

	/**
	 * Account id.
	 *
	 * @sinc 1.0.0
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
	 * Create an acccount note.
	 *
	 * @since 1.0.0
	 *
	 * @param array $note	Note detail.
	 * @param int	$id		Account's id to assign new note to
	 *
	 * @return WP_Error|array
	 */
	public function create( $note ) {
		$data['note'] = $note;

		$response = $this->request( 'POST', "accounts/{$this->account_id}/notes", array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$account = isset( $response['account'] ) ? $response['account'] : array();
		return $account;
	}

	/**
	 * Get an acccount note.
	 *
	 * @since 1.0.0
	 *
	 * @param int	$note_id		Account note's id to update
	 * @param array $note	Note detail.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $note_id  )  {
		return $this->request( 'GET', "notes/{$note_id}" );
	}

	/**
	 * Get an acccount note.
	 *
	 * @since 1.0.0
	 *
	 * @param int	$note_id		Account note's id to update
	 * @param array $note	Note detail.
	 *
	 * @return WP_Error|array
	 */
	public function update( $note_id, $note  ) {
		 $data['note'] = $note;

		return $this->request( 'PUT', "accounts/{$this->account_id}/notes/{$note_id}", array(), $data, true );
	}

	/**
	 * Delete an acccount note.
	 *
	 * @since 1.0.0
	 *
	 * @param int	$note_id		Account note's id to update
	 *
	 * @return WP_Error|array
	 */
	public function delete( $note_id  ) {
		$response = $this->request( 'DELETE', "accounts/{$this->account_id}/notes/{$note_id}" );

		if( is_wp_error( $response) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List acccount notes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $note	Note detail.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() )  {
		$params = urlencode( implode( '&', $args ) );

		$response = $this->request( 'GET', "accounts/{$this->account_id}/notes" );

		if( is_wp_error( $response) ) {
			return $response;
		}

		$notes = isset( $response['notes'] ) ? $response['notes'] : array();
		return $notes;
	}
}
