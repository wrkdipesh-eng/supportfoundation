# Square Subscription Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Square recurring subscription support to the Square addon so that when a form contains a `payment-subscription-plan` field and Square is enabled, the payment flow creates a Square subscription instead of a one-time charge.

**Architecture:** Mirror Razorpay's pattern — sync `payment-subscription-plan` choices as Square Catalog objects on form save, intercept the submit button click to tokenize the card and create the subscription before form submission, then inject the `subscription_id` as a hidden input so `process_entry` can record it. No new files; four existing files modified.

**Tech Stack:** PHP 7.2+, Square PHP SDK (`vendor/square/square`), jQuery/ES2017 async-await (existing Square JS), WordPress AJAX

---

## File Map

| File | What changes |
|------|-------------|
| `src/Addons/Square/Api/Api.php` | Add `save_customer_card()`, `upsert_subscription_plan()` static methods; add two `use` imports |
| `src/Addons/Square/Process/Process.php` | Register two new AJAX actions + form-save hook in `__construct`; add `sync_square_plans()` and `create_square_subscription()` methods; extend `process_entry()` |
| `includes/fields/class-evf-field-payment-square.php` | In `load_assets()` detect `payment-subscription-plan` field and emit a per-form inline script |
| `src/Addons/Square/assets/js/frontend/evf-square-payment.js` | In `loadSquareCreditCard()` add a submit-button click handler for the subscription flow |

---

## Task 1: Add imports and `save_customer_card()` to Api.php

**Files:**
- Modify: `src/Addons/Square/Api/Api.php`

### What this does
Adds two missing `use` statements and a new static method that stores the tokenised card against a Square customer via the Cards API, returning the `card_id` needed for `CreateSubscriptionRequest`.

- [ ] **Step 1: Add the two missing `use` imports**

Open `src/Addons/Square/Api/Api.php`. After line 26 (`use Square\Models\UpsertCatalogObjectRequest;`) add:

```php
use Square\Models\CreateCardRequest;
use Square\Models\CatalogSubscriptionPlanVariation;
```

File should now have these consecutive lines near the top:
```php
use Square\Models\UpsertCatalogObjectRequest;
use Square\Models\CreateCardRequest;
use Square\Models\CatalogSubscriptionPlanVariation;
use Square\Authentication\BearerAuthCredentialsBuilder;
```

- [ ] **Step 2: Add `save_customer_card()` after `list_of_customers()`**

Append this method inside the `Api` class, after the closing brace of `list_of_customers()` (currently the last method, around line 309):

```php
/**
 * Save a tokenised card to a Square customer (Cards API).
 *
 * @param string $customer_id    Square customer ID.
 * @param string $source_id      Card nonce/token from Square Web Payments SDK.
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
```

- [ ] **Step 3: Manual verify — confirm the method compiles**

```bash
php -l src/Addons/Square/Api/Api.php
```

Expected output: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/Api/Api.php
git commit -m "feat(square): add save_customer_card() API method"
```

---

## Task 2: Add `upsert_subscription_plan()` to Api.php

**Files:**
- Modify: `src/Addons/Square/Api/Api.php`

### What this does
Creates a Square Catalog `SUBSCRIPTION_PLAN` object containing one `SUBSCRIPTION_PLAN_VARIATION` for the given plan label/amount/cadence. Returns the real `planVariationId` from Square's response — this ID is what `CreateSubscriptionRequest` needs.

- [ ] **Step 1: Add cadence map constant and the method**

Append inside the `Api` class, after `save_customer_card()`:

```php
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
 * Create or update a Square Catalog subscription plan + variation.
 *
 * Uses a deterministic idempotency key so repeated saves with the same
 * label/amount/period do not create duplicate catalog objects.
 *
 * @param string $label          Plan display name (from subscription plan field choice).
 * @param float  $amount         Billing amount in major currency units (e.g. 9.99).
 * @param string $period         EVF period key: day|week|month|year.
 * @param int    $interval_count Billing interval (e.g. 1 = every 1 month).
 * @return string planVariationId returned by Square.
 * @throws \Exception On API failure.
 */
