<?php
/**
 * Everest Forms Mollie Settings.
 *
 * @package EverestForms\pro\Mollie\Settings
 *
 * @since 1.7.7
 */

namespace EverestForms\Pro\Addons\Mollie\Settings;

use EverestForms\Pro\Addons\Mollie\builder\MollieField as BuilderMollieField;

defined( 'ABSPATH' ) || exit;

/**
 * Mollie Settings.
 *
 * @since 1.7.7
 */
class Settings {

	/**
	 * Settings Constructor
	 *
	 * @since 1.7.7
	 */
	public function __construct() {
		add_filter( 'everest_forms_payment_settings', array( $this, 'add_mollie_settings' ) );

		if ( defined( 'EFP_VERSION' ) && version_compare( EFP_VERSION, '1.7.5', '>=' ) ) {
			// Enqueue Scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}
	}

	/**
	 * Add stripe field.
	 *
	 * @param array $settings Setting Field Data.
	 */
	public function add_mollie_settings( $settings ) {
		$mollie_accordion = array(
			array(
				'type'  => 'accordion',
				'items' => array(
					array(
						'title'            => __( 'Mollie Settings', 'everest-forms-pro' ),
						'icon'             => plugins_url( 'src/Addons/Mollie/assets/img/mollie.png', EFP_PLUGIN_FILE ),
						'is_open'          => false,
						'connection_check' => array(
							'mode'   => 'any_group',
							'groups' => array(
								'live' => array( 'everest_forms_mollie_live_api_key' ),
								'test' => array( 'everest_forms_mollie_test_api_key' ),
							),
						),
						'fields'           => array(
							array(
								'title'    => __( 'Enable Test Mode', 'everest-forms-pro' ),
								'desc'     => __( 'Toggle this option to enable disable test mode for Mollie transactions. Note: Test mode should only be used for testing purposes and not for live transactions.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_mollie_test_mode',
								'default'  => '',
								'type'     => 'toggle',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Test mode API key', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your Mollie test mode API key.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_mollie_test_api_key',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Live mode API key', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your Mollie live mode API key.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_mollie_live_api_key',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
						),
					),
				),
			),
		);

		return array_merge( $settings, $mollie_accordion );
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin scripts.
		wp_register_script( 'everest-forms-mollie-settings', plugins_url( "src/Addons/Mollie/assets/js/admin/admin{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION, true );

		// Admin scripts for EVF settings page.
		if ( 'everest-forms_page_evf-settings' === $screen_id || 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-mollie-settings' );
		}
	}
}
