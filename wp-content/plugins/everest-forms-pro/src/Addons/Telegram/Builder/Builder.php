<?php
/**
 * Everest Forms Telegram Form Builder Settings.
 *
 * @package EverestForms\Pro\Telegram\Builder
 * @since   1.7.7
 */

namespace EverestForms\Pro\Addons\Telegram\Builder;

use EverestForms\Pro\Addons\Telegram\Api\Api;

defined( 'ABSPATH' ) || exit;


/**
 * Telegram Integration.
 */
class Builder {
	/**
	 * Everest Forms Telegram Builder Constructor
	 *
	 * @since 1.7.7
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_telegram_admin_scripts' ) );
		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'output_telegram_settings' ) );
	}

	/**
	 * Load scripts.
	 *
	 * @since 1.0.0
	 */
	public function load_telegram_admin_scripts() {
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		// Enqueue Scripts.
		wp_register_script( 'everest-forms-telegram-script', plugins_url( "src/Addons/Telegram/assets/js/admin{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_script( 'everest-forms-telegram-script' );
			wp_localize_script(
				'everest-forms-telegram-script',
				'everest_forms_telegram',
				array(
					'telegram_disable_message' => esc_html__( 'Telegram is currently disabled . Please enable it to activate this feature with Everest Forms . ', 'everest-forms-pro' ),
				)
			);
		}
	}


	/**
	 * Register settings section.
	 *
	 * @param  array $sections Settings section.
	 * @return array
	 */
	public function add_settings_section( $sections ) {
		$new_sections = array(
			'telegram' => esc_html__( 'Telegram', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}

		/**
		 * Output User Registration settings.
		 *
		 * @param object $object Form settings object.
		 */
	public function output_telegram_settings( $object ) {
		$settings = isset( $object->form_data['settings'] ) ? $object->form_data['settings'] : array();
		if ( ! isset( $settings['telegram']['telegram_connection_1'] ) ) {
			$settings['telegram']['telegram_connection_1'] = array( 'connection_name' => __( 'Default Notification', 'everest-forms-pro' ) );

			$sms_notifications_settings = array( 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $sms_notifications_settings as $sms_notifications_setting ) {
				$settings['telegram']['telegram_connection_1'][ $sms_notifications_setting ] = isset( $settings['telegram'][ $sms_notifications_setting ] ) ? $settings['sms_notifications'][ $sms_notifications_setting ] : '';
			}
		}
		echo "<div class = 'evf-telegram-settings-wrapper'>";

		foreach ( $settings['telegram'] as $connection_id => $connection ) :
			// Backward Compatibility.
			if ( isset( $settings['telegram']['enable_telegram'] ) && '0' === $settings['telegram']['enable_telegram'] ) {
				$sms_notifications_status = isset( $settings['telegram']['enable_telegram'] ) ? $settings['telegram']['enable_telegram'] : '1';
			} else {
				$sms_notifications_status = isset( $settings['telegram'][ $connection_id ]['enable_telegram'] ) ? $settings['telegram'][ $connection_id ]['enable_telegram'] : '1';
			}
			$hidden_class       = '1' !== $sms_notifications_status ? 'everest-forms-hidden' : '';
			$toggler_hide_class = isset( $toggler_hide_class ) ? 'style=display:none;' : '';
			echo '<div class="evf-content-section evf-content-telegram-settings">';
			echo '<div class="evf-content-telegram-title evf-content-section-title" ' . esc_attr( $toggler_hide_class ) . '>';
			echo '<div class="evf-title">' . esc_html__( 'Telegram', 'everest-forms-pro' ) . '</div>';
			?>
				<div class="evf-toggle-section">
					<label class="evf-toggle-switch">
						<input type="hidden" name="settings[telegram][<?php echo esc_attr( $connection_id ); ?>][enable_telegram]" value="0" class="widefat">
						<input type="checkbox" name="settings[telegram][<?php echo esc_attr( $connection_id ); ?>][enable_telegram]" value="1" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" <?php echo checked( '1', $sms_notifications_status, false ); ?> >
						<span class="evf-toggle-switch-wrap"></span>
						<span class="evf-toggle-switch-control"></span>
					</label>
				</div></div>
				<?php

				echo '<div class="evf-content-telegram-settings-inner ' . esc_attr( $hidden_class ) . '" data-connection_id=' . esc_attr( $connection_id ) . '>';

				everest_forms_panel_field(
					'text',
					'telegram',
					'connection_name',
					$object->form_data,
					'',
					array(
						'default'    => isset( $settings['telegram'][ $connection_id ]['connection_name'] ) ? $settings['telegram'][ $connection_id ]['connection_name'] : __( 'Default Notification', 'everest-forms-pro' ),
						'class'      => 'everest-forms-telegram-name',
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				everest_forms_panel_field(
					'text',
					'telegram',
					'telegram_channel_id',
					$object->form_data,
					__( 'Channel or Group ID', 'everest-forms-pro' ),
					array(
						'default'     => isset( $settings['telegram'][ $connection_id ]['telegram_channel_id'] ) ? $settings['telegram'][ $connection_id ]['telegram_channel_id'] : '',
						'placeholder' => 'Channel or Group ID',
						'tooltip'     => esc_html__( 'Enter the group ID or channel ID or chat ID you want send the message to.', 'everest-forms-pro' ),
						'parent'      => 'settings',
						'subsection'  => $connection_id,
					)
				);

					everest_forms_panel_field(
						'tinymce',
						'telegram',
						'telegram_message',
						$object->form_data,
						esc_html__( 'Telegram Message', 'everest-forms' ),
						array(
							'default'    => isset( $settings['telegram'][ $connection_id ]['telegram_message'] ) && ! empty( $settings['telegram'][ $connection_id ]['telegram_message'] ) ? evf_string_translation( $object->form_data['id'], 'telegram_message', $settings['telegram'][ $connection_id ]['telegram_message'] ) : 'Everest Forms: New submission Entry {entry_id} on {form_name}.',
							/* translators: %1$s - general settings docs url */
							'tooltip'    => sprintf( esc_html__( 'Enter the message to be sent for the telegram group or channel. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms-pro' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#6-toc-title' ) ),
							'smarttags'  => array(
								'type'        => 'all',
								'form_fields' => 'all',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);

				do_action( 'everest_forms_inline_telegram_settings', $this, $connection_id );

				echo '</div></div>';

			endforeach;

		echo '</div>';
	}
}
