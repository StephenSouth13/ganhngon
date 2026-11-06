# Alex event staging draft

This draft describes the plugin on the merged main branch plus the route and staging changes. It is not a production runbook. It does not contain customer records, bank details, or provider secrets. P1 partner pricing is not complete. P2 payout does not exist.

## Do not use an old unmerged tree

Use the repository after pull request 2 is on `main`, then the later route and staging pull requests. Do not keep serving `/gnv/khach` or `/gnv/quay` from this plugin.

## What this plugin owns

- `/su-kien/{slug}/` for an event slug only. Bare `/su-kien/` stays with the existing theme.
- `/gn-alex/khach` is a read-only pass when a valid guest header is present. Otherwise it links to the event page and does not issue a registration token. Registration stays on `/su-kien/{slug}/`.
- `/gn-alex/quay` counter: staff PIN, one guest lookup, then one confirmed beer or salad.
- `/gn-alex/desk` private partner review after a staff PIN. There is no public application list.
- `/gn-alex/dashboard` only with a staff session. The figure is eligible food base, not total revenue.
- `/gn-alex/referral/{code}` after a manager review and a separate onboarding grant. The code is for the referral-food campaign, not the Alex event. A visit records one provisional click and links only to `/dat-ban/?gn_ref={code}`. It does not verify a phone, issue a pass, or price an invoice. Staff still match the booking by hand.
- `/wp-json/gn-alex/v1/` for the same actions.

`/gnv/khach` and `/gnv/quay` are not registered here and are not redirected. This plugin does not turn another voucher plugin off, and it does not call the older gross commission functions.

## Staging admin

WordPress Tools → Alex event, as an administrator:

- Create the first manager PIN. There is no default.
- Create additional `quay`, `thungan`, `quanly`, and `onboarding` people. The PIN is hashed. The screen does not show it again.
- Read whether an OTP provider is verified and whether `GN_ALEX_WEBHOOK_SECRET` is configured. Type those values only in server config, not in this screen and not in git.

A counter person looks up a guest by typed name and phone. A code in the URL is ignored. One beer or one salad is confirmed. A quota increase is only a request until a different manager approves it. Sessions expire, rotate, and revoke.

Partner applications stay pending. A manager can mark one reviewed. A different onboarding person can grant attribution and only then a referral link exists. Paying an invoice records `pending_unpriced` with reason `requires_approval`. A price is a separate approval for that invoice after a versioned food-only rule and its effective interval. Driver approval does not price every later bill. Alex commission is not calculated. Payout stays disabled until one BeautyOS source of record is wired. Bank numbers are not collected.

## Local check

```text
php scripts/run-gn-alex-tests.php
php -S 127.0.0.1:8765 scripts/gn-alex-demo-router.php
```

The demo router does not enable the OTP mock and has no webhook secret. Registration stays fail-closed. The test suite's isolated mock is not production.

## Still blocked for a real staging host

- A verified OTP gateway and a webhook secret in server config.
- A WordPress database activation and rewrite flush on a staging site, not production.
- BeautyOS contract and signed facts before any payout.
- VAT must be known before a POS reconcile shows a food total. Unknown VAT blocks that total.
