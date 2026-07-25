<?php
/**
 * Process
 *
 * AJAX Event Handler
 *
 * @class    Process
 * @version  1.0.0
 * @package  EverestForms\WooCommerce\Process
 */

namespace EverestForms\Pro\Addons\WooCommerce\Ajax;

use EverestForms\Pro\Addons\WooCommerce\Admin\FieldListTable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax Class
 */
class Ajax {

	/**
	 * Hooks in ajax handlers
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax)
	 *
	 * @since 1.0.0
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'setting_form_field_listing' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_everest_forms_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_everest_forms_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Get form field table in Setting page.
	 *
	 * @since 1.0.0
	 */
	public static function setting_form_field_listing() {

		if ( ! check_ajax_referer( 'everest_forms_woocommerce_form_field_listing_nonce', 'security', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nonce error, please reload.', 'everest-forms-pro' ),
				)
			);
		}

		$form_id    = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : '';
		$option_key = isset( $_POST['option_key'] ) ? sanitize_text_field( $_POST['option_key'] ) : ''; // phpcs:ignore

		$data = array();

		if ( $form_id && $option_key ) {
			$woocommerce_field_table_list = new FieldListTable();
			$data['table']                = $woocommerce_field_table_list->display_table_list( $form_id, $option_key, true );

		} else {
			wp_send_json_success( array() );
		}

		wp_send_json_success( $data );
	}
}
