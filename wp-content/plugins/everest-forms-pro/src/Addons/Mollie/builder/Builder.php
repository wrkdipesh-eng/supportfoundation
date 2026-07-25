<?php
/**
 * Everest Forms Mollie form builder settings.
 *
 * @since 1.7.7
 */

namespace EverestForms\Pro\Addons\Mollie\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Mollie Form Settings class.
 *
 * @since 1.7.7
 */
class Builder extends \EVF_Payments {

	/**
	 * Form ID.
	 *
	 * @since 1.7.7
	 *
	 * @var int
	 */
	protected $form_id = 0;

	/**
	 * Form data.
	 *
	 * @since 1.7.7
	 *
	 * @var array
	 */
	protected $form_data = array();

	/**
	 * Form.
	 *
	 * @since 1.7.7
	 *
	 * @var object|string
	 */
	protected $form = '';

	/**
	 * Constructor.
	 *
	 * @since 1.7.7
	 */
	public function __construct() {
		$this->id        = 'mollie';
		$this->form      = '';
		$this->icon      = plugins_url( 'src/Addons/Mollie/assets/img/mollie.png', EFP_PLUGIN_FILE );
		$this->form_id   = 0;
		$this->form_data = array();
		$this->name      = __( 'Mollie', 'everest-forms-pro' );

		parent::__construct();

		// Hooks.
		add_action( 'everest_forms_payments_panel_content', array( $this, 'output_panel_content' ) );
		add_filter( 'everest_forms_credit_card_gateway', array( $this, 'gateway_info' ) );
		add_filter( 'evf_pgw_builder_gateway_panel_html', array( $this, 'payment_gateway_selector_mollie_panel_html' ), 10, 4 );
		add_filter( 'evf_pgw_builder_gateway_chevron_hidden', array( $this, 'payment_gateway_selector_mollie_chevron_hidden' ), 10, 5 );
	}

	/**
	 * Returns the gateways information.
	 *
	 * @since 1.7.7
	 *
	 * @param  array $gateways Gateway Information.
	 */
	public function gateway_info( $gateways ) {
		$test_api  = get_option( 'everest_forms_mollie_test_api_key' );
		$live_api  = get_option( 'everest_forms_mollie_live_api_key' );
		$test_mode = get_option( 'everest_forms_mollie_test_mode' );
		$test_mode = isset( $test_mode ) ? $test_mode : 'no';

		if ( 'yes' === $test_mode ) {
			if ( empty( $test_api ) || empty( $test_api ) ) {
				return $gateways;
			} else {
				$gateways[ $this->id ] = array( 'gateway' => $this->id );
			}
		} elseif ( 'no' === $test_mode ) {
			if ( empty( $live_api ) || empty( $live_api ) ) {
				return $gateways;
			} else {
				$gateways[ $this->id ] = array( 'gateway' => $this->id );
			}
		}
		return $gateways;
	}

