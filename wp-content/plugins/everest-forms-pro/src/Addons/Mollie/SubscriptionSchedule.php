<?php
/**
 * Mollie subscription trial, expiry, and prorated billing schedule.
 *
 * Mirrors Square/Stripe subscription schedule behavior for trial-only,
 * expiry-only, and trial + short-expiry plans.
 *
 * @package EverestForms\Pro\Addons\Mollie
 * @since   1.9.16
 */

namespace EverestForms\Pro\Addons\Mollie;

defined( 'ABSPATH' ) || exit;

if ( trait_exists( 'EVF_Subscription_Schedule_Choices', false ) ) :

/**
 * Builds Mollie subscription parameters from subscription plan field choices.
 */
class SubscriptionSchedule {

	use \EVF_Subscription_Schedule_Choices;

	/**
	 * Minimum amount for mandate-only checkout (trial).
	 *
	 * @return string Decimal amount string.
	 */
	public static function mandate_only_amount() {
		$currency = strtoupper( (string) get_option( 'everest_forms_currency', 'EUR' ) );
		$min      = in_array( $currency, array( 'JPY', 'KRW', 'VND' ), true ) ? '1.00' : '0.01';
		return apply_filters( 'everest_forms_mollie_mandate_only_amount', $min, $currency );
	}

	/**
	 * Mollie interval length in seconds.
	 *
	 * @param string $interval_type  DAYS|WEEKS|MONTHS|YEARS.
	 * @param int    $interval_count Interval multiplier.
	 * @return int
	 */
	public static function mollie_interval_seconds( $interval_type, $interval_count ) {
		$interval_count = max( 1, absint( $interval_count ) );
		$interval_type  = strtoupper( (string) $interval_type );

		switch ( $interval_type ) {
			case 'DAYS':
				return DAY_IN_SECONDS * $interval_count;
			case 'WEEKS':
				return WEEK_IN_SECONDS * $interval_count;
			case 'YEARS':
				return YEAR_IN_SECONDS * $interval_count;
			case 'MONTHS':
			default:
				return MONTH_IN_SECONDS * $interval_count;
		}
	}

	/**
	 * Format timestamp as Y-m-d for Mollie startDate.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	public static function format_date( $timestamp ) {
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'Y-m-d', $timestamp );
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * First subscription charge date (Y-m-d) after optional trial.
	 *
	 * @param array $settings Parsed settings.
	 * @return string
	 */
	public static function billing_start_date( $settings ) {
		return self::format_date( self::billing_start_timestamp( $settings ) );
	}

	/**
	 * Mollie `times` from billing start through expiry (full-rate cycles).
	 *
	 * @param int    $billing_start_ts Billing start.
	 * @param int    $expiry_end_ts    Expiry end.
	 * @param string $interval_type    DAYS|WEEKS|MONTHS|YEARS.
	 * @param int    $interval_count   Interval multiplier.
	 * @return int
	 */
	public static function times_for_expiry( $billing_start_ts, $expiry_end_ts, $interval_type, $interval_count ) {
		if ( $expiry_end_ts <= $billing_start_ts ) {
			return 1;
		}

		$window         = $expiry_end_ts - $billing_start_ts;
		$period_seconds = self::mollie_interval_seconds( $interval_type, $interval_count );

		return max( 1, (int) ceil( $window / $period_seconds ) );
	}

	/**
	 * Legacy Mollie `times` estimate (approx. one year of cycles).
	 *
	 * @param string $interval_type  DAYS|WEEKS|MONTHS|YEARS.
	 * @param int    $interval_count Interval multiplier.
	 * @return int
	 */
	public static function estimate_yearly_times( $interval_type, $interval_count ) {
		$interval_count = max( 1, absint( $interval_count ) );

		switch ( strtoupper( (string) $interval_type ) ) {
			case 'DAYS':
				$days = $interval_count;
				return max( 1, (int) floor( 365 / $days ) );
			case 'WEEKS':
				$days_in_week = 7 * $interval_count;
				return max( 1, (int) floor( 365 / $days_in_week ) );
			case 'MONTHS':
				$month_in_days = 30 * $interval_count;
				return max( 1, (int) floor( 365 / $month_in_days ) );
			case 'YEARS':
			default:
				$days_in_year = 365 * $interval_count;
				return max( 1, (int) floor( 365 / $days_in_year ) );
		}
	}

