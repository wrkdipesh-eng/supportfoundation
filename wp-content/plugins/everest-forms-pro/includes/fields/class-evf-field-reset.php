<?php
/**
 * Reset field.
 *
 * @package EverestForms_Pro\Fields
 * @since   1.5.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_color class.
 */
class EVF_Field_Reset extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Reset', 'everest-forms-pro' );
		$this->type     = 'reset';
		$this->icon     = 'evf-icon evf-icon-reset';
		$this->order    = 180;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'button_text',
					'button_type',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( 'everest_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * Apply Button Text.
	 *
	 * @param array $field Field data object.
	 */
	public function button_text( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'button_text',
				'value'   => esc_html__( 'Button Text', 'everest-forms-coupons' ),
				'tooltip' => esc_html__( 'Add text to the reset button', 'everest-forms-pro' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'button_text',
				'value' => ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Reset', 'everest-forms-pro' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'button_text',
				'content' => $lbl . $fld,
			)
		);
	}


	/**
	 * Apply Button Text.
	 *
	 * @param array $field Field data object.
	 */
	public function button_type( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'button_type',
				'value'   => esc_html__( 'Button Type', 'everest-forms-coupons' ),
				'tooltip' => esc_html__( '1. Reset - Removes all the field values except the default values.
				2. Clear - Removes all field values, including the default values.
				', 'everest-forms-pro' ),
			),
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'button_type',
				'value'   => ! empty( $field['button_type'] ) ? esc_attr( $field['button_type'] ) : 'reset',
				'options' =>
					array(
						'reset'  => esc_html__( 'Reset', 'everest-forms-pro' ),
						'button' => esc_html__( 'Clear', 'everest-forms-pro' ),
					),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'button_text',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$properties['inputs']['primary']['class'][] = 'evf-reset-button';
		$properties['inputs']['primary']['class'][] = 'input-text';
		return $properties;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$button_label = ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Reset', 'everest-forms-pro' );

		// Primary Input.

		echo '<div class="everest-forms-reset-button">';

		printf(
			'<button type="button" class="evf-reset-button" disabled>%s</button>',
			esc_html( $button_label )
		);

		echo '</div>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$primary      = $field['properties']['inputs']['primary'];
		$button_label = ! empty( $field['button_text'] ) ? $field['button_text'] : __( 'Reset', 'everest-forms-pro' );
		$button_type  = ! empty( $field['button_type'] ) ? $field['button_type'] : 'reset';
		echo '<div class="everest-forms-reset-buttons">';
		printf(
			'<button type="%s" class="evf-reset-button" data-text="%s" data-type="%s">%s</button>',
			esc_attr( $button_type ),
			esc_attr( $button_label ),
			esc_attr( $button_type ),
			esc_html( $button_label )
		);
		echo '</div>';
	}


	/**
	 * Formats and sanitizes field.
	 *
	 * @param int    $field_id     Field id.
	 * @param array  $field_submit Field submit value.
	 * @param array  $form_data    Form data object.
	 * @param string $meta_key     Meta key data for the field.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {}
}