	/**
	 * Whether the form has a Payment Gateway selector field.
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
	 * Mollie settings.
	 *
	 * @since 1.7.7
	 */
	public function output_panel_content() {
		$this->form_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // PHPCS:ignore WordPress.Security.NonceVerification
		$this->form      = EVF()->form->get( $this->form_id );
		$this->form_data = ! empty( $this->form->post_content ) ? evf_decode( $this->form->post_content ) : '';
		$payments        = isset( $this->form_data['payments'] ) ? $this->form_data['payments'] : array();
		$test_api        = get_option( 'everest_forms_mollie_test_api_key' );
		$live_api        = get_option( 'everest_forms_mollie_live_api_key' );

		echo '<div class="evf-panel-content-section evf-payment-setting-content evf-content-mollie-settings">';
		// Mollie.
		if ( ( ! empty( $test_api ) ) || ( ! empty( $live_api ) ) ) {
			echo '<div class="evf-content-section-title">';
			esc_html_e( 'Mollie', 'everest-forms-pro' );
			echo '</div>';

			everest_forms_panel_field(
				'toggle',
				'payments[mollie]',
				'enable_mollie',
				$this->form_data,
				__( 'Enable Mollie', 'everest-forms-pro' ),
				array(
					'default' => isset( $payments['mollie']['enable_mollie'] ) ? $payments['mollie']['enable_mollie'] : 0,
				)
			);

			$hidden = ! isset( $payments['mollie']['enable_mollie'] ) || '1' !== $payments['mollie']['enable_mollie'] ? 'everest-forms-hidden' : '';

			echo '<div class="evf-mollie-gateway-additional-settings-wrap">';
				echo '<div class="evf-mollie-gateway-conditional ' . esc_attr( $hidden ) . '">';
					do_action( 'everest_forms_inline_payment_settings', $this, 'mollie', 'connection_1' );
				echo '</div>';

				$form_data_array = is_array( $this->form_data ) ? $this->form_data : array();
				if ( ! $this->form_has_payment_gateway_selector_field( $form_data_array ) ) {
				everest_forms_panel_field(
					'text',
					'mollie',
					'redirect_url',
					$this->form_data,
					esc_html__( 'Redirect URL', 'everest-forms-pro' ),
					array(
						'default' => isset( $payments['mollie']['redirect_url'] ) ? $payments['mollie']['redirect_url'] : '',
						'parent'  => 'payments',
						'tooltip' => esc_html__( 'Returns to this page after payment', 'everest-forms-pro' )
					)
				);
			}

				echo '<div class="evf-content-section-title">';
				esc_html_e( 'Subscriptions', 'everest-forms-pro' );
				echo '</div>';

				everest_forms_panel_field(
					'toggle',
					'mollie',
					'enable_mollie_recurring',
					$this->form_data,
					esc_html__( 'Enable recurring subscription payments on Mollie', 'everest-forms-pro' ),
					array(
						'default' => isset( $payments['mollie']['enable_mollie_recurring'] ) ? $payments['mollie']['enable_mollie_recurring'] : 0,
						'parent'  => 'payments',
					)
				);

				echo '<div class="evf-mollie-gateway-recurring-wrap ' . esc_attr( $hidden ) . '">';
				everest_forms_panel_field(
					'text',
					'mollie',
					'subscription_description',
					$this->form_data,
					esc_html__( 'Subscription Description', 'everest-forms-pro' ),
					array(
						'default' => isset( $payments['mollie']['subscription_description'] ) ? $payments['mollie']['subscription_description'] : esc_html__( 'Mollie subscription description', 'everest-forms-pro' ),
						'parent'  => 'payments',
						'tooltip' => esc_html__( 'Title or the description for the subscription form name', 'everest-forms-pro' )
					)
				);

				everest_forms_panel_field(
					'text',
					'mollie',
					'interval_count',
					$this->form_data,
					esc_html__( 'Recurring Duration', 'everest-forms-pro' ),
					array(
						'default' => isset( $payments['mollie']['interval_count'] ) ? $payments['mollie']['interval_count'] : 1,
						'parent'  => 'payments',
						'type'    => 'number',
						'tooltip' => esc_html__( 'Enter the duration for accepting the recurring payment', 'everest-forms-pro' )
					)
				);

				everest_forms_panel_field(
					'select',
					'mollie',
					'interval',
					$this->form_data,
					esc_html__( 'Time period of the recurring payment', 'everest-forms-pro' ),
					array(
						'default' => isset( $payments['mollie']['interval'] ) ? $payments['mollie']['interval'] : '12 months',
						'parent'  => 'payments',
						'options' => array(
							'DAYS'   => __( 'Days', 'everest-forms-pro' ),
							'WEEKS'  => __( 'Weeks', 'everest-forms-pro' ),
							'MONTHS' => __( 'Months', 'everest-forms-pro' ),
							'YEARS'  => __( 'Years', 'everest-forms-pro' ),
						),
					)
				);

				$form_data_array = is_array( $this->form_data ) ? $this->form_data : array();

				if ( ! $this->form_has_payment_gateway_selector_field( $form_data_array ) ) {
					everest_forms_panel_field(
						'select',
						'mollie',
						'email',
						$this->form_data,
						esc_html__( 'Customer\'s Email', 'everest-forms-pro' ),
						array(
							'parent'      => 'payments',
							'subsection'  => 'recurring',
							'field_map'   => array( 'email' ),
							'placeholder' => esc_html__( '--- Select Email ---', 'everest-forms-pro' ),
							'tooltip'     => esc_html__( "Select the field that contains the customer's email address. Required.", 'everest-forms-pro' ),
						)
					);

					everest_forms_panel_field(
						'select',
						'mollie',
						'customer_first_name',
						$this->form_data,
						esc_html__( 'Customer\'s First Name', 'everest-forms-pro' ),
						array(
							'parent'      => 'payments',
							'subsection'  => 'recurring',
							'field_map'   => array( 'first-name' ),
							'placeholder' => esc_html__( '-- Select First Name --', 'everest-forms-pro' ),
							'tooltip'     => esc_html__( "Select the field that contains the customer's first name. Required.", 'everest-forms-pro' ),
						)
					);

					everest_forms_panel_field(
						'select',
						'mollie',
						'customer_last_name',
						$this->form_data,
						esc_html__( 'Customer\'s Last Name', 'everest-forms-pro' ),
						array(
							'parent'      => 'payments',
							'subsection'  => 'recurring',
							'field_map'   => array( 'last-name' ),
							'placeholder' => esc_html__( '-- Select Last Name --', 'everest-forms-pro' ),
							'tooltip'     => esc_html__( "Select the field that contains the customer's last name. Required.", 'everest-forms-pro' ),
						)
					);
				}
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';

		} else {
			echo "<p class='everest-forms-notice everest-forms-notice-info'>";
				echo wp_kses_post(
					sprintf(
						/* translators: %s: payment settings URL */
						__( 'Please enter mollie API key on <a href="%s">settings panel</a> to enable mollie.', 'everest-forms-pro' ),
						esc_url( admin_url( 'admin.php?page=evf-settings&tab=payment#everest-forms-settings-id-mollie' ) )
					)
				);
			echo '</p>';
		}

		echo '</div>';
	}

