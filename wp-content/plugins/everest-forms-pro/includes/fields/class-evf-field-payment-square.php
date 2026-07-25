<?php
/**
 * Square field
 *
 * @package EverestForms\Fields
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * SquareFields Class.
 */
class EVF_Field_Payment_Square extends \EVF_Form_Fields {

	/**
	 * The order for the field.
	 *
	 * @var int
	 */
	public $order;

	public function __construct() {
		$enabled_features = get_option( 'everest_forms_enabled_features', array() );

		$this->name  = esc_html__( 'Square', 'everest-forms-pro' );
		$this->type  = 'square-payment';
		$this->icon  = 'evf-icon evf-icon-payment';
		$this->order = 231;
		$this->group = 'payment';

		if ( ! in_array( 'everest-forms-square', $enabled_features ) ) {
			$this->class = 'everest-forms-pro-is_square_install';
		}

		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'description',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'meta',
					'label_hide',
					'css',
				),
			),
		);
		parent::__construct();
	}

	/**
	 * Hook in tabs.
	 */
	public function init_hooks() {
		add_filter( "everest_forms_should_display_field_{$this->type}", array( $this, 'should_display_field' ), 10, 3 );
		add_action( 'everest_forms_shortcode_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );
		?>
		<div class="everest-forms-credit-card-cardnumber">
			<input class="card-number" type="text" placeholder="Card Number" disabled>
			<input class="card-expiration" type="text" placeholder="MM / YY" disabled>
			<input class ="card-cvc" type="text" placeholder="CVC" disabled>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
	}


	/**
	 * Check if Stripe is enabled.
	 *
	 * @param boolean $bool Is field visible?.
	 * @param mixed   $field Field Data.
	 * @param array   $form_data Form data and settings.
	 * @return bool
	 */
	public function should_display_field( $bool, $field, $form_data ) {
		if ( function_exists( 'evf_is_payment_gateway_enabled_for_form' ) && evf_is_payment_gateway_enabled_for_form( $form_data, 'square' ) ) {
			return $bool;
		}

		return false;
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$conditional_rules = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';
		$conditional_id    = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$payments          = $form_data['payments'];
		$square_enabled      = isset( $payments['square'] ) ? $payments['square']['enable_square'] : 0;
		$square_via_selector = function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) );
		$form_id             = isset( $form_data['id'] ) ? $form_data['id'] : 0;
		$gateway             = 'square';

		if ( 1 != $square_enabled && ! $square_via_selector ) {
			return;
		}

		$square_credit_card = sprintf(
			'<div id="everest_forms_%s_gateway_%s" data-gateway="%s" class="input-text everest-forms-gateway" data-form-id="%s" conditional_rules="%s" conditional_id="%s"></div><label id="card-errors" class="evf-error" role="alert"></label>',
			$gateway,
			$form_id,
			$gateway,
			$form_id,
			esc_attr( $conditional_rules ),
			esc_attr( $conditional_id )
		);

		echo $square_credit_card;
		if ( ! is_ssl() ) {
			echo '<label class="everest-forms-ssl-warning evf-error">';
			esc_html_e( 'Please establish an SSL connection on this page to create a Square payment.', 'everest-forms-pro' );
			echo '</label>';
		}

	}

	/**
	 * Field should default to being required.
	 *
	 * @since 1.3.0
	 *
	 * @param bool  $required Required status, true is required.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function field_default_required( $required, $field ) {
		if ( 'square-payment' === $field['type'] ) {
			return true;
		}
		return $required;
	}

	/**
	 * Queue frontend scripts.
	 *
	 * @since 1.8.1
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function load_assets( $atts ) {
		$form_data                  = evf()->form->get( $atts['id'], array( 'content_only' => true ) );
		$square_test_application_id = get_option( 'everest_forms_square_test_app_id', '' );
		$square_test_access_token   = get_option( 'everest_forms_square_test_access_token', '' );
		$square_test_location_id    = get_option( 'everest_forms_square_test_location_id', '' );
		$check_square_test_mode     = get_option( 'everest_forms_pro_square_test_mode', 'no' );

		$square_live_application_id = get_option( 'everest_forms_square_live_app_id', '' );
		$square_live_access_token   = get_option( 'everest_forms_square_live_access_token', '' );
		$square_live_location_id    = get_option( 'everest_forms_square_live_location_id', '' );

		if ( ! empty( $form_data['form_fields'] ) ) {
			$is_square_field = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type' => 'square-payment',
				)
			);

			$has_square_proxy = function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) );

		if ( ! empty( $is_square_field ) || $has_square_proxy ) {
				wp_enqueue_script( 'everest-forms-pro-square-payment' );

				if ( function_exists( 'evf_square_mark_recurring_form_script' ) ) {
					evf_square_mark_recurring_form_script( absint( $atts['id'] ), $form_data );
				}

				if ( ! empty( $square_test_application_id ) && ! empty( $square_test_access_token ) && ! empty( $square_test_location_id ) && 'yes' === $check_square_test_mode ) {
					wp_enqueue_script( 'everest-forms-pro-square-v1', 'https://sandbox.web.squarecdn.com/v1/square.js', array() );
				} elseif ( ! empty( $square_live_application_id ) && ! empty( $square_live_access_token ) && ! empty( $square_live_location_id ) && 'no' === $check_square_test_mode ) {
					wp_enqueue_script( 'everest-forms-pro-square-v1', 'https://web.squarecdn.com/v1/square.js', array() );
				}
			}
		}
	}
}
