<?php
namespace EverestForms\Pro\Addons\Calculation\Process;

use EverestForms\Pro\Addons\Calculation\Helpers;
use EverestForms\Pro\Addons\Calculation\Transpiler\Transpiler;

/**
 * Calculation AJAX in Form Builder.
 *
 * @since 1.7.8
 */
class Ajax {

	/**
	 * Constructor.
	 *
	 * @since 1.7.8
	 */
	public function __construct() {
		self::hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.7.8
	 */
	public static function hooks() {
		add_filter( 'everest_forms_save_form_args', array( __CLASS__, 'save_form_args' ), 999, 3 );
		add_action( 'wp_ajax_everest_forms_pro_calculations_validate_formula', array( __CLASS__, 'validate_formula' ) );
	}

	/**
	 * Update form_data with updated php code.
	 *
	 * @since 1.7.8
	 *
	 * @param array $form Form update post arguments.
	 * @param array $data Initial form data.
	 * @param array $args Update form args.
	 *
	 * @return array
	 */
	public static function save_form_args( $form, $data, $args ) {
		$form_data = json_decode( stripslashes( $form['post_content'] ), true );

		if ( ! Helpers::calculation_enabled_or_not( $form_data ) ) {
			return $form;
		}

		$transpiler = new Transpiler();

		foreach ( $form_data['form_fields'] as $field_id => $field_data ) {

			if ( empty( $field_data['enable_calculation'] ) ) {
				continue;
			}

			$form_data['form_fields'][ $field_id ] = $transpiler->process_field( $field_data, $form_data );
		}

		$form['post_content'] = evf_encode( $form_data );

		return $form;
	}

	/**
	 * Validate formula AJAX callback.
	 *
	 * @since 1.7.8
	 */
	public static function validate_formula() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
		// Run a security check.
		if ( ! check_ajax_referer( 'ajax_post_submission_nonce', 'nonce', false ) ) {
			wp_send_json_error( esc_html__( 'Your session expired. Please reload the builder.', 'everest-forms-pro' ) );
		}

		// Check for permissions.
		if ( ! current_user_can( 'everest_forms_edit_forms' ) ) {
			wp_send_json_error( esc_html__( 'You are not allowed to perform this action.', 'everest-forms-pro' ) );
		}

		$forms = evf_get_all_forms();

		$form_id  = sanitize_text_field( wp_unslash( $_POST['form_id'] ) );
		$field_id = isset( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) : null;

		if ( empty( $form_id ) || ! isset( $field_id ) || empty( $forms ) ) {
			wp_send_json_error( esc_html__( 'Something went wrong while performing this action.', 'everest-forms-pro' ) );
		}

		$form_data  = evf_get_form_fields( $form_id );
		$code       = isset( $_POST['code'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
		$transpiler = new Transpiler();

		if ( ! isset( $form_data[ $field_id ] ) ) {
			$form_data[ $field_id ] = array(
				'id' => $field_id,
			);
		}

		$data['form_fields'] = $form_data;

		$errors = $transpiler->parse_and_validate_formula_code( $code, $field_id, $data, false );

		if ( ! empty( $errors ) ) {
			wp_send_json_error( $errors );
			die();
		}

		wp_send_json_success();
	}
}
