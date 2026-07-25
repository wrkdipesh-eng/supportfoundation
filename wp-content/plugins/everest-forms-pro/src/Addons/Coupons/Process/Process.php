<?php
/**
 * Coupons Process.
 *
 * @package EverestForms\Pro\Addons\Coupons\Process
 * @since   3.0.5
 */

namespace EverestForms\Pro\Addons\Coupons\Process;

use EverestForms\Pro\Addons\Coupons\CouponsListTable\CouponsListTable;
use EverestForms\Pro\Addons\Coupons\CouponForm;
use EverestForms\Pro\Addons\Coupons\BulkCouponForm;
use EVF_Bulk_Coupon_Generator;

defined( 'ABSPATH' ) || exit;

/**
 * Process Class.
 *
 * @since 3.0.5
 */
class Process {

	private static $background_process;

	/**
	 * Constructor.
	 *
	 * @since 3.0.5
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'coupons_menu' ), 35 );
		add_action( 'admin_init', array( $this, 'register_post_types' ), 5 );
		add_action( 'admin_init', array( $this, 'insert_coupon' ) );
		if ( isset( $_POST['evf-bulk-coupon'] ) && $_POST['evf-bulk-coupon'] === 'Save' ) {
			add_action( 'admin_init', array( $this, 'generate_bulk_coupon_code' ) );
		}
		add_action( 'admin_init', array( $this, 'update_coupon' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 11, 3 );
		add_filter( 'screen_options_show_screen', array( $this, 'remove_screen_options' ) );
		add_filter( 'everest_forms_screen_ids', array( $this, 'evf_coupons_add_screen_id' ) );
		add_action( 'wp_ajax_everest_forms_coupons_apply', array( $this, 'check_coupon' ) );
		add_action( 'wp_ajax_nopriv_everest_forms_coupons_apply', array( $this, 'check_coupon' ) );

		add_filter( 'everest_forms_coupon_discount', array( $this, 'coupon_discount' ), 10, 4 );
		add_filter( 'everest_forms_coupon_discount_map_field', array( $this, 'discount_map_field' ), 10, 2 );
		include_once plugin_dir_path( EFP_PLUGIN_FILE ) . 'src/Addons/Coupons/class-evf-bulk-coupon-generator.php';

		add_action( 'everest_forms_complete_entry_save', array( $this, 'save_applied_coupons_data' ), 10, 5 );

		self::$background_process = new EVF_Bulk_Coupon_Generator();
	}

	/**
	 * Discount Map Field.
	 *
	 * @param mixed $field Field.
	 * @param mixed $form_data Form Data.
	 * @return mixed Map field.
	 */
	public function discount_map_field( $field, $form_data ) {
		foreach ( $form_data['form_fields'] as $field ) {
			if ( 'payment-coupon' === $field['type'] ) {
				return $field['map_field'];
			}
		}
	}

	/**
	 * Coupon Discount.
	 *
	 * @param mixed $discount Discount.
	 * @param mixed $total Total.
	 * @param mixed $fields Fields.
	 * @param mixed $form_data Form Data.
	 * @return mixed $discount Discount.
	 */
	public function coupon_discount( $discount, $total, $fields, $form_data ) {
		foreach ( $fields as $field ) {
			if ( 'payment-coupon' === $field['type'] ) {
				$coupon_field          = $form_data['form_fields'][ $field['id'] ];
				$coupon_field['value'] = $field['value'];
				break;
			}
		}

		$coupon_field = ! empty( $form_data['applied_coupons_data'] ) ? $form_data['applied_coupons_data'] : '';

		if ( ! empty( $coupon_field ) && ! is_array( $coupon_field) ) {
			$coupon_code = $coupon_field['value'];
			if ( empty( $coupon_code ) ) {
				return $discount;
			}
			return $this->calculate_coupon_discount( $coupon_code, $coupon_field, $total, $form_data, $fields );
		}elseif( ! empty( $coupon_field ) ){
			foreach ( $coupon_field as $key => $value ) {
				$coupon_code = ! empty( $value['code'] ) ? $value['code'] : '';
				if ( empty( $coupon_code ) ) {
					continue;
				}
				$discount += $this->calculate_coupon_discount( $coupon_code, $total, $value, $form_data, $fields );
			}
		}
		return $discount;
	}

