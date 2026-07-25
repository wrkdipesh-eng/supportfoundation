<?php
/**
 * WooCommerce product data tabs form fields list table
 *
 * @package EverestForms\WooCommerce\Admin\FieldListTable
 * @since 1.0.0
 */

namespace EverestForms\Pro\Addons\WooCommerce\Admin;

/**
 * FieldListTable class
 *
 * @since 1.0.0
 */
class FieldListTable {

	/**
	 * Form id to list fields.
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $form_id = 0;

	/**
	 * List checkbox option key.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $cb_option_key = '';

		/**
		 * Get Form field list table columns.
		 *
		 * @return array Columns.
		 */
	public function get_list_columns() {

		$columns                = array();
		$columns['cb']          = '<input type="checkbox" class="evfwc-select-all" />';
		$columns['field_label'] = __( 'Field Label', 'everest-forms-pro' );
		$columns['field_name']  = __( 'Field ID', 'everest-forms-pro' );
		$columns['plugin']      = __( 'Plugin', 'everest-forms-pro' );

		return apply_filters( 'everest_forms_woocommerce_field_list_table_columns', $columns );
	}

	/**
	 * Display List header.
	 *
	 * @since 1.0.0
	 */
	public function list_header() {
		$columns = $this->get_list_columns();
		$header  = '<tr>';

		foreach ( $columns as $column_key => $column_name ) {
			$header .= sprintf( '<th id="%s"> %s </th>', $column_key, $column_name );
		}

		$header .= '</tr>';

		return $header;
	}

	/**
	 * Display list content.
	 *
	 * @since 1.0.0
	 */
	public function list_content() {
		$fields = evf_get_form_fields( $this->form_id );

		$cb_fields = get_option( preg_replace( '/\//', '', $this->cb_option_key ), array() );

		$form_id     = 'form-' . $this->form_id;
		$mapped_name = array();

		if ( is_wp_error( $fields ) ) {
			return sprintf( '<tr><td colspan="4"><h4 align="center">%s</h4></td></tr>', $fields->get_error_message() );
		}

		foreach ( $cb_fields as $cb_key => $cb_value ) {
			if ( $cb_key === $form_id ) {
				$mapped_name = $cb_value;
			}
		}

		$exclude_fields = array(
			'payment-single',
		);

		$field_elements = '';
		$exclude_fields = apply_filters( 'everest_forms_woocommerce_exclude_sync_fields', $exclude_fields );

		if ( $fields ) {
			foreach ( $fields as $field_name => $field_details ) {
				if ( in_array( $field_details['type'], $exclude_fields, true ) ) {
					continue;
				}
				$checked         = ( in_array( $field_name, $mapped_name, true ) ) ? ' checked="checked"' : '';
				$field_elements .= '<tr>';
				$field_elements .= sprintf( '<td><input type="checkbox" name="%s[]" value="%s"%s /></td>', preg_replace( '/\//', '', $this->cb_option_key ), $field_name, $checked );
				$field_elements .= sprintf( '<td>%s</td>', $field_details['label'] );
				$field_elements .= sprintf( '<td>%s</td>', $field_name );
				$field_elements .= sprintf( '<td>%s</td>', 'Everest Forms' );
				$field_elements .= '</tr>';
			}
		}

		if ( '' === $field_elements ) {
			$field_elements = sprintf(
				'<tr><td colspan="4"><h4>%s</h4></td></tr>',
				esc_html__( 'No fields found to sync.', 'everest-forms-pro' )
			);
		}

		return $field_elements;
	}

	/**
	 * Display table list with header, footer and body contents.
	 *
	 * @param int    $form_id Form ID.
	 * @param string $cb_option_key Option key of checkbox.
	 * @param bool   $return Return.
	 *
	 * @since 1.0.0
	 */
	public function display_table_list( $form_id, $cb_option_key, $return = false ) {
		$this->form_id       = $form_id;
		$this->cb_option_key = $cb_option_key;

		ob_start();
		?>
			<table class="wp-list-table widefat fixed">
				<thead>
				<?php echo $this->list_header(); ?>
				</thead>
				<tbody>
				<?php echo $this->list_content(); ?>
				</tbody>
				<tfoot>
				<?php echo $this->list_header(); ?>
				</tfoot>
			</table>
		<?php

		$field_table = ob_get_clean();

		if ( $return ) {
			return $field_table;
		}

		echo $field_table;

	}
}
