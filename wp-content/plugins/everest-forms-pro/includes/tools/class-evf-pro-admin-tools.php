<?php
/**
 * Add nav in Tools.
 *
 * @package EverestForms_Pro/Admin/Tools
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin_Pro_Tools Class.
 */
class EVF_Pro_Admin_Tools {
	/**
	 * Init.
	 *
	 * @since 1.7.7
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ), 11 );
	}

	/**
	 * Admin enqueue script.
	 *
	 * @since 1.7.6
	 */
	public static function admin_enqueue_scripts() {

		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore

		if ( 'evf-payment-log' === $current_page ) {
			wp_register_script( 'evf-payment-log', plugins_url( 'dist/payment_log.min.js', EFP_PLUGIN_FILE ), array( 'wp-element', 'react', 'react-dom', 'wp-api-fetch', 'wp-i18n', 'wp-blocks' ), EFP_VERSION, true );
			wp_enqueue_script( 'evf-payment-log' );
			wp_localize_script(
				'evf-payment-log',
				'evf_payment_log',
				array(
					'security'        => wp_create_nonce( 'wp_rest' ),
					'restURL'         => rest_url(),
					'adminURL'        => admin_url( 'admin.php' ),
					'not_found_image' => evf()->plugin_url() . '/assets/images/not-found-image.png',
				)
			);

			// Ensure cancelled statuses in React payment log table use the requested neutral tone.
			wp_add_inline_script(
				'evf-payment-log',
				"(function(){\n\tvar CANCEL_COLOR='#7e7d77';\n\tvar CANCEL_VALUES=['cancelled','canceled','cancled','cancel'];\n\tfunction applyCancelledStyles(){\n\t\tvar root=document.getElementById('everest-forms-payment-log');\n\t\tif(!root){return;}\n\t\tvar nodes=root.querySelectorAll('table tbody td span, table tbody td p');\n\t\tnodes.forEach(function(node){\n\t\t\tvar text=(node.textContent||'').trim().toLowerCase();\n\t\t\tif(CANCEL_VALUES.indexOf(text)===-1){return;}\n\t\t\tnode.style.color=CANCEL_COLOR;\n\t\t\tnode.style.border='1px solid '+CANCEL_COLOR;\n\t\t\tnode.style.borderRadius='999px';\n\t\t\tnode.style.padding='2px 10px';\n\t\t\tnode.style.display='inline-block';\n\t\t});\n\t}\n\tif(document.readyState==='loading'){\n\t\tdocument.addEventListener('DOMContentLoaded',applyCancelledStyles);\n\t}else{applyCancelledStyles();}\n\tvar observer=new MutationObserver(applyCancelledStyles);\n\tobserver.observe(document.body,{childList:true,subtree:true});\n})();",
				'after'
			);
			return;
		}

		if ( empty( $current_page ) || 'evf-tools' !== $current_page ) {
			return;
		}
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore
		if ( empty( $current_tab ) ) {
			return;
		}

		switch ( $current_tab ) {
			case 'api_logs':
				wp_register_script( 'evf-pro-api-logs', plugins_url( 'dist/api_logs.min.js', EFP_PLUGIN_FILE ), array( 'wp-element', 'react', 'react-dom', 'wp-api-fetch', 'wp-i18n', 'wp-blocks' ), EFP_VERSION, true );
				wp_register_style( 'evf-pro-api-logs-style', plugins_url( 'dist/api_logs.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION, 'all' );
				wp_enqueue_style( 'evf-pro-api-logs-style' );
				wp_enqueue_script( 'evf-pro-api-logs' );
				wp_localize_script(
					'evf-pro-api-logs',
					'evfp_api_logs_script',
					array(
						'apiLogRestNonce' => wp_create_nonce( 'wp_rest' ),
						'restURL'         => rest_url(),
						'adminURL'        => esc_url( admin_url() ),
						'siteURL'         => esc_url( home_url( '/' ) ),
						'builderURL'      => esc_url( admin_url( 'admin.php?page=evf-builder&tab=fields' ) ),
						'viewEntryURL'    => esc_url( admin_url( 'admin.php?page=evf-entries' ) ),
						'statusCodes'     => self::get_status(),
						'not_found_image' => evf()->plugin_url() . '/assets/images/not-found-image.png',
					)
				);
				break;
		}
	}
	/**
	 * Get the status of log.
	 * Filter for Http response code.
	 *
	 * @since 1.7.8
	 */
	public static function get_status() {
		$http_statuses = apply_filters(
			'evf_http_response_code',
			array(
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				204 => 'No Content',
				301 => 'Moved Permanently',
				302 => 'Found',
				304 => 'Not Modified',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				409 => 'Conflict',
				410 => 'Gone',
				500 => 'Internal Server Error',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
			)
		);

		return $http_statuses;
	}
	/**
	 * Payment log.
	 *
	 * @since 1.7.6
	 */
	public static function payment_log() {
		echo '<div id="everest-forms-payment-log"></div>';
	}
	/**
	 * Api logs.
	 *
	 * @since 1.7.8
	 */
	public static function api_logs() {
		echo '<div id="evf-pro-api-logs"></div>';
	}
}

EVF_Pro_Admin_Tools::init();
