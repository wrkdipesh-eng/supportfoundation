<?php
/**
 * Divider field.
 *
 * @package EverestForms_Pro\Fields
 * @since   xx.xx.xx
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Summary Class.
 */
class EVF_Field_Payment_Summary extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name     = __( 'Payment Summary', 'everest-forms-pro' );
		$this->type     = 'payment_summary';
		$this->icon     = 'evf-icon evf-icon-payment-summary';
		$this->order    = 85;
		$this->group    = 'payment';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'payment_summary_description',
					// 'show_hide_payment_summary'
				),
			),
		);

		parent::__construct();
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since xx.xx.xx
	 *
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$allowed_html = array(
			'label' => array(
				'class' => true,
			),
			'span' => array(
				'class' => true,
			),
			'div' => array(
				'class' => true,
			),
		);

		$output  = '<label class="label-title"><span class="text">'
			. esc_html__( 'Payment Summary', 'everest-forms-pro' )
			. '</span></label>';

		$output .= '<div class="everest-forms-payment-summary-form-builder">'
			. esc_html__( 'Payment Summary will be shown here', 'everest-forms-pro' )
			. '</div>';

		echo wp_kses( $output, $allowed_html );
	}


	/**
	 * Field display on the form front-end.
	 *
	 * @since xx.xx.xx
	 *
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$show_hide_payment_summary = isset( $field['show_hide_payment_summary' ] ) ? $field[ 'show_hide_payment_summary' ] : '0';
		$payment_summary_description = ! empty( $field['payment_summary_description'] ) ? $field['payment_summary_description'] : 'No payment items has been selected yet';
		?>
		<div class="evf-el-group evf-payment_summary_component" tabindex="-1">
			<div class="evf_payment_summary">
				<div class="evf-payment-summary-header">
					<span class="evf-summary-title"><?php echo __( 'Payment Summary', 'everest-forms-pro'); ?></span>
				</div>
				<div class="evf_table_wrapper">
					<table class="table evf_table input_items_table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Title', 'everest-forms-pro' ); ?></th>
								<th><?php echo esc_html__( 'Price', 'everest-forms-pro' ); ?></th>
								<th><?php echo esc_html__( 'Qty', 'everest-forms-pro' ); ?></th>
								<th><?php echo esc_html__( 'Total', 'everest-forms-pro' ); ?></th>
							</tr>
						</thead>
						<tbody class="everest-forms-payment-summary-items"></tbody>
						<tfoot>
							<tr>
								<th class="item_right" colspan="3"><?php echo esc_html__( 'Total', 'everest-forms-pro' ); ?></th>
								<th class="everest-forms-payment-summary-item-final-amount"></th>
							</tr>
						</tfoot>
					</table>
					<div class="evf_payment_summary_fallback" style="display:none;">
						<?php echo esc_html( $payment_summary_description ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @param int    $field_id     Field id.
	 * @param array  $field_submit Field submit value.
	 * @param array  $form_data    Form data object.
	 * @param string $meta_key     Meta key data for the field.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {}
}
