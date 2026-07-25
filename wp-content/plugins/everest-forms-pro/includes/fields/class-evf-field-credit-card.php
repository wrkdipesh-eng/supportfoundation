<?php
/**
 * Credit card field
 *
 * @package EverestForms_Pro\Fields
 * @since   1.2.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Credit_Card Class.
 */
class EVF_Field_Credit_Card extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! is_plugin_active( 'everest-forms-stripe/everest-forms-stripe.php' ) ) {
			return;
		}
		$this->name     = esc_html__( 'Credit Card', 'everest-forms-pro' );
		$this->type     = 'credit-card';
		$this->icon     = 'evf-icon evf-icon-payment';
		$this->order    = 50;
		$this->group    = 'payment';
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
		add_action( 'admin_print_footer_scripts', array( $this, 'builder_footer_scripts' ) );
		// add_filter( 'everest_forms_field_new_required', array( $this, 'field_default_required' ), 5, 3 ); @codingStandardsIgnoreLine
		add_filter( "everest_forms_should_display_field_{$this->type}", array( $this, 'should_display_field' ), 10, 3 );
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
		if ( function_exists( 'evf_is_payment_gateway_enabled_for_form' ) && evf_is_payment_gateway_enabled_for_form( $form_data, 'stripe' ) ) {
			return $bool;
		}

		return false;
	}

	/**
	 * Restrict the users to add the field inside the
	 * form builder if not supporting payment gateway is active.
	 */
	public function builder_footer_scripts() {
		if ( apply_filters( 'everest_forms_field_credit_card_enable', false ) ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(function($){
				$( '#everest-forms-add-fields-credit-card' ).remove();
			});
		</script>
		<?php
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
		if ( 'credit-card' === $field['type'] ) {
			return true;
		}
		return $required;
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		if ( ! is_plugin_active( 'everest-forms-stripe/everest-forms-stripe.php' ) ) {
			return;
		}
		$this->field_preview_option( 'label', $field );
		?>
		<div class="everest-forms-credit-card-cardnumber">
			<div class="everest-forms-card-icon">
				<svg focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 21"><g fill="none" fill-rule="evenodd"><g id="unknown" class="Icon-fill"><g id="card" transform="translate(0 2)"><path id="shape" d="M26.58 19H2.42A2.4 2.4 0 0 1 0 16.62V2.38A2.4 2.4 0 0 1 2.42 0h24.16A2.4 2.4 0 0 1 29 2.38v14.25A2.4 2.4 0 0 1 26.58 19zM10 5.83c0-.46-.35-.83-.78-.83H3.78c-.43 0-.78.37-.78.83v3.34c0 .46.35.83.78.83h5.44c.43 0 .78-.37.78-.83V5.83z" opacity=".2"></path><path id="shape" d="M25 15h-3c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1zm-6 0h-3c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1zm-6 0h-3c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1zm-6 0H4c-.65 0-1-.3-1-1s.35-1 1-1h3c.65 0 1 .3 1 1s-.35 1-1 1z" opacity=".3"></path></g></g></g></svg>
			</div>
			<input class="card-number" type="text" placeholder="Card Number" disabled>
			<input class="card-expiration" type="text" placeholder="MM / YY" disabled>
			<input class ="card-cvc" type="text" placeholder="CVC" disabled>
		</div>
		<?php
		// Description.
		$this->field_preview_option( 'description', $field );
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
		$credit_card_gateways = apply_filters( 'everest_forms_credit_card_gateway', array() );
		$conditional_rules    = isset( $field['properties']['inputs']['primary']['attr']['conditional_rules'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_rules'] : '';
		$conditional_id       = isset( $field['properties']['inputs']['primary']['attr']['conditional_id'] ) ? $field['properties']['inputs']['primary']['attr']['conditional_id'] : '';
		$form_id              = isset( $form_data['id'] ) ? $form_data['id'] : 0;
		$payments             = $form_data['payments'];

		$creditcard_html = '';

		if ( ! empty( $credit_card_gateways ) ) {
			foreach ( $credit_card_gateways as $gateway => $value ) {
				$creditcard_html = sprintf(
					'<div id="everest_forms_%s_gateway_%s" data-gateway="%s" class="input-text everest-forms-gateway" data-form-id="%s" conditional_rules="%s" conditional_id="%s"></div><label id="card-errors" class="evf-error" role="alert"></label>',
					$gateway,
					$form_id,
					$gateway,
					$form_id,
					esc_attr( $conditional_rules ),
					esc_attr( $conditional_id )
				);
			}
		}

		// Backward Compatibility.
		if ( ! isset( $payments['stripe']['enable_credit_card'] ) ) {
			// @codingStandardsIgnoreStart
			echo $creditcard_html;
			// @codingStandardsIgnoreEnd
			return;
		}

		// IDEAL html.
		$ideal_hmtl = sprintf(
			'<div id="everest_forms_ideal_gateway_%s" data-gateway="ideal" class="input-text everest-forms-gateway" data-form-id="%s" conditional_rules="%s" conditional_id="%s">%s</div><label id="card-errors" class="evf-error" role="alert"></label>',
			esc_attr( $form_id ),
			esc_attr( $form_id ),
			esc_attr( $conditional_rules ),
			esc_attr( $conditional_id ),
			esc_html__( 'Select bank', 'everest-forms-pro' )
		);

		$gateway_options = array(
			'credit_card' => array(
				'name' => __( 'Credit Card', 'everest-forms-pro' ),
				'html' => $creditcard_html,
				'id'   => 'stripe',
			),
			'ideal'       => array(
				'name' => __( 'iDeal Payment', 'everest-forms-pro' ),
				'html' => $ideal_hmtl,
				'id'   => 'ideal',
			),
		);

		$gateways = array();

		foreach ( $gateway_options as $key => $gateway_option ) {
			if ( ! empty( $payments['stripe'][ 'enable_' . $key ] && '1' === $payments['stripe'][ 'enable_' . $key ] ) ) {
				if ( 'ideal' === $key && 'EUR' !== get_option( 'everest_forms_currency', 'USD' ) ) {
					continue;
				}
				$gateways [] = $gateway_option;
			}
		}

		if ( count( $gateways ) === 1 ) {
			// @codingStandardsIgnoreStart
			echo $gateways[0]['html'];
			// @codingStandardsIgnoreEnd
			return;
		}

		$output  = '<div class="everest-forms-payment-gateway">';
		$output .= '<div class="everest-forms-stripe-gateways-tabs">';
		foreach ( $gateways as $key => $gateway ) {
			$active  = 0 === $key ? 'active' : '';
			$output .= '<div class="evf-tab" data-tab="' . $key . '" data-gateway="' . $gateway['id'] . '"><a href="#" class="label ' . $active . '">' . esc_html( $gateway['name'] ) . '</a></div>';
		}
		$output .= '</div>';
		$output .= '<div class="everest-forms-stripe-gateways-contents">';
		foreach ( $gateways as $key => $gateway ) {
			$style   = 0 < $key ? 'display:none;' : '';
			$output .= '<div class="tab_content_' . $key . '" style="' . $style . '">' . $gateway['html'] . '</div>';
		}
		$output .= '</div>';
		$output .= '</div>';
		// @codingStandardsIgnoreStart
		echo $output;
		// @codingStandardsIgnoreEnd
	}
}
