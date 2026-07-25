<?php
/**
 * User Registration process.
 *
 * @since   1.0.0
 * @package EverestForms_User_Registration
 */

 namespace EverestForms\Pro\Addons\UserRegistration\Process;

use EverestForms\Pro\Addons\UserRegistration\EVFUR_Social_Data;
use EverestForms\Pro\Addons\UserRegistration\Helper;
use EverestForms\Pro\Addons\UserRegistration\Networks\Facebook\EVF_Network_Facebook;
use EverestForms\Pro\Addons\UserRegistration\Networks\Google\EVF_Network_Google;
use EverestForms\Pro\Addons\UserRegistration\Networks\Linkedin\EVF_Network_Linkedin;

defined( 'ABSPATH' ) || exit;

/**
 * EVF User Registration process class.
 *
 * @since 1.7.9
 */
class EVF_User_Registration_Process {

	/**
	 * Errors.
	 *
	 * @var array
	 */
	public $errors = '';

	/**
	 * Success.
	 *
	 * @var string
	 */
	public $success = '';

	/**
	 * Hook in tabs.
	 */
	public $wp_error = '';

	/**
	 * Primary class constructor.
	 *
	 * @since 1.7.9
	 */
	public function __construct() {
		add_action( 'everest_forms_process', array( $this, 'validate_user_registration' ), 10, 3 );
		add_action( 'everest_forms_process_user_registration', array( $this, 'process_user_registration' ), 30, 4 );
		add_filter( 'wp_authenticate_user', array( $this, 'check_status_on_login' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'process_login' ) );
		add_filter( 'everest_forms_frontend_load', array( $this, 'display_form' ), 10, 2 );
		add_filter( 'everest_forms_process_after_filter', array( $this, 'password_value_remove' ), 10, 3 );
		add_action( 'init', array( $this, 'social_login_check' ), 99 );
	}

