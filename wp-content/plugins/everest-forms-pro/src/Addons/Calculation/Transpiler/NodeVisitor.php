<?php

namespace EverestForms\Pro\Addons\Calculation\Transpiler;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;

/**
 * CFL transpiler. Node visitor.
 *
 * @since 1.7.8
 */
class NodeVisitor extends NodeVisitorAbstract {

	/**
	 * Result of node validation.
	 *
	 * @since 1.7.8
	 *
	 * @var bool
	 */
	private $node_is_valid;

	/**
	 * Validator class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Validator
	 */
	private $validator;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.8
	 *
	 * @param Validator $validator Validator class instance.
	 */
	public function __construct( $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Before traversal.
	 *
	 * @since 1.7.8
	 *
	 * @param Node[] $nodes Nodes array.
	 */
	public function beforeTraverse( array $nodes ) {
		$this->validator->reset_errors();
	}

	/**
	 * Enter node. Make all checks here.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return null
	 */
	public function enterNode( Node $node ) {
		// Check if node is valid.
		$this->node_is_valid = $this->validator->is_valid( $node );

		return null;
	}

	/**
	 * Leave node. Make node transformations here.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Node.
	 *
	 * @return null
	 */
	public function leaveNode( Node $node ) {
		if ( ! $this->node_is_valid ) {
			return null;
		}

		// Convert all functions calls to $_FUNC['function_name]( ... ) call.
		if ( isset( $node->name ) && $this->validator->is_function( $node ) ) {
			$node->name = $this->get_updated_function_name( $node );

			return null;
		}

		// Convert all math operators to $_INNER_FUNC['operator']( ... ) call.
		if ( $this->validator->is_math_op( $node ) ) {
			$node = $this->get_updated_math_op( $node );

			return $node;
		}

		if ( ! $this->validator->is_if( $node ) ) {
			return null;
		}

		// Convert all statements inside if-elseif-else to assignments of $_RETURN variable.
		if ( empty( $node->stmts ) ) {
			return null;
		}
		$node->stmts = $this->get_updated_statements( $node->stmts );

		return null;
	}

	/**
	 * Update nodes single level.
	 *
	 * @since 1.7.8
	 *
	 * @param Node[] $nodes Nodes array.
	 *
	 * @return Node[]
	 */
	public function get_updated_statements( $nodes ) {
		$new_nodes = array();

		$retval = new Node\Expr\Variable( Transpiler::RESULT_VAR_NAME );

		foreach ( $nodes as $node ) {

			if (
				$this->validator->is_assign( $node ) ||
				$this->validator->is_if( $node ) ||
				$this->validator->is_nop( $node )
			) {
				$new_nodes[] = $node;

				continue;
			}
			if ( isset( $node->expr ) ) {
				$new_nodes[] = new Node\Stmt\Expression( new Node\Expr\Assign( $retval, $node->expr ) );
			}
		}

		return $new_nodes;
	}

	/**
	 * Update function call node name element.
	 * Replace function name with `$_FUNC['function_name']['func']`.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Function call node.
	 *
	 * @return Node\Expr\ArrayDimFetch
	 */
	private function get_updated_function_name( $node ) {
		if ( ! isset( $node->name ) ) {
			return null;
		}

		if ( ! $node->name instanceof Node\Name ) {
			return $node->name;
		}

		$func = new Node\Expr\ArrayDimFetch(
			new Node\Expr\Variable( Transpiler::FUNCTIONS_ARRAY_NAME ),
			new Node\Scalar\String_( $node->name->toString() )
		);

		// Replace function name with `$_FUNC['function_name']['func']`.
		return new Node\Expr\ArrayDimFetch(
			$func,
			new Node\Scalar\String_( 'func' )
		);
	}

	/**
	 * Update math operator node.
	 * Replace operator with `$_INNER_FUNC['operator']( ... )`.
	 *
	 * @since 1.7.8
	 *
	 * @param Node $node Function call node.
	 *
	 * @return Node\Expr\FuncCall
	 */
	private function get_updated_math_op( $node ) {
		if ( ! isset( $node->left, $node->right ) ) {
			return null;
		}

		$func_name = new Node\Expr\ArrayDimFetch(
			new Node\Expr\Variable( Transpiler::INNER_FUNCTIONS_ARRAY_NAME ),
			new Node\Scalar\String_( strtolower( ( new ReflectionClass( $node ) )->getShortName() ) )
		);

		return new Node\Expr\FuncCall(
			$func_name,
			array(
				new Node\Arg( $node->left ),
				new Node\Arg( $node->right ),
			)
		);
	}
}
