<?php
/**
 * EverestForms Conditional Logics
 *
 * @package EverestForms\Admin
 * @version 1.1.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Conditional_Logics Class.
 */
class EVF_Conditional_Logics {

	/**
	 * Construct.
	 */
	public function __construct() {
		add_action( 'everest_forms_field_options_after_advanced-options', array( $this, 'conditional_logic_field' ), 11, 2 );
		add_action( 'everest_forms_field_properties', array( $this, 'conditional_logic_field_properties' ), 10, 3 );
		add_action( 'everest_forms_submission_redirection_settings', array( $this, 'conditional_logic_submission_redirection' ), 50, 2 );
		add_action( 'everest_forms_multipart_part_conditional_logic', array( $this, 'conditional_logic_multipart_part' ), 50, 2 );
		add_action( 'everest_forms_rows_conditional_logic', array( $this, 'conditional_logic_rows' ), 50, 2 );
		add_action( 'everest_forms_entry_email_process', array( $this, 'conditional_logic_email_process' ), 10, 5 );
		add_action( 'everest_forms_inline_email_settings', array( $this, 'conditional_logic_email_setting' ), 50, 2 );
		add_action( 'everest_forms_inline_payment_settings', array( $this, 'conditional_logic_payment_setting' ), 50, 3 );
		add_action( 'everest_forms_inline_sms_notifications_settings', array( $this, 'conditional_logic_sms_notifications_setting' ), 50, 3 );
		add_action( 'everest_forms_inline_submit_settings', array( $this, 'conditional_logic_submit_setting' ), 50, 3 );
		add_filter( 'everest_forms_visible_fields', array( $this, 'conditional_logic_visible_field' ), 50, 4 );
		add_action( 'everest_forms_process_complete', array( $this, 'set_transient' ), 5, 4 );
		add_action( 'evf_paypal_standard_process_complete', array( $this, 'send_email_after_payment_complete' ), 50, 4 );
		add_action( 'everest_forms_stripe_process_complete', array( $this, 'send_email_after_stripe_payment_complete' ), 50, 4 );
		add_action( 'everest_forms_square_process_complete', array( $this, 'send_email_after_payment_complete' ), 50, 4 );
		add_action( 'everest_forms_razorpay_process_complete', array( $this, 'send_email_after_payment_complete' ), 50, 4 );

		// Conditional Redirection.
		add_filter( 'everest_forms_submission_redirection_process', array( $this, 'conditional_logic_submission_redirection_process' ), 10, 3 );
		add_filter( 'everest_forms_entry_payment_process', array( $this, 'conditional_logic_payment_process' ), 10, 5 );
		add_filter( 'everest_forms_sms_notifications_process', array( $this, 'conditional_logic_sms_notifications_process' ), 10, 5 );
		add_filter( 'everest_forms_process_filter', array( $this, 'check_conditional_logic_before_submit' ), 10, 3 );
	}

	/**
	 * Save posted entry data in transient.
	 *
	 * @param array $form_fields Form fields.
	 * @param array $entry Entry data.
	 * @param array $form_data Form data.
	 * @param int   $entry_id Entry ID.
	 */
	public function set_transient( $form_fields, $entry, $form_data, $entry_id ) {
		$notifications       = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();
		$data                = array();
		$data['form_fields'] = $form_fields;
		$data['entry']       = $entry;
		$transient_name      = 'evf_entry_data_' . (int) $entry_id;

		foreach ( $notifications as $notification ) :
			if ( ! isset( $notification['conditional_logic_status'] ) || '1' !== $notification['conditional_logic_status'] ) {
				continue;
			}

			$conditionals = isset( $notification['conditionals'] ) ? $notification['conditionals'] : array();

			foreach ( $conditionals as $group ) {
				foreach ( $group as $rule ) {
					$rule_field = isset( $rule['field'] ) ? $rule['field'] : '';
					if ( ! empty( $rule_field ) && 'payment' === $rule_field ) {
						if ( false === get_transient( $transient_name ) ) {
							set_transient( $transient_name, $data, DAY_IN_SECONDS );
						}
					}
				}
			}
		endforeach;
	}

	/**
	 * Send Email only after payment is completed.
	 *
	 * @param mixed $payment Entry details.
	 * @param array $form_data Form data.
	 * @param int   $payment_id Payment ID.
	 * @param array $data Payment details.
	 */
	public function send_email_after_stripe_payment_complete( $payment, $form_data, $payment_id, $data ) {
		$payment_status = isset( $data['payment_status'] ) ? strtolower( $data['payment_status'] ) : '';
		$notifications  = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();
		$is_not_field   = array( 'location', 'status', 'type', 'meta' );
		$transient_data = get_transient( 'evf_entry_data_' . absint( $payment_id ) );
		$fields         = isset( $data['fields'] ) ? $data['fields'] : $transient_data['form_fields'];
		$entry          = isset( $data['entry'] ) ? $data['entry'] : $transient_data['entry'];

		if ( 'complete' === $payment_status ) {
			$payment_status = 'completed';
		}

		foreach ( $fields as $key => $value ) {
			if ( ! in_array( $key, $is_not_field ) ) {
				$entry['form_fields'][ $key ] = $value;
			}
		}

		$filtered_notifications = array_filter(
			$notifications,
			function ( $notification ) use ( $payment_status, $form_data, $fields, $payment_id ) {
				if ( isset( $notification['conditional_logic_status'] ) && $notification['conditional_logic_status'] === '1' ) {
					$type          = isset( $notification['conditional_option'] ) ? $notification['conditional_option'] : '';
					$conditionals  = isset( $notification['conditionals'] ) ? $notification['conditionals'] : array();
					$process_email = true;

					foreach ( $conditionals as $group_id => $group ) {
						foreach ( $group as $rule_id => $rule ) {
							$rule_field    = isset( $rule['field'] ) ? $rule['field'] : '';
							$rule_operator = isset( $rule['operator'] ) ? $rule['operator'] : '';
							$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';

							if ( $rule_field === 'payment' && $rule_operator === 'is' && $rule_value !== 'completed' ) {
								$process_email = false;
								break 2;
							}

							switch ( $rule_operator ) {
								case 'is':
									$process_email = ( $rule_value == $payment_status );
									break;
								case 'is_not':
									$process_email = ( $rule_value != $payment_status );
									break;
							}
						}
					}

					if ( $type === 'not_send' ) {
						$process_email = ! $process_email;
					}

					return $process_email;
				}

				return false;
			}
		);

		foreach ( $filtered_notifications as $connection_id => $notification ) {
			$email        = array();
			$evf_to_email = isset( $notification['evf_to_email'] ) ? $notification['evf_to_email'] : '';

			// Setup email properties.
			$email['subject']        = ! empty( $notification['evf_email_subject'] ) ? $notification['evf_email_subject'] : sprintf( esc_html__( 'New %s Entry', 'everest-forms-pro' ), $form_data['settings']['form_title'] );
			$email['address']        = explode( ',', apply_filters( 'everest_forms_process_smart_tags', $evf_to_email, $form_data, $fields, $payment_id ) );
			$email['address']        = array_map( 'sanitize_email', $email['address'] );
			$email['sender_name']    = ! empty( $notification['evf_from_name'] ) ? $notification['evf_from_name'] : get_bloginfo( 'name' );
			$email['sender_address'] = ! empty( $notification['evf_from_email'] ) ? $notification['evf_from_email'] : get_option( 'admin_email' );
			$email['reply_to']       = ! empty( $notification['evf_reply_to'] ) ? $notification['evf_reply_to'] : $email['sender_address'];
			$email['message']        = ! empty( $notification['evf_email_message'] ) ? $notification['evf_email_message'] : '{all_fields}';
			$email                   = apply_filters( 'everest_forms_entry_email_atts', $email, $fields, $entry, $form_data );

			$attachment = '';

			// Create new email.
			$emails = new EVF_Emails();
			$emails->__set( 'form_data', $form_data );
			$emails->__set( 'fields', $fields );
			$emails->__set( 'entry_id', $payment_id );
			$emails->__set( 'from_name', $email['sender_name'] );
			$emails->__set( 'from_address', $email['sender_address'] );
			$emails->__set( 'reply_to', $email['reply_to'] );
			$emails->__set( 'attachments', apply_filters( 'everest_forms_email_file_attachments', $attachment, $fields, $form_data, 'entry-email', $connection_id, $payment_id ) );

			if ( 'yes' === get_option( 'everest_forms_enable_email_copies' ) ) {

				if ( ! empty( $notification['evf_carboncopy'] ) ) {
					$emails->__set( 'cc', $notification['evf_carboncopy'] );
				}

				if ( ! empty( $notification['evf_blindcarboncopy'] ) ) {
					$emails->__set( 'bcc', $notification['evf_blindcarboncopy'] );
				}
			}

			$emails = apply_filters( 'everest_forms_entry_email_before_send', $emails );

			// Send entry email.
			foreach ( $email['address'] as $address ) {
				$emails->send( trim( $address ), $email['subject'], $email['message'], '', $connection_id );
			}
		}
	}


	/**
	 * Send Email only after payment is completed.
	 *
	 * @param mixed $payment Entry details.
	 * @param array $form_data Form data.
	 * @param int   $payment_id Payment ID.
	 * @param array $data Payment details.
	 */
	public function send_email_after_payment_complete( $payment, $form_data, $payment_id, $data ) {
		$payment_status = isset( $data['payment_status'] ) ? $data['payment_status'] : '';
		$notifications  = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();
		$is_not_field   = array( 'location', 'status', 'type', 'meta' );
		$transient_data = get_transient( 'evf_entry_data_' . absint( $payment_id ) );
		$fields         = isset( $data['fields'] ) ? $data['fields'] : $transient_data['form_fields'];
		$entry          = isset( $data['entry'] ) ? $data['entry'] : $transient_data['entry'];

		if ( 'complete' === strtolower( $payment_status ) ) {
			$payment_status = 'completed';
		}

		foreach ( $fields as $key => $value ) {
			if ( ! in_array( $key, $is_not_field ) ) {
				$entry['form_fields'][ $key ] = $value;
			}
		}

		foreach ( $notifications as $connection_id => $notification ) :
			if ( ! isset( $notification['conditional_logic_status'] ) || '1' !== $notification['conditional_logic_status'] ) {
				continue;
			}

			$type          = isset( $notification['conditional_option'] ) ? $notification['conditional_option'] : '';
			$conditionals  = isset( $notification['conditionals'] ) ? $notification['conditionals'] : array();
			$process_email = true;

			foreach ( $conditionals as $group_id => $group ) {

				foreach ( $group as $rule_id => $rule ) {

					$rule_field    = isset( $rule['field'] ) ? $rule['field'] : '';
					$rule_operator = isset( $rule['operator'] ) ? $rule['operator'] : '';
					$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';

					if ( ! empty( $rule_field ) && 'payment' !== $rule_field ) {
						continue;
					}

					switch ( $rule_operator ) {
						case 'is':
							$process_email = ( $rule_value == strtolower( $payment_status ) );
							break;
						case 'is_not':
							$process_email = ( $rule_value != strtolower( $payment_status ) );
							break;
					}
				}
			}

			if ( 'not_send' === $type ) {
				$process_email = ! $process_email;
			}

			if ( ! $process_email ) {
				continue;
			}

			$email        = array();
			$evf_to_email = isset( $notification['evf_to_email'] ) ? $notification['evf_to_email'] : '';

			// Setup email properties.
			/* translators: %s - form name. */
			$email['subject']        = ! empty( $notification['evf_email_subject'] ) ? $notification['evf_email_subject'] : sprintf( esc_html__( 'New %s Entry', 'everest-forms-pro' ), $form_data['settings']['form_title'] );
			$email['address']        = explode( ',', apply_filters( 'everest_forms_process_smart_tags', $evf_to_email, $form_data, $fields, $payment_id ) );
			$email['address']        = array_map( 'sanitize_email', $email['address'] );
			$email['sender_name']    = ! empty( $notification['evf_from_name'] ) ? $notification['evf_from_name'] : get_bloginfo( 'name' );
			$email['sender_address'] = ! empty( $notification['evf_from_email'] ) ? $notification['evf_from_email'] : get_option( 'admin_email' );
			$email['reply_to']       = ! empty( $notification['evf_reply_to'] ) ? $notification['evf_reply_to'] : $email['sender_address'];
			$email['message']        = ! empty( $notification['evf_email_message'] ) ? $notification['evf_email_message'] : '{all_fields}';
			$email                   = apply_filters( 'everest_forms_entry_email_atts', $email, $fields, $entry, $form_data );

			$attachment = '';

			// Create new email.
			$emails = new EVF_Emails();
			$emails->__set( 'form_data', $form_data );
			$emails->__set( 'fields', $fields );
			$emails->__set( 'entry_id', $payment_id );
			$emails->__set( 'from_name', $email['sender_name'] );
			$emails->__set( 'from_address', $email['sender_address'] );
			$emails->__set( 'reply_to', $email['reply_to'] );
			$emails->__set( 'attachments', apply_filters( 'everest_forms_email_file_attachments', $attachment, $fields, $form_data, 'entry-email', $connection_id, $payment_id ) );

			// Maybe include Cc and Bcc email addresses.
			if ( 'yes' === get_option( 'everest_forms_enable_email_copies' ) ) {

				if ( ! empty( $notification['evf_carboncopy'] ) ) {
					$emails->__set( 'cc', $notification['evf_carboncopy'] );
				}

				if ( ! empty( $notification['evf_blindcarboncopy'] ) ) {
					$emails->__set( 'bcc', $notification['evf_blindcarboncopy'] );
				}
			}

			$emails = apply_filters( 'everest_forms_entry_email_before_send', $emails );

			// Send entry email.
			foreach ( $email['address'] as $address ) {
				$emails->send( trim( $address ), $email['subject'], $email['message'], '', $connection_id );
			}

		endforeach;
	}

