<?php
/**
 * Handles the admin settings and functionality for the Everest Forms MailPoet add-on.
 *
 * @package EverestForms\MailPoet\Admin
 *
 * @since 1.0.0
 */

namespace EverestForms\Pro\Addons\MailPoet\Admin;

use EverestForms\Pro\Addons\MailPoet\Admin\Integration\MailPoetIntegration;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Admin' ) ) :
	/**
	 * Class Admin
	 *
	 * @since 1.0.0
	 */
	class Admin {

		/**
		 * Admin constructor.
		 * Initializes hooks for admin functionality.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->init_hooks();
		}

		/**
		 * Initialize hooks.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function init_hooks() {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
			add_action( 'wp_ajax_everest_forms_mailpoet_connection_action', array( $this, 'mail_poet_connection' ) );
		}

		/**
		 * Load scripts.
		 *
		 * @since 1.0.0
		 */
		public function load_admin_scripts() {
			$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';
			// Enqueue Scripts.
			wp_register_script( 'everest-forms-admin-mailpoet-script', plugins_url( "src/Addons/MailPoet/assets/js/admin/admin{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
			if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
				wp_enqueue_script( 'everest-forms-admin-mailpoet-script' );
				wp_localize_script(
					'everest-forms-admin-mailpoet-script',
					'evf_admin_mailpoet',
					array(
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
						'mailpoet_connection_nonce' => wp_create_nonce( 'mailpoet_connection_nonce' ),
					)
				);
			}
		}

		/**
		 * Save the mailpoet connections.
		 *
		 * @since 1.0.0
		 */
		public function mail_poet_connection() {
			try {
				check_ajax_referer( 'mailpoet_connection_nonce', 'security' );
				$option_value = isset( $_POST['option_value'] ) ? $_POST['option_value'] : null;
				if ( is_null( $option_value ) ) {
					wp_send_json_error(
						array(
							'message' => esc_html__( 'Value is null', 'everest-forms-pro' ),
						)
					);
				}
				$mailpoet_instance = new MailPoetIntegration();
				update_option( 'everest_forms_integrations_' . $mailpoet_instance->id, $option_value );
				wp_send_json_success(
					array(
						'message' => esc_html__( 'Saved !!', 'everest-forms-pro' ),
						'status'  => evf_string_to_bool( $option_value ) ? esc_html__( 'Connected' ) : '',
					)
				);

			} catch ( \Exception $e ) {
				wp_send_json_error(
					array(
						'message' => $e->getMessage(),
					)
				);
			}
		}
	}
endif;
