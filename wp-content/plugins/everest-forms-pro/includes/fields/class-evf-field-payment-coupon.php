<?php
/**
 * Payment Coupon Field.
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

use EverestForms\Pro\Addons\Coupons\Process\Process;

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Single Field.
 */
class EVF_Field_Payment_Coupon extends \EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Coupon', 'everest-forms-pro' );
		$this->type     = 'payment-coupon';
		$this->icon     = 'evf-icon evf-icon-coupon';
		$this->order    = 16;
		$this->group    = 'payment';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
					'discount_message',
					'map_field',
					'button_text',
					'invalid_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'meta',
					'label_hide',
					'css',
				),
			),
		);

		add_filter( 'everest_forms_html_field_value', array( $this, 'html_field_value' ), 10, 4 );
		parent::__construct();
	}

	/**
	 * Filter the HTML field value.
	 *
	 * @param string|array $value     Field value.
	 * @param array        $field     Field data.
	 * @param array        $form_data Form data.
	 * @param string       $context   Field context.
	 *
	 * @return array|string
	 */
	public function html_field_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( empty( $value ) || ( ! is_array( $value ) && ! is_string( $value ) ) ) {
			return $value;
		}

		if ( is_string( $value ) && ! is_serialized( $value ) ) {
			return $value;
		}

		$decoded = evf_maybe_unserialize( $value );

		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return $value;
		}

		$first = reset( $decoded );
		if ( ! is_array( $first ) || ( ! isset( $first['code'] ) && ! isset( $first['coupon_code'] ) ) ) {
			return $value;
		}

		$coupon_codes = array();
		foreach ( $decoded as $coupon ) {
			if ( is_array( $coupon ) ) {
				if ( ! empty( $coupon['code'] ) ) {
					$coupon_codes[] = $coupon['code'];
				} elseif ( ! empty( $coupon['coupon_code'] ) ) {
					$coupon_codes[] = $coupon['coupon_code'];
				}
			} else {
				$coupon_codes[] = (string) $coupon;
			}
		}

		if ( ! empty( $coupon_codes ) ) {
			return implode( ' | ', array_unique( array_filter( $coupon_codes ) ) );
		}

		return is_array( $value ) ? '' : $value;
	}

	/**
	 * Validates field on form submit.
	 *
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted data.
	 * @param array $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $all_form_data ) {
		$form_data   = ! empty( $all_form_data['form_data'] ) ? $all_form_data['form_data'] : '';
		$form_id     = (int) $form_data['id'];
		$coupon_id   = $field_id;
		$coupon_code = ! empty( $field_submit[0] ) ? $field_submit[0] : '';
		$field       = $form_data['form_fields'][ $field_id ];
		$entry       = isset( $form_data['entry'] ) ? $form_data['entry'] : array();
		$visible     = apply_filters( 'everest_forms_visible_fields', true, $form_data['form_fields'][ $field_id ], $entry, $form_data );
		if ( false === $visible ) {
			return;
		}
		$data = Process::validate_coupon( $form_id, $coupon_id, $coupon_code, $all_form_data, 'form_submission' );

		if ( empty( $data ) ) {
			return array();
		}

		$coupon_code = ! empty( $all_form_data['applied_coupons_data'] ) ? $all_form_data['applied_coupons_data'] : $coupon_code;

		// Check the provided coupon is valid or not.
		if ( ! empty( $coupon_code ) && empty( $data['status'] ) ) {
			if ( ! empty( $data['type' ] ) && 'minimum_purchase_not_met' === $data['type'] ) {
				evf()->task->errors[ $form_id ][ $field_id ] = ! empty( $data['message'] ) ? $data['message'] : __( 'Minimum purchase requirement not met', 'everest-forms-coupons' );
			}elseif ( ! empty( $data['type' ] ) && 'limit_reached' === $data['type'] ) {
				evf()->task->errors[ $form_id ][ $field_id ] = ! empty( $data['message'] ) ? $data['message'] : __( 'Coupon usage limit has been reached.', 'everest-forms-coupons' );
			}else{
				evf()->task->errors[ $form_id ][ $field_id ] = ! empty( $field['invalid_message'] ) ? $field['invalid_message'] : __( 'Coupon is invalid or expired', 'everest-forms-coupons' );
			}
			update_option( 'evf_validation_error', 'yes' );
		}
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

		$options = array_merge( array( '' => __( 'Total', 'everest-forms-coupons' ) ), $options );

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'map_field',
				'value'   => esc_html__( 'Calculate with this field', 'everest-forms-coupons' ),
				'tooltip' => esc_html__( 'Choose the field to calculate the coupon discount.', 'everest-forms-coupons' ),
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
				'tooltip' => esc_html__( 'Add text to the apply coupon button', 'everest-forms-coupons' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'button_text',
				'value' => ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Apply Coupon', 'everest-forms-coupons' ),
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
	public function invalid_message( $field ) {

		// Input Mask.
		$lbl = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'invalid_message',
				'value'   => esc_html__( 'Invalid Coupon Message', 'everest-forms-coupons' ),
				'tooltip' => esc_html__( 'Add text to be displayed when coupon code is invalid', 'everest-forms-coupons' ),
			),
			false
		);
		$fld = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'invalid_message',
				'value' => ! empty( $field['invalid_message'] ) ? esc_attr( $field['invalid_message'] ) : __( 'Coupon code is invalid or expired', 'everest-forms-coupons' ),
			),
			false
		);
		$this->field_element(
			'row',
			$field,
			array(
				'slug'    => 'invalid_message',
				'content' => $lbl . $fld,
			)
		);
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
		$properties['inputs']['primary']['attr']['name'] = 'everest_forms[form_fields][' . $field['id'] . '][]';
		$properties['inputs']['primary']['class'][]      = 'evf-payment-coupon';
		$properties['inputs']['primary']['class'][]      = 'input-text';
		$default_value                                   = empty( $field['default_value'] ) ? '' : esc_attr( $field['default_value'] );
		$properties['inputs']['primary']['data']   ['default'] = $default_value;

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
		$placeholder  = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$value        = ! empty( $field['coupon_code'] ) ? esc_attr( $field['coupon_code'] ) : '';
		$button_label = ! empty( $field['button_text'] ) ? esc_attr( $field['button_text'] ) : __( 'Apply Coupon', 'everest-forms-coupons' );
		echo '<div>';

			$this->field_preview_option( 'label', $field );

			echo '<div class="everest-forms-coupons">';

			printf(
				'<input type="text" placeholder="%s" class="widefat primary-input" value="%s" disabled>',
				esc_attr( $placeholder ),
				esc_attr( $value )
			);

			printf(
				'<button type="button" class="evf-coupon-apply" disabled>%s</button>',
				esc_html( $button_label )
			);

			echo '</div>';

			$this->field_preview_option( 'description', $field );

		echo '</div>';
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
		$item_type    = ! empty( $field['item_type'] ) ? $field['item_type'] : 'single';
		$map_field    = isset( $form_data['form_fields'][ $field['id'] ]['map_field'] ) ? $form_data['form_fields'][ $field['id'] ]['map_field'] : '';
		$button_label = ! empty( $field['button_text'] ) ? $field['button_text'] : __( 'Apply Now', 'everest-forms-coupons' );
		echo '<div class="everest-forms-coupons">';
		echo '<span class="everest-forms-coupons-wrapper">';

		printf(
			'<input type="text" data-map_field="%s" %s>',
			esc_attr( $map_field ),
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
		);
		echo '<span class="evf-clear-coupon everest-forms-hidden">x</span></span>';
		printf(
			'<button type="button" class="evf-coupon-apply" data-text="%s" data-coupon="%s">%s</button>',
			esc_attr( $button_label ),
			esc_attr( $primary['id'] ),
			esc_html( $button_label )
		);
		echo '</div>';
		echo '<div class="evf-coupon-error-message-container everest-forms-hidden"></div>';
		echo '<input type="hidden" name="applied_coupon_codes" class="applied-coupon-codes" value="[]">';
		echo '<input type="hidden" name="applied_coupons_data" class="applied-coupons-data" value="[]">';
	}
}