	/**
	 * Conditional_logic_visible_field.
	 *
	 * @param mixed $visibility Visibility.
	 * @param mixed $field Field.
	 * @param mixed $entry Entry.
	 * @param mixed $form_data FormData.
	 */
	public function conditional_logic_visible_field( $visibility, $field, $entry, $form_data ) {
		$fields                   = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$field_conditional_status = isset( $form_data['form_fields'][ $field['id'] ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field['id'] ]['conditional_logic_status'] : '0';
		$field_conditional_option = isset( $form_data['form_fields'][ $field['id'] ]['conditional_option'] ) ? $form_data['form_fields'][ $field['id'] ]['conditional_option'] : 'show';
		$pass                     = false;
		if ( $field_conditional_status ) {
			$field_conditions = isset( $form_data['form_fields'][ $field['id'] ]['conditionals'] ) ? $form_data['form_fields'][ $field['id'] ]['conditionals'] : array();
		} else {
			$field_conditions = array();
		}
		$row_conditions       = $this->get_row_conditions( $form_data, $field['id'] );
		$multipart_conditions = $this->get_multipart_conditions( $form_data, $field['id'] );
		$distinct_option      = false; // Row and Field conditional logic have different show/hide option.
		if ( ! empty( $row_conditions['conditionals'] ) ) {
			if ( $row_conditions['conditional_option'] !== $field_conditional_option ) {
				$distinct_option          = true;
				$field_conditional_option = $row_conditions['conditional_option'];
			}
			$updated_conditions = array();
			foreach ( $row_conditions['conditionals'] as $rk => $rv ) {
				$main_logic = array();
				foreach ( $rv as $rvk => $rvv ) {
					$main_logic [] = $rvv;
				}
				if ( ! empty( $field_conditions ) ) {
					foreach ( $field_conditions as $fk => $fv ) {
						$sub_logic = array();
						foreach ( $fv as $fvk => $fvv ) {
							if ( ! empty( $distinct_option ) ) {
								if ( 'greater_than' === $fvv['operator'] ) {
									$fvv['value'] = $fvv['value'] + 0.00001;
								} elseif ( 'less_than' === $fvv['operator'] ) {
									$fvv['value'] = $fvv['value'] - 0.00001;
								}
								$fvv = $this->swicth_conditions( $fvv );
							}
							$sub_logic [] = $fvv;
						}
						$updated_conditions [] = array_merge( $main_logic, $sub_logic );
					}
				} else {
					$updated_conditions [] = $main_logic;
				}
			}
			$field_conditions = $updated_conditions;
		}
		$distinct_option = false; // Part and Field conditional logic have different show/hide option.
		if ( ! empty( $multipart_conditions['conditionals'] ) ) {
			if ( $multipart_conditions['conditional_option'] !== $field_conditional_option ) {
				$distinct_option          = true;
				$field_conditional_option = $multipart_conditions['conditional_option'];
			}

			$updated_conditions = array();
			foreach ( $multipart_conditions['conditionals'] as $rk => $rv ) {
				$main_logic = array();
				foreach ( $rv as $rvk => $rvv ) {
					$main_logic [] = $rvv;
				}
				if ( ! empty( $field_conditions ) ) {
					foreach ( $field_conditions as $fk => $fv ) {
						$sub_logic = array();
						foreach ( $fv as $fvk => $fvv ) {
							if ( ! empty( $distinct_option ) ) {
								if ( 'greater_than' === $fvv['operator'] ) {
									$fvv['value'] = $fvv['value'] + 0.00001;
								} elseif ( 'less_than' === $fvv['operator'] ) {
									$fvv['value'] = $fvv['value'] - 0.00001;
								}
								$fvv = $this->swicth_conditions( $fvv );
							}
							$sub_logic [] = $fvv;
						}
						$updated_conditions [] = array_merge( $main_logic, $sub_logic );
					}
				} else {
					$updated_conditions [] = $main_logic;
				}
			}
			$field_conditions = $updated_conditions;
		}

		if ( '1' === $field_conditional_status || ! empty( $row_conditions['conditionals'] ) || ! empty( $multipart_conditions['conditionals'] ) ) {
			foreach ( $field_conditions as $group_id => $group ) {
				$pass_group = true;
				foreach ( $group as $rule_id => $rule ) {
					$rule_field    = $rule['field'];
					$rule_operator = $rule['operator'];
					$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';
					$pass_rule     = true;
					if ( empty( $rule_field ) || ! isset( $fields[ $rule_field ]['type'] ) ) {
						continue;
					}

					if ( empty( $entry['form_fields'][ $rule_field ] ) ) {
						$entry['form_fields'][ $rule_field ] = ''; // Setting empty values for conditionally hidden field in row/multi-part.
					}

					if ( in_array( $fields[ $rule_field ]['type'], array( 'text', 'first-name', 'last-name', 'textarea', 'email', 'url', 'number', 'hidden', 'payment-quantity', 'lookup', 'date-time' ), true ) ) {
						if ( 'payment-quantity' === $fields[ $rule_field ]['type'] ) {
							$raw_pq = $entry['form_fields'][ $rule_field ];
							$left   = is_array( $raw_pq ) ? implode( ' ', array_filter( $raw_pq ) ) : $raw_pq;
							$right  = $rule_value;
						} else {
							$raw_left = $entry['form_fields'][ $rule_field ];
							$left     = trim( strtolower( is_array( $raw_left ) ? implode( ' ', array_filter( $raw_left ) ) : ( is_string( $raw_left ) ? $raw_left : '' ) ) );
							$right    = trim( strtolower( is_array( $rule_value ) ? implode( ' ', array_filter( $rule_value ) ) : ( is_string( $rule_value ) ? $rule_value : '' ) ) );
						}

						if ( 'title' === $field['type'] && empty( $left ) ) {
							$left = $entry[ $rule_field ]['value'];
						}

						if ( 'html' === $field['type'] && empty( $left ) ) {
							$left = $entry[ $rule_field ]['value'];
						}

						switch ( $rule_operator ) {
							case 'is':
								$pass_rule = ( $left == $right );
								break;
							case 'is_not':
								$pass_rule = ( $left != $right );
								break;
							case 'empty':
								$pass_rule = ( '' == $left );
								break;
							case 'not_empty':
								$pass_rule = ( '' != $left );
								break;

							case 'greater_than':
								if ( ! empty( $left ) ) {
									$left_timestamp  = is_numeric( $left ) ? (int) $left : strtotime( $left );
									$right_timestamp = is_numeric( $right ) ? (int) $right : strtotime( $right );

									if ( $left_timestamp !== false && $right_timestamp !== false ) {
										$pass_rule = $left_timestamp > $right_timestamp;
									} else {
										$left_clean  = preg_replace( '/[^0-9.]/', '', $left );
										$right_clean = preg_replace( '/[^0-9.]/', '', $right );
										$pass_rule   = ( $left_clean !== '' ) && ( floatval( $left_clean ) > floatval( $right_clean ) );
									}
								}
								break;

							case 'less_than':
								if ( ! empty( $left ) ) {
									$left_timestamp  = is_numeric( $left ) ? (int) $left : strtotime( $left );
									$right_timestamp = is_numeric( $right ) ? (int) $right : strtotime( $right );

									if ( $left_timestamp !== false && $right_timestamp !== false ) {
										$pass_rule = $left_timestamp < $right_timestamp;
									} else {
										$left_clean  = preg_replace( '/[^0-9.]/', '', $left );
										$right_clean = preg_replace( '/[^0-9.]/', '', $right );
										$pass_rule   = ( $left_clean !== '' ) && ( floatval( $left_clean ) < floatval( $right_clean ) );
									}
								}
								break;

							case 'between':
								if ( $left === $right ) {
									$pass_rule = true;
								} elseif ( ! empty( $left ) && ! empty( $right ) ) {
									$date_range = explode( ' to ', $right );

									if ( count( $date_range ) === 2 ) {
										$start_date = strtotime( $date_range[0] );
										$end_date   = strtotime( $date_range[1] );

										if ( strpos( $left, ' to ' ) !== false ) {
											$left_range = explode( ' to ', $left );
											if ( count( $left_range ) === 2 ) {
												$left_start_date = strtotime( $left_range[0] );
												$left_end_date   = strtotime( $left_range[1] );

												$pass_rule = ( $left_start_date >= $start_date && $left_end_date <= $end_date );
											} else {
												$pass_rule = false;
											}
										} else {
											$left_date = strtotime( $left );
											$pass_rule = ( $left_date >= $start_date && $left_date <= $end_date );
										}
									} else {
										$pass_rule = false;
									}
								} else {
									$pass_rule = false;
								}
								break;

							case 'multiple':
								if ( $left === $right ) {
									$pass_rule = true;
								} else {
									$right_dates    = explode( ', ', $right );
									$selected_dates = explode( ', ', $left );

									$allMatch = true;
									foreach ( $selected_dates as $date ) {
										if ( ! in_array( $date, $right_dates ) ) {
											$allMatch = false;
											break;
										}
									}

									if ( $allMatch && count( $selected_dates ) === count( $right_dates ) ) {
										$pass_rule = true;
									} else {
										$pass_rule = false;
									}
								}
								break;
						}
					} else {
						$provided_id = false;
						if (
							in_array( $fields[ $rule_field ]['type'], array( 'payment-multiple', 'payment-checkbox' ), true ) &&
							isset( $fields[ $rule_field ]['choices'] )
						) {
							$values     = isset( $entry['form_fields'][ $rule_field ] ) ? $entry['form_fields'][ $rule_field ] : array();
							$rule_value = isset( $form_data['form_fields'][ $rule_field ]['choices'][ $rule_value ] ) ? $form_data['form_fields'][ $rule_field ]['choices'][ $rule_value ]['label'] : '';
							foreach ( $form_data['form_fields'][ $rule_field ]['choices'] as $key => $choice ) {

								$choice = array_map( 'sanitize_text_field', $choice );

								foreach ( $fields[ $rule_field ]['choices'] as $key => $value ) {
									$value = evf_decode_string( $value['label'] );
									if ( ( ! is_array( $values ) && $key == $values ) || ( is_array( $values ) && in_array( $key, $values ) ) ) {
										$provided_id[] = strtolower( $value );
									}
								}
							}
						} elseif ( isset( $entry['form_fields'][ $rule_field ] ) && '' != $entry['form_fields'][ $rule_field ] ) {

							$provided_id = array();

							if ( 'checkbox' === $fields[ $rule_field ]['type'] ) {
								$values = $entry['form_fields'][ $rule_field ];
							} else {
								$values = (array) $entry['form_fields'][ $rule_field ];
							}

							if ( isset( $form_data['form_fields'][ $rule_field ]['choices'] ) && is_array( $form_data['form_fields'][ $rule_field ]['choices'] ) ) {
								foreach ( $form_data['form_fields'][ $rule_field ]['choices'] as $key => $choice ) {
									$choice = array_map( 'sanitize_text_field', $choice );

									foreach ( $values as $value ) {
										$value = evf_decode_string( $value );

										if ( in_array( $value, $choice, true ) ) {
											$provided_id[] = strtolower( $value );
										}
									}
								}
							}
						}

						$left  = (array) $provided_id;
						$right = strtolower( $rule_value );

						switch ( $rule_operator ) {
							case 'is':
								$pass_rule = in_array( $right, (array) $left );
								break;
							case 'is_not':
								$pass_rule = ! in_array( $right, (array) $left );
								break;
							case 'empty':
								$pass_rule = ( '' == $left );
								break;
							case 'not_empty':
								$pass_rule = ( '' != $left );
								break;
							case 'greater_than':
								if ( ! empty( $left ) ) {
									$left_str        = is_array( $left ) ? implode( ' ', array_filter( $left ) ) : (string) $left;
									$right_str       = is_array( $right ) ? implode( ' ', array_filter( (array) $right ) ) : (string) $right;
									$left_timestamp  = is_numeric( $left_str ) ? (int) $left_str : strtotime( $left_str );
									$right_timestamp = is_numeric( $right_str ) ? (int) $right_str : strtotime( $right_str );

									if ( $left_timestamp !== false && $right_timestamp !== false ) {
										$pass_rule = $left_timestamp > $right_timestamp;
									} else {
										$left_clean  = preg_replace( '/[^0-9.]/', '', $left_str );
										$right_clean = preg_replace( '/[^0-9.]/', '', $right_str );
										$pass_rule   = ( $left_clean !== '' ) && ( floatval( $left_clean ) > floatval( $right_clean ) );
									}
								}
								break;
							case 'less_than':
								if ( ! empty( $left ) ) {
									$left_str        = is_array( $left ) ? implode( ' ', array_filter( $left ) ) : (string) $left;
									$right_str       = is_array( $right ) ? implode( ' ', array_filter( (array) $right ) ) : (string) $right;
									$left_timestamp  = is_numeric( $left_str ) ? (int) $left_str : strtotime( $left_str );
									$right_timestamp = is_numeric( $right_str ) ? (int) $right_str : strtotime( $right_str );

									if ( $left_timestamp !== false && $right_timestamp !== false ) {
										$pass_rule = $left_timestamp < $right_timestamp;
									} else {
										$left_clean  = preg_replace( '/[^0-9.]/', '', $left_str );
										$right_clean = preg_replace( '/[^0-9.]/', '', $right_str );
										$pass_rule   = ( $left_clean !== '' ) && ( floatval( $left_clean ) < floatval( $right_clean ) );
									}
								}
								break;
							case 'between':
								if ( $left === $right ) {
									$pass_rule = true;
								} elseif ( ! empty( $left ) && ! empty( $right ) ) {
									$left_str  = is_array( $left ) ? implode( ' ', array_filter( $left ) ) : (string) $left;
									$right_str = is_array( $right ) ? implode( ' ', array_filter( (array) $right ) ) : (string) $right;

									$date_range = explode( ' to ', $right_str );

									if ( count( $date_range ) === 2 ) {
										$start_date = strtotime( $date_range[0] );
										$end_date   = strtotime( $date_range[1] );

										if ( strpos( $left_str, ' to ' ) !== false ) {
											$left_range = explode( ' to ', $left_str );
											if ( count( $left_range ) === 2 ) {
												$left_start_date = strtotime( $left_range[0] );
												$left_end_date   = strtotime( $left_range[1] );

												$pass_rule = ( $left_start_date >= $start_date && $left_end_date <= $end_date );
											} else {
												$pass_rule = false;
											}
										} else {
											$left_date = strtotime( $left_str );
											$pass_rule = ( $left_date >= $start_date && $left_date <= $end_date );
										}
									} else {
										$pass_rule = false;
									}
								} else {
									$pass_rule = false;
								}
								break;

							case 'multiple':
								if ( $left === $right ) {
									$pass_rule = true;
								} else {
									$left_str       = is_array( $left ) ? implode( ', ', array_filter( $left ) ) : (string) $left;
									$right_str      = is_array( $right ) ? implode( ', ', array_filter( (array) $right ) ) : (string) $right;
									$right_dates    = explode( ', ', $right_str );
									$selected_dates = explode( ', ', $left_str );

									$allMatch = true;
									foreach ( $selected_dates as $date ) {
										if ( ! in_array( $date, $right_dates ) ) {
											$allMatch = false;
											break;
										}
									}

									if ( $allMatch && count( $selected_dates ) === count( $right_dates ) ) {
										$pass_rule = true;
									} else {
										$pass_rule = false;
									}
								}
								break;
						}
					}
					if ( ! $pass_rule ) {
						$pass_group = false;
						break;
					}
				}
				if ( $pass_group ) {
					$pass = true;
					break;
				}
			}
			if ( ( $pass && 'show' === $field_conditional_option ) || ( ! $pass && 'hide' === $field_conditional_option ) ) {
				$visibility = true;
			} else {
				$visibility = false;
			}
		}
		return $visibility;
	}

	/**
	 * Check Conditional Logic before submit.
	 *
	 * @param array $fields    Form fields.
	 * @param array $entry     Entry data.
	 * @param array $form_data Form data.
	 */
	public function check_conditional_logic_before_submit( $fields, $entry, $form_data ) {
		foreach ( $fields as $field_id => $field ) {
			if ( isset( $form_data['form_fields'][ $field_id ] ) ) {
				$field_data = $form_data['form_fields'][ $field_id ];
				$is_visible = apply_filters( 'everest_forms_visible_fields', true, $field_data, $entry, $form_data );

				if ( ! $is_visible ) {
					unset( $fields[ $field_id ] );
				}
			}
		}
		return $fields;
	}

	/**
	 * Switch Conditions.
	 *
	 * @param mixed $condition Condition.
	 * @return mixed
	 */
	public function swicth_conditions( $condition ) {
		$operators = array(
			'is'           => 'is_not',
			'is_not'       => 'is',
			'empty'        => 'not_empty',
			'not_empty'    => 'empty',
			'greater_than' => 'less_than',
			'less_than'    => 'greater_than',
		);

		$condition['operator'] = $operators[ $condition['operator'] ];

		return $condition;
	}


	/**
	 * Get Row Conditions.
	 *
	 * @param mixed $form_data Form Data.
	 * @param mixed $field_key Field Key.
	 * @return string
	 */
	public function get_row_conditions( $form_data, $field_key ) {
		if ( ! empty( $form_data['structure'] ) ) {
			foreach ( $form_data['structure'] as $row_key => $grid ) {
				foreach ( $grid as $fields ) {
					if ( is_array( $fields ) && in_array( $field_key, $fields, true ) ) {
						if ( ! empty( $form_data['settings']['form_rows'][ 'connection_' . $row_key ]['conditional_logic_status'] ) && '1' === $form_data['settings']['form_rows'][ 'connection_' . $row_key ]['conditional_logic_status'] ) {
							return $form_data['settings']['form_rows'][ 'connection_' . $row_key ];
						}
					}
				}
			}
		}
		return array();
	}

	/**
	 * Get Multipart Conditions.
	 *
	 * @param mixed $form_data Form Data.
	 * @param mixed $field_key Form Data.
	 * @return string
	 */
	public function get_multipart_conditions( $form_data, $field_key ) {
		if ( ! empty( $form_data['multi_part'] ) ) {
			foreach ( $form_data['multi_part'] as $part_key => $part ) {
				if ( ! empty( $form_data['settings']['multi_part_conditional'][ 'connection_' . $part['id'] ]['conditional_logic_status'] ) && '1' === $form_data['settings']['multi_part_conditional'][ 'connection_' . $part['id'] ]['conditional_logic_status'] ) {
					if ( ! empty( $part['rows'] ) ) {
						foreach ( $part['rows'] as $row ) {
							if ( ! empty( $form_data['structure'][ $row ] ) ) {
								foreach ( $form_data['structure'][ $row ] as $fields ) {
									if ( is_array( $fields ) && in_array( $field_key, $fields, true ) ) {
										return $form_data['settings']['multi_part_conditional'][ 'connection_' . $part['id'] ];
									}
								}
							}
						}
					}
				}
			}
		}
		return array();
	}


	/**
	 * Adding conditional logic rules attr.
	 *
	 * @param array $field_properties Field properties array data.
	 * @param array $field Field data.
	 * @param array $form_data Form data.
	 */
	public function conditional_logic_field_properties( $field_properties, $field, $form_data ) {
		$required = isset( $field['required'] ) ? $field['required'] : 0;
		$field_properties['inputs']['primary']['attr']['conditional_id'] = $field['id'];

		if ( isset( $form_data['settings']['submit']['connection_1']['conditional_logic_status'] ) && '1' === $form_data['settings']['submit']['connection_1']['conditional_logic_status'] ) {
			$conditional_field_trigger_submit = $this->field_is_trigger_for_submit( $field, $form_data );
			if ( $conditional_field_trigger_submit ) {
				$field_properties['container']['class'][] = 'everest-forms-trigger-conditional';
			}
		}

		$conditional_field_trigger_field = $this->field_is_trigger( $field, $form_data );

		if ( $conditional_field_trigger_field ) {
			$field_properties['container']['class'][] = 'everest-forms-trigger-conditional';
		}

		if ( ! isset( $field['conditional_logic_status'] ) ) {
			return $field_properties;
		}

		$conditional_rules = array(
			'conditional_option' => $field['conditional_option'],
			'conditionals'       => $field['conditionals'],
			'required'           => $required,
		);

		$field_properties['inputs']['primary']['attr']['conditional_rules'] = json_encode( $conditional_rules );
		$field_properties['container']['class'][]                           = 'everest-forms-conditional-field';

		return $field_properties;
	}

	/**
	 * Check if the field is triggerd on Submit button conditional logic.
	 *
	 * @param [array] $field Fields.
	 * @param [array] $form_data Form data.
	 */
	public function field_is_trigger_for_submit( $field, $form_data ) {

		$field_id = $field['id'];

		foreach ( $form_data['settings']['submit'] as $field ) {

			if ( empty( $field['conditional_logic_status'] ) || empty( $field['conditionals'] ) || '1' != $field['conditional_logic_status'] ) {
				continue;
			}

			foreach ( $field['conditionals'] as $group ) {

				foreach ( $group as $rule ) {

					if ( ! isset( $rule['field'] ) || '' === trim( $rule['field'] ) || empty( $rule['operator'] ) ) {
						continue;
					}

					if (
						( $rule['field'] == $field_id ) || ( isset( $rule['value'] ) && '' !== trim( $rule['value'] ) && $rule['field'] == $field_id ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Check if the field is triggerd on conditional logic.
	 *
	 * @param [array] $field Fields.
	 * @param [array] $form_data Form data.
	 */
	public function field_is_trigger( $field, $form_data ) {
		$field_id = $field['id'];

		foreach ( $form_data['form_fields'] as $field ) {

			if ( empty( $field['conditional_logic_status'] ) || empty( $field['conditionals'] ) || '1' != $field['conditional_logic_status'] ) {
				continue;
			}

			foreach ( $field['conditionals'] as $group ) {

				foreach ( $group as $rule ) {

					if ( ! isset( $rule['field'] ) || '' === trim( $rule['field'] ) || empty( $rule['operator'] ) ) {
						continue;
					}

					if ( ( $rule['field'] == $field_id ) || ( isset( $rule['value'] ) && '' !== trim( $rule['value'] ) && $rule['field'] == $field_id ) ) {
						return true;
					}
				}
			}
		}

		$form_rows = ! empty( $form_data['settings']['form_rows'] ) ? $form_data['settings']['form_rows'] : array();
		if ( ! empty( $form_rows ) ) {
			foreach ( $form_rows as $conditionals ) {
				$conditional = $conditionals['conditionals'];
				foreach ( $conditional as $group ) {
					foreach ( $group as $rule ) {
						if ( ! isset( $rule['field'] ) || '' === trim( $rule['field'] ) || empty( $rule['operator'] ) ) {
							continue;
						}
						if ( ( $rule['field'] == $field_id ) || ( isset( $rule['value'] ) && '' !== trim( $rule['value'] ) && $rule['field'] == $field_id ) ) {
							return true;
						}
					}
				}
			}
		}

		$multipart_conditionals = ! empty( $form_data['settings']['multi_part_conditional'] ) ? $form_data['settings']['multi_part_conditional'] : array();
		if ( ! empty( $multipart_conditionals ) ) {
			foreach ( $multipart_conditionals as $conditionals ) {
				$conditional = $conditionals['conditionals'];
				foreach ( $conditional as $group ) {
					foreach ( $group as $rule ) {
						if ( ! isset( $rule['field'] ) || '' === trim( $rule['field'] ) || empty( $rule['operator'] ) ) {
							continue;
						}
						if ( ( $rule['field'] == $field_id ) || ( isset( $rule['value'] ) && '' !== trim( $rule['value'] ) && $rule['field'] == $field_id ) ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Form setting for admin and user
	 *
	 * @param mixed $setting Settings.
	 * @param mixed $panel Panel.
	 * @return void
	 */
	public function conditional_logic_submission_redirection( $setting, $panel ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'settings',
				'panel'         => $panel,
				'connection_id' => 'connection_1',
				'source'        => 'redirection',
			)
		);
	}
	/**
	 * custom Confirmation block for conditional logic.
	 *
	 * @since 1.9.7
	 *
	 * @param [type] $args The args data eg form etc.
	 * @param [type] $connection_id The connection id.
	 * @param [type] $key The rule id.
	 * @param [type] $panel The panel type.
	 */
	public static function confirmation_blocks( $args, $connection_id, $key, $panel ) {
		$type = ! empty( $args['type'] ) ? $args['type'] : 'field';

		$form_data = $args['form'];

		if ( ! function_exists( 'evf_form_confirmation_backward_compatibility' ) ) {
			return;
		}

		$form_data = evf_form_confirmation_backward_compatibility( $form_data );

		$settings = $args['settings'];
		$content  = '';
		ob_start();
		echo '<div class="evf-content-section-title">';
		echo '<div class="confirmation-title">';
		echo '<button class="evf-edit-confirm-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
			<path d="M9 15H15.75" stroke="#6B6B6B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M12.2832 2.71679C12.5818 2.41822 12.9867 2.25049 13.409 2.25049C13.8312 2.25049 14.2361 2.41822 14.5347 2.71679C14.8333 3.01536 15.001 3.4203 15.001 3.84254C15.001 4.26478 14.8333 4.66972 14.5347 4.96829L5.5272 13.9765C5.34878 14.155 5.12822 14.2855 4.88595 14.356L2.73195 14.9845C2.66742 15.0034 2.59901 15.0045 2.53389 14.9878C2.46876 14.9711 2.40932 14.9372 2.36179 14.8897C2.31425 14.8422 2.28037 14.7827 2.26369 14.7176C2.247 14.6525 2.24813 14.5841 2.26695 14.5195L2.89545 12.3655C2.96612 12.1235 3.09664 11.9033 3.27495 11.725L12.2832 2.71679Z" stroke="#6B6B6B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M11.25 3.75L13.5 6" stroke="#6B6B6B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
			</svg></button>';

		$title = ! empty( $settings['title'] ) ? $settings['title'] : 'Conditional Confirmation';
		echo '<h3 class="show-title">' . $title . '</h3>';
		everest_forms_panel_field(
			'text',
			$key,
			'title',
			$form_data,
			esc_html__( '', 'everest-forms' ),
			array(
				'parent'      => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection'  => 'settings',
				'class'       => 'edit-title everest-forms-hidden',
				'input_class' => 'evf-confirm-edit-title-input',
				'default'     => isset( $settings['title'] ) ? sanitize_text_field( $settings['title'] ) : 'Conditional Confirmation',
			)
		);

		echo '<button class="evf-confirmation-edit-ok everest-forms-hidden"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="14" viewBox="0 0 15 14" fill="none">
		<path d="M12.2689 3.5L5.85221 9.91667L2.93555 7" stroke="#383838" stroke-width="1.16667" stroke-linecap="round" stroke-linejoin="round"/>
		</svg></button>';

		// for first creation.
		if ( ! isset( $settings['active'] ) ) {
			$settings['active'] = 1;
		}

		$status = ( isset( $settings['active'] ) && 1 == $settings['active'] ) ? 'active' : 'inactive';
		echo '<div class="active-status ' . ( 'active' == $status ? ' ' : 'everest-forms-hidden' ) . '">';
		echo '<span>active</span>';
		echo '</div>';
		echo '<div class="inactive-status ' . ( 'inactive' == $status ? ' ' : 'everest-forms-hidden' ) . '">';
		echo '<span>Inactive</span>';
		echo '</div>';

		echo '</div>';
		echo '<div><a href="#" class="evf-close-custom-confirm-settings" >';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="10" viewBox="0 0 16 10" fill="none">
  		<path d="M2 2L8 8L14 2" stroke="#383838" stroke-width="2.66667" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>';
		echo '</a></div>';
		echo '</div>';
		echo '<div class="evf-conditional-confirmation-body everest-forms-border-container">';
		echo '<h4 class="evf-content-section-title">';
		esc_html_e( 'Confirmation Settings', 'everest-forms' );
		echo '</h4>';

		$confirmation_type = isset( $settings['redirect_to'] ) ? $settings['redirect_to'] : 'same';
		?>
		<div id="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-wrap" class="everest-forms-panel-field evf-builder-radio  everest-forms-panel-field-radio">
			<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to"> <?php echo __( 'Confirmation Type', 'everest-forms' ); ?> <i title="<?php echo __( 'Choose the confirmation type', 'everest-forms' ); ?>" class="dashicons dashicons-editor-help everest-forms-help-tooltip tooltipstered"></i>
			</label>
			<div class="radio-list">
			<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-1" class="inline"> <input type="radio" id="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-1" name="settings[submission_redirection][connection_1][conditionals][rules][<?php echo $key; ?>][settings][redirect_to]" value="same" class="widefat confirmation-redirect-to" <?php echo checked( 'same', $confirmation_type, false ); ?> ><?php echo __( 'Same Page', 'everest-forms' ); ?></label>
			<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-2" class="inline"> <input type="radio" id="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-2" name="settings[submission_redirection][connection_1][conditionals][rules][<?php echo $key; ?>][settings][redirect_to]" value="custom_page" class="widefat confirmation-redirect-to" <?php echo checked( 'custom_page', $confirmation_type, false ); ?> ><?php echo __( 'Redirect to Custom Page', 'everest-forms' ); ?></label>
			<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-3" class="inline"><input type="radio" id="everest-forms-panel-field-<?php echo $key; ?>-settings-redirect_to-3" name="settings[submission_redirection][connection_1][conditionals][rules][<?php echo $key; ?>][settings][redirect_to]" value="external_url" class="widefat confirmation-redirect-to" <?php echo checked( 'external_url', $confirmation_type, false ); ?> ><?php echo __( 'Redirect to External URL', 'everest-forms' ); ?></label>
			</div>

		</div>
		<?php

		everest_forms_panel_field(
			'select',
			$key,
			'custom_page',
			$form_data,
			esc_html__( 'Custom Page', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'class'      => 'custom-page-setting',
				'default'    => '0',
				'options'    => self::evf_get_all_pages(),
			)
		);

		everest_forms_panel_field(
			'text',
			$key,
			'external_url',
			$form_data,
			esc_html__( 'External URL', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'class'      => 'external-page-setting',
				'default'    => isset( $form_data->external_url ) ? $form_data->external_url : '',
			)
		);
		everest_forms_panel_field(
			'toggle',
			$key,
			'enable_redirect_in_new_tab',
			$form_data,
			esc_html__( ' Open in new tab', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'class'      => 'external-page-setting',
				'tooltip'    => esc_html__( 'Enable to open the url in the new tab.', 'everest-forms' ),
				'default'    => '0',
			)
		);
		everest_forms_panel_field(
			'toggle',
			$key,
			'enable_redirect_query_string',
			$form_data,
			esc_html__( ' Append Query String', 'everest-forms' ),
			array(
				'parent'      => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection'  => 'settings',
				'class'       => 'custom-and-external-page-setting',
				'input_class' => 'append-query-string-input',
				'tooltip'     => esc_html__( 'Enable to add the query string in the url.', 'everest-forms' ),
				'default'     => '0',
			)
		);

		everest_forms_panel_field(
			'text',
			$key,
			'query_string',
			$form_data,
			esc_html__( 'Query String', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'default'    => isset( $settings['query_string'] ) ? $settings['query_string'] : '',
				'class'      => 'custom-and-external-page-setting query-string-wrap',
				'smarttags'  => array(
					'type'        => 'all',
					'form_fields' => 'all',
				),
				'after'      => '<p class="desc">' . sprintf( esc_html__( 'Example: firstname= {field_id="name_ys0GeZISRs-1"}&email={field_id="email_LbH5NxasXM-2"}', 'everest-forms' ) ) . '</p>',
			)
		);

		everest_forms_panel_field(
			'textarea',
			$key,
			'successful_form_submission_message',
			$form_data,
			esc_html__( 'Success Message', 'everest-forms' ),
			array(
				'parent'      => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection'  => 'settings',
				'class'       => 'same-page-setting',
				'input_class' => 'short',
				'default'     => isset( $form_data->successful_form_submission_message ) ? $form_data->successful_form_submission_message : __( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms' ),
				/* translators: %1$s - general settings docs url */
				'tooltip'     => sprintf( esc_html__( 'Success message that shows up after submitting form. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#4-toc-title' ) ),
			)
		);

		everest_forms_panel_field(
			'toggle',
			$key,
			'submission_message_scroll',
			$form_data,
			__( 'Auto scroll to Submission Message', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'class'      => 'same-page-setting',
				'default'    => '1',
			)
		);

		$form_state_type = isset( $settings['form_state_type'] ) ? $settings['form_state_type'] : 'hide';

		?>
		<div id="everest-forms-panel-field-<?php echo $key; ?>-settings-form_state_type-wrap" class="everest-forms-panel-field same-page-setting evf-builder-radio  everest-forms-panel-field-radio">
			<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-form_state_type"><?php echo __( 'After Submission Form Behavior', 'everest-forms' ); ?><i title="<?php echo __( 'Choose the submission form behavior.', 'everest-forms' ); ?>" class="dashicons dashicons-editor-help everest-forms-help-tooltip tooltipstered"></i></label>
			<div class="radio-list">
				<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-form_state_type-1" class="inline"><input type="radio" id="everest-forms-panel-field-<?php echo $key; ?>-settings-form_state_type-1" name="settings[submission_redirection][connection_1][conditionals][rules][<?php echo $key; ?>][settings][form_state_type]" value="reset" class="widefat form-state-type" <?php echo checked( 'reset', $form_state_type, false ); ?> ><?php echo __( 'Reset Form', 'everest-forms' ); ?></label>
				<label for="everest-forms-panel-field-<?php echo $key; ?>-settings-form_state_type-2" class="inline"><input type="radio" id="everest-forms-panel-field-<?php echo $key; ?>-settings-form_state_type-2" name="settings[submission_redirection][connection_1][conditionals][rules][<?php echo $key; ?>][settings][form_state_type]" value="hide" class="widefat form-state-type" <?php echo checked( 'hide', $form_state_type, false ); ?> ><?php echo __( 'Hide Form', 'everest-forms' ); ?></label>
			</div>
		</div>
		<?php

		everest_forms_panel_field(
			'toggle',
			$key,
			'preview_confirmation',
			$form_data,
			esc_html__( 'Show User Submitted Form Summary After Submission', 'everest-forms' ),
			array(
				'parent'      => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection'  => 'settings',
				'class'       => 'same-page-setting preview-confirmation-toggle-wrapper',
				'input_class' => 'everest-preview-confirmation',
				'tooltip'     => esc_html__( 'Show entry preview after form submission', 'everest-forms' ),
			)
		);

		everest_forms_panel_field(
			'select',
			$key,
			'preview_confirmation_select',
			$form_data,
			esc_html__( 'Preview type', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'class'      => 'same-page-setting preview-confirm-select-wrapper',
				'default'    => 'basic',
				'tooltip'    => esc_html__( 'Choose preview style type.', 'everest-forms' ),
				'options'    => array(
					'basic'   => esc_html__( 'Basic', 'everest-forms' ),
					'table'   => esc_html__( 'Table', 'everest-forms' ),
					'compact' => esc_html__( 'Compact', 'everest-forms' ),
				),
			)
		);
		// message display type based on the form state type.
		$option = array(
			'hide'  => esc_html__( 'Shown Message in Place of Form', 'everest-forms' ),
			'popup' => esc_html__( 'As Popup', 'everest-forms' ),
		);

		$is_preview = isset( $settings['preview_confirmation'] ) ? $settings['preview_confirmation'] : false;

		if ( $is_preview ) {
			$option = array(
				'top'    => esc_html__( 'Above Form Summary', 'everest-forms' ),
				'bottom' => esc_html( 'Below Form Summary', 'everest-forms' ),
				'popup'  => esc_html__( 'As Popup', 'everest-forms' ),
			);
		}
		everest_forms_panel_field(
			'select',
			$key,
			'message_display_location_of_hide',
			$form_data,
			esc_html__( 'Display Message', 'everest-forms' ),
			array(
				'parent'      => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'input_class' => 'message-display-location-of-hide',
				'subsection'  => 'settings',
				'class'       => 'same-page-setting form-state-hide',
				'default'     => 'hide',
				/* translators: %1$s - general settings docs url */
				'tooltip'     => sprintf( esc_html__( 'Choose where to display success message. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/confirmations/' ) ),
				'options'     => $option,
			)
		);

		everest_forms_panel_field(
			'select',
			$key,
			'message_display_location_of_reset',
			$form_data,
			esc_html__( 'Display Message', 'everest-forms' ),
			array(
				'parent'     => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
				'subsection' => 'settings',
				'class'      => 'same-page-setting form-state-reset',
				'default'    => 'hide',
				/* translators: %1$s - general settings docs url */
				'tooltip'    => sprintf( esc_html__( 'Choose where to display success message. <a href="%s" target="_blank">Learn More</a>', 'everest-forms' ), esc_url( 'https://docs.everestforms.net/docs/confirmations/' ) ),
				'options'    => array(
					'top'    => esc_html__( 'Above the form', 'everest-forms' ),
					'bottom' => esc_html__( 'Below the form', 'everest-forms' ),
					'popup'  => esc_html__( 'As Popup', 'everest-forms' ),
				),
			)
		);

		echo '</div>';
		// echo '</div>';

		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Form setting for admin and user
	 *
	 * @param mixed $part Settings.
	 * @param mixed $panel Panel.
	 * @return void
	 */
	public function conditional_logic_multipart_part( $part, $panel ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0; //phpcs:ignore
		if ( empty( $form_id ) ) {
			$form_id   = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : 0; //phpcs:ignore
		}
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'settings',
				'panel'         => $panel,
				'connection_id' => 'connection_' . $part['id'],
			)
		);
	}

	/**
	 * Form setting for admin and user
	 *
	 * @param mixed $row Settings.
	 * @param mixed $panel Panel.
	 * @return void
	 */
	public function conditional_logic_rows( $row, $panel ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'settings',
				'panel'         => $panel,
				'connection_id' => 'connection_' . $row,
			)
		);
	}


	/**
	 * Form setting for admin and user
	 *
	 * @param mixed $setting Settings.
	 * @param mixed $connection_id ConnectionID.
	 * @return void
	 */
	public function conditional_logic_email_setting( $setting, $connection_id ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'settings',
				'panel'         => 'email',
				'connection_id' => $connection_id,
			)
		);
	}

	/**
	 * Form setting for admin and user
	 *
	 * @param  mixed $setting Settings.
	 * @param  mixed $gateway Gateway.
	 * @param mixed $connection_id ConnectionID.
	 * @return void
	 */
	public function conditional_logic_payment_setting( $setting, $gateway, $connection_id ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'payments',
				'panel'         => $gateway,
				'connection_id' => $connection_id,
			)
		);
	}

	/**
	 * Form setting for admin and user
	 *
	 * @param mixed $setting Settings.
	 * @param mixed $connection_id ConnectionID.
	 * @return void
	 */
	public function conditional_logic_sms_notifications_setting( $setting, $connection_id ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ( ! empty( $form_obj->post_content ) ) ? evf_decode( $form_obj->post_content ) : '';
		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'settings',
				'panel'         => 'sms_notifications',
				'connection_id' => $connection_id,
			)
		);
	}

	/**
	 * Form setting for admin and user.
	 *
	 * @param  mixed $setting Setting.
	 * @param mixed $panel PanelName.
	 * @param mixed $connection_id ConnectionID.
	 * @return void
	 */
	public function conditional_logic_submit_setting( $setting, $panel, $connection_id ) {
		$form_id   = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0;
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		$this->conditional_block(
			array(
				'form'          => $form_data,
				'type'          => 'settings',
				'panel'         => $panel,
				'connection_id' => $connection_id,
			)
		);
	}

	/**
	 * Conditional_logic_email_process.
	 *
	 * @param mixed $process Process.
	 * @param mixed $fields Fields.
	 * @param mixed $form_data FormsData.
	 * @param mixed $context Context.
	 * @param mixed $connection_id ConnectionID.
	 */
	public function conditional_logic_email_process( $process, $fields, $form_data, $context, $connection_id ) {
		$email_setting = isset( $form_data['settings']['email'][ $connection_id ] ) ? $form_data['settings']['email'][ $connection_id ] : array();

		if ( ! isset( $email_setting['conditional_logic_status'] ) || '1' !== $email_setting['conditional_logic_status'] ) {
			return $process;
		}

		$type         = isset( $email_setting['conditional_option'] ) ? $email_setting['conditional_option'] : '';
		$conditionals = isset( $email_setting['conditionals'] ) ? $email_setting['conditionals'] : array();
		$pass         = false;

		foreach ( $conditionals as $group_id => $group ) {
			$pass_group = true;

			foreach ( $group as $rule_id => $rule ) {

				$rule_field    = isset( $rule['field'] ) ? $rule['field'] : '';
				$rule_operator = isset( $rule['operator'] ) ? $rule['operator'] : '';
				$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';

				if ( ! empty( $rule_field ) && 'payment' === $rule_field ) {
					return false;
				}

				if ( empty( $rule_field ) || ! isset( $fields[ $rule_field ]['type'] ) ) {
					continue;
				}

				if ( isset( $fields[ $rule_field ]['type'] ) && in_array( $fields[ $rule_field ]['type'], array( 'text', 'first-name', 'last-name', 'textarea', 'email', 'url', 'number', 'hidden', 'country', 'date-time' ), true ) ) {
						$right = trim( strtolower( $rule_value ) );

					if ( 'country' === $fields[ $rule_field ]['type'] ) {
							$left = trim( strtolower( $fields[ $rule_field ]['value']['country_code'] ) );
					} else {
							$left = trim( strtolower( $fields[ $rule_field ]['value'] ) );
					}

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = ( $left == $right );
							break;
						case 'is_not':
							$pass_rule = ( $left != $right );
							break;
						case 'empty':
							$pass_rule = ( '' == $left );
							break;
						case 'not_empty':
							$pass_rule = ( '' != $left );
							break;
						case 'greater_than':
							if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
								$pass_rule = strtotime( $left ) > strtotime( $right );
							} else {
								$left      = preg_replace( '/[^0-9.]/', '', $left );
								$pass_rule = ( '' !== $left ) && ( floatval( $left ) > floatval( $right ) );
							}
							break;

						case 'less_than':
							if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
								$pass_rule = strtotime( $left ) < strtotime( $right );
							} else {
								$left      = preg_replace( '/[^0-9.]/', '', $left );
								$pass_rule = ( '' !== $left ) && ( floatval( $left ) < floatval( $right ) );
							}
							break;
						case 'between':
							if ( $left === $right ) {
								$pass_rule = true;
							} elseif ( ! empty( $left ) && ! empty( $right ) ) {
								$date_range = explode( ' to ', $right );

								if ( count( $date_range ) === 2 ) {
									$start_date = strtotime( $date_range[0] );
									$end_date   = strtotime( $date_range[1] );

									if ( strpos( $left, ' to ' ) !== false ) {
										$left_range = explode( ' to ', $left );
										if ( count( $left_range ) === 2 ) {
											$left_start_date = strtotime( $left_range[0] );
											$left_end_date   = strtotime( $left_range[1] );

											$pass_rule = ( $left_start_date >= $start_date && $left_end_date <= $end_date );
										} else {
											$pass_rule = false;
										}
									} else {
										$left_date = strtotime( $left );
										$pass_rule = ( $left_date >= $start_date && $left_date <= $end_date );
									}
								} else {
									$pass_rule = false;
								}
							} else {
								$pass_rule = false;
							}
							break;

						case 'multiple':
							if ( $left === $right ) {
								$pass_rule = true;
							} else {
								$right_dates    = explode( ', ', $right );
								$selected_dates = explode( ', ', $left );

								$allMatch = true;
								foreach ( $selected_dates as $date ) {
									if ( ! in_array( $date, $right_dates ) ) {
										$allMatch = false;
										break;
									}
								}

								if ( $allMatch && count( $selected_dates ) === count( $right_dates ) ) {
									$pass_rule = true;
								} else {
									$pass_rule = false;
								}
							}
							break;
					}
				} else {
					$is_val_array = false;
					if ( in_array( $fields[ $rule_field ]['type'], array( 'checkbox' ), true ) ) {
						$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
						if ( is_array( $values ) ) {
							$is_val_array = true;
							$values       = implode( ',', $values );

						}
					} else {
						$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
					}

					if ( ! isset( $fields[ $rule_field ]['value_raw'] ) ) {
						$provided_id = array();
						foreach ( $form_data['form_fields'][ $rule_field ]['choices'] as $key => $choice ) {
							$choice = array_map( 'sanitize_text_field', $choice );
							foreach ( $values as $value ) {
								$value = evf_decode_string( $value );
								if ( in_array( $value, $choice, true ) ) {
									$provided_id[] = $value;
								}
							}
						}
					}
					if ( isset( $fields[ $rule_field ]['value_raw'], $fields[ $rule_field ]['type'], $fields[ $rule_field ]['value']['label'] ) && 'radio' === $fields[ $rule_field ]['type'] && $fields[ $rule_field ]['value_raw'] !== $fields[ $rule_field ]['value']['label'] ) {
						// Handles the auto-populate value in multiple choice option.
						$left = array( $fields[ $rule_field ]['value']['label'] );
					} elseif ( ! isset( $fields[ $rule_field ]['value_raw'] ) && ! empty( $provided_id ) ) {
						$left = (array) $provided_id;
					} else {
						$left = true === $is_val_array ? explode( ',', $values ) : (array) $values;
					}
					$right = trim( $rule_value );

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = in_array( $right, $left );
							break;
						case 'is_not':
							$pass_rule = ! in_array( $right, $left );
							break;
						case 'empty':
							$pass_rule = ( false === $left[0] );
							break;
						case 'not_empty':
							$pass_rule = ( false !== $left[0] );
							break;
					}
				}

				if ( ! $pass_rule ) {
					$pass_group = false;
					break;
				}
			}

			if ( $pass_group ) {
				$pass = true;
			}
		}

