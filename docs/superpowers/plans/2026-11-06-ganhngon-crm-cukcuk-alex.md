# Gánh Ngon — Alex DJ event, CRM boundary, CukCuk read

Date: 2026-11-06. This document does not authorize a deploy, a production database change, or an outbound SMS, email, or Zalo message.

## What was audited in this repository

- Paid e-voucher denominations live only in `public_html/wp-content/mu-plugins/gn-voucher-tiers.php` (`gn_voucher_tiers`). That filter is a price list. It is not an event redemption engine and this work does not change it.
- `gn-voucher-online` 0.16.13 is not in this repository. The local audit of that build is treated as a constraint, not as source we can import. No old voucher tables are read or migrated.
- This repository does not contain `gn-voucher-online`. That name is only a collision risk for `/gnv/khach` and `/gnv/quay`. This plugin does not register those paths and does not redirect them. Its own screens are `/gn-alex/khach` and `/gn-alex/quay`. Bare `/su-kien/` stays with the existing theme. Only `/su-kien/{slug}/` is the event landing.
- Jetpack CRM (`zero-bs-crm`) is present. BeautyOS is not in this repository. The plugin does not post rows to CRM. Partner rules that can change without a version or effective interval, and any plaintext bank field, are not used as a pricing source.
- Legacy gross commission remains in place and is not called:
  - `gn_commission_for_day()` in the chat-stack payload uses full-day revenue tiers (10% / 12% / 15%), including booking revenue and voucher pay/topup.
  - `gn_pt_commission()` in `gn-partner.php` multiplies booking `total` by a partner percent. `GN_PT_ENABLED` is already false.
  - No `bill_total` calculator exists in this repo. The new ledger treats `bill_total`, `grand_total`, `revenue`, `total`, `status`, and `amount` as ignored keys. Eligible base is food only. Tax, drinks, service, and discount are stored on the invoice and are not added or subtracted. There is no approved rule that includes them.
- Partner registration for the public site stays on `/doi-tac/` via `gn-partner-register.php`. The event application form is `/gn-alex/partner` and does not rewrite that theme form.
- Booking tables `{$wpdb->prefix}sao_*` are not altered.

## Plugin

Standalone plugin: `public_html/wp-content/plugins/gn-alex-event/`.

WordPress wiring reuses the usual hooks (`register_activation_hook`, `init` rewrites, `template_redirect`, `rest_api_init`, `admin_menu`, `admin_post`, `load_plugin_textdomain`, `esc_html` when WordPress is loaded). It does not copy theme templates. Activation creates only `{$wpdb->prefix}gn_alex_*` tables and adds capabilities `gn_alex_staff`, `gn_alex_admin`, and `gn_alex_bank` to the administrator role. It does not seed a PIN, a partner, a bank account, or a revenue number.

### P0 event

