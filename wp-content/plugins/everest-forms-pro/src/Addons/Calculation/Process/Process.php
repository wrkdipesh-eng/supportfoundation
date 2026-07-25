<?php
/**
 * Calculations Process.
 *
 * @package EverestForms_Pro\Calculations
 * @since   1.7.8
 */

namespace EverestForms\Pro\Addons\Calculation\Process;

use EverestForms\Pro\Addons\Calculation\Helpers;
use EverestForms\Pro\Addons\Calculation\Transpiler\Functions;
use EverestForms\Pro\Addons\Calculation\Transpiler\InnerFunctions;
use EverestForms\Pro\Addons\Calculation\Transpiler\Transpiler;

defined( 'ABSPATH' ) || exit;

/**
 * Process Class.
 *
 * @since 1.7.8
 */
class Process {

	/**
	 * Functions array.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $functions;

	/**
	 * Inner functions array.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $inner_functions;

	/**
	 * Helpers class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Helpers
	 */
	private $helpers;

	/**
	 * Constructor.
	 *
	 * @since 1.7.8
	 *
	 * @return void
	 */
	public function __construct() {
		$this->functions       = ( new Functions() )->get();
		$this->inner_functions = ( new InnerFunctions() )->get();
		$this->helpers         = new Helpers();

		add_filter( 'everest_forms_calculations_fields', array( $this, 'calculations_fields' ) );
		add_filter( 'everest_forms_field_properties', array( $this, 'field_properties' ), 10, 3 );
		add_filter( 'everest_forms_process_filter', array( $this, 'process_filter' ), 10, 3 );
		add_filter( 'everest_forms_process_smart_tags', array( $this, 'process_smart_tags' ), 1, 3 );
	}

	/**
	 * Process Smart Tags for Repeater Fields and Calculations.
	 *
	 * @param mixed $content content.
	 * @param mixed $form_data Form Data.
	 * @param array $form_fields Form Fields.
	 * @return string $content Content.
	 */
	public function process_smart_tags( $content, $form_data, $form_fields = array() ) {
		if ( empty( $form_data['current_field'] ) ) {
			return $content;
		}

		preg_match_all( '/\{field_id="(.+?)"\}/', $content, $ids );

		if ( ! empty( $ids[1] ) && ! empty( $form_fields ) ) {
			foreach ( $ids[1] as $key => $field_id ) {
				$mixed_field_id = explode( '_', $field_id );
				if ( ! empty( $form_data['form_fields'][ $mixed_field_id[1] ] ) && ! empty( $form_data['form_fields'][ $mixed_field_id[1] ]['repeater-fields'] ) && 'yes' === $form_data['form_fields'][ $mixed_field_id[1] ]['repeater-fields'] ) {
					preg_match_all( '/\-\-(\d+)$/', $form_data['current_field'], $added_key );
					$old_id  = $mixed_field_id[0] . '_' . $mixed_field_id[1];
					$new_id  = $old_id . '--' . $added_key[1][0];
					$content = str_replace( $old_id, $new_id, $content );
				}
			}
		}

		preg_match_all( '/\{field_id="(.+?)"\}/', $content, $ids );

		if ( ! empty( $ids[1] ) && ! empty( $form_fields ) ) {
			foreach ( $ids[1] as $key => $field_id ) {
				$mixed_field_id = explode( '_', $field_id );
				if ( ! empty( $form_fields[ $mixed_field_id[1] ] ) ) {
					$field        = $form_fields[ $mixed_field_id[1] ];
					$field_option = $form_data['form_fields'][ $mixed_field_id[1] ];
					if ( 'payment-checkbox' === $field_option['type'] || 'payment-multiple' === $field_option['type'] ) {
						$content = str_replace( '{field_id="' . $field_id . '"}', $field['amount_raw'], $content );
					}
				}
			}
		}

		return $content;
	}

