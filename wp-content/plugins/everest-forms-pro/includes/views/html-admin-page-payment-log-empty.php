<?php
/**
 * Admin View: Payment log empty state (Pro — no payments received yet).
 *
 * @package EverestForms_Pro\Admin\Views
 */

defined( 'ABSPATH' ) || exit;

$builder_url = admin_url( 'admin.php?page=evf-builder' );
$icon_url    = plugins_url( 'assets/images/payment-gateway-icon.png', EVF_PLUGIN_FILE );
?>
<div class="evfp-payment-empty-wrap">
	<div class="evfp-payment-empty-card">

		<div class="evfp-payment-empty-icon-wrap">
			<img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="80" height="80" aria-hidden="true">
		</div>

		<h2><?php esc_html_e( 'No Payments Yet', 'everest-forms-pro' ); ?></h2>

		<p class="evfp-payment-empty-desc">
			<?php esc_html_e( 'Check your gateway setup or create a form with a payment field.', 'everest-forms-pro' ); ?>
		</p>

		<a href="<?php echo esc_url( $builder_url ); ?>" class="evfp-payment-empty-btn">
			<?php esc_html_e( 'Get Started', 'everest-forms-pro' ); ?>
		</a>

	</div>
</div>
