<?php
/**
 * Payment Quantity field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Quantity class.
 */
class EVF_Field_Payment_Quantity extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Quantity', 'everest-forms-pro' );
		$this->type     = 'payment-quantity';
		$this->icon     = 'evf-icon evf-icon-single-item';
		$this->order    = 40;
		$this->group    = 'payment';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'map_field',
					'required',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'meta',
					'label_hide',
					'default_value',
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
		$properties['inputs']['primary']['attr']['name']    = 'everest_forms[form_fields][' . $field['id'] . '][]';
		$properties['inputs']['primary']['class'][]         = 'evf-payment-price';
		$properties['inputs']['primary']['class'][]         = 'evf-payment-quantity';
		$properties['inputs']['primary']['class'][]         = 'input-text';
		$default_value = ( isset( $field['default_value'] ) && '' !== $field['default_value'] )
			? max( 0, intval( $field['default_value'] ) )
			: 1;

		$properties['inputs']['primary']['data']['default'] = $default_value;

		if ( ! isset( $properties['inputs']['primary']['attr']['value'] ) || '' === $properties['inputs']['primary']['attr']['value'] ) {
			$properties['inputs']['primary']['attr']['value'] = $default_value;
		}

		return $properties;
	}

	/**
	 * Mapping the payment field with quantity.
	 *
	 * @param array $field Field data object.
	 */
	public function map_field( $field ) {
		$form_id   = isset( $_GET['form_id'] ) ? wp_unslash( absint( $_GET['form_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';
		$options   = array();

		if ( isset( $form_data['form_fields'] ) && is_array( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $id => $form_field ) {
				if ( isset( $form_field['enable_payment_slider'] ) && '1' === $form_field['enable_payment_slider'] ) {
					if ( in_array( $form_field['type'], array( 'payment-single', 'payment-multiple', 'payment-checkbox', 'range-slider' ), true ) ) {
						$options[ $form_field['id'] ] = $form_field['label'];
					}
				} else {
					if ( in_array( $form_field['type'], array( 'payment-single', 'payment-multiple', 'payment-checkbox' ), true ) ) {
						$options[ $form_field['id'] ] = $form_field['label'];
					}
				}
			}
		}

		if ( empty( $options ) ) {
			$options = array( '' => __( '---Select Field---', 'everest-forms-pro' ) );
		}

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => esc_html__( 'Calculate with this field', 'everest-forms-pro' ),
				'tooltip' => esc_html__( 'Choose the number of icons.', 'everest-forms-pro' ),
			),
			false
		);
		$fld = $this->field_element(
			'select',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => ! empty( $field['map_field'] ) ? esc_attr( $field['map_field'] ) : '',
				'options' => $options,
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'map_field',
				'content' => $lbl . $fld,
			)
		);
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
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<input type="number" placeholder="' . $placeholder . '" class="widefat" disabled>'; // @codingStandardsIgnoreLine.

		// Description.
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Validates the field.
	 *
	 * @param string $field_id     Field ID.
	 * @param mixed  $field_submit Submitted field value.
	 * @param array  $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$quantity = isset( $field_submit[0] ) ? intval( $field_submit[0] ) : 0;

		if ( $quantity < 0 ) {
			evf()->task->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Quantity cannot be less than 0.', 'everest-forms-pro' );
			update_option( 'evf_validation_error', 'yes' );
		}
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
		// Define data.
		$primary   = $field['properties']['inputs']['primary'];
		$map_field = isset( $form_data['form_fields'][ $field['id'] ]['map_field'] ) ? $form_data['form_fields'][ $field['id'] ]['map_field'] : '';

		// Primary field.
		printf(
			'<input type="number" min="0" onkeypress="return (event.charCode == 8 || event.charCode == 0 || event.charCode == 13) ? null : event.charCode >= 46 && event.charCode <= 57" %s  data-map_field="%s" %s />',
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $map_field ),
			esc_attr( $primary['required'] )
		);
	}

}
