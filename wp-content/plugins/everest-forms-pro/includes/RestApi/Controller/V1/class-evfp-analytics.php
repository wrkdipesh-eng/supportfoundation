<?php
/**
 * Analytics REST Controller.
 *
 * @since 1.x.x
 *
 * @package EverestForms/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVFP_Analytics_REST Class.
 */
class EVFP_Analytics_REST {

	/**
	 * Form IDs queued for impression tracking on this page load.
	 *
	 * @var int[]
	 */
	private static $forms_to_track = array();

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace = 'everest-forms-pro/v1';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	protected $rest_base = 'analytics';

	/**
	 * Register routes.
	 *
	 * @since 1.x.x
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_analytics' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'date_from' => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'date_to'   => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'unit'      => array(
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => 'day',
						),
						'form_id'   => array(
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/track',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'track_form_view' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'form_id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'referer' => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preferences',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_preferences' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_preferences' ),
					'permission_callback' => array( $this, 'check_admin_permissions' ),
					'args'                => array(
						'box_metrics' => array(
							'required' => false,
							'type'     => 'array',
							'items'    => array( 'type' => 'string' ),
						),
						'chart_prefs' => array(
							'required' => false,
							'type'     => 'object',
						),
					),
				),
			)
		);
	}

	/**
	 * Get analytics overview data.
	 *
	 * @since 1.x.x
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_analytics( $request ) {
		$date_to   = $request->get_param( 'date_to' );
		$date_from = $request->get_param( 'date_from' );
		$form_id   = (int) $request->get_param( 'form_id' );
		$unit      = $request->get_param( 'unit' );

		if ( empty( $date_to ) ) {
			$date_to = current_time( 'Y-m-d' );
		}
		if ( empty( $date_from ) ) {
			$date_from = date( 'Y-m-d', strtotime( '-30 days', strtotime( $date_to ) ) );
		}
		if ( empty( $unit ) || ! in_array( $unit, array( 'hour', 'day', 'week', 'month', 'year' ), true ) ) {
			$unit = 'day';
		}

		// Calculate previous period (same duration, immediately before current period).
		$from_ts       = strtotime( $date_from );
		$to_ts         = strtotime( $date_to );
		$duration_secs = max( $to_ts - $from_ts, 86400 );
		$prev_to_ts    = $from_ts - 1;
		$prev_from_ts  = $prev_to_ts - $duration_secs;

		$prev_date_to   = date( 'Y-m-d', $prev_to_ts );
		$prev_date_from = date( 'Y-m-d', $prev_from_ts );

		// Fetch metrics for current and previous periods.
		$current  = $this->get_period_metrics( $form_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59' );
		$previous = $this->get_period_metrics( $form_id, $prev_date_from . ' 00:00:00', $prev_date_to . ' 23:59:59' );

		// Build the overview response.
		$overview = array(
			'new_members'         => array(
				'count'             => $current['total'],
				'previous'          => $previous['total'],
				'percentage_change' => $this->percentage_change( $current['total'], $previous['total'] ),
				'currency'          => false,
			),
			'approved_members'    => array(
				'count'             => $current['complete'],
				'previous'          => $previous['complete'],
				'percentage_change' => $this->percentage_change( $current['complete'], $previous['complete'] ),
				'currency'          => false,
			),
			'pending_members'     => array(
				'count'             => $current['incomplete'],
				'previous'          => $previous['incomplete'],
				'percentage_change' => $this->percentage_change( $current['incomplete'], $previous['incomplete'] ),
				'currency'          => false,
			),
			'denied_members'      => array(
				'count'             => $current['failed'],
				'previous'          => $previous['failed'],
				'percentage_change' => $this->percentage_change( $current['failed'], $previous['failed'] ),
				'currency'          => false,
			),
			'total_revenue'       => array(
				'count'             => $current['revenue'],
				'previous'          => $previous['revenue'],
				'percentage_change' => $this->percentage_change( $current['revenue'], $previous['revenue'] ),
				'currency'          => true,
			),
			'average_order_value' => array(
				'count'             => $current['avg_order'],
				'previous'          => $previous['avg_order'],
				'percentage_change' => $this->percentage_change( $current['avg_order'], $previous['avg_order'] ),
				'currency'          => true,
			),
			'refunded_revenue'    => array(
				'count'             => $current['refunded'],
				'previous'          => $previous['refunded'],
				'percentage_change' => $this->percentage_change( $current['refunded'], $previous['refunded'] ),
				'currency'          => true,
			),
		);

		// Transactions and refund counts.
		$overview['transactions']       = array(
			'count'             => $current['transactions'],
			'previous'          => $previous['transactions'],
			'percentage_change' => $this->percentage_change( $current['transactions'], $previous['transactions'] ),
			'currency'          => false,
		);
		$overview['refund_count']       = array(
			'count'             => $current['refund_count'],
			'previous'          => $previous['refund_count'],
			'percentage_change' => $this->percentage_change( $current['refund_count'], $previous['refund_count'] ),
			'currency'          => false,
		);
		$overview['impressions_metric'] = array(
			'count'             => $current['impressions'],
			'previous'          => $previous['impressions'],
			'percentage_change' => $this->percentage_change( $current['impressions'], $previous['impressions'] ),
			'currency'          => false,
		);
		$overview['bounced_metric']     = array(
			'count'             => $current['bounced'],
			'previous'          => $previous['bounced'],
			'percentage_change' => $this->percentage_change( $current['bounced'], $previous['bounced'] ),
			'currency'          => false,
		);

		// Active forms (distinct forms with entries in the period).
		global $wpdb;
		$entries_table = $wpdb->prefix . 'evf_entries';
		if ( $form_id > 0 ) {
			$active_current  = $current['total'] > 0 ? 1 : 0;
			$active_previous = $previous['total'] > 0 ? 1 : 0;
		} else {
			$active_current  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT form_id) FROM {$entries_table} WHERE date_created BETWEEN %s AND %s",
					$date_from . ' 00:00:00',
					$date_to . ' 23:59:59'
				)
			);
			$active_previous = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT form_id) FROM {$entries_table} WHERE date_created BETWEEN %s AND %s",
					$prev_date_from . ' 00:00:00',
					$prev_date_to . ' 23:59:59'
				)
			);
		}
		$overview['active_forms'] = array(
			'count'             => $active_current,
			'previous'          => $active_previous,
			'percentage_change' => $this->percentage_change( $active_current, $active_previous ),
			'currency'          => false,
		);

		// Unique respondents (distinct IP addresses with submissions in the period).
		if ( $form_id > 0 ) {
			$resp_current  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_ip_address) FROM {$entries_table} WHERE form_id = %d AND date_created BETWEEN %s AND %s AND user_ip_address != ''",
					$form_id,
					$date_from . ' 00:00:00',
					$date_to . ' 23:59:59'
				)
			);
			$resp_previous = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_ip_address) FROM {$entries_table} WHERE form_id = %d AND date_created BETWEEN %s AND %s AND user_ip_address != ''",
					$form_id,
					$prev_date_from . ' 00:00:00',
					$prev_date_to . ' 23:59:59'
				)
			);
		} else {
			$resp_current  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_ip_address) FROM {$entries_table} WHERE date_created BETWEEN %s AND %s AND user_ip_address != ''",
					$date_from . ' 00:00:00',
					$date_to . ' 23:59:59'
				)
			);
			$resp_previous = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT user_ip_address) FROM {$entries_table} WHERE date_created BETWEEN %s AND %s AND user_ip_address != ''",
					$prev_date_from . ' 00:00:00',
					$prev_date_to . ' 23:59:59'
				)
			);
		}
		$overview['unique_respondents'] = array(
			'count'             => $resp_current,
			'previous'          => $resp_previous,
			'percentage_change' => $this->percentage_change( $resp_current, $resp_previous ),
			'currency'          => false,
		);

		// Append time-series data, forms list, and chart data.
		$overview['time_series']        = $this->get_time_series( $form_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59', $unit );
		$overview['forms']              = $this->get_forms_list();
		$overview['top_forms']          = $this->get_top_forms( $form_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59' );
		$overview['device_breakdown']   = $this->get_device_breakdown( $form_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59' );
		$overview['top_referral_pages'] = $this->get_top_referral_pages( $form_id, $date_from . ' 00:00:00', $date_to . ' 23:59:59' );

		return new \WP_REST_Response( $overview, 200 );
	}

	/**
	 * Get entry and payment metrics for a specific date period.
	 *
	 * @since 1.x.x
	 *
	 * @param int    $form_id   Form ID (0 for all forms).
	 * @param string $date_from Start datetime (Y-m-d H:i:s).
	 * @param string $date_to   End datetime (Y-m-d H:i:s).
	 * @return array
	 */
	private function get_period_metrics( $form_id, $date_from, $date_to ) {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'evf_entries';
		$meta_table    = $wpdb->prefix . 'evf_entrymeta';
		$visits_table  = $wpdb->prefix . 'evf_user_post_visits';

		// Fetch entries in the date period.
		if ( $form_id > 0 ) {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT entry_id, fields FROM {$entries_table} WHERE form_id = %d AND date_created BETWEEN %s AND %s",
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$entries = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT entry_id, fields FROM {$entries_table} WHERE date_created BETWEEN %s AND %s",
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}

		if ( empty( $entries ) ) {
			return $this->default_metrics( 0, 0, 0 );
		}

		// Count complete vs incomplete entries.
		$total    = count( $entries );
		$complete = 0;
		foreach ( $entries as $entry ) {
			$fields = json_decode( $entry['fields'], true );
			if ( $this->is_entry_complete( $fields ) ) {
				++$complete;
			}
		}
		$incomplete = $total - $complete;

		// Fetch payment metrics for these entries.
		$entry_ids    = array_column( $entries, 'entry_id' );
		$placeholders = implode( ',', array_fill( 0, count( $entry_ids ), '%d' ) );

		// Get payment entry IDs (entries that have a payment).
		$payment_entry_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT entry_id FROM {$meta_table} WHERE entry_id IN ({$placeholders}) AND meta_key = 'type' AND meta_value = 'payment'",
				...$entry_ids
			)
		);

		if ( empty( $payment_entry_ids ) ) {
			return $this->default_metrics( $total, $complete, $incomplete );
		}

		// Bulk-fetch payment status and meta blobs for payment entries.
		$pay_placeholders = implode( ',', array_fill( 0, count( $payment_entry_ids ), '%d' ) );

		$statuses = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT entry_id, meta_value FROM {$meta_table} WHERE entry_id IN ({$pay_placeholders}) AND meta_key = 'status'",
				...$payment_entry_ids
			),
			ARRAY_A
		);

		$meta_blobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT entry_id, meta_value FROM {$meta_table} WHERE entry_id IN ({$pay_placeholders}) AND meta_key = 'meta'",
				...$payment_entry_ids
			),
			ARRAY_A
		);

		// Index by entry_id for O(1) lookup.
		$status_by_entry = array();
		foreach ( $statuses as $row ) {
			$status_by_entry[ $row['entry_id'] ] = strtolower( $row['meta_value'] );
		}

		$meta_by_entry = array();
		foreach ( $meta_blobs as $row ) {
			$decoded = json_decode( $row['meta_value'], true );
			if ( is_array( $decoded ) ) {
				$meta_by_entry[ $row['entry_id'] ] = $decoded;
			}
		}

		// Calculate payment totals.
		$revenue      = 0.0;
		$refunded     = 0.0;
		$failed       = 0;
		$paid_count   = 0;
		$refund_count = 0;

		foreach ( $payment_entry_ids as $eid ) {
			$status = isset( $status_by_entry[ $eid ] ) ? $status_by_entry[ $eid ] : '';
			$meta   = isset( $meta_by_entry[ $eid ] ) ? $meta_by_entry[ $eid ] : array();
			$amount = isset( $meta['payment_total'] ) ? (float) $meta['payment_total'] : 0.0;

			switch ( $status ) {
				case 'complete':
					$revenue += $amount;
					++$paid_count;
					break;
				case 'refunded':
					$refunded += $amount;
					++$refund_count;
					break;
				case 'failed':
				case 'cancelled':
					++$failed;
					break;
			}
		}

		// Fetch impressions and bounced from the Form Analytics addon tracking table.
		$impressions = 0;
		$bounced     = 0;
		if ( $visits_table === $wpdb->get_var( "SHOW TABLES LIKE '{$visits_table}'" ) ) { // phpcs:ignore
			if ( $form_id > 0 ) {
				$visit_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT(*) AS impressions, SUM(form_submitted) AS submitted_count, SUM(form_abandoned) AS abandoned_count
						FROM {$visits_table} WHERE form_id = %d AND created_at BETWEEN %s AND %s",
						$form_id,
						$date_from,
						$date_to
					),
					ARRAY_A
				);
			} else {
				$visit_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT(*) AS impressions, SUM(form_submitted) AS submitted_count, SUM(form_abandoned) AS abandoned_count
						FROM {$visits_table} WHERE form_id > 0 AND created_at BETWEEN %s AND %s",
						$date_from,
						$date_to
					),
					ARRAY_A
				);
			}
			if ( $visit_row ) {
				$impressions = (int) $visit_row['impressions'];
				$submitted   = (int) $visit_row['submitted_count'];
				$abandoned   = (int) $visit_row['abandoned_count'];
				$bounced     = max( 0, $impressions - $submitted - $abandoned );
			}
		}

		return array(
			'total'        => $total,
			'complete'     => $complete,
			'incomplete'   => $incomplete,
			'failed'       => $failed,
			'revenue'      => round( $revenue, 2 ),
			'refunded'     => round( $refunded, 2 ),
			'avg_order'    => $paid_count > 0 ? round( $revenue / $paid_count, 2 ) : 0.0,
			'transactions' => $paid_count,
			'refund_count' => $refund_count,
			'impressions'  => $impressions,
			'bounced'      => $bounced,
		);
	}

	/**
	 * Return a zeroed-out metrics array.
	 *
	 * @param int $total      Total entries.
	 * @param int $complete   Complete entries.
	 * @param int $incomplete Incomplete entries.
	 * @return array
	 */
	private function default_metrics( $total, $complete, $incomplete ) {
		return array(
			'total'        => $total,
			'complete'     => $complete,
			'incomplete'   => $incomplete,
			'failed'       => 0,
			'revenue'      => 0.0,
			'refunded'     => 0.0,
			'impressions'  => 0,
			'bounced'      => 0,
			'avg_order'    => 0.0,
			'transactions' => 0,
			'refund_count' => 0,
		);
	}

	/**
	 * Check whether an entry is fully complete (all required fields filled).
	 *
	 * @param array|null $fields Entry fields array.
	 * @return bool
	 */
	private function is_entry_complete( $fields ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return false;
		}

		$allowed_empty = array( 'hidden', 'credit-card' );

		foreach ( $fields as $field ) {
			if ( ! isset( $field['type'] ) ) {
				continue;
			}
			if ( in_array( $field['type'], $allowed_empty, true ) ) {
				continue;
			}
			$raw   = isset( $field['value'] ) ? $field['value'] : '';
			$value = is_array( $raw ) ? trim( implode( '', array_filter( $raw, 'is_scalar' ) ) ) : trim( (string) $raw );
			if ( '' === $value ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the MySQL DATE_FORMAT string for a given time unit.
	 *
	 * @param string $unit Time unit: hour|day|week|month|year.
	 * @return string
	 */
	private function get_mysql_date_format( $unit ) {
		switch ( $unit ) {
			case 'hour':
				return '%Y-%m-%d %H:00';
			case 'week':
				return '%x-W%V';
			case 'month':
				return '%Y-%m';
			case 'year':
				return '%Y';
			case 'day':
			default:
				return '%Y-%m-%d';
		}
	}

	/**
	 * Get time-series entry data grouped by the given unit.
	 *
	 * Uses SQL GROUP BY DATE_FORMAT() to avoid loading large fields JSON
	 * and eliminate PHP-level processing on potentially huge entry sets.
	 *
	 * @since 1.x.x
	 *
	 * @param int    $form_id   Form ID (0 for all forms).
	 * @param string $date_from Start datetime (Y-m-d H:i:s).
	 * @param string $date_to   End datetime (Y-m-d H:i:s).
	 * @param string $unit      Time unit: hour|day|week|month|year.
	 * @return array
	 */
	private function get_time_series( $form_id, $date_from, $date_to, $unit ) {
		global $wpdb;

		$entries_table = $wpdb->prefix . 'evf_entries';
		$meta_table    = $wpdb->prefix . 'evf_entrymeta';
		$date_format   = $this->get_mysql_date_format( $unit );

		// Get submission counts per period with entry-status breakdown via SQL aggregation.
		// read   = viewed=1, not trashed/spam
		// unread = viewed=0, not trashed/spam
		// trash / spam come from the status column directly.
		if ( $form_id > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(date_created, %s) AS period,
					COUNT(*) AS total,
					SUM(CASE WHEN status = 'trash'   THEN 1 ELSE 0 END) AS trash_count,
					SUM(CASE WHEN status = 'spam'    THEN 1 ELSE 0 END) AS spam_count,
					SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
					SUM(CASE WHEN status NOT IN ('trash','spam') AND viewed = 1 THEN 1 ELSE 0 END) AS read_count,
					SUM(CASE WHEN status NOT IN ('trash','spam') AND viewed = 0 THEN 1 ELSE 0 END) AS unread_count,
					SUM(CASE WHEN starred = 1 THEN 1 ELSE 0 END) AS starred_count
					FROM {$entries_table}
					WHERE form_id = %d AND date_created BETWEEN %s AND %s
					GROUP BY period ORDER BY period ASC",
					$date_format,
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(date_created, %s) AS period,
					COUNT(*) AS total,
					SUM(CASE WHEN status = 'trash'   THEN 1 ELSE 0 END) AS trash_count,
					SUM(CASE WHEN status = 'spam'    THEN 1 ELSE 0 END) AS spam_count,
					SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
					SUM(CASE WHEN status NOT IN ('trash','spam') AND viewed = 1 THEN 1 ELSE 0 END) AS read_count,
					SUM(CASE WHEN status NOT IN ('trash','spam') AND viewed = 0 THEN 1 ELSE 0 END) AS unread_count,
					SUM(CASE WHEN starred = 1 THEN 1 ELSE 0 END) AS starred_count
					FROM {$entries_table}
					WHERE date_created BETWEEN %s AND %s
					GROUP BY period ORDER BY period ASC",
					$date_format,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}

		if ( empty( $rows ) ) {
			return array();
		}

		// Index period totals.
		$period_data = array();
		foreach ( $rows as $row ) {
			$period_data[ $row['period'] ] = array(
				'total'       => (int) $row['total'],
				'complete'    => 0,
				'incomplete'  => 0,
				'revenue'     => 0.0,
				'read'        => (int) $row['read_count'],
				'unread'      => (int) $row['unread_count'],
				'trash'       => (int) $row['trash_count'],
				'spam'        => (int) $row['spam_count'],
				'pending'     => (int) $row['pending_count'],
				'starred'     => (int) $row['starred_count'],
				'impressions' => 0,
				'bounced'     => 0,
			);
		}

		// Compute actual complete/incomplete counts per period by loading fields JSON.
		if ( $form_id > 0 ) {
			$field_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(date_created, %s) AS period, fields FROM {$entries_table} WHERE form_id = %d AND date_created BETWEEN %s AND %s",
					$date_format,
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$field_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(date_created, %s) AS period, fields FROM {$entries_table} WHERE date_created BETWEEN %s AND %s",
					$date_format,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}
		foreach ( $field_rows as $frow ) {
			$p = $frow['period'];
			if ( ! isset( $period_data[ $p ] ) ) {
				continue;
			}
			$fields = json_decode( $frow['fields'], true );
			if ( $this->is_entry_complete( $fields ) ) {
				++$period_data[ $p ]['complete'];
			} else {
				++$period_data[ $p ]['incomplete'];
			}
		}

		// Fetch impressions and bounced counts from the Form Analytics addon tracking table.
		// The addon (everest-forms-form-analytics) writes to wp_evf_user_post_visits.
		// Impressions = total page visits where form was present.
		// Bounced     = visits where user never interacted with the form (not submitted, not abandoned).
		$visits_table = $wpdb->prefix . 'evf_user_post_visits';
		if ( $visits_table === $wpdb->get_var( "SHOW TABLES LIKE '{$visits_table}'" ) ) { // phpcs:ignore
			if ( $form_id > 0 ) {
				$impression_rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DATE_FORMAT(created_at, %s) AS period,
						COUNT(*) AS impressions,
						SUM(form_submitted) AS submitted_count,
						SUM(form_abandoned) AS abandoned_count
						FROM {$visits_table}
						WHERE form_id = %d AND created_at BETWEEN %s AND %s
						GROUP BY period",
						$date_format,
						$form_id,
						$date_from,
						$date_to
					),
					ARRAY_A
				);
			} else {
				$impression_rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DATE_FORMAT(created_at, %s) AS period,
						COUNT(*) AS impressions,
						SUM(form_submitted) AS submitted_count,
						SUM(form_abandoned) AS abandoned_count
						FROM {$visits_table}
						WHERE form_id > 0 AND created_at BETWEEN %s AND %s
						GROUP BY period",
						$date_format,
						$date_from,
						$date_to
					),
					ARRAY_A
				);
			}
			foreach ( $impression_rows as $irow ) {
				if ( ! isset( $period_data[ $irow['period'] ] ) ) {
					continue;
				}
				$impressions                                   = (int) $irow['impressions'];
				$submitted                                     = (int) $irow['submitted_count'];
				$abandoned                                     = (int) $irow['abandoned_count'];
				$period_data[ $irow['period'] ]['impressions'] = $impressions;
				$period_data[ $irow['period'] ]['bounced']     = max( 0, $impressions - $submitted - $abandoned );
			}
		}

		// Get completed payment revenue per period.
		// Include e.entry_id so we can deduplicate in PHP — duplicate entrymeta rows
		// (e.g. from multiple seed scripts) would otherwise cause the JOIN to return
		// multiple rows per entry, inflating the revenue total.
		if ( $form_id > 0 ) {
			$revenue_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT e.entry_id, DATE_FORMAT(e.date_created, %s) AS period, em_meta.meta_value AS meta
					FROM {$entries_table} e
					INNER JOIN {$meta_table} em_type   ON e.entry_id = em_type.entry_id   AND em_type.meta_key   = 'type'   AND em_type.meta_value   = 'payment'
					INNER JOIN {$meta_table} em_status ON e.entry_id = em_status.entry_id AND em_status.meta_key = 'status' AND em_status.meta_value = 'complete'
					INNER JOIN {$meta_table} em_meta   ON e.entry_id = em_meta.entry_id   AND em_meta.meta_key   = 'meta'
					WHERE e.form_id = %d AND e.date_created BETWEEN %s AND %s",
					$date_format,
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$revenue_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT e.entry_id, DATE_FORMAT(e.date_created, %s) AS period, em_meta.meta_value AS meta
					FROM {$entries_table} e
					INNER JOIN {$meta_table} em_type   ON e.entry_id = em_type.entry_id   AND em_type.meta_key   = 'type'   AND em_type.meta_value   = 'payment'
					INNER JOIN {$meta_table} em_status ON e.entry_id = em_status.entry_id AND em_status.meta_key = 'status' AND em_status.meta_value = 'complete'
					INNER JOIN {$meta_table} em_meta   ON e.entry_id = em_meta.entry_id   AND em_meta.meta_key   = 'meta'
					WHERE e.date_created BETWEEN %s AND %s",
					$date_format,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}

		$seen_revenue_entries = array();
		foreach ( $revenue_rows as $row ) {
			if ( ! isset( $period_data[ $row['period'] ] ) ) {
				continue;
			}
			// Skip duplicate rows for the same entry (caused by multiple entrymeta rows).
			if ( isset( $seen_revenue_entries[ $row['entry_id'] ] ) ) {
				continue;
			}
			$seen_revenue_entries[ $row['entry_id'] ] = true;
			$meta                                     = json_decode( $row['meta'], true );
			$amount                                   = isset( $meta['payment_total'] ) ? (float) $meta['payment_total'] : 0.0;
			$period_data[ $row['period'] ]['revenue'] = round(
				$period_data[ $row['period'] ]['revenue'] + $amount,
				2
			);
		}

		// Get payment transaction counts by status per period.
		// Use COUNT(DISTINCT CASE WHEN...) to avoid inflated counts from duplicate
		// entrymeta rows (e.g. multiple type=payment rows for the same entry).
		if ( $form_id > 0 ) {
			$payment_status_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(e.date_created, %s) AS period,
					COUNT(DISTINCT CASE WHEN em_status.meta_value = 'complete'               THEN e.entry_id END) AS payment_complete,
					COUNT(DISTINCT CASE WHEN em_status.meta_value = 'pending'                THEN e.entry_id END) AS payment_pending,
					COUNT(DISTINCT CASE WHEN em_status.meta_value IN ('failed','cancelled')  THEN e.entry_id END) AS payment_failed,
					COUNT(DISTINCT CASE WHEN em_status.meta_value = 'refunded'               THEN e.entry_id END) AS payment_refunded
					FROM {$entries_table} e
					INNER JOIN {$meta_table} em_type   ON e.entry_id = em_type.entry_id   AND em_type.meta_key   = 'type'   AND em_type.meta_value = 'payment'
					INNER JOIN {$meta_table} em_status ON e.entry_id = em_status.entry_id AND em_status.meta_key = 'status'
					WHERE e.form_id = %d AND e.date_created BETWEEN %s AND %s
					GROUP BY period ORDER BY period ASC",
					$date_format,
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$payment_status_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT(e.date_created, %s) AS period,
					COUNT(DISTINCT CASE WHEN em_status.meta_value = 'complete'               THEN e.entry_id END) AS payment_complete,
					COUNT(DISTINCT CASE WHEN em_status.meta_value = 'pending'                THEN e.entry_id END) AS payment_pending,
					COUNT(DISTINCT CASE WHEN em_status.meta_value IN ('failed','cancelled')  THEN e.entry_id END) AS payment_failed,
					COUNT(DISTINCT CASE WHEN em_status.meta_value = 'refunded'               THEN e.entry_id END) AS payment_refunded
					FROM {$entries_table} e
					INNER JOIN {$meta_table} em_type   ON e.entry_id = em_type.entry_id   AND em_type.meta_key   = 'type'   AND em_type.meta_value = 'payment'
					INNER JOIN {$meta_table} em_status ON e.entry_id = em_status.entry_id AND em_status.meta_key = 'status'
					WHERE e.date_created BETWEEN %s AND %s
					GROUP BY period ORDER BY period ASC",
					$date_format,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}

		foreach ( $payment_status_rows as $row ) {
			if ( isset( $period_data[ $row['period'] ] ) ) {
				$period_data[ $row['period'] ]['payment_complete'] = (int) $row['payment_complete'];
				$period_data[ $row['period'] ]['payment_pending']  = (int) $row['payment_pending'];
				$period_data[ $row['period'] ]['payment_failed']   = (int) $row['payment_failed'];
				$period_data[ $row['period'] ]['payment_refunded'] = (int) $row['payment_refunded'];
			}
		}

		// Build result array.
		$result = array();
		foreach ( $period_data as $period => $data ) {
			$result[] = array(
				'period'           => $period,
				'total'            => $data['total'],
				'complete'         => $data['complete'],
				'incomplete'       => $data['incomplete'],
				'revenue'          => $data['revenue'],
				'read'             => $data['read'],
				'unread'           => $data['unread'],
				'trash'            => $data['trash'],
				'spam'             => $data['spam'],
				'pending'          => $data['pending'],
				'starred'          => $data['starred'],
				'payment_complete' => $data['payment_complete'] ?? 0,
				'payment_pending'  => $data['payment_pending'] ?? 0,
				'payment_failed'   => $data['payment_failed'] ?? 0,
				'payment_refunded' => $data['payment_refunded'] ?? 0,
				'impressions'      => $data['impressions'] ?? 0,
				'bounced'          => $data['bounced'] ?? 0,
			);
		}

		return $result;
	}

	/**
	 * Get top-performing forms by entry count for the given period.
	 *
	 * @param int    $form_id   Form ID (0 = all forms).
	 * @param string $date_from Start datetime.
	 * @param string $date_to   End datetime.
	 * @return array Array of { title, count } sorted descending.
	 */
	/**
	 * Get a form title by ID, falling back to the post title (any status) and finally "Form #ID".
	 *
	 * @param int   $fid       Form ID.
	 * @param array $all_forms Published forms map from evf_get_all_forms().
	 * @return string
	 */
	private function resolve_form_title( $fid, $all_forms ) {
		if ( isset( $all_forms[ $fid ] ) ) {
			return (string) $all_forms[ $fid ];
		}
		$post = get_post( $fid );
		if ( $post && ! empty( $post->post_title ) ) {
			// Append status hint so the user knows the form is no longer active.
			$status = $post->post_status;
			/* translators: 1: Form title, 2: Post status (e.g. trash, draft) */
			return sprintf( __( '%1$s (%2$s)', 'everest-forms-pro' ), $post->post_title, $status );
		}
		/* translators: %d: Form ID */
		return sprintf( __( 'Form #%d', 'everest-forms-pro' ), $fid );
	}

	private function get_top_forms( $form_id, $date_from, $date_to ) {
		global $wpdb;
		$entries_table = $wpdb->prefix . 'evf_entries';
		$meta_table    = $wpdb->prefix . 'evf_entrymeta';
		$all_forms     = evf_get_all_forms();

		if ( $form_id > 0 ) {
			// Count entries and fetch meta blobs to compute revenue in PHP (payment_total lives in JSON blob).
			$cnt        = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$entries_table} WHERE form_id = %d AND date_created BETWEEN %s AND %s",
					$form_id,
					$date_from,
					$date_to
				)
			);
			$meta_blobs = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT em.meta_value FROM {$entries_table} e
					INNER JOIN {$meta_table} em_type ON e.entry_id = em_type.entry_id AND em_type.meta_key = 'type' AND em_type.meta_value = 'payment'
					INNER JOIN {$meta_table} em       ON e.entry_id = em.entry_id       AND em.meta_key = 'meta'
					WHERE e.form_id = %d AND e.date_created BETWEEN %s AND %s",
					$form_id,
					$date_from,
					$date_to
				)
			);
			$revenue    = 0.0;
			foreach ( $meta_blobs as $blob ) {
				$decoded  = json_decode( $blob, true );
				$revenue += isset( $decoded['payment_total'] ) ? (float) $decoded['payment_total'] : 0.0;
			}
			$title = $this->resolve_form_title( $form_id, $all_forms );
			return array(
				array(
					'title'   => $title,
					'count'   => $cnt,
					'revenue' => round( $revenue, 2 ),
				),
			);
		}

		// All forms: get counts grouped by form_id then fetch revenue per form in PHP.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.form_id, COUNT(*) AS cnt
				FROM {$entries_table} e
				WHERE e.date_created BETWEEN %s AND %s
				GROUP BY e.form_id
				ORDER BY cnt DESC LIMIT 10",
				$date_from,
				$date_to
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array();
		}

		// Fetch payment meta blobs for the top form IDs in one query.
		$top_form_ids  = array_column( $rows, 'form_id' );
		$placeholders  = implode( ', ', array_fill( 0, count( $top_form_ids ), '%d' ) );
		$revenue_blobs = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				"SELECT e.form_id, em.meta_value FROM {$entries_table} e
				INNER JOIN {$meta_table} em_type ON e.entry_id = em_type.entry_id AND em_type.meta_key = 'type' AND em_type.meta_value = 'payment'
				INNER JOIN {$meta_table} em       ON e.entry_id = em.entry_id       AND em.meta_key = 'meta'
				WHERE e.form_id IN ($placeholders) AND e.date_created BETWEEN %s AND %s",
				array_merge( $top_form_ids, array( $date_from, $date_to ) )
			),
			ARRAY_A
		);

		$revenue_by_form = array();
		foreach ( $revenue_blobs as $blob_row ) {
			$fid                     = (int) $blob_row['form_id'];
			$decoded                 = json_decode( $blob_row['meta_value'], true );
			$amount                  = isset( $decoded['payment_total'] ) ? (float) $decoded['payment_total'] : 0.0;
			$revenue_by_form[ $fid ] = ( $revenue_by_form[ $fid ] ?? 0.0 ) + $amount;
		}

		$result = array();
		foreach ( $rows as $row ) {
			$fid      = (int) $row['form_id'];
			$title    = $this->resolve_form_title( $fid, $all_forms );
			$result[] = array(
				'title'   => $title,
				'count'   => (int) $row['cnt'],
				'revenue' => round( $revenue_by_form[ $fid ] ?? 0.0, 2 ),
			);
		}
		return $result;
	}

	/**
	 * Get top referral pages (by page view referer) for the given period.
	 *
	 * @param int    $form_id   Form ID (0 = all forms).
	 * @param string $date_from Start datetime.
	 * @param string $date_to   End datetime.
	 * @return array
	 */
	private function get_top_referral_pages( $form_id, $date_from, $date_to ) {
		global $wpdb;

		// Use the Form Analytics addon table when available (same data source as old analytics).
		$fa_table = $wpdb->prefix . 'evf_user_post_visits';
		$use_fa   = $wpdb->get_var( "SHOW TABLES LIKE '{$fa_table}'" ) === $fa_table;

		if ( $use_fa ) {
			$referer_col = 'referer_url';
			$date_col    = 'created_at';
			$table       = $fa_table;
		} else {
			$referer_col = 'referer';
			$date_col    = 'date_created';
			$table       = $wpdb->prefix . 'evf_form_views';
		}

		if ( $form_id > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						CASE WHEN TRIM({$referer_col}) = '' THEN 'Direct / (none)' ELSE {$referer_col} END AS referer_url,
						COUNT(*) AS cnt
					FROM {$table}
					WHERE form_id = %d AND {$date_col} BETWEEN %s AND %s
					GROUP BY referer_url
					ORDER BY cnt DESC
					LIMIT 5",
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						CASE WHEN TRIM({$referer_col}) = '' THEN 'Direct / (none)' ELSE {$referer_col} END AS referer_url,
						COUNT(*) AS cnt
					FROM {$table}
					WHERE {$date_col} BETWEEN %s AND %s
					GROUP BY referer_url
					ORDER BY cnt DESC
					LIMIT 5",
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}

		if ( empty( $rows ) ) {
			return array();
		}

		$result = array();
		foreach ( $rows as $row ) {
			$url   = $row['referer_url'];
			$title = $url;

			if ( 'Direct / (none)' !== $url ) {
				$post_id = url_to_postid( $url );
				if ( $post_id > 0 ) {
					$page_title = get_the_title( $post_id );
					if ( ! empty( $page_title ) ) {
						$title = $page_title;
					}
				}
			}

			$result[] = array(
				'url'   => $url,
				'title' => $title,
				'count' => (int) $row['cnt'],
			);
		}
		return $result;
	}

	/**
	 * Get device breakdown (desktop / mobile / tablet) for the given period.
	 *
	 * The user_device column stores "Browser/Platform/DeviceType".
	 * We extract the last segment to get "Desktop", "Mobile", or "Tablet".
	 *
	 * @param int    $form_id   Form ID (0 = all forms).
	 * @param string $date_from Start datetime.
	 * @param string $date_to   End datetime.
	 * @return array { desktop, mobile, tablet }
	 */
	private function get_device_breakdown( $form_id, $date_from, $date_to ) {
		global $wpdb;
		$entries_table = $wpdb->prefix . 'evf_entries';

		if ( $form_id > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT SUBSTRING_INDEX(user_device, '/', -1) AS device, COUNT(*) AS cnt
					FROM {$entries_table}
					WHERE form_id = %d AND date_created BETWEEN %s AND %s
					GROUP BY device",
					$form_id,
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT SUBSTRING_INDEX(user_device, '/', -1) AS device, COUNT(*) AS cnt
					FROM {$entries_table}
					WHERE date_created BETWEEN %s AND %s
					GROUP BY device",
					$date_from,
					$date_to
				),
				ARRAY_A
			);
		}

		$breakdown = array(
			'desktop' => 0,
			'mobile'  => 0,
			'tablet'  => 0,
		);

		$mobile_label = strtolower( esc_html__( 'Mobile', 'everest-forms' ) );
		$tablet_label = strtolower( esc_html__( 'Tablet', 'everest-forms' ) );

		foreach ( $rows as $row ) {
			$device = strtolower( trim( $row['device'] ) );
			if ( $mobile_label === $device ) {
				$breakdown['mobile'] += (int) $row['cnt'];
			} elseif ( $tablet_label === $device ) {
				$breakdown['tablet'] += (int) $row['cnt'];
			} else {
				// Desktop and any unknown value → desktop bucket.
				$breakdown['desktop'] += (int) $row['cnt'];
			}
		}

		return $breakdown;
	}

	/**
	 * Get the list of all Everest Forms.
	 *
	 * @return array Array of { id, title } objects.
	 */
	private function get_forms_list() {
		$forms_raw = evf_get_all_forms();
		$forms     = array();

		foreach ( $forms_raw as $form_id => $form_title ) {
			$is_payment_form = false;
			$is_survey_form  = false;
			$form            = evf()->form->get( (int) $form_id );
			if ( $form && ! empty( $form->post_content ) ) {
				$form_data = evf_decode( $form->post_content );
				if ( ! empty( $form_data['form_fields'] ) ) {
					foreach ( $form_data['form_fields'] as $field ) {
						if ( isset( $field['type'] ) && strpos( $field['type'], 'payment-' ) === 0 ) {
							$is_payment_form = true;
						}
						if ( isset( $field['survey_status'] ) && '1' === $field['survey_status'] ) {
							$is_survey_form = true;
						}
					}
				}
			}

			$forms[] = array(
				'id'              => (int) $form_id,
				'title'           => (string) $form_title,
				'is_payment_form' => $is_payment_form,
				'is_survey_form'  => $is_survey_form,
			);
		}

		return $forms;
	}

	/**
	 * Calculate the percentage change between a current and previous value.
	 *
	 * @param float $current  Current period value.
	 * @param float $previous Previous period value.
	 * @return float
	 */
	private function percentage_change( $current, $previous ) {
		if ( 0 == $previous ) {
			return $current > 0 ? 100.0 : 0.0;
		}
		return round( ( ( $current - $previous ) / $previous ) * 100, 1 );
	}

	/**
	 * Record a form impression (view) in the evf_form_views table.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function track_form_view( $request ) {
		global $wpdb;

		$form_id = (int) $request->get_param( 'form_id' );
		if ( $form_id <= 0 ) {
			return new \WP_Error( 'invalid_form', 'Invalid form ID.', array( 'status' => 400 ) );
		}

		$referer     = (string) $request->get_param( 'referer' );
		$views_table = $wpdb->prefix . 'evf_form_views';

		// Silently skip if table does not exist yet.
		if ( $views_table !== $wpdb->get_var( "SHOW TABLES LIKE '{$views_table}'" ) ) { // phpcs:ignore
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		}

		$wpdb->insert(
			$views_table,
			array(
				'form_id'      => $form_id,
				'referer'      => $referer,
				'date_created' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s' )
		);

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Collect a form ID to be tracked when it is displayed on the frontend.
	 * Called via the everest_forms_frontend_output action.
	 *
	 * @param array $form_data Form data array.
	 */
	public static function collect_form_for_tracking( $form_data ) {
		if ( is_admin() ) {
			return;
		}
		$form_id = absint( isset( $form_data['id'] ) ? $form_data['id'] : 0 );
		if ( $form_id > 0 && ! in_array( $form_id, self::$forms_to_track, true ) ) {
			self::$forms_to_track[] = $form_id;
		}
	}

	/**
	 * Output a small inline script in wp_footer to track form impressions.
	 */
	public static function output_tracking_script() {
		if ( empty( self::$forms_to_track ) ) {
			return;
		}
		$track_url = rest_url( 'everest-forms-pro/v1/analytics/track' );
		$form_ids  = array_values( self::$forms_to_track );
		echo '<script id="evf-analytics-tracking">';
		echo '(function(){';
		echo 'var u=' . wp_json_encode( $track_url ) . ';';
		echo 'var r=document.referrer;';
		echo 'var f=' . wp_json_encode( $form_ids ) . ';';
		echo 'if(window.fetch){f.forEach(function(id){fetch(u,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({form_id:id,referer:r}),keepalive:true});});}';
		echo '})();';
		echo '</script>';
	}

	/**
	 * Get the current user's analytics dashboard preferences.
	 *
	 * @return WP_REST_Response
	 */
	public function get_preferences( $request ) {
		$defaults    = array( 'total', 'complete', 'incomplete', 'revenue' );
		$box_metrics = get_user_meta( get_current_user_id(), '_evf_analytics_box_metrics', true );

		if ( empty( $box_metrics ) || ! is_array( $box_metrics ) ) {
			$box_metrics = $defaults;
		}

		$chart_prefs = get_user_meta( get_current_user_id(), '_evf_analytics_chart_prefs', true );
		if ( empty( $chart_prefs ) || ! is_array( $chart_prefs ) ) {
			$chart_prefs = null;
		}

		$response = array( 'box_metrics' => $box_metrics );
		if ( $chart_prefs ) {
			$response['chart_prefs'] = $chart_prefs;
		}

		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Save the current user's analytics dashboard preferences.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_preferences( $request ) {
		$user_id = get_current_user_id();

		// Save box_metrics if provided.
		$box_metrics = $request->get_param( 'box_metrics' );
		if ( null !== $box_metrics ) {
			$valid_keys = array( 'total', 'complete', 'incomplete', 'active', 'impressions', 'convrate', 'bounce', 'abandonments', 'abandon_rate', 'revenue', 'transactions' );

			if ( ! is_array( $box_metrics ) || count( $box_metrics ) < 1 || count( $box_metrics ) > 14 ) {
				return new \WP_Error( 'invalid_preferences', __( 'box_metrics must contain between 1 and 14 items.', 'everest-forms' ), array( 'status' => 400 ) );
			}
			foreach ( $box_metrics as $key ) {
				if ( ! in_array( $key, $valid_keys, true ) ) {
					return new \WP_Error( 'invalid_metric', sprintf( __( 'Invalid metric key: %s', 'everest-forms' ), $key ), array( 'status' => 400 ) );
				}
			}
			update_user_meta( $user_id, '_evf_analytics_box_metrics', $box_metrics );
		}

		// Save chart_prefs if provided.
		$chart_prefs = $request->get_param( 'chart_prefs' );
		if ( null !== $chart_prefs ) {
			$valid_primary   = array( 'submissions', 'total', 'completed', 'incomplete', 'abandoned', 'bounced', 'impressions', 'revenue', 'completion_rate', 'entry_status', 'read', 'unread', 'trash', 'spam', 'payment_overview', 'payment_complete', 'payment_pending', 'payment_failed', 'payment_refunded', 'pending', 'starred' );
			$valid_secondary = array( 'complete_vs_incomplete', 'top_forms', 'device_breakdown', 'revenue_overview', 'top_referral_pages' );
			$valid_types     = array( 'line', 'bar' );

			$primary_view    = isset( $chart_prefs['primary_view'] ) ? sanitize_text_field( $chart_prefs['primary_view'] ) : 'submissions';
			$secondary_views = isset( $chart_prefs['secondary_views'] ) && is_array( $chart_prefs['secondary_views'] ) ? $chart_prefs['secondary_views'] : array();
			$chart_type      = isset( $chart_prefs['chart_type'] ) ? sanitize_text_field( $chart_prefs['chart_type'] ) : 'line';

			if ( ! in_array( $primary_view, $valid_primary, true ) ) {
				return new \WP_Error( 'invalid_chart_prefs', __( 'Invalid primary chart view.', 'everest-forms' ), array( 'status' => 400 ) );
			}
			if ( count( $secondary_views ) !== 3 ) {
				return new \WP_Error( 'invalid_chart_prefs', __( 'secondary_views must contain exactly 3 items.', 'everest-forms' ), array( 'status' => 400 ) );
			}
			foreach ( $secondary_views as $view ) {
				if ( ! in_array( sanitize_text_field( $view ), $valid_secondary, true ) ) {
					return new \WP_Error( 'invalid_chart_prefs', sprintf( __( 'Invalid secondary chart view: %s', 'everest-forms' ), $view ), array( 'status' => 400 ) );
				}
			}
			if ( ! in_array( $chart_type, $valid_types, true ) ) {
				return new \WP_Error( 'invalid_chart_prefs', __( 'Invalid chart type.', 'everest-forms' ), array( 'status' => 400 ) );
			}

			$sanitized = array(
				'primary_view'    => $primary_view,
				'secondary_views' => array_map( 'sanitize_text_field', $secondary_views ),
				'chart_type'      => $chart_type,
			);
			update_user_meta( $user_id, '_evf_analytics_chart_prefs', $sanitized );
		}

		if ( null === $box_metrics && null === $chart_prefs ) {
			return new \WP_Error( 'invalid_preferences', __( 'No valid preferences provided.', 'everest-forms' ), array( 'status' => 400 ) );
		}

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Check if the request has admin permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function check_admin_permissions( $request ) {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_everest_forms' );
	}
}
