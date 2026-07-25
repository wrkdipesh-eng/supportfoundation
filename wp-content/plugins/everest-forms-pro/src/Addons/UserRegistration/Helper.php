<?php

namespace EverestForms\Pro\Addons\UserRegistration;

/**
 * Helper class.
 *
 * @since 1.7.9
 */
class Helper {

	/**
	 * Set a session variable.
	 *
	 * @since 1.7.9
	 *
	 * @param string $key   The key for the session data.
	 * @param mixed  $value The value to store in the session.
	 */
	public static function everest_forms_connect_set_session( $key, $value ) {

		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		$_SESSION['everest_forms_social_connect'][ $key ] = $value;
	}

	/**
	 * Redirect to a URL.
	 *
	 * @since 1.7.9
	 *
	 * @param string $redirect The URL to redirect to.
	 */
	public static function evfur_custom_redirect( $redirect ) {
		if ( headers_sent() ) { // Use JavaScript to redirect if content has been previously sent (not recommended, but safe)
			echo '<script language="JavaScript" type="text/javascript">window.location=\'';
			echo $redirect;
			echo '\';</script>';
		} else { // Default Header Redirect
			header( 'Location: ' . $redirect );
		}
		exit;
	}

	/**
	 * Unset a session variable.
	 *
	 * @since 1.7.9
	 *
	 * @param string $key The key of the session data to unset.
	 */
	public static function everest_forms_social_connect_unset_session( $key ) {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		if ( isset( $_SESSION['everest_forms_social_connect'] ) ) {
			if ( isset( $_SESSION['everest_forms_social_connect'][ $key ] ) ) {
				unset( $_SESSION['everest_forms_social_connect'][ $key ] );
			}
		}
	}

	/**
	 * Retrieve a session variable.
	 *
	 * @since 1.7.9
	 *
	 * @param string $key The key of the session data to retrieve.
	 * @return mixed The session value, or false if not found.
	 */
	public static function everest_forms_social_connect_get_session( $key ) {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		if ( isset( $_SESSION['everest_forms_social_connect'] ) ) {
			if ( isset( $_SESSION['everest_forms_social_connect'][ $key ] ) ) {
				return $_SESSION['everest_forms_social_connect'][ $key ];
			}
		}

		return false;
	}

	/**
	 * Retrieve an array of supported social networks and their settings.
	 *
	 * @since 1.7.9
	 *
	 * @return array The social networks configuration array.
	 */
	public static function everest_forms_social_networks() {
		$networks = array(
			'facebook' => array(
				'enable_id'  => 'everest_forms_social_setting_enable_facebook_connect',
				'key_id'     => 'everest_forms_social_setting_facebook_app_id',
				'secret_id'  => 'everest_forms_social_setting_facebook_app_secret',
				'login_text' => 'everest_forms_social_login_with_facebook_text',
			),
			'google'   => array(
				'enable_id'  => 'everest_forms_social_setting_enable_google_connect',
				'key_id'     => 'everest_forms_social_setting_google_client_id',
				'secret_id'  => 'everest_forms_social_setting_google_client_secret',
				'login_text' => 'everest_forms_social_login_with_google_text',
			),
			'linkedin' => array(
				'enable_id'  => 'everest_forms_social_setting_enable_linkedin_connect',
				'key_id'     => 'everest_forms_social_setting_linkedin_client_id',
				'secret_id'  => 'everest_forms_social_setting_linkedin_client_secret',
				'login_text' => 'everest_forms_social_login_with_linkedin_text',
			),
		);

		return apply_filters( 'everest-forms-registered-social-networks', $networks );
	}

	/**
	 * Flush all session data.
	 *
	 * @since 1.7.9
	 */
	public static function evfur_flush_all() {

		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}
		if ( isset( $_SESSION['everest_forms_social_connect'] ) ) {
			unset( $_SESSION['everest_forms_social_connect'] );
		}

		global $evfur_response_global;
		unset( $evfur_response_global );
	}

	/**
	 * Get Form ID associated with a User ID.
	 *
	 * @since 1.7.9
	 *
	 * @param int $user_id User ID.
	 * @return int $form_id Form ID.
	 */
	public static function evfur_get_form_id_by_userid( $user_id ) {
		$form_id_array = get_user_meta( $user_id, 'evf-form-id' );

		$form_id = 0;

		if ( isset( $form_id_array[0] ) ) {
			$form_id = $form_id_array[0];
		}
		return $form_id;
	}

	/**
	 * Start the session if not already started.
	 *
	 * @since 1.7.9
	 */
	public static function everest_forms_session_start() {

		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}
	}

	/**
	 * Generate a unique username.
	 *
	 * @since 1.7.9
	 *
	 * @param string $user_name The base username.
	 * @param string $email Optional email to check if the user already exists.
	 * @return string A unique username.
	 */
	public static function evfur_get_username( $user_name, $email = '' ) {
		if ( ! empty( $email ) ) {
			$user_id = email_exists( $email );
			if ( false !== $user_id ) {
				$user = get_userdata( $user_id );
				return $user->user_login;
			}
		}

		$username = $user_name;
		$i        = 1;
		while ( username_exists( $username ) ) {
			$username = $user_name . '_' . $i;
			++$i;
		}
		return $username;
	}

	/**
	 * Retrieve the list of default admin roles.
	 *
	 * @since 1.7.9
	 *
	 * @return array $all_roles Array of all available user roles.
	 */
	public static function evfur_get_default_admin_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		$roles     = isset( $wp_roles->roles ) ? $wp_roles->roles : array();
		$all_roles = array();

		foreach ( $roles as $role_key => $role ) {
			$all_roles[ $role_key ] = $role['name'];
		}

		/**
		 * Filters the default user roles available.
		 *
		 * @since 1.7.9
		 *
		 * @param array $all_roles An array of all available user roles.
		 */
		return apply_filters( 'everest_forms_user_default_roles', $all_roles );
	}
}
