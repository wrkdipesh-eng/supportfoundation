<?php
/**
 * Active Campaign note api.
 */

namespace EverestForms\Pro\Addons\ActiveCampaign\API;

class EVF_ActiveCampaign_Note_Api extends EVF_Abstract_ActiveCampaign_Api
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
	 * Create a note.
	 *
	 * @since 1.0.0
	 *
	 * @param array $note note
	 *
	 * @return WP_Error|array
	 */
	public function create( $note ) {
		$response = $this->request( 'POST', 'notes', array(), $note, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$note = isset( $response['note'] ) ? $response['note'] : array();
		return $note;
	}

	/**
	 * Retrieve a note.
	 *
	 * @since 1.0.0
	 *
	 * @param iny $id ID of the note.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		return $this->request( 'GET', "notes/$id" );
	}

	/**
	 * Update a note.
	 *
	 * #since 1.0.0
	 *
	 * @param int   $id   ID of the note.
	 * @param array $note note details.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $note ) {
		$data['note'] = $note;

		$response = $this->request( 'PUT', "notes/{$id}", array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$note = isset( $response['note'] ) ? $response['note'] : array();
		return $note;
	}

	/**
	 * Delete a note.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the note.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "notes/{$id}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all notes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		$response = $this->request( 'GET', "notes?{$params}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$notes = isset( $response['notes'] ) ? $response['notes'] : array();
		return $notes;
	}
}
