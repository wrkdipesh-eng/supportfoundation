<?php
/**
 * Api Logs Controller.
 *
 * @since 1.7.8
 *
 * @package  EverestFormsPro/RestApi/ApiLogs
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVFP_Api_Logs Class.
 */
class EVFP_Api_Logs {

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string The namespace of this controller's route.
	 */
	protected $namespace = 'evfp/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string The base of this controller's route.
	 */
	protected $rest_base = 'api-logs';

	/**
	 * Register routes.
	 *
	 * @since 1.7.6
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'get_api_logs' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get/content',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'get_api_log_content' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/delete/logs',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'delete_api_logs' ),
					'permission_callback' => array( __CLASS__, 'check_admin_permissions' ),
				),
			)
		);

	}
	/**
	 * Delete the api logs.
	 *
	 * @param \WP_Rest_Request $request The request ids list.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function delete_api_logs( $request ) {
		$params = $request->get_json_params();

		$model = new EVFP_Api_Logs_Model();
		foreach ( $params['ids'] as $id ) {
			$res = $model->remove_log( $id );
		}

		return new \WP_REST_Response(
			$res,
			200
		);

	}
	/**
	 * Get api log content.
	 *
	 * @param \WP_Rest_Request $request Full detail about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function get_api_log_content( $request ) {
		$params = $request->get_json_params();

		$model = new EVFP_Api_Logs_Model();

		$res = $model->get_api_logs( $params );
		return new \WP_REST_Response(
			$res,
			200
		);
	}

	/**
	 * Get api logs
	 *
	 * @param \WP_Rest_Request $request Full detail about the request.
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public static function get_api_logs( $request ) {
		$params = $request->get_json_params();

		$model = new EVFP_Api_Logs_Model();

		$res = $model->get_api_logs( $params );
		return new \WP_REST_Response(
			$res,
			200
		);
	}

	/**
	 * Check if a given request has access to update a setting
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public static function check_admin_permissions( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		// Nonce check.
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You do not have permissions to perform this action.', 'everest-forms-pro' ),
				array( 'status' => 403 )
			);
		}
		// Capability check.
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_everest_forms' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				esc_html__( 'You are not allowed to access this resource.', 'everest-forms-pro' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
