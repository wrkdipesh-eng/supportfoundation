<?php

/**
 * iContact API.
 *
 * @package EverestForms\iContact\Api
 * @since   1.0.0
 * @version 1.0.0
 */

 namespace EverestForms\Pro\Addons\iContact\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Api Class.
 *
 * @since 1.0.0
 */
class Api {


	/**
	 * Base URL for the iContact Pro API.
	 *
	 * @var string
	 */
	protected $api_url = 'https://app.icontact.com/icp/a/';

	/**
	 * iContact Pro account ID.
	 * This variable represents your iContact Pro account ID, used to identify your account when making API requests.
	 *
	 * @var string|null
	 */
	public $account_id = null;

	/**
	 * Client folder ID within iContact Pro account.
	 * This variable represents the ID of a client folder within your iContact Pro account, used for organizing contacts and campaigns.
	 *
	 * @var string|null
	 */
	protected $client_folder_id = null;


	/**
	 * Api Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $app_id           App Id.
	 * @param  string $api_username     App Username.
	 * @param  string $api_password     App Password.
	 * @param  int    $client_folder_id Client Folder Id.
	 * @param  int    $account_id       Account Id.
	 */
	public function __construct( $app_id, $api_username, $api_password, $client_folder_id = null, $account_id = '' ) {
		$this->app_id           = $app_id;
		$this->api_username     = $api_username;
		$this->api_password     = $api_password;
		$this->client_folder_id = $client_folder_id;
		$this->account_id       = $account_id;
	}

	/**
	 * Make API request.
	 *
	 * @param string $path   Path.
	 * @param array  $data   Data.
	 * @param string $method Method.
	 */
	public function make_request( $path = null, $data = array(), $method = 'GET', $key = null, $context = '' ) {

		$request_query = ( 'GET' === $method && ! empty( $data ) ) ? '?' . http_build_query( $data ) : '';

		$request_url = $this->api_url . $path . $request_query;

		$header = array(
			'Expect'       => '',
			'Accept'       => 'application/json',
			'Content-type' => 'application/json',
			'Api-Version'  => '2.2',
			'Api-AppId'    => $this->app_id,
			'Api-Username' => $this->api_username,
			'Api-Password' => $this->api_password
		);

		$args = array(
			'headers' => $header,
			'method'  => $method
		);

		if ( 'POST' === $method ) {
			$args['body'] = json_encode( $data );
		}

		$response = wp_remote_request( $request_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {

			$response = json_decode( $response['body'], true );

			if ( isset( $response['errors'] ) ) {
				if ( isset( $response['errors'][0] ) && is_string( $response['errors'][0] ) ) {
					if ( 'test' === $context ) {
						return $response;
					}
					throw new \Exception( $response['errors'][0] );
				}
			}

			if ( isset( $response['warnings'] ) ) {
				if ( isset( $response['warnings'][0] ) && is_string( $response['warnings'][0] ) ) {
					if ( 'test' === $context ) {
						return $response;
					}
					throw new \Exception( $response['warnings'][0] );
				}
			}

			return empty( $key ) ? $response : $response[ $key ];
		}
	}

	/**
	 * Test Call.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $context Context.
	 */
	public function auth_test( $context = '' ) {
		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/', array(), 'GET', '', $context );
	}

	/**
	 * Set the Account ID.
	 */
	public function set_the_account_id() {

		if ( empty( $this->account_id ) ) {

			$accounts = $this->make_request( '/' );

			if ( isset( $accounts['errors'] ) ) {
				throw new \Exception( $accounts['errors'][0] );
			}

			$account = $accounts['accounts'][0];

			if ( 1 === $account['enabled'] ) {
				$this->account_id = $account['accountId'];
			} else {
				throw new \Exception( __( 'Your account has been disabled.', 'everest-forms-pro' ) );
			}
		}

		return $this->account_id;

	}

	/**
	 * Add a new contact.
	 *
	 * @param array $contact Contact.
	 */
	public function add_contact( $contact ) {

		$contacts = $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'contacts', array( $contact ), 'POST', 'contacts' );

		return $contacts[0];

	}

	/**
	 * Add a contact to a list.
	 *
	 * @param int    $contact_id Contact ID.
	 * @param int    $list_id    List ID.
	 * @param string $status     Status.
	 */
	public function add_subscription( $contact_id, $list_id, $status = 'normal' ) {

		$subscription_data = array(
			'contactId' => $contact_id,
			'listId'    => $list_id,
			'status'    => $status
		);

		$new_subscription = $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'subscriptions', array( $subscription_data ), 'POST', 'subscriptions' );

		return $new_subscription;

	}

	/**
	 * Fetch all contacts associated with this account.
	 *
	 * @since 1.0.0
	 */
	public function get_contacts() {

		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'contacts', array(), 'GET', 'contacts' );

	}

	/**
	 * Fetch contact by email address.
	 *
	 * @since 1.0.0
	 */
	public function get_contact_by_email( $email ) {
		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'contacts', array( 'email' => $email ), 'GET', 'contacts' );
	}

	/**
	 * Fetch custom fields for associated with this account.
	 *
	 * @since 1.0.0
	 */
	public function get_custom_fields() {

		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'customfields', array(), 'GET', 'customfields' );

	}

	/**
	 * Fetch all lists associated with this account.
	 *
	 * @since 1.0.0
	 */
	public function get_lists() {

		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'lists', array( 'limit' => 999 ), 'GET', 'lists' );

	}

	/**
	 * Fetch a specific list associated with this account.
	 *
	 * @since 1.0.0
	 * @param int $list_id List Id.
	 */
	public function get_list( $list_id ) {

		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'lists/' . $list_id, array(), 'GET', 'list' );

	}

	/**
	 * Update an existing contact.
	 *
	 * @since 1.0.0
	 * @param int   $contact_id Contact Id.
	 * @param array $contact Contact.
	 */
	public function update_contact( $contact_id, $contact ) {

		return $this->make_request( $this->set_the_account_id() . '/c/' . $this->client_folder_id . '/' . 'contacts/' . $contact_id, $contact, 'POST', 'contact' );
	}
}
