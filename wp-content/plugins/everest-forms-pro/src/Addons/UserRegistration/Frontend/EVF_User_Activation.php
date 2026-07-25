<?php
/**
 * Everest Forms User Activation Class.
 *
 * @package EverestFormsUserRegistration\Admin
 * @version 1.0.0
 * @since   1.0.0
 */

 namespace EverestForms\Pro\Addons\UserRegistration\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * EVF User Activation class.
 */
class EVF_User_Activation {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'everest_forms_user_registration_new_user_email', array( $this, 'admin_emails' ), 10, 2 );
		add_action( 'everest_forms_user_registration_new_user_email', array( $this, 'user_emails' ), 10, 3 );
		add_action( 'everest_forms_user_registration_confirmation_email', array( $this, 'email_confirmation' ), 10, 4 );
		add_action( 'template_redirect', array( $this, 'check_verification_token' ) );
		add_action( 'wp_authenticate', array( $this, 'check_verification_token' ) );

		// Admin Approval.
		add_action( 'admin_notices', array( $this, 'display_admin_notices' ), 99 );
		add_action( 'admin_notices', array( $this, 'pending_users_notices' ) );

		add_filter( 'user_row_actions', array( $this, 'create_quick_links' ), 10, 2 );
		add_filter( 'manage_users_columns', array( $this, 'add_column_head' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'add_column_cell' ), 10, 3 );
		add_action( 'load-users.php', array( $this, 'trigger_query_actions' ) );
	}

	/**
	 * Admin email for new users.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id New user ID.
	 * @param array $form_data Form data.
	 */
	public function admin_emails( $user_id, $form_data ) {
		$user_registration_email_admin = isset( $form_data['settings']['user_registration_email_admin'] ) ? $form_data['settings']['user_registration_email_admin'] : 0;
		if ( ! $user_id || '1' !== $user_registration_email_admin ) {
			return;
		}

		$user        = get_userdata( $user_id );
		$username    = $user->user_login;
		$user_email  = $user->user_email;
		$admin_email = get_bloginfo( 'admin_email' );
		$message     = apply_filters(
			'everest_forms_user_registration_admin_email_message',
			sprintf(
				/* translators: %1$s: Username. %2$s: User Email. %3$s: Site Address. %4$s: Blog info. */
				__(
					'Hi Admin,

		A new user %1$s - %2$s has successfully registered to your site <a href="%3$s">%4$s</a>.

		Please review the user role and details at \'<b>Users</b>\' menu in your WP dashboard.

		Thank You!',
					'everest-forms-pro'
				),
				$username,
				$user_email,
				get_home_url(),
				get_bloginfo()
			)
		);
		$emails  = new \EVF_Emails();
		$message = '<tr><td>' . $message . '</td></tr>';
		$emails->__set( 'html', true );
		$emails->__set( 'form_data', $form_data );
		$emails->send( $admin_email, 'success', $message, '', 'connection_1' );
	}

	/**
	 * Email for new users.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id New user ID.
	 * @param array  $form_data Form data.
	 * @param string $password Password.
	 */
	public function user_emails( $user_id, $form_data, $password ) {
		$user_registration_email_user = isset( $form_data['settings']['user_registration_email_user'] ) ? $form_data['settings']['user_registration_email_user'] : 0;
		if ( ! $user_id || '1' !== $user_registration_email_user ) {
			return;
		}

		$login_options = isset( $form_data['settings']['user_registration_login_options'] ) ? $form_data['settings']['user_registration_login_options'] : 'auto_login';
		$user          = get_userdata( $user_id );
		$username      = $user->user_login;
		$user_email    = $user->user_email;
		$user_info     = '';
		if ( isset( $form_data['settings']['user_registration_email_user'] ) && '1' === $form_data['settings']['user_registration_email_user'] ) {
			$user_info = sprintf(
				/* translators: %1$s: Username. %2$s: User Password. */
				__(
					'Your Login details are below:
			 		Username : %1$s
			 		Password : %2$s',
					'everest-forms-pro'
				),
				$username,
				$password
			);
		}

		if ( 'admin_approval' === $login_options ) {
			$message = apply_filters(
				'everest_forms_user_registration_user_email_message',
				sprintf(
					/* translators: %1$s: Username. %2$s: User Email. %3$s: Site Address. %4$s: Blog info. */
					__(
						'Hi %1$s,

			You have successfully completed user registration on <a href="%2$s">%3$s</a>.

			Please wait until the site admin approves your registration. You will be notified after it is approved.

			%4$s

			Thank You!',
						'everest-forms-pro'
					),
					$username,
					get_home_url(),
					get_bloginfo(),
					$user_info
				)
			);
			$emails  = new \EVF_Emails();
			$message = '<tr><td>' . $message . '</td></tr>';
			$emails->__set( 'html', true );
			$emails->__set( 'form_data', $form_data );
			$emails->send( $user_email, 'success', $message, '', 'connection_1' );

		} elseif ( 'auto_login' === $login_options ) {
			$message = apply_filters(
				'everest_forms_user_registration_user_email_message',
				sprintf(
					/* translators: %1$s: Username. %2$s: User Email. %3$s: Site Address. %4$s: Blog info. */
					__(
						'Hi %1$s,

			You have successfully completed user registration on <a href="%2$s">%3$s</a>.

			%4$s

			Thank You!',
						'everest-forms-pro'
					),
					$username,
					get_home_url(),
					get_bloginfo(),
					$user_info
				)
			);
			$emails  = new \EVF_Emails();
			$message = '<tr><td>' . $message . '</td></tr>';
			$emails->__set( 'html', true );
			$emails->__set( 'form_data', $form_data );
			$emails->send( $user_email, 'success', $message, '', 'connection_1' );
		}
	}

	/**
	 * Email confirmation.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id User ID.
	 * @param array $form_data Form data.
	 * @param mixed $token Activation token.
	 * @param mixed $password Password.
	 */
	public function email_confirmation( $user_id, $form_data, $token, $password ) {
		if ( ! $user_id ) {
			return;
		}
		$login_options = isset( $form_data['settings']['user_registration_login_options'] ) ? $form_data['settings']['user_registration_login_options'] : 'auto_login';
		$user          = get_userdata( $user_id );
		$username      = $user->user_login;
		$user_email    = $user->user_email;
		$user_info     = '';
		if ( isset( $form_data['settings']['user_registration_email_user'] ) && '1' === $form_data['settings']['user_registration_email_user'] ) {
			$user_info = sprintf(
				/* translators: %1$s: Username. %2$s: User Password.*/
				__(
					'Your Login details are below:
			 		Username : %1$s
			 		Password : %2$s',
					'everest-forms-pro'
				),
				$username,
				$password
			);
		}

		if ( 'email_confirmation' === $login_options ) {
			$message = apply_filters(
				'everest_forms_user_registration_get_email_confirmation',
				sprintf(
					/* translators: %1$s: Username. %2$s: User Email. %3$s: Site Address. %4$s: Blog info. */
					__(
						'Hi %1$s,

 				You have registered on <a href="%2$s">%3$s</a>.

				 Please click on this verification link %2$s/wp-login.php?evf_token=%4$s to confirm registration.

				%5$s

 				Thank You!',
						'everest-forms-pro'
					),
					$username,
					get_home_url(),
					get_bloginfo(),
					$token,
					$user_info
				)
			);
			$emails  = new \EVF_Emails();
			$message = '<tr><td>' . $message . '</td></tr>';
			$emails->__set( 'html', true );
			$emails->__set( 'form_data', $form_data );
			$emails->send( $user_email, 'Confirmation email', $message, '', 'connection_1' );
		}
	}

	/**
	 * Email for new users after Email Verification.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id New user ID.
	 * @param array $form_data Form data.
	 */
	public function user_confirmation_success_emails( $user_id, $form_data ) {
		$user_registration_email_user = isset( $form_data['settings']['user_registration_email_user'] ) ? $form_data['settings']['user_registration_email_user'] : 0;
		if ( ! $user_id || '1' !== $user_registration_email_user ) {
			return;
		}

		$user       = get_userdata( $user_id );
		$username   = $user->user_login;
		$user_email = $user->user_email;

		$message = apply_filters(
			'everest_forms_user_registration_user_verify_email_message',
			sprintf(
				/* translators: %1$s: Username. %2$s: Site Address. %3$s: Blog info. */
				__(
					'Hi %1$s,

		You have successfully completed user registration on <a href="%2$s">%3$s</a>.

		Thank You!',
					'everest-forms-pro'
				),
				$username,
				get_home_url(),
				get_bloginfo()
			)
		);
		$emails  = new \EVF_Emails();
		$message = '<tr><td>' . $message . '</td></tr>';
		$emails->__set( 'html', true );
		$emails->__set( 'form_data', $form_data );
		$emails->send( $user_email, 'success', $message, '', 'connection_1' );
	}

	/**
	 * Check verification token.
	 *
	 * @since 1.0.0
	 */
	public function check_verification_token() {
		if ( ! isset( $_GET['evf_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$user = get_users(
			array(
				'meta_key'   => 'evf_confirm_email_token',
				'meta_value' => evf_clean( $_GET['evf_token'] ), // @codingStandardsIgnoreLine
			)
		);

		if ( ! empty( $user ) ) {
			$user = $user[0];

			update_user_meta( $user->ID, 'evf-user-status', 1 );
			delete_user_meta( $user->ID, 'evf_confirm_email_token' );
			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user->ID );

			$form_id   = get_user_meta( $user->ID, 'evf-form-id', true );
			$form_data = EVF()->form->get(
				$form_id,
				array(
					'content_only' => true,
				)
			);
			$this->user_confirmation_success_emails( $user->ID, $form_data );
			$redirect_url = ! empty( $form_data['settings']['registration_activation_confirmation'] ) ? $form_data['settings']['registration_activation_confirmation'] : '';
			wp_safe_redirect( apply_filters( 'everest_forms_user_registration_email_confirmation_redirect_url', ! empty( $redirect_url ) ? get_permalink( $redirect_url ) : wp_login_url( get_permalink() ), $form_data, $user->ID ) );
			exit;
		} else {
			evf_add_notice( __( 'Token Mismatch!', 'everest-forms-pro' ), 'error' );
			add_filter( 'login_message', array( $this, 'custom_registration_error_message' ) );
		}
	}

	/**
	 * Token mismatch message.
	 *
	 * @since 1.0.0
	 */
	public function custom_registration_error_message() {
		evf_print_notices();
	}

	/**
	 * Create two quick links Approve and Deny for each user in the users list.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $actions Actions.
	 * @param object $user User details.
	 *
	 * @return array
	 */
	public function create_quick_links( $actions, $user ) {

		$current_user_id = get_current_user_id();

		if ( $current_user_id === $user->ID || ! user_can( $current_user_id, 'manage_options' ) || user_can( $user->ID, 'manage_options' ) || ! user_can( $current_user_id, 'edit_users' ) ) {
			return $actions;
		}

		$approve_link = add_query_arg(
			array(
				'action' => 'approve',
				'user'   => $user->ID,
			)
		);
		$approve_link = remove_query_arg( array( 'new_role' ), $approve_link );
		$approve_link = wp_nonce_url( $approve_link, 'evf_user_change_status' );

		$deny_link = add_query_arg(
			array(
				'action' => 'deny',
				'user'   => $user->ID,
			)
		);
		$deny_link = remove_query_arg( array( 'new_role' ), $deny_link );
		$deny_link = wp_nonce_url( $deny_link, 'evf_user_change_status' );

		$approve_action = '<a style="color:#086512" href="' . esc_url( $approve_link ) . '">' . _x( 'Approve', 'The action on users list page', 'everest-forms-pro' ) . '</a>';
		$deny_action    = '<a style="color:#e20707" href="' . esc_url( $deny_link ) . '">' . _x( 'Deny', 'The action on users list page', 'everest-forms-pro' ) . '</a>';

		$status = get_user_meta( $user->ID, 'evf-user-status', true );

		if ( '0' === $status || '-1' === $status ) {
			$actions['evf_user_approve_action'] = $approve_action;
		}

		if ( '0' === $status || '1' === $status ) {
			$actions['evf_user_deny_action'] = $deny_action;
		}

		return $actions;
	}

	/**
	 * Add the column header for the status column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Field columns.
	 * @return array
	 */
	public function add_column_head( $columns ) {
		$the_columns['evf_user_user_status'] = __( 'Status', 'everest-forms-pro' );
		$newcol                              = array_slice( $columns, 0, -1 );
		$newcol                              = array_merge( $newcol, $the_columns );
		$columns                             = array_merge( $newcol, array_slice( $columns, 1 ) );

		return $columns;
	}

	/**
	 * Set the status value for each user in the users list.
	 *
	 * @since 1.0.0
	 *
	 * @param string $val value.
	 * @param string $column_name Name of column.
	 * @param int    $user_id User ID.
	 * @return string
	 */
	public function add_column_cell( $val, $column_name, $user_id ) {

		if ( 'evf_user_user_status' === $column_name ) {
			$status      = get_user_meta( $user_id, 'evf-user-status', true );
			$user_status = '';
			if ( '0' === $status ) {
				$user_status = esc_html__( 'Pending', 'everest-forms-pro' );
			} elseif ( '1' === $status ) {
				$user_status = esc_html__( 'Approved', 'everest-forms-pro' );
			} elseif ( '-1' === $status ) {
				$user_status = esc_html__( 'Denied', 'everest-forms-pro' );
			}

			return $user_status;
		}

		return $val;
	}

	/**
	 * Trigger the action query and check if some users have been approved or denied.
	 *
	 * @since 1.0.0
	 */
	public function trigger_query_actions() {

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : false;
		$mode   = isset( $_POST['mode'] ) ? wp_unslash( sanitize_key( $_POST['mode'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// If this is a multisite, bulk request, stop now!
		if ( 'list' === $mode ) {
			return;
		}

		if ( ! empty( $action ) && in_array( $action, array( 'approve', 'deny' ), true ) && ! isset( $_GET['new_role'] ) ) {

			check_admin_referer( 'evf_user_change_status' );

			$redirect = admin_url( 'users.php' );
			$status   = $action;
			$user     = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : -1;

			if ( 'approve' === $status && -1 !== $user ) {
				update_user_meta( $user, 'evf-user-status', '1' );
				$redirect = add_query_arg( array( 'approved' => 1 ), $redirect );
			} else {
				update_user_meta( $user, 'evf-user-status', '-1' );
				$redirect = add_query_arg( array( 'denied' => 1 ), $redirect );
			}

			$this->send_user_status_email( $user, $status );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Send a email to user if the user has been approved or denied.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user User ID.
	 * @param string $status User status.
	 */
	public function send_user_status_email( $user, $status ) {

		$message    = null;
		$subject    = null;
		$userdata   = get_userdata( $user );
		$username   = $userdata->user_login;
		$user_email = $userdata->user_email;

		if ( 'approve' === $status ) {
			$subject = esc_html__( 'User has been Approved', 'everest-forms-pro' );
			$message = apply_filters(
				'everest_forms_user_registration_user_approved_email_message',
				sprintf(
					/* translators: %1$s: Username. %2$s: Site Address. %3$s: Blog info. */
					__(
						'Hi %1$s,

			You have successfully completed user registration on <a href="%2$s">%3$s</a>.

			Thank You!',
						'everest-forms-pro'
					),
					$username,
					get_home_url(),
					get_bloginfo()
				)
			);
		} elseif ( 'deny' === $status ) {
			$subject = esc_html__( 'User has been Denined', 'everest-forms-pro' );
			$message = apply_filters(
				'everest_forms_user_registration_user_denied_email_message',
				sprintf(
					/* translators: %1$s: Username. %2$s: Site Address. %3$s: Blog info. */
					__(
						'Hi %1$s,

			Sorry, Your registration has been denied at <a href="%2$s">%3$s</a>.

			Thank You!',
						'everest-forms-pro'
					),
					$username,
					get_home_url(),
					get_bloginfo()
				)
			);
		}

		if ( ! empty( $message ) ) {
			$emails  = new \EVF_Emails();
			$message = '<tr><td>' . $message . '</td></tr>';
			$emails->__set( 'html', true );
			$emails->__set( 'form_data', $form_data );
			$emails->send( $user_email, $subject, $message, '', 'connection_1' );
		}
	}

	/**
	 * Display a notice to admin if some users have been approved or denied
	 *
	 * @since 1.0.0
	 *
	 */
	public function display_admin_notices() {
		$screen = get_current_screen();

		if ( 'users' !== $screen->id ) {
			return;
		}

		$message        = null;
		$users_denied   = ( isset( $_GET['denied'] ) && is_numeric( $_GET['denied'] ) ) ? absint( $_GET['denied'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$users_approved = ( isset( $_GET['approved'] ) && is_numeric( $_GET['approved'] ) ) ? absint( $_GET['approved'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $users_approved ) {
			/* translators: %d: Multiple users (cardinality). */
			$message = sprintf( esc_html( _n( 'User approved.', '%d users approved.', $users_approved, 'everest-forms-pro' ), $users_approved ) ); // @codingStandardsIgnoreLine - Plural form okay
		} elseif ( $users_denied ) {
			/* translators: %d: Multiple users (cardinality). */
			$message = sprintf( esc_html( _n( 'User denied.', '%d users denied.', $users_denied, 'everest-forms-pro' ), $users_denied ) ); // @codingStandardsIgnoreLine - Plural form okay
		}

		if ( ! empty( $message ) ) {
			echo '<div id="user-approvation-result" class="notice notice-success is-dismissible"><p><strong>' . $message . '</strong></p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Display a notice to admin notifying the pending users.
	 *
	 * @since 1.0.0
	 */
	public function pending_users_notices() {

		if ( ! current_user_can( 'list_users' ) ) {
			return;
		}

		$user_query = new \WP_User_Query(
			array(
				'meta_key'   => 'evf-user-status',
				'meta_value' => 0, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		// Get the results from the query, returning the first user.
		$users = $user_query->get_results();

		if ( count( $users ) > 0 ) {
			echo '<div id="user-approvation-result" class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Everest Forms:', 'everest-forms-pro' ) . '</strong> ' . count( $users ) . ' <a href="' . esc_url_raw( admin_url( 'users.php' ) ) . '">' . ( ( count( $users ) === 1 ) ? esc_html__( 'User', 'everest-forms-pro' ) : esc_html__( 'Users', 'everest-forms-pro' ) ) . '</a> ' . esc_html__( 'pending approval.', 'everest-forms-pro' ) . '</p></div>';
		}
	}
}
