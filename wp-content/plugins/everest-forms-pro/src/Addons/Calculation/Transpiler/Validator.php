<?php

namespace EverestForms\Pro\Addons\Calculation\Transpiler;

use EverestForms\Pro\Addons\Calculation\Calculation;
use EverestForms\Pro\Addons\Calculation\Helpers;
use PhpParser\Node;

/**
 * CFL validator class.
 *
 * @since 1.7.8
 */
class Validator {

	/**
	 * Allowed AST node types.
	 *
	 * @since 1.7.8
	 */
	// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar, Squiz.PHP.CommentedOutCode.Found
	const ALLOWED_NODE_TYPES = array(
		'Arg',                          // Function argument
		'Name',                         // Function name

		'Expr_BinaryOp_BooleanAnd',     // &&
		'Expr_BinaryOp_BooleanOr',      // ||
		'Expr_BinaryOp_Div',            // /
		'Expr_BinaryOp_Equal',          // ==
		'Expr_BinaryOp_Greater',        // >
		'Expr_BinaryOp_GreaterOrEqual', // >=
		'Expr_BinaryOp_Minus',          // -
		'Expr_BinaryOp_Mod',            // %
		'Expr_BinaryOp_Mul',            // *
		'Expr_BinaryOp_NotEqual',       // !=
		'Expr_BinaryOp_Plus',           // +
		'Expr_BinaryOp_Smaller',        // <
		'Expr_BinaryOp_SmallerOrEqual', // <=
		'Expr_BooleanNot',              // !
		'Expr_FuncCall',                // function()
		'Expr_UnaryMinus',              // -1
		'Expr_Variable',                // $var

		'Scalar_DNumber',               // 1.2
		'Scalar_Float',                 // 1.2
		'Scalar_Int',                   // 1
		'Scalar_LNumber',               // 1
		'Scalar_String',                // 'string'

		'Stmt_ElseIf',                  // elseif
		'Stmt_Else',                    // else
		'Stmt_Expression',              // expression
		'Stmt_If',                      // if
		'Stmt_Nop',                     // No operation (comments)
	);
	// phpcs:enable Squiz.Commenting.InlineComment.InvalidEndChar, Squiz.PHP.CommentedOutCode.Found

	/**
	 * Validation errors.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $errors;

	/**
	 * The code being validated.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $code;

	/**
	 * Allowed functions array.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $functions;

	/**
	 * Form settings data.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $form_data;

	/**
	 * Fields and their structure allowed in calculations.
	 *
	 * @since 1.7.8
	 *
	 * @var array
	 */
	private $allowed_fields;

	/**
	 * Whether check variables or not.
	 *
	 * @since 1.7.8
	 *
	 * @var bool
	 */
	private $check_vars;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.8
	 *
	 * @param array  $form_data  Form data.
	 * @param array  $functions  Allowed functions array.
	 * @param string $code       Source code.
	 * @param bool   $check_vars Whether check variables or not.
	 */
	public function __construct( $form_data, $functions, $code, $check_vars = true ) {
		$this->code           = $code;
		$this->functions      = $functions;
		$this->form_data      = $form_data;
		$this->check_vars     = $check_vars;
		$this->allowed_fields = Helpers::get_fields_allowed_in_calculation();
	}

