=== GN Alex Event ===
Contributors: ganhngon
Requires at least: 6.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later

Event registration and counter redemption for the Alex DJ party, plus a separate food-base partner ledger.

== Local tests ==

php scripts/run-gn-alex-tests.php

== Routes ==

* /su-kien/{slug}/ guest landing
* /gnv/khach guest pass
* /gnv/quay counter
* /wp-json/gn-alex/v1/ staff and registration API

OTP stays closed until a verified provider is configured. The isolated test mock is not loaded by this plugin unless GN_ALEX_ALLOW_ISOLATED_TEST_MOCK is defined and GN_ALEX_TEST_HARNESS=1 and production mode is off.
