<?php
/**
 * Square subscription trial, expiry, and prorated billing schedule.
 *
 * Mirrors EVF_Stripe_Subscription_Schedule so Square catalog + subscriptions
 * behave the same for trial-only, expiry-only, and trial + short-expiry plans.
 *
 * @package EverestForms\Pro\Addons\Square
 * @since   1.9.16
 */

namespace EverestForms\Pro\Addons\Square;

defined( 'ABSPATH' ) || exit;

if ( trait_exists( 'EVF_Subscription_Schedule_Choices', false ) ) :

/**
 * Builds Square catalog/subscription parameters from subscription plan choices.
 */
class SubscriptionSchedule {

	use \EVF_Subscription_Schedule_Choices;

	/**
	 * Build schedule for Square catalog phases and subscription requests.
	 *
	 * Case 1: trial only — $0 trial phase, then full plan amount each period.
	 * Case 2: expiry only — full amount; prorated single phase only if short window.
	 * Case 3: trial + short expiry — trial phase, then one prorated paid phase; canceled_date on expiry.
	 *
	 * @param array $plan_row Plan choice row.
	 * @return array
	 */
	public static function build( $plan_row ) {
		$settings            = self::parse_choice_settings( $plan_row );
		$settings['period']  = self::normalize_period( $settings['period'] );
		$plan_amount         = max( 0, (float) $settings['plan_amount'] );
		$billing_start_ts    = self::billing_start_timestamp( $settings );
		$subscription_amount = $plan_amount;
		$use_prorated_expiry = false;
		$prorated_amount     = null;
		$paid_periods        = null;
		$cancel_at           = null;

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
					$prorated_amount     = round( $prorated, 2 );
					$subscription_amount = $prorated_amount;
					$paid_periods        = 1;
				}
			}
		}

		return array(
			'trial_enabled'       => ! empty( $settings['trial_enabled'] ),
			'trial_period'        => isset( $settings['trial_period'] ) ? $settings['trial_period'] : 'week',
			'trial_interval_count' => isset( $settings['trial_interval_count'] ) ? $settings['trial_interval_count'] : 1,
			'expiry_enabled'      => ! empty( $settings['expiry_enabled'] ),
			'expiry_date'         => $settings['expiry_date'],
			'cancel_at'           => $cancel_at,
			'use_prorated_expiry' => $use_prorated_expiry,
			'prorated_amount'     => $prorated_amount,
			'subscription_amount' => $subscription_amount,
			'plan_amount'         => $plan_amount,
			'period'              => $settings['period'],
			'interval_count'      => $settings['interval_count'],
			'paid_periods'        => $paid_periods,
			'entry_trialing'      => ! empty( $settings['trial_enabled'] ),
		);
	}
}

endif;