- Campaign `alex-dj-20261108`, family `alex-dj-pq-20261108`, partner code `alex`.
- Start `2026-11-08 18:00:00` Asia/Bangkok. End `2026-11-09 02:00:00` Asia/Bangkok.
- Landing copy in Russian and English states a maximum of 3 beers, one at a time, plus one optional salad. Beer is not described as unlimited.
- Registration QR is `/su-kien/{slug}/` with no phone and no ticket code.
- Guest route `/gn-alex/khach`. Counter route `/gn-alex/quay`.
- Registration requires phone, RU/EN privacy consent, campaign, and a name so the counter can check the guest. Phone is canonicalized to `+84` and identified by HMAC-SHA256 with a pepper. The pepper is generated into a WordPress option on first use. It is not an environment PIN.
- OTP: unconfigured provider fail-closes. A challenge row is written only after the provider returns an acknowledgement id. The OTP is stored as an HMAC, expires in 300 seconds, and locks after 5 mismatches. The isolated mock class `Gn_Alex_Otp_Provider_IsolatedTestMock` acknowledges only when `GN_ALEX_ALLOW_ISOLATED_TEST_MOCK` is defined, `GN_ALEX_TEST_HARNESS=1`, and production mode is off. `Gn_Alex_Otp_Provider_VerifiedGateway` is not constructed by the factory. Live SMS is not wired.
- One voucher per phone HMAC plus campaign family. The same identity is reused across campaigns. A second QR in the same family does not mint quota. Reissue rotates the ticket secret and does not add quota. The secret is returned once and `tokenUrl()` refuses to place it in a URL.
- A constant ticket code, ticket secret, or query-string ticket is rejected by staff redeem. A static referral QR records a scan and never redeems. Staff redeem requires a bearer session, CSRF, explicit confirmation, item `beer` or `salad`, and a fresh name plus phone. Quantity, status, and amount from the client are ignored. One confirmed beer is one inserted redemption row with a unique `commit_key`. A pending hold expires instead of debiting twice. The same idempotency key returns the stored result.
- Staff roles are `quay`, `thungan`, `quanly`, and `onboarding`. PIN is `password_hash`, never a default and never read from the environment. Session TTL is 900 seconds, with rotate and revoke. The session token is a bearer header, not a cookie. REST `permission_callback` only checks that a credential is present. The engine still verifies it. A passing callback is not authentication.
- Guest registration uses a single-use token stored as a hash with a 10-minute expiry. It is issued by the server on GET and consumed on POST. A client-supplied session id is not a token.
- OTP sends are reserved inside a transaction per phone HMAC before the provider is called. A second send is refused while a challenge is still pending. The code is stored only as an HMAC.
- Dashboard reads require a staff session. There is no no-auth admin dashboard. The public figure is `eligible_food_base` for linked manual or import invoices that are paid and not reversed. It is not labeled as total revenue. Alex commission is not calculated.
- POS and attribution webhooks require HMAC-SHA256 over the raw body, `X-GN-Timestamp`, `X-GN-Signature`, and `X-GN-Tenant`. The tenant header and the body tenant must both match the server tenant. The secret is server-side. If it is missing, the route fail-closes and stores nothing. Timestamp skew is 300 seconds. The same event key and body returns the first result. The same key with a different body returns a conflict. A webhook cannot set source `manual`, `link_verified`, partner attribution, or a priced commission.

### Staged attribution (not a finished P1)

Self-enrollment stores a pending application. Choosing `driver` or `ctv` does not grant attribution and does not issue a referral QR. A `quanly` review still does not grant it. A different person with role `onboarding` grants attribution. Until then, invoice recording rejects the public id.

Paying an attributable invoice writes a `pending_unpriced` ledger row with reason `requires_approval`. A priced amount exists only after an approved `policy_rule` with version, food-only flag, and an effective interval, and only when `onboarding` prices that one invoice. Approving the driver rule does not price every later bill. Rates, when explicitly priced, are 500 bps for CTV and 1500 bps for driver, on the food base only. Alex is never priced. There is no payout run. Bank requests return `payout_provider_pending` and do not store an account.

CukCuk reads only when an endpoint and client exist, stores the raw snapshot and time, and does not create invoices. Outage returns the previous snapshot time. `posWrite()` stays unsupported and `simulated` stays false. CRM writes stay off. This is not production-proven partner accounting.

### Completeness

Coded and covered by local tests, not proven on production WordPress:

- P0 guest registration, OTP fail-closed, beer and salad redemption, identity, quotas, staff sessions, signed webhooks, and staff-only eligible food base.
- Staged partner application, distinct-role grant, unpriced ledger, and explicit single-invoice pricing.

Not production work:

- Live SMS gateway, live WordPress activation, payout provider, CRM sync, and automatic commission. P1 is not complete.

## Local run

```text
php scripts/run-gn-alex-tests.php
php -S 127.0.0.1:8765 scripts/gn-alex-demo-router.php
```

The demo server does not enable the OTP mock, does not seed a staff PIN, and has no webhook secret. Registration returns `otp_provider_required`. An unsigned webhook is not stored.

## Production gate

Do not activate this on a live site until a human approves the OTP provider, the webhook secret, and staging. Activation would create new `gn_alex_*` tables. This branch does not perform that activation.
