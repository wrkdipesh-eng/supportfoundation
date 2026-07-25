<?php
/**
 * Active Campaign tag api.
 */
namespace EverestForms\Pro\Addons\ActiveCampaign\API;

class EVF_ActiveCampaign_Tag_Api extends EVF_Abstract_ActiveCampaign_Api
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
	 * Create a tag.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tag tag
	 *
	 * @return WP_Error|array
	 */
	public function create( $tag ) {
		$data['tag'] = $tag;

		$response = $this->request( 'POST', 'tags', array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tag = isset( $response['tag'] ) ? $response['tag'] : array();
		return $tag;
	}

	/**
	 * Retrieve a tag.
	 *
	 * @since 1.0.0
	 *
	 * @param iny $id ID of the tag.
	 *
	 * @return WP_Error|array
	 */
	public function retrieve( $id ) {
		$response = $this->request( 'GET', "tags/$id" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tag = isset( $response['tag'] ) ? $response['tag'] : array();
		return $tag;
	}

	/**
	 * Update a tag.
	 *
	 * #since 1.0.0
	 *
	 * @param int   $id   ID of the tag.
	 * @param array $tag tag details.
	 *
	 * @return WP_Error|array
	 */
	public function update( $id, $tag ) {
		$data['tag'] = $tag;

		$response = $this->request( 'PUT', "tags/{$id}", array(), $data, true );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tag = isset( $response['tag'] ) ? $response['tag'] : array();
		return $tag;
	}

	/**
	 * Delete a tag.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the tag.
	 *
	 * @return WP_Error|bool
	 */
	public function delete( $id ) {
		$response = $this->request( 'DELETE', "tags/{$id}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * List all tags.
	 *
	 * View many (or all) tags by including their ID's or various filters.
	 * This is useful for searching for tags that match certain
	 * criteria - such as being part of a certain list, or having a specific custom field value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Filter, limit and offset arguments.
	 *
	 * @return WP_Error|array
	 */
	public function list( $args = array() ) {
		foreach ( $args as $key => $value ) {
			$new_args[] = $key . '=' . urlencode( $value );
		}
		$params   = implode( '&', $new_args );
		$response = $this->request( 'GET', "tags?{$params}" );

		// Bail early if the response is error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$tags = isset( $response['tags'] ) ? $response['tags'] : array();
		return $tags;
	}
}
