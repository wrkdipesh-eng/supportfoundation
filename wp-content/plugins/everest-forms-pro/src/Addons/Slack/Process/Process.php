<?php
/**
 * Slack Process.
 *
 * @package EverestForms\Pro\Slack\Process
 *
 * @since 3.0.5
 */

namespace EverestForms\Pro\Addons\Slack\Process;

use EverestForms\Pro\Addons\Slack\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Slack Process class.
 *
 * @since 3.0.5
 */
class Process {
	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_action( 'everest_forms_process_complete', array( $this, 'everest_forms_send_slack_message' ), 10, 4 );
	}

	/**
	 * Send Message to slack.
	 *
	 * @since 3.0.5
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function everest_forms_send_slack_message( $fields, $entry, $form_data, $entry_id ) {
		$slack = isset( $form_data['settings']['slack'] ) ? $form_data['settings']['slack'] : array();

		foreach ( $slack as $connection_id => $slack ) :
			// Don't proceed if slack is not enabled.
			if ( isset( $slack['enable_slack'] ) && '1' !== $slack['enable_slack'] ) {
				continue;
			}

			$process_slack = apply_filters( 'everest_forms_slack_process', true, $fields, $form_data, 'slack', $connection_id );

			if ( ! $process_slack ) {
				continue;
			}

			$form_id          = isset( $form_data['id'] ) ? $form_data['id'] : '';
			$entry_url        = admin_url( 'admin.php?page=evf-entries&form_id=' . $form_id . '&view-entry=' . $entry_id );
			$message_title    = isset( $form_data['settings']['connection_name'] ) ? $form_data['settings']['connection_name'] : 'Form Entry Details';
			$message          = ! empty( $slack['slack_message'] ) ? apply_filters( 'everest_forms_process_smart_tags', $slack['slack_message'], $form_data, $fields, $entry_id ) : __( 'View entry details: ', 'everest-forms-pro' ) . $entry_url;
			$incoming_webhook = isset( $slack['slack_incoming_webhook'] ) ? $slack['slack_incoming_webhook'] : '';

			if ( empty( $incoming_webhook ) ) {
				return;
			}

			$message_body = array(
				'payload' => json_encode(
					array(
						'attachments' => array(
							array(
								'color'      => '#7457BB',
								'fallback'   => $message_title,
								'title'      => $message_title,
								'fields'     => array(
									array(
										'title' => 'Message',
										'value' => $message,
										'short' => false,
									),
								),
								'ts'         => round( microtime( true ) * 1000 ),
								'title_link' => $entry_url,
							),
						),
					)
				),
			);
			try {
				$api          = new API( $incoming_webhook );
				$send_message = $api->send_message( $message_body, 'POST', $form_id, $entry_id );
			} catch ( \Exception $e ) {
				evf_get_logger()->critical(
					$e->getMessage(),
					array( 'source' => 'slack' )
				);
			}
			endforeach;
	}
}
