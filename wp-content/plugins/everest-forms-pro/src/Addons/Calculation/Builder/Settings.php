<?php
/**
 * Calculations Settings.
 *
 * @package EverestForms_Pro\Calculations
 * @since   1.7.8
 */

namespace EverestForms\Pro\Addons\Calculation\Builder;

use EverestForms\Pro\Addons\Calculation\Transpiler\Functions;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 *
 * @since 1.7.8
 */
class Settings {

	/**
	 * Constructor.
	 *
	 * @since 1.7.8
	 *
	 * @return void
	 */
	public function __construct() {
		// Builder Settings.
		add_action( 'everest_forms_field_options_after_advanced-options', array( $this, 'calculation_field' ), 11, 2 );
	}

	/**
	 * Field Calculations
	 *
	 * @param mixed $field Field.
	 * @param mixed $instance Field Instance.
	 *
	 * @since 1.7.8
	 * @return void
	 */
	public function calculation_field( $field, $instance ) {
		if ( ! in_array( $field['type'], apply_filters( 'everest_forms_calculations_fields', array() ) ) ) {
			return; // Ignore Other Fields.
		}
		?>
		<div class="everest-forms-pro everest-forms-field-option-group everest-forms-field-option-group-calculations everest-forms-hide closed" id="everest-forms-field-option-calculations-<?php echo esc_attr( $field['id'] ); ?>">
			<a href="#" class="everest-forms-field-option-group-toggle">
				<?php esc_html_e( 'Calculations', 'everest-forms-pro' ); ?> <i class="handlediv"></i>
			</a>
			<div class="everest-forms-field-option-group-inner">
				<?php
				$args = array(
					'slug'    => 'field_calculation',
					'content' => $instance->field_element(
						'toggle',
						$field,
						array(
							'slug'    => 'enable_calculation',
							'value'   => ! empty( $field['enable_calculation'] ) ? $field['enable_calculation'] : '',
							'desc'    => __( 'Enable Calculation', 'everest-forms-pro' ),
							'tooltip' => __( 'Check to enable calculation on this field.', 'everest-forms-pro' ),
						),
						false
					),
				);

				$instance->field_element( 'row', $field, $args );

				echo '<div class="evf-field-calculation-container" style="display:' . ( ! empty( $field['enable_calculation'] ) && '1' === $field['enable_calculation'] ? 'block' : 'none' ) . '">';

				$args = array(
					'slug'    => 'decimal_places',
					'content' => $instance->field_element(
						'label',
						$field,
						array(
							'slug'    => 'Decimal Places',
							'value'   => esc_html__( 'Decimal Places', 'everest-forms-pro' ),
							'tooltip' => esc_html__( 'Set decimal places for the calculated value', 'everest-forms-pro' ),
							'default' => 2,
						),
						false
					),
				);

				$instance->field_element( 'row', $field, $args );

				$decimal_places = isset( $field['decimal_places'] ) && ( ! empty( $field['decimal_places'] ) || 0 === (int) $field['decimal_places'] ) ? $field['decimal_places'] : 2;

				$decimal_places = apply_filters( 'everest_forms_change_decimal_places_in_calculation', $decimal_places );

				$args = array(
					'slug'    => 'decimal_places',
					'content' => $instance->field_element(
						'text',
						$field,
						array(
							'slug'     => 'decimal_places',
							'value'    => $decimal_places,
							'min'      => 1,
							'max'      => 99,
							'required' => true,
							'type'     => 'number',
						),
						false
					),
				);

				$instance->field_element( 'row', $field, $args );

				$allowed_function = ( new Functions() )->get();

				$functions_list_with_parentheses = array_map(
					function( $function ) {
						return $function . '()';
					},
					array_keys( $allowed_function )
				);

				$output = '<a href="#" class="evf-toggle-smart-tag-display smart-field-calculation" data-type="calculations"><span class="dashicons dashicons-editor-code"></span></a>';

				$suggestion_list = array( '(', ')', '/', '*', '+', '-', 'if():', 'elseif():', 'else :' );
				$suggestion_list = array_merge( $suggestion_list, $functions_list_with_parentheses );

				$output .= '<div class="evf-calculation-shortcut-container">';
				$output .= '<img class="evf-calculation-shortcut-container-btn" src="' . plugins_url( 'src/Addons/Calculation/assets/img/shortcut list icon 16x16.svg', EFP_PLUGIN_FILE ) . '"/>';
				$output .= '<span>Expressions</span>';
				$output .= '</div>';
				$output .= '<ul class="evf-calculation-shortcut-list">';

				foreach ( $suggestion_list as $list ) {
					$output .= '<li class="evf-calculation-shortcut-item">' . esc_html( $list ) . '</li>';
				}

				$output .= '</ul>';

				$output .= $instance->field_element(
					'textarea',
					$field,
					array(
						'slug'    => 'calculation_field',
						'value'   => ! empty( $field['calculation_field'] ) ? $field['calculation_field'] : '',
						'desc'    => __( 'Enable Calculation', 'everest-forms-pro' ),
						'tooltip' => __( 'Write your formula', 'everest-forms-pro' ),
					),
					false
				);

				$output .= '<div class="evf-smart-tag-lists top" style="">';

				$output .= '<ul class="calculations"></ul>';

				$output .= '</div>';

				$output .= '<div class="everest-forms-calculations-below-editor">';
				$output .= '<div class="everest-forms-pro-validate-formula-wrap">';
				$output .= '<button type="button" class="everest-forms-pro-validate-formula">' . esc_html__( 'Validate Formula', 'everest-forms-pro' ) . '</button>';
				$output .= '<span class="everest-forms-pro-validate-formula-status"></span></div></div>';

				$args = array(
					'slug'    => 'calculation_field',
					'content' => $output,
				);

				$instance->field_element( 'row', $field, $args );

				echo '</div>';
				?>
			</div>
		</div>
		<?php
	}
}
