<?php

namespace EverestForms\Pro\Addons\Calculation\Transpiler;

use EverestForms\Pro\Addons\Calculation\Calculation;
use EverestForms\Pro\Addons\Calculation\Helpers;

/**
 * Custom functions class.
 *
 * @since 1.7.8
 */
class Functions {

	/**
	 * Filtered array of functions allowed in calculations.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $filtered_functions;

	/**
	 * Inner functions class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var InnerFunctions
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
	 * Primary class constructor.
	 *
	 * @since 1.7.8
	 */
	public function __construct() {
		$this->inner_functions = new InnerFunctions();
		$this->helpers         = new Helpers();
	}

	/**
	 * Get default functions as array.
	 *
	 * @since 1.7.8
	 *
	 * @return array Array of functions and their metadata.
	 */
	private function get_default() {
		// phpcs:ignore .Formatting.EmptyLineBeforeReturn.RemoveEmptyLineBeforeReturnStatement
		return array(

			/**
			 * Returns the absolute value of num.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The argument to process.
			 *
			 * @return int|float
			 */
			'abs'      => array(
				'func'     => function ( $num ) {
					return abs( $this->inner_functions->to_float( $num ) );
				},
				'args_num' => 1,
			),

			/**
			 * Returns the average value of given values.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $val1 Value 1.
			 * @param int|float|string $val2 Value 2.
			 *                        ...
			 * @param int|float|string $valN Value N.
			 *
			 * @return int|float
			 */
			'average'  => array(
				'func'     => function () {

					$args = array_map( array( $this->inner_functions, 'to_float' ), func_get_args() );

					return array_sum( $args ) / func_num_args();
				},
				'args_num' => '1+',
			),

			/**
			 * Returns the next highest integer value by rounding up num if necessary.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The argument to process.
			 *
			 * @return float
			 */
			'ceil'     => array(
				'func'     => function ( $num ) {
					return ceil( $this->inner_functions->to_float( $num ) );
				},
				'args_num' => 1,
			),

			/**
			 * Calculates the exponent of e.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The argument to process.
			 *
			 * @return float
			 */
			'exp'      => array(
				'func'     => function ( $num ) {
					return exp( $this->inner_functions->to_float( $num ) );
				},
				'args_num' => 1,
			),

			/**
			 * Round fractions down.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The argument to process.
			 *
			 * @return float
			 */
			'floor'    => array(
				'func'     => function ( $num ) {
					return floor( $this->inner_functions->to_float( $num ) );
				},
				'args_num' => 1,
			),

			/**
			 * Natural logarithm.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The value to calculate the logarithm for.
			 *
			 * @return float
			 */
			'ln'       => array(
				'func'     => function ( $num ) {
					return log( $this->inner_functions->to_float( $num ), M_E );
				},
				'args_num' => 1,
			),

			/**
			 * Base-10 logarithm.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The value to calculate the logarithm for.
			 *
			 * @return float
			 */
			'log'      => array(
				'func'     => function ( $num ) {
					return log( $this->inner_functions->to_float( $num ), 10 );
				},
				'args_num' => 1,
			),

			/**
			 * Find maximum value.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $val1 Value 1.
			 * @param int|float|string $val2 Value 2.
			 * ...
			 * @param int|float|string $valN Value N.
			 *
			 * @return int|float
			 */
			'max'      => array(
				'func'     => function () {

					$args = array_map( array( $this->inner_functions, 'to_float' ), func_get_args() );

					return max( $args );
				},
				'args_num' => '1+',
			),

			/**
			 * Find minimal value.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $val1 Value 1.
			 * @param int|float|string $val2 Value 2.
			 * ...
			 * @param int|float|string $valN Value N.
			 *
			 * @return int|float
			 */
			'min'      => array(
				'func'     => function () {

					$args = array_map( array( $this->inner_functions, 'to_float' ), func_get_args() );

					return min( $args );
				},
				'args_num' => '1+',
			),

			/**
			 * Convert string to a number.
			 *
			 * @since 1.7.8
			 *
			 * @param string           $str       String to convert.
			 * @param int|float|string $precision Round number to $precision decimal digits. Defaults to 14. Optional.
			 *
			 * @return int|float
			 */
			'num'      => array(
				'func'     => function ( $str, $precision = 14 ) {
					$num = $this->inner_functions->to_float( $str, $precision );

					return empty( $num ) ? 0 : $num;
				},
				'args_num' => '1-2',
			),

			/**
			 * Get value of pi.
			 *
			 * @since 1.7.8
			 *
			 * @return float
			 */
			'pi'       => array(
				'func'     => function () {
					return M_PI;
				},
				'args_num' => 0,
			),

			/**
			 * Returns base raised to the power of exponent.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $base     The base.
			 * @param int|float|string $exponent The exponent.
			 *
			 * @return float
			 */
			'pow'      => array(
				'func'     => function ( $base, $exponent ) {

					$base     = $this->inner_functions->to_float( $base );
					$exponent = $this->inner_functions->to_float( $exponent );

					return $base ** $exponent;
				},
				'args_num' => 2,
			),

			/**
			 * Generate a random integer.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $min The lowest value to return (default: 0).
			 * @param int|float|string $max The highest value to return (default: 2147483647).
			 *
			 * @return int
			 */
			'rand'     => array(
				'func'     => function ( $min = 0, $max = 2147483647 ) {

					$min = (int) $this->inner_functions->to_float( $min );
					$max = (int) $this->inner_functions->to_float( $max );

					return wp_rand( $min, $max );
				},
				'args_num' => '0-2',
			),

			/**
			 * Rounds a float.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num       The value to round.
			 * @param int|float|string $precision The optional number of decimal digits to round to.
			 *
			 * @return float
			 */
			'round'    => array(
				'func'     => function ( $num, $precision = 0 ) {

					$num       = $this->inner_functions->to_float( $num );
					$precision = (int) $this->inner_functions->to_float( $precision );

					return $this->inner_functions->round( $num, $precision );
				},
				'args_num' => '1-2',
			),

			/**
			 * Square root.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $num The argument to process.
			 *
			 * @return float
			 */
			'sqrt'     => array(
				'func'     => function ( $num ) {

					$num = $this->inner_functions->to_float( $num );

					return sqrt( $num );
				},
				'args_num' => 1,
			),

			/**
			 * Strips whitespace (or other characters) from the beginning and end of the string.
			 *
			 * @since 1.7.8
			 *
			 * @param string $str The string to process.
			 *
			 * @return string
			 */
			'trim'     => array(
				'func'     => function ( $str ) {
					return trim( $str );
				},
				'args_num' => 1,
			),

			/**
			 * Returns the first length characters of the string.
			 *
			 * @since 1.7.8
			 *
			 * @param string $str    The string to process.
			 * @param int    $length String length limit.
			 *
			 * @return string
			 */
			'truncate' => array(
				'func'     => function ( $str, $length ) {
					return substr( $str, 0, $length );
				},
				'args_num' => 2,
			),

			/**
			 * Concatenates all arguments str1, str2 … strN to one string.
			 *
			 * @since 1.7.8
			 *
			 * @param string $strs Strings to concatenate.
			 *
			 * @return string
			 */
			'concat'   => array(
				'func'     => function ( ...$strs ) {
					return implode( '', $strs );
				},
				'args_num' => '1+',
			),
		);
	}

	/**
	 * Get filtered functions as array.
	 *
	 * @since 1.7.8
	 *
	 * @return array Array of functions.
	 */
	public function get() {
		if ( empty( $this->filtered_functions ) ) {

			/**
			 * Filter functions allowed in calculations.
			 *
			 * @since 1.7.8
			 *
			 * @param array $functions Array of functions allowed in calculations.
			 */
			$this->filtered_functions = apply_filters( 'everest_forms_pro_transpiler_functions_get', $this->get_default() );
		}

		return $this->filtered_functions;
	}

	/**
	 * Get filtered functions names.
	 *
	 * @since 1.7.8
	 *
	 * @return array Array of function names.
	 */
	public function get_names() {
		return array_keys( $this->get() );
	}
}