	/**
	 * Social login check.
	 *
	 * @since 1.7.9
	 */
	public function social_login_check() {
		ob_start();
		$state_only = false;

		if ( isset( $_GET['everest_forms_social_login'] ) || $state_only ) {
			if ( isset( $_REQUEST['state'] ) ) {
				parse_str( base64_decode( $_REQUEST['state'] ), $state_vars );

				if ( isset( $state_vars['redirect_to'] ) ) {
					$_GET['redirect_to'] = $_REQUEST['redirect_to'] = $state_vars['redirect_to'];
				}
			}

			$social_network      = $social_network ?? $_GET['everest_forms_social_login'];
			$all_social_networks = Helper::everest_forms_social_networks();

			if ( isset( $all_social_networks[ $social_network ] ) ) {
				$api_key    = get_option( $all_social_networks[ $social_network ]['key_id'] );
				$api_secret = get_option( $all_social_networks[ $social_network ]['secret_id'] );

				switch ( $social_network ) {
					case 'google':
						$google_network = new EVF_Network_Google();
						$google_network->request( $api_key, $api_secret );
						break;
					case 'facebook':
						$facebook_network = new EVF_Network_Facebook();
						$facebook_network->request( $api_key, $api_secret );
						break;
					case 'linkedin':
						$linkedin_network = new EVF_Network_Linkedin();
						$linkedin_network->request( $api_key, $api_secret );
						break;

				}
			}
		}

		if ( ! is_user_logged_in() ) {
			$this->check_if_already_connected();
		} else {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$bypass = get_user_meta( $user_id, 'everest_forms_social_connect_bypass_current_password', true );
				if ( $bypass ) {
					add_filter(
						'everest_forms_save_account_bypass_current_password',
						array(
							$this,
							'bypass_current_password'
						)
					);
					add_filter(
						'everest_forms_change_password_current_password_display',
						array(
							$this,
							'bypass_current_password_display'
						)
					);
				}
			}
		}
		ob_end_flush();
	}

	/**
	 * On form display actions.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $load_form Indicates whether a form should be loaded.
	 * @param array $form_data Form information.
	 *
	 * @return bool
	 */
	public function display_form( $load_form, $form_data ) {
		// If the form has user registration enabled and current Whether the current user is logged in and doesn't have a administrator capability do not proceed.
		if ( isset( $form_data['settings']['enable_user_registration'] ) && '1' === $form_data['settings']['enable_user_registration'] && is_user_logged_in() && ! current_user_can( 'administrator' ) ) {
			echo '<p class="everest-forms-notice everest-forms-notice--error">' . esc_html__( 'You are already logged in. Please logout to register New User.', 'everest-forms-pro' ) . ' <a href="' . esc_url( wp_logout_url( get_permalink() ) ) . '">' . esc_html__( 'Logout', 'everest-forms-pro' ) . '</a></p>';
			return false;
		}

		return $load_form;
	}

	/**
	 * Validate registration form.
	 *
	 * @since 1.7.9
	 *
	 * @param array $fields Submitted fields.
	 * @param array $entry  Submitted data.
	 * @param array $form_data Form data.
	 */
	public function validate_user_registration( $fields, $entry, $form_data ) {
		if ( ! isset( $form_data['settings']['enable_user_registration'] ) || '1' !== $form_data['settings']['enable_user_registration'] ) {
			return;
		}

		$user_login_field_id = ! empty( $form_data['settings']['user_registration_user_login'] ) ? $form_data['settings']['user_registration_user_login'] : '';
		$user_email_field_id = ! empty( $form_data['settings']['user_registration_user_email'] ) ? $form_data['settings']['user_registration_user_email'] : '';

		if ( ! empty( $user_login_field_id ) && ! empty( $entry['form_fields'][ $user_login_field_id ] ) ) {
			$user_login = $entry['form_fields'][ $user_login_field_id ];
			if ( username_exists( $user_login ) ) {
				evf()->task->errors[ $form_data['id'] ][ $user_login_field_id ] = __( 'This username is already registered. Please choose another one.', 'everest-forms-pro' );
			}
		}

		if ( ! empty( $user_email_field_id ) && ! empty( $entry['form_fields'][ $user_email_field_id ] ) ) {
			$user_email = $entry['form_fields'][ $user_email_field_id ];
			if ( is_array( $user_email ) ) {
				$user_email = $user_email['primary'];
			}
			if ( email_exists( $user_email ) ) {
				evf()->task->errors[ $form_data['id'] ][ $user_email_field_id ] = __( 'This email is already registered. Please choose another one.', 'everest-forms-pro' );
			}
		}
	}

	/**
	 * Validate and process registration form.
	 *
	 * @since 1.7.9
	 *
	 * @param array $fields Submitted fields.
	 * @param array $entry  Submitted data.
	 * @param array $form_data Form data.
	 */
	public function process_user_registration( $fields, $entry, $form_data, $entry_id ) {
		if ( ! isset( $form_data['settings']['enable_user_registration'] ) || false === $form_data['settings']['enable_user_registration'] ) {
			return;
		}

		// Return if form has errors.
		$errors = evf()->task->errors;
		if ( ! empty( $errors[ $form_data['id'] ] ) ) {
			return;
		}

		$register_fields = array();
		$db_key          = array( 'user_login', 'user_email', 'display_name', 'user_url', 'first_name', 'last_name', 'description' );

		foreach ( $db_key as $value ) {
			if ( ! empty( $form_data['settings'][ 'user_registration_' . $value ] ) ) {
				$field_id                               = $form_data['settings'][ 'user_registration_' . $value ];
				$register_fields['user_data'][ $value ] = $entry['form_fields'][ $field_id ];
			}
		}

		if ( is_array( $register_fields['user_data']['user_email'] ) ) {
			$register_fields['user_data']['user_email'] = $register_fields['user_data']['user_email']['primary'];
		}

		if ( ! empty( $form_data['settings']['user_registration_role'] ) ) {
			$register_fields['user_data']['role'] = $form_data['settings']['user_registration_role'];
		}

		if ( ! empty( $form_data['settings']['user_registration_user_pass'] ) ) {
			$field_id                                  = $form_data['settings']['user_registration_user_pass'];
			$register_fields['user_data']['user_pass'] = isset( $entry['form_fields'][ $field_id ]['primary'] ) ? $entry['form_fields'][ $field_id ]['primary'] : $entry['form_fields'][ $field_id ];
		} else {
			$register_fields['user_data']['user_pass'] = wp_generate_password( 10 );
		}

		if ( ! empty( $register_fields['user_data']['user_login'] ) ) {
			if ( username_exists( $register_fields['user_data']['user_login'] ) ) {
				$field_id = isset( $form_data['settings']['user_registration_user_login'] ) ? $form_data['settings']['user_registration_user_login'] : '';
				if ( ! empty( $field_id ) ) {
					evf()->task->errors[ $form_data['id'] ][ $field_id ] = __( 'This username is already registered. Please choose another one.', 'everest-forms-pro' );
				} else {
					evf()->task->errors[ $form_data['id'] ]['header'] = __( 'This username is already registered. Please choose another one.', 'everest-forms-pro' );
				}
				return;
			}
			$register_fields['user_data']['user_login'] = $this->check_username( $register_fields['user_data']['user_login'] );
			if ( ! $register_fields['user_data']['user_login'] ) {
				evf()->task->errors[ $form_data['id'] ]['header'] = __( 'Invalid Username', 'everest-forms-pro' );
				return;
			}
		} else {
			$part_of_email                              = explode( '@', $register_fields['user_data']['user_email'] );
			$register_fields['user_data']['user_login'] = $this->check_username( $part_of_email[0] );
		}

		$register_fields = apply_filters( 'everest_forms_user_registration_before_user_register_procss', $register_fields, $entry, $form_data );
		$user_id         = wp_insert_user( $register_fields['user_data'] );

		// Custom user meta.
		if ( ! empty( $form_data['settings']['registration_meta'] ) ) {
			foreach ( $form_data['settings']['registration_meta'] as $meta_key => $meta_field ) {
				if ( ! empty( $fields[ $meta_field ]['value'] ) ) {
					$field_value = $fields[ $meta_field ]['value'];

					if ( in_array( $fields[ $meta_field ]['type'], array( 'checkbox', 'radio' ) ) ) {
						$field_value = $field_value['label'];
					}

					update_user_meta( $user_id, $meta_key, $field_value );
				}
			}
		}

		// On success.
		if ( ! is_wp_error( $user_id ) ) {
			global $wpdb;

			$wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array( 'user_id' => absint( $user_id ) ),
				array( 'entry_id' => absint( $entry_id ) ),
				array( '%d' ),
				array( '%d' )
			);

			update_user_meta( $user_id, 'evf-form-id', $form_data['id'] );
			if ( empty( $form_data['settings']['user_registration_login_options'] ) ) {
				return;
			}

			do_action( 'everest_forms_user_registration_new_user_email', $user_id, $form_data, $register_fields['user_data']['user_pass'] );

			if ( 'admin_approval' === $form_data['settings']['user_registration_login_options'] ) {
				update_user_meta( $user_id, 'evf-user-status', 0 );

				$this->success = __( 'User registered! Wait until admin approves your registration.', 'everest-forms-pro' );
				add_action( 'everest_forms_after_success_message', array( $this, 'show_success_message' ), 10, 2 );
				return;
			} elseif ( 'email_confirmation' === $form_data['settings']['user_registration_login_options'] ) {
				update_user_meta( $user_id, 'evf-user-status', 0 );
				$token = $this->get_token();
				update_user_meta( $user_id, 'evf_confirm_email_token', $token );
				do_action( 'everest_forms_user_registration_confirmation_email', $user_id, $form_data, $token, $register_fields['user_data']['user_pass'] );

				$this->success = __( 'User registered! Verify your email by clicking on the link sent to your email.', 'everest-forms-pro' );
				add_action( 'everest_forms_after_success_message', array( $this, 'show_success_message' ), 10, 2 );
				return;
			} else {
				wp_clear_auth_cookie();
				wp_set_auth_cookie( $user_id );
			}

			if ( isset( $form_data['settings']['redirect_to'] ) && 'custom_page' === $form_data['settings']['redirect_to'] ) {
				if ( isset( $form_data['settings']['enable_redirect_query_string'] ) && '1' === $form_data['settings']['enable_redirect_query_string'] ) {
					parse_str( $form_data['settings']['query_string'], $output );
					$query_redirect_url = array();
					foreach ( $output as $key => $value ) {
						$query_redirect_url[ $key ] = apply_filters( 'everest_forms_process_smart_tags', $value, $this->form_data, $this->form_fields );
					}
					$redirect_url = apply_filters( 'everest_forms_user_registration_signup_redirect_url', add_query_arg( $query_redirect_url, esc_url( get_page_link( $form_data['settings']['custom_page'] ) ) ), $user_id );
				} else {
					$redirect_url = apply_filters( 'everest_forms_user_registration_signup_redirect_url', get_page_link( $form_data['settings']['custom_page'] ), $user_id );
				}

				?>
						<script>
						var redirect = '<?php echo esc_url_raw( $redirect_url ); ?>';
						window.setTimeout( function () {
							window.location.href = redirect;
						})
						</script>
					<?php
			} elseif ( isset( $form_data['settings']['redirect_to'] ) && 'external_url' === $form_data['settings']['redirect_to'] ) {
				?>
					<script>
						window.setTimeout( function () {
							window.location.href = '<?php echo esc_url( $form_data['settings']['external_url'] ); ?>';
						})
						</script>
					<?php
			}
		} else {
			evf()->task->errors[ $form_data['id'] ]['header'] = $user_id->get_error_message();
		}
	}

	/**
	 * Show success message after form submission.
	 *
	 * @since 1.7.9
	 *
	 * @param array $form_data Form data.
	 * @param array $entry Entry data.
	 */
	public function show_success_message( $form_data, $entry ) {
		if ( ! empty( $this->success ) ) {
			evf_clear_notices();
			evf_add_notice( $this->success, 'success' );
		}
	}

	/**
	 * Generate email token.
	 *
	 * @since 1.7.9
	 *
	 * @return string   Token.
	 */
	public function get_token() {

		$length         = 50;
		$token          = '';
		$code_alphabet  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$code_alphabet .= 'abcdefghijklmnopqrstuvwxyz';
		$code_alphabet .= '0123456789';
		$max            = strlen( $code_alphabet );

		for ( $i = 0; $i < $length; $i++ ) {
			$token .= $code_alphabet[ random_int( 0, $max - 1 ) ];
		}

		return $token;
	}

	/**
	 * Login Process.
	 *
	 * @since 1.7.9
	 */
	public function process_login() {
		if ( ! isset( $_POST['evf-user-login'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['evf-nonce'] ), 'evf-user-login' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			evf_add_notice( esc_html__( 'Nonce Error! Please reload.', 'everest-forms-pro' ), 'error' );
			return;
		}
		$user_login       = isset( $_POST['user_login'] ) ? evf_clean( $_POST['user_login'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$user_password    = isset( $_POST['user_password'] ) ? evf_clean( $_POST['user_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$remember         = ( isset( $_POST['rememberme'] ) && '1' === $_POST['rememberme'] ) ? true : false;
		$enable_recaptcha = isset( $_POST['evf-login-recaptcha'] ) ? $_POST['evf-login-recaptcha'] : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! ( $user_login && validate_username( $user_login ) ) ) {
			evf_add_notice( esc_html__( 'Invalid Username', 'everest-forms-pro' ), 'error' );
			return;
		}

		if ( ! $user_password ) {
			evf_add_notice( esc_html__( 'Password is required', 'everest-forms-pro' ), 'error' );
			return;
		}

		// reCAPTCHA check.
		if ( ! apply_filters( 'everest_forms_recaptcha_disabled', false ) ) {
			$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
			$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );

			if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
				$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
				$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
			} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
				$site_key   = get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
				$secret_key = get_option( 'everest_forms_recaptcha_v2_invisible_secret_key' );
			} elseif ( 'v3' === $recaptcha_type ) {
				$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
				$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
			} elseif ( 'hcaptcha' === $recaptcha_type ) {
				$site_key   = get_option( 'everest_forms_recaptcha_hcaptcha_site_key' );
				$secret_key = get_option( 'everest_forms_recaptcha_hcaptcha_secret_key' );
			}

			if ( ! empty( $site_key ) && ! empty( $secret_key ) && $enable_recaptcha ) {
				if ( 'hcaptcha' === $recaptcha_type ) {
					$error = esc_html__( 'hCaptcha verification failed, please try again later.', 'everest-forms' );
				} else {
					$error = esc_html__( 'Google reCAPTCHA verification failed, please try again later.', 'everest-forms' );
				}

				$token = ! empty( $_POST['g-recaptcha-response'] ) ? evf_clean( wp_unslash( $_POST['g-recaptcha-response'] ) ) : false;

				if ( 'v3' === $recaptcha_type ) {
					$token = ! empty( $_POST['everest_forms']['recaptcha'] ) ? evf_clean( wp_unslash( $_POST['everest_forms']['recaptcha'] ) ) : false;
				}

				if ( 'hcaptcha' === $recaptcha_type ) {
					$token        = ! empty( $_POST['h-captcha-response'] ) ? evf_clean( wp_unslash( $_POST['h-captcha-response'] ) ) : false;
					$raw_response = wp_safe_remote_get( 'https://hcaptcha.com/siteverify?secret=' . $secret_key . '&response=' . $token );
				} else {
					$raw_response = wp_safe_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $token );
				}

				if ( ! is_wp_error( $raw_response ) ) {
					$response = json_decode( wp_remote_retrieve_body( $raw_response ) );

					// Check reCAPTCHA response.
					if ( empty( $response->success ) || ( 'v3' === $recaptcha_type && $response->score <= get_option( 'everest_forms_recaptcha_v3_threshold_score', apply_filters( 'everest_forms_recaptcha_v3_threshold', '0.5' ) ) ) ) {
						if ( 'v3' === $recaptcha_type ) {

							if ( isset( $response->score ) ) {
								$error .= ' (' . esc_html( $response->score ) . ')';
							}
						}
						evf_add_notice( $error, 'error' );
						return;
					}
				}
			}
		}

		$user_data = array(
			'user_login'    => $user_login,
			'user_password' => $user_password,
			'remember'      => $remember,
		);

		$user = wp_signon( $user_data, is_ssl() );

		if ( is_wp_error( $user ) ) {
			evf_add_notice( $user->get_error_message(), 'error' );
			return;
		}

		$redirect_url = apply_filters( 'everest_forms_user_registration_login_redirect_url', esc_url( $_POST['evf-redirect-url'] ), $user->ID ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		?>
		<script>
		var redirect = '<?php echo esc_url_raw( $redirect_url ); ?>';
		window.setTimeout( function () {
			window.location.href = redirect;
		})
		</script>
		<?php
	}

	/**
	 * Check the status of an user on login
	 *
	 * @since 1.7.9
	 *
	 * @param WP_User $user User details.
	 *
	 * @return \WP_Error
	 * @throws \Exception Login status failures.
	 */
	public function check_status_on_login( \WP_User $user ) {

		if ( is_wp_error ( $user ) ) {
			return $user;
		}

		$status      = get_user_meta( $user->ID, 'evf-user-status', true );
		$email_token = get_user_meta( $user->ID, 'evf_confirm_email_token', true );
		$interaction = null;

		do_action( 'everest_forms_user_registration_user_before_check_status_on_login', $status, $user );

		switch ( $status ) {
			case '0':
				if ( $email_token ) {
					$message = '<strong>' . __( 'ERROR:', 'everest-forms-pro' ) . '</strong> ' . __( 'Please check your email to verify your account.', 'everest-forms-pro' );
				} else {
					$message = '<strong>' . __( 'ERROR:', 'everest-forms-pro' ) . '</strong> ' . __( 'Your account is still pending approval.', 'everest-forms-pro' );
				}

				$interaction = new \WP_Error( 'pending_approval', $message );
				break;
			case '-1':
				$message = '<strong>' . __( 'ERROR:', 'everest-forms-pro' ) . '</strong> ' . __( 'Your account has been denied.', 'everest-forms-pro' );

				$interaction = new \WP_Error( 'denied_access', $message );
				break;
			default:
				$interaction = $user;
				break;
		}
		return $interaction;
	}

	/**
	 * Check if username already exists in case of optional username
	 * And while stripping through email address and incremet last number by 1.
	 *
	 * @since 1.7.9
	 *
	 * @param  string $username Username.
	 * @return string
	 */
	public function check_username( $username ) {
		if ( username_exists( $username ) ) {
			preg_match_all( '/\d+$/m', $username, $matches );

			if ( isset( $matches[0][0] ) ) {
				$last_char       = $matches[0][0];
				$strip_last_char = substr( $username, 0, -( strlen( (string) $last_char ) ) );
				$last_char++;
				$username = $strip_last_char . $last_char;
				$username = $this->check_username( $username );

				return $username;
			} else {
				$username = $username . '_1';
				$username = $this->check_username( $username );

				return $username;
			}
		}

		return $username;
	}

	/**
	 * Real password with dummy one.
	 *
	 * @since 1.7.9
	 *
	 * @param array $fields Form fields.
	 * @param array $entry Entry data.
	 * @param array $form_data Form data.
	 */
	public function password_value_remove( $fields, $entry, $form_data ) {

		if ( ! isset( $form_data['settings']['enable_user_registration'] ) || '1' !== $form_data['settings']['enable_user_registration'] ) {
			return $fields;
		}

		foreach ( $fields as $id => $field ) {
			if ( 'password' === $field['type'] ) {
				$fields[ $id ]['value'] = '**********';
			}
		}

		return $fields;
	}

	/**
	 * Checks if the email address is already connected.
	 *
	 * @since 1.7.9
	 *
	 * @return void
	 */
	public function check_if_already_connected() {
		global $evfur_response_global;

		if ( isset( $evfur_response_global['status'] ) && 'SUCCESS' === $evfur_response_global['status'] ) {

			if ( isset( $evfur_response_global['data'] ) && isset( $evfur_response_global['data']['email'] ) ) {

				$global_data = isset( $evfur_response_global['data'] ) ? $evfur_response_global['data'] : array();

				$network_data = array(
					'email'       => isset( $global_data['email'] ) ? $global_data['email'] : '',
					'username'    => isset( $global_data['username'] ) ? $global_data['username'] : '',
					'profile'     => isset( $global_data['profile'] ) ? $global_data['profile'] : '',
					'profile_pic' => isset( $global_data['profile_pic'] ) ? $global_data['profile_pic'] : '',
					'first_name'  => isset( $global_data['first_name'] ) ? $global_data['first_name'] : '',
					'last_name'   => isset( $global_data['last_name'] ) ? $global_data['last_name'] : '',
					'network'     => isset( $evfur_response_global['network'] ) ? $evfur_response_global['network'] : '',
					'has_email'   => ! empty( $global_data['email'] ) ? true : false,
				);

				$is_already_connected_through_same_network            = EVFUR_Social_Data::is_already_connected_network( $network_data['username'], $network_data['network'], $network_data['email'] );
				$is_already_connected_through_other_medium_or_network = EVFUR_Social_Data::is_already_connected( $network_data['username'], $network_data['email'] );
				if ( $is_already_connected_through_same_network ) {
					$status = EVFUR_Social_Data::check_user_and_login( $network_data );
					if ( is_wp_error( $status ) ) {
						$this->wp_error = $status;
					}
				} elseif ( $is_already_connected_through_other_medium_or_network['status'] ) {
					if ( 'yes' == get_option( 'everest_forms_social_setting_multi_social_login' ) ) {
						$status = EVFUR_Social_Data::login_user( $is_already_connected_through_other_medium_or_network['user_id'] );
						if ( is_wp_error( $status ) ) {
							return $status;
						}
						if ( wp_safe_redirect( admin_url() ) ) {
							exit;
						}
					} else {
						evf_clear_notices();
						$message = __( 'User already registered through other medium.', 'everest-forms-pro' );
						evf_add_notice( $message, 'error' );
						$this->wp_error = new \WP_Error( 'everest_forms_user_already_created', $message );
					}
				} else {
					try {
						if ( ( ( $network_data['network'] !== 'google' && $network_data['network'] !== 'linkedin' && $network_data['network'] !== 'instagram' ) && empty( $network_data['profile'] ) ) || empty( $network_data['username'] ) ) {
							throw  new \Exception( __( 'Network user profile not found for this email address.', 'everest-forms-pro' ) );
						}
						$password = wp_generate_password( 15, true, true );
						$user_id  = EVFUR_Social_Data::evfur_register_user( $network_data, $password );

						if ( ! is_numeric( $user_id ) ) {
							Helper::evfur_flush_all();
							throw  new \Exception( $user_id );
						}
						$status = EVFUR_Social_Data::login_user( $user_id );

						if ( is_wp_error( $status ) ) {
							throw  new \Exception( $status->get_error_message() );
						}

						if ( wp_safe_redirect( admin_url() ) ) {
							exit;
						}
					} catch ( \Exception $e ) {
						Helper::evfur_flush_all();
						$this->wp_error = new \WP_Error( 'everest_forms_social_connect_registration_error', $e->getMessage() );
						evf_add_notice( $e->getMessage(), 'error' );
					}
				}
			}
		}
	}
}
