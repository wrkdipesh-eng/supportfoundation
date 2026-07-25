<?php

namespace EverestForms\Pro\Addons\Calculation\Transpiler;

use EverestForms\Pro\Addons\Calculation\Calculation;
use EverestForms\Pro\Addons\Calculation\Helpers;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\ErrorHandler;
use PhpParser\Error;
use PhpParser\Lexer;
use PhpParser\PrettyPrinter;
use PhpParser\NodeTraverser;
use RuntimeException;

/**
 * CFL transpiler class.
 *
 * @since 1.7.8
 */
class Transpiler {

	/**
	 * Result variable name.
	 *
	 * @since 1.7.8
	 */
	const RESULT_VAR_NAME = '_RETURN';

	/**
	 * Functions array name.
	 *
	 * @since 1.7.8
	 */
	const FUNCTIONS_ARRAY_NAME = '_FUNC';

	/**
	 * Internal functions array name.
	 *
	 * @since 1.7.8
	 */
	const INNER_FUNCTIONS_ARRAY_NAME = '_INNER_FUNC';

	/**
	 * Parser instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Parser
	 */
	private $parser;

	/**
	 * Field settings which is currently processing.
	 *
	 * @since 1.7.8
	 *
	 * @var Parser
	 */
	private $field;

	/**
	 * Error handler instance.
	 *
	 * @since 1.7.8
	 *
	 * @var ErrorHandler\Collecting
	 */
	private $error_handler;

	/**
	 * Translated parser errors.
	 *
	 * @since 1.7.8
	 *
	 * @var string[]
	 */
	private $parser_errors;

	/**
	 * Pretty printer instance.
	 *
	 * @since 1.7.8
	 *
	 * @var PrettyPrinter\Standard
	 */
	private $pretty_printer;

	/**
	 * Node traverser instance.
	 *
	 * @since 1.7.8
	 *
	 * @var NodeTraverser
	 */
	private $traverser;

	/**
	 * Node traverser instance.
	 *
	 * @since 1.7.8
	 *
	 * @var NodeVisitor
	 */
	private $node_visitor;

	/**
	 * Functions class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Functions
	 */
	private $functions;

	/**
	 * Helpers class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Helpers
	 */
	private $helpers;

	/**
	 * Validation errors.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $validation_errors;

	/**
	 * Currently processed formula code.
	 *
	 * @since 1.7.8
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Code parsing result. AST nodes.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $nodes;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.8
	 */
	public function __construct() {
		$this->parser         = ( new ParserFactory() )->createForHostVersion();
		$this->error_handler  = new ErrorHandler\Collecting();
		$this->pretty_printer = new PrettyPrinter\Standard();
		$this->traverser      = new NodeTraverser();
		$this->functions      = ( new Functions() )->get();
		$this->helpers        = new Helpers();
	}

	/**
	 * Parse and validate formula code.
	 *
	 * @since 1.7.8
	 *
	 * @param string $code       Formula code.
	 * @param int    $field_id   Field ID.
	 * @param array  $form_data  Form settings data.
	 * @param bool   $check_vars Whether check field variables or not.
	 *
	 * @return array Array of collected errors.
	 */
	public function parse_and_validate_formula_code( $code, $field_id, $form_data, $check_vars = true ) {
		if ( empty( $code ) ) {
			return array();
		}

		if ( ! isset( $form_data['form_fields'][ $field_id ] ) ) {
			return array();
		}

		// Store field settings which is currently processing.
		$this->field = $form_data['form_fields'][ $field_id ];

		// Clear parser errors. We need to do this because we use the same error_handler instance for all fields.
		$this->error_handler->clearErrors();

		// Prepare and parse the code.
		$this->code  = $this->get_prepared_code( $code, $form_data );
		$this->nodes = $this->parser->parse( $this->code, $this->error_handler );

		// Parser error processing.
		if ( $this->error_handler->hasErrors() ) {
			$this->process_parser_errors();

			return $this->parser_errors;
		}
		// Prepare validator and node visitor.
		$validator          = new Validator( $form_data, $this->functions, $this->code, $check_vars );
		$this->node_visitor = new NodeVisitor( $validator );

		// Traverse AST data.
		$this->traverser->addVisitor( $this->node_visitor );
		$this->traverser->traverse( $this->nodes );

		// Get validation errors.
		$this->validation_errors = $validator->get_errors();

		if ( empty( $this->validation_errors ) ) {
			return array();
		}

		return $this->validation_errors;
	}

	/**
	 * Process single field.
	 *
	 * - Parse and validate the formula code.
	 * - Generate `php_code` and `js_code`.
	 * - Update field settings data with the new generated values.
	 *
	 * @since 1.7.8
	 *
	 * @param array $field     Field settings data.
	 * @param array $form_data Form settings data.
	 *
	 * @return array
	 */
	public function process_field( $field, $form_data ) {
		$code = isset( $field['calculation_field'] ) ? trim( $field['calculation_field'] ) : '';

		$errors = $this->parse_and_validate_formula_code( $code, $field['id'], $form_data );

		// There are parser or validation errors, so we need to process them.
		if ( ! empty( $errors ) || empty( $code ) ) {
			// Reset generated executable code.
			$field['php_code'] = '';
			$field['js_code']  = '';

			return $field;
		}

		// There are no parser or validation errors, so we can proceed.

		// Update statements in the root of the nodes tree.
		$nodes = $this->node_visitor->get_updated_statements( $this->nodes );

		// Generate new PHP code.
		$field['php_code'] = $this->pretty_printer->prettyPrintFile( $nodes );

		// Generate new JS code.
		$field['js_code'] = $this->convert_php_to_js( $field['php_code'] );

		// Add backslashes to not lose the special symbols like `\n` and `\t`.
		$field['calculation_field'] = $field['calculation_field'];
		$field['php_code']          = $field['php_code'];
		$field['js_code']           = $field['js_code'];

		return $field;
	}