	/**
	 * Validate node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_valid( $node ) {
		if ( ! $node instanceof Node ) {
			$this->errors[] = esc_html__( 'Syntax element is not valid', 'everest-forms-pro' );

			return false;
		}

		$type     = $node->getType();
		$location = $this->get_node_location( $node );

		// Check node type.
		if ( ! in_array( $type, self::ALLOWED_NODE_TYPES, true ) ) {
			$this->errors[] = sprintf(
				esc_html__( 'Syntax element `%1$s` is not allowed.', 'everest-forms-pro' ),
				$type
			);

			return false;
		}

		// Check function name.
		if (
			$this->is_function( $node ) && (
				! $this->is_allowed_function( $node ) ||
				! $this->is_correct_function_args_num( $node )
			)
		) {
			return false;
		}

		// Check variable name.
		if ( $this->check_vars && $this->is_var( $node ) && ! $this->is_allowed_var( $node ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Whether node is allowed function call node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	private function is_allowed_function( $node ) {
		if ( ! isset( $node->name ) ) {
			return false;
		}

		$location = $this->get_node_location( $node );

		// Check for the explicit function name.
		if ( ! $node->name instanceof Node\Name ) {
			$this->errors[] = sprintf(
				esc_html__( 'Only explicit function names are allowed.', 'everest-forms-pro' )
			);

			return false;
		}

		$function_name = $node->name->toString();

		// Check for the allowed function name.
		if ( array_key_exists( $function_name, $this->functions ) ) {
			return true;
		}

		$this->errors[] = sprintf(
			esc_html__( 'Function `%1$s` is not allowed.', 'everest-forms-pro' ),
			$function_name
		);

		return false;
	}

	/**
	 * Whether the function has correct args number.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	private function is_correct_function_args_num( $node ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
		if ( ! isset( $node->name, $node->args ) ) {
			return false;
		}

		$location      = $this->get_node_location( $node );
		$function_name = $node->name->toString();
		$args_num      = is_array( $node->args ) ? count( $node->args ) : 0;
		$function      = $this->functions[ $function_name ];

		// Check for the exact number of arguments.
		if ( is_int( $function['args_num'] ) && $function['args_num'] !== $args_num ) {
			$this->errors[] = sprintf(
				esc_html__( 'Function `%1$s` should have %2$s argument(s).', 'everest-forms-pro' ),
				$function_name,
				$function['args_num']
			);

			return false;
		}

		// Check for the minimum number of arguments.
		$at_least_args = null;

		if ( strpos( $function['args_num'], '+' ) !== false ) {
			$at_least_args = absint( explode( '+', $function['args_num'] )[0] );
		}

		if ( is_int( $at_least_args ) && $args_num < $at_least_args ) {
			$this->errors[] = sprintf(
				esc_html__( 'Function `%1$s` should have at least %2$s argument(s).', 'everest-forms-pro' ),
				$function_name,
				$at_least_args
			);

			return false;
		}

		// Check for the range of arguments.
		$min_args = null;
		$max_args = null;

		if ( strpos( $function['args_num'], '-' ) !== false ) {
			$chunks   = explode( '-', $function['args_num'] );
			$min_args = (int) $chunks[0];
			$max_args = (int) $chunks[1];
		}

		if ( is_int( $min_args ) && is_int( $max_args ) && ( $args_num < $min_args || $args_num > $max_args ) ) {
			$this->errors[] = sprintf(
				esc_html__( 'Function `%1$s` should have from %2$s to %3$s argument(s).', 'everest-forms-pro' ),
				$function_name,
				$min_args,
				$max_args
			);

			return false;
		}

		return true;
	}

	/**
	 * Whether node name is valid or not.
	 *
	 * Node name must be $FIELD_1, $FIELD_2, etc.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	private function is_allowed_var( $node ) {
		if ( empty( $node->name ) ) {
			return true;
		}

		$var_name = $node->name;
		$location = $this->get_node_location( $node );

		if ( ! is_string( $node->name ) ) {
			$var_name       = Helpers::print_node( $var_name );
			$this->errors[] = sprintf(
				esc_html__( 'Variable $%1$s is not allowed. Only explicit variable names are allowed.', 'everest-forms-pro' ),
				str_replace( array( '{', '}' ), '', $var_name )
			);

			return false;
		}

		// Match field variable name.
		// All fields variables should start with `FIELD`
		preg_match_all( '/^(FIELD_(\d+))$/', $var_name, $matches );

		$processedMatches = array(
			0 => $matches[0],
			1 => $matches[2],
			2 => array()
		);

		// Check field variable name and get the error message.
		$error = $this->get_field_var_validation_error( $processedMatches );

		if ( ! $error ) {
			return true;
		}

		$this->errors[] = sprintf(
			esc_html__( 'Variable $%1$s is not allowed. %2$s.', 'everest-forms-pro' ),
			$var_name,
			$error
		);

		return false;
	}

	/**
	 * Check variable type is allowed or not.
	 *
	 * @since 1.7.8
	 *
	 * @param array $matches Field variable regexp match result.
	 *
	 * @return string|null
	 */
	private function get_field_var_validation_error( $matches ) {
		// Variable name is not valid.
		if ( empty( $matches[0] ) || ! isset( $matches[1][0] ) ) {
			return esc_html__( 'Variable name is not valid', 'everest-forms-pro' );
		}

		$form_fields_ids = array_keys( $this->form_data ['form_fields'] );
		$field_id        = (int) $matches[1][0];
		$array_id        = array();
		foreach ( $form_fields_ids as $id ) {
			$array_id = explode( '-', $id );

			// Field ID is not valid.
			if ( isset( $array_id[1] ) && $field_id == $array_id[1] ) {

				$matched_field = $this->form_data['form_fields'][ $id ];

				// Field type is not allowed.
				if (
					empty( $matched_field['type'] ) ||
					! array_key_exists( $matched_field['type'], $this->allowed_fields )
				) {
					return sprintf(
						esc_html__( 'Field with ID %1$s has type `%2$s` which is not allowed', 'everest-forms-pro' ),
						$id,
						$matched_field['type']
					);
				}

				// Check subfield.
				return $this->get_field_var_subfield_validation_error( $matches );
			}
		}

			return sprintf( /* translators: %1$s - Field ID. */
				esc_html__( 'Field with ID %1$s does not exist', 'everest-forms-pro' ),
				(int) $matches[1][0]
			);
	}