	public function calculate_coupon_discount( $coupon_code, $total, $coupon_field, $form_data, $fields ) {
			if ( empty( $coupon_code ) ) {
				return $discount;
			}
			$all_coupons = self::get_coupons();
			foreach ( $all_coupons as $coupon ) {
				$date = time();
				if ( $date >= strtotime( $coupon['start_date'] ) && $date <= strtotime( $coupon['end_date'] ) && '1' === $coupon['status'] && in_array( (int) $form_data['id'], $coupon['applicable_forms'], true ) && $coupon_code === $coupon['code'] ) {
					$map_field = ! empty( 'map_field' ) && ! empty( $fields[ $coupon_field['map_field'] ] ) ? $fields[ $coupon_field['map_field'] ] : '';
					if ( 'fixed' === $coupon['type'] ) {
						return preg_replace( '/^.*?\;/', '', $coupon['amount'] );
					}
					$coupon['amount'] = str_replace( '%', '', $coupon['amount'] );
					if ( ! empty( $map_field ) ) {
						return $map_field['amount_raw'] * $coupon['amount'] / 100;
					} else {
						return $total * $coupon['amount'] / 100;
					}
				}
			}
		return $discount;
	}

	/**
	 * Validate Coupons.
	 *
	 * @return void
	 */
	public function check_coupon() {
		if ( ! isset( $_POST['action'] ) || ! isset( $_POST['nonce'] ) || 'everest_forms_coupons_apply' !== sanitize_title( wp_unslash( $_POST['action'] ) ) || wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ), 'everest_forms_coupons_nonce' ) ) ) {
			return;
		}

		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
		$coupon_id   = isset( $_POST['coupon_id'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_id'] ) ) : '';

		if ( empty( $coupon_code ) || empty( $coupon_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Coupon code is missing', 'everest-forms-pro' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? sanitize_title( wp_unslash( $_POST['form_id'] ) ) : 0;

		if ( empty( $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Form ID is missing', 'everest-forms-pro' ) ) );
		}

		$data = self::validate_coupon( $form_id, $coupon_id, $coupon_code );
		if ( ! empty( $data['status'] ) ) {
			wp_send_json_success( $data );
		}
		wp_send_json_error( $data );
	}

	/**
	 * Validate Coupon.
	 *
	 * @param mixed $form_id Form ID.
	 * @param mixed $coupon_id Coupon ID.
	 * @param mixed $coupon_code Coupon Code.
	 * @return mixed $data Coupon Data.
	 */
	public static function validate_coupon( $form_id, $coupon_id, $coupon_code, $all_form_data = array(), $context = '' ) {
		$form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );
		$applied_coupons_data = ! empty( $all_form_data['applied_coupons_data'] ) ? $all_form_data['applied_coupons_data'] : array();

		if ( 'form_submission' === $context && empty( $applied_coupons_data ) ) {
			return;
		}

		if ( empty( $form_data['form_fields'][ $coupon_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Coupon Field is removed or missing', 'everest-forms-pro' ) ) );
		}

		$coupon_field = $form_data['form_fields'][ $coupon_id ];

		$coupons = ! empty( $coupon_field['coupons'] ) ? explode( ',', $coupon_field['coupons'] ) : array();

		$all_coupons = self::get_coupons();

		if ( empty( $applied_coupons_data ) ) {
			return self::validate_on_apply_coupon( $all_coupons, $coupon_code, $form_data, $coupon_field, $form_id );
		} else {
			return self::validate_coupon_on_form_submission( $applied_coupons_data, $all_coupons, $form_id, $form_data, $coupon_field, $context, $all_form_data );
		}
		return array(
			'status'  => false,
			'message' => ! empty( $coupon_field['invalid_message'] ) ? esc_html( $coupon_field['invalid_message'] ) : esc_html__( 'Coupon code is invalid or expired', 'everest-forms-pro' ),
		);
	}

	/**
	 * Validate coupon on form submission.
	 */
	public static function validate_coupon_on_form_submission( $applied_coupons_data, $all_coupons, $form_id, $form_data, $coupon_field, $context, $all_form_data = array() ){
		$validated_coupons = array();
		$non_stackable_found = false;

		foreach ( $applied_coupons_data as $applied_coupon ) {
			$matched_coupon = false;

			foreach ( $all_coupons as $coupon ) {
				$date = time();

				if (
					$date >= strtotime( $coupon['start_date'] ) &&
					$date <= strtotime( $coupon['end_date'] ) &&
					'1' === $coupon['status'] &&
					in_array( (int) $form_id, $coupon['applicable_forms'], true ) &&
					$applied_coupon['code'] === $coupon['code']
				) {
					$matched_coupon = true;

					$map_field = ! empty( $coupon_field['map_field'] ) && ! empty( $form_data['form_fields'][ $coupon_field['map_field'] ] )
						? $form_data['form_fields'][ $coupon_field['map_field'] ]
						: '';

					$coupon_limit = ! empty( $coupon['coupon_limit'] ) ? absint( $coupon['coupon_limit'] ) : 0;

					if ( ! empty( $coupon_limit ) ) {
						$post_id       = isset( $coupon['id'] ) ? absint( $coupon['id'] ) : 0;
						$current_usage = get_post_meta( $post_id, '_evf_coupon_usage', true );
						$current_usage = ! empty( $current_usage ) ? absint( $current_usage ) : 0;

						if ( $current_usage >= $coupon_limit ) {
							return array(
								'status'  => false,
								'type'    => 'limit_reached',
								'message' => __( 'Coupon usage limit has been reached.', 'everest-forms-pro' ),
							);
						} elseif ( 'form_submission' === $context ) {
							update_post_meta( $post_id, '_evf_coupon_usage', $current_usage + 1 );
						}
					}

					$minimum_purchase = ! empty( $coupon['minimum_purchase'] ) ? absint( $coupon['minimum_purchase'] ) : 0;

					if ( ! empty( $minimum_purchase ) && 'form_submission' === $context ) {
						$total_amount = self::get_total_field_amount( $all_form_data );

						if ( $total_amount < $minimum_purchase ) {
							return array(
								'status'  => false,
								'type'    => 'minimum_purchase_not_met',
								'message' => sprintf(
									__( 'Minimum purchase of %s is required to use this coupon.', 'everest-forms-pro' ),
									evf_format_amount( $minimum_purchase )
								),
							);
						}
					}

					$stackable = ! empty( $coupon['stackable'] )
						? evf_string_to_bool( absint( $coupon['stackable'] ) )
						: false;

					// If one non-stackable coupon is found and there are multiple applied coupons, stop.
					if ( ! $stackable && count( $applied_coupons_data ) > 1 ) {
						return array(
							'status'  => false,
							'type'    => 'non_stackable_coupon',
							'message' => __( 'This coupon cannot be combined with other coupons.', 'everest-forms-pro' ),
						);
					}

					if ( ! empty( $map_field ) ) {
						$message = sprintf( __( '- %1$s', 'everest-forms-pro' ), $coupon['amount'] );
					} else {
						$total_field_label = __( 'Total', 'everest-forms-pro' );

						foreach ( $form_data['form_fields'] as $field ) {
							if ( 'payment-total' === $field['type'] ) {
								$total_field_label = $field['label'];
								break;
							}
						}

						$message = sprintf( __( '- %1$s', 'everest-forms-pro' ), $coupon['amount'] );
					}

					$currency   = get_option( 'everest_forms_currency', 'USD' );
					$currencies = evf_get_currencies();

					$validated_coupons[] = array(
						'status'           => true,
						'discount_type'    => $coupon['type'],
						'amount'           => $coupon['amount'],
						'map_field'        => $coupon_field['map_field'],
						'minimum_purchase' => $minimum_purchase,
						'currency_symbol'  => sanitize_text_field( $currencies[ $currency ]['symbol'] ),
						'coupon_code'      => $coupon['code'],
						'stackable'        => $stackable,
					);

					break;
				}
			}

			if ( ! $matched_coupon ) {
				return array(
					'status'  => false,
					'type'    => 'invalid_coupon',
					'message' => __( 'Invalid coupon code.', 'everest-forms-pro' ),
				);
			}
		}

		return array(
			'status'  => true,
			'message' => __( 'Coupons validated successfully.', 'everest-forms-pro' ),
			'coupons' => $validated_coupons,
		);
	}
	/**
	 * Validate coupon on apply coupon.
	 */
	public static function validate_on_apply_coupon( $all_coupons, $coupon_code, $form_data, $coupon_field, $form_id ){
		foreach ( $all_coupons as $coupon ) {
			$date = time();
			if ( $date >= strtotime( $coupon['start_date'] ) && $date <= strtotime( $coupon['end_date'] ) && '1' === $coupon['status'] && in_array( (int) $form_id, $coupon['applicable_forms'], true ) && $coupon_code === $coupon['code'] ) {
				$map_field     = ! empty( 'map_field' ) && ! empty( $form_data['form_fields'][ $coupon_field['map_field'] ] ) ? $form_data['form_fields'][ $coupon_field['map_field'] ] : '';
				$coupon_limit  = ! empty( $coupon['coupon_limit'] ) ? absint( $coupon['coupon_limit'] ) : 0;

				if ( ! empty( $coupon_limit ) ) {
					$post_id       = isset( $coupon['id'] ) ? absint( $coupon['id'] ) : 0;
					$current_usage = get_post_meta( $post_id, '_evf_coupon_usage', true );
					$current_usage = ! empty( $current_usage ) ? absint( $current_usage ) : 0;

					if ( $current_usage >= $coupon_limit ) {
						return array(
							'status'  => false,
							'type'    => 'limit_reached',
							'message' => __( 'Coupon usage limit has been reached.', 'everest-forms-pro' ),
						);
					}
				}

				$minimum_purchase = ! empty( $coupon['minimum_purchase'] ) ? absint( $coupon['minimum_purchase'] ) : 0;

				$stackable = ! empty( $coupon['stackable'] ) ? evf_string_to_bool( absint( $coupon['stackable'] ) )  : false;

				if ( ! empty( $map_field ) ) {
					/* translators: 1: Label. 2: Discount */
					$message = sprintf( __( '- %1$s', 'everest-forms-pro' ), $coupon['amount'] );
				} else {
					$total_field_label = __( 'Total', 'everest-forms-pro' );
					foreach ( $form_data['form_fields'] as $field ) {
						if ( 'payent-total' === $field['type'] ) {
							$total_field_label = $field['label'];
							break;
						}
					}
					/* translators: 1: Label. 2: Discount */
					$message = sprintf( __( '- %1$s', 'everest-forms-pro' ), $coupon['amount'] );
				}

				$currency   = get_option( 'everest_forms_currency', 'USD' );
				$currencies = evf_get_currencies();

				$data = array(
					'status'           => true,
					'discount_type'    => $coupon['type'],
					'amount'           => $coupon['amount'],
					'map_field'        => $coupon_field['map_field'],
					'minimum_purchase' => $minimum_purchase,
					'currency_symbol'  => sanitize_text_field( $currencies[ $currency ]['symbol'] ),
					'message'          => $message,
					'coupon_code'      => $coupon['code'],
					'stackable'        => $stackable,
				);
				return $data;
			}
		}

		return array(
			'status'  => false,
			'type'    => 'invalid_coupon',
			'message' => __( 'Invalid coupon code.', 'everest-forms-pro' ),
		);
	}

	/**
	 * Add menu item.
	 */
	public function coupons_menu() {
		$coupon_page = add_submenu_page( 'everest-forms', esc_html__( 'Coupons', 'everest-forms-pro' ), esc_html__( 'Coupons', 'everest-forms-pro' ), 'manage_everest_forms', 'evf-coupons', array( $this, 'coupons_page' ) );
		add_action( 'load-' . $coupon_page, array( $this, 'coupon_page_init' ) );
	}

	/**
	 * Loads template page.
	 */
	public function coupon_page_init() {
		global $CouponsListTable;
		$CouponsListTable = new CouponsListTable();

		// Add screen option.
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'evf_coupons_per_page',
			)
		);

		do_action( 'everest_forms_coupons_page_init' );
	}

	/**
	 * Init the coupons page.
	 */
	public function coupons_page() {
		if ( isset( $_REQUEST['create-coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			CouponForm::output_form_html();
		} elseif ( ( isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] ) && isset( $_REQUEST['coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$coupon_id = absint( wp_unslash( $_REQUEST['coupon'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$post      = get_post( $coupon_id );
			$coupon    = ! empty( $post->post_content ) ? evf_decode( $post->post_content ) : '';
			CouponForm::output_form_html( 'edit', $coupon );
		} elseif ( isset( $_REQUEST['create-bulk-coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			BulkCouponForm::output_bulk_coupon_form_html();
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output.
	 */
	public static function table_list_output() {
		global $CouponsListTable;

		if ( ! $CouponsListTable ) {
			return;
		}

		$CouponsListTable->process_bulk_action();
		$CouponsListTable->prepare_items();

		$current_tab      = 'email-templates';
		$use_react_header = apply_filters(
			'everest_forms_use_react_header',
			true,
			$current_tab
		);

		// React header.
		if ( $use_react_header ) :
			?>
		<div id="evf-react-header-root"
			data-active-menu="<?php echo esc_attr( $current_tab ); ?>">
		</div>
			<?php
	endif;
		?>

	<div class="wrap evf-coupon__wrapper">
		<?php settings_errors(); ?>
		<form id="form-list" method="post">
			<input type="hidden" name="page" value="evf-coupons" />
			<div class="everest-forms-base-list-table-heading"
				style="display:flex;justify-content:space-between;align-items:center;">

				<div style="display:flex;align-items:center;gap:16px;">
					<span class="evf-forms-title">
						<?php esc_html_e( 'Coupons', 'everest-forms-pro' ); ?>
					</span>

					<?php if ( current_user_can( 'manage_everest_forms' ) ) : ?>
						<a id="everest-forms-coupons__create-btn" href="javascript:void(0);" class="page-title-action" style="margin: 0;">
								<?php esc_html_e( 'Add New', 'everest-forms-pro' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="search-box">
					<?php $CouponsListTable->search_box( esc_html__( 'Search Coupons', 'everest-forms-pro' ), 'search_input' ); ?>
				</div>

			</div>

			<?php
			$CouponsListTable->views();
			$CouponsListTable->display();
			wp_nonce_field( 'save', 'everest-forms-email-templates_nonce' );
			?>
		</form>
	</div>
		<?php
	}

	/**
	 * Insert Coupon.
	 *
	 * @since 1.0.0
	 */
	public function insert_coupon() {
		// Check for non empty $_POST.
		if ( ! isset( $_POST['evf-coupons-nonce'], $_POST['evf-coupon-insert'] ) ) {
			return;
		}

		// Nonce check.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['evf-coupons-nonce'] ) ), 'evf_coupons_nonce' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms-pro' ) );
		}
		$data = $this->process_coupon( $_POST );

		if ( 0 >= count( $data['msg'] ) ) {
			$post_id = wp_insert_post(
				array(
					'post_content' => wp_json_encode( $data['data'] ),
					'post_title'   => $data['data']['title'],
					'post_type'    => 'evf_coupons',
					'post_status'  => 'publish',
				)
			);

			if ( $post_id ) {
				add_settings_error(
					'coupon_insert_action',
					'coupon_insert_action',
					esc_html__( 'Coupon created successfully.', 'everest-forms-pro' ),
					'updated'
				);
				$_POST = array();
			} else {
				add_settings_error(
					'coupon_insert_action',
					'coupon_insert_action',
					esc_html__( 'Sorry!, error while creating coupon.', 'everest-forms-pro' ),
					'error'
				);
			}
		} else {
			foreach ( $data['msg'] as $key => $value ) {
				self::evf_coupons_errors()->add( 'evf_coupon_' . $key, $value );
			}
			add_settings_error(
				'coupon_insert_action',
				'coupon_insert_action',
				esc_html__( 'There was an error while saving your coupon.', 'everest-forms-pro' ),
				'error'
			);
		}
	}

	/**
	 * Edit Coupon.
	 *
	 * @since 1.0.0
	 */
	public function update_coupon() {
		// Check for non empty $_POST.
		if ( ! isset( $_POST['evf-coupons-nonce'], $_POST['evf-coupon-update'] ) ) {
			return;
		}

		// Nonce check.
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['evf-coupons-nonce'] ) ), 'evf_coupons_nonce' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms-pro' ) );
		}
		$post_id = isset( $_POST['evf_coupon_update_id'] ) ? absint( wp_unslash( $_POST['evf_coupon_update_id'] ) ) : 0;
		$data    = $this->process_coupon( $_POST );

		if ( 0 >= count( $data['msg'] ) ) {
			$post_id = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_json_encode( $data['data'] ),
					'post_title'   => $data['data']['title'],
					'post_type'    => 'evf_coupons',
					'post_status'  => 'publish',
				)
			);

			if ( $post_id ) {
				add_settings_error(
					'coupon_update_action',
					'coupon_update_action',
					esc_html__( 'Coupon updated successfully.', 'everest-forms-pro' ),
					'updated'
				);
			} else {
				add_settings_error(
					'coupon_update_action',
					'coupon_update_action',
					esc_html__( 'Sorry!, error while updating coupon.', 'everest-forms-pro' ),
					'error'
				);
			}
		} else {
			foreach ( $data['msg'] as $key => $value ) {
				self::evf_coupons_errors()->add( 'evf_coupon_' . $key, $value );
			}
			add_settings_error(
				'coupon_insert_action',
				'coupon_insert_action',
				esc_html__( 'There was an error while updating your coupon.', 'everest-forms-pro' ),
				'error'
			);
		}
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'evf_coupons_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Remove screen options.
	 */
	public function remove_screen_options() {
		if ( isset( $_REQUEST['create-coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		} elseif ( ( isset( $_REQUEST['action'] ) && 'edit' === $_REQUEST['action'] ) && isset( $_REQUEST['coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		} else {
			$current_screen = get_current_screen();

			if ( 'everest-forms_page_evf-coupons' === $current_screen->id ) {
				return true;
			}
		}
	}

	/**
	 * Add Coupons add-on screen_ids to the pool of everest forms screen ids.
	 *
	 * @param array $screen_ids Screens ids of everest forms and addons.
	 * @return array
	 */
	public function evf_coupons_add_screen_id( $screen_ids ) {
		$evffl_screen_ids = array(
			'everest-forms_page_evf-coupons',
		);

		$screen_ids = array_merge( $screen_ids, $evffl_screen_ids );
		return $screen_ids;
	}

	/**
	 * Register core post types.
	 */
	public function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'evf_coupons' ) ) {
			return;
		}

		register_post_type(
			'evf_coupons',
			apply_filters(
				'everest_forms_coupons_register_post_type',
				array(
					'labels'              => array(
						'name'                  => __( 'Coupons', 'everest-forms-pro' ),
						'singular_name'         => __( 'Coupon', 'everest-forms-pro' ),
						'all_items'             => __( 'Coupons', 'everest-forms-pro' ),
						'menu_name'             => _x( 'Coupons', 'Admin menu name', 'everest-forms-pro' ),
						'add_new'               => __( 'Add New', 'everest-forms-pro' ),
						'add_new_item'          => __( 'Add a new coupon', 'everest-forms-pro' ),
						'edit'                  => __( 'Edit', 'everest-forms-pro' ),
						'edit_item'             => __( 'Edit coupon', 'everest-forms-pro' ),
						'new_item'              => __( 'New coupon', 'everest-forms-pro' ),
						'view_item'             => __( 'View coupon', 'everest-forms-pro' ),
						'search_items'          => __( 'Search coupons', 'everest-forms-pro' ),
						'not_found'             => __( 'No coupons found', 'everest-forms-pro' ),
						'not_found_in_trash'    => __( 'No coupons found in trash', 'everest-forms-pro' ),
						'parent'                => __( 'Parent coupons', 'everest-forms-pro' ),
						'insert_into_item'      => __( 'Insert into coupon', 'everest-forms-pro' ),
						'filter_items_list'     => __( 'Filter coupons', 'everest-forms-pro' ),
						'items_list_navigation' => __( 'Coupons navigation', 'everest-forms-pro' ),
						'items_list'            => __( 'Coupons list', 'everest-forms-pro' ),
					),
					'public'              => false,
					'show_ui'             => true,
					'description'         => __( 'This is where you can add new coupons.', 'everest-forms-pro' ),
					'capability_type'     => 'post',
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'show_in_rest'        => true,
					'show_in_menu'        => false,
					'menu_position'       => 3,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => false,
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
				)
			)
		);
	}

	/**
	 * Process coupon.
	 *
	 * @param array $data Request Data.
	 * @since 1.0.0
	 */
	public function process_coupon( $data ) {
		$processed_data = array();
		$messages       = array();

		$title         = isset( $data['coupons']['title'] ) && ! empty( $data['coupons']['title'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['title'] ) ) : '';
		$prefix        = isset( $data['coupons']['prefix'] ) && ! empty( $data['coupons']['prefix'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['prefix'] ) ) : '';
		$coupon_number = isset( $data['coupons']['number'] ) && ! empty( $data['coupons']['prefix'] ) ? absint( sanitize_text_field( wp_unslash( $data['coupons']['prefix'] ) ) ) : 0;

		if ( ! is_numeric( $coupon_number ) ) {
			$messages['number'] = __( 'Please enter valid number', 'everest-forms-pro' );
		}

		if ( isset( $data['coupons']['prefix'] ) && ! empty( $data['coupons']['prefix'] ) && '' === $title ) {
			if ( preg_match( '/[^a-z0-9]/i', $prefix ) ) {
				$messages['prefix'] = __( 'Please enter a valid Coupon Code. The Coupon Code can only contain alphanumeric characters.', 'everest-forms-pro' );
			}

			$coupon_code             = strtoupper( $prefix . evf_get_random_string( 10 ) );
			$processed_data['title'] = $coupon_code;
		} elseif ( '' === $title && '' === $prefix ) {
			$messages['title'] = __( 'This field is required.', 'everest-forms-pro' );
		} else {
			$processed_data['title'] = $title;
		}

		$code = isset( $data['coupons']['code'] ) && ! empty( $data['coupons']['code'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['code'] ) ) : '';

		if ( '' === $code && '' === $prefix ) {
			$messages['code'] = __( 'This field is required.', 'everest-forms-pro' );
		} elseif ( ! empty( $prefix ) ) {
			$processed_data['code'] = $coupon_code;
		} elseif ( preg_match( '/[^a-z0-9]/i', $code ) ) {
			$messages['code'] = __( 'Please enter a valid Coupon Code. The Coupon Code can only contain alphanumeric characters.', 'everest-forms-pro' );
		} else {
			$processed_data['code'] = $code;
		}

		$type                   = isset( $data['coupons']['discount_type'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['discount_type'] ) ) : 'fixed';
		$processed_data['type'] = $type;

		$amount = isset( $data['coupons']['discount_amount'] ) && ! empty( $data['coupons']['discount_amount'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['discount_amount'] ) ) : '';

		if ( '' === $amount ) {
			$messages['amount'] = __( 'This field is required.', 'everest-forms-pro' );
		} else {
			$currency   = get_option( 'everest_forms_currency', 'USD' );
			$currencies = evf_get_currencies();

			if ( 'fixed' === $type ) {
				$formatted_amount = evf_format_amount( evf_sanitize_amount( $amount ) );

				if ( 0 < absint( $formatted_amount ) ) {
					$processed_data['amount'] = sanitize_text_field( $currencies[ $currency ]['symbol'] ) . $formatted_amount;
				} else {
					$messages['amount'] = __( 'Discount amount should be valid amount.', 'everest-forms-pro' );
				}
			} else {
				$sanitized_percent = floatval( preg_replace( '/\.(([^\.]*)\.)*/', '.$2', preg_replace( '/[^\d\.]/', '', $amount ) ) );

				if ( 0 < absint( $sanitized_percent ) ) {
					if ( preg_match( '/\./', $sanitized_percent ) && 2 < strlen( explode( '.', $sanitized_percent )[1] ) ) {
						$processed_data['amount'] = number_format( $sanitized_percent, 2 ) . '%';
					} else {
						$processed_data['amount'] = $sanitized_percent . '%';
					}
				} else {
					$messages['amount'] = __( 'Discount percent should be valid number.', 'everest-forms-pro' );
				}
			}
		}

		$start_date = isset( $data['coupons']['start_date'] ) && ! empty( $data['coupons']['start_date'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['start_date'] ) ) : '';
		if ( '' === $start_date ) {
			$messages['start_date'] = __( 'This field is required.', 'everest-forms-pro' );
		} else {
			$processed_data['start_date'] = $start_date;
		}

		$end_date = isset( $data['coupons']['end_date'] ) && ! empty( $data['coupons']['end_date'] ) ? sanitize_text_field( wp_unslash( $data['coupons']['end_date'] ) ) : '';
		if ( '' === $end_date ) {
			$messages['end_date'] = __( 'This field is required.', 'everest-forms-pro' );
		} else {
			$processed_data['end_date'] = $end_date;
		}

		if ( strtotime( $start_date ) > strtotime( $end_date ) ) {
			$messages['start_date'] = __( 'Start date can not be greater than end date.', 'everest-forms-pro' );
		}

		$coupon_limit = isset( $data['coupons']['coupon_limit'] ) && ! empty( $data['coupons']['coupon_limit'] ) ? absint( sanitize_text_field( wp_unslash( $data['coupons']['coupon_limit'] ) ) ) : 0;

		if ( ! is_numeric( $coupon_limit ) || 0 > $coupon_limit ) {
			$messages['coupon_limit'] = __( 'Please enter valid coupon limit.', 'everest-forms-pro' );
		} else {
			$processed_data['coupon_limit'] = $coupon_limit;
		}

		$minimum_purchase = isset( $data['coupons']['minimum_purchase'] ) && ! empty( $data['coupons']['minimum_purchase'] ) ? absint( sanitize_text_field( wp_unslash( $data['coupons']['minimum_purchase'] ) ) ) : 0;
		if ( ! is_numeric( $minimum_purchase ) || 0 > $minimum_purchase ) {
			$messages['minimum_purchase'] = __( 'Please enter valid minimum purchase amount.', 'everest-forms-pro' );
		} else {
			$processed_data['minimum_purchase'] = $minimum_purchase;
		}

		$stackable = isset( $data['coupons']['stackable'] ) && '1' === $data['coupons']['stackable'] ? '1' : '0';
		$processed_data['stackable'] = $stackable;

		$applicable_form_ids = isset( $data['coupons']['applicable_form_ids'] ) && ! empty( $data['coupons']['applicable_form_ids'] ) ? wp_unslash( $data['coupons']['applicable_form_ids'] ) : array();

		foreach ( $applicable_form_ids as $value ) {
			$processed_data['applicable_forms'][ absint( $value ) ] = absint( $value );
		}

		if ( isset( $data['coupons']['status'] ) ) {
			$today       = date_i18n( 'Y-m-d', strtotime( 'today' ) );
			$expiry_date = date_i18n( 'Y-m-d', strtotime( $end_date ) );

			if ( 'active' === $data['coupons']['status'] && strtotime( $expiry_date ) < strtotime( $today ) ) {
				$messages['status'] = __( 'Status can not be Active while End Date is less than Today Date.', 'everest-forms-pro' );
			}
		}

		$processed_data['status'] = isset( $data['coupons']['status'] ) && 'active' === $data['coupons']['status'] ? '1' : '0';

		return array(
			'data' => $processed_data,
			'msg'  => $messages,
		);
	}

	/**
	 * Used for tracking error messages.
	 *
	 * @since 1.0.0
	 */
	public static function evf_coupons_errors() {
		static $wp_error;
		$wp_error = isset( $wp_error ) ? $wp_error : new \WP_Error( null, null, null );
		return $wp_error;
	}

	/**
	 * Get Coupons.
	 *
	 * @return array Coupons.
	 */
	public static function get_coupons() {
		$args = array(
			'post_type'           => 'evf_coupons',
			'ignore_sticky_posts' => true,
			'post_status'         => array( 'any' ),
		);

		$query   = new \WP_Query( $args );
		$coupons = array();

		foreach ( $query->posts as $key => $post ) {
			$coupon = ! empty( $post->post_content ) ? evf_decode( $post->post_content ) : '';
			if ( '' !== $coupon ) {
				$coupon['id'] = $post->ID;
				$coupons[]    = $coupon;
			}
		}

		return $coupons;
	}

	/**
	 * Generates the bulk coupon code.
	 *
	 * @since 3.0.5
	 */
	public function generate_bulk_coupon_code() {
		if ( ! isset( $_POST['evf-bulk-coupon'] ) || $_POST['evf-bulk-coupon'] !== 'Save' ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST['evf-coupons-nonce'] ), 'evf_coupons_nonce' ) ) {
			wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'everest-forms-pro' ) );
		}

		$coupon_number = isset( $_POST['coupons']['number'] ) ? intval( $_POST['coupons']['number'] ) : 0;

		if ( self::$background_process->is_updating() ) {
			add_settings_error(
				'coupon_insert_action',
				'coupon_insert_action',
				esc_html__( 'A background process is already running. Please wait until it completes.', 'everest-forms-pro' ),
				'error'
			);
			return false;
		}

		$errors = array();

		for ( $i = 0; $i <= $coupon_number; $i++ ) {
			$data = $this->process_coupon( $_POST );

			if ( empty( $data['msg'] ) ) {
				self::$background_process->push_to_queue( array( 'data' => $data['data'] ) );
			} else {
				foreach ( $data['msg'] as $key => $value ) {
					self::evf_coupons_errors()->add( 'evf_coupon_' . $key, $value );
					$errors[] = $value;
				}
			}
		}

		if ( ! empty( $errors ) ) {
			add_settings_error(
				'coupon_insert_action',
				'coupon_insert_action',
				esc_html__( 'There is an error while creating coupons. Please see the errors below.', 'everest-forms-pro' ) . $errors[0],
				'error'
			);
		}

		self::$background_process->save()->dispatch();
	}

	public static function get_total_field_amount( $all_form_data ) {
		$total_amount = 0;
		$fields	   = ! empty( $all_form_data['form_fields'] ) ? $all_form_data['form_fields'] : array();
		$entry	   = ! empty( $all_form_data['entry'] ) ? $all_form_data['entry'] : array();
		$form_data = ! empty( $all_form_data['form_data'] ) ? $all_form_data['form_data'] : array();

		$total_amount      = evf_sanitize_amount( evf_get_total_payment( $fields, $entry, $form_data ) );

		return $total_amount;
	}

	/**
	 * Save applied coupons data to entry meta.
	 *
	 * @since 3.3.0
	 *
	 * @param int   $entry_id Entry ID.
	 * @param array $fields   List of form fields.
	 * @param array $entry    User submitted data.
	 * @param int   $form_id  Form ID.
	 * @param array $form_data Prepared form settings.
	 */
	public function save_applied_coupons_data( $entry_id, $fields, $entry, $form_id, $form_data ) {
		if ( ! empty( $form_data['applied_coupons_data'] ) ) {
			global $wpdb;
			$applied_coupons_data = $form_data['applied_coupons_data'];

			if ( is_string( $applied_coupons_data ) && evf_is_json( $applied_coupons_data ) ) {
				$applied_coupons_data = json_decode( wp_unslash( $applied_coupons_data ), true );
			}

			$wpdb->insert(
				$wpdb->prefix . 'evf_entrymeta',
				array(
					'entry_id'   => $entry_id,
					'meta_key'   => 'applied_coupons_data',
					'meta_value' => maybe_serialize( $applied_coupons_data ),
				)
			);
		}
	}
}
