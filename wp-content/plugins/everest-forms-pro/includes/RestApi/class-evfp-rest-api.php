<?php
/**
 * EverestForms EVFP_REST_API
 *
 * API Handler
 *
 * @class    EVFP_REST_API
 * @version  1.7.6
 * @package  EverestForms/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EVFP_REST_API Class
 */
class EVFP_REST_API {

	/**
	 * REST API classes and endpoints.
	 *
	 * @since 1.7.6
	 *
	 * @var array
	 */
	protected static $rest_classes = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 *
	 * @since 1.7.6
	 */
	public static function init() {

		include __DIR__ . '/Controller/V1/class-evfp-payment-log.php';
		include __DIR__ . '/Controller/V1/class-evfp-api-logs.php';
		include __DIR__ . '/Controller/V1/class-evfp-analytics.php';
		include __DIR__ . '/Controller/V1/class-evfp-user-payment-entry.php';

		// Models.
		include __DIR__ . '/Model/V1/class-evfp-api-logs-model.php';

		// Form views tracking table + frontend hooks.
		include dirname( __DIR__ ) . '/analytics/class-evf-form-views-schema.php';
		add_action( 'everest_forms_frontend_output', array( 'EVFP_Analytics_REST', 'collect_form_for_tracking' ), 1, 1 );
		add_action( 'wp_footer', array( 'EVFP_Analytics_REST', 'output_tracking_script' ) );

		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.7.6
	 */
	public static function register_rest_routes() {
		foreach ( self::get_rest_classes() as $rest_namespace => $classes ) {
			foreach ( $classes as $class_name ) {
				self::$rest_classes[ $rest_namespace ][ $class_name ] = new $class_name();
				self::$rest_classes[ $rest_namespace ][ $class_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API Classes - new classes should be registered here.
	 *
	 * @since 3.1.6
	 *
	 * @return array List of Classes.
	 */
	protected static function get_rest_classes() {
		/**
		 * Filters rest API controller classes.
		 *
		 * @since 1.7.6
		 *
		 * @param array $rest_routes API namespace to API classes index array.
		 */
		return apply_filters(
			'everest_forms_pro_rest_api_get_rest_namespaces',
			array(
				'everest-forms-pro/v1' => self::get_v1_rest_classes(),
			)
		);
	}

	/**
	 * List of classes in the user-registration/v1 namespace.
	 *
	 * @since 1.7.6
	 * @static
	 *
	 * @return array
	 */
	protected static function get_v1_rest_classes() {
		return array(
			'payment_log'        => 'EVFP_Payment_Log',
			'api_logs'           => 'EVFP_Api_Logs',
			'analytics'          => 'EVFP_Analytics_REST',
			'user_payment_entry' => 'EVFP_User_Payment_Entry',
		);
	}
}

EVFP_REST_API::init();
