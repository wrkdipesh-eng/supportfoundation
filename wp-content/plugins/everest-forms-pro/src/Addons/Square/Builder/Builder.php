<?php

/**
 * Square Builder Payment Settings.
 *
 * @package EverestForms\Pro\Addons\Square\Builder
 * @since   1.0.0
 */


namespace  EverestForms\Pro\Addons\Square\Builder;

use EverestForms\Pro\Addons\Square\Process\Process;

defined( 'ABSPATH' ) || exit;

/**
 * Builder class.
 *
 * @since 1.0.0
 */
class Builder extends \EVF_Payments {

	/**
	 * Forms.
	 *
	 * @var int
	 */
	public $form;

	/**
	 * Form ID.
	 *
	 * @var int
	 */
	public $form_id;

	/**
	 * Form data.
	 *
	 * @var array
	 */
	public $form_data;

	/**
	 * Extended Constructor functionality for Builder -  EVF_Payment
	 */
	public function __construct() {
		$this->id        = 'square';
		$this->form      = '';
		$this->icon      = plugins_url( 'src/Addons/Square/assets/img/square-payment.png', EFP_PLUGIN_FILE );
		$this->form_id   = 0;
		$this->form_data = array();
		$this->name      = __( 'Square', 'everest-forms-pro' );
		// Hooks.
		add_filter( 'everest_forms_field_square_enable', '__return_true' );
		add_action( 'everest_forms_payments_panel_content', array( $this, 'output_panel_content' ) );
		add_filter( 'evf_pgw_builder_gateway_panel_html', array( $this, 'payment_gateway_selector_square_panel_html' ), 10, 4 );
		parent::__construct();
	}

