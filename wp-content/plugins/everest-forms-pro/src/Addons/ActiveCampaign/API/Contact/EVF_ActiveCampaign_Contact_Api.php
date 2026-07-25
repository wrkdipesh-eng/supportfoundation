<?php
/**
 * Active Campaign contact api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API\Contact;

use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Abstract_ActiveCampaign_Api;
use EverestForms\Pro\Addons\ActiveCampaign\API\EVF_Interface_ActiveCampaign_Api;

class EVF_ActiveCampaign_Contact_Api extends EVF_Abstract_ActiveCampaign_Api implements EVF_Interface_ActiveCampaign_Api {

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
	public function __construct( $api_url, $api_key ) {
		parent::__construct( $api_url, $api_key );
	}

	/**
	 * Create a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $contact Contact.
	 * @param string $path Contact path used by contact_or_update().
	 *
	 * @return WP_Error|array
	 */
	public function create( $contact, $path = '' ) {
		$form_id  = isset( $contact['form_id'] ) ? $contact['form_id'] : 0;
		$entry_id = isset( $contact['entry_id'] ) ? $contact['entry_id'] : 0;
		unset( $contact['form_id'], $contact['entry_id'] );

		$data['contact']  = $contact;
		$data['form_id']  = $form_id;
		$data['entry_id'] = $entry_id;

		if ( empty( $path ) ) {
			$response = $this->request( 'POST', 'contacts', array(), $data, true );
		} else {
			$response = $this->request( 'POST', "contact/{$path}", array(), $data, true );
		}

		// Fail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$contact = isset( $response['contact'] ) ? $response['contact'] : array();
		return $contact;
	}

	/**
	 * Create or update contact.
	 *
	 * @since 1.0.0
	 *
	 * @param array $contact Contact.
	 *
	 * @return WP_Error|array
	 */
	public function create_or_update( $contact ) {
		return $this->create( $contact, 'sync' );
	}

	/**
	 * Retrieve a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param iny $id ID of the contact.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		return $this->request( 'GET', "contacts/$id" );
	}

	/**
	 * Update list status for a contact.
	 *
	 * Subscribe a contact to a list or unsubscribe a contact from a list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $contact_list Contact list.
	 *
	 * @return WP_Error|array
	 */
	public function update_list_status( $contact_list ) {
		$data['contactList'] = $contact_list;
		return $this->request( 'POST', 'contactLists', array(), $data, true );
	}

	/**
	 * Update a contact.
	 *
	 * #since 1.0.0
	 *
	 * @param int   $id ID of the contact.
	 * @param array $contact Contact details.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $contact ) {
		$data['contact'] = $contact;
		$response        = $this->request( 'PUT', "contacts/{$id}", array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$contact = isset( $response['contact'] ) ? $response['contact'] : array();
		return $contact;
	}

	/**
	 * Delete a contact.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the contact.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "contacts/{$id}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all contacts.
	 *
	 * View many (or all) contacts by including their ID's or various filters.
	 * This is useful for searching for contacts that match certain
	 * criteria - such as being part of a certain list, or having a specific custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		$response = $this->request( 'GET', "contacts?{$params}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$contacts = isset( $response['contacts'] ) ? $response['contacts'] : array();
		return $contacts;
	}

	/**
	 * List all automations the contact is in.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id ID of the contact to receive automations for.
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list_automations( $id, $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		$response = $this->request( 'GET', "contacts/{$id}/contactAutomations?{$params}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$contactAutomations = isset( $response['contactAutomations'] ) ? $response['contactAutomations'] : array();
		return $contactAutomations;
	}

	/**
	 * Retrieve a contacts score value.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id ID of the contact.
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function score_value( $id, $args ) {
		$params   = urlencode( implode( '&', $args ) );
		$response = $this->request( 'GET', "contacts/{$id}/scoreValues?{$params}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$scoreValues = isset( $response['scoreValues'] ) ? $response['scoreValues'] : array();
		return $scoreValues;
	}

	/**
	 * Contact automations.
	 *
	 * @param int $contact_id Contact ID of the Contact.
	 *
	 * @return EVF_ActiveCampaign_Contact_Automation_Api
	 */
	public function automations( $contact_id ) {
		return new EVF_ActiveCampaign_Contact_Automation_Api( $this->api_url, $this->api_key, $contact_id );
	}

	/**
	 * Contact custom fields.
	 *
	 * @since 1.0.0
	 *
	 * @param int $contact_id Contact Ids.
	 *
	 * @return EVF_ActiveCampaign_Contact_Custom_Field_Api
	 */
	public function fields( $contact_id = '' ) {
		$this->contact_id = $contact_id;
		return new EVF_ActiveCampaign_Contact_Custom_Field_Api( $this->api_url, $this->api_key, $contact_id );
	}

	/**
	 * Contact tags.
	 *
	 * @since 1.0.0
	 *
	 * @param int $contact_id Contact Ids.
	 *
	 * @return EVF_ActiveCampaign_Contact_Tag_Api
	 */
	public function tags( $contact_id = '' ) {
		$this->contact_id = $contact_id;
		return new EVF_ActiveCampaign_Contact_Tag_Api( $this->api_url, $this->api_key, $contact_id );
	}

	/**
	 * Contact list subscribe.
	 *
	 * @since 1.0.1
	 *
	 * @param int $contact_id Contact Id.
	 * @param int $list_id List Id.
	 *
	 * @return $contact Contact List.
	 */
	public function subscribe( $contact_id, $list_id ) {
		$data['contactList'] = array(
			'list'    => (int) $list_id,
			'contact' => (int) $contact_id,
			'status'  => 1,
		);

		$response = $this->request( 'POST', 'contactLists', array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$contactList = isset( $response['contactList'] ) ? $response['contactList'] : array();
		return $contactList;
	}

	/**
	 * Retrieve the contact ID by email address.
	 *
	 * This function sends a GET request to the ActiveCampaign API to fetch the contact
	 * details associated with the provided email address. If the request is successful
	 * and the contact is found, it returns the contact ID. If the request fails or the
	 * contact is not found, it returns 0.
	 *
	 * @param string $email The email address of the contact to retrieve.
	 * @return int The contact ID if found, or 0 if not found or on error.
	 */
	public function get_id_by_email( $email ) {
		$response = $this->request( 'GET', "contacts?email={$email}" );
		if ( is_wp_error( $response ) ) {
			return 0;
		}
		$contact = isset( $response['contacts'] ) ? $response['contacts'] : array();
		if ( ! empty( $contact ) ) {
			return $contact[0]['id'];
		}
		return 0;
	}
}
