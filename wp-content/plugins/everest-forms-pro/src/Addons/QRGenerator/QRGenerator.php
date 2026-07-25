<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\Pro\Addons\Calculation
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\QRGenerator;

use EverestForms\Pro\Addons\QRGenerator\Builder\DisplayPagePublicLink;
use EverestForms\Pro\Addons\QRGenerator\Builder\Settings;

/**
 * Main plugin class.
 *
 * @since 1.7.9
 */
class QRGenerator {

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.7.9
	 */
	public function __construct() {
		/**
		 * Public Form Link.
		 */
		add_filter( 'template_include', array( $this, 'maybe_load_public_form' ) );

		$this->init();
	}

	/**
	 * Init function.
	 *
	 * @since 1.7.9
	 */
	public function init() {
		if ( is_admin() ) {
			new Settings();
		}
	}

	/**
	 * May be load public form.
	 *
	 * @since 1.7.9
	 *
	 * @param  object $template Template.
	 */
	function maybe_load_public_form( $template ) {
		if ( $public_link_key = sanitize_text_field( get_query_var( 'evf_public_link' ) ) ) {

			global $wpdb;

			$query   = "SELECT ID FROM $wpdb->posts WHERE post_name = %s LIMIT 1";
			$form_id = $wpdb->get_var( $wpdb->prepare( $query, $public_link_key ) );

			$page_template = get_page_template();

			if ( ! empty( $page_template ) ) {
				$template = $page_template;
			}
			new DisplayPagePublicLink( $form_id );
		}

		return $template;
	}
}
