<?php
/**
 * Coupon HTML Form Oupout.
 *
 * @package EverestForms\Pro\Addons\Coupons
 * @since   1.0.0
 */

namespace EverestForms\Pro\Addons\Coupons;

use EverestForms\Pro\Addons\Coupons\Process\Process;

defined( 'ABSPATH' ) || exit;

/**
 * Coupons table list class.
 */
class CouponForm {
	/**
	 * Output form html.
	 *
	 * @param string $action Action.
	 * @param array  $coupon Coupon.
	 */
	public static function output_form_html( $action = 'insert', $coupon = array() ) {
		$title   = 'insert' === $action ? 'Create New Single Coupon' : 'Edit Coupon';
		$post_id = isset( $_REQUEST['coupon'] ) ? absint( wp_unslash( $_REQUEST['coupon'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! empty( $coupon ) ) {
			$data = $coupon;
		}

		$errors = Process::evf_coupons_errors();

		$current_tab = 'evf-coupons';

		?>
		<form class="evf-coupons-form" method="post">
		<div class="evf-entry-header evf-coupon-header">
			<div class="evf-entry-header-left">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-coupons' ) ); ?>" class="evf-back-link">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22">
						<path d="M10.352 3.935a.917.917 0 0 1 1.296 1.297l-5.769 5.767 5.769 5.77a.916.916 0 1 1-1.296 1.296l-6.417-6.417a.917.917 0 0 1 0-1.296z"/>
						<path d="M17.416 10.083a.917.917 0 0 1 0 1.834H4.583a.917.917 0 0 1 0-1.834z"/>
					</svg>
				</a>

				<div class="evf-entry-title">
					<?php
					printf( esc_html__( '%s', 'everest-forms-pro' ), $title ); // phpcs:ignore
					?>
				</div>
			</div>

			<div class="evf-entry-header-right">
				<input type="submit" class="everest-forms-btn everest-forms-btn-primary everest-forms-coupons__save" value="<?php echo 'insert' === $action ? esc_attr__( 'Save', 'everest-forms-pro' ) : esc_attr__( 'Update Coupon', 'everest-forms-pro' ); ?>" name="<?php echo 'insert' === $action ? esc_attr( 'evf-coupon-insert' ) : esc_attr( 'evf-coupon-update' ); ?>">
			</div>
		</div>
		<div class="wrap">

		<?php // include_once plugin_dir_path( EFP_PLUGIN_FILE ) . 'src/Addons/Coupons/views/header.php'; ?>

			<div class="everest-forms-coupons-create-coupon__wrapper">
				<?php settings_errors(); ?>
					<?php if ( 'edit' === $action ) : ?>
						<input type="hidden" name="evf_coupon_update_id" value="<?php echo esc_attr( $post_id ); ?>">
					<?php endif; ?>
					<div class="evf-field">
						<label for="evf-coupon-name" class="evf-field-label"><?php esc_html_e( 'Coupon Name', 'everest-forms-pro' ); ?></label>
					<div class="evf-field-container">
						<?php
							/* translators: %s,%s: Coupon Title. */
							$value = isset( $_POST['coupons']['title'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), sanitize_text_field( wp_unslash( $_POST['coupons']['title'] ) ) ) : ( isset( $data['title'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), $data['title'] ): '' );  // phpcs:ignore
						?>
						<input class="evf-coupon-name" type="text" name="coupons[title]" id="evf-coupon-name" value="<?php echo $value; // phpcs:ignore ?>">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_title' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_title' ) ); ?></label>
						<?php endif; ?>
					</div>
					</div>
					<div class="evf-field">
						<label for="evf-coupon-code" class="evf-field-label"><?php esc_html_e( 'Coupon Code', 'everest-forms-pro' ); ?></label>
					<div class="evf-field-container">
						<?php
						/* translators: %s,%s: Coupon Code. */
						$value = isset( $_POST['coupons']['code'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), sanitize_text_field( wp_unslash( $_POST['coupons']['code'] ) ) ) : (  isset( $data['code'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), $data['code'] ): '' ); // phpcs:ignore
						?>
						<input class="evf-coupon-code" type="text" name="coupons[code]" id="evf-coupon-code" value="<?php echo $value; // phpcs:ignore ?>">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_code' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_code' ) ); ?></label>
						<?php endif; ?>
					</div>
					</div>
					<div class="evf-field">
						<label for="" class="evf-field-label"><?php esc_html_e( 'Discount Type', 'everest-forms-pro' ); ?></label>
						<label class="evf-coupon-discount__type">
							<?php

							$checked = esc_attr( 'checked' );

							if ( isset( $_POST['coupons']['discount_type'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
								$checked = 'fixed' === sanitize_text_field( wp_unslash( $_POST['coupons']['discount_type'] ) ) ? esc_attr( 'checked' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
							} elseif ( isset( $data['type'] ) ) {
								$checked = 'fixed' === $data['type'] ? esc_attr( 'checked' ) : '';
							}

							?>
							<input class="evf-coupon-discount-type-fixed" type="radio" name="coupons[discount_type]" value="fixed" id="evf-coupon-discount-type-fixed" <?php echo $checked; // phpcs:ignore  ?>>
							<?php esc_html_e( 'Fixed Discount', 'everest-forms-pro' ); ?>
							<p class="evf-coupon-discount__desc">
								<?php esc_html_e( 'This involves a specific amount of money deducted from the original price of a product.', 'everest-forms-pro' ); ?>
							</p>
						</label>

						<label class="evf-coupon-discount__type">
							<?php

							$checked = '';

							if ( isset( $_POST['coupons']['discount_type'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
								$checked = 'percent' === sanitize_text_field( wp_unslash( $_POST['coupons']['discount_type'] ) ) ? esc_attr( 'checked' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
							} elseif ( isset( $data['type'] ) ) {
								$checked = 'percent' === $data['type'] ? esc_attr( 'checked' ) : '';
							}

							?>
							<input class="evf-coupon-discount-type-percentage" type="radio" name="coupons[discount_type]" value="percent" id="evf-coupon-discount-type-percent" <?php echo $checked; // phpcs:ignore ?>>
							<?php esc_html_e( 'Percent Based Discount', 'everest-forms-pro' ); ?>
							<p class="evf-coupon-discount__desc">
								<?php esc_html_e( 'This is a discount calculated as a percentage of the original price of the product.', 'everest-forms-pro' ); ?>
							</p>
						</label>
					</div>
					<div class="evf-field">
						<label for="evf-coupon-discount-amount" class="evf-field-label"><?php esc_html_e( 'Discount Amount/Percent', 'everest-forms-pro' ); ?></label>
						<div class="evf-field-container">
						<?php
							/* translators: %s,%s: Discount Amount. */
							$value = isset( $_POST['coupons']['discount_amount'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), sanitize_text_field( wp_unslash( $_POST['coupons']['discount_amount'] ) ) ): (  isset( $data['amount'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), $data['amount'] ): '' ); // phpcs:ignore
						?>
						<input class="evf-coupon-discount-amount" type="text" name="coupons[discount_amount]" id="evf-coupon-discount-amount" value="<?php echo $value; // phpcs:ignore ?>">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_amount' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_amount' ) ); ?></label>
						<?php endif; ?>
						</div>
					</div>
					<div class="evf-field">
						<label for="evf-coupon-start-date" class="evf-field-label"><?php esc_html_e( 'Start Date', 'everest-forms-pro' ); ?></label>
						<div class="evf-field-container">
						<?php /* translators: %s,%s: Start Date. */ ?>
						<input class="evf-coupon-start-date" type="date" name="coupons[start_date]" id="evf-coupon-start-date" value="<?php isset( $_POST['coupons']['start_date'] ) ? printf( esc_attr__( '%s', 'everest-forms-pro' ), wp_unslash( $_POST['coupons']['start_date'] ) )  : (  isset( $data['start_date'] ) ? printf( esc_attr__( '%s', 'everest-forms-pro' ), $data['start_date'] ) : '' ); // phpcs:ignore ?>">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_start_date' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_start_date' ) ); ?></label>
						<?php endif; ?>
						</div>
					</div>
					<div class="evf-field">
						<label for="evf-coupon-end-date" class="evf-field-label"><?php esc_html_e( 'End Date', 'everest-forms-pro' ); ?></label>
						<div class="evf-field-container">
						<?php /* translators: %s,%s: End Date. */ ?>
						<input class="evf-coupon-end-date" type="date" name="coupons[end_date]" id="evf-coupon-end-date" value="<?php isset( $_POST['coupons']['end_date'] ) ? printf( esc_attr__( '%s', 'everest-forms-pro' ), wp_unslash( $_POST['coupons']['end_date'] ) ) : ( isset( $data['end_date'] ) ? printf( esc_attr__( '%s', 'everest-forms-pro' ), $data['end_date'] ) : '' ); // phpcs:ignore ?>">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_end_date' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_end_date' ) ); ?></label>
						<?php endif; ?>
						</div>
					</div>
					<div class="evf-field">
						<label for="evf-coupon-coupons-limit" class="evf-field-label"><?php esc_html_e( 'Coupon Limit', 'everest-forms-pro' ); ?></label>
						<div class="evf-field-container">
						<?php
							$value = isset( $_POST['coupons']['coupon_limit'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), sanitize_text_field( wp_unslash( $_POST['coupons']['coupon_limit'] ) ) ): ( isset( $data['coupon_limit'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), $data['coupon_limit'] ): '' ); // phpcs:ignore
						?>
						<input class="evf-coupon-coupons-limit" type="number" name="coupons[coupon_limit]" id="evf-coupon-coupons-limit" value="<?php echo $value; // phpcs:ignore ?>" step="1" min="0">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_limit' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_limit' ) ); ?></label>
						<?php endif; ?>
						</div>
					</div>
					<div class="evf-field">
						<label for="evf-coupon-minimum-purchase" class="evf-field-label"><?php esc_html_e( 'Minimum Purchase Amount', 'everest-forms-pro' ); ?></label>
						<div class="evf-field-container">
						<?php
							$value = isset( $_POST['coupons']['minimum_purchase'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), sanitize_text_field( wp_unslash( $_POST['coupons']['minimum_purchase'] ) ) ): ( isset( $data['minimum_purchase'] ) ? sprintf( esc_attr__( '%s', 'everest-forms-pro' ), $data['minimum_purchase'] ): '' ); // phpcs:ignore
						?>
						<input class="evf-coupon-minimum-purchase" type="number" name="coupons[minimum_purchase]" id="evf-coupon-minimum-purchase" value="<?php echo $value; // phpcs:ignore ?>" step="1" min="0">
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_limit' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_limit' ) ); ?></label>
						<?php endif; ?>
						</div>
					</div>
					<div class="evf-field">
						<label class="evf-field-label"><?php esc_html_e( 'Stackable', 'everest-forms-pro' ); ?></label>
					<div class="evf-field-container">
						<div style="display: flex;">
						<label>
							<?php
								$checked = esc_attr( 'checked' );

							if ( isset( $_POST['coupons']['stackable'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
								$checked = '1' === sanitize_text_field( wp_unslash( $_POST['coupons']['stackable'] ) ) ? esc_attr( 'checked' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
							} elseif ( isset( $data['stackable'] ) ) {
								$checked = '1' === $data['stackable'] ? esc_attr( 'checked' ) : '';
							}

							?>
							<input class="evf-coupon-stackable" type="radio" name="coupons[stackable]" value="1" id="evf-coupon-stackable" <?php echo $checked; // phpcs:ignore ?>>
							<?php esc_html_e( 'Yes', 'everest-forms-pro' ); ?>
						</label>
						<label style="margin-left: 10px;">
						<?php

						$checked = '';

						if ( isset( $_POST['coupons']['stackable'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
							$checked = '0' === sanitize_text_field( wp_unslash( $_POST['coupons']['stackable'] ) ) ? esc_attr( 'checked' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
						} elseif ( isset( $data['stackable'] ) ) {
							$checked = '0' === $data['stackable'] ? esc_attr( 'checked' ) : '';
						}else {
							$checked = esc_attr( 'checked' );
						}

						?>
							<input class="evf-coupon-stackable" type="radio" name="coupons[stackable]" value="0" id="evf-coupon-stackable" <?php echo $checked; // phpcs:ignore  ?>>
						<?php esc_html_e( 'No', 'everest-forms-pro' ); ?>
						</label>
						</div>
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_stackable' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_stackable' ) ); ?></label>
						<?php endif; ?>
					</div>
					</div>
					<div class="evf-field">
						<label class="evf-field-label"><?php esc_html_e( 'Status', 'everest-forms-pro' ); ?></label>
					<div class="evf-field-container">
						<div style="display: flex;">
						<label>
							<?php
								$checked = esc_attr( 'checked' );

							if ( isset( $_POST['coupons']['status'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
								$checked = 'active' === sanitize_text_field( wp_unslash( $_POST['coupons']['status'] ) ) ? esc_attr( 'checked' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
							} elseif ( isset( $data['status'] ) ) {
								$checked = '1' === $data['status'] ? esc_attr( 'checked' ) : '';
							}

							?>
							<input class="evf-coupon-status-active" type="radio" name="coupons[status]" value="active" id="evf-coupon-status-active" <?php echo $checked; // phpcs:ignore ?>>
							<?php esc_html_e( 'Active', 'everest-forms-pro' ); ?>
						</label>
						<label>
						<?php

						$checked = '';

						if ( isset( $_POST['coupons']['status'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
							$checked = 'inactive' === sanitize_text_field( wp_unslash( $_POST['coupons']['status'] ) ) ? esc_attr( 'checked' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
						} elseif ( isset( $data['status'] ) ) {
							$checked = '0' === $data['status'] ? esc_attr( 'checked' ) : '';
						}

						?>
							<input class="evf-coupon-status-inactive" type="radio" name="coupons[status]" value="inactive" id="evf-coupon-status-inactive" <?php echo $checked; // phpcs:ignore  ?>>
						<?php esc_html_e( 'Inactive', 'everest-forms-pro' ); ?>
						</label>
						</div>
						<?php if ( ! empty( $errors->get_error_message( 'evf_coupon_status' ) ) ) : ?>
						<label class="evf-error"><?php echo esc_html( $errors->get_error_message( 'evf_coupon_status' ) ); ?></label>
						<?php endif; ?>
					</div>
					</div>
					<div class="evf-field">
						<label class="evf-field-label"><?php esc_html_e( 'Applicable Forms', 'everest-forms-pro' ); ?></label>
						<?php
						$forms = evf_get_all_forms( true );
						if ( ! empty( $forms ) ) {
							echo '<select class="evf-enhanced-select everest-forms-pro-applicable-forms" style="min-width: 350px;" name="coupons[applicable_form_ids][]" data-placeholder="' . esc_attr__( 'Select Form(s)', 'everest-forms-pro' ) . '" multiple>';
							$selected = '';

							foreach ( $forms as $id => $form ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
								if ( isset( $_POST['coupons']['applicable_form_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
									$selected = in_array( absint( $id ), array_map( 'absint', $_POST['coupons']['applicable_form_ids'] ), true ) ? esc_attr( 'selected' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
								} elseif ( isset( $data['applicable_forms'] ) ) {
									$selected = in_array( absint( $id ), $data['applicable_forms'], true ) ? esc_attr( 'selected' ) : '';
								}

								echo '<option value="' . esc_attr( $id ) . '" ' . $selected . '>' . esc_html( $form ) . '</option>'; // phpcs:ignore
							}
							echo '</select>';
						} else {
							echo '<p>' . esc_html__( 'You need to create a form before you can apply coupon.', 'everest-forms-pro' ) . '</p>';
						}
						?>
					</div>
					<?php wp_nonce_field( 'evf_coupons_nonce', 'evf-coupons-nonce' ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
