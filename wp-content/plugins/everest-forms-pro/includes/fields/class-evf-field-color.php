<?php
/**
 * Color field.
 *
 * @package EverestForms_Pro\Fields
 * @since   1.6.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_color class.
 */
class EVF_Field_Color extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Color', 'everest-forms-pro' );
		$this->type     = 'color';
		$this->icon     = 'evf-icon evf-icon-color';
		$this->order    = 210;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'meta',
					'default_color',
					'label_hide',
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
		add_filter( 'everest_forms_html_field_value', array( $this, 'html_color_value' ), 10, 4 );
		add_filter( 'everest_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	/**
	 * Define additional field properties.
	 *
	 * @since 1.6.1
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$properties['container']['class'][]               = 'evf-color-picker';
		$properties['inputs']['primary']['attr']['value'] = ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : '';

		return $properties;
	}

	/**
	 * Customize format for HTML email notifications and entry details.
	 *
	 * @param string $value       Value.
	 * @param array  $field     Field settings.
	 * @param array  $form_data Form data settings.
	 * @param string $context   Context usage.
	 *
	 * @return string
	 */
	public function html_color_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field ) ) {
			$field_value = maybe_unserialize( $field );
			if ( isset( $field_value['type'] ) && $field_value['type'] === $this->type ) {
				if ( 'entry-table' === $context || 'entry-single' === $context ) {
					$value = ! empty( $field_value['value'] ) ? sprintf(
						'<span style="background-color: %s; width: 18px; height: 18px; border-radius: 3px; margin-right: 6px; display: inline-block;"></span> %s',
						sanitize_text_field( $field_value['value'] ),
						sanitize_text_field( $field_value['value'] )
					) : '';

					if ( empty( $value ) && 'entry-table' === $context ) {
						$value = sprintf( '<span class="na">&mdash;</span>' );
					}
				} elseif ( 'export-pdf' === $context || 'email-html' === $context ) {
					$value = ! empty( $field_value['value'] ) ? sprintf(
						'<span style="color: %s;">%s</span>',
						sanitize_text_field( $field_value['value'] ),
						sanitize_text_field( $field_value['value'] )
					) : '';
				} else {
					$value = sanitize_text_field( $field_value['value'] );
				}
			}
		}
		return $value;
	}

	/**
	 * Filter callback for outputting formatted data.
	 *
	 * @since 1.6.1
	 *
	 * @param array $field Field Data.
	 * @return array Data for field exporter PDF or Email.
	 */
	public function field_exporter( $field ) {
		return array(
			'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
			'value' => ! empty( $field['value']['value'] ) ? sprintf( '<span style="color:%s;">%s</span>', sanitize_text_field( $field['value']['value'] ), sanitize_text_field( $field['value']['value'] ) ) : false,
		);
	}

	/**
	 * Color field default value option.
	 *
	 * @param array $field Field settings.
	 */
	public function default_color( $field ) {

		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'default',
				'value'   => esc_html__( 'Default Color', 'everest-forms-pro' ),
				'tooltip' => esc_html__( 'Choose the default color selected in the field.', 'everest-forms-pro' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'default',
				'value' => ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : '#000000',
				'class' => 'evf-colorpicker',
				'data'  => array(
					'default-color' => '#000000',
				),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'default',
				'content' => $lbl . $fld,
			)
		);
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.6.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {

		// Define data.
		$default = ! empty( $field['default'] ) ? esc_attr( $field['default'] ) : '#000000';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<div class="evf-color-picker-bg" style="background: ' . esc_attr( $default ) . ';"></div><input type="text" class="widefat colorpickpreview" disabled>';

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.6.1
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		// Define data.
		$primary    = $field['properties']['inputs']['primary'];
		$value      = $primary['attr']['value'];
		$background = ! empty( $value ) ? esc_attr( $value ) : '");';

		// Primary field.
		printf(
			'<div class="evf-color-picker-bg" style="background:%s;"><input class="evf-cp-input" type="color" value="%s" /></div><input type="text" %s %s>',
			esc_attr( $background ),
			esc_attr( $value ),
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}

	/**
	 * Formats field.
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data    Form data and settings.
	 * @param mixed $meta_key     Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$name  = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$value = ! empty( $field_submit ) ? sanitize_text_field( $field_submit ) : '';
		$value = ! empty( $value ) && preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/i', $value ) ? $value : '';

		EVF()->task->form_fields[ $field_id ] = array(
			'name'     => make_clickable( $name ),
			'value'    => array(
				'value' => $value,
				'type'  => $this->type,
			),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}
