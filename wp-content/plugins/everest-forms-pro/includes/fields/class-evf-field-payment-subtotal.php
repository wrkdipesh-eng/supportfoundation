<?php
/**
 * Payment total field.
 *
 * @package EverestForms_Pro\Fields
 * @since   1.6.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Subtotal class.
 */
class EVF_Field_Payment_Subtotal extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Subtotal', 'everest-forms-pro' );
		$this->type     = 'payment-subtotal';
		$this->icon     = 'evf-icon evf-icon-subtotal';
		$this->order    = 220;
		$this->group    = 'payment';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'map_field',
					'required',
					'required_field_message_setting',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
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
		add_filter( 'everest_forms_process_filter', array( $this, 'process_filter' ), 10, 3 );
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
		// Input Primary: initial total is always zero.
		$properties['inputs']['primary']['attr']['value'] = '0';

		// Input Primary: add class for targeting calculations.
		$properties['inputs']['primary']['class'][] = 'evf-payment-subtotal';

		// Input Primary: add data attribute if total is required.
		if ( ! empty( $field['required'] ) ) {
			$properties['inputs']['primary']['data']['rule-required-payment'] = true;
		}

		return $properties;
	}

	/**
	 * Process form after validation.
	 *
	 * @param mixed $form_fields Form Fields.
	 * @param mixed $entry Entry.
	 * @param mixed $form_data Form Data.
	 * @return mixed $form_fields Form Fields.
	 */
	public function process_filter( $form_fields, $entry, $form_data ) {
		$post_fields    = isset( $_POST['everest_forms']['form_fields'] ) ? array_keys( $_POST['everest_forms']['form_fields'] ) : array(); //phpcs:ignore
		$fields         = evf_get_payment_items( $form_fields, $entry, $form_data );
		$quantity_price = array();
		$map_field      = array();

		if ( empty( $fields ) ) {
			return $form_fields;
		}

		if ( isset( $form_data['form_fields'] ) && ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $key => $field ) {
				if ( 'payment-subtotal' === $field['type'] ) {
					$map_field[ $field['id'] ] = isset( $field['map_field'] ) ? $field['map_field'] : '';
				}
			}
		}

		foreach ( $fields as $field ) {

			if ( ! in_array( $field['id'], $post_fields, true ) ) {
				continue;
			}

			if ( ! empty( $field['amount_raw'] ) && ! in_array( $field['id'], $map_field, true ) ) {
					$amount = evf_sanitize_amount( $field['amount_raw'] );
			}

			if ( 'payment-quantity' === $field['type'] && ! empty( $map_field ) ) {
				if ( ! empty( $field['value'] ) ) {
					foreach ( $map_field as $key => $id ) {
						if ( $form_data['form_fields'][ $field['id'] ]['map_field'] === $id && isset( $fields[ $id ] ) ) {
							$quantity_price[ $key ] = evf_sanitize_amount( $fields[ $id ]['amount_raw'] ) * $field['value'];
						}
					}
				}
			} else {
				if ( ! empty( $field['value'] ) && ! empty( $map_field ) ) {
					foreach ( $map_field as $key => $id ) {
						if ( $form_data['form_fields'][ $field['id'] ] === $id && isset( $fields[ $id ] ) ) {
							$form_fields[ $key ]['amount']     = evf_format_amount( evf_sanitize_amount( $fields[ $id ]['amount_raw'] ) );
							$form_fields[ $key ]['amount_raw'] = evf_format_amount( evf_sanitize_amount( $fields[ $id ]['amount_raw'] ) );
							$form_fields[ $key ]['value']      = evf_format_amount( evf_sanitize_amount( $fields[ $id ]['amount_raw'] ), true );
						}
					}
				}
			}
		}

		if ( ! empty( $quantity_price ) ) {
			foreach ( $quantity_price as $field_id => $amount ) {
				$form_fields[ $field_id ]['amount']     = evf_format_amount( $amount );
				$form_fields[ $field_id ]['amount_raw'] = evf_format_amount( $amount );
				$form_fields[ $field_id ]['value']      = evf_format_amount( $amount, true );
			}
		}

		return $form_fields;
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

		$options = evf_parse_args( $options, array( '' => __( '---Select a Field---', 'everest-forms-pro' ) ) );

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => esc_html__( 'Calculate with this field', 'everest-forms-pro' ),
				'tooltip' => esc_html__( 'Calculate subtoal value for this selected payment field.', 'everest-forms-pro' ),
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
	 * @since 1.6.1
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary field.
		echo '<div>' . evf_format_amount( 0, true ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
		$primary   = $field['properties']['inputs']['primary'];
		$type      = ! empty( $field['required'] ) ? 'text' : 'hidden';
		$map_field = isset( $form_data['form_fields'][ $field['id'] ]['map_field'] ) ? $form_data['form_fields'][ $field['id'] ]['map_field'] : '';
		$style     = ! empty( $field['required'] ) ? 'style="position:absolute!important;clip:rect(0,0,0,0)!important;height:1px!important;width:1px!important;border:0!important;overflow:hidden!important;padding:0!important;margin:0!important;" readonly ' : '';

		// This displays the total the user sees.
		echo '<div class="evf-payment-subtotal">' . evf_sanitize_amount( evf_format_amount( 0, true ) ) . '</div>';

		// Hidden input for processing.
		printf(
			'<input type="%s" data-map_field="%s" %s %s>',
			esc_attr( $type ),
			esc_attr( $map_field ),
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			$style // phpcs:ignore WordPress.Security.EscapeOutput
		);
	}

	/**
	 * Validates field on form submit.
	 *
	 * @param int    $field_id     Field ID.
	 * @param string $field_submit Field value submitted by a user.
	 * @param array  $form_data    Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$entry   = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );

		if ( false === $visible ) {
			return;
		}

		// Basic required check - If field is marked as required, check for entry data.
		if ( ! empty( $form_data['fields'][ $field_id ]['required'] ) && ( empty( $field_submit ) || evf_sanitize_amount( $field_submit ) <= 0 ) ) {
			evf()->process->errors[ $form_data['id'] ][ $field_id ] = esc_html__( 'Payment is required.', 'everest-forms-pro' );
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.6.1
	 *
	 * @param int    $field_id     Field ID.
	 * @param mixed  $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 * @param string $meta_key     Field meta key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$name   = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';
		$amount = evf_sanitize_amount( $field_submit );

		// Set final field details.
		EVF()->task->form_fields[ $field_id ] = array(
			'name'       => make_clickable( $name ),
			'value'      => evf_format_amount( $amount, true ),
			'amount'     => evf_format_amount( $amount ),
			'amount_raw' => $amount,
			'id'         => $field_id,
			'type'       => $this->type,
			'meta_key'   => $meta_key,
		);
	}
}
