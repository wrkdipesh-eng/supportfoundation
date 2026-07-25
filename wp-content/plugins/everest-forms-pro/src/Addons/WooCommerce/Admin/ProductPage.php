<?php
/**
 * WooCommerce Product Page Settings
 *
 * @package EverestForms\WooCommerce\Admin\ProductPage
 * @since 1.0.0
 */

namespace EverestForms\Pro\Addons\WooCommerce\Admin;

use EverestForms\Pro\Addons\WooCommerce\Admin\FieldListTable;

/**
 * ProductPage class
 *
 * @since 1.0.0
 */
class ProductPage {

	/**
	 * Setting Id.
	 *
	 * @var string
	 * @since 1.0.0
	 */

	public $product_id = false;
	/**
	 * Everest Forms product field key
	 *
	 * @var string
	 * @since 1.0.0
	 */

	private $evfwc_product_page_fields_option_key = 'everest_forms_woocommerce_product_page_fields';

	/**
	 * Everest Forms product field key
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $evfwc_product_page_settings_form = 'everest_forms_woocommerce_product_page_settings_form';

	/**
	 * Product
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $product = false;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// WooCommerce admin actions.
		add_action( 'woocommerce_product_data_tabs', array( $this, 'evfwc_product_data_tabs' ), 10, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'evfwc_product_data' ), 10 );
		add_action( 'save_post', array( $this,'evfwc_save_product_tab_settings' ), 10, 3 );
		// add_action( 'admin_head', array( $this, 'evfwc_admin_head' ) );

		// WooCommerce product filters.
		add_filter( 'woocommerce_add_to_cart_url', array( $this, 'evfwc_add_to_cart_url' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'evfwc_add_to_cart_url' ), 10, 2 );
		add_filter( 'woocommerce_product_supports', array( $this, 'evfwc_product_supports' ), 10, 3 );

		// Everest Forms action.
		add_filter( 'everest_forms_allowed_form_fields', array( $this, 'evfwc_allowed_fields' ) );
		add_action( 'admin_head', array( $this, 'evfwc_admin_head' ) );

	}


	/**
	 * Add icon before Everest Forms.
	 *
	 * @since 1.0.0
	 */
	public function evfwc_admin_head() {
		echo '<style>
				#woocommerce-product-data ul.wc-tabs li.evf_woocommerce_product_tabs_options a::before {
					font-family: "EverestForms";
					content: "\e904";
				}
			</style>';

	}

	/**
	 * WooCommerce - Product data tabs.
	 *
	 * @since 1.0.0
	 * @param array $product_data_tabs list of product tabs.
	 */
	public function evfwc_product_data_tabs( $product_data_tabs ) {
		$product_data_tabs['evf_woocommerce_product_tabs'] = array(
			'label'  => __( 'Everest Forms', 'everest-forms-pro' ),
			'target' => 'evf_product_data',
		);
		return $product_data_tabs;
	}

	/**
	 * Undocumented function
	 *
	 * @since 1.0.0
	 */
	public function evfwc_product_data() {
		// Check product.
		if ( ! self::evfwc_product_set() ) {
			return;
		}

		if ( false === $this->product_id ) {
			return;
		}

		$product_page_settings = $this->get_settings();

		echo '<div id="evf_product_data" class="panel woocommerce_options_panel">';
		$this->output( $product_page_settings );
		echo '</div>';
	}

	/**
	 * WooCommerce product page settings.
	 *
	 * @param array $settings Page Settings.
	 * @since 1.0.0
	 */
	public function output( $settings ) {
		$this->output_fields( $settings );

		$product_form_key = $this->evfwc_product_page_settings_form . '_' . $this->product_id;
		$form_id          = get_post_meta( $this->product_id, $product_form_key, true );

		$product_page_fields_meta_key = $this->evfwc_product_page_fields_option_key . '_' . $this->product_id;
		echo '<div class="everest_forms_woocommerce_form_fields_wrapper">';
		$this->evfwc_product_display_form_field_lists( $form_id, $product_page_fields_meta_key );
		echo '</div>';
	}

	/**
	 * Output for fields.
	 *
	 * @param array $options Settings.
	 * @since 1.0.0
	 */
	public function output_fields( $options ) {
		$settings = '';
		if ( is_array( $options ) && ! empty( $options ) ) {
			if ( isset( $options['sections'] ) ) {

				foreach ( $options['sections'] as $id => $section ) {
					if ( ! isset( $section['type'] ) ) {
						continue;
					}

					if ( 'card' === $section['type'] ) {
						$settings .= '<div class="everest-forms-card">';

						$header_css = '';
						if ( isset( $section['preview_link'] ) ) {
							$header_css = 'display:flex; justify-content: space-between;';
						}

						$settings .= '<div class="everest-forms-card__header" style="' . esc_attr( $header_css ) . '">';
						if ( ! empty( $section['title'] ) ) {
							$settings .= '<h3 class="everest-forms-card__title">' . esc_html( strtoupper( $section['title'] ) );

							if ( isset( $section['back_link'] ) ) {
								$settings .= wp_kses_post( $section['back_link'] );
							}

							$settings .= '</h3>';
						}

						if ( isset( $section['preview_link'] ) ) {
							$settings .= wp_kses_post( $section['preview_link'] );
						}

						$settings .= '</div>';

						if ( ! empty( $section['desc'] ) ) {
							$settings .= '<p class="evf-p-tag">' . wptexturize( wp_kses_post( $section['desc'] ) ) . '</p>';
						}
						$settings .= '<div class="everest-forms-card__body pt-0 pb-0">';
					}
					if ( is_array( $section['settings'] ) || is_object( $section['settings'] ) ) {
						foreach ( $section['settings'] as $key => $value ) {

							if ( ! isset( $value['type'] ) ) {
								continue;
							}

							if ( ! isset( $value['id'] ) ) {
								$value['id'] = '';
							}
							if ( ! isset( $value['row_class'] ) ) {
								$value['row_class'] = '';
							}
							if ( ! isset( $value['rows'] ) ) {
								$value['rows'] = '';
							}
							if ( ! isset( $value['cols'] ) ) {
								$value['cols'] = '';
							}
							if ( ! isset( $value['title'] ) ) {
								$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
							}
							if ( ! isset( $value['class'] ) ) {
								$value['class'] = '';
							}
							if ( ! isset( $value['css'] ) ) {
								$value['css'] = '';
							}
							if ( ! isset( $value['default'] ) ) {
								$value['default'] = '';
							}
							if ( ! isset( $value['desc'] ) ) {
								$value['desc'] = '';
							}
							if ( ! isset( $value['desc_tip'] ) ) {
								$value['desc_tip'] = false;
							}
							// Custom attribute handling.
							$custom_attributes = array();

							if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
								foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
									$custom_attributes[] = esc_attr( $attribute ) . '=' . esc_attr( $attribute_value ) . '';
								}
							}
							$tooltip_html = '';

							// Switch based on type.
							switch ( $value['type'] ) {
								// Select boxes.
								case 'select':
								case 'multiselect':
									$option_value = get_post_meta( $this->product_id, $value['id'], true ) ? get_post_meta( $this->product_id, $value['id'], true ) : $value['default'];

									$settings .= '<div class="everest-forms-global-settings">';
									$settings .= '<div>' . esc_html( $value['title'] ) . ' ' . wp_kses_post( $tooltip_html ) . '</div>';
									$settings .= '<div class="everest-forms-global-settings--field">';
									$multiple  = '';
									$type      = '';
									if ( 'multiselect' == $value['type'] ) {
										$type     = '[]';
										$multiple = 'multiple="multiple"';
									}

									$settings .= '<select
												name="' . esc_attr( $value['id'] ) . '' . $type . '"
												id="' . esc_attr( $value['id'] ) . '"
												style="' . esc_attr( $value['css'] ) . '"
												class="' . esc_attr( $value['class'] ) . '"
												' . esc_attr( implode( ' ', $custom_attributes ) ) . '
												' . esc_attr( $multiple ) . '>';

									foreach ( $value['options'] as $key => $val ) {
										$selected = '';

										if ( is_array( $option_value ) ) {
											$selected = selected( in_array( $key, $option_value ), true, false );
										} else {
											$selected = selected( $option_value, $key, false );
										}

										$settings .= '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>';
										$settings .= esc_html( $val );
										$settings .= '</option>';
									}

									$settings .= '</select>';
									$settings .= '</div>';
									$settings .= '</div>';
									break;
							}
							$settings .= ' </div > ';
							$settings .= ' </div > ';
						}
					}
				}
			}
		}
		echo $settings; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save product tab settings.
	 *
	 * @since 1.0.0
	 *
	 *  @param int    $post_id Post ID.
	 * @param object $post Post.
	 * @param   bool   $update Update.
	 */
	public function evfwc_save_product_tab_settings( $post_id, $post, $update ) {
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		$screen_id = $screen ? $screen->id : '';

		if ( '' === $screen_id || 'product' !== $screen_id ) {
			return;
		}

		// update the form id.
		$form_id = isset( $_POST[ $this->evfwc_product_page_settings_form . '_' . $post_id ] ) ? sanitize_text_field( $_POST[ $this->evfwc_product_page_settings_form . '_' . $post_id ] ) : null; // phpcs:ignore

		if ( ! is_null( $form_id ) ) {
			update_post_meta( $post_id, $this->evfwc_product_page_settings_form . '_' . $post_id, $form_id );
		}

		// update the form field list.
		$product_page_fields_option_key = $this->evfwc_product_page_fields_option_key . '_' . $post_id;
		$form_fields                    = isset( $_POST[ $product_page_fields_option_key ] ) ? $_POST[ $product_page_fields_option_key ] : array();// phpcs:ignore

		$saved_sync_field_data                 = get_option( $product_page_fields_option_key, array() );
		$sync_field_data[ 'form-' . $form_id ] = $form_fields;

		if ( empty( $saved_sync_field_data ) ) {
			update_option( $product_page_fields_option_key, $sync_field_data );
		} else {
			$new_sync_data = array_merge( $saved_sync_field_data, $sync_field_data );
			update_option( $product_page_fields_option_key, $new_sync_data );
		}
	}

	/**
	 * Set the product.
	 *
	 * @since 1.0.0
	 * @param int $product_id Product ID.
	 */
	public function evfwc_product_set( $product_id = false ) {

		if ( false === $product_id ) {

			$this->product_id = self::evfwc_product_id_get();

		} else {

			$this->product_id = $product_id;
		}

		if (
			empty( $this->product_id ) ||
			( 'product' !== get_post_type( $this->product_id ) )
		) {

			$this->product_id = false;

			return false;
		}

		// Read product.
		$this->product = wc_get_product( $this->product_id );
		if ( empty( $this->product ) ) {

			return false;
		}

		// Product is good.
		return $this->product;

	}

	/** Get product ID
	 *
	 * @since 1.0.0
	 * @param string $method method.
	 */
	public function evfwc_product_id_get( $method = 'queried_object' ) {

		switch ( $method ) {

			case 'queried_object':
				$queried_object = get_queried_object();
				$post_id        = ( ! is_null( $queried_object ) && isset( $queried_object->ID ) ) ? $queried_object->ID : false;
				break;

			case 'post':
				global $post;
				$post_id = ( $post && isset( $post->ID ) ) ? $post->ID : false;
				break;

		}

		/** Check post ID
		 *
		 * @param $post_id Post ID.
		 */
		if (
		empty( $post_id ) ||
		( 'product' !== get_post_type( $post_id ) )
		) {

			switch ( $method ) {

				case 'queried_object':
					$post_id = self::evfwc_product_id_get( 'post' );
					break;

				case 'post':
					$post_id = self::evfwc_product_id_get( 'root' );
					break;

				default:
					return false;
			}
		}

		return $post_id;
	}

	/**
	 * Woocommerece product page settings.
	 *
	 * @since 1.0.0
	 */
	public function evfwc_woocommerece_product_page_settings() {
		$forms    = evf_get_all_forms();
		$forms[0] = __( 'None', 'everest-forms-pro' );
		ksort( $forms );

		$product_page_settings = apply_filters(
			'everest_forms_woocommerce_product_page_settings',
			array(
				'title'    => __( 'Everest Form', 'everest-forms-pro' ),
				'sections' => array(
					'everest_forms_woocommerce_product_page_settings' => array(
						'title'    => esc_html__( 'Everest Forms', 'everest-forms-pro' ),
						'type'     => 'card',
						'desc'     => '',
						'settings' => array(
							array(
								'title'             => __( 'Select Everest Forms', 'everest-forms-pro' ),
								'desc'              => __( 'Choose everest forms to sync with WooCommerce.', 'everest-forms-pro' ),
								'id'                => 'everest_forms_woocommerce_product_page_settings_form_' . $this->product_id,
								'default'           => 'None',
								'type'              => 'select',
								'class'             => 'evf-enhanced-select evfwc-product-tab-panel-select',
								'css'               => 'min-width: 350px;',
								'desc_tip'          => true,
								'custom_attributes' => array( 'product_form_field_key' => $this->evfwc_product_page_fields_option_key . '_' . $this->product_id ),
								'options'           => $forms,
							),
						),
					),
				),
			)
		);

		return $product_page_settings;
	}

	/**
	 * Get Global Settings.
	 *
	 * @since 1.0.0
	 */
	public function get_settings() {
		return $this->evfwc_woocommerece_product_page_settings();
	}

	/**
	 * Product page form list.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param string $product_page_fields_meta_key Meta key for fields for product page.
	 */
	public function evfwc_product_display_form_field_lists( $form_id, $product_page_fields_meta_key ) {
		if ( $form_id && $product_page_fields_meta_key ) {
			$woocommerce_field_table_list = new FieldListTable();
			$woocommerce_field_table_list->display_table_list( $form_id, $product_page_fields_meta_key );
		}
	}

	/**
	 * Allow extra fields to sync in woocommerce.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param string $product_page_fields_meta_key Meta key for fields for product page.
	 */
	public function evfwc_allowed_fields( $allowed_form_fields ) {
		$fields_to_allow = array(
			'privacy-policy',
			'wysiwyg',
			'color',
			'date-time',
		);

		foreach ( $fields_to_allow as $field ) {
			array_push( $allowed_form_fields, $field );
		}

		return $allowed_form_fields;

	}

	/**
	 * Modify the Add to Cart URL for a product.
	 *
	 * This function is responsible for generating and customizing the URL used when adding
	 * a product to the cart. It checks for conditions such as whether the product is empty,
	 * whether the request is from the WC_Quick_View API, and retrieves the product and form IDs.
	 * If all conditions are met, it filters the product's permalink through the 'addons_add_to_cart_url'
	 * filter before returning it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url     The original Add to Cart URL.
	 * @param object $product The WooCommerce product object.
	 *
	 * @return string The modified Add to Cart URL.
	 */
	public function evfwc_add_to_cart_url( $url, $product ) {
		// Check product.
		if ( empty( $product ) ) {
			return $url;
		}

		// Check for quick view.
		if ( isset( $_GET['wc-api'] ) && ( 'WC_Quick_View' === $_GET['wc-api'] ) ) {	// phpcs:ignore
			return $url;
		}

		// Get product ID.
		$product_id = $product->get_id();

		// Check product ID.
		if ( empty( $product_id ) ) {
			return $url;
		}

		// Get form ID on product.
		$form_id = get_post_meta( $product_id, 'everest_forms_woocommerce_product_page_settings_form_' . $product_id, true );

		// Check form ID.
		if ( empty( $form_id ) ) {
			return $url;
		}

		// Return filtered permalink.
		return apply_filters( 'evfwc_addons_add_to_cart_url', get_permalink( $product_id ) );
	}

	/**
	 * Disable AJAX Add to Cart support for a specific product feature.
	 *
	 * This function is used to customize the support for specific product features.
	 * In this case, it checks if the feature being examined is 'ajax_add_to_cart'.
	 * If it is, it returns false to disable AJAX Add to Cart support for the product.
	 * Otherwise, it returns the original value of the $supports variable.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $supports An array of supported features for the product.
	 * @param string $feature  The feature being examined.
	 * @param object $product  The WooCommerce product object.
	 *
	 * @return array  The modified array of supported features.
	 */
	public function evfwc_product_supports( $supports, $feature, $product ) {
		// Get product ID.
		$product_id = $product->get_id();
		// Get form ID on product.
		$form_id = get_post_meta( $product_id, 'everest_forms_woocommerce_product_page_settings_form_' . $product_id, true );

		// Check form ID.
		if ( empty( $form_id ) ) {
			return $supports;
		}
		// Ensure feature is not ajax add to cart.
		if ( 'ajax_add_to_cart' !== $feature ) {
			return $supports;
		}

		return false;
	}
}
