<?php

namespace EverestForms\Pro\Addons\Calculation\Transpiler;

/**
 * Inner functions class.
 *
 * @since 1.7.8
 */
class InnerFunctions {

	/**
	 * Convert the given string to a number.
	 *
	 * @since 1.7.8
	 *
	 * @param mixed $str       String to convert.
	 * @param int   $precision Precision.
	 *
	 * @return float|string Floating point number value OR empty string.
	 */
	public function parse_float( $str, $precision = 14 ) {
		if ( empty( $str ) && ! in_array( $str, array( 0, '0' ), true ) ) {
			return '';
		}

		$precision = $precision || $precision === 0 ? (int) $precision : 14;

		if ( is_numeric( $str ) ) {
			return $this->round( $str, $precision );
		}

		$default_currency = array(
			'symbol'              => '&#36;',
			'symbol_pos'          => 'left',
			'thousands_separator' => ',',
			'decimal_separator'   => '.',
			'decimals'            => 2,
		);

		$currency_code = strtoupper( get_option( 'everest_forms_currency' ) );
		$currencies    = evf_get_currencies();
		$currency      = $currencies[ $currency_code ] ?? $default_currency;
		$currency      = wp_parse_args( $currency, $default_currency );

		$symbol       = html_entity_decode( $currency['symbol'] ?? '$' );
		$str          = str_replace( $currency['symbol'], $symbol, (string) $str );
		$slash_symbol = str_replace( '$', '\$', $symbol );
		$left_symbol  = $currency['symbol_pos'] === 'left' ? $slash_symbol . '[ ]?' : '';
		$right_symbol = $currency['symbol_pos'] === 'right' ? '[ ]?' . $slash_symbol : '';
		$matches      = array();

		$amount_pattern =
			"#(-?$left_symbol(\\d*)([{$currency['thousands_separator']}]?\\d{3})*([{$currency['decimal_separator']}]\\d*)?($right_symbol))" .
			'|(-?(\\d+)(,?\\d{3})*([.]?\\d*)?)#';

		preg_match_all( $amount_pattern, $str, $matches );

		if ( empty( $matches[0] ) || ! is_array( $matches[0] ) ) {
			return '';
		}

		$matches[0] = array_filter( $matches[0] );
		$found      = reset( $matches[0] );

		if ( ! $found ) {
			return '';
		}

		if ( strpos( $found, $symbol ) !== false ) {
			return evf_sanitize_amount( $found );
		}

		$found = str_replace( array( ',', ' ' ), '', $found );

		return $this->round( $found, $precision );
	}

	/**
	 * Convert the given string to a floating point number.
	 *
	 * @since 1.7.8
	 *
	 * @param mixed $str       String to convert.
	 * @param int   $precision Precision.
	 *
	 * @return float Floating point number.
	 */
	public function to_float( $str, $precision = 14 ): float {
		return (float) $this->parse_float( $str, $precision );
	}

	/**
	 * Rounds a float.
	 *
	 * @since 1.7.8
	 *
	 * @param int|float|string $num       The value to round.
	 * @param int              $precision Precision.
	 *
	 * @return float Floating point number.
	 */
	public function round( $num, $precision = 14 ): float {
		return round( (float) $num, $precision, $num < 0 ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP );
	}

	/**
	 * Get the number precision.
	 *
	 * @since 1.7.8
	 *
	 * @param int|float $num Number value.
	 *
	 * @return int Number precision.
	 */
	public function get_precision( $num ): int {
		if ( ! is_numeric( $num ) ) {
			return 0;
		}

		$chunks = explode( '.', (string) $num );

		return strlen( $chunks[1] ?? '' );
	}

	/**
	 * Get prepared math operation arguments.
	 *
	 * @since 1.7.8
	 *
	 * @param int|float|string $left  The left argument.
	 * @param int|float|string $right The right argument.
	 *
	 * @return array Prepared args.
	 */
	private function get_prepared_math_args( $left, $right ) {
		$left_num  = $this->to_float( $left );
		$right_num = $this->to_float( $right );

		return array(
			'left_num'  => $left_num,
			'right_num' => $right_num,
			'precision' => max( $this->get_precision( $left_num ), $this->get_precision( $right_num ) ),
		);
	}

	/**
	 * Get inner functions as array.
	 *
	 * @since 1.7.8
	 *
	 * @return array Array of functions and their metadata.
	 */
	public function get() {
		return array(

			/**
			 * Plus operation.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $left  The left argument.
			 * @param int|float|string $right The right argument.
			 *
			 * @return int|float
			 */
			'plus'  => function ( $left, $right ) {

				$arg = $this->get_prepared_math_args( $left, $right );

				return $this->round( $arg['left_num'] + $arg['right_num'], $arg['precision'] );
			},

			/**
			 * Minus operation.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $left  The left argument.
			 * @param int|float|string $right The right argument.
			 *
			 * @return int|float
			 */
			'minus' => function ( $left, $right ) {

				$arg = $this->get_prepared_math_args( $left, $right );

				return $this->round( $arg['left_num'] - $arg['right_num'], $arg['precision'] );
			},

			/**
			 * Multiply operation.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $left  The left argument.
			 * @param int|float|string $right The right argument.
			 *
			 * @return int|float
			 */
			'mul'   => function ( $left, $right ) {

				$arg = $this->get_prepared_math_args( $left, $right );

				return $this->round( $arg['left_num'] * $arg['right_num'], $arg['precision'] );
			},

			/**
			 * Division operation.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $left  The left argument.
			 * @param int|float|string $right The right argument.
			 *
			 * @return int|float
			 */
			'div'   => function ( $left, $right ) {

				$arg = $this->get_prepared_math_args( $left, $right );

				return $this->round( $arg['left_num'] / $arg['right_num'], 14 );
			},

			/**
			 * Modulo operation.
			 *
			 * @since 1.7.8
			 *
			 * @param int|float|string $left  The left argument.
			 * @param int|float|string $right The right argument.
			 *
			 * @return int|float
			 */
			'mod'   => function ( $left, $right ) {

				$arg = $this->get_prepared_math_args( $left, $right );

				$div = $arg['left_num'] / $arg['right_num'];
				$mod = $arg['left_num'] - floor( $div ) * $arg['right_num'];

				return $this->round( $mod, $arg['precision'] );
			},
		);
	}
}
