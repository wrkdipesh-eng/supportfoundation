<?php

/**
 * Square Global Payment Settings.
 *
 * @package EverestForms\Square\Builder
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Square\Settings;

use EverestForms\Pro\Addons\Square\Builder\SquareFields;

class Settings {

	/**
	 * ID.
	 *
	 * @since 1.7.5
	 */
	public $id = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( 'everest_forms_payment_settings', array( $this, 'add_square_settings' ) );
	}


	/**
	 * Square global payment settings.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $settings Settings.
	 */
	public function add_square_settings( $settings ) {
		$square_accordion = array(
			array(
				'type'  => 'accordion',
				'items' => array(
					array(
						'title'            => __( 'Square Settings', 'everest-forms-pro' ),
						'icon'             => plugins_url( 'src/Addons/Square/assets/img/Square.png', EFP_PLUGIN_FILE ),
						'is_open'          => false,
						'connection_check' => array(
							'mode'   => 'any_group',
							'groups' => array(
								'live' => array(
									'everest_forms_square_live_app_id',
									'everest_forms_square_live_access_token',
									'everest_forms_square_live_location_id',
								),
								'test' => array(
									'everest_forms_square_test_app_id',
									'everest_forms_square_test_access_token',
									'everest_forms_square_test_location_id',
								),
							),
						),
						'fields'           => array(
							array(
								'title'    => __( 'Enable Test Mode', 'everest-forms-pro' ),
								'desc'     => __( 'Enable this option if you want to use test mode for Square transactions. Note: Test mode should only be used for testing purposes and not for live transactions.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_pro_square_test_mode',
								'default'  => '',
								'type'     => 'toggle',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Test Application ID', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your Square Application ID.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_square_test_app_id',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Test Access Token', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your access token.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_square_test_access_token',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Test Location ID', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your test location id.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_square_test_location_id',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Live Application ID', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your Square live Application ID.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_square_live_app_id',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Live Access Token', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your live access token.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_square_live_access_token',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
							array(
								'title'    => __( 'Live Location ID', 'everest-forms-pro' ),
								'desc'     => __( 'Enter your live location id.', 'everest-forms-pro' ),
								'id'       => 'everest_forms_square_live_location_id',
								'default'  => '',
								'type'     => 'text',
								'desc_tip' => true,
							),
						),
					),
				),
			),
		);

		return array_merge( $settings, $square_accordion );
	}
}
