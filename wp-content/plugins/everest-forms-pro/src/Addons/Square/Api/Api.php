<?php

namespace  EverestForms\Pro\Addons\Square\Api;

use EverestForms\Pro\Addons\Square\SubscriptionSchedule;
use Square\Environment;
use Square\Models\Card;
use Square\Models\Order;
use Square\Models\Money;
use Square\Models\Address;
use Square\Models\Customer;
use Square\Models\OrderSource;
use Square\Models\CatalogItem;
use Square\SquareClientBuilder;
use Square\Models\CatalogObject;
use Square\Models\PhaseRotation;
use Square\Exceptions\ApiException;
use Square\Models\SubscriptionPhase;
use Square\Models\CreateOrderRequest;
use Square\Models\CreatePaymentRequest;
use Square\Models\CatalogItemVariation;
use Square\Models\CreateCustomerRequest;
use Square\Models\CatalogSubscriptionPlan;
use Square\Models\CreateCustomerCardRequest;
use Square\Models\CreateSubscriptionRequest;
use Square\Models\UpsertCatalogObjectRequest;
use Square\Models\CreateCardRequest;
use Square\Models\SubscriptionPricing;
use Square\Models\CatalogSubscriptionPlanVariation;
use Square\Models\CatalogDiscount;
use Square\Models\Builders\CatalogQueryBuilder;
use Square\Models\Builders\CatalogQueryTextBuilder;
use Square\Models\Builders\SearchCatalogObjectsRequestBuilder;
use Square\Authentication\BearerAuthCredentialsBuilder;
use Square\Http\ApiResponse;
use Square\Models\UpsertCatalogObjectResponse;

class Api {

	/**
	 * Access token.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private static $access_token;


	/**
	 * Item.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $item_data;

	/**
	 * Customer.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $customer;

	/**
	 * Variation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $variation;

	/**
	 * Card.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $card;

	/**
	 * Plan.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $plan;

	/**
	 * Subscription.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $subscription;

	/**
	 * Idempotancy Key.
	 *
	 * @since 1.0.0
	 */
	private static $idempotency_key;

	/**
	 * Location id.
	 *
	 * @since 1.0.0
	 */
	private static $location_id;
	/**
	 * Create client instance.
	 *
	 * @since 1.0.0
	 *
	 * @return mixed CLient instance.
	 */
	public static function get_client() {
		$mode               = 'yes' === get_option( 'everest_forms_pro_square_test_mode' ) ? 'test' : 'live';
		self::$access_token = get_option( 'everest_forms_square_' . $mode . '_access_token' );

		$client = 'yes' === get_option( 'everest_forms_pro_square_test_mode' ) ? SquareClientBuilder::init()
		->bearerAuthCredentials(
			BearerAuthCredentialsBuilder::init(
				self::$access_token
			)
		)
				->environment( Environment::SANDBOX )
				->build() : SquareClientBuilder::init()
				->bearerAuthCredentials(
					BearerAuthCredentialsBuilder::init(
						self::$access_token
					)
				)
						->environment( Environment::PRODUCTION )
						->build();
			return $client;
	}

