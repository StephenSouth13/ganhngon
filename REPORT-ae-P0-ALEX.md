# REPORT-ae-P0-ALEX

Local worker authorization for this branch and pull request only: `p-t604u`. No deploy, no production database write, no POS write, and no outbound SMS, email, or Zalo was performed.

## 1. Audited

- This repository has `gn-voucher-tiers.php` for paid e-voucher prices. It does not have an event beer engine. `gn-voucher-online` 0.16.13 is not in the repo, so its tables were not opened and its data was not copied. The audit notes about that build were applied as design constraints: staff roles `quay` / `thungan` / `quanly`, short-lived sessions, transactional single-voucher locking, and no redeemable constant ticket code in a URL.
- `/gnv/khach`, `/gnv/quay`, and `/su-kien/<slug>` were not implemented in the repo. Bare `/su-kien/` belongs to the existing SAO marketing page and is not taken over.
- Jetpack CRM (`zero-bs-crm`) is present. BeautyOS is not. Legacy gross commission remains in `gn_commission_for_day()` (full-day revenue tiers) and `gn_pt_commission()` (booking total times a percent, already disabled). Neither is called. No `bill_total` calculator exists here. The new ledger ignores `bill_total` and uses food base only.
- `public_html/wp-content/mu-plugins/gn-secrets.php` is already committed with live mail and lead secrets. This change does not print, rotate, or use those values.
- The cloud image did not include PHP or MariaDB. Both were installed in the VM so the local suite could run. That is an environment gap, not a preexisting application test failure. The repo had no project test suite to fail before this work.

## 2. Implemented

Standalone plugin `public_html/wp-content/plugins/gn-alex-event` plus `scripts/run-gn-alex-tests.php`, `scripts/gn-alex-demo-router.php`, and the plan in `docs/superpowers/plans/2026-11-06-ganhngon-crm-cukcuk-alex.md`.

P0 covers the Alex campaign at 2026-11-08 18:00 Asia/Bangkok, RU/EN landing, phone HMAC identity shared across campaigns, one voucher per phone plus campaign family, fail-closed OTP with acknowledgement audit, staff PIN sessions (900 second TTL, rotate, revoke, no default and no environment PIN), one confirmed beer or salad per atomic row with a unique redemption key, pending-hold expiry, idempotent replay, referral QR that cannot redeem, reissue without new quota, two-person quota and bank approval, and a dashboard fed only by recorded rows. Alex commission is not calculated.

P1 covers partner apply and approval, deterministic partner id, CTV 0.05 and driver 0.15 of approved food base, immutable invoice plus reversal ledger, ciphertext bank placeholder, CukCuk read/snapshot/reconciliation with no simulated success, and a read-only CRM contract.

## 3. Not done why

- Live SMS is not connected. No commercial credential is in the repo. Registration fail-closes with `otp_provider_required`. The isolated mock is refused unless the test constant and `GN_ALEX_TEST_HARNESS=1` are both set and production mode is off.
- The plugin was not activated inside a live WordPress database. `wp-config.php` is gitignored and this run does not point the plugin at production.
- `gn-voucher-online` 0.16.13 source was not available on this VM, so its engine was not reused line for line. Behavior constraints from the audit were implemented in the new plugin instead of a parallel read of old tables.
- CukCuk has no write call. Without an endpoint the adapter returns `no_supported_endpoint` and `simulated: false`.
- Legacy gross commission files were left unchanged so existing booking and voucher accounting is not rewritten in this branch.
- BeautyOS is absent, so no second CRM calculator was created.

## 4. Accounts permissions needed

- A verified OTP/SMS gateway endpoint and secret, supplied outside this repo, before any production registration can succeed. Do not define `GN_ALEX_ALLOW_ISOLATED_TEST_MOCK` or set `GN_ALEX_TEST_HARNESS=1` on production. Set `GN_ALEX_PRODUCTION` when that gateway is actually connected.
- A WordPress administrator opens Tools → Alex event and types the first manager PIN. The plugin does not ship a PIN. The PIN is hashed. There is no environment variable such as `QUAY_PIN` that can sign in.
- Counter staff need role `quay` or `thungan`. Policy, partner approval, refund, and bank request need `quanly`. A second, different `quanly` must approve a quota increase or a bank ciphertext.
- CukCuk, if used, needs a read endpoint chosen by the operator. This plugin will not write to it.
- Activation adds `gn_alex_staff`, `gn_alex_admin`, and `gn_alex_bank` to the administrator role and creates only `{$prefix}gn_alex_*` tables. It does not alter `sao_*` booking tables or Jetpack CRM tables.
- `p-t604u` is the id for this local branch and pull request. It is not a staff account and it cannot redeem or approve a bank change.

