<?php
/**
 * Api Logs.
 *
 * @package EverestForms_Pro/Admin/Tools/Api-logs/BackTrace
 * @version 1.7.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Pro_Api_Logs_Schema Class.
 */
class EVF_Pro_Api_Logs_Schema {
	/**
	 * Init.
	 *
	 * @since 1.7.8
	 */
	public function __construct() {
		$this->setup();
	}
	/**
	 * Setup.
	 *
	 * @since 1.7.8
	 */
	private function setup() {
		$this->create_table();
	}

	/**
	 * Create table.
	 *
	 * @since 1.7.8
	 */
	private function create_table() {
		global $wpdb;

		$wpdb->hide_errors();

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$collate = '';

		 $this->get_schema();
	}

	/**
	 * Schema list.
	 *
	 * @since 1.7.8
	 */
	private function get_schema() {
		global $wpdb;

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		$schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}evfp_api_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL,
			entry_id BIGINT UNSIGNED NOT NULL,
			source VARCHAR(100) NOT NULL,
			request LONGTEXT NULL,
			response LONGTEXT NULL,
			log LONGTEXT NULL,
			status VARCHAR(20) NOT NULL,
			created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY entry_id (entry_id),
			KEY status (status)
		) $charset_collate;";
		maybe_create_table( $wpdb->prefix . 'evfp_api_logs', $schema );
	}
}
new EVF_Pro_Api_Logs_Schema();
