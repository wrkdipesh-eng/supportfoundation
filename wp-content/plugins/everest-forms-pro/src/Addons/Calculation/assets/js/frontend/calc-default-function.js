/* global EverestFormsCalculations */

export default function() {
	/**
	 * Default functions for calculation.
	 *
	 * @since 1.7.8
	 *
	 * @type {Object}.
	 */
		const defaultFunctions = {

			/**
			 * Returns the absolute value of num.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The argument to process.
			 *
			 * @return {number} The absolute value of num.
			 */
			abs( num ) {
				num = EverestFormsCalculations.mathInnerFunctions.parseFloat( num );

				return Math.abs( num );
			},

			/**
			 * Returns the average value of given values.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} values Values.
			 *
			 * @return {number} The average value of given values.
			 */
			// eslint-disable-next-line no-unused-vars
			average( ...values ) {
				return values.reduce( ( x, y ) => EverestFormsCalculations.mathInnerFunctions.plus( x, y ) ) / arguments.length;
			},

			/**
			 * Returns the next highest integer value by rounding up num if necessary.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The argument to process.
			 *
			 * @return {number} The next highest integer value by rounding up num if necessary.
			 */
			ceil( num ) {
				return Math.ceil( EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );
			},

			/**
			 * Calculates the exponent of e.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The argument to process.
			 *
			 * @return {number} The exponent of e.
			 */
			exp( num ) {
				return Math.exp( EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );
			},

			/**
			 * Round fractions down.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The argument to process.
			 *
			 * @return {number} The rounded value.
			 */
			floor( num ) {
				return Math.floor( EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );
			},

			/**
			 * Natural logarithm.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The value to calculate the logarithm for.
			 *
			 * @return {number} The natural logarithm of num.
			 */
			ln( num ) {
				return Math.log( EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );
			},

			/**
			 * Base-10 logarithm.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The value to calculate the logarithm for.
			 *
			 * @return {number} The base-10 logarithm of num.
			 */
			log( num ) {
				return Math.log10( EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );
			},

			/**
			 * Find highest value.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} values Values.
			 *
			 * @return {number} The highest value.
			 */
			max( ...values ) {
				values = values.map( ( num ) => EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );

				return Math.max( ...values );
			},

			/**
			 * Find lowest value.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} values Values.
			 *
			 * @return {number} The lowest value.
			 */
			min( ...values ) {
				values = values.map( ( num ) => EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );

				return Math.min( ...values );
			},

			/**
			 * Convert string to a number.
			 *
			 * @since 1.7.8
			 *
			 * @param {string} str       String to convert.
			 * @param {number} precision Round number to $precision decimal digits. Default is 14. Optional.
			 *
			 * @return {number} The converted number.
			 */
			num( str, precision ) {
				return EverestFormsCalculations.mathInnerFunctions.parseFloat( str, precision );
			},

			/**
			 * Get value of pi.
			 *
			 * @since 1.7.8
			 *
			 * @return {number} The value of pi.
			 */
			pi() {
				return Math.PI;
			},

			/**
			 * Returns base raised to the power of exponent.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} base     The base.
			 * @param {number} exponent The exponent.
			 *
			 * @return {number} The base raised to the power of exponent.
			 */
			pow( base, exponent ) {
				return Math.pow( EverestFormsCalculations.mathInnerFunctions.parseFloat( base ), EverestFormsCalculations.mathInnerFunctions.parseFloat( exponent ) );
			},

			/**
			 * Generate a random integer.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} min The lowest value to return (default: 0).
			 * @param {number} max The highest value to return (default: 2147483647).
			 *
			 * @return {number} A random integer.
			 */
			rand( min = 0, max = 2147483647 ) {
				min = Math.ceil( EverestFormsCalculations.mathInnerFunctions.parseFloat( min ) );
				max = Math.floor( EverestFormsCalculations.mathInnerFunctions.parseFloat( max ) );

				return Math.floor( Math.random() * ( max - min + 1 ) ) + min;
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
				return EverestFormsCalculations.mathInnerFunctions.round(
					EverestFormsCalculations.mathInnerFunctions.parseFloat( num ),
					EverestFormsCalculations.mathInnerFunctions.parseFloat( precision )
				);
			},

			/**
			 * Square root.
			 *
			 * @since 1.7.8
			 *
			 * @param {number} num The argument to process.
			 *
			 * @return {number} The square root of num.
			 */
			sqrt( num ) {
				return Math.sqrt( EverestFormsCalculations.mathInnerFunctions.parseFloat( num ) );
			},

			/**
			 * Strips whitespace (or other characters) from the beginning and end of the string.
			 *
			 * @since 1.7.8
			 *
			 * @param {string} str The string to process.
			 *
			 * @return {string} Trimmed string.
			 */
			trim( str ) {
				return str.trim();
			},

			/**
			 * Returns the first length characters of the string.
			 *
			 * @since 1.7.8
			 *
			 * @param {string} str    The string to process.
			 * @param {number} length String length limit.
			 *
			 * @return {string} Truncated string.
			 */
			truncate( str, length ) {
				return str.substring( 0, length );
			},

			/**
			 * Concatenates all arguments str1, str2 … strN to one string.
			 *
			 * @since 1.7.8
			 *
			 * @param {string} strs Strings to concatenate.
			 *
			 * @return {string} Concatenated string.
			 */
			concat( ...strs ) {
				return ''.concat( ...strs );
			},
		};

		return defaultFunctions;
}
