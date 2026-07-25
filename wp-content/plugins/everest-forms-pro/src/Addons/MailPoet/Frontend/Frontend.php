<?php
/**
 * Handles the frontend functionality for Everest Forms MailPoet.
 *
 * @package  EverestForms\MailPoet\Frontend
 *
 * @since 1.0.0
 */

namespace EverestForms\Pro\Addons\MailPoet\Frontend;

use EverestForms\Pro\Addons\MailPoet\Admin\Integration\MailPoetIntegration;


defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Frontend' ) ) :
	/**
	 *  Class Frontend.
	 *
	 * @since 1.0.0
	 */
	class Frontend {
		public function __construct() {
			add_action( 'everest_forms_process_complete', array( $this, 'process_feed' ), 5, 4 );
		}

		/**
		 * Process data and submit entry to Integration.
		 *
		 * @param array $fields    Fields for the Form.
		 * @param array $entry     Form Entry.
		 * @param array $form_data Form Data object.
		 * @param int   $entry_id  Entry Identifier.
		 */
		public function process_feed( $fields, $entry, $form_data, $entry_id ) {

			$settings = isset( $form_data['settings'] ) ? $form_data['settings'] : array();
			if ( ! isset( $settings['enable_mailpoet'] ) ) {
				return;
			}

			$mailpoet_instance   = new MailPoetIntegration();
			$mailpoet_connection = get_option( 'everest_forms_integrations_' . $mailpoet_instance->id, false );
			if ( ! evf_string_to_bool( $settings['enable_mailpoet'] ) || ! evf_string_to_bool( $mailpoet_connection ) || ! $mailpoet_instance->is_configured() ) {
				return;
			}

			$mailpoet_settings = isset( $settings['mailpoet'] ) ? $settings['mailpoet'] : array();
			if ( empty( $mailpoet_settings ) ) {
				return;
			}
			$list_id            = isset( $mailpoet_settings['list_id'] ) ? $mailpoet_settings['list_id'] : '';
			$email              = isset( $mailpoet_settings['email_field'] ) ? $mailpoet_settings['email_field'] : '';
			$first_name         = isset( $mailpoet_settings['first_name_field'] ) ? $mailpoet_settings['first_name_field'] : '';
			$last_name          = isset( $mailpoet_settings['last_name_field'] ) ? $mailpoet_settings['last_name_field'] : '';
			$confirmation_email = isset( $mailpoet_settings['confirmation_email'] ) ? $mailpoet_settings['confirmation_email'] : false;
			if ( '' === $list_id || '' === $email ) {
				return;
			}

			$contact = array();
			foreach ( $fields as $e ) {
				if ( $email === $e['id'] ) {
					$contact['email'] = $e['value'];
				}
				if ( $first_name === $e['id'] ) {
					$contact['first_name'] = $e['value'];
				}
				if ( $last_name === $e['id'] ) {
					$contact['last_name'] = $e['value'];
				}
				$custom_fields = isset( $mailpoet_settings['custom_fields'] ) ? $mailpoet_settings['custom_fields'] : array();
				if ( ! empty( $custom_fields ) ) {
					if ( in_array( $e['id'], $custom_fields, true ) ) {
						$contact[ $e['meta_key'] ] = $e['value'];
					}
				}
			}
			if ( empty( $contact ) ) {
				return;
			}
			$logger = new \EVF_Logger();

			try {
				if ( ! class_exists( \MailPoet\API\API::class ) ) {
					return false;
				}
				$subscriber = \MailPoet\API\API::MP( 'v1' )->getSubscriber( $contact['email'] );
				if ( $subscriber ) {
					return;
				}
			} catch ( \Exception $exception ) {
				$logger->log( 'info', esc_html__( 'The subscriber already exists into the list!!', 'everest-forms-pro' ) );
			}

			try {
				$options = array(
					'skip_subscriber_notification' => true,
					'send_confirmation_email'      => $confirmation_email,
				);

				$subscriber = \MailPoet\API\API::MP( 'v1' )->addSubscriber(
					$contact,
					array(
						$list_id,
					),
					$options
				);

			} catch ( \Exception $exception ) {
				$logger->log( 'info', esc_html__( 'Failed to add the subscriber!!', 'everest-forms-pro' ) );
			}
		}
	}
endif;
