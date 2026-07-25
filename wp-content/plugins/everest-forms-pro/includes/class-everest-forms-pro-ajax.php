<?php
/**
 * EverestForms Pro EVF_PRO_AJAX. AJAX Event Handlers.
 *
 * @class   EVF_PRO_AJAX
 * @package EverestFormsPro/Classes
 */

use EverestForms\Helpers\FormHelper;
use EverestForms\Pro\Helper\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * EVF_PRO_AJAX class.
 */
class EVF_PRO_AJAX {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_evf_ajax' ), 0 );
		self::add_ajax_events();
	}



	/**
	 * Set EVF Pro AJAX constant and headers.
	 */
	public static function define_ajax() {
		// @codingStandardsIgnoreStart
		if ( ! empty( $_GET['evf-pro-ajax'] ) ) {
			evf_maybe_define_constant( 'DOING_AJAX', true );
			evf_maybe_define_constant( 'EVFP_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
				}
			$GLOBALS['wpdb']->hide_errors();
			}
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Send headers for EVFP Ajax Requests.
	 *
	 * @since 1.0.0
	 */
	private static function evfp_ajax_headers() {
		if ( ! headers_sent() ) {
			send_origin_headers();
			send_nosniff_header();
			evf_nocache_headers();
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "evf_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Check for EVFP Ajax request and fire action.
	 */
	public static function do_evf_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['evf-pro-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$wp_query->set( 'evf-pro-ajax', sanitize_text_field( wp_unslash( $_GET['evf-pro-ajax'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		$action = $wp_query->get( 'evf-pro-ajax' );

		if ( $action ) {
			self::evfp_ajax_headers();
			$action = sanitize_text_field( $action );
			do_action( 'evf_ajax_' . $action );
			wp_die();
		}
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'add_webhooks' => false,
			'update_entry' => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_everest_forms_pro_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_everest_forms_pro_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// EVFP AJAX can be used for frontend ajax requests.
				add_action( 'evfp_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Add the webhook.
	 *
	 * @since 1.7.8
	 */
	public static function add_webhooks() {
		check_ajax_referer( 'evf_webhook_nonce', 'security' );
		$next_id = isset( $_POST['next_id'] ) ? sanitize_text_field( wp_unslash( $_POST['next_id'] ) ) : 'unknown';

		$pro_instance = EverestForms_Pro::get_instance();
		$form_data    = array();

		if ( ! empty( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_data = evf()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		$next_web_hook_settings = $pro_instance->output_single_webhook_settings( (object) array( 'form_data' => $form_data ), $next_id, array() );

		wp_send_json_success( array( 'content' => $next_web_hook_settings ) );
	}

	/**
	 * Ajax handler to update editable entry.
	 *
	 * @since 1.9.7
	 *
	 * @throws Exception If editable entry cannot be updated.
	 */
	public static function update_entry() {
		global $wpdb;

		// Run a security check.
		check_ajax_referer( 'everest_forms_entry_update', 'security' );

		if ( ! isset( $_POST['data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_die( -1 );
		}

		try {
			parse_str( wp_unslash( $_POST['data'] ), $data ); // phpcs:ignore WordPress.Security.NonceVerification

			// Normalize data structure for both frontend listing and admin side.

			self::update_entry_data( $data );

			wp_send_json_success( array( 'message' => __( 'Entry updated successfully!', 'everest-forms-pro' ) ) );
		} catch ( Exception $e ) {
			$error = json_decode( $e->getMessage(), true );

			if ( is_array( $error ) ) {
				wp_send_json_error( $error );
			} else {
				wp_send_json_error(
					array(
						'message' => $e->getMessage(),
					)
				);
			}
		}
	}

	/**
	 * Update the entry data.
	 */
	public static function update_entry_data( $data ) {
		global $wpdb;

		evf()->task->form_fields = array();

		$form_id = absint( $data['everest_forms']['form_id'] );

		// Get the form data
		$form = evf()->form->get( $form_id );

			// Validate form is real and active (published)
		if ( ! $form || 'publish' !== $form->post_status ) {
			throw new Exception(
				__( 'Invalid form. Please check again.', 'everest-forms-pro' ) . ' ' . $data['everest_forms']['form_id']
			);
		}

			// Formatted form data for hooks
			$form_data = apply_filters(
				'everest_forms_process_before_form_data',
				evf_decode( $form->post_content ),
				$data['everest_forms']
			);
			// Pre-process filter
			$entry = apply_filters(
				'everest_forms_process_before_filter_entry_update',
				$data['everest_forms'],
				$form_data
			);

			// Validate editable entry is real
			$entry_data = evf_get_entry( (int) $entry['entry_id'], true );
			// Check permissions
		if ( ! current_user_can( 'everest_forms_edit_entries', $entry_data->entry_id ) ) {
			throw new Exception( __( 'Insufficient permissions.', 'everest-forms-pro' ) );
		}

		if ( ! $entry_data->entry_id || trim( $entry['entry_id'] ) !== trim( $entry_data->entry_id ) ) {
			throw new Exception(
				__( 'Invalid entry ID. Please check again.', 'everest-forms-pro' ) . ' ' . $entry['entry_id']
			);
		}

		// Validate form fields of editable form
		$form_fields = isset( $entry['form_fields'] ) ? (array) $entry['form_fields'] : array();

		// Process field updates
		foreach ( $form_data['form_fields'] as $field ) {
			$field_id     = $field['id'];
			$field_key    = $field['meta-key'];
			$field_type   = $field['type'];
			$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : array();

			// Handle file uploads
			if ( in_array( $field_type, array( 'file-upload', 'image-upload' ) ) ) {
				$field_submit['new_files'] = isset( $data[ 'everest_forms_' . $form_id . '_' . $field_id ] ) ? $data[ 'everest_forms_' . $form_id . '_' . $field_id ] : array();
				$field_submit['old_files'] = isset( $data[ 'everest_forms_' . $form_id . '_old_' . $field_id ] ) ? $data[ 'everest_forms_' . $form_id . '_old_' . $field_id ] : array();

				$deleted_files = isset( $data[ 'everest_forms_' . $form_id . '_delete_' . $field_id ] ) ? $data[ 'everest_forms_' . $form_id . '_delete_' . $field_id ] : '';

				/**
				 * Check required file upload field
				 * If required and no files (new or old) exist, throw error.
				 *
				 * @since xx.xx.xx
				 */
				if ( evf_string_to_bool( $form_data['form_fields'][$field_id]['required'] ) && empty( $field_submit['new_files'] ) && empty( $field_submit['old_files'] ) ) {
					throw new Exception(
						wp_json_encode(
						array(
							'message'  => sprintf(
								__( get_option( 'everest_forms_required_validation' ), 'everest-forms-pro' ),
								$form_data['form_fields'][ $field_id ]['label']
							),
							'field_id' => $field_id,
							'form_id'  => $form_id,
						)
					)
					);
				}

				if ( ! empty( $deleted_files ) ) {
					$deleted_files = json_decode( $deleted_files, true );
					foreach ( $deleted_files as $file ) {
						$file = json_decode( $file, true );
						if ( ! empty( $file ) ) {
							FormHelper::remove_file( $file['value'] );
						}
					}
				}
			}

			// Handle signature updates
			if ( 'signature' === $field_type ) {
				$signature = isset( $data[ 'everest_forms_' . $form_id . '_old_signature_image_' . $field_id ] ) ? $data[ 'everest_forms_' . $form_id . '_old_signature_image_' . $field_id ] : '';

				if ( $signature !== $entry['form_fields'][ $field_id ]['signature_image'] ) {
					$uploads       = wp_upload_dir();
					$signature_url = trailingslashit( content_url() ) .
					str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $signature );
					FormHelper::remove_file( $signature_url );
				}
			}

			// Skip non-editable fields
			if ( ! apply_filters(
				'everest_forms_admin_entries_edit_field_output_editable',
				Helper::is_field_entries_editable( $field['type'] ),
				$field
			) ) {
				continue;
			}

			// Sub-fields that only live inside a repeater are formatted by EVF_Field_Repeater::format().
			$repeater_fields = isset( $field['repeater-fields'] ) ? $field['repeater-fields'] : 'no';
			if ( 'yes' === $repeater_fields && 'repeater-fields' !== $field_type ) {
				continue;
			}

			do_action(
				"everest_forms_process_format_{$field_type}",
				$field_id,
				$field_submit,
				$form_data,
				$field_key
			);
		}

			// Post-process filter
			$form_fields = apply_filters(
				'everest_forms_process_filter_entry_update',
				evf()->task->form_fields,
				$entry,
				$form_data
			);

			// Post-process hook
			do_action( 'everest_forms_process_form', $form_fields, $entry, $form_data );

			// Merge entry fields
			$entry_fields = array_merge( json_decode( $entry_data->fields, true ), $form_fields );

			// Update entry
			$wpdb->update(
				$wpdb->prefix . 'evf_entries',
				array( 'fields' => wp_json_encode( $entry_fields ) ),
				array(
					'form_id'  => (int) $entry['form_id'],
					'entry_id' => (int) $entry['entry_id']
				)
			);

			// Update entry meta
		foreach ( $form_fields as $field ) {
			$field = apply_filters(
				'everest_forms_entry_save_fields',
				$field,
				$form_data,
				$entry['entry_id']
			);

			if ( ! apply_filters(
				'everest_forms_admin_entries_edit_field_output_editable',
				Helper::is_field_entries_editable( $field['type'] ),
				$field
			) ) {
					continue;
			}

			if ( isset( $field['meta_key'], $field['value'] ) ) {
				$wpdb->update(
					$wpdb->prefix . 'evf_entrymeta',
					array( 'meta_value' => maybe_serialize( $field['value'] ) ),
					array(
						'entry_id' => (int) $entry['entry_id'],
						'meta_key' => $field['meta_key']
					)
				);
			}
		}

			// Clear relevant caches
			$cache_keys = array(
				'evf-entry',
				'evf-entrymeta',
				'evf-entries-ids',
				'evf-last-entries-count',
				'evf-search-entries'
			);
			foreach ( $cache_keys as $key ) {
				wp_cache_delete( $entry['entry_id'], $key );
			}
			wp_cache_delete( EVF_Cache_Helper::get_cache_prefix( 'entries' ) . '_unread_count', 'entries' );
	}
}

EVF_PRO_AJAX::init();
