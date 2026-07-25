<?php
namespace EverestForms\Pro\Addons\Calculation;

use PhpParser\Node;
use PhpParser\PrettyPrinter;

/**
 * Calculation-related utility functions.
 *
 * This class provides various helper methods to facilitate calculations
 * within forms.
 */
class Helpers {

	/**
	 * Fields allowed in calculations (and their structure).
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	const ALLOWED_FIELDS = array(
		'text'             => array(),
		'select'           => array(),
		'radio'            => array(),
		'checkbox'         => array(),
		'number'           => array(),
		'name'             => array(),
		'first-name'       => array(),
		'lsat-name'        => array(),
		'email'            => array(),
		'url'              => array(),
		'payment-checkbox' => array(),
		'payment-multiple' => array(),
		'payment-single'   => array(),
		'payment-total'    => array(),
		'range-slider'     => array()
	);

	/**
	 * Fields where calculations are allowed.
	 *
	 * @var array
	 */
	private $fields_calculation_is_possible;

	/**
	 * Fields and their structure allowed in calculations.
	 *
	 * @var array
	 */
	private static $allowed_fields;

	/**
	 * Instance of PrettyPrinter\Standard for printing code.
	 *
	 * @var PrettyPrinter\Standard
	 */
	private static $pretty_printer;

	/**
	 * Determines if calculations are enabled in the form.
	 *
	 * @param array $forms Forms or a single form data.
	 *
	 * @return bool
	 */
	public static function calculation_enabled_or_not( $forms ) {
		if ( ! is_array( $forms ) ) {
			return false;
		}

		$forms = isset( $forms['form_fields'] ) ? array( $forms ) : $forms;

		foreach ( $forms as $form ) {
			if ( empty( $form['form_fields'] ) ) {
				continue;
			}

			foreach ( $form['form_fields'] as $field_data ) {
				if ( empty( $field_data['enable_calculation'] ) ) {
					continue;
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Retrieves the fields and their structure allowed in calculations.
	 *
	 * @return array
	 */
	public static function get_fields_allowed_in_calculation() {
		if ( empty( self::$allowed_fields ) ) {
			self::$allowed_fields = (array) apply_filters( 'everest_forms_pro_helpers_get_fields_allowed_in_calculation', self::ALLOWED_FIELDS );
		}

		return self::$allowed_fields;
	}

	/**
	 * Replaces the search pattern in the text while avoiding replacements inside quotes.
	 *
	 * @param string $pattern Search pattern.
	 * @param string $replace Replacement.
	 * @param string $text    Text.
	 *
	 * @return string
	 */
	public static function preg_replace_not_in_quotes( $pattern, $replace, $text ) {
		$lookahead       = '(?=(?:(?:[^"]*"){2})*[^"]*$)';
		$replace_pattern = '/' . $pattern . $lookahead . '/i';

		return preg_replace( $replace_pattern, $replace, $text );
	}

	/**
	 * Print AST node to a string.
	 *
	 * @since 1.0.0
	 *
	 * @param Node[] $nodes AST nodes object.
	 *
	 * @return string
	 */
	public static function print_node( $nodes ) {

		if ( empty( self::$pretty_printer ) ) {
			self::$pretty_printer = new PrettyPrinter\Standard();
		}

		$nodes = is_array( $nodes ) ? $nodes : array( $nodes );
		$code  = self::$pretty_printer->prettyPrint( $nodes );

		return trim( str_replace( '<php', '', $code ) );
	}
}