		if ( 'not_send' === $type ) {
			$pass = ! $pass;
		}

		return $pass;
	}

	/**
	 * Conditional logic process for payment.
	 *
	 * @param array  $process Process.
	 * @param array  $fields Fields.
	 * @param array  $form_data Form Data.
	 * @param array  $gateway Gateway.
	 * @param string $connection_id ConnectionID.
	 */
	public function conditional_logic_payment_process( $process, $fields, $form_data, $gateway, $connection_id ) {
		$payment_setting = isset( $form_data['payments'][ $gateway ][ $connection_id ] ) ? $form_data['payments'][ $gateway ][ $connection_id ] : array();

		if ( ! isset( $payment_setting['conditional_logic_status'] ) || '1' !== $payment_setting['conditional_logic_status'] ) {
			return $process;
		}

		$type         = isset( $payment_setting['conditional_option'] ) ? $payment_setting['conditional_option'] : '';
		$conditionals = isset( $payment_setting['conditionals'] ) ? $payment_setting['conditionals'] : array();
		$pass         = false;

		foreach ( $conditionals as $group_id => $group ) {
			$pass_group = true;

			foreach ( $group as $rule_id => $rule ) {

				$rule_field    = $rule['field'];
				$rule_operator = $rule['operator'];
				$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';

				if ( empty( $rule_field ) || ! isset( $fields[ $rule_field ]['type'] ) ) {
					continue;
				}

				if ( 'payment-gateway-selector' === $fields[ $rule_field ]['type'] ) {
					$left  = isset( $fields[ $rule_field ]['value_raw'] ) ? trim( strtolower( $fields[ $rule_field ]['value_raw'] ) ) : '';
					$right = trim( strtolower( $rule_value ) );

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = ( $left === $right );
							break;
						case 'is_not':
							$pass_rule = ( $left !== $right );
							break;
						case 'not_empty':
							$pass_rule = ( '' !== $left );
							break;
						case 'empty':
							$pass_rule = ( '' === $left );
							break;
						default:
							$pass_rule = false;
							break;
					}
				} elseif ( in_array( $fields[ $rule_field ]['type'], array( 'text', 'first-name', 'last-name', 'textarea', 'email', 'url', 'number', 'hidden', 'date-time' ), true ) ) {

					$left  = trim( strtolower( $fields[ $rule_field ]['value'] ) );
					$right = trim( strtolower( $rule_value ) );

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = ( $left == $right );
							break;
						case 'is_not':
							$pass_rule = ( $left != $right );
							break;
						case 'empty':
							$pass_rule = ( '' == $left );
							break;
						case 'not_empty':
							$pass_rule = ( '' != $left );
							break;
						case 'greater_than':
							if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
								$pass_rule = strtotime( $left ) > strtotime( $right );
							} else {
								$left      = preg_replace( '/[^0-9.]/', '', $left );
								$pass_rule = ( '' !== $left ) && ( floatval( $left ) > floatval( $right ) );
							}
							break;

						case 'less_than':
							if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
								$pass_rule = strtotime( $left ) < strtotime( $right );
							} else {
								$left      = preg_replace( '/[^0-9.]/', '', $left );
								$pass_rule = ( '' !== $left ) && ( floatval( $left ) < floatval( $right ) );
							}
							break;
						case 'between':
							if ( $left === $right ) {
								$pass_rule = true;
							} elseif ( ! empty( $left ) && ! empty( $right ) ) {
								if ( is_string( $right ) ) {
									$date_range = explode( ' to ', $right );

									if ( count( $date_range ) === 2 ) {
										$start_date = strtotime( $date_range[0] );
										$end_date   = strtotime( $date_range[1] );

										if ( strpos( $left, ' to ' ) !== false ) {
											$left_range = explode( ' to ', $left );
											if ( count( $left_range ) === 2 ) {
												$left_start_date = strtotime( $left_range[0] );
												$left_end_date   = strtotime( $left_range[1] );

												$pass_rule = ( $left_start_date >= $start_date && $left_end_date <= $end_date );
											} else {
												$pass_rule = false;
											}
										} else {
											$left_date = strtotime( $left );
											$pass_rule = ( $left_date >= $start_date && $left_date <= $end_date );
										}
									}
								} else {
									$pass_rule = false;
								}
							} else {
								$pass_rule = false;
							}
							break;

						case 'multiple':
							if ( $left === $right ) {
								$pass_rule = true;
							} else {
								$right_dates    = explode( ', ', $right );
								$selected_dates = explode( ', ', $left );

								$allMatch = true;
								foreach ( $selected_dates as $date ) {
									if ( ! in_array( $date, $right_dates ) ) {
										$allMatch = false;
										break;
									}
								}

								if ( $allMatch && count( $selected_dates ) === count( $right_dates ) ) {
									$pass_rule = true;
								} else {
									$pass_rule = false;
								}
							}
							break;
					}
				} else {
					$is_val_array = false;
					if ( in_array( $fields[ $rule_field ]['type'], array( 'checkbox' ), true ) ) {
						$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
						if ( is_array( $values ) ) {
							$is_val_array = true;
							$values       = implode( ',', $values );
						}
					} else {
						$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
					}

					if ( ! isset( $fields[ $rule_field ]['value_raw'] ) ) {
						$provided_id = array();
						foreach ( $form_data['form_fields'][ $rule_field ]['choices'] as $key => $choice ) {
							$choice = array_map( 'sanitize_text_field', $choice );
							foreach ( $values as $value ) {
								$value = evf_decode_string( $value );
								if ( in_array( $value, $choice, true ) ) {
									$provided_id[] = $value;
								}
							}
						}
					}
					if ( ! isset( $fields[ $rule_field ]['value_raw'] ) && ! empty( $provided_id ) ) {
						$left = (array) $provided_id;
					} elseif ( true === $is_val_array ) {
						$left = explode( ',', $values );
					} elseif ( is_array( $values ) ) {
						$left = $values;
					} else {
						$left = explode( ',', (string) $values );
					}
					$right = trim( $rule_value );

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = in_array( $right, $left );
							break;
						case 'is_not':
							$pass_rule = ! in_array( $right, $left );
							break;
						case 'empty':
							$pass_rule = ( false === $left[0] );
							break;
						case 'not_empty':
							$pass_rule = ( false !== $left[0] );
							break;
					}
				}

				if ( ! $pass_rule ) {
					$pass_group = false;
					break;
				}
			}

			if ( $pass_group ) {
				$pass = true;
			}
		}

		if ( 'not_send' === $type ) {
			$pass = ! $pass;
		}

		return $pass;
	}

	/**
	 * Conditional logic process for twilio.
	 *
	 * @param array  $process Process.
	 * @param array  $fields Fields.
	 * @param array  $form_data Form Data.
	 * @param array  $gateway Gateway.
	 * @param string $connection_id ConnectionID.
	 */
	public function conditional_logic_sms_notifications_process( $process, $fields, $form_data, $gateway, $connection_id ) {
		$twilio_setting = isset( $form_data['settings'][ $gateway ][ $connection_id ] ) ? $form_data['settings'][ $gateway ][ $connection_id ] : array();
		if ( ! isset( $twilio_setting['conditional_logic_status'] ) || '1' !== $twilio_setting['conditional_logic_status'] ) {
			return $process;
		}

		$type         = isset( $twilio_setting['conditional_option'] ) ? $twilio_setting['conditional_option'] : '';
		$conditionals = isset( $twilio_setting['conditionals'] ) ? $twilio_setting['conditionals'] : array();
		$pass         = false;

		foreach ( $conditionals as $group_id => $group ) {
			$pass_group = true;

			foreach ( $group as $rule_id => $rule ) {

				$rule_field    = $rule['field'];
				$rule_operator = $rule['operator'];
				$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';

				if ( empty( $rule_field ) || ! isset( $fields[ $rule_field ]['type'] ) ) {
					continue;
				}

				if ( in_array( $fields[ $rule_field ]['type'], array( 'text', 'first-name', 'last-name', 'textarea', 'email', 'url', 'number', 'hidden', 'date-time' ), true ) ) {

					$left  = trim( strtolower( $fields[ $rule_field ]['value'] ) );
					$right = trim( strtolower( $rule_value ) );

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = ( $left == $right );
							break;
						case 'is_not':
							$pass_rule = ( $left != $right );
							break;
						case 'empty':
							$pass_rule = ( '' == $left );
							break;
						case 'not_empty':
							$pass_rule = ( '' != $left );
							break;
						case 'greater_than':
							if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
								$pass_rule = strtotime( $left ) > strtotime( $right );
							} else {
								$left      = preg_replace( '/[^0-9.]/', '', $left );
								$pass_rule = ( '' !== $left ) && ( floatval( $left ) > floatval( $right ) );
							}
							break;

						case 'less_than':
							if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
								$pass_rule = strtotime( $left ) < strtotime( $right );
							} else {
								$left      = preg_replace( '/[^0-9.]/', '', $left );
								$pass_rule = ( '' !== $left ) && ( floatval( $left ) < floatval( $right ) );
							}
							break;
						case 'between':
							if ( $left === $right ) {
								$pass_rule = true;
							} elseif ( ! empty( $left ) && ! empty( $right ) ) {
								$date_range = explode( ' to ', $right );

								if ( count( $date_range ) === 2 ) {
									$start_date = strtotime( $date_range[0] );
									$end_date   = strtotime( $date_range[1] );
									if ( strpos( $left, ' to ' ) !== false ) {
										$left_range = explode( ' to ', $left );
										if ( count( $left_range ) === 2 ) {
											$left_start_date = strtotime( $left_range[0] );
											$left_end_date   = strtotime( $left_range[1] );
											$pass_rule       = ( $left_start_date >= $start_date && $left_end_date <= $end_date );
										} else {
											$pass_rule = false;
										}
									} else {
										$left_date = strtotime( $left );
										$pass_rule = ( $left_date >= $start_date && $left_date <= $end_date );
									}
								} else {
									$pass_rule = false;
								}
							} else {
								$pass_rule = false;
							}
							break;

						case 'multiple':
							if ( $left === $right ) {
								$pass_rule = true;
							} else {
								$right_dates    = explode( ', ', $right );
								$selected_dates = explode( ', ', $left );

								$allMatch = true;
								foreach ( $selected_dates as $date ) {
									if ( ! in_array( $date, $right_dates ) ) {
										$allMatch = false;
										break;
									}
								}

								if ( $allMatch && count( $selected_dates ) === count( $right_dates ) ) {
									$pass_rule = true;
								} else {
									$pass_rule = false;
								}
							}
							break;
					}
				} else {
					$is_val_array = false;
					if ( in_array( $fields[ $rule_field ]['type'], array( 'checkbox' ), true ) ) {
						$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
						if ( is_array( $values ) ) {
							$is_val_array = true;
							$values       = implode( ',', $values );
						}
					} else {
						$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
					}

					if ( ! isset( $fields[ $rule_field ]['value_raw'] ) ) {
						$provided_id = array();
						if ( isset( $form_data['form_fields'][ $rule_field ]['choices'] ) && is_array( $form_data['form_fields'][ $rule_field ]['choices'] ) ) {
							foreach ( $form_data['form_fields'][ $rule_field ]['choices'] as $key => $choice ) {
								$choice = array_map( 'sanitize_text_field', $choice );
								foreach ( $values as $value ) {
									$value = evf_decode_string( $value );
									if ( in_array( $value, $choice, true ) ) {
										$provided_id[] = $value;
									}
								}
							}
						}
					}

					if ( ! isset( $fields[ $rule_field ]['value_raw'] ) && ! empty( $provided_id ) ) {
						$left = (array) $provided_id;
					} elseif ( true === $is_val_array ) {
						$left = explode( ',', $values );
					} elseif ( is_array( $values ) ) {
						$left = $values;
					} else {
						$left = explode( ',', (string) $values );
					}
					$right = trim( $rule_value );

					switch ( $rule_operator ) {
						case 'is':
							$pass_rule = in_array( $right, $left );
							break;
						case 'is_not':
							$pass_rule = ! in_array( $right, $left );
							break;
						case 'empty':
							$pass_rule = ( false === $left[0] );
							break;
						case 'not_empty':
							$pass_rule = ( false !== $left[0] );
							break;
					}
				}

				if ( ! $pass_rule ) {
					$pass_group = false;
					break;
				}
			}

			if ( $pass_group ) {
				$pass = true;
			}
		}

		if ( 'not_send' === $type ) {
			$pass = ! $pass;
		}

		return $pass;
	}


	/**
	 * Conditional_block.
	 *
	 * @param array $args Args.
	 * @return void
	 */
	public static function conditional_block( $args = array() ) {

		if ( ! empty( $args['form'] ) ) {
			$form_fields = evf_get_form_fields( $args['form'], array( 'text', 'textarea', 'select', 'radio', 'email', 'url', 'checkbox', 'number', 'payment-multiple', 'payment-single', 'hidden' ) );
		} else {
			$form_fields = array();
		}

		$type  = ! empty( $args['type'] ) ? $args['type'] : 'field';
		$panel = ! empty( $args['panel'] ) ? $args['panel'] : false;
		$field = ! empty( $args['field'] ) ? $args['field'] : array();

		// Check if form fields has no support for conditional logic.
		$disable_conditional_fields = apply_filters( 'everest_forms_disable_conditional_fields', array( 'hidden' ) );
		$field_to_be_restricted     = apply_filters(
			'everest_forms_restricted_conditional_fields',
			array(
				'html',
				'title',
				'address',
				'image-upload',
				'file-upload',
				'hidden',
				'scale-rating',
				'likert',
				'signature',
			)
		);

		if ( isset( $field['type'] ) && in_array( $field['type'], $disable_conditional_fields, true ) ) {
			return;
		}

		$form_id = isset( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $form_id ) ) {
			$form_id   = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : 0; //phpcs:ignore
		}
		$form_obj  = EVF()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? evf_decode( $form_obj->post_content ) : '';

		if ( ! function_exists( 'evf_form_confirmation_backward_compatibility' ) ) {
			return;
		}

		$form_data          = evf_form_confirmation_backward_compatibility( $form_data );
		$conditional_fields = array();

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $all_field ) {
				if ( ! in_array( $all_field['type'], $field_to_be_restricted, true ) ) {
					if ( isset( $field['id'] ) && $field['id'] === $all_field['id'] ) {
						continue;
					}

					$conditional_fields[] = $all_field;
				}
			}
		}

		if ( 'field' === $type ) {
			$f_id               = str_replace( '-', '_', $field['id'] );
			$conditional_option = isset( $field['conditional_option'] ) ? $field['conditional_option'] : '';
			$conditionals       = ! empty( $field['conditionals'] ) ? $field['conditionals'] : array();
			$l10n               = wp_json_encode(
				array(
					'conditional_option' => $conditional_option,
					'conditionals'       => $conditionals,
				)
			);

			wp_add_inline_script(
				'everest-forms-conditionals-scripts',
				sprintf( 'var evf_field_integration_data_%1$s = %2$s;', $f_id, html_entity_decode( wp_json_encode( $l10n ), ENT_QUOTES, 'UTF-8' ) ),
				'before'
			);

			$instance = $args['instance'];
			$value    = isset( $field['conditional_logic_status'] ) ? $field['conditional_logic_status'] : '0';
			$tooltip  = __( 'Check this option to enable condition logic.', 'everest-forms-pro' );

			// Build output.
			$output = $instance->field_element(
				'toggle',
				$field,
				array(
					'slug'    => 'conditional_logic_status',
					'value'   => $value,
					'desc'    => __( 'Enable Conditional Logic', 'everest-forms-pro' ),
					'tooltip' => $tooltip,
					'data'    => array( 'panel-source' => $type ),
				),
				false
			);
			$output = $instance->field_element(
				'row',
				$field,
				array(
					'slug'    => 'conditional_logic_status',
					'class'   => 'evf_conditional_logic_container',
					'content' => $output,
				),
				false
			);

			$output     .= '<div class="evf-field-conditional-container">';
			$output     .= '<h4>' . __( 'Conditional Rules', 'everest-forms-pro' ) . '</h4>';
				$output .= '<div class="evf-field-logic">';
				$output .= sprintf( '<select class="evf-field-show-hide" name="form_fields[%s][conditional_option]">', $field['id'] );
				$output .= '<option value="show"  ' . selected( $conditional_option, 'show', false ) . '>Show</option>';
				$output .= '<option value="hide" ' . selected( $conditional_option, 'hide', false ) . '>Hide</option>';
				$output .= '</select>';
				$output .= '<p> only if following matches.</p>';
				$output .= '</div>';

			if ( $conditionals ) {
				foreach ( $conditionals as $group_id => $conditions ) {
					$output .= '<ul class="evf-field-conditional-wrapper" data-group=' . $group_id . ' data-field-id=' . $field['id'] . '>';

					foreach ( $conditions as $key => $condition ) {
						$output .= '<li class="evf-conditional-group" data-key="' . $key . '">';
						$output .= '<div class="evf-form-group">';
						$output .= sprintf( '<select class="evf-field-conditional-field-select" data-panel-source = ' . $type . ' name="form_fields[%s][conditionals][' . $group_id . '][' . $key . '][field]"><option>---Select Field---</option>', $field['id'] );

						if ( ! empty( $conditional_fields ) ) {
							foreach ( $conditional_fields as $form_fields ) {
								if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
									$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
								}
							}
						}

						$output .= '</select>';
						$output .= sprintf( '<select class="evf-field-conditional-condition" name="form_fields[%s][conditionals][' . $group_id . '][' . $key . '][operator]">', $field['id'] );
						$output .= '<option value = "is"  ' . selected( $condition['operator'], 'is', false ) . '> is </option>';
						$output .= '<option value = "is_not" ' . selected( $condition['operator'], 'is_not', false ) . '> is not </option>';
						$output .= '<option value = "empty" ' . selected( $condition['operator'], 'empty', false ) . '> empty </option>';
						$output .= '<option value = "not_empty" ' . selected( $condition['operator'], 'not_empty', false ) . '> not empty </option>';
						$output .= '<option value = "greater_than" ' . selected( $condition['operator'], 'greater_than', false ) . '> greater than </option>';
						$output .= '<option value = "less_than" ' . selected( $condition['operator'], 'less_than', false ) . '> less than </option>';
						$output .= '<option value = "between" ' . selected( $condition['operator'], 'between', false ) . ' class="everest-forms-hidden"> between </option>';
						$output .= '<option value = "multiple" ' . selected( $condition['operator'], 'multiple', false ) . ' class="everest-forms-hidden"> multiple </option>';
						$output .= '</select>';
						$output .= '</div class="evf-form-group">';
						$output .= '<a class="conditonal-rule-add" href="#">AND</a>';
						$output .= '<a class="conditonal-rule-remove" href="#"><i class="dashicons dashicons-minus"></i></a>';
						$output .= '</li>';
					}

					$output .= '<span class="conditional_or">OR</span>';
					$output .= '</ul>';
				}
			} else {
				$output .= '<ul class="evf-field-conditional-wrapper" data-group="1" data-field-id =' . $field['id'] . '>';
				$output .= '<li class="evf-conditional-group" data-key="1">';
				$output .= '<div class="evf-form-group">';
				$output .= sprintf( '<select class="evf-field-conditional-field-select" data-panel-source = ' . $type . ' name="form_fields[%s][conditionals][1][1][field]"><option>---Select Field---</option>', $field['id'] );
				if ( ! empty( $conditional_fields ) ) {
					foreach ( $conditional_fields as $form_fields ) {
						if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
							$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
						}
					}
				}
					$output .= '</select>';
					$output .= sprintf( '<select class="evf-field-conditional-condition" name="form_fields[%s][conditionals][1][1][operator]">', $field['id'] );
					$output .= '<option value = "is"> is </option>';
					$output .= '<option value = "is_not"> is not </option>';
					$output .= '<option value = "empty"> empty </option>';
					$output .= '<option value = "not_empty"> not empty </option>';
					$output .= '<option value = "greater_than"> greater than </option>';
					$output .= '<option value = "less_than"> less than </option>';
					$output .= '<option value = "between" class="everest-forms-hidden"> between </option>';
					$output .= '<option value = "multiple" class="everest-forms-hidden"> multiple </option>';
					$output .= '</select>';
					$output .= '<input class="evf-field-conditional-input" name="form_fields[' . $field['id'] . '][conditionals][1][1][value]" type="text" value="" />';
					$output .= '</div>';
					$output .= '<a class="conditonal-rule-add" href="#">AND</a>';
					$output .= '<a class="conditonal-rule-remove" href="#"><i class="dashicons dashicons-minus"></i></a>';
					$output .= '</li>';
					$output .= '<span class="conditional_or">OR</span>';
					$output .= '</ul>';
			}

			$output .= '<a class="button button-small conditonal-group-add" data-panel-source=' . $type . ' href="#">Add Conditional Group</a>';
			$output .= '</div>';

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( ! empty( $form_data ) && ( 'settings' === $type || 'payments' === $type ) ) {
			$con_id   = ! empty( $args['connection_id'] ) ? $args['connection_id'] : false;
			$settings = $form_data;

			if ( ! isset( $settings[ $type ][ $panel ][ $con_id ] ) ) {
				$settings[ $type ][ $panel ][ $con_id ] = array( 'connection_name' => __( 'Admin Notification', 'everest-forms-pro' ) );
				$email_settings                         = array( 'conditional_logic_status', 'conditional_option', 'conditionals' );
				foreach ( $email_settings as $email_setting ) {
					$settings[ $type ][ $panel ][ $con_id ][ $email_setting ] = isset( $settings[ $type ][ $panel ][ $email_setting ] ) ? $settings[ $type ][ $panel ][ $email_setting ] : '';
				}
			}

			foreach ( $settings[ $type ][ $panel ] as $connection_id => $data ) {
				if ( $connection_id === $con_id ) {
					$conditionals       = ! empty( $settings[ $type ][ $panel ][ $connection_id ]['conditionals'] ) ? $settings[ $type ][ $panel ][ $connection_id ]['conditionals'] : array();
					$conditional_option = isset( $settings[ $type ][ $panel ][ $connection_id ]['conditional_option'] ) ? $settings[ $type ][ $panel ][ $connection_id ]['conditional_option'] : 'send';
					$l10n               = wp_json_encode(
						array(
							'conditional_option' => $conditional_option,
							'conditionals'       => $conditionals,
						)
					);

					wp_add_inline_script(
						'everest-forms-conditionals-scripts',
						sprintf( 'var evf_%1$s_%2$s_conditional_data_%3$s = %4$s;', $type, $panel, $connection_id, html_entity_decode( wp_json_encode( $l10n ), ENT_QUOTES, 'UTF-8' ) ),
						'before'
					);

					$tooltip = __( 'Check this option to enable condition logic.', 'everest-forms-pro' );
					everest_forms_panel_field(
						'toggle',
						$panel,
						'conditional_logic_status',
						$form_data,
						sprintf( __( 'Enable Conditional Logic', 'everest-forms-pro' ) ),
						array(
							'default'    => isset( $settings[ $type ][ $panel ][ $connection_id ]['conditional_logic_status'] ) ? $settings[ $type ][ $panel ][ $connection_id ]['conditional_logic_status'] : 0,
							'tooltip'    => $tooltip,
							'class'      => 'evf_conditional_logic_container',
							'data'       => array( 'panel-source' => $panel ),
							'parent'     => $type,
							'subsection' => $connection_id,
						)
					);

					$output = '';

					if ( 'submission_redirection' !== $panel ) {
						$border  = 'multi_part_conditional' === $panel ? '' : 'everest-forms-border-container';
						$output  = '<div class="everest-forms-panel-field evf-field-conditional-container ' . $border . '" data-connection_id="' . $connection_id . '">';
						$output .= '<h4 class="everest-forms-border-container-title">' . __( 'Conditional Rules', 'everest-forms-pro' ) . '</h4>';
						if ( 'submit' === $panel || 'multi_part_conditional' === $panel || 'form_rows' === $panel ) {
							$output .= '<div class="evf-field-logic">';
							$output .= '<select class="evf-field-show-hide" name="' . $type . '[' . $panel . '][' . $connection_id . '][conditional_option]">';
							$output .= '<option value="show"  ' . selected( $conditional_option, 'show', false ) . '>' . __( 'Show', 'everest-forms-pro' ) . '</option>';
							$output .= '<option value="hide" ' . selected( $conditional_option, 'hide', false ) . '>' . __( 'Hide', 'everest-forms-pro' ) . '</option>';
							if ( 'submit' === $panel ) {
								$output .= '<option value="disable" ' . selected( $conditional_option, 'disable', false ) . '>' . __( 'Disable', 'everest-forms-pro' ) . '</option>';
							}
							$output .= '</select>';
							$output .= '<p> only if following matches.</p>';
							$output .= '</div>';
						} else {
							$output .= '<div class="evf-field-logic">';
							$output .= '<select class="evf-field-show-hide" name="' . $type . '[' . $panel . '][' . $connection_id . '][conditional_option]">';
							$output .= '<option value="send"  ' . selected( $conditional_option, 'send', false ) . '>Send</option>';
							$output .= '<option value="not_send" ' . selected( $conditional_option, 'not_send', false ) . '>Don\'t send</option>';
							$output .= '</select>';
							$output .= '<p> only if following matches.</p>';
							$output .= '</div>';
						}

						if ( $conditionals ) {
							foreach ( $conditionals as $group_id => $conditions ) {
								$output .= '<ul class="evf-field-conditional-wrapper" data-group=' . $group_id . '>';
								foreach ( $conditions as $key => $condition ) {
									$operator = isset( $condition['operator'] ) ? $condition['operator'] : 'is';
									$output  .= '<li class="evf-conditional-group" data-key="' . $key . '">';
									$output  .= '<div class="evf-form-group">';
									$output  .= '<select class="evf-field-conditional-field-select" data-source=' . $panel . ' data-panel-source = ' . $type . ' name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][' . $group_id . '][' . $key . '][field]">';
									if ( ! empty( $conditional_fields ) ) {
										if ( 'email' === $panel ) {
												$output .= '<optgroup label="Fields"><option>---Select Field---</option>';
											foreach ( $conditional_fields as $form_fields ) {
												if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
													$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
												}
											}
												$output .= '</optgroup>';
												$output .= '<optgroup label="Payments"><option data-field_type="payment" value="payment" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', 'payment', false ) . '>' . __( 'Payment', 'everest-forms-pro' ) . '</option></optgroup>';
										} else {
												$output .= '<option>---Select Field---</option>';
											foreach ( $conditional_fields as $form_fields ) {
												if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
													$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
												}
											}
										}
									}
									$output .= '</select>';
									$output .= '<select class="evf-field-conditional-condition" name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][' . $group_id . '][' . $key . '][operator]">';
									$output .= '<option value = "is"  ' . selected( $operator, 'is', false ) . '> is </option>';
									$output .= '<option value = "is_not" ' . selected( $operator, 'is_not', false ) . '> is not </option>';
									$output .= '<option value = "empty" ' . selected( $operator, 'empty', false ) . '> empty </option>';
									$output .= '<option value = "not_empty" ' . selected( $operator, 'not_empty', false ) . '> not empty </option>';
									$output .= '<option value = "greater_than" ' . selected( $operator, 'greater_than', false ) . '> greater than </option>';
									$output .= '<option value = "less_than" ' . selected( $operator, 'less_than', false ) . '> less than </option>';
									$output .= '<option class="everest-forms-hidden" value = "between" ' . selected( $operator, 'between', false ) . '> between </option>';
									$output .= '</select>';
									$output .= '</div>';
									$output .= '<a class="conditonal-rule-add" href="#">AND</a>';
									$output .= '<a class="conditonal-rule-remove" href="#"><i class="dashicons dashicons-minus"></i></a>';
									$output .= '</li>';
								}
								$output .= '<span class="conditional_or">OR</span>';
								$output .= '</ul>';
							}
						} else {
							$output .= '<ul class="evf-field-conditional-wrapper" data-group="1">';
							$output .= '<li class="evf-conditional-group" data-key="1">';
							$output .= '<div class="evf-form-group">';
							$output .= '<select class="evf-field-conditional-field-select" data-source=' . $panel . ' data-panel-source = ' . $type . ' name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][1][1][field]"><option>---Select Field---</option>';
							if ( ! empty( $conditional_fields ) ) {
								if ( 'email' === $panel ) {
									$output .= '<optgroup label="Fields"><option>---Select Field---</option>';
									foreach ( $conditional_fields as $form_fields ) {
										if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
											$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
										}
									}
									$output .= '</optgroup>';
									$output .= '<optgroup label="Payments"><option data-field_type="payment" value="payment" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', 'payment', false ) . '>' . __( 'Payment', 'everest-forms-pro' ) . '</option></optgroup>';
								} else {
									$output .= '<option>---Select Field---</option>';
									foreach ( $conditional_fields as $form_fields ) {
										if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
											$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
										}
									}
								}
							}
							$output .= '</select>';
							$output .= '<select class="evf-field-conditional-condition" name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][1][1][operator]">';
							$output .= '<option value = "is"> is </option>';
							$output .= '<option value = "is_not"> is not </option>';
							$output .= '<option value = "empty"> empty </option>';
							$output .= '<option value = "not_empty"> not empty </option>';
							$output .= '<option value = "greater_than"> greater than </option>';
							$output .= '<option value = "less_than"> less than </option>';
							$output .= '<option value = "between" class="everest-forms-hidden"> between </option>';
							$output .= '</select>';
							$output .= '<input class="evf-field-conditional-input" name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][1][1][value]" type="text" value="" />';
							$output .= '</div>';
							$output .= '<a class="conditonal-rule-add" href="#">AND</a>';
							$output .= '<a class="conditonal-rule-remove" href="#"><i class="dashicons dashicons-minus"></i></a>';
							$output .= '</li>';
							$output .= '<span class="conditional_or">OR</span>';
							$output .= '</ul>';
						}
						$output .= '<a class="button button-small conditonal-group-add" data-panel-source=' . $type . ' href="#">Add Conditional Group</a>';
						$output .= '</div>';
					} else {
						// For initial case.
						if ( empty( $conditionals ) ) {
							$conditionals = array(
								'rules' => array(
									1 => array(
										'settings' => array(
											'title'        => 'Conditional Confirmations',
											'redirect_to'  => 'same',
											'custom_page'  => 0,
											'external_url' => '',
											'enable_redirect_in_new_tab' => 0,
											'enable_redirect_query_string' => 0,
											'query_string' => '',
											'successful_form_submission_message' => esc_html( 'Thanks for contacting us! We will be in touch with you shortly', 'everest-forms' ),
											'submission_message_scroll' => 0,
											'form_state_type' => 'hide',
											'preview_confirmation' => 0,
											'preview_confirmation_select' => 'basic',
											'message_display_location_of_hide' => 'hide',
											'message_display_location_of_reset' => 'top',
											'active'       => 0,
										),
										1          => array(
											1 => array(
												'field'    => '---Select Field---',
												'operator' => 'is',
											),
										),
									),
								),
							);
						}
						if ( empty( $conditionals['rules'] ) ) {
							$conditionals['rules'][1] = $conditionals;
						}
						foreach ( $conditionals['rules']  as $rule_id => $rule ) {
							$conditional_option = isset( $settings[ $type ][ $panel ][ $connection_id ]['rules'][ $rule_id ]['conditional_option'] ) ? $settings[ $type ][ $panel ][ $connection_id ]['rules'][ $rule_id ]['conditional_option'] : 'send';

							$conditional_option_redirection_custom_page  = isset( $settings[ $type ][ $panel ][ $connection_id ]['rules'][ $rule_id ]['conditional_option_redirection_custom_page'] ) ? $settings[ $type ][ $panel ][ $connection_id ]['rules'][ $rule_id ]['conditional_option_redirection_custom_page'] : '';
							$conditional_option_redirection_external_url = isset( $settings[ $type ][ $panel ][ $connection_id ]['rules'][ $rule_id ]['conditional_option_redirection_external_url'] ) ? $settings[ $type ][ $panel ][ $connection_id ]['rules'][ $rule_id ]['conditional_option_redirection_external_url'] : '';
							$output                                     .= '<div class="evf-confirmation-wrap evf-content-section evf-content-confirmation-settings evf-field-conditional-container evf-custom-confirmation-wrap">';
							$args['settings']                            = isset( $form_data[ $type ][ $panel ][ $connection_id ]['conditionals']['rules'][ $rule_id ]['settings'] ) ? $form_data[ $type ][ $panel ][ $connection_id ]['conditionals']['rules'][ $rule_id ]['settings'] : array();

							$output .= self::confirmation_blocks( $args, $connection_id, $rule_id, $panel );

							$output .= '<div class="everest-forms-panel-field evf-field-confirmation-conditional-container everest-forms-border-container" data-connection_id="' . $connection_id . '">';
							$output .= '<h4 class="everest-forms-border-container-title">' . __( 'Conditional Rules', 'everest-forms-pro' ) . '</h4>';

							foreach ( $rule as $group_id => $conditions ) {
								if ( 'settings' === $group_id ) {
									continue;
								}
								$output .= '<ul class="evf-field-conditional-wrapper" data-rule=' . $rule_id . ' data-group=' . $group_id . '>';
								foreach ( $conditions as $key => $condition ) {
									$operator = isset( $condition['operator'] ) ? $condition['operator'] : 'is';
									$output  .= '<li class="evf-conditional-group" data-key="' . $key . '">';
									$output  .= '<div class="evf-form-group">';
									$output  .= '<select class="evf-field-conditional-field-select for-confirmation" data-source=' . $panel . ' data-panel-source = ' . $type . ' name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules][' . $rule_id . '][' . $group_id . '][' . $key . '][field]">';
									if ( ! empty( $conditional_fields ) ) {
										if ( 'email' === $panel ) {
											$output .= '<optgroup label="Fields"><option>---Select Field---</option>';
											foreach ( $conditional_fields as $form_fields ) {
												if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
													$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
												}
											}
											$output .= '</optgroup>';
											$output .= '<optgroup label="Payments"><option data-field_type="payment" value="payment" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', 'payment', false ) . '>' . __( 'Payment', 'everest-forms-pro' ) . '</option></optgroup>';
										} else {
											$output .= '<option>---Select Field---</option>';
											foreach ( $conditional_fields as $form_fields ) {
												if ( isset( $form_fields['meta-key'], $form_fields['label'], $form_fields['type'] ) ) {
															$output .= '<option class="evf-conditional-fields" data-field_type="' . $form_fields['type'] . '" data-field_id="' . $form_fields['id'] . '" value="' . $form_fields['id'] . '" ' . selected( isset( $condition['field'] ) ? $condition['field'] : '', $form_fields['id'], false ) . '>' . $form_fields['label'] . '</option>';
												}
											}
										}
									}
									$output .= '</select>';
									$output .= '<select class="evf-field-conditional-condition" name="' . $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules][' . $rule_id . '][' . $group_id . '][' . $key . '][operator]">';
									$output .= '<option value = "is"  ' . selected( $operator, 'is', false ) . '> is </option>';
									$output .= '<option value = "is_not" ' . selected( $operator, 'is_not', false ) . '> is not </option>';
									$output .= '<option value = "empty" ' . selected( $operator, 'empty', false ) . '> empty </option>';
									$output .= '<option value = "not_empty" ' . selected( $operator, 'not_empty', false ) . '> not empty </option>';
									$output .= '<option value = "greater_than" ' . selected( $operator, 'greater_than', false ) . '> greater than </option>';
									$output .= '<option value = "less_than" ' . selected( $operator, 'less_than', false ) . '> less than </option>';
									$output .= '<option class="everest-forms-hidden" value = "between" ' . selected( $operator, 'between', false ) . '> between </option>';
									$output .= '<option class="everest-forms-hidden" value = "multiple" ' . selected( $operator, 'multiple', false ) . '> multiple </option>';
									$output .= '</select>';
									$output .= '</div>';
									$output .= '<a class="conditonal-rule-add" href="#">AND</a>';
									$output .= '<a class="conditonal-rule-remove" href="#"><i class="dashicons dashicons-minus"></i></a>';
									$output .= '</li>';
								}
								$output .= '<span class="conditional_or">OR</span>';
								$output .= '</ul>';
							}
							$output .= '<a class="button button-small conditonal-group-add" data-panel-source=' . $type . ' href="#">Add Conditional Group</a>';
							if ( 'submission_redirection' !== $panel ) {
								$output .= '<a class="button button-small conditonal-logic-add" data-panel-source=' . $type . ' href="#">Add Conditional Logic</a>';
							}
							$output .= '</div>';
							$output .= everest_forms_panel_field(
								'toggle',
								$rule_id,
								'active',
								$form_data,
								__( 'Active', 'everest-forms' ),
								array(
									'parent'      => $type . '[' . $panel . '][' . $connection_id . '][conditionals][rules]',
									'subsection'  => 'settings',
									'class'       => 'evf-conditional-confirmation-active-toggle',
									'input_class' => 'evf-confirmationn-active-toggle',
									'default'     => '1',
								),
								false
							);

							$is_hidden = 1 === count( $conditionals['rules'] ) ? 'everest-forms-hidden' : '';
							$output   .= '<button class="evf-delete-conditional-confirmation ' . $is_hidden . '"><span>' . esc_html( 'Delete', 'everet-forms' ) . '</span></button>';

							$output .= '</div>';
						}
						if ( 'submission_redirection' === $panel ) {
							$is_enalbed = $data['conditional_logic_status'];
							$hidden     = $is_enalbed ? '' : 'everest-forms-hidden';
							$output    .= '<div class="confirmation-conditonal-logic-add-wrapper ' . $hidden . '">';
							$output    .= '<div class="add-confirmation-btn-wrap">';
							$output    .= '<a class="button button-small confirmation-conditonal-logic-add" data-panel-source=' . $type . ' href="#"><svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 19 19" fill="none">
  <path d="M4.25 9.5H14.75" stroke="#7545BB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M9.5 4.25V14.75" stroke="#7545BB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg><p>Add Conditional Confirmation</p></a>';
							$output    .= '</div>';
							$output    .= '</div>';
						}
					}

					echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}
	}

	/**
	 * Output conditional logic field option settings.
	 *
	 * @param array  $field Field array data.
	 * @param object $instance Form instance.
	 */
	public function conditional_logic_field( $field, $instance ) {
		?>
		<div class="everest-forms-conditional-fields everest-forms-field-option-group everest-forms-field-option-group-conditionals everest-forms-hide closed" id="everest-forms-field-option-conditionals-<?php echo esc_attr( $field['id'] ); ?>">
			<a href="#" class="everest-forms-field-option-group-toggle">
				<?php esc_html_e( 'Conditional Logic', 'everest-forms-pro' ); ?> <i class="handlediv"></i>
			</a>
			<div class="everest-forms-field-option-group-inner">
				<?php
				self::conditional_block(
					array(
						'form'     => $instance->form_id,
						'field'    => $field,
						'instance' => $instance,
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Conditional Logic for Submission Redirection Process.
	 *
	 * @since 1.7.7
	 *
	 * @param array $process Process Data.
	 * @param array $fields Field Data.
	 * @param array $form_data Form Data.
	 */
	public function conditional_logic_submission_redirection_process( $process, $fields, $form_data ) {
		$submission_setting = isset( $form_data['settings']['submission_redirection']['connection_1'] ) ? $form_data['settings']['submission_redirection']['connection_1'] : array();

		if ( ! isset( $submission_setting['conditional_logic_status'] ) || '1' !== $submission_setting['conditional_logic_status'] ) {
			return $process;
		}

		$conditionals = isset( $submission_setting['conditionals'] ) ? $submission_setting['conditionals'] : array();
		if ( empty( $conditionals['rules'] ) ) {
			$conditionals['rules'][1] = $conditionals;
		}

		foreach ( $conditionals['rules'] as $logic_id => $logic ) {
			$type                                        = isset( $logic['settings']['redirect_to'] ) ? $logic['settings']['redirect_to'] : '';
			$conditional_option_redirection_custom_page  = isset( $logic['settings']['custom_page'] ) ? $logic['settings']['custom_page'] : '';
			$conditional_option_redirection_external_url = isset( $logic['settings']['external_url'] ) ? $logic['settings']['external_url'] : '';
			$pass                                        = false;
			$redirection_url                             = array(
				'redirect_to'  => $type,
				'external_url' => '',
				'custom_page'  => $conditional_option_redirection_custom_page,
				'settings'     => isset( $logic['settings'] ) ? $logic['settings'] : array(),
			);
			$is_active                                   = isset( $logic['settings']['active'] ) ? $logic['settings']['active'] : 0;
			// skiping the not active settings.
			if ( 0 === $is_active ) {
				continue;
			}

			foreach ( $logic as $group_id => $group ) {
				$pass_group = true;
				// skiping the settings.
				if ( 'settings' === $group_id ) {
					continue;
				}

				foreach ( $group as $rule_id => $rule ) {

					$rule_field    = isset( $rule['field'] ) ? $rule['field'] : '';
					$rule_operator = isset( $rule['operator'] ) ? $rule['operator'] : '';
					$rule_value    = isset( $rule['value'] ) ? $rule['value'] : '';

					if ( ! empty( $rule_field ) && 'payment' === $rule_field ) {
						return false;
					}

					if ( empty( $rule_field ) || ! isset( $fields[ $rule_field ]['type'] ) ) {
						continue;
					}

					if ( isset( $fields[ $rule_field ]['type'] ) && in_array( $fields[ $rule_field ]['type'], array( 'text', 'first-name', 'last-name', 'textarea', 'email', 'url', 'number', 'hidden', 'country', 'payment-total' ), true ) ) {
							$right = trim( strtolower( $rule_value ) );

						if ( 'country' === $fields[ $rule_field ]['type'] ) {
								$left = trim( strtolower( $fields[ $rule_field ]['value']['country_code'] ) );
						} elseif ( 'payment-total' === $fields[ $rule_field ]['type'] ) {
							$left = trim( absint( $fields[ $rule_field ]['amount'] ) );
						} else {
								$left = trim( strtolower( $fields[ $rule_field ]['value'] ) );
						}

						switch ( $rule_operator ) {
							case 'is':
								$pass_rule = ( $left === $right );
								break;
							case 'is_not':
								$pass_rule = ( $left !== $right );
								break;
							case 'empty':
								$pass_rule = ( '' === $left );
								break;
							case 'not_empty':
								$pass_rule = ( '' !== $left );
								break;
							case 'greater_than':
								if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
									$pass_rule = strtotime( $left ) > strtotime( $right );
								} else {
									$left      = preg_replace( '/[^0-9.]/', '', $left );
									$pass_rule = ( '' !== $left ) && ( floatval( $left ) > floatval( $right ) );
								}
								break;

							case 'less_than':
								if ( strtotime( $left ) !== false && strtotime( $right ) !== false ) {
									$pass_rule = strtotime( $left ) < strtotime( $right );
								} else {
									$left      = preg_replace( '/[^0-9.]/', '', $left );
									$pass_rule = ( '' !== $left ) && ( floatval( $left ) < floatval( $right ) );
								}
								break;
							case 'between':
								if ( $left === $right ) {
									$pass_rule = true;
								} elseif ( ! empty( $left ) && ! empty( $right ) ) {
									$date_range = explode( ' to ', $right );

									if ( count( $date_range ) === 2 ) {
										$start_date = strtotime( $date_range[0] );
										$end_date   = strtotime( $date_range[1] );

										if ( strpos( $left, ' to ' ) !== false ) {
											$left_range = explode( ' to ', $left );
											if ( count( $left_range ) === 2 ) {
												$left_start_date = strtotime( $left_range[0] );
												$left_end_date   = strtotime( $left_range[1] );

												$pass_rule = ( $left_start_date >= $start_date && $left_end_date <= $end_date );
											} else {
												$pass_rule = false;
											}
										} else {
											$left_date = strtotime( $left );
											$pass_rule = ( $left_date >= $start_date && $left_date <= $end_date );
										}
									} else {
										$pass_rule = false;
									}
								} else {
									$pass_rule = false;
								}
								break;
							case 'multiple':
								if ( $left === $right ) {
									$pass_rule = true;
								} else {
									$right_dates    = explode( ', ', $right );
									$selected_dates = explode( ', ', $left );

									$allMatch = true;
									foreach ( $selected_dates as $date ) {
										if ( ! in_array( $date, $right_dates ) ) {
											$allMatch = false;
											break;
										}
									}

									if ( $allMatch && count( $selected_dates ) === count( $right_dates ) ) {
										$pass_rule = true;
									} else {
										$pass_rule = false;
									}
								}
								break;
						}
					} else {
						$is_val_array = false;
						if ( in_array( $fields[ $rule_field ]['type'], array( 'checkbox' ), true ) ) {
							$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
							if ( is_array( $values ) ) {
								$is_val_array = true;
								$values       = implode( ',', $values );

							}
						} else {
							$values = isset( $fields[ $rule_field ]['value_raw'] ) ? $fields[ $rule_field ]['value_raw'] : $fields[ $rule_field ]['value'];
						}

						if ( ! isset( $fields[ $rule_field ]['value_raw'] ) ) {
							$provided_id = array();

							if ( isset( $form_data['form_fields'][ $rule_field ]['choices'] ) ) {
								foreach ( $form_data['form_fields'][ $rule_field ]['choices'] as $key => $choice ) {
									$choice = array_map( 'sanitize_text_field', $choice );
									foreach ( $values as $value ) {
										$value = evf_decode_string( $value );
										if ( in_array( $value, $choice, true ) ) {
											$provided_id[] = $value;
										}
									}
								}
							}
						}
						if ( ! isset( $fields[ $rule_field ]['value_raw'] ) && ! empty( $provided_id ) ) {
							$left = (array) $provided_id;
						} else {
							$left = true === $is_val_array ? explode( ',', $values ) : (array) $values;
						}
						$right = trim( $rule_value );

						switch ( $rule_operator ) {
							case 'is':
								$pass_rule = in_array( $right, $left, true );
								break;
							case 'is_not':
								$pass_rule = ! in_array( $right, $left, true );
								break;
							case 'empty':
								$pass_rule = ( false === $left[0] );
								break;
							case 'not_empty':
								$pass_rule = ( false !== $left[0] );
								break;
						}
					}

					if ( ! $pass_rule ) {
						$pass_group = false;
						break;
					}
				}

				if ( $pass_group ) {
					$pass = true;
				}
			}

			if ( false !== $pass ) {
				if ( 'custom_page' === $type ) {
					$redirection_url['external_url'] = get_page_link( absint( $conditional_option_redirection_custom_page ) );
				} elseif ( 'external_url' === $type ) {

					$redirection_url['external_url'] = esc_url( $conditional_option_redirection_external_url );
				}
				return $redirection_url;
			}
		}

		return $process;
	}

	/**
	 * Get all Pages.
	 */
	public static function evf_get_all_pages() {
		$pages = array();
		foreach ( get_pages() as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}

		return $pages;
	}
}

new EVF_Conditional_Logics();
