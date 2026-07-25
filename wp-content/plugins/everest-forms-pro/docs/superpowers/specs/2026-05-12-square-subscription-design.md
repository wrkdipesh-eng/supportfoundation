# Square Subscription Support — Design Spec

**Date:** 2026-05-12  
**Branch:** EVF-2435-new-payment-gateway-field-version-razopay-subscription  
**Status:** Approved

---

## Overview

Add Square subscription support to the `everest-forms-pro` Square addon. When a form contains a `payment-subscription-plan` field and Square is enabled, the payment flow switches from one-time charge to a recurring Square subscription. Mirrors the existing Razorpay and Stripe subscription patterns.

---

## Architecture

Four touch points, all inside `everest-forms-pro`. No new files.

| File | Change |
|------|--------|
| `src/Addons/Square/Process/Process.php` | Add `sync_square_plans()` (form-save hook) + `create_square_subscription()` AJAX handler |
| `src/Addons/Square/Api/Api.php` | Add `upsert_subscription_plan()`, `save_customer_card()`, `create_subscription()` static methods |
| `src/Addons/Square/assets/js/frontend/evf-square-payment.js` | Detect subscription plan field; branch into subscription flow before existing credit-card flow |
| `includes/class-everest-forms-pro.php` | Add `recurring` key to `evf_square_payment_obj` localized data |

`Builder.php`, `Settings.php`, and `Square.php` are unchanged.

---

## Subscription Detection

Auto-detect (no toggle required). Subscription mode activates when **both** conditions are true at runtime:

1. Square is enabled on the form (existing `is_square_enabled()` check).
2. The form contains at least one `payment-subscription-plan` field.

PHP sets `evf_square_payment_obj.recurring = '1'` during script localization when these conditions are met. JS reads this flag to route the submit flow.

---

## Data Flow

### 1. Form Save — Plan Sync

Hook: `everest_forms_save_form`  
Handler: `Process::sync_square_plans( $form_id, $form_data )`

```
foreach choice in payment-subscription-plan field:
    skip if choice already stored in _square_subscription_plans (match by label)
    Api::upsert_subscription_plan( label, amount, period, interval_count )
        → CatalogObject( type=SUBSCRIPTION_PLAN )
            CatalogSubscriptionPlan( name=label )
                CatalogSubscriptionPlanVariation(
                    name        = label,
                    phases      = [ SubscriptionPhase( cadence, periods ) ],
                    price       = Money( amount * 100, currency )
                )
        → $client->getCatalogApi()->upsertCatalogObject( $body )
        → return planVariationId
    append { plan_name, plan_variation_id, period, interval_count } to array
update_post_meta( $form_id, '_square_subscription_plans', $plans )
```

Errors during upsert are logged via `evf_get_logger()->critical()` and skipped silently — form save is never blocked.

### 2. Frontend Submit

```
submit clicked
  form validates (existing jQuery validation)
  evf_square_payment_obj.recurring === '1'
    AND .evf-field-payment-subscription-plan input:checked exists
  → intercept default Square credit-card flow
  → tokenize card: Square.payments( appId, locationId ).card().tokenize()
  → AJAX POST: action=evf_square_create_subscription
      { source_id, form_id, field_id, choice_key, idempotency_key, location_id, security }
    PHP handler:
      Api::create_customer( payment_details, payments )   ← existing, reused
      Api::save_customer_card( customer_id, source_id )   ← new
          POST /v2/customers/{customer_id}/cards { source_id }
          return card_id
      lookup planVariationId from _square_subscription_plans post meta by choice label
      if not found → lazy Api::upsert_subscription_plan() + persist
      CreateSubscriptionRequest( location_id, customer_id )
          ->setPlanVariationId( plan_variation_id )
          ->setCardId( card_id )
          ->setIdempotencyKey( idempotency_key )
      $client->getSubscriptionsApi()->createSubscription( $body )
      wp_send_json_success( [ 'subscription_id' => $id ] )
  → JS injects hidden input: everest_form_square_subscription_id = subscription_id
  → form submits normally (existing EVF submit flow)
```

### 3. Entry Processing

Hook: `everest_forms_process_complete` (priority 20)  
Handler: `Process::process_entry()` — extended, not replaced.

```
existing Square enabled + amount checks (unchanged)
if $_POST['everest_form_square_subscription_id'] set:
    meta['payment_subscription']  = sanitized subscription_id
    meta['payment_transaction']   = '' (no one-time transaction)
    entry status = 'Complete'
else:
    existing one-time credit-card flow unchanged
```

---

## Error Handling

| Scenario | Handling |
|----------|----------|
| Square API down during form save | Log + skip; form save proceeds normally |
| Plan missing from post meta at subscribe time | Lazy upsert on-demand, persist, continue |
| Card tokenization fails | Existing `evfSquareCardErrors` JS handler (unchanged) |
| `createSubscription` API error | `wp_send_json_error`; JS displays error above form |
| Customer already exists | Existing `create_customer()` dedup by email (unchanged) |
| Zero-amount plan choice | Skip upsert + block subscription with error |
| `save_customer_card` fails | `wp_send_json_error`; JS calls existing `removeEntryAfterCardDeclined` |
| Gateway selector present, Square not chosen | Existing `evfSquareGatewaySelectorSlug` guard (unchanged) |
| Form has subscription plan + single items | Subscription plan takes precedence; one-time items ignored in subscription flow |

---

## Post Meta Schema

Key: `_square_subscription_plans`  
Type: serialized array stored via `update_post_meta`.

```php
[
    [
        'plan_name'         => 'Monthly Basic',   // label from field choice
        'plan_variation_id' => 'SQPLAN_VAR_xxx',  // Square catalog object ID
        'period'            => 'month',            // day|week|month|year
        'interval_count'    => 1,
    ],
    // ... one entry per plan choice
]
```

---

## Square Catalog Cadence Mapping

| EVF period | Square SubscriptionCadence |
|------------|---------------------------|
| `day`      | `DAILY` |
| `week`     | `WEEKLY` |
| `month`    | `MONTHLY` |
| `year`     | `ANNUAL` |

---

## Security

- `create_square_subscription` AJAX: verified via `check_ajax_referer( 'evf_square_payment_nonce', 'security' )` (same nonce as existing Square endpoints).
- All `$_POST` inputs sanitized (`absint`, `sanitize_text_field`) before use.
- `source_id` passed directly to Square API only (never stored, never echoed).

---

## Out of Scope

- Subscription management UI (cancel, pause, resume).
- Webhook handling for subscription lifecycle events.
- Trial period support (can be added later following Razorpay trial pattern).
- Subscription expiry date support (can be added later).
