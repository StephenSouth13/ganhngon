<?php
/**
 * HTML mockups for review only. Not a WordPress end-to-end test and not evidence that a guest arrived from a referral.
 * The person shown is the synthetic fixture Mila Petrova. OTP mock is test-only.
 * php -S 127.0.0.1:8766 scripts/gn-alex-screenshot-fixture.php
 */
$root = dirname(__DIR__) . '/public_html/wp-content/plugins/gn-alex-event';
require $root . '/includes/class-gn-alex-schema.php';
require $root . '/includes/class-gn-alex-storage.php';
require $root . '/includes/class-gn-alex-phone.php';
require $root . '/includes/class-gn-alex-providers.php';
require $root . '/includes/class-gn-alex-copy.php';
require $root . '/includes/class-gn-alex-engine.php';
require $root . '/includes/class-gn-alex-view.php';
require $root . '/includes/class-gn-alex-http.php';
putenv('GN_ALEX_TEST_HARNESS=1');
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($uri, '/assets/')) {
    $file = $root . $uri;
    if (is_file($file)) {
        header('Content-Type: ' . (str_ends_with($file, '.css') ? 'text/css' : 'text/javascript'));
        readfile($file);
        return true;
    }
}
$dbPath = sys_get_temp_dir() . '/gn-alex-shot.sqlite';
if (!is_file($dbPath)) {
    $db = Gn_Alex_Storage::sqlite($dbPath);
    $db->install();
    $mock = new Gn_Alex_Otp_Provider_IsolatedTestMock(true, false);
    $engine = new Gn_Alex_Engine($db, hash('sha256', 'shot-pepper'), hash('sha256', 'shot-token'), $mock, false, true);
    $start = (new DateTimeImmutable('2026-11-08 18:00:00', new DateTimeZone('Asia/Bangkok')))->getTimestamp();
    $engine->setNow($start);
    $engine->ensureAlexCampaign();
    do {
        $pin = (string) random_int(20000000, 98999999);
    } while (preg_match('/^(\d)\1+$/', $pin) || $pin === '12345678');
    $staff = $engine->enrollStaffForIsolatedTest('quay', $pin, 'counter');
    file_put_contents(sys_get_temp_dir() . '/gn-alex-shot-staff.txt', $staff['staff_id'] . ' ' . $pin);
    $token = $engine->issueRegistrationToken('otp_request');
    $otp = $engine->requestOtp([
        'csrf' => $token['csrf'], 'locale' => 'en', 'consent' => 1,
        'phone' => '0912000001', 'name' => 'Mila Petrova', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '127.0.0.1',
    ]);
    $code = $mock->lastOtp(Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize('0912000001'), hash('sha256', 'shot-pepper')));
    $engine->verifyOtp(['csrf' => $otp['verify_csrf'], 'phone' => '0912000001', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => $code]);
}
$db = Gn_Alex_Storage::sqlite($dbPath);
$engine = new Gn_Alex_Engine($db, hash('sha256', 'shot-pepper'), hash('sha256', 'shot-token'), new Gn_Alex_Otp_Provider_Unconfigured(), false, false);
$start = (new DateTimeImmutable('2026-11-08 18:00:00', new DateTimeZone('Asia/Bangkok')))->getTimestamp();
$engine->setNow($start);
$http = new Gn_Alex_Http($engine);
if ($uri === '/shot/resolved') {
    $parts = explode(' ', trim((string) file_get_contents(sys_get_temp_dir() . '/gn-alex-shot-staff.txt')));
    $login = $engine->staffLogin((int) $parts[0], $parts[1], '127.0.0.1');
    $look = $engine->lookupGuest([
        'session_token' => $login['token'], 'csrf' => $login['csrf'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_name' => 'Mila Petrova', 'proof_phone' => '0912000001',
    ]);
    $html = Gn_Alex_View::counter('en');
    $html = str_replace('id="gn-login"', 'id="gn-login" hidden', $html);
    $html = str_replace('id="gn-lookup" method="post" action="/gn-alex/v1/staff/lookup" hidden', 'id="gn-lookup" method="post" action="/gn-alex/v1/staff/lookup"', $html);
    $html = str_replace('id="gn-resolved" hidden', 'id="gn-resolved"', $html);
    $html = str_replace('id="gn-resolved-name"></h2>', 'id="gn-resolved-name">' . Gn_Alex_Text::esc((string) $look['display_name']) . '</h2>', $html);
    $html = str_replace('id="gn-resolved-mask"></p>', 'id="gn-resolved-mask">' . Gn_Alex_Text::esc((string) $look['phone_mask']) . '</p>', $html);
    $html = str_replace('id="gn-resolved-remaining"></p>', 'id="gn-resolved-remaining">Beers left ' . Gn_Alex_Text::esc((string) $look['remaining']) . '</p>', $html);
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    return true;
}
if ($uri === '/shot/dashboard') {
    $parts = explode(' ', trim((string) file_get_contents(sys_get_temp_dir() . '/gn-alex-shot-staff.txt')));
    $login = $engine->staffLogin((int) $parts[0], $parts[1], '127.0.0.1');
    $metrics = $engine->publicMetrics(Gn_Alex_Engine::ALEX_SLUG, [
        'session_token' => $login['token'],
        'csrf' => $login['csrf'],
    ]);
    header('Content-Type: text/html; charset=utf-8');
    echo Gn_Alex_View::dashboard('en', $metrics, false);
    return true;
}
if ($uri === '/shot/admin') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Alex staging</title></head><body>';
    echo '<h1>Alex event staging</h1>';
    echo '<h2>Route ownership</h2><ul><li>Owned: /gn-alex/khach, /gn-alex/quay, /gn-alex/desk</li>';
    echo '<li>Not owned and not redirected: /gnv/khach, /gnv/quay</li></ul>';
    echo '<h2>Money</h2><p>Payout is disabled. Alex commission is not calculated.</p>';
    echo '<h2>Server config</h2><ul><li>OTP provider id: unconfigured</li><li>Webhook secret configured: no</li></ul>';
    echo '<p>Set GN_ALEX_WEBHOOK_SECRET and the OTP gateway in server config. This screen does not accept or display those values.</p>';
    echo '<h2>Additional staff</h2><form><label>Role</label><select><option>quay</option><option>thungan</option><option>quanly</option><option>onboarding</option></select>';
    echo '<label>Label</label><input type="text"><label>PIN</label><input type="password"><button>Create staff</button></form></body></html>';
    return true;
}
$res = $http->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri, $_GET, [], [], []);
http_response_code($res['status']);
foreach ($res['headers'] as $name => $value) {
    header($name . ': ' . $value);
}
echo $res['body'];
return true;
