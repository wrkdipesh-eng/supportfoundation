<?php
/**
 * WooCommerce product data tabs form fields list table
 *
 * @package EverestForms\WooCommerce\Frontend\Frontend
 * @since 1.0.0
 */

namespace EverestForms\Pro\Addons\WooCommerce\Frontend;

/**
 * Frontend class
 *
 * @since 1.0.0
 */
class Frontend {


	/**
	 * Hooks in tab
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		 // load scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'evfwc_load_scripts' ) );

		// Use to add everest foems fields.
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'evfwc_before_add_to_cart_button' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'evfwc_add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'evfwc_get_item_data' ), 10, 2 );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'evfwc_custom_update_cart_item_price' ), 10, 1 );

		// Display extra fields data on WooCommerce checkout and order page.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'evfwc_checkout_create_order_line_item' ), 10, 4 );

	}

	/**
	 * Load scripts
	 *
	 * @since 1.0.0
	 */
	public function evfwc_load_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue Scripts.
		wp_register_script( 'evfwc-frontend', plugins_url( "src/Addons/WooCommerce/assets/js/frontend/evfwc-frontend{$suffix}.js", EFP_PLUGIN_FILE ), array(), EFP_VERSION, true );

		do_action( 'everest_forms_woocommerce_load_script' );
		do_action( 'everest_forms_woocommerce_js' );
	}

	/**
	 * Render the selected field on product page.
	 *
	 * @since 1.0.0
	 */
	public function evfwc_before_add_to_cart_button() {
		global $post;

		if ( false === $post->ID ) {
			return;
		}

		$_product     = wc_get_product( $post->ID );
		$_total_price = empty( $_product->get_sale_price() ) ? ( empty( $_product->get_price() ) ? $_product->get_regular_price() : $_product->get_price() ) : $_product->get_sale_price();

		$form_id = get_post_meta( $post->ID, 'everest_forms_woocommerce_product_page_settings_form_' . $post->ID, true );

		if ( empty( $form_id ) ) {
			return;
		}

		$product_form_fields = get_option( 'everest_forms_woocommerce_product_page_fields_' . $post->ID, array() );

		$form_template_details       = get_post( $form_id );
		$form_template_post_contents = evf_decode( $form_template_details->post_content );
		$form_data                   = apply_filters( 'everest_forms_frontend_form_data', $form_template_post_contents );

		$matched_form_id = 'form-' . $form_template_post_contents['id'];

		// enqueue scripts.
		wp_enqueue_script( 'evfwc-frontend' );
		wp_enqueue_script( 'jquery-validate' );

		if ( array_key_exists( $matched_form_id, $product_form_fields ) ) {

			// Form fields area.
			echo '<div class="everest-forms">';
			echo '<div class="evf-field-container">';
			echo '<div class="evf-frontend-row">';

			foreach ( $form_template_post_contents['form_fields'] as $key => $field ) {

				if ( in_array( $field['id'], $product_form_fields[ $matched_form_id ], true ) ) {

					$field_type = $field['type'];
					$field      = apply_filters( 'everest_forms_field_data', $field, $form_data );

					// Get field properties.
					$properties = \EVF_Shortcode_Form::get_field_properties( $field, $form_data );

					if ( 'date-time' === $field_type ) {
						wp_enqueue_style( 'flatpickr' );
						wp_enqueue_script( 'flatpickr' );
					}

					// Add properties to the field so it's available everywhere.
					$field['properties'] = $properties;

					$asterisk               = isset( $field['required'] ) ? ( '1' === $field['required'] ? '<span style="color: red;"> *</span>' : '' ) : '';
					$container_classes      = isset( $properties['container']['class'] ) ? implode( ' ', array_values( $properties['container']['class'] ) ) : '';
					$field_id               = isset( $properties['inputs']['primary']['attr']['conditional_id'] ) ? $properties['inputs']['primary']['attr']['conditional_id'] : '';
					$label_classes          = isset( $properties['label']['class'] ) ? implode( ' ', $properties['label']['class'] ) : '';
					$label_for              = isset( $properties['label']['attr']['for'] ) ? $properties['label']['attr']['for'] : '';
					$attr_id                = isset( $properties['container']['id'] ) ? $properties['container']['id'] : '';
					$required_field_message = isset( $properties['container']['data']['required-field-message'] ) ? $properties['container']['data']['required-field-message'] : '';
					$data_attr_array        = isset( $properties['container']['data'] ) ? array_keys( $properties['container']['data'] ) : '';
					$data_attr_name         = isset( $data_attr_array['0'] ) ? $data_attr_array['0'] : '';

					echo '<div class="evf-frontend-grid">';

					echo '<div id="' . $attr_id . '" class="' . $container_classes . '" data-' . $data_attr_name . '="' . $required_field_message . ' " data-field-id="' . $field_id . '">';

					echo '<label class="' . $label_classes . '" for="' . $label_for . '">' . $field['label'] . $asterisk . '</label>';

					// display fields
					do_action( 'everest_forms_display_field_' . $field_type, $field, '', $form_data );

					echo '</div>';
					echo '</div>';
				}
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * Get field type by meta key
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_key Field key.
	 * @param string $field_value Field's value .
	 */
	public function evfwc_format_field_values( $field_key, $field_value, $form_fields ) {
		switch ( $field_key ) {
			case 'checkbox':
			case 'multi_select2':
				$field_value = ( is_array( $field_value ) && ! empty( $field_value ) ) ? implode( ', ', $field_value ) : $field_value;
				break;
			case 'payment-checkbox':
			case 'payment-multiple':
				if ( ! is_array( $field_value ) ) {
					if ( array_key_exists( $field_value, $form_fields['choices'] ) ) {
						$field_value = $form_fields['choices'][ $field_value ]['value'];
					}
				} else {
					$value_array = array();
					foreach ( $field_value as $value ) {
						if ( array_key_exists( $value, $form_fields['choices'] ) ) {
							array_push( $value_array, $form_fields['choices'][ $value ]['value'] );
						}
					}

					$field_value = implode( ', ', $value_array );
				}
				break;
			case 'country':
				$countries = evf_get_countries();

				if ( array_key_exists( $field_value, $countries ) ) {
					$field_value = $countries[ $field_value ];
				}
				break;
			case 'image-upload':
			case 'file-upload':
				$decoded_field_value = evf_decode( wp_unslash( $field_value ) );
				$file_name_array     = array();
				$permanent_dir       = $this->get_permanent_dir();
				foreach ( $decoded_field_value as $value ) {
					$uploads     = wp_upload_dir();
					$tmp_root    = untrailingslashit( $uploads['basedir'] ) . '/everest_forms_uploads/tmp';
					$temp_path   = $tmp_root . '/' . $value['file'];
					$destination = $permanent_dir . '/' . $value['file'];
					$image_url   = '';

					if ( rename( $temp_path, $destination ) ) {
						$image_url = get_home_url() . '/wp-content/uploads/everest_forms_uploads/woocommerce/' . $value['file'];
					}

					$image_href = '<a href="' . $image_url . '" target="_blank">' . $value['name'] . '</a>';

					array_push( $file_name_array, $image_href );
				}
				$field_value = implode( ', ', array_values( $file_name_array ) );
				break;
			case 'wysiwyg':
				$field_value = html_entity_decode( $field_value );
				break;
			case 'address':
				$countries              = evf_get_countries();
				$field_value['country'] = isset( $field_value['country'] ) ? ( array_key_exists( $field_value['country'], $countries ) ? $countries[ $field_value['country'] ] : '' ) : '';
				$address                = implode( ",\n", array_values( $field_value ) );
				$field_value            = $address;
			default:
				$field_value = $field_value;
				break;
		}

		return $field_value;
	}


	/**
	 * Add cart item data for WooCommerce products.
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item_data Array of other cart item data.
	 * @param int   $product_id    ID of the product added to the cart.
	 * @param int   $variation_id  Variation ID of the product added to the cart.
	 *
	 * @return array Modified $cart_item_data with additional product form data.
	 */
	public function evfwc_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {

		if ( isset( $cart_item_data['evfwc_product_form_data'] ) ) {
			unset( $cart_item_data['evfwc_product_form_data'] );
		}

		$form_id = get_post_meta( $product_id, 'everest_forms_woocommerce_product_page_settings_form_' . $product_id, true );
		if ( empty( $form_id ) ) {
			return $cart_item_data;
		}

		$form_fields             = evf_get_form_fields( $form_id );
		$product_all_form_fields = get_option( 'everest_forms_woocommerce_product_page_fields_' . $product_id, array() );

		if ( empty( $product_all_form_fields ) ) {
			return $cart_item_data;
		}

		if ( array_key_exists( 'form-' . $form_id, $product_all_form_fields ) ) {
			$product_form_fields = $product_all_form_fields[ 'form-' . $form_id ];
		} else {
			return $cart_item_data;
		}

		$submited_product_form_data = array();

		foreach ( $product_form_fields as $field ) {

			if ( array_key_exists( $field, $form_fields ) ) {
				$field_label = $form_fields[ $field ]['label'];
				$field_type  = $form_fields[ $field ]['type'];
			}

			if ( 'image-upload' === $field_type || 'file-upload' === $field_type ) {
				$field_value = isset( $_POST[ 'everest_forms_' . $form_id . '_' . $field ] ) ? $_POST[ 'everest_forms_' . $form_id . '_' . $field ] : '';
			} else {
				$field_value = isset( $_POST['everest_forms']['form_fields'][ $field ] ) ? $_POST['everest_forms']['form_fields'][ $field ] : ''; // phpcs:ignore
			}

			$parse_field_value = $this->evfwc_format_field_values( $field_type, $field_value, $form_fields[ $field ] );

			$submited_product_form_data[ $field ] = array(
				'label' => $field_label,
				'type'  => $field_type,
				'value' => $parse_field_value,
				'key'   => $field_label,
			);
		}

		$evfwc_product_form_data = array(
			'form-id'   => $form_id,
			'form-data' => $submited_product_form_data,
		);

		$cart_item_data['evfwc_product_form_data'] = $evfwc_product_form_data;
		return $cart_item_data;
	}

	/**
	 * Retrieve additional item data for a WooCommerce cart item.
	 *
	 * This function is used to retrieve and merge additional item data, specifically product form data,
	 * to be displayed for a cart item in the WooCommerce cart.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item_data  Existing item data for the cart item.
	 * @param array $cart_item The cart item being processed.
	 *
	 * @return array Modified item data including product form data if available.
	 */
	public function evfwc_get_item_data( $item_data, $cart_item ) {

		if ( isset( $cart_item['evfwc_product_form_data'] ) ) {

			$evfwc_form_data = isset( $cart_item['evfwc_product_form_data']['form-data'] ) ? $cart_item['evfwc_product_form_data']['form-data'] : array();
			$item_data       = array_merge( $item_data, $evfwc_form_data );
		}
		return $item_data;
	}

	/**
	 * Custom function to add data to WooCommerce order line items during checkout.
	 *
	 * This function is hooked into the 'woocommerce_checkout_create_order_line_item' action hook.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order_Item_Product $item          The order line item being created.
	 * @param string                $cart_item_key  The key representing the cart item.
	 * @param array                 $values         Information about the cart item.
	 * @param WC_Order              $order          The order object.
	 */
	public function evfwc_checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {

		// Add field meta data.
		$evfwc_form_item_data = isset( $values['evfwc_product_form_data'] ) ? $values['evfwc_product_form_data'] : array();

		if ( empty( $evfwc_form_item_data ) ) {
			return $item;
		}
		foreach ( $evfwc_form_item_data['form-data'] as $item_data ) {

			$item->add_meta_data( $item_data['key'], $item_data['value'] );
		}

		// Add submit to order item.
		$item->add_meta_data( 'evfwc_product_form_data', $evfwc_form_item_data );
	}

	/**
	 * Update the cart item price based on checkbox state.
	 *
	 * @since 1.0.0
	 * @param object $cart Cart item.
	 */
	public function evfwc_custom_update_cart_item_price( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		$additional_price = 0;
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['evfwc_product_form_data']['form-data'] ) ) {
				foreach ( $cart_item['evfwc_product_form_data']['form-data'] as $cart_data ) {
					if ( in_array( $cart_data['type'], array( 'payment-checkbox', 'payment-multiple' ) ) ) {
						$price_array       = explode( ', ', $cart_data['value'] );
						$additional_price += array_sum( $price_array );
					}
				}
			}
			$cart_item['data']->set_price( $cart_item['data']->get_price() + $additional_price );
		}
	}

	/**
	 * Get permanent directory
	 *
	 * @since 1.0.0
	 */
	public function get_permanent_dir() {
		$uploads  = wp_upload_dir();
		$tmp_root = untrailingslashit( $uploads['basedir'] ) . '/everest_forms_uploads/woocommerce';

		if ( ! file_exists( $tmp_root ) || ! wp_is_writable( $tmp_root ) ) {
			wp_mkdir_p( $tmp_root );
		}

		$index = trailingslashit( $tmp_root ) . 'index.html';

		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		return $tmp_root;
	}
}
