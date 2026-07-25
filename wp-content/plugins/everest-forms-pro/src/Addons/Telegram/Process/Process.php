<?php
/**
 * Telegram Process.
 *
 * @package EverestForms\Pro\Telegram\Process
 *
 * @since 1.7.7
 */

namespace EverestForms\Pro\Addons\Telegram\Process;

use EverestForms\Pro\Addons\Telegram\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Everest Forms Telegram Process class.
 *
 * @since 1.7.7
 */
class Process {
	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_action( 'everest_forms_process_complete', array( $this, 'everest_forms_send_telegram_message' ), 10, 4 );
	}

	/**
	 * Send Message to telegram.
	 *
	 * @since 1.7.7
	 *
	 * @param array $fields    Fields for the Form.
	 * @param array $entry     Form Entry.
	 * @param array $form_data Form Data object.
	 * @param int   $entry_id  Entry Identifier.
	 */
	public function everest_forms_send_telegram_message( $fields, $entry, $form_data, $entry_id ) {

		$telegram = isset( $form_data['settings']['telegram'] ) ? $form_data['settings']['telegram'] : array();
		foreach ( $telegram as $connection_id => $telegram ) :
			// Don't proceed if sms telegram is not enabled.
			if ( isset( $telegram['enable_sms_notifications'] ) && '1' !== $telegram['enable_sms_notifications'] ) {
				continue;
			}

			$process_telegram = apply_filters( 'everest_forms_telegram_process', true, $fields, $form_data, 'telegram', $connection_id );

			if ( ! $process_telegram ) {
				continue;
			}

			$chat_id      = isset( $form_data['settings']['telegram_channel_id'] ) ? $form_data['settings']['telegram_channel_id'] : '';
			$message      = ! empty( $telegram['telegram_message'] ) ? apply_filters( 'everest_forms_process_smart_tags', $telegram['telegram_message'], $form_data, $fields, $entry_id ) : 'Thanks for contacting us! We will be in touch with you shortly';
			$chat_id      = isset( $telegram['telegram_channel_id'] ) ? $telegram['telegram_channel_id'] : '';
			$integrations = get_option( 'everest_forms_integrations', '' );
			$telegram_api = isset( $integrations['telegram'] ) ? wp_list_pluck( $integrations['telegram'], 'api' ) : '';
			if ( empty( $telegram_api ) || empty( $chat_id ) ) {
				return;
			}
			try {
				$api          = new API( $telegram_api );
				$send_message = $api->send_message( $message, 'HTML', $chat_id );
			} catch ( \Exception $e ) {
				evf_get_logger()->critical(
					$e->getMessage(),
					array( 'source' => 'telegram' )
				);
			}
			endforeach;
	}
}