	/**
	 * Calculations Fields Filter.
	 *
	 * @param array $fields Calculation Fields.
	 */
	public function calculations_fields( $fields = array() ) {
		return array_merge(
			$fields,
			array(
				'number',
				'payment-single',
			)
		);
	}

	/**
	 * Build field variable assignments for eval().
	 *
	 * All field values are cast strictly to (float) or 0.
	 * String values are never injected into the eval() context,
	 * eliminating any possibility of string breakout or code injection
	 * regardless of user input.
	 *
	 * @param array $form_data   Form configuration data.
	 * @param array $form_fields Processed form field values.
	 * @param array $entry       Raw entry data.
	 * @return string PHP variable assignment statements.
	 */
	private function build_field_variables( $form_data, $form_fields, $entry ) {
		$excludeField = array(
			'credit-card',
			'square-payment',
			'authorize-net',
			'textarea',
			'privacy-policy',
			'signature',
			'address',
			'phone',
			'date-time',
			'wysiwyg',
			'color',
			'country',
			'yes-no',
			'likert',
			'image-upload',
			'file-upload',
			'scale-rating',
		);

		$field_variable = array();

		foreach ( $form_data['form_fields'] as $field_id => $form_field_data ) {
			$field_id_arr = explode( '-', $field_id );
			$cal_field_id     = isset( $field_id_arr[1] ) ? $field_id_arr[1] : $field_id_arr[0];
			$var_name     = '$FIELD_' . $cal_field_id;
			$field_type   = $form_field_data['type'];
			$field_value  = ! empty( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : 0;

			if ( in_array( $field_type, $excludeField, true ) ) {
				continue;
			}

			if ( ! is_array( $field_value ) ) {
				$field_value = trim( preg_replace( '/\$/', '', $field_value ) );

				switch ( $field_type ) {
					case 'text':
					case 'select':
					case 'url':
					case 'email':
					case 'first-name':
					case 'last-name':
					case 'name':
					case 'radio':
						// FIX: Never inject strings into eval(). Cast to float or 0
						// to eliminate any string quoting context and prevent
						// any possibility of string breakout or code injection.
						$field_variable[] = "$var_name = " . ( is_numeric( $field_value ) ? (float) $field_value : 0 );
						break;

					case 'payment-multiple':
						$field_value = ! empty( $form_field_data['choices'][ $field_value ]['value'] )
							? $form_field_data['choices'][ $field_value ]['value']
							: 0;
						// FIX: Never inject strings into eval(). Cast to float or 0.
						$field_variable[] = "$var_name = " . ( is_numeric( $field_value ) ? (float) $field_value : 0 );
						break;

					default:
						if ( is_numeric( $field_value ) ) {
							$field_variable[] = "$var_name = " . (float) $field_value;
						} else {
							$field_variable[] = "$var_name = 0";
						}
						break;
				}
			} else {
				switch ( $field_type ) {
					case 'payment-checkbox':
						$field_value = ! empty( $form_fields[ $field_id ]['value']['amount'] )
							? $form_fields[ $field_id ]['value']['amount']
							: 0;
						if ( is_numeric( $field_value ) ) {
							$field_variable[] = "$var_name = " . (float) $field_value;
						} else {
							$field_variable[] = "$var_name = 0";
						}
						break;

					default:
						break;
				}
			}
		}

		return implode( ";\n", $field_variable );
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
		$payment_fields = apply_filters( 'everest_forms_calculations_fields', array() );

		if ( ! empty( $form_data['form_fields'] ) ) {

			foreach ( $form_data['form_fields'] as $key => $field ) {
				if ( 'repeater-fields' === $field['type'] && ! empty( $form_fields[ $field['id'] ]['value_raw'] ) ) {
					foreach ( $form_fields[ $field['id'] ]['value_raw'] as $row_key => $row ) {
						foreach ( $row as $field_key => $field_value ) {
							$field_data                                    = $form_data['form_fields'][ $field_key ];
							$field_value['id']                             = $field_key . '--' . $row_key;
							$field_data['id']                              = $field_value['id'];
							$form_fields[ $field_value['id'] ]             = $field_value;
							$post_fields[]                                 = $field_value['id'];
							$field_data['repeater']['id']                  = $key;
							$field_data['repeater']['row']                 = $row_key;
							$field_data['repeater']['field_id']            = $field_key;
							$form_data['form_fields'][ $field_data['id'] ] = $field_data;
							$form_data['added_fields'][]                   = $field_data['id'];
						}
					}
				}
			}

			$calculation_fields = array();
			$fieldIndex         = 0;

			foreach ( $form_data['form_fields'] as $key => $field ) {
				if ( ! in_array( $field['id'], $post_fields, true ) ) {
					continue;
				}
				if ( in_array( $field['type'], $payment_fields ) && ! empty( $field['enable_calculation'] ) && '1' === $field['enable_calculation'] ) {
					$calculation_fields[ $fieldIndex ] = $field;
					$fieldIndex++;
				}
			}

			ksort( $calculation_fields );

			$form_data['for_calculations'] = true;

			foreach ( $calculation_fields as $field ) {
				$form_data['current_field'] = $field['id'];

				if ( isset( $field['php_code'] ) && isset( $field['js_code'] ) && ! empty( $field['php_code'] ) && ! empty( $field['js_code'] ) ) {

					$php_code              = $field['php_code'];
					$final_field_variables = $this->build_field_variables( $form_data, $form_fields, $entry );

					$return_var                               = Transpiler::RESULT_VAR_NAME;
					${Transpiler::FUNCTIONS_ARRAY_NAME}       = $this->functions;
					${Transpiler::INNER_FUNCTIONS_ARRAY_NAME} = $this->inner_functions;

					// FIX: Use str_replace() instead of preg_replace() to prevent
					// backslash sequence interpretation in the replacement string,
					// which was undoing addslashes() escaping and enabling string breakout.
					$php_code = str_replace(
						'<?php',
						$final_field_variables . '; $_RETURN = 0;',
						$php_code . 'return $' . $return_var . ';'
					);

					try {
						$decimal_places = ! empty( $field['decimal_places'] ) ? $field['decimal_places'] : 2;
						$total          = eval( $php_code );
						$total          = empty( $total ) ? 0 : $total;
						$total          = round( $total, $decimal_places );
					} catch ( \Exception $e ) {
						evf_get_logger()->critical(
							$e->getMessage(),
							array( 'source' => 'form-submission' )
						);
						$total = 0;
					}

					if ( empty( $total ) && ! empty( $form_data[ $field['id'] ]['default'] ) ) {
						$total = $form_data[ $field['id'] ]['default'];
					}

				} else {

					$formula = apply_filters( 'everest_forms_process_smart_tags', $field['calculation_field'], $form_data, $form_fields );
					$formula = preg_replace( '/\s+|\&.*?\;/', '', $formula );
					$total   = 0;

					if ( preg_match( '/[+-\/*%]{2}/', $formula ) ) {
						preg_match_all( '/[+-\/*%]{2}/', $formula, $matches );
						if ( $matches ) {
							foreach ( $matches[0] as $match ) {
								$matchArr = str_split( $match );
								$replace  = $matchArr[0] . '0' . $matchArr[1];
								$formula  = str_replace( $match, $replace, $formula );
							}
						}
					}

					if ( preg_match( '/[+-\/*%]$/', $formula ) ) {
						$formula .= '0';
					}

					if ( preg_match( '/^[+-\/*%]/', $formula ) ) {
						$formula = '0' . $formula;
					}

					if ( preg_match( '/[+-\/*]+\)|\([+-\/*]+/', $formula ) ) {
						preg_match_all( '/[+-\/*]+\)|\([+-\/*]+/', $formula, $matches );
						if ( $matches ) {
							foreach ( $matches[0] as $match ) {
								$matchArr = str_split( $match );
								$replace  = $matchArr[0] . '0' . $matchArr[1];
								$formula  = str_replace( $match, $replace, $formula );
							}
						}
					}

					$formula = str_replace( ',', '.', $formula );

					if ( ! empty( $formula ) ) {
						try {
							$evaluator = new \Matex\Evaluator();
							$total     = $evaluator->execute( $formula );
							$decimal   = $field['decimal_places'];
							$total     = round( $total, ! empty( $decimal ) ? $decimal : 2 );
						} catch ( \Exception $e ) {
							evf_get_logger()->critical(
								$e->getMessage(),
								array( 'source' => 'form-submission' )
							);
						}
					}

					if ( empty( $total ) && ! empty( $form_data[ $field['id'] ]['default'] ) ) {
						$total = $form_data[ $field['id'] ]['default'];
					}
				}

				if ( preg_match( '/\-\-/', $field['id'] ) ) {
					if ( 'payment-single' === $form_fields[ $field['repeater']['id'] ]['value_raw'][ $field['repeater']['row'] ][ $field['repeater']['field_id'] ] ) {
						$form_fields[ $field['repeater']['id'] ]['value_raw'][ $field['repeater']['row'] ][ $field['repeater']['field_id'] ]['value']      = preg_replace( '/\;.*$/', '; ' . $total, $form_fields[ $field['repeater']['id'] ][ $field['repeater']['row'][ $field['repeater']['field_id'] ] ]['value'] );
						$form_fields[ $field['repeater']['id'] ]['value_raw'][ $field['repeater']['row'] ][ $field['repeater']['field_id'] ]['amount']     = $total;
						$form_fields[ $field['repeater']['id'] ]['value_raw'][ $field['repeater']['row'] ][ $field['repeater']['field_id'] ]['amount_raw'] = $total;
					} else {
						$form_fields[ $field['repeater']['id'] ]['value_raw'][ $field['repeater']['row'] ][ $field['repeater']['field_id'] ]['value'] = $total;
					}
				} else {
					if ( 'payment-single' === $form_fields[ $field['id'] ]['type'] ) {
						$form_fields[ $field['id'] ]['value']      = preg_replace( '/\;.*$/', '; ' . $total, $form_fields[ $field['id'] ]['value'] );
						$form_fields[ $field['id'] ]['amount']     = $total;
						$form_fields[ $field['id'] ]['amount_raw'] = $total;
					} else {
						$form_fields[ $field['id'] ]['value'] = $total;
					}
				}
			}
		}

		if ( ! empty( $form_data['added_fields'] ) ) {
			foreach ( $form_data['added_fields'] as $field ) {
				unset( $form_fields[ $field ] );
			}
		}

		return $form_fields;
	}

	/**
	 * Disabled calculation field.
	 *
	 * @param mixed $properties Field Properties.
	 * @param mixed $field Field.
	 * @param mixed $form_data Form Data.
	 * @return mixed $properties Field Properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {

		$calculations_field = apply_filters( 'everest_forms_calculations_fields', array() );

		if ( in_array( $field['type'], $calculations_field ) && ! empty( $field['enable_calculation'] ) && '1' === $field['enable_calculation'] ) {
			$properties['inputs']['primary']['attr']['readonly']            = 'readonly';
			$properties['inputs']['primary']['class'][]                     = 'formula-field';
			$properties['inputs']['primary']['attr']['decimal_places']      = $field['decimal_places'];
			$properties['inputs']['primary']['attr']['calculation_formula'] = isset( $field['js_code'] ) && ! empty( $field['js_code'] )
																				? $field['js_code']
																				: ( isset( $field['calculation_field'] ) && ! empty( $field['calculation_field'] )
																					? $field['calculation_field']
																					: '' );
		}

		if ( in_array( $field['type'], $calculations_field ) ) {
			if ( in_array( $field['type'], array( 'payment-checkbox' ) ) ) {
				$properties['inputs'][ $key ]['class'][] = 'evf-calculation-field';
			} else {
				$properties['inputs']['primary']['class'][] = 'evf-calculation-field';
			}
		}

		return $properties;
	}
}