	/**
	 * Check field variable subfield and get the error message.
	 *
	 * @since 1.7.8
	 *
	 * @param array $matches Field variable regexp match result.
	 *
	 * @return string|null
	 */
	private function get_field_var_subfield_validation_error( $matches ) {
		if ( ! isset( $matches[1][0] ) || empty( $matches[2][0] ) ) {
			return null;
		}

		// Check subfield.
		$field_id          = (int) $matches[1][0];
		$subfield          = substr( $matches[2][0], 1 );
		$subfield          = is_numeric( $subfield ) ? (int) $subfield : $subfield;
		$matched_field     = $this->form_data['form_fields'][ $field_id ];
		$allowed_subfields = $this->allowed_fields[ $matched_field['type'] ];

		if (
			! empty( $matched_field['choices'] ) &&
			empty( $matched_field['dynamic_choices'] ) && // Dynamic choices are not supported.
			in_array( $matched_field['type'], array( 'checkbox', 'payment-checkbox' ), true )
		) {
			$allowed_subfields = array_merge( $allowed_subfields, array_keys( $matched_field['choices'] ) );
		}

		if ( in_array( $subfield, $allowed_subfields, true ) ) {
			return null;
		}

		return sprintf( /* translators: %1$s - Field ID; %2$s - subfield slug. */
			esc_html__( 'Field with ID %1$s does not have subfield `%2$s`', 'everest-forms-pro' ),
			$field_id,
			$subfield
		);
	}

	/**
	 * Whether node is If-elseif-else node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_if( $node ) {
		return $node instanceof Node\Stmt\If_ ||
			$node instanceof Node\Stmt\ElseIf_ ||
			$node instanceof Node\Stmt\Else_;
	}

	/**
	 * Whether node is a math operator node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_math_op( $node ) {
		return $node instanceof Node\Expr\BinaryOp\Plus ||
			$node instanceof Node\Expr\BinaryOp\Minus ||
			$node instanceof Node\Expr\BinaryOp\Mul ||
			$node instanceof Node\Expr\BinaryOp\Div ||
			$node instanceof Node\Expr\BinaryOp\Mod;
	}

	/**
	 * Whether node is assign operation node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_assign( $node ) {
		return ! empty( $node->expr ) && $node->expr instanceof Node\Expr\Assign;
	}

	/**
	 * Whether node is function call node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_function( $node ) {
		return $node instanceof Node\Expr\FuncCall;
	}

	/**
	 * Whether node is variable node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_var( $node ) {
		return $node instanceof Node\Expr\Variable;
	}

	/**
	 * Whether node is Nop node.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return bool
	 */
	public function is_nop( $node ) {
		return $node instanceof Node\Stmt\Nop;
	}

	/**
	 * Reset errors.
	 *
	 * @since 1.7.8
	 */
	public function reset_errors() {
		$this->errors = array();
	}

	/**
	 * Get errors.
	 *
	 * @since 1.7.8
	 *
	 * @return array
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Converts a file offset into a column.
	 *
	 * @since 1.7.8
	 *
	 * @param int $file_pos Position in the code (0-based).
	 * @param int $line     Line number.
	 *
	 * @return null|int 1-based column (relative to start of line).
	 */
	private function get_column_position( $file_pos, $line ) {
		if ( $file_pos > strlen( $this->code ) ) {
			return null;
		}

		$line_start_pos = strrpos( $this->code, "\n", $file_pos - strlen( $this->code ) );

		if ( $line_start_pos === false ) {
			$line_start_pos = - 1;
		}

		$column = $file_pos - $line_start_pos;

		return $line === 1 ? $column - 6 : $column;
	}

	/**
	 * Get node location in `line:column` format.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return string
	 */
	private function get_node_location( $node ) {
		$attr = $node->getAttributes();

		if ( ! isset( $attr['startLine'] ) ) {
			return esc_html__( 'unknown', 'everest-forms-pro' );
		}

		if ( ! isset( $attr['startFilePos'] ) ) {
			return $attr['startLine'];
		}

		return $attr['startLine'] . ':' . $this->get_column_position( $attr['startFilePos'], $attr['startLine'] );
	}
}