	/**
	 * Build schedule for first checkout payment and recurring subscription.
	 *
	 * Case 1: trial only — mandate at checkout; subscription starts after trial at full rate.
	 * Case 2: expiry only — full amount at checkout when no trial; prorated only for short window.
	 * Case 3: trial + short expiry — mandate at checkout; one prorated subscription cycle after trial.
	 *
	 * @param array $plan_row Subscription plan choice row.
	 * @return array
	 */
	public static function build( $plan_row ) {
		$settings = self::parse_choice_settings( $plan_row );
		$settings['period'] = self::normalize_period( $settings['period'] );

		$currency = (string) get_option( 'everest_forms_currency', 'USD' );

		$iv = function_exists( 'evf_subscription_plan_period_to_mollie_interval' )
			? evf_subscription_plan_period_to_mollie_interval( $settings['period'], $settings['interval_count'] )
			: array(
				'interval'       => 'MONTHS',
				'interval_count' => 1,
			);

		$interval_type   = $iv['interval'];
		$interval_count  = $iv['interval_count'];
		$mollie_interval = $interval_count . ' ' . strtolower( $interval_type );

		$billing_start_ts    = self::billing_start_timestamp( $settings );
		$start_date          = self::format_date( $billing_start_ts );
		$plan_amount         = max( 0, (float) $settings['plan_amount'] );
		$subscription_amount = $plan_amount;
		$first_amount        = $plan_amount;
		$times               = self::estimate_yearly_times( $interval_type, $interval_count );
		$use_prorated_expiry = false;
		$checkout_only       = false;
		$cancel_at           = null;
		$entry_status        = 'complete';

		if ( ! empty( $settings['trial_enabled'] ) ) {
			$first_amount = (float) self::mandate_only_amount();
			$entry_status = 'Trialing';
		}

		if ( ! empty( $settings['expiry_enabled'] ) && ! empty( $settings['expiry_date'] ) ) {
			$expiry_end = self::expiry_end_timestamp( $settings['expiry_date'] );
			if ( $expiry_end ) {
				$cancel_at = $expiry_end;
				$prorated = self::calculate_prorated_amount(
					$plan_amount,
					$billing_start_ts,
					$expiry_end,
					$settings['period'],
					$settings['interval_count']
				);

				if (
					null !== $prorated
					&& $prorated > 0
					&& self::is_short_billing_window(
						$billing_start_ts,
						$expiry_end,
						$settings['period'],
						$settings['interval_count']
					)
				) {
					$use_prorated_expiry = true;
					$subscription_amount = round( $prorated, 2 );
					$times               = 1;

					if ( empty( $settings['trial_enabled'] ) ) {
						$first_amount  = $subscription_amount;
						$checkout_only = true;
					}
				} else {
					$times = self::times_for_expiry( $billing_start_ts, $expiry_end, $interval_type, $interval_count );
				}
			}
		}

		$mandate_only              = (float) self::mandate_only_amount();
		$charges_first_at_checkout = empty( $settings['trial_enabled'] ) && $first_amount > $mandate_only;

		if ( $charges_first_at_checkout && ! $checkout_only ) {
			$period_seconds    = self::mollie_interval_seconds( $interval_type, $interval_count );
			$billing_start_ts += $period_seconds;
			$start_date        = self::format_date( $billing_start_ts );
			$times             = max( 1, $times - 1 );
		}

		return array(
			'trial_enabled'         => ! empty( $settings['trial_enabled'] ),
			'expiry_enabled'        => ! empty( $settings['expiry_enabled'] ),
			'expiry_date'           => $settings['expiry_date'],
			'cancel_at'             => $cancel_at,
			'use_prorated_expiry'   => $use_prorated_expiry,
			'checkout_only'         => $checkout_only,
			'entry_status'          => $entry_status,
			'start_date'            => $start_date,
			'times'                 => $times,
			'interval'              => $mollie_interval,
			'plan_amount'           => number_format( $plan_amount, 2, '.', '' ),
			'first_checkout_amount' => number_format( $first_amount, 2, '.', '' ),
			'subscription_amount'   => number_format( $subscription_amount, 2, '.', '' ),
			'currency'              => $currency,
		);
	}
}

endif;
