<?php
/**
 * SmsNotification Process.
 *
 * @package EverestForms\Pro\Addons\SmsNotification\Process
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\SmsNotification\Process;

use EverestForms\Pro\Addons\SmsNotification\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * Process Class.
 *
 * @since 1.0.0
 */
class Process {

	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_action( 'everest_forms_process_complete', array( $this, 'evf_send_message_to_user' ), 10, 4 );
	}

		/**
		 * Send Message to User.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields    Fields for the Form.
		 * @param array $entry     Form Entry.
		 * @param array $form_data Form Data object.
		 * @param int   $entry_id  Entry Identifier.
		 */
	public function evf_send_message_to_user( $fields, $entry, $form_data, $entry_id ) {

		$notifications     = isset( $form_data['settings']['sms_notifications'] ) ? $form_data['settings']['sms_notifications'] : array();
		$notification_type = isset( $form_data['settings']['sms_notifications']['sms_service_type'] ) ? $form_data['settings']['sms_notifications']['sms_service_type'] : array();

		foreach ( $notifications as $connection_id => $notification ) :
			// Don't proceed if sms notification is not enabled.
			if ( isset( $notification['enable_sms_notifications'] ) && '1' !== $notification['enable_sms_notifications'] ) {
				continue;
			}

			$process_sms = apply_filters( 'everest_forms_sms_notifications_process', true, $fields, $form_data, 'sms_notifications', $connection_id );

			if ( ! $process_sms ) {
				continue;
			}

			$sms                        = array();
			$phone_no                   = isset( $notification['sms_notifications_user_phone_no'] ) ? $notification['sms_notifications_user_phone_no'] : '';
			$sms['number']              = explode( ',', apply_filters( 'everest_forms_process_smart_tags', $phone_no, $form_data, $fields ) );
			$sms['message']             = ! empty( $notification['sms_notifications_message'] ) ? apply_filters( 'everest_forms_process_smart_tags', $notification['sms_notifications_message'], $form_data, $fields ) : esc_html__( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms-pro' );
			$clicksend['campaign_name'] = ! empty( $notification['clicksend_campaign_name'] ) ? apply_filters( 'everest_forms_process_smart_tags', $notification['clicksend_campaign_name'], $form_data, $fields ) : esc_html__( 'Everest Forms ClickSend SMS Campaign', 'everest-forms-pro' );

			if ( 'twilio' === $notification_type ) {
				$providers      = get_option( 'everest_forms_sms_notifications_settings', array() );
				$account_number = ! empty( $providers['client_number'] ) ? $providers['client_number'] : '';
				$account_sid    = ! empty( $providers['client_id'] ) ? $providers['client_id'] : '';
				$account_token  = ! empty( $providers['client_auth'] ) ? $providers['client_auth'] : '';
			} else {
				$providers      = get_option( 'everest_forms_click_send_settings', array() );
				$account_number = ! empty( $providers['client_phone_number'] ) ? $providers['client_phone_number'] : '';
				$account_sid    = ! empty( $providers['client_username'] ) ? $providers['client_username'] : '';
				$account_token  = ! empty( $providers['clicksend_auth'] ) ? $providers['clicksend_auth'] : '';
			}

			if ( empty( $account_number ) || empty( $account_sid ) || empty( $account_token ) ) {
				return;
			}

			try {
				$api = new API( $account_sid, $account_token );
				foreach ( $sms['number'] as $number ) {
					$sms_numbers = array_filter( explode( '+', $number ) );

					foreach ( $sms_numbers as $sms_number ) {

						switch ( $notification_type ) {
							case 'twilio':
								$request_body = array(
									'To'   => '+' . trim( $sms_number ),
									'From' => $account_number,
									'Body' => $sms['message'],
								);
								$message      = $api->send_message( $request_body, $form_data['id'], $entry_id );
								break;

							case 'clicksend':
								$clicksend_service_type = isset( $form_data['settings']['sms_notifications']['clicksend_service_type'] ) ? $form_data['settings']['sms_notifications']['clicksend_service_type'] : 'single_sms';

								if ( 'single_sms' === $clicksend_service_type ) {
									$request_body = array(
										'messages' => array(
											array(
												'source' => 'php',
												'form'   => $account_number,
												'body'   => $sms['message'],
												'to'     => '+' . trim( $sms_number ),
											),
										),
									);

									$message = $api->send_clicksend_notification( $request_body, $form_data['id'], $entry_id );
								} else {
									$list_id      = isset( $form_data['settings']['sms_notifications']['clicksend_contact_list'] ) ? $form_data['settings']['sms_notifications']['clicksend_contact_list'] : '';
									$request_body = array(
										'list_id' => $list_id,
										'name'    => $clicksend['campaign_name'],
										'from'    => $account_number,
										'body'    => $sms['message'],
									);
									$message      = $api->send_clicksend_sms_campaign_notification( $request_body, $form_data['id'], $entry_id );
								}
								break;

							default:
								$message = $api->send_message( $request_body );
						}
					}
				}
			} catch ( \Exception $e ) {
				evf_get_logger()->critical(
					$e->getMessage(),
					array( 'source' => 'sms-notifications' )
				);
			}
			endforeach;
	}
}
