<?php
/**
 * Everest Forms Slack Form Builder Settings.
 *
 * @package EverestForms\Pro\Addons\Slack\Builder
 * @since 3.0.5
 */

namespace EverestForms\Pro\Addons\Slack\Builder;

use EverestForms\Pro\Addons\Slack\API\API;

defined( 'ABSPATH' ) || exit;


/**
 * Slack Integration.
 *
 * @since 3.0.5
 */
class Builder {
	/**
	 * Everest Forms Slack Builder Constructor
	 *
	 * @since 3.0.5
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_slack_admin_scripts' ) );
		add_filter( 'everest_forms_builder_settings_section', array( $this, 'add_settings_section' ) );
		add_action( 'everest_forms_settings_panel_content', array( $this, 'output_slack_settings' ) );
	}

	/**
	 * Load scripts.
	 *
	 * @since 3.0.5
	 */
	public function load_slack_admin_scripts() {
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		// Enqueue Scripts.
		wp_register_script( 'everest-forms-slack-script', plugins_url( "src/Addons/Slack/assets/js/admin{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
		if ( in_array( $screen_id, evf_get_screen_ids(), true ) ) {
			wp_enqueue_script( 'everest-forms-slack-script' );
			wp_localize_script(
				'everest-forms-slack-script',
				'everest_forms_slack',
				array(
					'slack_disable_message' => esc_html__( 'Slack is currently disabled . Please enable it to activate this feature with Everest Forms . ', 'everest-forms-pro' ),
				)
			);
		}
	}

	/**
	 * Register settings section.
	 *
	 * @param  array $sections Settings section.
	 * @return array
	 *
	 * @since 3.0.5
	 */
	public function add_settings_section( $sections ) {
		$new_sections = array(
			'slack' => esc_html__( 'Slack', 'everest-forms-pro' ),
		);

		return array_merge( $sections, $new_sections );
	}

	/**
	 * Output User Registration settings.
	 *
	 * @param object $object Form settings object.
	 *
	 * @since 3.0.5
	 */
	public function output_slack_settings( $object ) {
		$settings = isset( $object->form_data['settings'] ) ? $object->form_data['settings'] : array();

		if ( ! isset( $settings['slack']['slack_connection_1'] ) ) {
			$settings['slack']['slack_connection_1'] = array( 'connection_name' => $object->form->post_title );

			$sms_notifications_settings = array( 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $sms_notifications_settings as $sms_notifications_setting ) {
				$settings['slack']['slack_connection_1'][ $sms_notifications_setting ] = isset( $settings['slack'][ $sms_notifications_setting ] ) ? $settings['sms_notifications'][ $sms_notifications_setting ] : '';
			}
		}
		echo "<div class = 'evf-slack-settings-wrapper'>";

		foreach ( $settings['slack'] as $connection_id => $connection ) :
			// Backward Compatibility.
			if ( isset( $settings['slack']['enable_slack'] ) && '0' === $settings['slack']['enable_slack'] ) {
				$sms_notifications_status = isset( $settings['slack']['enable_slack'] ) ? $settings['slack']['enable_slack'] : '1';
			} else {
				$sms_notifications_status = isset( $settings['slack'][ $connection_id ]['enable_slack'] ) ? $settings['slack'][ $connection_id ]['enable_slack'] : '1';
			}
			$hidden_class       = '1' !== $sms_notifications_status ? 'everest-forms-hidden' : '';
			$toggler_hide_class = isset( $toggler_hide_class ) ? 'style=display:none;' : '';
			echo '<div class="evf-content-section evf-content-slack-settings">';
			echo '<div class="evf-content-slack-title evf-content-section-title" ' . esc_attr( $toggler_hide_class ) . '>';
			echo '<div class="evf-title">' . esc_html__( 'Slack', 'everest-forms-pro' ) . '</div>';
			?>
				<div class="evf-toggle-section">
					<label class="evf-toggle-switch">
						<input type="hidden" name="settings[slack][<?php echo esc_attr( $connection_id ); ?>][enable_slack]" value="0" class="widefat">
						<input type="checkbox" name="settings[slack][<?php echo esc_attr( $connection_id ); ?>][enable_slack]" value="1" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" <?php echo checked( '1', $sms_notifications_status, false ); ?> >
						<span class="evf-toggle-switch-wrap"></span>
						<span class="evf-toggle-switch-control"></span>
					</label>
				</div></div>
				<?php

				echo '<div class="evf-content-slack-settings-inner ' . esc_attr( $hidden_class ) . '" data-connection_id=' . esc_attr( $connection_id ) . '>';

				everest_forms_panel_field(
					'text',
					'slack',
					'connection_name',
					$object->form_data,
					__( 'Slack Message Title', 'everest-forms-pro' ),
					array(
						'default'    => isset( $settings['slack'][ $connection_id ]['connection_name'] ) ? $settings['slack'][ $connection_id ]['connection_name'] . __( ' Entry Details', 'everest-forms-pro' ) : __( 'Chat Title', 'everest-forms-pro' ),
						'class'      => 'everest-forms-slack-name',
						'tooltip'    => esc_html__( 'Enter the title for message.', 'everest-forms-pro' ),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				everest_forms_panel_field(
					'text',
					'slack',
					'slack_incoming_webhook',
					$object->form_data,
					__( 'Slack incoming webhook', 'everest-forms-pro' ),
					array(
						'default'     => isset( $settings['slack'][ $connection_id ]['slack_incoming_webhook'] ) ? $settings['slack'][ $connection_id ]['slack_incoming_webhook'] : '',
						'placeholder' => 'Incoming webhook',
						'tooltip'     => esc_html__( 'Enter the incoming webhook url ', 'everest-forms-pro' ),
						'parent'      => 'settings',
						'subsection'  => $connection_id,
					)
				);

					everest_forms_panel_field(
						'tinymce',
						'slack',
						'slack_message',
						$object->form_data,
						esc_html__( 'Slack Message', 'everest-forms' ),
						array(
							'default'    => isset( $settings['slack'][ $connection_id ]['slack_message'] ) && ! empty($settings['slack'][ $connection_id ]['slack_message']) ? evf_string_translation( $object->form_data['id'], 'slack_message', $settings['slack'][ $connection_id ]['slack_message'] ) : 'Everest Forms: New submission Entry {entry_id} on {form_name}.',
							/* translators: %1$s - general settings docs url */
							'tooltip'    => sprintf( esc_html__( 'Enter the message to be sent for the slack channel. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms-pro' ), esc_url( 'https://docs.everestforms.net/docs/email-settings/#6-toc-title' ) ),
							'smarttags'  => array(
								'type'        => 'all',
								'form_fields' => 'all',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);

				do_action( 'everest_forms_inline_slack_settings', $this, $connection_id );

				echo '</div></div>';

			endforeach;

		echo '</div>';
	}
}
