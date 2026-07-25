<?php
/**
 * EverestForms Required Indicators
 *
 * @package EverestForms\Admin
 * @version 1.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Required_Indicators Class.
 *
 * @since 1.4.0
 */
class EVF_Required_Indicators {

	/**
	 * Construct.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {
		add_action( 'everest_forms_field_required_indicators', array( $this, 'required_indicators_type' ), 10, 2 );
		add_filter( 'everest_form_get_required_type', array( $this, 'get_required_type' ), 10, 3 );
	}

	/**
	 * Required Indicators Type.
	 *
	 * @since 1.4.0
	 *
	 * @param array $form_data Form Data.
	 * @param array $settings Form Settings.
	 */
	public function required_indicators_type( $form_data, $settings ) {
		echo '<div class="everest-forms-border-container"><h4 class="everest-forms-border-container-title">' . esc_html__( 'Required Indicators', 'everest-forms-pro' ) . '</h4>';
		everest_forms_panel_field(
			'select',
			'settings',
			'required_indicators',
			$form_data,
			esc_html__( 'Required Indicators Type', 'everest-forms-pro' ),
			array(
				'default' => 'asterisk',
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Choose how to display required indicator type. <a href="%s" target="_blank">Learn More</a>', 'everest-forms-pro' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#8-toc-title' ) ),
				'options' => array(
					'asterisk'    => esc_html( 'Asterisk (*)' ),
					'text'        => esc_html__( 'Required', 'everest-forms-pro' ),
					'custom_text' => esc_html__( 'Custom Text', 'everest-forms-pro' ),
				),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'custom_text',
			$form_data,
			esc_html__( 'Custom Text', 'everest-forms-pro' ),
			array(
				'default' => isset( $settings['custom_text'] ) ? $settings['custom_text'] : __( 'Required', 'everest-forms-pro' ),
			)
		);
		echo '</div>';
	}

	/**
	 * Get Required Type values for Form Settings.
	 *
	 * @since 1.4.0
	 *
	 * @param string $required_type Required Type Text.
	 * @param mixed  $field Field Values.
	 * @param mixed  $form_data Form Data.
	 */
	public function get_required_type( $required_type, $field, $form_data ) {
		$settings = isset( $form_data['settings'] ) ? $form_data['settings'] : array();
		if ( isset( $field['required'] ) && isset( $settings['required_indicators'] ) ) {
			if ( 'text' === $settings['required_indicators'] ) {
				$required_type = '(Required)';
			} elseif ( 'custom_text' === $settings['required_indicators'] ) {
				$required_type = ! empty( $settings['custom_text'] ) ? $settings['custom_text'] : '(Required)';
			} else {
				$required_type = '*';
			}
		}
		return $required_type;
	}
}

new EVF_Required_Indicators();
