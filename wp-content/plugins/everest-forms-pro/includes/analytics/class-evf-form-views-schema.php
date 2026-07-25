<?php
/**
 * Form Views Analytics Schema.
 *
 * Creates the wp_evf_form_views table used for impressions and bounce tracking.
 *
 * @package EverestForms_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Form_Views_Schema Class.
 */
class EVF_Form_Views_Schema {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->create_table();
	}

	/**
	 * Create the evf_form_views table if it does not exist.
	 */
	private function create_table() {
		global $wpdb;

		$wpdb->hide_errors();

		if ( ! function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		$schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}evf_form_views (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			form_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			referer TEXT NOT NULL,
			date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY date_created (date_created)
		) $charset_collate;";

		maybe_create_table( $wpdb->prefix . 'evf_form_views', $schema );
	}
}

new EVF_Form_Views_Schema();
