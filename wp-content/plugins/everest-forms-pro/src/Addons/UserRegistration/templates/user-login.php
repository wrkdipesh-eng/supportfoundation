<div class="everest-forms everest-form-user-registration-login-wrapper">
	<?php
	/**
	 * Everest Forms User Login template.
	 *
	 * @package EverestForms_User_Registration
	 * @since   1.0.0
	 */

	use EverestForms\Pro\Addons\UserRegistration\Helper;

	if ( function_exists( 'evf_print_notices' ) ) {
		evf_print_notices();
	}

	global $wp;

	$social_networks  = Helper::everest_forms_social_networks();
	$enabled_networks = array();

	foreach ( $social_networks as $network_key => $network_data ) {
		if ( 'yes' === get_option( 'everest_forms_social_setting_enable_social_registration' ) && 'yes' === get_option( $network_data['enable_id'] ) ) {
			$enabled_networks[ $network_key ] = $network_data;
		}
	}

	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';

	$url = ( ! empty( $_SERVER['HTTPS'] ) ) ? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	$formatted_url = substr( $url, 0, strpos( $url, '?' ) );

	$encoded_url = '';

	?>
	<form class="" id="everest-form-user-registation-login" method="POST">
		<div class="evf-field evf-field-username form-row">
			<label><?php echo esc_html__( 'Username or Email', 'everest-forms-user-registration' ); ?></label>
			<input type="text" name="user_login" class="input-text" value="<?php echo isset( $_POST['user_login'] ) ? esc_attr( wp_unslash( $_POST['user_login'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>" required />
		</div>
		<div class="evf-field evf-field-password form-row">
			<label><?php echo esc_html__( 'Password', 'everest-forms-user-registration' ); ?></label>
			<input type="password" name="user_password" class="input-text" required />
			<?php if ( 'yes' === get_option( 'everest_forms_lost_password' ) ) { ?>
			<label>
				<a href="<?php echo esc_url( get_site_url() ); ?>/wp-login.php?action=lostpassword"><?php echo esc_html__( 'Forgot Password?', 'everest-forms-user-registration' ); ?></a>
			</label>
			<?php } ?>
		</div>
		<div class="evf-field form-row">
			<label>
			<input type="checkbox" name="rememberme" value='1' <?php checked( '1', ( isset( $_POST['rememberme'] ) ? esc_attr( wp_unslash( $_POST['rememberme'] ) ) : '0' ) ); /* phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing */ ?> />
			<?php echo esc_html__( 'Remember me', 'everest-forms-user-registration' ); ?>
			</label>
		</div>
		<?php
		if ( ! empty( $recaptcha_node ) ) {
			echo '<div id="evf-recaptcha-node"> ' . $recaptcha_node . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<input type="hidden" name="evf-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<input type="hidden" name="evf-redirect-url" value="<?php echo esc_url( $redirect_url ); ?>" />
		<input type="hidden" name="evf-login-recaptcha" value="<?php echo esc_attr( $recaptcha_status ); ?>" />
		<button type="submit" class="button" name="evf-user-login" value="evf-submit"><?php echo esc_html__( 'Login', 'everest-forms-user-registration' ); ?></button>
		<?php
		if ( count( $enabled_networks ) > 0 ) {
			?>
			<div class="everest-forms-social-connect-networks">
				<ul class="evfur-network-lists evfur_theme_4">
						<?php
						foreach ( $enabled_networks as $network_key => $network_data ) {
							?>
							<li class="evfur-login-media evfur-login-media--<?php echo $network_key; ?>">
								<a href="<?php echo $formatted_url; ?>?everest_forms_social_login=<?php echo $network_key; ?>&evfur_action=login
											<?php
											if ( $encoded_url ) {
												echo '&state=' . base64_encode( "redirect_to=$encoded_url" );
											}
											?>
								" title='
								<?php
								_e( 'Login with', 'user-registration-social-connect' );
								echo ' ' . $network_key;
								?>
								'>
								<span class="evfur-icon-block icon-<?php echo $network_key; ?> evfur-login-with-<?php echo $network_key; ?>"></span>
								<span class="evfur-login-text"><?php echo esc_html( get_option( $network_data['login_text'] ) ); ?></span>
								</a>
							</li>
							<?php
						}
						?>
				</ul>
			</div>
			<?php
		}
		?>
	</form>
</div>
