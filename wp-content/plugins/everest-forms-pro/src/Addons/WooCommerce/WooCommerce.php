<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\WooCommerce
 * @since   1.0.0
 */

namespace  EverestForms\Pro\Addons\WooCommerce;

  use EverestForms\Pro\Addons\WooCommerce\Frontend\Frontend;
  use EverestForms\Pro\Addons\WooCommerce\Admin\ProductPage;
  use EverestForms\Pro\Addons\WooCommerce\Ajax\Ajax;

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class WooCommerce {


	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function() {
					printf(
						'<div class="notice notice-warning is-dismissible"><p><strong>%s </strong>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
						esc_html( 'Everest Forms:' ),
						wp_kses_post( 'WooCommerce Integration addon requires WooCommerce to be installed and activated.', 'everest-forms' ),
						esc_html__( 'Dismiss this notice.', 'everest-forms' )
					);
				}
			);
			return;
		}

		// Enqueue Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

		new ProductPage();
		new Ajax();
		new Frontend();

	}

	/**
	 * Frontend Enqueue scripts.
	 *
	 * @param array $form_data Form Data.
	 */
	public function admin_enqueue_scripts( $form_data ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Enqueue Scripts.
			wp_register_script( 'everest-forms-woocommerce', plugins_url( "src/Addons/WooCommerce/assets/js/admin/evfwc-admin{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
			wp_enqueue_script( 'everest-forms-woocommerce' );
			wp_localize_script(
				'everest-forms-woocommerce',
				'everest_forms_woocommerce_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'everest_forms_woocommerce_form_field_listing' => wp_create_nonce( 'everest_forms_woocommerce_form_field_listing_nonce' ),
				)
			);
	}

	/**
	 * Register Admin Styles
	 */
	public function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_style( 'everest-forms-woocommerce-admin-style', plugins_url( 'src/Addons/WooCommerce/assets/css/evfwc-admin.scss', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
		if ( 'product' === $screen_id ) {
			wp_enqueue_style( 'everest-forms-woocommerce-admin-style' );
		}
	}
}
