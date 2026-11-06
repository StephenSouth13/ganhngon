=== GN Alex Event ===
Contributors: ganhngon
Requires at least: 6.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later

Event registration and counter redemption for the Alex DJ party, plus a separate food-base partner ledger.

Use the build on main after pull request 2. Do not keep using an unmerged copy of that branch.

This plugin is not a finished P1 or P2 payout system. Partner amounts stay unpriced until a separate approval. Alex commission is not calculated.

== Local tests ==

php scripts/run-gn-alex-tests.php

== Routes owned by this plugin ==

* /su-kien/{slug}/ guest landing for an event slug only. Bare /su-kien/ is not taken.
* /gn-alex/khach guest pass
* /gn-alex/quay counter
* /gn-alex/dashboard staff session required
* /gn-alex/partner public pending application
* /wp-json/gn-alex/v1/ API

/gnv/khach and /gnv/quay are not registered and are not redirected. An existing voucher plugin can keep them.

OTP stays closed until a verified provider is configured outside this repository. The isolated test mock is not loaded unless GN_ALEX_ALLOW_ISOLATED_TEST_MOCK is defined and GN_ALEX_TEST_HARNESS=1 and production mode is off.
