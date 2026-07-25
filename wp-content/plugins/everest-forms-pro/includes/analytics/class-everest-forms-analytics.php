<?php
/**
 * Entries Analytics
 *
 * @package EVFP_Analytics
 * @version 1.3.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class EVFP_Analytics.
 *
 * @since 1.3.5
 */
class EVFP_Analytics {

	/**
	 * Duration.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $duration = array(
		'day'   => 0,
		'week'  => 6,
		'month' => 29,
	);

	/**
	 * Instance.
	 *
	 * @var EVFP_Analytics
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize Hooks.
	 */
	public function init_hooks() {
		add_action( 'wp_loaded', array( $this, 'maybe_delete_transient' ) );
		add_action( 'wp_ajax_evfp_get_analytics', array( $this, 'forms_analytics' ) );
		add_action( 'wp_ajax_nopriv_evfp_get_analytics', array( $this, 'forms_analytics' ) );
		add_action( 'everest_forms_inline_advance_settings', array( $this, 'display_enable_setting' ) );
	}

	/**
	 * Get instance.
	 *
	 * @since 1.3.5
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Disable/Enable setting.
	 *
	 * @since 1.3.5
	 *
	 * @param object $obj Form object.
	 */
	public function display_enable_setting( $obj ) {
		everest_forms_panel_field(
			'toggle',
			'settings',
			'enable_entries_dashboard_analytics',
			$obj->form_data,
			esc_html__( 'Enable entries dashboard analytics', 'everest-forms-pro' ),
			array(
				'default' => isset( $settings['enable_entries_dashboard_analytics'] ) ? $settings['enable_entries_dashboard_analytics'] : 1,
				/* translators: %1$s - general settings docs url */
											'tooltip' => sprintf( esc_html__( 'Enable entries dashboard analytics. <a href="%1$s" target="_blank">Learn More</a>', 'everest-forms-pro' ), esc_url( 'https://docs.everestforms.net/docs/general-settings/#13-toc-title' ) ),
			)
		);
	}

