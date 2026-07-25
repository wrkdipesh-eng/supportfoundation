<?php
/**
 * Progress field.
 *
 * @package EverestForms_Pro\Fields
 * @since   1.5.9
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_progress class.
 */
class EVF_Field_Progress extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = esc_html__( 'Progress', 'everest-forms-pro' );
		$this->type     = 'progress';
		$this->icon     = 'evf-icon evf-icon-progress';
		$this->order    = 200;
		$this->group    = 'advanced';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
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
	 * @since 1.2.0
	 *
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$properties['container']['class'][] = 'evf-progress-picker';

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

		if ( ! empty( $form_data['form_fields'] ) ) {

			$is_progress_field = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type' => 'progress',
				)
			);
		}
		if ( empty( $is_progress_field ) ) {
			return $form_fields;
		}

		$field_values    = array();
		$progress_fields = array();
		$total_fields    = 0;

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $field_id => $field ) {
				if ( 'payment-total' === $field['type'] || 'payment-subtotal' === $field['type'] || 'progress' === $field['type'] || 'title' === $field['type'] || 'html' === $field['type'] || 'divider' === $field['type'] || 'hidden' === $field['type'] || ( 'payment-single' === $field['type'] && ( isset( $form_data['form_fields'][ $field_id ]['item_type'] ) && 'user' !== $form_data['form_fields'][ $field_id ]['item_type'] ) ) ) {
					if ( 'progress' === $field['type'] ) {
						$progress_fields [] = $field_id;
					}
					continue;
				} else {
					$total_fields++;
				}

				if ( 'image-upload' === $field['type'] || 'file-upload' === $field['type'] ) {
					if ( ! empty( $field['value'] ) ) {
						$field_values [] = $field_id;
					}
				} elseif ( 'number' === $field['type'] ) {
					if ( is_numeric( $field['value'] ) ) {
						$field_values [] = $field_id;
					}
				} else {
					if ( ! empty( $entry['form_fields'][ $field_id ] ) ) {
						if ( 'payment-checkbox' === $field['type'] || 'payment-multiple' === $field['type'] ) {
							if ( 0 < floatval( $field['value']['amount'] ) ) {
								$field_values [] = $field_id;
							}
						} elseif ( 'payment-single' === $field['type'] ) {
							if ( 0 < floatval( $field['amount'] ) ) {
								$field_values [] = $field_id;
							}
						} elseif ( 'payment-quantity' === $field['type'] || 'payment-coupon' === $field['type'] || 'number' === $field['type'] ) {
							if ( ! empty( $field['value'] ) ) {
								$field_values [] = $field_id;
							}
						} else {
							$field_values [] = $field_id;
						}
					}
				}
			}
		}

		$completed_fields     = count( $field_values );
		$completed_percentage = ceil( ( $completed_fields / $total_fields ) * 100 ) . '%';

		if ( ! empty( $progress_fields ) ) {
			foreach ( $progress_fields as $field_id ) {
				$form_fields[ $field_id ]['value'] = $completed_percentage;
			}
		}

		return $form_fields;
	}
	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		// Label.
		$this->field_preview_option( 'label', $field );

		// Primary input.
		echo '<progress value="0" max="100"></progress><span class="evf-progress-percentage">0%</span>';

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
		// Define data.
		$primary                  = $field['properties']['inputs']['primary'];
		$primary['attr']['value'] = '0';

		// Primary field.
		printf(
			'<progress max="100" class="evf-progress" value="%s"></progress><span class="evf-progress-percentage">%s</span><input type="hidden" %s>',
			esc_attr( $primary['attr']['value'] ),
			esc_attr( $primary['attr']['value'] . '%' ),
			evf_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] )
		);
	}
}