public static function upsert_subscription_plan( $label, $amount, $period, $interval_count ) {
    $client   = self::get_client();
    $currency = get_option( 'everest_forms_currency', 'USD' );
    $cadence  = isset( self::$cadence_map[ $period ] ) ? self::$cadence_map[ $period ] : 'MONTHLY';

    // Recurring price money.
    $money = new Money();
    $money->setAmount( (int) round( (float) $amount * 100 ) );
    $money->setCurrency( $currency );

    // One billing phase.
    $phase = new SubscriptionPhase( $cadence );
    $phase->setRecurringPriceMoney( $money );

    // Variation catalog object (temp ID, replaced by Square).
    $temp_variation_id      = '#variation_' . md5( $label . $amount . $cadence . $interval_count );
    $variation_catalog_obj  = new CatalogObject( 'SUBSCRIPTION_PLAN_VARIATION', $temp_variation_id );
    $variation_data         = new CatalogSubscriptionPlanVariation( $label, array( $phase ) );
    $variation_catalog_obj->setSubscriptionPlanVariationData( $variation_data );

    // Plan catalog object (temp ID, replaced by Square).
    $temp_plan_id      = '#plan_' . md5( $label );
    $plan_catalog_obj  = new CatalogObject( 'SUBSCRIPTION_PLAN', $temp_plan_id );
    $plan_data         = new CatalogSubscriptionPlan( $label );
    $plan_data->setSubscriptionPlanVariations( array( $variation_catalog_obj ) );
    $plan_catalog_obj->setSubscriptionPlanData( $plan_data );

    // Deterministic idempotency key — safe to re-run with same inputs.
    $idempotency_key = md5( 'evf_square_plan_' . $label . $amount . $cadence . $interval_count );

    $request  = new UpsertCatalogObjectRequest( $idempotency_key, $plan_catalog_obj );
    $response = $client->getCatalogApi()->upsertCatalogObject( $request );

    if ( $response->isSuccess() ) {
        $returned_plan       = $response->getResult()->getCatalogObject();
        $returned_variations = $returned_plan->getSubscriptionPlanData()->getSubscriptionPlanVariations();
        if ( ! empty( $returned_variations[0] ) ) {
            return $returned_variations[0]->getId();
        }
    }

    $errors = $response->getErrors();
    $detail = ! empty( $errors[0] ) ? $errors[0]->getDetail() : 'Plan upsert failed';
    throw new \Exception( esc_html( $detail ) );
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l src/Addons/Square/Api/Api.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/Api/Api.php
git commit -m "feat(square): add upsert_subscription_plan() API method"
```

---

## Task 3: Add `sync_square_plans()` to Process.php

**Files:**
- Modify: `src/Addons/Square/Process/Process.php`

### What this does
Hooks into `everest_forms_save_form`. When a form with Square enabled and a `payment-subscription-plan` field is saved, syncs each plan choice to Square Catalog and caches `planVariationId` values in `_square_subscription_plans` post meta.

- [ ] **Step 1: Register the `everest_forms_save_form` hook in `__construct`**

In `Process::__construct()`, after the existing `add_action` calls (around line 43), add:

```php
add_action( 'everest_forms_save_form', array( $this, 'sync_square_plans' ), 10, 2 );
```

- [ ] **Step 2: Add the `sync_square_plans()` method**

Append after `is_square_enabled()` (the last method, currently ending around line 234):

```php
/**
 * Sync payment-subscription-plan choices to Square Catalog on form save.
 *
 * Creates or reuses a Square SUBSCRIPTION_PLAN catalog object for each
 * choice and caches the planVariationId in post meta so subscription
 * creation can skip the Catalog API call.
 *
 * @param int   $form_id   Saved form ID.
 * @param array $form_data Freshly saved form data.
 */
public function sync_square_plans( $form_id, $form_data = array() ) {
    if ( empty( $form_data ) ) {
        $form_data = evf()->form->get( $form_id, array( 'content_only' => true ) );
    }

    if ( empty( $form_data ) ) {
        return;
    }

    $payments = isset( $form_data['payments'] ) ? $form_data['payments'] : array();

    $square_active = isset( $payments['square']['enable_square'] ) && '1' === $payments['square']['enable_square'];
    if ( ! $square_active && ! ( function_exists( 'evf_is_gateway_in_selector_allowlist' ) && evf_is_gateway_in_selector_allowlist( array( 'form_data' => $form_data, 'gateway' => 'square' ) ) ) ) {
        return;
    }

    $subscription_plan_field = evf_get_form_data_by_key( $form_data, 'payment-subscription-plan' );
    if ( empty( $subscription_plan_field ) ) {
        return;
    }

    $plan_choices = isset( $subscription_plan_field[0]['choices'] ) ? $subscription_plan_field[0]['choices'] : array();
    if ( empty( $plan_choices ) ) {
        return;
    }

    $existing_plans = get_post_meta( $form_id, '_square_subscription_plans', true );
    $existing_plans = is_array( $existing_plans ) ? $existing_plans : array();
    $updated_plans  = array();

    foreach ( $plan_choices as $choice ) {
        $plan_label     = isset( $choice['label'] ) ? $choice['label'] : '';
        $plan_amount    = isset( $choice['value'] ) ? evf_sanitize_amount( $choice['value'] ) : 0;
        $period         = isset( $choice['recurring_period'] ) ? $choice['recurring_period'] : 'month';
        $interval_count = isset( $choice['interval_count'] ) ? max( 1, absint( $choice['interval_count'] ) ) : 1;

        if ( empty( $plan_label ) || empty( $plan_amount ) ) {
            continue;
        }

        // Reuse cached variation ID if already synced.
        $plan_variation_id = '';
        foreach ( $existing_plans as $ep ) {
            if ( $ep['plan_name'] === $plan_label ) {
                $plan_variation_id = $ep['plan_variation_id'];
                break;
            }
        }

        if ( empty( $plan_variation_id ) ) {
            try {
                $plan_variation_id = \EverestForms\Pro\Addons\Square\Api\Api::upsert_subscription_plan(
                    $plan_label,
                    $plan_amount,
                    $period,
                    $interval_count
                );
            } catch ( \Exception $e ) {
                evf_get_logger()->critical( $e->getMessage(), array( 'source' => 'square-subscription' ) );
                continue;
            }
        }

        $updated_plans[] = array(
            'plan_name'         => $plan_label,
            'plan_variation_id' => $plan_variation_id,
            'period'            => $period,
            'interval_count'    => $interval_count,
        );
    }

    update_post_meta( $form_id, '_square_subscription_plans', $updated_plans );
}
```

- [ ] **Step 3: Verify syntax**

```bash
php -l src/Addons/Square/Process/Process.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Manual test — form save syncs plans**

1. Go to EVF builder → create/open a form
2. Add a `payment-subscription-plan` field; add one choice labelled "Monthly Basic" with value 9.99, period month
3. Enable Square payment in the Payments panel
4. Save form
5. In WP admin: check post meta for the form post. Expected: `_square_subscription_plans` contains `[{ plan_name: 'Monthly Basic', plan_variation_id: 'SQPLAN_VAR_xxx', period: 'month', interval_count: 1 }]`
6. In Square Dashboard (sandbox): verify a SUBSCRIPTION_PLAN catalog object named "Monthly Basic" exists

- [ ] **Step 5: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/Process/Process.php
git commit -m "feat(square): sync subscription plans to Square catalog on form save"
```

---

## Task 4: Add `create_square_subscription()` AJAX handler to Process.php

**Files:**
- Modify: `src/Addons/Square/Process/Process.php`

### What this does
Server-side AJAX endpoint called by the JS before form submission. Creates customer → saves card → looks up plan variation → creates subscription. Returns `subscription_id`.

- [ ] **Step 1: Register AJAX actions in `__construct`**

In `Process::__construct()`, after the hook added in Task 3, add:

```php
add_action( 'wp_ajax_evf_square_create_subscription', array( $this, 'create_square_subscription' ) );
add_action( 'wp_ajax_nopriv_evf_square_create_subscription', array( $this, 'create_square_subscription' ) );
```

- [ ] **Step 2: Add the `create_square_subscription()` method**

Append after `sync_square_plans()`:

```php
/**
 * AJAX: create a Square subscription for the selected plan.
 *
 * Called before form submission. Returns subscription_id to JS which
 * injects it as a hidden input before native form submit.
 */
public function create_square_subscription() {
    check_ajax_referer( 'evf_square_payment_nonce', 'security' );

    try {
        $form_id        = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
        $field_id       = isset( $_POST['field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['field_id'] ) ) : '';
        $choice_key     = isset( $_POST['choice_key'] ) ? sanitize_text_field( wp_unslash( $_POST['choice_key'] ) ) : '';
        $source_id      = isset( $_POST['source_id'] ) ? sanitize_text_field( wp_unslash( $_POST['source_id'] ) ) : '';
        $idempotency_key = isset( $_POST['idempotency_key'] ) ? sanitize_text_field( wp_unslash( $_POST['idempotency_key'] ) ) : '';
        $location_id    = isset( $_POST['location_id'] ) ? sanitize_text_field( wp_unslash( $_POST['location_id'] ) ) : '';
        $payment_data   = isset( $_POST['payment_data'] ) ? json_decode( wp_unslash( $_POST['payment_data'] ), true ) : array();

        if ( ! $form_id || ! $source_id || ! $idempotency_key || ! $location_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Missing required parameters.', 'everest-forms-pro' ) ) );
        }

        $form      = EVF()->form->get( $form_id );
        $form_data = ! empty( $form->post_content ) ? evf_decode( $form->post_content ) : array();
        $payments  = isset( $form_data['payments'] ) ? $form_data['payments'] : array();

        // Resolve plan label from field choices.
        $sub_field  = isset( $form_data['form_fields'][ $field_id ] ) ? $form_data['form_fields'][ $field_id ] : array();
        $plan_label = isset( $sub_field['choices'][ $choice_key ]['label'] ) ? $sub_field['choices'][ $choice_key ]['label'] : '';
        if ( empty( $plan_label ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Plan not found.', 'everest-forms-pro' ) ) );
        }

        // Create or reuse Square customer.
        $payment_details = array(
            'source_id'      => $source_id,
            'idempotency_key' => $idempotency_key,
            'location_id'    => $location_id,
            'payment_data'   => array(
                'form_id' => $form_id,
                'data'    => isset( $payment_data['data'] ) ? $payment_data['data'] : array(),
            ),
        );

        $customer_details = \EverestForms\Pro\Addons\Square\Api\Api::create_customer( $payment_details, $payments );

        if ( is_array( $customer_details ) && isset( $customer_details['customer_id'] ) ) {
            $customer_id = $customer_details['customer_id'];
        } elseif ( is_array( $customer_details ) && ! empty( $customer_details[0] ) ) {
            wp_send_json_error( $customer_details );
            return;
        } else {
            $customer_id = $customer_details->getCustomer()->getId();
        }

        // Save card on file.
        $card_id = \EverestForms\Pro\Addons\Square\Api\Api::save_customer_card(
            $customer_id,
            $source_id,
            $idempotency_key . '_card'
        );

        // Look up cached plan variation, or create lazily.
        $stored_plans   = get_post_meta( $form_id, '_square_subscription_plans', true );
        $stored_plans   = is_array( $stored_plans ) ? $stored_plans : array();
        $plan_variation_id = '';

        foreach ( $stored_plans as $plan ) {
            if ( $plan['plan_name'] === $plan_label ) {
                $plan_variation_id = $plan['plan_variation_id'];
                break;
            }
        }

        if ( empty( $plan_variation_id ) ) {
            $choice         = isset( $sub_field['choices'][ $choice_key ] ) ? $sub_field['choices'][ $choice_key ] : array();
            $plan_amount    = isset( $choice['value'] ) ? evf_sanitize_amount( $choice['value'] ) : 0;
            $period         = isset( $choice['recurring_period'] ) ? $choice['recurring_period'] : 'month';
            $interval_count = isset( $choice['interval_count'] ) ? max( 1, absint( $choice['interval_count'] ) ) : 1;

            $plan_variation_id = \EverestForms\Pro\Addons\Square\Api\Api::upsert_subscription_plan(
                $plan_label,
                $plan_amount,
                $period,
                $interval_count
            );

            $stored_plans[] = array(
                'plan_name'         => $plan_label,
                'plan_variation_id' => $plan_variation_id,
                'period'            => $period,
                'interval_count'    => $interval_count,
            );
            update_post_meta( $form_id, '_square_subscription_plans', $stored_plans );
        }

        // Create subscription.
        $client = \EverestForms\Pro\Addons\Square\Api\Api::get_client();

        $subscription_request = new \Square\Models\CreateSubscriptionRequest( $location_id, $customer_id );
        $subscription_request->setPlanVariationId( $plan_variation_id );
        $subscription_request->setCardId( $card_id );
        $subscription_request->setIdempotencyKey( $idempotency_key . '_sub' );

        $subscription_response = $client->getSubscriptionsApi()->createSubscription( $subscription_request );

        if ( $subscription_response->isSuccess() ) {
            $subscription_id = $subscription_response->getResult()->getSubscription()->getId();
            wp_send_json_success( array( 'subscription_id' => $subscription_id ) );
        } else {
            $errors = $subscription_response->getErrors();
            $detail = ! empty( $errors[0] ) ? $errors[0]->getDetail() : 'Subscription creation failed';
            wp_send_json_error( array( 'message' => esc_html( $detail ) ) );
        }
    } catch ( \Exception $e ) {
        evf_get_logger()->critical( $e->getMessage(), array( 'source' => 'square-subscription' ) );
        wp_send_json_error( array( 'message' => $e->getMessage() ) );
    }
}
```

- [ ] **Step 3: Verify syntax**

```bash
php -l src/Addons/Square/Process/Process.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/Process/Process.php
git commit -m "feat(square): add create_square_subscription AJAX handler"
```

---

## Task 5: Extend `process_entry()` to record subscriptions

**Files:**
- Modify: `src/Addons/Square/Process/Process.php`

### What this does
After form submission, if `everest_form_square_subscription_id` is present in `$_POST` (injected by JS), immediately updates the entry to `Complete` with the subscription ID stored in meta.

- [ ] **Step 1: Add subscription handling in `process_entry()`**

In `Process::process_entry()`, the existing code sets entry status to `Pending` inside the `else` block (around line 95-119). After the call to `evf_payment_entries( $entry_id, $entry_data )` inside that block, add:

```php
// Subscription payment completed via Square.
if ( isset( $_POST['everest_form_square_subscription_id'] ) && $process ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $subscription_id                            = sanitize_text_field( wp_unslash( $_POST['everest_form_square_subscription_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $entry_meta                                 = json_decode( $entry_data['meta'], true );
    $entry_meta['payment_subscription']         = $subscription_id;
    $entry_meta['payment_transaction']          = '';
    $entry_update = array(
        'status' => 'Complete',
        'meta'   => wp_json_encode( $entry_meta ),
    );
    evf_payment_entries( $entry_id, $entry_update, true );
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l src/Addons/Square/Process/Process.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/Process/Process.php
git commit -m "feat(square): record subscription_id in process_entry"
```

---

## Task 6: Emit per-form recurring flag in `load_assets()`

**Files:**
- Modify: `includes/fields/class-evf-field-payment-square.php`

### What this does
After enqueuing the Square JS, emit a tiny inline script that marks the form as recurring-enabled. JS reads `window.evfSquareRecurringForms[formId]` to decide which payment flow to use. Per-form, safe for pages with multiple forms.

- [ ] **Step 1: Detect subscription plan field and emit inline script**

In `EVF_Field_Payment_Square::load_assets()`, inside the `if ( ! empty( $is_square_field ) || $has_square_proxy )` block, after `wp_enqueue_script( 'everest-forms-pro-square-payment' )` (line 186), add:

```php
$form_id_int             = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
$has_subscription_field  = ! empty(
    wp_list_filter( $form_data['form_fields'], array( 'type' => 'payment-subscription-plan' ) )
);

if ( $has_subscription_field && $form_id_int ) {
    wp_add_inline_script(
        'everest-forms-pro-square-payment',
        sprintf(
            'window.evfSquareRecurringForms = window.evfSquareRecurringForms || {}; window.evfSquareRecurringForms["%s"] = true;',
            esc_js( (string) $form_id_int )
        ),
        'after'
    );
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -l includes/fields/class-evf-field-payment-square.php
```

Expected: `No syntax errors detected`

- [ ] **Step 3: Manual verify — inline script emitted**

Load a page with a Square + subscription plan form. View page source. Search for `evfSquareRecurringForms`. Expected: a script block sets `window.evfSquareRecurringForms["<form_id>"] = true`.

- [ ] **Step 4: Commit**

```bash
git add everest-forms-pro/includes/fields/class-evf-field-payment-square.php
git commit -m "feat(square): emit per-form recurring flag for subscription JS routing"
```

---

## Task 7: Add subscription flow to the Square JS

**Files:**
- Modify: `src/Addons/Square/assets/js/frontend/evf-square-payment.js`

### What this does
In `loadSquareCreditCard()`, when the form is recurring (`window.evfSquareRecurringForms[formId]`), attaches a submit-button click handler. On click: validates form, tokenizes card, calls `evf_square_create_subscription` AJAX, injects `everest_form_square_subscription_id` hidden input, removes EVF's AJAX handler, and submits natively (same pattern Razorpay uses). The existing `everest_forms_frontend_payment_before_success_message` handler is left untouched and only fires for non-subscription forms.

- [ ] **Step 1: Add `createSquareSubscription` helper inside `square_payment`**

Inside the `square_payment` object (before the closing `}` of the object literal at the bottom), after the `removeEntryAfterCardDeclined` method, add a comma after that method's closing brace and then add:

```javascript
createSquareSubscription: async function( $form, card, formId ) {
    var $checkedPlan = $form.find( '.evf-field-payment-subscription-plan input.evf-payment-price:checked' );

    if ( ! $checkedPlan.length ) {
        $form.off( 'submit' );
        $form[0].submit();
        return;
    }

    var fieldId   = $checkedPlan.closest( '[data-field-id]' ).data( 'field-id' );
    var choiceKey = $checkedPlan.val();

    var token;
    try {
        token = await square_payment.tokenize( card, formId );
    } catch ( tokenErr ) {
        evfSquareCardErrors( formId ).html( tokenErr.message ).show();
        $( '#evf-submit-' + formId ).attr( 'disabled', false ).html(
            $( '#evf-submit-' + formId ).data( 'submit-text' ) || 'Submit'
        );
        return;
    }

    var array          = new Uint32Array( 1 );
    var idempotencyKey = String( window.crypto.getRandomValues( array )[0] );

    $.ajax( {
        url:    evf_square_payment_obj.ajax_url,
        type:   'POST',
        data:   {
            action:          'evf_square_create_subscription',
            security:        evf_square_payment_obj.security,
            form_id:         formId,
            field_id:        fieldId,
            choice_key:      choiceKey,
            source_id:       token,
            idempotency_key: idempotencyKey,
            location_id:     evf_square_payment_obj.location_id,
            payment_data:    JSON.stringify( {
                data: $form.serializeArray()
            } )
        },
        success: function( response ) {
            if ( response.success ) {
                $( '<input>', {
                    type:  'hidden',
                    name:  'everest_form_square_subscription_id',
                    value: response.data.subscription_id
                } ).appendTo( $form );

                $form.off( 'submit' );
                $form[0].submit();
            } else {
                var msg = response.data && response.data.message
                    ? response.data.message
                    : 'Subscription failed. Please try again.';
                evfSquareCardErrors( formId ).html( msg ).show();
                $( '#evf-submit-' + formId ).attr( 'disabled', false ).html(
                    $( '#evf-submit-' + formId ).data( 'submit-text' ) || 'Submit'
                );
            }
        },
        error: function() {
            evfSquareCardErrors( formId ).html( 'An error occurred. Please try again.' ).show();
            $( '#evf-submit-' + formId ).attr( 'disabled', false ).html(
                $( '#evf-submit-' + formId ).data( 'submit-text' ) || 'Submit'
            );
        }
    } );
}
```

- [ ] **Step 2: Attach the submit-button click handler in `loadSquareCreditCard`**

In `loadSquareCreditCard`, after the line `$cardContainer.data( 'initialized', true )` (inside the `try` block, around line 98), add:

```javascript
// Subscription flow: intercept submit button click, create subscription before form submit.
if ( window.evfSquareRecurringForms && window.evfSquareRecurringForms[ formId ] ) {
    $form.find( '.everest-forms-submit-button' )
        .off( 'click.evfSquareSubscription' )
        .on( 'click.evfSquareSubscription', function( evt ) {
            // Only handle when Square is the active gateway.
            var $gatewaySelector = $form.find( '.evf-payment-gateway-selector-inputs' );
            if ( $gatewaySelector.length && 'square' !== evfSquareGatewaySelectorSlug( $form ) ) {
                return;
            }

            var $checkedPlan = $form.find( '.evf-field-payment-subscription-plan input.evf-payment-price:checked' );
            if ( ! $checkedPlan.length ) {
                return; // no plan selected — fall through to normal validation
            }

            evt.preventDefault();
            evt.stopImmediatePropagation();

            if ( ! $form.validate().form() ) {
                return false;
            }

            $( '#evf-submit-' + formId ).attr( 'disabled', true );

            square_payment.createSquareSubscription( $form, card, formId );
            return false;
        } );
}
```

- [ ] **Step 3: Verify JS has no obvious syntax errors**

Open the file in a browser DevTools console or run:
```bash
node --check src/Addons/Square/assets/js/frontend/evf-square-payment.js
```

Expected: no output (no errors)

- [ ] **Step 4: Manual end-to-end test**

1. Load a form page that has: Square payment field, payment-subscription-plan field (1+ choices), Square enabled
2. Fill in all required fields; select a subscription plan
3. Enter test card number in Square widget: `4111 1111 1111 1111`, any future expiry, any CVV
4. Click Submit
5. Expected browser: button disables → AJAX fires → `everest_form_square_subscription_id` injected → native form submit
6. Expected server: `process_entry` runs → entry status = `Complete` → `payment_subscription` in meta = Square subscription ID
7. In Square Dashboard (sandbox): verify subscription exists for the test customer
8. Verify success message / redirect shows correctly

- [ ] **Step 5: Test with gateway selector (Square + another gateway on same form)**

1. Add a Payment Method Selector field alongside a Stripe field
2. Select Square radio → fill form → submit
3. Verify subscription created (not a one-time charge)
4. Switch selector to Stripe → fill form → submit
5. Verify Square subscription is NOT created

- [ ] **Step 6: Test non-subscription Square form unchanged**

1. Load a form with Square but NO subscription plan field
2. Fill in, enter card, submit
3. Verify one-time payment flow runs as before (no regression)

- [ ] **Step 7: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/assets/js/frontend/evf-square-payment.js
git commit -m "feat(square): add subscription payment flow to Square JS"
```

---

## Task 8: Build minified JS

**Files:**
- Modify: `src/Addons/Square/assets/js/frontend/evf-square-payment.min.js`

### What this does
Production uses the `.min.js` file. Rebuild it so the minified version matches the source.

- [ ] **Step 1: Check how minification is done for this file**

```bash
cat package.json | grep -A5 "scripts"
```

Look for a `build` or `minify` script. If there is one:

```bash
npm run build
```

- [ ] **Step 2: If no build script, minify manually**

If the project has no build script for this file, copy the unminified JS as the minified (acceptable for dev; production build handled separately):

```bash
cp src/Addons/Square/assets/js/frontend/evf-square-payment.js \
   src/Addons/Square/assets/js/frontend/evf-square-payment.min.js
```

- [ ] **Step 3: Commit**

```bash
git add everest-forms-pro/src/Addons/Square/assets/js/frontend/evf-square-payment.min.js
git commit -m "build(square): rebuild minified Square payment JS with subscription support"
```

---

## Self-Review Checklist

- [x] **Spec: form-save sync** → Task 3 `sync_square_plans()`
- [x] **Spec: lazy fallback** → Task 4 `create_square_subscription()` re-upserts if not in meta
- [x] **Spec: tokenize → save card → create subscription flow** → Task 4 (PHP) + Task 7 (JS)
- [x] **Spec: `process_entry` subscription handling** → Task 5
- [x] **Spec: `recurring` detection auto-detect** → Task 6 inline script + Task 7 JS check
- [x] **Spec: nonce check on AJAX** → Task 4 uses `check_ajax_referer('evf_square_payment_nonce', 'security')`
- [x] **Spec: gateway selector guard** → Task 7 checks `evfSquareGatewaySelectorSlug`
- [x] **Spec: error handling — API down during save** → Task 3 catches + logs, continues
- [x] **Spec: error handling — card save fails** → Task 4 PHP throws, JS shows error
- [x] **Spec: error handling — zero amount** → `sync_square_plans` skips empty `$plan_amount`; `create_square_subscription` will get API error from Square (no pre-flight needed)
- [x] **Type consistency** — `plan_variation_id` key used in Tasks 3, 4 post meta read/write; `subscription_id` used in Tasks 4 (PHP), 7 (JS), 5 (process_entry)
- [x] **No TBDs or placeholders** — all code is complete
