# Gánh Ngon — Alex DJ event, CRM boundary, CukCuk read

Date: 2026-11-06. Local worker authorization for this branch and pull request only: `p-t604u`. This document does not authorize a deploy, a production database change, or an outbound SMS, email, or Zalo message.

## What was audited in this repository

- Paid e-voucher denominations live only in `public_html/wp-content/mu-plugins/gn-voucher-tiers.php` (`gn_voucher_tiers`). That filter is a price list. It is not an event redemption engine and this work does not change it.
- `gn-voucher-online` 0.16.13 is not in this repository. The local audit of that build is treated as a constraint, not as source we can import. No old voucher tables are read or migrated.
- Counter routes `/gnv/khach` and `/gnv/quay`, and the event shell `/su-kien/<slug>`, are not implemented elsewhere in this repo. The SAO theme links to bare `/su-kien/` for a different marketing page. The new plugin serves only `/su-kien/{campaign-slug}/` and leaves `/su-kien/` alone.
- Jetpack CRM (`zero-bs-crm`) is present. BeautyOS is not. The new code does not write CRM rows and does not add a second commission calculator.
- Legacy gross commission remains in place and is not called:
  - `gn_commission_for_day()` in the chat-stack payload uses full-day revenue tiers (10% / 12% / 15%), including booking revenue and voucher pay/topup.
  - `gn_pt_commission()` in `gn-partner.php` multiplies booking `total` by a partner percent. `GN_PT_ENABLED` is already false.
  - No `bill_total` calculator exists in this repo. The new ledger treats `bill_total`, `grand_total`, `revenue`, `total`, `status`, and `amount` as ignored keys. Eligible base is food only. Tax, drinks, service, and discount are stored on the invoice and are not added or subtracted. There is no approved rule that includes them.
- Partner registration for the public site stays on `/doi-tac/` via `gn-partner-register.php`. The new partner form is a separate route, `/gnv/partner`, and does not rewrite that theme form.
- Booking tables `{$wpdb->prefix}sao_*` are not altered.

## Plugin

Standalone plugin: `public_html/wp-content/plugins/gn-alex-event/`.

WordPress wiring reuses the usual hooks (`register_activation_hook`, `init` rewrites, `template_redirect`, `rest_api_init`, `admin_menu`, `admin_post`, `load_plugin_textdomain`, `esc_html` when WordPress is loaded). It does not copy theme templates. Activation creates only `{$wpdb->prefix}gn_alex_*` tables and adds capabilities `gn_alex_staff`, `gn_alex_admin`, and `gn_alex_bank` to the administrator role. It does not seed a PIN, a partner, a bank account, or a revenue number.

### P0 event

- Campaign `alex-dj-20261108`, family `alex-dj-pq-20261108`, partner code `alex`.
- Start `2026-11-08 18:00:00` Asia/Bangkok. End `2026-11-09 02:00:00` Asia/Bangkok.
- Landing copy in Russian and English states a maximum of 3 beers, one at a time, plus one optional salad. Beer is not described as unlimited.
- Registration QR is `/su-kien/{slug}/` with no phone and no ticket code.
- Guest route `/gnv/khach`. Counter route `/gnv/quay`.
- Registration requires phone, RU/EN privacy consent, campaign, and a name so the counter can check the guest. Phone is canonicalized to `+84` and identified by HMAC-SHA256 with a pepper. The pepper is generated into a WordPress option on first use. It is not an environment PIN.
- OTP: unconfigured provider fail-closes. A challenge row is written only after the provider returns an acknowledgement id. The OTP is stored as an HMAC, expires in 300 seconds, and locks after 5 mismatches. The isolated mock class `Gn_Alex_Otp_Provider_IsolatedTestMock` acknowledges only when `GN_ALEX_ALLOW_ISOLATED_TEST_MOCK` is defined, `GN_ALEX_TEST_HARNESS=1`, and production mode is off. `Gn_Alex_Otp_Provider_VerifiedGateway` is not constructed by the factory. Live SMS is not wired.
- One voucher per phone HMAC plus campaign family. The same identity is reused across campaigns. A second QR in the same family does not mint quota. Reissue rotates the ticket secret and does not add quota. The secret is returned once and `tokenUrl()` refuses to place it in a URL.
- A constant ticket code, ticket secret, or query-string ticket is rejected by staff redeem. A static referral QR records a scan and never redeems. Staff redeem requires a bearer session, CSRF, explicit confirmation, item `beer` or `salad`, and a fresh name plus phone. Quantity, status, and amount from the client are ignored. One confirmed beer is one inserted redemption row with a unique `commit_key`. A pending hold expires instead of debiting twice. The same idempotency key returns the stored result.
- Staff roles follow the counter pattern `quay`, `thungan`, and `quanly`. PIN is `password_hash`, never a default and never read from the environment. Session TTL is 900 seconds, with rotate and revoke. Quota increase and bank approval need two different `quanly` users.
- Dashboard metrics are counts from scan, registration, check-in, redemption, abuse, and invoice rows. Alex commission is always null. POS revenue is the food base of invoices whose source is `manual` or `import`, whose link flag was set at insert, and whose latest paid state is not cancelled or refunded. Webhook rows and CukCuk snapshots do not count.

### P1 shared module

- Partner apply for `ctv` (500 bps, 0.05) or `driver` (1500 bps, 0.15) only. Admin approval issues a deterministic public id `GNP` plus a hash of role, phone HMAC, and the shared referral family `referral-food`.
- Invoice rows are insert-only. Status changes are `invoice_event` rows. Paid approval writes an accrual. Cancellation or refund writes a reversal of the same amount. A second reversal is rejected.
- Bank values must already be `enc:v1:` ciphertext. The request stores a hash. The ciphertext is written only after a second manager approves. Audit JSON records the field name, not the value. The partner page has no bank input and says collection is not available.
- CukCuk adapter reads only when an endpoint and HTTP client exist. It stores the raw body and timestamp. It does not create invoices. Outage returns the previous snapshot time and a reconciliation CSV. `posWrite()` always returns unsupported and `simulated: false`.
- CRM boundary is a JSON contract: Jetpack CRM is in the repo, BeautyOS is not, writes are false, and the legacy calculators are not invoked.

## Local run

```text
php scripts/run-gn-alex-tests.php
php -S 127.0.0.1:8765 scripts/gn-alex-demo-router.php
```

The demo server does not enable the OTP mock and does not seed a staff PIN. Registration on that server returns `otp_provider_required`.

## Production gate

Do not activate this on the live site until the approvals in `REPORT-ae-P0-ALEX.md` are explicit. Activation would create new tables in that site's database. This branch does not perform that activation.
