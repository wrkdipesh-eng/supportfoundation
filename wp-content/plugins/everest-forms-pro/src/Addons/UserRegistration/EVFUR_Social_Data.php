<?php
/**
 * EVFUR Frontend.
 *
 * @class    EVFUR_Social_Data
 * @version  1.0.0
 * @package  EVFUR/Admin
 * @category Admin
 * @author   WPEverest
 */

namespace  EverestForms\Pro\Addons\UserRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EVFUR_Social_Data
 *
 * @since 1.7.9
 */
class EVFUR_Social_Data {

	/**
     * Check if an email exists.
     *
     * @since 1.7.9
     *
     * @param string $email The email to check.
     * @return bool True if email exists, false otherwise.
     */
	public static function email_exists( $email ) {

		$exists = email_exists( $email );

		if ( $exists ) {

			return true;
		}

		return false;
	}

   /**
     * Authenticate a user using email and password.
     *
     * @since 1.7.9
     *
     * @param string $email    The email to authenticate.
     * @param string $password The password to authenticate.
     * @return string|WP_Error Authentication result.
     */
	public static function check_password( $email, $password ) {

		$check = wp_authenticate_email_password( null, $email, $password );

		if ( null === $check ) {

			return __( 'Could not found any user.', 'everest-forms-pro' );
		}
		if ( is_wp_error( $check ) ) {

			return \WP_Error::get_error_message( $check );
		}

		return 'success';
	}

	/**
     * Check if a user is already connected to a social network.
     *
     * @since 1.7.9
     *
     * @param string $username The social network username.
     * @param string $network_name The name of the network.
     * @param string $email Optional email to match with the username.
     * @return bool True if the user is already connected, false otherwise.
     */
	public static function is_already_connected_network( $username, $network_name, $email = '' ) {
		$meta_prefix = 'everest_forms_social_connect_';
		$user_data   =
			get_users(
				array(
					'meta_key'    => $meta_prefix . $network_name . '_username',
					'meta_value'  => $username,
					'number'      => 1,
					'count_total' => false,
				)
			);

		if ( ! empty( $email ) ) {

			if ( isset( $user_data[0] ) ) {
				return $user_data[0]->data->user_email === $email ? true : false;
			}

			return false;
		}

		return count( $user_data ) === 0 ? false : true;
	}

    /**
     * Check if a user is already connected by email.
     *
     * @since 1.7.9
     *
     * @param string $username The username.
     * @param string $email Optional email to check.
     * @return array Status of the connection with user ID if connected.
     */
	public static function is_already_connected( $username, $email = '' ) {
		$user_data = get_user_by( 'email', $email );
		$status = array( 'status' => false );
		if ( ! empty( $email ) ) {

			if ( $user_data && $user_data->data->user_email === $email ) {
				$status = array(
					'status'  => true,
					'user_id' => $user_data->ID
				);
			}

			return $status;
		}

		if ( $user_data ) {
			$status = array(
				'status'  => true,
				'user_id' => $user_data->ID
			);
		}

		return $status;
	}

	 /**
     * Update social network connection for a user.
     *
     * @since 1.7.9
     *
     * @param int   $user_id       The ID of the user.
     * @param array $network_data  Data of the social network connection.
     */
	public static function update_network_connection( $user_id, $network_data = array() ) {

		$network_name = $network_data['network'];
		$meta_prefix  = 'everest_forms_social_connect_';
		update_user_meta( $user_id, $meta_prefix . $network_name . '_username', $network_data['username'] );
		update_user_meta( $user_id, $meta_prefix . $network_name . '_profile', $network_data['profile'] );

		if ( '' !== $network_data['profile_pic'] ) {
			update_user_meta( $user_id, $meta_prefix . $network_name . '_profile_pic', $network_data['profile_pic'] );

			if ( ! is_numeric( $network_data['profile_pic'] ) ) {
				$everest_forms_profile_pic_attachment = attachment_url_to_postid( $network_data['profile_pic'] );
				if ( 0 != $everest_forms_profile_pic_attachment ) {
					update_user_meta( $user_id, 'everest_forms_profile_pic_url', absint( $everest_forms_profile_pic_attachment ) );
				}
			}
		}

		update_user_meta( $user_id, 'first_name', $network_data['first_name'] );
		update_user_meta( $user_id, 'last_name', $network_data['last_name'] );

		update_user_meta( $user_id, $meta_prefix . 'bypass_current_password', true );
	}

	/**
     * Check if user exists and log in.
     *
     * @since 1.7.9
     *
     * @param array $network_data Data from the social network.
     * @return WP_Error|string The status of the login attempt.
     */
	public static function check_user_and_login( $network_data ) {
		$meta_prefix = 'everest_forms_social_connect_';
		$user_data   =
			get_users(
				array(
					'meta_key'    => $meta_prefix . $network_data['network'] . '_username',
					'meta_value'  => $network_data['username'],
					'number'      => 1,
					'count_total' => false,
				)
			);
		$user = $user_data[0];

		$status = self::login_user( $user->ID );

		if ( is_wp_error( $status ) ) {
			return $status;
		}

		if ( wp_safe_redirect( admin_url() ) ) {
			exit;
		}
	}

	 /**
     * Log in a user.
     *
     * @since 1.7.9
     *
     * @param int $user_id The ID of the user.
     */
	public static function login_user( $user_id ) {
		Helper::evfur_flush_all();
		wp_clear_auth_cookie();
		wp_set_auth_cookie( $user_id );
		do_action( 'everest_forms_after_login_succeed', $user_id );
	}

	/**
     * Register a new user.
     *
     * @since 1.7.9
     *
     * @param array $userdata Data for the new user.
     * @return int|WP_Error User ID on success, WP_Error on failure.
     */
	public static function register_user( $userdata ) {

		$userdata = array(
			'user_login' => $userdata['user_login'],
			'user_pass'  => $userdata['user_pass'],
			'user_email' => $userdata['user_email'],
			'role'       => $userdata['role'],
		);

		$user_id = wp_insert_user( $userdata );

		return $user_id;
	}

	/**
     * Register a user via social network data.
     *
     * @since 1.7.9
     *
     * @param array  $network_data Social network data.
     * @param string $password     Password for the new user.
     * @return int|string User ID on success, error message on failure.
     */
	public static function evfur_register_user( $network_data, $password ) {
		$valid_form_data = array();
		$user_data       = array(
			'user_email' => $network_data['email'],
			'user_pass'  => $password,
			'user_login' => $network_data['username'],
			'role'       => get_option( 'everest_forms_social_setting_default_user_role', 'subscriber' ),
		);

		if ( ! get_option( 'everest_forms_social_setting_enable_social_registration', false ) ) {

			return __( 'Could not register user, please contact with site administrator', 'everest-forms-pro' );
		}

		$user_id = self::register_user( $user_data );

		$form_data = array(
			'user_login' => array(
				'value'      => $network_data['username'],
				'field_name' => 'user_login',
			),
			'user_email' => array(
				'value'      => $network_data['email'],
				'field_name' => 'user_email',
			),
		);

		self::update_network_connection(
			$user_id,
			$network_data
		);

		// do_action( 'everest_forms_after_register_user_action', $valid_form_data, $form_id, $user_id );

		return $user_id;
	}
}
