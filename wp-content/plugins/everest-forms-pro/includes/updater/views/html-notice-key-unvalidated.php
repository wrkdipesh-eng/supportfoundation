<?php
/**
 * Admin View: Notice - License Unvalidated
 *
 * @package EverestForms_Pro
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated inline" style="margin: 16px 0; border-left-color: #F25656 !important;">
	<p class="evf-updater-dismiss" style="float:right;"><a href="<?php echo esc_url( add_query_arg( 'dismiss-' . sanitize_title( $this->plugin_slug ), '1' ) ); ?>" style="color: #2271b1 !important" ><?php esc_html_e( 'Hide notice', 'everest-forms-pro' ); ?></a></p>
	<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: 1: license key URL 2: plugin name */
			__( '<a href="%1$s" style="color: #2271b1 !important"><strong>Please click here to activate the license </strong></a> for  <strong>%2$s</strong> to access addons and get updates for the plugin.', 'everest-forms-pro' ),
			esc_url( admin_url( 'admin.php?page=evf-settings&tab=license' ) ),
			esc_html( $this->plugin_data['Name'] )
		)
	);
	?>
	</p>
</div>
