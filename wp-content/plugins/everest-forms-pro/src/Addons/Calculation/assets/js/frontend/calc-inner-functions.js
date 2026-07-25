/* global EverestFormsCalculations */

/**
 * Everest Forms Calculation.
 *
 * Calculation Inner functions module.
 *
 * @since 1.7.8
 *
 * @return {Object} Calculation Inner Functions module.
 */
export default function() {

	/**
	 * Math functions.
	 *
	 * @since 1.7.8
	 *
	 * @type {Object}
	 */
	const math = {
		/**
		 * Plus operation for calculation.
		 *
		 * @since 1.7.8
		 *
		 * @param {string|number} left  The left argument.
		 * @param {string|number} right The right argument.
		 *
		 * @return {number} Result.
		 */
		plus( left, right ) {
			const arg = math.getPreparedMathArgs( left, right );

			return math.round( arg.leftNum + arg.rightNum, arg.precision );
		},

		/**
		 * Minus operation for calculation.
		 *
		 * @since 1.7.8
		 *
		 * @param {string|number} left  The left argument.
		 * @param {string|number} right The right argument.
		 *
		 * @return {number} Result.
		 */
		minus( left, right ) {
			const arg = math.getPreparedMathArgs( left, right );

			return math.round( arg.leftNum - arg.rightNum, arg.precision );
		},

		/**
		 * Multiply operation for calculation.
		 *
		 * @since 1.7.8
		 *
		 * @param {string|number} left  The left argument.
		 * @param {string|number} right The right argument.
		 *
		 * @return {number} Result.
		 */
		mul( left, right ) {
			const arg = math.getPreparedMathArgs( left, right );

			return math.round( arg.leftNum * arg.rightNum, arg.precision );
		},

		/**
		 * Division operation for calculation.
		 *
		 * @since 1.7.8
		 *
		 * @param {string|number} left  The left argument.
		 * @param {string|number} right The right argument.
		 *
		 * @return {number} Result.
		 */
		div( left, right ) {
			const arg = math.getPreparedMathArgs( left, right );

			return math.round( arg.leftNum / arg.rightNum, 14 );
		},

		/**
		 * Modulo operation for calculation.
		 *
		 * @since 1.7.8
		 *
		 * @param {string|number} left  The left argument.
		 * @param {string|number} right The right argument.
		 *
		 * @return {number} Result.
		 */
		mod( left, right ) {
			const arg = math.getPreparedMathArgs( left, right );

			return math.round( arg.leftNum % arg.rightNum, arg.precision );
		},

		/**
		 * Rounds a float.
		 *
		 * @since 1.7.8
		 *
		 * @param {number} num       The value to round.
		 * @param {number} precision The optional number of decimal digits to round to.
		 *
		 * @return {number} The rounded value.
		 */
		round( num, precision = 0 ) {
			return Number( Math.round( Number( num + 'e+' + precision ) ) + 'e-' + precision );
		},

		/**
		 * Convert string to a number.
		 *
		 * This function is a wrapper for EverestFormsCalculations.parseFloat,
		 * but in the case of str === '' it returns 0.
		 *
		 * @since 1.7.8
		 *
		 * @param {string} str       String to convert.
		 * @param {number} precision Round number to $precision decimal digits. Defaults to 14. Optional.
		 *
		 * @return {number} Converted number.
		 */
		parseFloat( str, precision = 14 ) {
			return Number( EverestFormsCalculations.parseToFloat( str, precision ));
		},

		/**
		 * Get prepared math operation for calculation arguments.
		 *
		 * @since 1.7.8
		 *
		 * @param {string|number} left  The left argument.
		 * @param {string|number} right The right argument.
		 *
		 * @return {Object} Prepared args.
		 */
		getPreparedMathArgs( left, right ) {
			const leftNum = math.parseFloat( left ),
				rightNum = math.parseFloat( right );

			return {
				leftNum,
				rightNum,
				precision: Math.max( math.getPrecision( leftNum ), math.getPrecision( rightNum ) ),
			};
		},

		/**
		 * Get prepared math operation for calculation arguments.
		 *
		 * @since 1.7.8
		 *
		 * @param {number} num Number value.
		 *
		 * @return {number} Number precision.
		 */
		getPrecision( num ) {
			const chunks = num.toString().split( '.' );

			return chunks[ 1 ] ? chunks[ 1 ].length : 0;
		},
	};

	return { ...math };
}