	/**
	 * Whether the form has a Payment Gateway selector (Square customer mapping then lives only in that accordion).
	 *
	 * @param array $form_data Decoded form data.
	 * @return bool
	 */
	private function form_has_payment_gateway_selector_field( $form_data ) {
		if ( empty( $form_data['form_fields'] ) || ! is_array( $form_data['form_fields'] ) ) {
			return false;
		}
		foreach ( $form_data['form_fields'] as $form_field ) {
			if ( ! empty( $form_field['type'] ) && 'payment-gateway-selector' === $form_field['type'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Builder output settings.
	 *
	 * @since 1.0.0
	 */
	public function output_panel_content() {
		$this->form_id   	= isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$form            	= EVF()->form->get( $this->form_id );
		$this->form_data 	= ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : '';
		$payments        	= isset( $this->form_data['payments'] ) ? $this->form_data['payments'] : array();
		$test_publish_key  	= get_option( 'everest_forms_square_test_app_id' );
		$live_publish_key   = get_option( 'everest_forms_square_live_app_id' );

		// Square payment.
		echo '<div class="evf-panel-content-section evf-payment-setting-content evf-content-square-settings">';
		    if ( empty( $test_publish_key ) && empty( $live_publish_key ) ) {
				echo "<p class='everest-forms-notice everest-forms-notice-info'>";
						echo wp_kses_post(
							sprintf(
								/* translators: %s: payment settings URL */
								__( 'Please enter square API key on<a href="%s">settings panel</a>to enable square.', 'everest-forms-pro' ),
								esc_url( admin_url( 'admin.php?page=evf-settings&tab=payment' ) )
							)
						);
				echo '</p>';
			} else {
				echo '<div class="evf-content-section-title">';
				esc_html_e( 'Square', 'everest-forms-pro' );
				echo '</div>';

				everest_forms_panel_field(
					'toggle',
					'square',
					'enable_square',
					$this->form_data,
					esc_html__( 'Enable Square Payment', 'everest-forms-pro' ),
					array(
						'default' => isset( $payments['square']['enable_square'] ) ? $payments['square']['enable_square'] : 0,
						'parent'  => 'payments',
					)
				);

				$hidden = empty( $payments['square']['enable_square'] ) || '1' !== $payments['square']['enable_square'] ? 'everest-forms-hidden' : '';

				$form_data_array = is_array( $this->form_data ) ? $this->form_data : array();

				if ( ! $this->form_has_payment_gateway_selector_field( $form_data_array ) ) {
					echo '<div class="evf-square-payment-sync-field ' . $hidden . '">';
					everest_forms_panel_field(
						'select',
						'square',
						'customer_email',
						$this->form_data,
						esc_html__( 'Email', 'everest-forms-pro' ),
						array(
							'parent'        => 'payments',
							'field_map'     => array( 'email', ),
							'placeholder'   => esc_html__( '-- Select Email --', 'everest-forms-pro' ),
							'tooltip'       => esc_html__( "Select field for customer's Email. Required.", 'everest-forms-pro' ),
							'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
						)
					);
					everest_forms_panel_field(
						'select',
						'square',
						'customer_first_name',
						$this->form_data,
						esc_html__( 'Customer First Name', 'everest-forms-pro' ),
						array(
							'parent'        => 'payments',
							'field_map'     => array( 'first-name', ),
							'placeholder'   => esc_html__( '-- Select First Name --', 'everest-forms-pro' ),
							'tooltip'       => esc_html__( "Select field for customer's first name. Required.", 'everest-forms-pro' ),
							'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
						)
					);

					everest_forms_panel_field(
						'select',
						'square',
						'customer_last_name',
						$this->form_data,
						esc_html__( 'Customer Last Name', 'everest-forms-pro' ),
						array(
							'parent'      => 'payments',
							'field_map'   => array( 'last-name' ),
							'placeholder' => esc_html__( '-- Select Last Name --', 'everest-forms-pro' ),
							'tooltip'     => esc_html__( "Select field for customer's last name. Optional.", 'everest-forms-pro' ),
						)
					);

					everest_forms_panel_field(
						'select',
						'square',
						'customer_billing_address',
						$this->form_data,
						esc_html__( 'Customer Billing Address', 'everest-forms-pro' ),
						array(
							'parent'      => 'payments',
							'field_map'   => array( 'address' ),
							'placeholder' => esc_html__( '-- Select Billing Address --', 'everest-forms-pro' ),
							'tooltip'     => esc_html__( "Select field for customer's billing address. Optional.", 'everest-forms-pro' ),
						)
					);

					echo '</div>';
				}

				echo '<div class="evf-square-gateway-conditional ' . $hidden . '">';
						do_action( 'everest_forms_inline_payment_settings', $this, 'square', 'connection_1' );
				echo '</div>';
			}
		echo '</div>';
	}

	/**
	 * Render Square customer mapping fields inside Payment Gateway selector → Square accordion.
	 *
	 * When the form includes a Payment Gateway selector, this is the only place these fields are
	 * rendered (the Payments tab omits them) so duplicate input names/IDs are not submitted on save.
	 *
	 * @param string $html      Existing HTML (from other filters).
	 * @param string $slug      Gateway slug.
	 * @param array  $field     Payment gateway selector field data.
	 * @param array  $form_data Decoded form data for values/options.
	 * @return string
	 */
	public function payment_gateway_selector_square_panel_html( $html, $slug, $field, $form_data = array() ) {
		if ( 'square' !== $slug ) {
			return $html;
		}

		if ( ! is_array( $form_data ) ) {
			$form_data = array();
		}

		$payments = isset( $form_data['payments'] ) ? $form_data['payments'] : array();

		$out  = $html;
		$out .= '<div class="evf-pgw-square-settings evf-pgw-accordion-fields">';

		$out .= everest_forms_panel_field(
			'select',
			'square',
			'customer_email',
			$form_data,
			esc_html__( 'Email', 'everest-forms-pro' ),
			array(
				'parent'        => 'payments',
				'field_map'     => array( 'email' ),
				'placeholder'   => esc_html__( '- Select Email -', 'everest-forms-pro' ),
				'tooltip'       => esc_html__( "Select the field to map as customer's Email.", 'everest-forms-pro' ),
				'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
			),
			false
		);

		$out .= everest_forms_panel_field(
			'select',
			'square',
			'customer_first_name',
			$form_data,
			esc_html__( 'First Name', 'everest-forms-pro' ),
			array(
				'parent'        => 'payments',
				'field_map'     => array( 'first-name' ),
				'placeholder'   => esc_html__( '- Select First Name -', 'everest-forms-pro' ),
				'tooltip'       => esc_html__( "Select the field to map as the customer's first name.", 'everest-forms-pro' ),
				'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
			),
			false
		);

		$out .= everest_forms_panel_field(
			'select',
			'square',
			'customer_last_name',
			$form_data,
			esc_html__( 'Last Name', 'everest-forms-pro' ),
			array(
				'parent'      => 'payments',
				'field_map'   => array( 'last-name' ),
				'placeholder' => esc_html__( '- Select Last Name -', 'everest-forms-pro' ),
				'tooltip'     => esc_html__( "Select the field to map as the customer's last name.", 'everest-forms-pro' ),
			),
			false
		);

		$out .= everest_forms_panel_field(
			'select',
			'square',
			'customer_billing_address',
			$form_data,
			esc_html__( 'Billing Address', 'everest-forms-pro' ),
			array(
				'parent'      => 'payments',
				'field_map'   => array( 'address' ),
				'placeholder' => esc_html__( '- Select Billing Address -', 'everest-forms-pro' ),
				'tooltip'     => esc_html__( "Select the field to map as the customer's billing address.", 'everest-forms-pro' ),
			),
			false
		);

		$out .= '</div>';

		unset( $field );
		return $out;
	}
}
