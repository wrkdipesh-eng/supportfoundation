<?php
/**
 * TWilio Settings.
 *
 * @package EverestForms\Pro\Addons\SmsNotification\Builder
 * @since   1.7.9
 */

namespace EverestForms\Pro\Addons\SmsNotification\Builder;

use EverestForms\Pro\Addons\SmsNotification\API\API;

defined( 'ABSPATH' ) || exit;

/**
 * SMSNotification Setting Class.
 *
 * @since 1.7.9
 */
class Settings {

	/**
	 * Primary class constructor.
	 */
	public function __construct() {
		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'output_sms_notifications_settings' ) );
		add_action( 'everest_forms_settings_connections_sms-notifications', array( $this, 'output_connections_list' ) );
	}

	/**
	 * Register settings section.
	 *
	 * @param  array $sections Settings section.
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$new_sections = array(
			'sms-notifications' => esc_html__( 'SMS Notifications', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}

		/**
		 * Get form data
		 *
		 * @return array form data.
		 */
	private function form_data() {
		$form_data = array();

		if ( ! empty( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_data = evf()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return $form_data;
	}

	/**
	 * Outputs the connection lists on sidebar.
	 */
	public function output_connections_list() {
		$form_data         = $this->form_data();
		$sms_notifications = isset( $form_data['settings']['sms_notifications'] ) ? $form_data['settings']['sms_notifications'] : array();

		if ( empty( $sms_notifications ) ) {
			$sms_notifications['sms_notification_connection_1'] = array( 'connection_name' => __( 'Admin Notification', 'everest-forms-pro' ) );
		}

		?>
			<div class="everest-forms-active-sms-notifications">
				<button class="everest-forms-btn everest-forms-btn-primary everest-forms-sms-notifications-add" data-form_id="<?php echo isset( $_GET['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification ?>" data-source="sms-notifications" data-type="<?php echo esc_attr( 'connection' ); ?>">
					<?php printf( esc_html__( 'Add New SMS Notifications', 'everest-forms-pro' ) ); ?>
				</button>
					<ul class="everest-forms-active-sms-notifications-connections-list">
					<?php if ( ! empty( $sms_notifications ) ) { ?>
						<h4><?php echo esc_html__( 'SMS Notifications', 'everest-forms-pro' ); ?> </h4>
						<?php
					}
					if ( ! empty( $sms_notifications ) ) {
						foreach ( $sms_notifications as $connection_id => $connection_data ) {
							if ( preg_match( '/sms_notification_connection_/', $connection_id ) ) {
								$connection_name = ! empty( $connection_data['connection_name'] ) ? $connection_data['connection_name'] : '';
								if ( 'sms_notification_connection_1' !== $connection_id ) {
									$remove_class = 'sms-notifications-remove';
								} else {
									$remove_class = 'sms-notifications-default-remove';
								}
								?>
									<li class="connection-list" data-connection-id="<?php echo esc_attr( $connection_id ); ?>">
										<a class="user-nickname" href="#"><?php echo esc_html( $connection_name ); ?></a>
										<a href="#"><span class="<?php echo esc_attr( $remove_class ); ?>"><?php esc_html_e( 'Remove', 'everest-forms-pro' ); ?></a>
									</li>
								<?php
							}
						}
					}
					?>
					</ul>
			</div>
			<?php
	}


	/**
	 * Output User Registration settings.
	 *
	 * @param object $object Form settings object.
	 */
	public function output_sms_notifications_settings( $object ) {
		$settings                = isset( $object->form_data['settings'] ) ? $object->form_data['settings'] : array();
		$integrations            = evf()->integrations->integrations;
		$click_send_integrations = isset( $integrations['click_send'] ) ? $integrations['click_send'] : '';
		$click_send_settings     = $click_send_integrations->settings;
		$click_send_username     = isset( $click_send_settings['client_username'] ) ? esc_html( $click_send_settings['client_username'] ) : '';
		$clicksend_auth          = isset( $click_send_settings['clicksend_auth'] ) ? esc_html( $click_send_settings['clicksend_auth'] ) : '';

		if ( ! empty( $clicksend_auth ) && ! empty( $click_send_username ) ) {
			$clicksend_api      = new API( $click_send_username, $clicksend_auth );
			$clicksend_response = $clicksend_api->get_click_send_contact_list();

			if ( isset( $clicksend_response['http_code'] ) && 200 === $clicksend_response['http_code'] ) {
				$clicksend_lists = isset( $clicksend_response['data']['data'] ) ? $clicksend_response['data']['data'] : array();
			}
		}

		if ( ! isset( $settings['sms_notifications']['sms_notification_connection_1'] ) ) {
			$settings['sms_notifications']['sms_notification_connection_1']                                    = array( 'connection_name' => __( 'Default Notification', 'everest-forms-sms_notifications' ) );
			$settings['sms_notifications']['sms_notification_connection_1']['sms_notifications_user_phone_no'] = isset( $settings['sms_notifications']['sms_notifications_user_phone_no'] ) ? $settings['sms_notifications']['sms_notifications_user_phone_no'] : '';
			$settings['sms_notifications']['sms_notification_connection_1']['sms_notifications_message']       = isset( $settings['sms_notifications']['sms_notifications_message'] ) ? $settings['sms_notifications']['sms_notifications_message'] : esc_html__( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms-pro' );

			$sms_notifications_settings = array( 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $sms_notifications_settings as $sms_notifications_setting ) {
				$settings['sms_notifications']['sms_notification_connection_1'][ $sms_notifications_setting ] = isset( $settings['sms_notifications'][ $sms_notifications_setting ] ) ? $settings['sms_notifications'][ $sms_notifications_setting ] : '';
			}
		}

		echo "<div class = 'evf-sms-notifications-settings-wrapper'>";

		foreach ( $settings['sms_notifications'] as $connection_id => $connection ) :
			if ( preg_match( '/sms_notification_connection_/', $connection_id ) ) {
				// Backward Compatibility.
				if ( isset( $settings['sms_notifications']['enable_sms_notifications'] ) && '0' === $settings['sms_notifications']['enable_sms_notifications'] ) {
					$sms_notifications_status = isset( $settings['sms_notifications']['enable_sms_notifications'] ) ? $settings['sms_notifications']['enable_sms_notifications'] : '1';
				} else {
					$sms_notifications_status = isset( $settings['sms_notifications'][ $connection_id ]['enable_sms_notifications'] ) ? $settings['sms_notifications'][ $connection_id ]['enable_sms_notifications'] : '1';
				}
				$hidden_class       = '1' !== $sms_notifications_status ? 'everest-forms-hidden' : '';
				$toggler_hide_class = isset( $toggler_hide_class ) ? 'style=display:none;' : '';
				echo '<div class="evf-content-section evf-content-sms-notifications-settings">';
				echo '<div class="evf-content-sms-notifications-title evf-content-section-title" ' . esc_attr( $toggler_hide_class ) . '>';
				echo '<div class="evf-title">' . esc_html__( 'SMS Notifications', 'everest-forms-pro' ) . '</div>';
				?>
				<div class="evf-toggle-section">
					<label class="evf-toggle-switch">
						<input type="hidden" name="settings[sms_notifications][<?php echo esc_attr( $connection_id ); ?>][enable_sms_notifications]" value="0" class="widefat">
						<input type="checkbox" name="settings[sms_notifications][<?php echo esc_attr( $connection_id ); ?>][enable_sms_notifications]" value="1" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" <?php echo checked( '1', $sms_notifications_status, false ); ?> >
						<span class="evf-toggle-switch-wrap"></span>
						<span class="evf-toggle-switch-control"></span>
					</label>
				</div></div>
				<?php

				echo '<div class="evf-content-sms-notifications-settings-inner ' . esc_attr( $hidden_class ) . '" data-connection_id=' . esc_attr( $connection_id ) . '>';

				everest_forms_panel_field(
					'text',
					'sms_notifications',
					'connection_name',
					$object->form_data,
					'',
					array(
						'default'    => isset( $settings['sms_notifications'][ $connection_id ]['connection_name'] ) ? $settings['sms_notifications'][ $connection_id ]['connection_name'] : __( 'Default Notification', 'everest-forms-pro' ),
						'class'      => 'everest-forms-pro-name',
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				everest_forms_panel_field(
					'select',
					'sms_notifications',
					'sms_service_type',
					$object->form_data,
					esc_html__( 'SMS Service Type', 'everest-forms' ),
					array(
						'default' => isset( $settings['sms_notifications'][ $connection_id ]['sms_service_type'] ) ? $settings['sms_notifications'][ $connection_id ]['sms_service_type'] : 'twilio',
						'tooltip' => sprintf( esc_html__( 'Please select the desired SMS service type', 'everest-forms-pro' ) ),
						'parent'  => 'settings',
						'options' => array(
							'twilio'    => esc_html__( 'Twilio', 'everest-forms-pro' ),
							'clicksend' => esc_html__( 'ClickSend', 'everest-forms-pro' ),
						),
					)
				);

				everest_forms_panel_field(
					'select',
					'sms_notifications',
					'clicksend_service_type',
					$object->form_data,
					esc_html__( 'ClickSend Service Type', 'everest-forms' ),
					array(
						'default' => isset( $settings['sms_notifications'][ $connection_id ]['clicksend_service_type'] ) ? $settings['sms_notifications'][ $connection_id ]['clicksend_service_type'] : 'single_sms',
						'tooltip' => sprintf( esc_html__( 'Please select the desired ClickSend service', 'everest-forms-pro' ) ),
						'parent'  => 'settings',
						'options' => array(
							'single_sms'   => esc_html__( 'Single SMS', 'everest-forms-pro' ),
							'sms_campaign' => esc_html__( 'SMS Campaign', 'everest-forms-pro' ),
						),
					)
				);

				$clicksend_options = array();
				if ( ! empty( $clicksend_lists ) ) {
					foreach ( $clicksend_lists as $clicksend_list ) {
						foreach ( $clicksend_lists as $clicksend_list ) {
							$clicksend_options[ $clicksend_list['list_id'] ] = $clicksend_list['list_name'];
						}
					}
				}

				everest_forms_panel_field(
					'select',
					'sms_notifications',
					'clicksend_contact_list',
					$object->form_data,
					esc_html__( 'ClickSend Contact List', 'everest-forms-pro' ),
					array(
						'default' => isset( $settings['sms_notifications'][ $connection_id ]['clicksend_contact_list'] ) ? $settings['sms_notifications'][ $connection_id ]['clicksend_contact_list'] : '',
						'tooltip' => sprintf( esc_html__( 'Please select the desired ClickSend service', 'everest-forms-pro' ) ),
						'options' => $clicksend_options,
						'parent'  => 'settings',
					)
				);

				everest_forms_panel_field(
					'text',
					'sms_notifications',
					'clicksend_campaign_name',
					$object->form_data,
					esc_html__( 'Campaign Name', 'everest-forms-pro' ),
					array(
						'default'    => isset( $settings['sms_notifications'][ $connection_id ]['clicksend_campaign_name'] ) ? $settings['sms_notifications'][ $connection_id ]['clicksend_campaign_name'] : '',
						'tooltip'    => esc_html__( 'Enter the campaign name Required', 'everest-forms-pro' ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				everest_forms_panel_field(
					'text',
					'sms_notifications',
					'sms_notifications_user_phone_no',
					$object->form_data,
					esc_html__( 'Recipient Phone Number', 'everest-forms-pro' ),
					array(
						'default'    => isset( $settings['sms_notifications'][ $connection_id ]['sms_notifications_user_phone_no'] ) ? $settings['sms_notifications'][ $connection_id ]['sms_notifications_user_phone_no'] : '',
						'tooltip'    => esc_html__( 'Enter the recipient phone number', 'everest-forms-pro' ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'phone',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'textarea',
					'sms_notifications',
					'sms_notifications_message',
					$object->form_data,
					__( 'Message', 'everest-forms-pro' ),
					array(
						'class'      => 'everest-forms-pro-smart-tags',
						'default'    => isset( $settings['sms_notifications'][ $connection_id ]['sms_notifications_message'] ) ? $settings['sms_notifications'][ $connection_id ]['sms_notifications_message'] : '',
						'tooltip'    => esc_html__( 'Enter the Message to send the user.', 'everest-forms-pro' ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				do_action( 'everest_forms_inline_sms_notifications_settings', $this, $connection_id );

				echo '</div></div>';
			}

		endforeach;

		echo '</div>';
	}
}