	/**
	 * Single form analytics.
	 */
	public function forms_analytics() {
		if ( ! isset( $_POST['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_send_json_error(
				esc_html__( 'Form ID is required.', 'everest-forms-pro' ),
				400
			);
		}

		$form_id = absint( $_POST['form_id'] );
		$form    = get_post( $form_id );
		if ( null === $form ) {
			wp_send_json_error(
				esc_html__( 'Invalid Form Id.', 'everest-forms-pro' ),
				400
			);
		}

		$duration = isset( $_POST['duration'] ) ? sanitize_text_field( $_POST['duration'] ) : 'month';
		$duration = strtolower( $duration );
		$duration = isset( $this->duration[ $duration ] ) ? $duration : 'month';
		$duration = apply_filters( 'everest_forms_pro_form_analytics_duration', $duration, $form_id );

		$this->allowed_empty_fields = apply_filters(
			'everest_forms_pro_allowed_empty_fields',
			array(
				'hidden',
				'credit-card',
			),
			$form_id
		);

		$stats = get_transient( sprintf( 'evf_entries_%s_stats', $duration ) );

		if ( false === $stats ) {
			$dates   = $this->get_range_dates( $duration );
			$entries = evf_get_entries_by_form_id( $form_id, $dates['from'], $dates['to'] );
			$stats   = $this->form_statistics( $entries, $duration );
			set_transient( sprintf( 'evf_entries_%s_stats', $duration ), $stats );
		}

		$stats = apply_filters( 'everest_forms_pro_form_analytics', $stats, $form_id );

		wp_send_json_success( $stats, 200 );
	}

	/**
	 * Maybe delete cached transients on load.
	 *
	 * @since 1.4.1
	 */
	public function maybe_delete_transient() {
		if ( ! wp_doing_ajax() ) {
			foreach ( array_keys( $this->duration ) as $duration ) {
				$stats = get_transient( sprintf( 'evf_entries_%s_stats', $duration ) );

				if ( ! empty( $stats ) ) {
					// Everythime on reload clear the transient cache. Object cache won't work with ajax.
					delete_transient( sprintf( 'evf_entries_%s_stats', $duration ) );
				}
			}
		}
	}

	/**
	 * Get date range.
	 *
	 * @param string $duration Duration in day, week and month.
	 *
	 * @return array
	 */
	private function get_range_dates( $duration ) {
		$to               = new DateTime();
		$from             = new DateTime();
		$duration_in_days = $this->duration[ $duration ];

		date_sub( $from, date_interval_create_from_date_string( "{$duration_in_days} days" ) );

		$from = 'day' === $duration ? $from->setTime( 0, 0, 0 ) : $from;

		$to   = $to->format( 'Y-m-d H:i:s' );
		$from = $from->format( 'Y-m-d H:i:s' );

		if ( isset( $_POST['from'] ) && ! empty( $_POST['from'] ) ) {
			$from = sanitize_text_field( $_POST['from'] );
		}
		if ( isset( $_POST['to'] ) && ! empty( $_POST['to'] ) ) {
			$to = sanitize_text_field( $_POST['to'] );
		}

		return array(
			'from' => $from,
			'to'   => $to,
		);
	}

	/**
	 * Compute form statistics.
	 *
	 * @param array  $entries Entries.
	 * @param string $duration Duration in day, week and month.
	 *
	 * @return array Form statistics.
	 */
	private function form_statistics( $entries, $duration ) {
		$stats = $this->default_form_statistics_values( $duration );

		// Bail early if the entries is empty.
		if ( empty( $entries ) ) {
			return $stats;
		}

		// Calcualte the complete and incomplete entries, and browser,
		// device stats.
		foreach ( $entries as $entry ) {
			$fields = json_decode( $entry['fields'], true );

			if ( $this->is_complete( $fields ) ) {
				++$stats['entries']['complete']['count'];
				$series = &$stats['entries']['complete']['series'];
			} else {
				++$stats['entries']['incomplete']['count'];
				$series = &$stats['entries']['incomplete']['series'];
			}

			$this->create_entries_series( $series, $entry['date_created'], $duration );

			$user_device = trim( $entry['user_device'] );
			$user_device = explode( '/', $user_device );
			$this->browser_stats( $user_device, $stats );
			$this->platform_stats( $user_device, $stats );
			$this->device_stats( $user_device, $stats );
		}

		// Calculate the total entries and add total series.
		$complete_count                      = $stats['entries']['complete']['count'];
		$incomplete_count                    = $stats['entries']['incomplete']['count'];
		$stats['entries']['total']['count']  = $complete_count + $incomplete_count;
		$stats['entries']['total']['series'] = $stats['entries']['complete']['series'];

		// Add in the incomplete series to the total series.
		foreach ( $stats['entries']['incomplete']['series'] as $key => $value ) {
			if ( ! isset( $stats['entries']['total']['series'][ $key ] ) ) {
				$stats['entries']['total']['series'][ $key ] = $value;
			} else {
				$stats['entries']['total']['series'][ $key ] += $value;
			}
		}

		ksort( $stats['entries']['complete']['series'] );
		ksort( $stats['entries']['incomplete']['series'] );
		ksort( $stats['entries']['total']['series'] );

		return $stats;
	}

	/**
	 * Entries collection by duraiton.
	 *
	 * @param array  $series         Entry series collection.
	 * @param string $date_created  Date of entry creation.
	 * @param string $duration      Duration.
	 */
	private function create_entries_series( &$series, $date_created, $duration ) {
		$date = DateTime::createFromFormat( 'Y-m-d H:i:s', $date_created );

		if ( 'day' === $duration ) {
			$date = $date->format( 'Y-m-d H:00:00' );
		} else {
			$date = $date->format( 'Y-m-d' );
		}

		if ( ! isset( $series[ $date ] ) ) {
			$series[ $date ] = 0;
		}

		++$series[ $date ];
	}

	/**
	 * Compute browser statistics.
	 *
	 * @param array $user_device User device data.
	 * @param array $stats Refrence to stats.
	 */
	private function browser_stats( $user_device, &$stats ) {
		$browser = isset( $user_device[0] ) ? $user_device[0] : 'Unknown';

		if ( ! isset( $stats['browser'][ $browser ] ) ) {
			$stats['browser'][ $browser ] = 1;
		} else {
			++$stats['browser'][ $browser ];
		}
	}

	/**
	 * Compute platform statistics.
	 *
	 * @param array $user_device User device data.
	 * @param array $stats Refrence to stats.
	 */
	private function platform_stats( $user_device, &$stats ) {
		$platform = isset( $user_device[1] ) ? $user_device[1] : 'Unknown';

		if ( ! isset( $stats['platform'][ $platform ] ) ) {
			$stats['platform'][ $platform ] = 1;
		} else {
			++$stats['platform'][ $platform ];
		}
	}

	/**
	 * Compute device statistics.
	 *
	 * @param array $user_device User device data.
	 * @param array $stats Refrence to stats.
	 */
	private function device_stats( $user_device, &$stats ) {
		$device = isset( $user_device[2] ) ? $user_device[2] : 'Desktop';

		if ( ! isset( $stats['device'][ $device ] ) ) {
			$stats['device'][ $device ] = 1;
		} else {
			++$stats['device'][ $device ];
		}
	}

	/**
	 * Check whether the entry is complete or not.
	 *
	 * @param array $fields Entry fields.
	 *
	 * @return boolean
	 */
	private function is_complete( $fields ) {
		foreach ( $fields as $field ) {
			if ( in_array( $field['type'], $this->allowed_empty_fields, true ) ) {
				continue;
			}

			if ( is_string( $field['value'] ) ) {
				$value = trim( $field['value'] );
			}

			if ( empty( $value ) ) {
				return false;
			}
		}

		return true;
	}

	private function default_form_statistics_values( $duration = 'month' ) {
		$dates = $this->get_range_dates( $duration );

		if ( 'day' === $duration ) {
			$to = date_create( $dates['to'] );
			$to = $to->setTime( 23, 0, 0 );
			$to = $to->format( 'Y-m-d H:i:s' );

			$fromt       = date_create( $dates['from'] );
			$fromt       = $fromt->setTime( 0, 0, 0 );
			$fromt       = $fromt->format( 'Y-m-d H:i:s' );
			$dates_range = evf_date_range( $fromt, $to, '+1 hour', 'Y-m-d H:00:00' );
		} else {
			$dates_range = evf_date_range( $dates['from'], $dates['to'], '+1 day', 'Y-m-d' );
		}

		$dates = array_reduce(
			$dates_range,
			function( $result, $date ) {
				$result[ $date ] = 0;
				return $result;
			},
			array()
		);

		$default_values = array(
			'entries'  => array(
				'complete'   => array(
					'count'  => 0,
					'series' => $dates,
				),
				'incomplete' => array(
					'count'  => 0,
					'series' => $dates,
				),
				'total'      => array(
					'count'  => 0,
					'series' => $dates,
				),
			),
			'device'   => array(
				'Desktop' => 0,
				'Tablet'  => 0,
				'Mobile'  => 0,
			),
			'platform' => array(),
			'browser'  => array(),
		);

		return apply_filters( 'everest_forms_default_form_statistics_values', $default_values );
	}
}

EVFP_Analytics::instance();