	/**
	 * Prepare the code before parsing.
	 *
	 * @since 1.7.8
	 *
	 * @param string $code      Source code.
	 * @param array  $form_data Form settings data.
	 *
	 * @return string
	 */
	private function get_prepared_code( $code, $form_data ) {
		$code = '<?php ' . html_entity_decode( $code ) . ';';

		// Replace empty conditions `if()` and `elseif()` with `if(0)` and `elseif(0)`.
		// This is needed to avoid unpredictable resulting code structure since PHP-Parser breaks the if statement in this case.
		$code = $this->helpers->preg_replace_not_in_quotes( '\sif\s*\(\s*\)', 'if(0)', $code );
		$code = $this->helpers->preg_replace_not_in_quotes( 'elseif\s*\(\s*\)', 'elseif(0)', $code );

		// Add semicolon before `if`, `elseif`, `else` and `endif` statements.
		$code = $this->helpers->preg_replace_not_in_quotes( '\s*elseif\s*\(', ";\nelseif(", $code );
		$code = $this->helpers->preg_replace_not_in_quotes( 'else:', ";\nelse:", $code );
		$code = $this->helpers->preg_replace_not_in_quotes( 'endif', ";\nendif", $code );
		$code = $this->helpers->preg_replace_not_in_quotes( '\:', ":\n", $code );
		$code = $this->helpers->preg_replace_not_in_quotes( '\<\?php', "<?php \n", $code );

		/**
		 * Filter the CFL code before transpiling (converting) to PHP.
		 *
		 * @since 1.7.8
		 *
		 * @param string $code      Source code.
		 * @param array  $field     Field settings data.
		 * @param array  $form_data Form settings data.
		 */
		return apply_filters( 'everest_forms_pro_transpiler_get_prepared_code', $code, $this->field, $form_data );
	}

	/**
	 * Check errors collected by the parser.
	 *
	 * @since 1.7.8
	 */
	private function process_parser_errors() {

		if ( ! $this->error_handler->hasErrors() ) {
			return;
		}

		$this->parser_errors = array();
		$error_num           = 0;

		foreach ( $this->error_handler->getErrors() as $error ) {

			$token = str_replace(
				array(
					'Syntax error, unexpected ',
					'Unexpected null byte',
					'Unexpected character',
				),
				array(
					'',
					esc_html__( 'null byte', 'everest-forms-pro' ),
					esc_html__( 'character', 'everest-forms-pro' ),
				),
				$error->getRawMessage()
			);

			++$error_num;

			// Compile translatable error message.
			$message = sprintf( /* translators: %1$s - unexpected token; %2$s - error location `line:column`. */
				esc_html__( 'Unexpected %1$s on line %2$s', 'everest-forms-pro' ),
				$token,
				$this->get_error_location( $error, $this->code )
			);

			$this->parser_errors[] = $message;
		}
	}

	/**
	 * Get parser error location in the code.
	 *
	 * @since 1.7.8
	 *
	 * @param Error  $error Error object.
	 * @param string $code  Source code.
	 *
	 * @return string
	 **/
	private function get_error_location( $error, $code ) {

		if ( ! $error instanceof Error ) {
			return esc_html__( 'unknown', 'everest-forms-pro' );
		}

		$line = $error->getStartLine();

		try {
			$column = $error->getStartColumn( $code );
			$column = $line === 1 ? $column - 6 : $column;

		} catch ( RuntimeException $e ) {
			$column = null;
		}

		return ! is_null( $column ) ? $line . ':' . $column : $line;
	}

	/**
	 * Convert PHP code to JavaScript.
	 *
	 * @since 1.7.8
	 *
	 * @param string $php_code PHP code.
	 *
	 * @return string
	 **/
	private function convert_php_to_js( $php_code ) {
		if ( empty( $php_code ) || ! is_string( $php_code ) ) {
			return '';
		}

		// Remove open PHP tag.
		$js_code = preg_replace( '/^<\?php/i', '', $php_code );

		// Replace `elseif` with `else if`.
		$js_code = $this->helpers->preg_replace_not_in_quotes(
			'\selseif\s*\(',
			"\nelse if (",
			$js_code
		);

		// $js_code = $this->helpers->preg_replace_not_in_quotes( '\\n', '', $js_code );
		// Simplify function call to `$_FUNCTION['function']`.
		return $this->helpers->preg_replace_not_in_quotes(
			'\$\_FUNC\[\'(\w+)\'\]\[\'func\'\]',
			'\$_FUNC[\'$1\']',
			$js_code
		);
	}
}
