<?php
/**
 * Local test runner. Does not deploy, send SMS, or write a production database.
 * Usage: php scripts/run-gn-alex-tests.php
 */
$root = dirname(__DIR__) . '/public_html/wp-content/plugins/gn-alex-event';
foreach (glob($root . '/includes/*.php') as $file) {
    passthru(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file), $code);
    if ($code !== 0) {
        exit($code);
    }
}
require $root . '/tests/suite.php';
