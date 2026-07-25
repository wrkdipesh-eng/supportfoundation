<?php
/**
 * Active Campaign list api.
 */

namespace EverestForms\Pro\Addons\ActiveCampaign\API;

class EVF_ActiveCampaign_List_Api extends EVF_Abstract_ActiveCampaign_Api
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
	 * Create a list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $list list
	 *
	 * @return WP_Error|array
	 */
	public function create( $list ) {
		$data['list'] = $list;

		$response = $this->request( 'POST', 'lists', array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$list = isset( $response['list'] ) ? $response['list'] : array();
		return $list;
	}

	/**
	 * Retrieve a list.
	 *
	 * @since 1.0.0
	 *
	 * @param iny $id ID of the list.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id )  {
		$response = $this->request( 'GET', "lists/$id" );

		if( is_wp_error( $response) ) {
			return $response;
		}

		$list = isset( $response['list'] ) ? $response['list'] : array();
		return $list;
	}

	/**
	 * Update a list.
	 *
	 * #since 1.0.0
	 *
	 * @param int $id	ID of the list.
	 * @param array $list list details.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $list ) {
		return new \WP_Error( 'evf_active_campaign_api_not_implemented', esc_html__( 'The API is not implemented by Active Campaign' ) );

	}

	/**
	 * Delete a list.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the list.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "lists/{$id}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all lists.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		$params = urlencode( implode( '&', $args ) );

		return $this->request( 'GET', "lists?{$params}" );
	}

	/**
	 * Create a list group permission.
	 *
	 * @since 1.0.0
	 *
	 * @param array $list_group List group.
	 *
	 * @return WP_Error|array
	 */
	public function create_group_permission( $list_group ) {
		$data['listGroup'] = $list_group;
		$response = $this->request( 'POST', 'listGroups', array(), $data, true );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$list_group = isset( $response['listGroup'] ) ? $response['listGroup'] : array();
		return $list_group;
	}
}