	/**
	 * Create payment.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $payment_details Payment Details.
	 */
	public static function create_payment( $payment_details ) {
		$client = self::get_client();

		$source_id             = sanitize_text_field( wp_unslash( $payment_details['source_id'] ) );
		self::$idempotency_key = $payment_details['idempotency_key'];
		self::$location_id     = $payment_details['location_id'];
		$total_amount          = (float) ( empty( $payment_details['payment_data']['total'] ) ? 0 : $payment_details['payment_data']['total'] );
		$form_id               = empty( $payment_details['payment_data']['form_id'] ) ? '' : $payment_details['payment_data']['form_id'];
		$form                  = EVF()->form->get( $form_id );
		$form_data             = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : '';
		$payments              = isset( $form_data['payments'] ) ? $form_data['payments'] : array();
		$currency              = get_option( 'everest_forms_currency', 'USD' );

		$square_active_api = ( isset( $payments['square']['enable_square'] ) && '1' == $payments['square']['enable_square'] ) || ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) ) );
		if ( ! $square_active_api ) {
			wp_send_json_error(
				array(
					'detail' => 'Square payment is not enabled.'
				)
			);
		}

		// Guard before customer lookup — avoids unnecessary API call when total is invalid.
		if ( $total_amount <= 0 ) {
			return wp_send_json_error(
				array( 'message' => __( 'Invalid payment total.', 'everest-forms-pro' ) )
			);
		}

		$customer_details = self::create_customer( $payment_details, $payments );

		if ( is_array( $customer_details ) && isset( $customer_details['customer_id'] ) ) {
			$customer_id = $customer_details['customer_id'];
		} elseif ( is_array( $customer_details ) && ! empty( $customer_details[0]->getDetail() ) ) {
			wp_send_json_error( $customer_details );
		} else {
			$customer_id = $customer_details->getCustomer()->getId();
		}

		$amount_money = new Money();
		$amount_money->setAmount( (int) round( $total_amount * 100 ) );
		$amount_money->setCurrency( $currency );

		$body = new CreatePaymentRequest( $source_id, self::$idempotency_key );

		$body->setCustomerId( $customer_id );
		$body->setAmountMoney( $amount_money );
		$body->setLocationId( self::$location_id );

		$payment_response = $client->getPaymentsApi()->createPayment( $body );

		if ( $payment_response->isSuccess() ) {
			$result = $payment_response->getResult();
			wp_send_json_success( $result );
		} else {
			$errors = $payment_response->getErrors();
			wp_send_json_error( $errors );
		}

	}


	/**
	 * Create customer.
	 *
	 * @since 1.7.5
	 *
	 * @param  array  $payment_details Payment details from user.
	 * @param  array  $payments List of payments in everest forms.
	 * @param  string $email Email.
	 */
	public static function create_customer( $payment_details, $payments ) {
		$client = self::get_client();

		$payment_data    = $payment_details['payment_data']['data'];
		$customer_fields = array(
			'first_name'      => empty( $payments['square']['customer_first_name'] ) ? '' : $payments['square']['customer_first_name'],
			'last_name'       => empty( $payments['square']['customer_last_name'] ) ? '' : $payments['square']['customer_last_name'],
			'billing_address' => empty( $payments['square']['customer_billing_address'] ) ? '' : $payments['square']['customer_billing_address'],
			'email'           => empty( $payments['square']['customer_email'] ) ? '' : $payments['square']['customer_email'],
			'phone'           => empty( $payments['square']['customer_phone'] ) ? '' : $payments['square']['customer_phone'],
		);

		$customer_info = array(
			'first_name'      => '',
			'last_name'       => '',
			'billing_address' => new Address(),
			'email'           => '',
			'phone'           => '',
		);

		foreach ( $payment_data as $data ) {
			foreach ( $customer_fields as $field => $field_id ) {
				if ( $field_id && strpos( $data['name'], $field_id ) !== false ) {
					switch ( $field ) {
						case 'first_name':
						case 'last_name':
						case 'email':
						case 'phone':
							$customer_info[ $field ] = $data['value'];
							break;
						case 'billing_address':
							self::set_address_field( $customer_info['billing_address'], $data['name'], $data['value'] );
							break;
					}
				}
			}
		}

		$customers_lists = self::list_of_customers();

		// list_of_customers() returns a plain array of errors on failure, or a result object on success.
		if ( is_array( $customers_lists ) ) {
			wp_send_json_error( $customers_lists );
		}

		if ( ! empty( $customers_lists->getErrors() ) ) {
			wp_send_json_error( $customers_lists->getErrors() );
		}

		// Only match existing customers if we have a non-empty email — an empty email would match any customer with no email set.
		if ( ! empty( $customer_info['email'] ) ) {
			foreach ( $customers_lists->getCustomers() as $customer ) {
				if ( $customer->getEmailAddress() === $customer_info['email'] ) {
					return array( 'customer_id' => $customer->getId() );
				}
			}
		}

		$body = new CreateCustomerRequest();
		$body->setGivenName( $customer_info['first_name'] );
		$body->setFamilyName( $customer_info['last_name'] );
		$body->setEmailAddress( $customer_info['email'] );
		$body->setAddress( $customer_info['billing_address'] );
		if ( ! empty( $customer_info['phone'] ) ) {
			$body->setPhoneNumber( $customer_info['phone'] );
		}

		$create_customer_response = $client->getCustomersApi()->createCustomer( $body );

		if ( $create_customer_response->isSuccess() ) {
			return $create_customer_response->getResult();
		} else {
			return $create_customer_response->getErrors();
		}
	}

	/**
	 * Set address fields.
	 *
	 * @param Address $address Address Object.
	 * @param string  $name Name of field.
	 * @param string  $value Value of field.
	 */
	protected static function set_address_field( Address $address, $name, $value ) {
		if ( strpos( $name, 'address1' ) !== false ) {
			$address->setAddressLine1( $value );
		} elseif ( strpos( $name, 'address2' ) !== false ) {
			$address->setAddressLine2( $value );
		} elseif ( strpos( $name, 'city' ) !== false ) {
			$address->setLocality( $value );
		} elseif ( strpos( $name, 'postal' ) !== false ) {
			$address->setPostalCode( $value );
		} elseif ( strpos( $name, 'country' ) !== false ) {
			$address->setCountry( $value );
		}
	}

	/**
	 * List of customers.
	 *
	 * @since 1.7.5
	 */
	protected static function list_of_customers() {
		$client       = self::get_client();
		$api_response = $client->getCustomersApi()->listCustomers();

		if ( $api_response->isSuccess() ) {
			return $api_response->getResult();
		} else {
			return $api_response->getErrors();
		}
	}

	/**
	 * Save a tokenised card to a Square customer (Cards API).
	 *
	 * @param string $customer_id     Square customer ID.
	 * @param string $source_id       Card nonce/token from Square Web Payments SDK.
	 * @param string $idempotency_key Unique key for this request.
	 * @return string card_id on success.
	 * @throws \Exception On API failure.
	 */
	public static function save_customer_card( $customer_id, $source_id, $idempotency_key ) {
		$client = self::get_client();

		$card = new Card();
		$card->setCustomerId( $customer_id );

		$body = new CreateCardRequest( $idempotency_key, $source_id, $card );

		$response = $client->getCardsApi()->createCard( $body );

		if ( $response->isSuccess() ) {
			return $response->getResult()->getCard()->getId();
		}

		$errors = $response->getErrors();
		$detail = ! empty( $errors[0] ) ? $errors[0]->getDetail() : 'Card save failed';
		throw new \Exception( esc_html( $detail ) );
	}

	/**
	 * Map EVF recurring period to Square SubscriptionCadence value.
	 *
	 * @var array
	 */
	private static $cadence_map = array(
		'day'   => 'DAILY',
		'week'  => 'WEEKLY',
		'month' => 'MONTHLY',
		'year'  => 'ANNUAL',
	);

	/**
	 * Parse subscription plan choice settings (trial + expiry) from form field data.
	 *
	 * @param array $choice Plan choice row from form_fields.
	 * @return array
	 */
	public static function parse_choice_plan_args( $choice ) {
		$choice   = is_array( $choice ) ? $choice : array();
		$schedule = SubscriptionSchedule::build( $choice );

		return self::plan_args_from_schedule( $schedule, $choice );
	}

	/**
	 * Map built schedule to legacy plan args used by Square API helpers.
	 *
	 * @param array $schedule Built schedule.
	 * @param array $choice   Original plan choice (optional).
	 * @return array
	 */
	public static function plan_args_from_schedule( array $schedule, $choice = array() ) {
		$settings = SubscriptionSchedule::parse_choice_settings( is_array( $choice ) ? $choice : array() );

		return array(
			'trial_enabled'        => ! empty( $schedule['trial_enabled'] ),
			'trial_period'         => isset( $settings['trial_period'] ) ? $settings['trial_period'] : 'week',
			'trial_interval_count' => isset( $settings['trial_interval_count'] ) ? max( 1, absint( $settings['trial_interval_count'] ) ) : 1,
			'expiry_enabled'       => ! empty( $schedule['expiry_enabled'] ),
			'expiry_date'          => isset( $schedule['expiry_date'] ) ? (string) $schedule['expiry_date'] : '',
			'use_prorated_expiry'  => ! empty( $schedule['use_prorated_expiry'] ),
			'subscription_amount'  => isset( $schedule['subscription_amount'] ) ? (float) $schedule['subscription_amount'] : 0,
			'plan_amount'          => isset( $schedule['plan_amount'] ) ? (float) $schedule['plan_amount'] : 0,
			'paid_periods'         => isset( $schedule['paid_periods'] ) ? $schedule['paid_periods'] : null,
		);
	}

	/**
	 * Deterministic hash for plan config (used to detect catalog resync needs).
	 *
	 * @param string $label          Plan label.
	 * @param float  $amount         Amount.
	 * @param string $period         Billing period key.
	 * @param int    $interval_count Interval count.
	 * @param array  $args           Trial/expiry args from parse_choice_plan_args().
	 * @return string
	 */
	public static function get_plan_config_hash( $label, $amount, $period, $interval_count, $args = array() ) {
		$args         = is_array( $args ) ? $args : array();
		$charge_amount = isset( $args['subscription_amount'] ) && $args['subscription_amount'] > 0
			? (float) $args['subscription_amount']
			: (float) $amount;

		return md5(
			wp_json_encode(
				array(
					'label'          => $label,
					'amount'         => number_format( (float) $amount, 2, '.', '' ),
					'charge_amount'  => number_format( $charge_amount, 2, '.', '' ),
					'period'         => $period,
					'interval_count' => max( 1, absint( $interval_count ) ),
					'trial'          => ! empty( $args['trial_enabled'] ) ? array(
						$args['trial_period'],
						$args['trial_interval_count'],
						'static_zero_v2',
					) : false,
					'expiry'         => ! empty( $args['expiry_enabled'] ) ? array(
						$args['expiry_date'],
						! empty( $args['use_prorated_expiry'] ) ? 'prorated' : 'recurring',
						'prorate_v2',
					) : false,
				)
			)
		);
	}

	/**
	 * Format expiry date for Square canceled_date (YYYY-MM-DD).
	 *
	 * @param string $expiry_date Date from flatpickr (Y-m-d).
	 * @return string|null
	 */
	public static function format_subscription_expiry_date( $expiry_date ) {
		if ( empty( $expiry_date ) ) {
			return null;
		}
		$timestamp = strtotime( $expiry_date );
		if ( ! $timestamp ) {
			return null;
		}
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'Y-m-d', $timestamp );
		}
		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Catalog discount ID used for 100% off during subscription trial phases (Square-documented pattern).
	 *
	 * @return string Square catalog DISCOUNT object id.
	 * @throws \Exception On API failure.
	 */
	public static function get_or_create_trial_discount_id() {
		static $discount_id = null;

		if ( null !== $discount_id ) {
			return $discount_id;
		}

		$client           = self::get_client();
		$temp_discount_id = '#evf_square_trial_100pct';
		$discount_data    = new CatalogDiscount();
		$discount_data->setName( 'EVF Subscription Trial (100% off)' );
		$discount_data->setDiscountType( 'FIXED_PERCENTAGE' );
		$discount_data->setPercentage( '100' );

		$discount_catalog_obj = new CatalogObject( 'DISCOUNT', $temp_discount_id );
		$discount_catalog_obj->setDiscountData( $discount_data );

		$idempotency_key = md5( 'evf_square_trial_discount_100pct_v1' );
		$request         = new UpsertCatalogObjectRequest( $idempotency_key, $discount_catalog_obj );
		$response        = $client->getCatalogApi()->upsertCatalogObject( $request );

		if ( $response->isSuccess() ) {
			$discount_id = $response->getResult()->getCatalogObject()->getId();
			return $discount_id;
		}

		$errors = $response->getErrors();
		$detail = ! empty( $errors[0] ) ? $errors[0]->getDetail() : 'Trial discount catalog setup failed';
		throw new \Exception( esc_html( $detail ) );
	}

	/**
	 * Apply subscription expiry to a CreateSubscriptionRequest.
	 *
	 * @param CreateSubscriptionRequest $request     Square request object.
	 * @param array                     $plan_args   Parsed plan args.
	 */
	public static function apply_subscription_expiry( $request, $plan_args ) {
		if ( empty( $plan_args['expiry_enabled'] ) || empty( $plan_args['expiry_date'] ) ) {
			return;
		}
		$canceled_date = self::format_subscription_expiry_date( $plan_args['expiry_date'] );
		if ( $canceled_date ) {
			$request->setCanceledDate( $canceled_date );
		}
	}

	/**
	 * Whether the plan variation should allow Square proration (partial period billing).
	 *
	 * @param array $args Parsed plan args.
	 * @return bool
	 */
	public static function should_prorate_for_expiry( $args ) {
		$args = is_array( $args ) ? $args : array();
		return ! empty( $args['use_prorated_expiry'] );
	}

	/**
	 * Whether catalog/subscription should allow Square partial-period proration on the last cycle.
	 *
	 * @param array $args Parsed plan args.
	 * @return bool
	 */
	public static function should_allow_expiry_proration( $args ) {
		$args = is_array( $args ) ? $args : array();
		return ! empty( $args['expiry_enabled'] ) && ! empty( $args['expiry_date'] );
	}

	/**
	 * Set subscription start_date so the first charge happens at signup (matches Stripe first invoice).
	 *
	 * Case 2 (expiry only, no trial): start today → immediate $7 charge, canceled_date on expiry.
	 * Case 3 (trial): catalog $0 trial phases handle timing; deferred fallback uses apply_subscription_trial_start.
	 *
	 * @param CreateSubscriptionRequest $request   Square request object.
	 * @param array                     $plan_args Parsed plan args.
	 */
	public static function apply_subscription_billing_start( $request, $plan_args ) {
		if ( ! empty( $plan_args['trial_enabled'] ) ) {
			if ( self::$defer_trial_to_start_date ) {
				return;
			}
			// Catalog trial phases delay paid billing — do not override with start_date.
			return;
		}

		if ( function_exists( 'wp_date' ) ) {
			$request->setStartDate( wp_date( 'Y-m-d' ) );
		} else {
			$request->setStartDate( gmdate( 'Y-m-d' ) );
		}
	}

	/**
	 * @deprecated Use apply_subscription_billing_start().
	 * @param CreateSubscriptionRequest $request   Square request object.
	 * @param array                     $plan_args Parsed plan args.
	 */
	public static function apply_subscription_proration_start( $request, $plan_args ) {
		self::apply_subscription_billing_start( $request, $plan_args );
	}

	/**
	 * Configure catalog plan variation options for expiry (proration, monthly anchor).
	 *
	 * @param CatalogSubscriptionPlanVariation $variation_data Plan variation model.
	 * @param array                            $args           Parsed plan args.
	 * @param string                           $period         EVF period key.
	 */
	private static function configure_plan_variation_options( $variation_data, $args, $period ) {
		if ( ! self::should_allow_expiry_proration( $args ) ) {
			return;
		}

		$variation_data->setCanProrate( true );

		// Monthly plans: anchor billing to the expiry day so partial periods align with canceled_date.
		if ( 'month' === $period ) {
			$timestamp = strtotime( $args['expiry_date'] );
			if ( $timestamp ) {
				$anchor_day = (int) gmdate( 'j', $timestamp );
				if ( $anchor_day >= 1 && $anchor_day <= 31 ) {
					$variation_data->setMonthlyBillingAnchorDate( $anchor_day );
				}
			}
		}
	}

	/**
	 * Delay first billing when catalog trial phases could not be created (fallback).
	 *
	 * @param CreateSubscriptionRequest $request   Square request object.
	 * @param array                     $plan_args Parsed plan args.
	 */
	public static function apply_subscription_trial_start( $request, $plan_args ) {
		if ( empty( $plan_args['trial_enabled'] ) ) {
			return;
		}

		$interval = max( 1, absint( $plan_args['trial_interval_count'] ) );
		$period   = isset( $plan_args['trial_period'] ) ? $plan_args['trial_period'] : 'week';
		$timestamp = strtotime( '+' . $interval . ' ' . $period, current_time( 'timestamp' ) );

		if ( ! $timestamp ) {
			return;
		}

		if ( function_exists( 'wp_date' ) ) {
			$start_date = wp_date( 'Y-m-d', $timestamp );
		} else {
			$start_date = gmdate( 'Y-m-d', $timestamp );
		}

		if ( $start_date ) {
			$request->setStartDate( $start_date );
		}
	}

	/**
	 * Resolve catalog object id from an upsert response (handles client temp ids).
	 *
	 * @param ApiResponse $response         Square HTTP API response wrapper.
	 * @param string      $client_object_id Optional client id to match.
	 * @return string
	 */
	private static function catalog_upsert_object_id( ApiResponse $response, $client_object_id = '' ) {
		$result = $response->getResult();
		if ( ! $result instanceof UpsertCatalogObjectResponse ) {
			return '';
		}

		$object = $result->getCatalogObject();
		if ( $object && $object->getId() && '#' !== substr( $object->getId(), 0, 1 ) ) {
			return $object->getId();
		}

		$mappings = $result->getIdMappings();
		if ( ! empty( $mappings ) ) {
			foreach ( $mappings as $mapping ) {
				if ( $client_object_id && $mapping->getClientObjectId() === $client_object_id ) {
					return (string) $mapping->getObjectId();
				}
			}
			if ( ! empty( $mappings[0]->getObjectId() ) ) {
				return (string) $mappings[0]->getObjectId();
			}
		}

		return $object && $object->getId() ? (string) $object->getId() : '';
	}

	/**
	 * Extract a human-readable error from a Square API response.
	 *
	 * @param ApiResponse $response Square HTTP API response wrapper.
	 * @param string      $fallback Default message.
	 * @return string
	 */
	private static function square_response_error_detail( ApiResponse $response, $fallback ) {
		$errors = $response->getErrors();
		if ( empty( $errors[0] ) ) {
			return $fallback;
		}
		$detail = $errors[0]->getDetail();
		$code   = $errors[0]->getCode();
		if ( $detail && $code ) {
			return $detail . ' (' . $code . ')';
		}
		return $detail ? $detail : $fallback;
	}

	/**
	 * Build catalog phases (optional $0 trial, then paid recurring phase).
	 *
	 * @param string $cadence        Square cadence for paid phase.
	 * @param float  $amount         Paid amount (major units).
	 * @param string $currency       Currency code.
	 * @param array  $args           Parsed plan args.
	 * @return SubscriptionPhase[]
	 */
	private static function build_subscription_phases( $cadence, $amount, $currency, $args ) {
		$phases  = array();
		$ordinal = 0;

		$money = new Money();
		$money->setAmount( (int) round( (float) $amount * 100 ) );
		$money->setCurrency( $currency );

		if ( ! empty( $args['trial_enabled'] ) ) {
			$trial_cadence = isset( self::$cadence_map[ $args['trial_period'] ] ) ? self::$cadence_map[ $args['trial_period'] ] : 'WEEKLY';

			// Flat-rate plans (no order template) must use STATIC pricing; RELATIVE requires catalog line items.
			$trial_money = new Money();
			$trial_money->setAmount( 0 );
			$trial_money->setCurrency( $currency );

			$trial_pricing = new SubscriptionPricing();
			$trial_pricing->setType( 'STATIC' );
			$trial_pricing->setPriceMoney( $trial_money );

			$trial_phase = new SubscriptionPhase( $trial_cadence );
			$trial_phase->setPeriods( max( 1, absint( $args['trial_interval_count'] ) ) );
			$trial_phase->setOrdinal( $ordinal );
			$trial_phase->setPricing( $trial_pricing );

			$phases[] = $trial_phase;
			++$ordinal;
		}

		$paid_pricing = new SubscriptionPricing();
		$paid_pricing->setType( 'STATIC' );
		$paid_pricing->setPriceMoney( $money );

		$paid_phase = new SubscriptionPhase( $cadence );
		$paid_phase->setOrdinal( $ordinal );
		$paid_phase->setPricing( $paid_pricing );

		if ( ! empty( $args['use_prorated_expiry'] ) && ! empty( $args['paid_periods'] ) ) {
			$paid_phase->setPeriods( max( 1, absint( $args['paid_periods'] ) ) );
		}

		$phases[] = $paid_phase;

		return $phases;
	}

	/**
	 * Create or update a Square Catalog subscription plan + variation.
	 *
	 * Uses a deterministic idempotency key so repeated saves with the same
	 * label/amount/period do not create duplicate catalog objects.
	 *
	 * @param string $label          Plan display name (from subscription plan field choice).
	 * @param float  $amount         Billing amount in major currency units (e.g. 9.99).
	 * @param string $period         EVF period key: day|week|month|year.
	 * @param int    $interval_count Billing interval (e.g. 1 = every 1 month).
	 * @param array  $args           Optional. Trial/expiry settings from parse_choice_plan_args().
	 * @return string planVariationId returned by Square.
	 * @throws \Exception On API failure.
	 */
	public static function upsert_subscription_plan( $label, $amount, $period, $interval_count, $args = array(), $retry_without_trial = false ) {
		$client           = self::get_client();
		$currency         = get_option( 'everest_forms_currency', 'USD' );
		$cadence          = isset( self::$cadence_map[ $period ] ) ? self::$cadence_map[ $period ] : 'MONTHLY';
		$interval_count   = max( 1, absint( $interval_count ) );
		$args             = is_array( $args ) ? $args : array();
		$charge_amount    = isset( $args['subscription_amount'] ) && $args['subscription_amount'] > 0
			? (float) $args['subscription_amount']
			: (float) $amount;
		$amount_key       = number_format( $charge_amount, 2, '.', '' );
		$plan_fingerprint = self::get_plan_config_hash( $label, $amount, $period, $interval_count, $args );

		try {
			$phases = self::build_subscription_phases( $cadence, $charge_amount, $currency, $args );
		} catch ( \Exception $e ) {
			if ( ! empty( $args['trial_enabled'] ) && ! $retry_without_trial ) {
				$args_no_trial = $args;
				$args_no_trial['trial_enabled'] = false;
				$variation_id = self::upsert_subscription_plan( $label, $amount, $period, $interval_count, $args_no_trial, true );
				self::$defer_trial_to_start_date = true;
				return $variation_id;
			}
			throw $e;
		}

		// Step 1: plan shell (stable id per label — variations are upserted separately).
		$temp_plan_id      = '#plan_' . md5( $label );
		$plan_catalog_obj  = new CatalogObject( 'SUBSCRIPTION_PLAN', $temp_plan_id );
		$plan_data         = new CatalogSubscriptionPlan( $label );
		$plan_catalog_obj->setSubscriptionPlanData( $plan_data );
		$plan_idempotency  = md5( 'evf_square_plan_shell_' . md5( $label ) );

		$plan_request  = new UpsertCatalogObjectRequest( $plan_idempotency, $plan_catalog_obj );
		$plan_response = $client->getCatalogApi()->upsertCatalogObject( $plan_request );

		if ( ! $plan_response->isSuccess() ) {
			$detail = self::square_response_error_detail( $plan_response, 'Subscription plan catalog setup failed' );
			if ( ! empty( $plan_response->getErrors()[0] ) && 'IDEMPOTENCY_KEY_REUSED' === $plan_response->getErrors()[0]->getCode() ) {
				$existing_variation_id = self::find_subscription_plan_variation_id( $label, $amount_key, $cadence );
				if ( $existing_variation_id ) {
					return $existing_variation_id;
				}
			}
			throw new \Exception( esc_html( $detail ) );
		}

		$square_plan_id = self::catalog_upsert_object_id( $plan_response, $temp_plan_id );
		if ( empty( $square_plan_id ) ) {
			throw new \Exception( esc_html__( 'Square did not return a subscription plan ID.', 'everest-forms-pro' ) );
		}

		// Step 2: variation linked to the plan (new fingerprint when trial/expiry/amount changes).
		$temp_variation_id     = '#variation_' . $plan_fingerprint;
		$variation_data = new CatalogSubscriptionPlanVariation( $label, $phases );
		$variation_data->setSubscriptionPlanId( $square_plan_id );
		self::configure_plan_variation_options( $variation_data, $args, $period );

		$variation_catalog_obj = new CatalogObject( 'SUBSCRIPTION_PLAN_VARIATION', $temp_variation_id );
		$variation_catalog_obj->setSubscriptionPlanVariationData( $variation_data );

		$variation_idempotency = md5( 'evf_square_plan_' . $plan_fingerprint );
		$variation_request     = new UpsertCatalogObjectRequest( $variation_idempotency, $variation_catalog_obj );
		$variation_response    = $client->getCatalogApi()->upsertCatalogObject( $variation_request );

		if ( $variation_response->isSuccess() ) {
			$variation_id = self::catalog_upsert_object_id( $variation_response, $temp_variation_id );
			if ( ! empty( $variation_id ) ) {
				self::$defer_trial_to_start_date = false;
				return $variation_id;
			}
		}

		$detail = self::square_response_error_detail( $variation_response, 'Plan variation upsert failed' );

		if ( ! empty( $args['trial_enabled'] ) && ! $retry_without_trial ) {
			$args_no_trial = $args;
			$args_no_trial['trial_enabled'] = false;
			$variation_id = self::upsert_subscription_plan( $label, $amount, $period, $interval_count, $args_no_trial, true );
			self::$defer_trial_to_start_date = true;
			return $variation_id;
		}

		if ( ! empty( $variation_response->getErrors()[0] ) && 'IDEMPOTENCY_KEY_REUSED' === $variation_response->getErrors()[0]->getCode() ) {
			$existing_variation_id = self::find_subscription_plan_variation_id( $label, $amount_key, $cadence );
			if ( $existing_variation_id ) {
				return $existing_variation_id;
			}
		}

		throw new \Exception( esc_html( $detail ) );
	}

	/**
	 * When true, the next subscription create should use start_date instead of catalog trial phases.
	 *
	 * @var bool
	 */
	public static $defer_trial_to_start_date = false;

	/**
	 * Find an existing subscription plan variation in the Square catalog by plan label.
	 *
	 * @param string $label      Plan display name.
	 * @param string $amount_key Normalized amount (e.g. "10.00").
	 * @param string $cadence    Square cadence (MONTHLY, etc.).
	 * @return string|null planVariationId or null.
	 */
	private static function find_subscription_plan_variation_id( $label, $amount_key, $cadence ) {
		$client = self::get_client();
		$body   = SearchCatalogObjectsRequestBuilder::init()
			->objectTypes( array( 'SUBSCRIPTION_PLAN' ) )
			->query(
				CatalogQueryBuilder::init()
					->textQuery( CatalogQueryTextBuilder::init( array( $label ) )->build() )
					->build()
			)
			->limit( 100 )
			->build();

		$response = $client->getCatalogApi()->searchCatalogObjects( $body );

		if ( ! $response->isSuccess() ) {
			return null;
		}

		$objects = $response->getResult()->getObjects();
		if ( empty( $objects ) ) {
			return null;
		}

		$target_amount = (int) round( (float) $amount_key * 100 );

		foreach ( $objects as $object ) {
			$plan_data = $object->getSubscriptionPlanData();
			if ( ! $plan_data || $plan_data->getName() !== $label ) {
				continue;
			}
			$variations = $plan_data->getSubscriptionPlanVariations();
			if ( empty( $variations ) ) {
				continue;
			}
			foreach ( $variations as $variation ) {
				$variation_data = $variation->getSubscriptionPlanVariationData();
				if ( ! $variation_data ) {
					continue;
				}
				$phases = $variation_data->getPhases();
				if ( empty( $phases[0] ) ) {
					continue;
				}
				$matched = false;
				foreach ( $phases as $phase ) {
					if ( $phase->getCadence() !== $cadence ) {
						continue;
					}
					$pricing = $phase->getPricing();
					if ( ! $pricing || ! $pricing->getPriceMoney() ) {
						continue;
					}
					if ( (int) $pricing->getPriceMoney()->getAmount() === $target_amount ) {
						$matched = true;
						break;
					}
				}
				if ( $matched ) {
					return $variation->getId();
				}
			}
		}

		return null;
	}
}