## 5. Files changed

- `public_html/wp-content/plugins/gn-alex-event/` plugin, assets, and `tests/suite.php`
- `scripts/run-gn-alex-tests.php`
- `scripts/gn-alex-demo-router.php`
- `docs/superpowers/plans/2026-11-06-ganhngon-crm-cukcuk-alex.md`
- `REPORT-ae-P0-ALEX.md`
- `.gitignore` ignores `/var/` and `*.sqlite`

No existing theme, mu-plugin, CRM, or booking file was modified.

## 6. Test evidence

Command:

```text
php scripts/run-gn-alex-tests.php
```

Result: `SUMMARY pass=81 fail=0 blocked=2`.

Passed, including the requested cases:

- New registration QR, phone canonicalization, consent, CSRF, and one voucher.
- Staff beers remaining 3 → 2 → 1 → 0, history of three commits, fourth denied, client quantity/status/amount ignored.
- SQLite and MariaDB 10.11 concurrent redeem of the same last beer: exactly one commit. MySQL uses `SELECT … FOR UPDATE`. SQLite serializes writers with `BEGIN IMMEDIATE` and does not implement row locks; that limit is asserted. MySQL rollback of an uncommitted identity also passed.
- Screenshot and replay: constant ticket code rejected, query ticket plus cookie does not redeem, presence token is not a URL, used token cannot redeem again, idempotent replay returns the first remaining count.
- 100 registration scans keep a single voucher. Same campaign family does not mint a second voucher. A different family shares the phone identity and has its own quota.
- CTV 0.05 and driver 0.15 of food base 200000 (10000 and 30000). Tax, drinks, service, discount, and `bill_total` 999999 are not in the base. Second source of the same invoice is a replay. Refund writes a reversal and drops that invoice from dashboard revenue. Webhook duplicate returns the first result and is not counted as revenue.
- POS with no endpoint and POS write are not success. An outage returns the previous snapshot timestamp and does not change revenue.
- Expired pending hold does not double-debit. Offline retry of the same idempotency key returns the original result. Policy edit is audited and does not raise issued quota. Self-approval and counter staff cannot raise quota. Two managers can. Bank plaintext is refused. Bank audit does not contain the ciphertext. CSV prefixes `=1+1` and omits the full phone. Unauthorized redeem, public customer list, and bootstrap without `manage_options` are denied.
- Verified-gateway double still requires the real OTP hash. A gateway that does not acknowledge fail-closes. OTP plaintext is absent from the challenge row and the debug sink.

Blocked:

- Live commercial SMS OTP. No credential is configured. There is no demo code that marks a phone verified.
- Live WordPress bootstrap against production. Route behavior is covered by `Gn_Alex_Http`, which the plugin calls.

Local demo, separate from the suite: `php -S 127.0.0.1:8765 scripts/gn-alex-demo-router.php`. A posted registration with a real form CSRF returned `otp_provider_required`. The no-auth dashboard showed the scans caused by opening the landing (7) and POS revenue 0. Alex commission is labeled not calculated. The counter PIN field is empty.

## 7. Remaining risks

- SQLite cannot prove InnoDB row locks. The MariaDB run on this VM did. Production WordPress on MySQL still needs a staging activation before guests use it.
- REST `permission_callback` is open so the bearer session and public CSRF inside the engine can run without a WordPress cookie. A missing or bad session is rejected by the engine. This should be reviewed before production.
- Webhook intake is unauthenticated aside from idempotency. It cannot mark revenue or commission by itself, but a public endpoint can still store untrusted rows.
- The first manager PIN bootstrap trusts a WordPress administrator. That account must already be protected.
- Legacy gross commission code is still in the repo and can still be invoked by older pages if those plugins are active. This plugin does not disable those files.
- `gn-secrets.php` remains a committed secret. It is outside this change and still needs a human rotation.
- Dashboard scan counts include anonymous page opens. They are real rows, not a revenue estimate.

## 8. Needs approval for production

- Approve activation on a staging WordPress database first, including rewrite flush and a check that bare `/su-kien/` still shows the existing marketing page.
- Approve the OTP provider, its endpoint, and its secret. Until that exists, registration must stay fail-closed.
- Approve the first manager PIN ceremony and the two-person rule for quota and bank data.
- Approve the food-only rates (CTV 0.05, driver 0.15) and confirm that tax, drinks, service, discount, and refunds stay out of the base until a written rule says otherwise.
- Approve any CukCuk read endpoint. Do not expect a write or a simulated sync.
- Do not merge this pull request from the agent. A human merges after the approvals above.
