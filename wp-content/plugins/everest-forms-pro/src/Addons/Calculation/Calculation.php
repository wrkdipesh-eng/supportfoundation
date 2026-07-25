<?php
/**
 * Main plugin class.
 *
 * @package EverestForms\Pro\Addons\Calculation
 * @since   1.7.8
 */

namespace EverestForms\Pro\Addons\Calculation;

use EverestForms\Pro\Addons\Calculation\Process\Process;
use EverestForms\Pro\Addons\Calculation\Process\Ajax;
use EverestForms\Pro\Addons\Calculation\Builder\Settings;
use EverestForms\Pro\Addons\Calculation\Transpiler\Transpiler;
use EverestForms\Pro\Addons\Calculation\Transpiler\Functions;

/**
 * Main plugin class.
 *
 * @since 1.7.8
 */
class Calculation {

	/**
	 * Helpers class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Helpers
	 */
	private $helpers;

	/**
	 * Function class instance.
	 *
	 * @since 1.7.8
	 *
	 * @var Functions
	 */
	private $functions;

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.7.8
	 */
	public function __construct() {
		$this->init();
		$this->helpers   = new Helpers();
		$this->functions = new Functions();
		// Enqueing Scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'everest_forms_frontend_output', array( $this, 'frontend_enqueue_scripts' ) );
	}

	/**
	 * Init function.
	 *
	 * @since 1.7.8
	 */
	public function init() {
		if ( is_admin() ) {
			new Settings();
		}
		new Process();
		new Ajax();
	}

		/**
		 * Frontend Enqueue scripts.
		 */
	public function frontend_enqueue_scripts( $form_data ) {
		$enabled_calculations = array();
		$form_id              = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
		$form_obj             = evf()->form->get( $form_id, array() );
		$form_data            = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : array();
		if ( isset( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $field_key => $data ) {
				if ( isset( $data['enable_calculation'] ) && '1' === $data['enable_calculation'] ) {
					$enabled_calculations = '1';
				}
			}
		}

		$form_fields = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : '';

		// If calculation do not enqueue the scripts.
		if ( ! $enabled_calculations ) {
			return;
		}
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// Enqueue Scripts.
		wp_enqueue_script( 'everest-forms-math-script', plugins_url( 'src/Addons/Calculation/assets/js/math.min.js', EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
		wp_register_script( 'everest-forms-calculations-script', plugins_url( "src/Addons/Calculation/assets/js/frontend/everest-forms-calculations{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery' ), EFP_VERSION, true );

		$inner_function_name = Transpiler::INNER_FUNCTIONS_ARRAY_NAME;
		$function_array_name = Transpiler::FUNCTIONS_ARRAY_NAME;
		$result_var_name     = Transpiler::RESULT_VAR_NAME;
		$allowed_fields      = $this->helpers->get_fields_allowed_in_calculation();

		wp_localize_script(
			'everest-forms-calculations-script',
			'evf_calculations_obj',
			array(
				'formFields'              => $form_fields,
				'allowedFields'           => $allowed_fields,
				'result'                  => $result_var_name,
				'functionsArrayName'      => $function_array_name,
				'innerFunctionsArrayName' => $inner_function_name,
				'suffix'                  => $suffix,
			)
		);
		wp_enqueue_script( 'everest-forms-calculations-script' );
	}

	/**
	 * Admin Enqueue Scripts.
	 */
	public function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register admin scripts.
		wp_register_script( 'everest-forms-calculations-builder', plugins_url( "src/Addons/Calculation/assets/js/admin/admin{$suffix}.js", EFP_PLUGIN_FILE ), array( 'jquery', 'jquery-confirm' ), EFP_VERSION, true );

		wp_localize_script(
			'everest-forms-calculations-builder',
			'everest_forms_calculations_params',
			array(
				'ajax_url'               => admin_url( 'admin-ajax.php', 'relative' ),
				'ajax_nonce'             => wp_create_nonce( 'ajax_post_submission_nonce' ),
				'functionName'           => $this->functions->get_names(),
				'i18n_disable_message'   => esc_html__( 'Calculations is currently disabled. Please enable it to activate this feature with Everest Forms.', 'everest-forms-pro' ),
				'formula_valid_message'  => esc_html__( 'Formula is valid.', 'everest-forms-pro' ),
				'operator_warning'       => esc_html__( 'The ^ operator is not supported in the latest version. Instead of using ^, use pow( $FIELD_1, $FIELD_2 ).', 'everest-forms-pro' ),
				'operator_warning_title' => esc_html__( 'Heads Up!', 'everest-forms-pro' ),
				'syntax_error_title'     => esc_html__( 'Heads Up!', 'everest-forms-pro' ),
			)
		);

		// Admin scripts for EVF builder page.
		if ( 'everest-forms_page_evf-builder' === $screen_id ) {
			wp_enqueue_script( 'everest-forms-math-script', plugins_url( 'src/Addons/Calculation/assets/js/math.min.js', EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );
			wp_enqueue_script( 'everest-forms-calculations-builder' );
			wp_enqueue_style( 'everest-forms-calculations-style', plugins_url( 'src/Addons/Calculation/assets/css/admin/admin.css', EFP_PLUGIN_FILE ), array(), EFP_VERSION );
		}
	}
}