	/**
	 * Hide Mollie accordion chevron unless the form uses a Subscription Plan field.
	 *
	 * @param bool   $hidden     Whether the chevron is hidden.
	 * @param string $slug       Gateway slug.
	 * @param array  $field      Payment gateway selector field data.
	 * @param array  $form_data  Form data.
	 * @param string $panel_html Panel HTML.
	 * @return bool
	 */
	public function payment_gateway_selector_mollie_chevron_hidden( $hidden, $slug, $field, $form_data, $panel_html ) {
		unset( $field, $panel_html );
		if ( 'mollie' !== $slug ) {
			return $hidden;
		}
		if ( ! is_array( $form_data ) ) {
			$form_data = array();
		}
		return ! function_exists( 'evf_form_has_subscription_plan_field' ) || ! evf_form_has_subscription_plan_field( $form_data );
	}

	/**
	 * Render Mollie customer fields inside Payment Gateway selector → Mollie accordion.
	 *
	 * Accordion chevron is hidden via {@see payment_gateway_selector_mollie_chevron_hidden()} until a Subscription Plan field is on the form.
	 *
	 * @param string $html      Existing HTML (from other filters).
	 * @param string $slug      Gateway slug.
	 * @param array  $field     Payment gateway selector field data.
	 * @param array  $form_data Decoded form data for values/options.
	 * @return string
	 */
	public function payment_gateway_selector_mollie_panel_html( $html, $slug, $field, $form_data = array() ) {
		if ( 'mollie' !== $slug ) {
			return $html;
		}

		if ( ! is_array( $form_data ) ) {
			$form_data = array();
		}

		$payments = isset( $form_data['payments'] ) ? $form_data['payments'] : array();

		$out  = $html;
		$out .= '<div class="evf-pgw-mollie-settings evf-pgw-accordion-fields">';

		$out .= everest_forms_panel_field(
			'select',
			'mollie',
			'email',
			$form_data,
			esc_html__( 'Email', 'everest-forms-pro' ),
			array(
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'field_map'   => array( 'email' ),
				'placeholder' => esc_html__( '--- Select Email ---', 'everest-forms-pro' ),
				'tooltip'     => esc_html__( "Select the field to map as the customer's email address.", 'everest-forms-pro' ),
				'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
			),
			false
		);

		$out .= everest_forms_panel_field(
			'select',
			'mollie',
			'customer_first_name',
			$form_data,
			esc_html__( 'First Name', 'everest-forms-pro' ),
			array(
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'field_map'   => array( 'first-name' ),
				'placeholder' => esc_html__( '-- Select First Name --', 'everest-forms-pro' ),
				'tooltip'     => esc_html__( "Select the field to map as the customer's first name.", 'everest-forms-pro' ),
				'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
			),
			false
		);

		$out .= everest_forms_panel_field(
			'select',
			'mollie',
			'customer_last_name',
			$form_data,
			esc_html__( 'Last Name', 'everest-forms-pro' ),
			array(
				'parent'      => 'payments',
				'subsection'  => 'recurring',
				'field_map'   => array( 'last-name' ),
				'placeholder' => esc_html__( '-- Select Last Name --', 'everest-forms-pro' ),
				'tooltip'     => esc_html__( "Select the field to map as the customer's last name.", 'everest-forms-pro' ),
				'before_tooltip' => function_exists( 'evf_pgw_panel_required_asterisk' ) ? evf_pgw_panel_required_asterisk() : '',
			),
			false
		);

		$out .= everest_forms_panel_field(
			'text',
			'mollie',
			'redirect_url',
			$form_data,
			esc_html__( 'Redirect URL', 'everest-forms-pro' ),
			array(
				'default' => isset( $payments['mollie']['redirect_url'] ) ? $payments['mollie']['redirect_url'] : '',
				'parent'  => 'payments',
				'tooltip' => esc_html__( 'Returns to this page after payment', 'everest-forms-pro' ),
			),
			false
		);

		$out .= '</div>';

		unset( $field );
		return $out;
	}
}
