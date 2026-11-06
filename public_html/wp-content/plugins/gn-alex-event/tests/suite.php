<?php
require __DIR__ . '/bootstrap.php';

$results = [];
function record(string $status, string $name, string $detail = ''): void {
    global $results;
    $results[] = [$status, $name, $detail];
    echo '[' . $status . '] ' . $name . ($detail !== '' ? ' :: ' . $detail : '') . PHP_EOL;
}
function pass(string $name): void { record('PASS', $name); }
function fail(string $name, string $detail): void { record('FAIL', $name, $detail); }
function blocked(string $name, string $detail): void { record('BLOCKED', $name, $detail); }
function expect(string $name, bool $ok, string $detail = ''): void {
    if ($ok) pass($name); else fail($name, $detail === '' ? 'assertion failed' : $detail);
}
function j($value): string {
    return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
}

putenv('GN_ALEX_TEST_HARNESS=1');
$start = (new DateTimeImmutable('2026-11-08 18:00:00', new DateTimeZone('Asia/Bangkok')))->getTimestamp();
$pepper = bin2hex(random_bytes(24));
$tokenKey = bin2hex(random_bytes(24));
$phone = '0912000001';
$name = 'Mila Petrova';

function pin(): string {
    do {
        $pin = (string) random_int(20000000, 98999999);
    } while (preg_match('/^(\d)\1+$/', $pin));
    return $pin;
}

function engine(Gn_Alex_Storage $db, Gn_Alex_Otp_Provider $otp, bool $production = false, bool $harness = true, ?Gn_Alex_Get_Client $http = null, string $endpoint = ''): Gn_Alex_Engine {
    global $pepper, $tokenKey, $start;
    $engine = new Gn_Alex_Engine($db, $pepper, $tokenKey, $otp, $production, $harness, $http, $endpoint);
    $engine->setNow($start);
    return $engine;
}

function csrf(Gn_Alex_Engine $engine, string $purpose = 'otp_request'): array {
    $issued = $engine->issueRegistrationToken($purpose);
    return ['csrf' => (string) ($issued['csrf'] ?? '')];
}

function auth(array $login): array {
    return ['session_token' => $login['token'], 'csrf' => $login['csrf']];
}

try {
    $plain = Gn_Alex_Provider_Factory::make();
    expect('otp factory fail closed without test constant', $plain instanceof Gn_Alex_Otp_Provider_Unconfigured, get_class($plain));
    if (!defined('GN_ALEX_ALLOW_ISOLATED_TEST_MOCK')) {
        define('GN_ALEX_ALLOW_ISOLATED_TEST_MOCK', true);
    }
    putenv('GN_ALEX_PRODUCTION=1');
    expect('otp factory refuses mock in production', Gn_Alex_Provider_Factory::make() instanceof Gn_Alex_Otp_Provider_Unconfigured);
    putenv('GN_ALEX_PRODUCTION');
    expect('otp factory allows isolated mock only with the test flag', Gn_Alex_Provider_Factory::make() instanceof Gn_Alex_Otp_Provider_IsolatedTestMock);

    $badSql = false;
    foreach (['mysql', 'sqlite'] as $driver) {
        foreach (Gn_Alex_Schema::statements($driver, 'gn_alex_') as $sql) {
            if (stripos($sql, 'sao_') !== false || stripos($sql, 'drop ') !== false || stripos($sql, 'alter table') !== false) {
                $badSql = true;
            }
        }
    }
    expect('schema does not alter legacy tables', !$badSql);

    $src = '';
    foreach (glob(dirname(__DIR__) . '/includes/*.php') as $file) {
        $src .= file_get_contents($file);
    }
    expect('no env staff pin is read', preg_match('/getenv\(\s*[\'\"][^\'\"]*PIN/', $src) !== 1);
    expect('legacy day-revenue calculator is not copied', !str_contains($src, 'gn_commission_for_day'));
    expect('bill_total is only an ignored base key', str_contains($src, "'bill_total'") && !str_contains($src, '$bill_total'));

    $dbPath = sys_get_temp_dir() . '/gn-alex-suite-' . bin2hex(random_bytes(4)) . '.sqlite';
    $db = Gn_Alex_Storage::sqlite($dbPath);
    $db->install();
    $mock = new Gn_Alex_Otp_Provider_IsolatedTestMock(true, false);
    $app = engine($db, $mock);
    $campaign = $app->ensureAlexCampaign();
    expect('event starts 2026-11-08 18:00 Asia/Bangkok', (int) $campaign['starts_at'] === $start && $campaign['tz'] === 'Asia/Bangkok' && $campaign['partner_code'] === 'alex');
    expect('registration qr has no ticket code', $app->registrationQr(Gn_Alex_Engine::ALEX_SLUG) === '/su-kien/alex-dj-20261108/' && !str_contains($app->registrationQr(Gn_Alex_Engine::ALEX_SLUG), 'ticket'));

    $closedDb = Gn_Alex_Storage::sqlite(sys_get_temp_dir() . '/gn-alex-closed-' . bin2hex(random_bytes(3)) . '.sqlite');
    $closedDb->install();
    $closed = engine($closedDb, new Gn_Alex_Otp_Provider_Unconfigured());
    $closed->ensureAlexCampaign();
    $closedTry = $closed->requestOtp(array_merge(csrf($closed), [
        'locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => $name, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.0.0.1',
    ]));
    expect('otp absent provider fail closed', !$closedTry['ok'] && $closedTry['error'] === 'otp_provider_required', j($closedTry));
    $prod = engine($closedDb, $mock, true, true);
    $prod->setNow($start);
    $prodTry = $prod->requestOtp(array_merge(csrf($prod), [
        'locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => $name, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.0.0.2',
    ]));
    expect('production rejects isolated mock', !$prodTry['ok'] && in_array($prodTry['error'], ['isolated_mock_refused', 'otp_provider_required'], true), j($prodTry));
    expect('isolated enroll refused in production', !$prod->enrollStaffForIsolatedTest('quay', pin(), 'quay')['ok']);

    $canon = Gn_Alex_Phone::canonicalize('0912 000 001');
    expect('phone canonicalization', $canon === Gn_Alex_Phone::canonicalize('+84 912 000 001') && $canon === Gn_Alex_Phone::canonicalize('84912000001') && $canon === '+84912000001');
    expect('sqli-like phone rejected', Gn_Alex_Phone::canonicalize("0912000001' OR '1'='1") === null);

    $noConsent = $app->requestOtp(array_merge(csrf($app), [
        'locale' => 'en', 'consent' => 0, 'phone' => $phone, 'name' => $name, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.0.0.3',
    ]));
    expect('consent required', !$noConsent['ok'] && $noConsent['error'] === 'consent_required');
    $noCsrf = $app->requestOtp(['locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => $name, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.0.0.3']);
    expect('public csrf required', !$noCsrf['ok'] && $noCsrf['error'] === 'csrf_failed');

    $gate = csrf($app);
    $otp = $app->requestOtp(array_merge($gate, [
        'locale' => 'ru', 'consent' => 1, 'phone' => $phone, 'name' => $name, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.1.1.1',
    ]));
    $hmac = Gn_Alex_Phone::hmac($canon, $pepper);
    $code = $mock->lastOtp($hmac);
    $row = $db->fetch('SELECT * FROM gn_alex_otp WHERE id=?', [(int) $otp['challenge_id']]);
    $rowText = j($row);
    expect('otp request acknowledged', $otp['ok'] && $otp['provider'] === 'isolated_test_mock' && str_starts_with((string) $otp['provider_ack'], 'isolated-test-mock-'), j($otp));
    expect('otp plaintext is not stored or logged', $code !== null && !str_contains($rowText, (string) $code) && !str_contains(implode(',', $app->debugSink()), (string) $code) && !array_key_exists('otp', $row));
    $bad = $app->verifyOtp(['csrf' => $otp['verify_csrf'], 'phone' => $phone, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => '000000']);
    expect('wrong otp rejected', !$bad['ok'] && $bad['error'] === 'otp_invalid');
    $replayCsrf = $app->verifyOtp(['csrf' => $otp['verify_csrf'], 'phone' => $phone, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => $code]);
    expect('registration token is single use', !$replayCsrf['ok'] && $replayCsrf['error'] === 'csrf_failed', j($replayCsrf));
    $guest = $app->verifyOtp(['csrf' => $bad['verify_csrf'], 'phone' => $phone, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => $code]);
    expect('new registration issues one voucher', $guest['ok'] && $guest['created'] === true && (int) $guest['remaining'] === 3 && !str_contains($app->registrationQr(Gn_Alex_Engine::ALEX_SLUG), (string) $guest['ticket_secret']), j($guest));
    expect('token cannot be placed in a url', $app->tokenUrl((string) $guest['ticket_secret'])['error'] === 'token_not_placeable_in_url');

    $quay = $app->staffLogin(($en = $app->enrollStaffForIsolatedTest('quay', $quayPin = pin(), 'quay-1'))['staff_id'], $quayPin, '10.2.2.2');
    $quanlyPin = pin();
    $quanlyEn = $app->enrollStaffForIsolatedTest('quanly', $quanlyPin, 'quanly-a');
    $quanly = $app->staffLogin($quanlyEn['staff_id'], $quanlyPin, '10.2.2.3');
    $quanlyBPin = pin();
    $quanlyBEn = $app->enrollStaffForIsolatedTest('quanly', $quanlyBPin, 'quanly-b');
    $quanlyB = $app->staffLogin($quanlyBEn['staff_id'], $quanlyBPin, '10.2.2.4');
    $thuPin = pin();
    $thuEn = $app->enrollStaffForIsolatedTest('thungan', $thuPin, 'thungan-1');
    $thungan = $app->staffLogin($thuEn['staff_id'], $thuPin, '10.2.2.5');
    expect('staff sessions issued', $quay['ok'] && $quanly['ok'] && $quanlyB['ok'] && $thungan['ok'], j($quay));
    $look = $app->lookupGuest(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
    ]));
    $lookIssued = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'];
    $badLook = $app->lookupGuest(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => 'Wrong Name',
    ]));
    $shotLook = $app->lookupGuest(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name, 'ticket_code' => 'CONSTANT',
    ]));
    expect('lookup shows mask once and does not redeem', $look['ok'] && $look['redeems'] === false && $look['phone_mask'] === Gn_Alex_Phone::mask($canon) && !str_contains(j($look), $canon) && $lookIssued === 0 && !$badLook['ok'] && $badLook['error'] === 'identity_mismatch' && !$shotLook['ok'] && $shotLook['error'] === 'constant_ticket_not_accepted', j($look) . j($shotLook));
    putenv('GN_ALEX_STAFF_PIN=' . $quayPin);
    putenv('QUAY_PIN=' . $quayPin);
    $envLogin = $app->staffLogin($en['staff_id'], '24681357', '10.2.2.9');
    expect('env pin is not a credential', !$envLogin['ok']);
    putenv('GN_ALEX_STAFF_PIN');
    putenv('QUAY_PIN');

    $app->setNow($start - 60);
    $early = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'early-beer-1',
    ]));
    expect('redeem before event start is denied', !$early['ok'] && $early['error'] === 'event_not_started', j($early));
    $app->setNow($start);

    $http = new Gn_Alex_Http($app);
    $cookieOnly = $http->handle('POST', '/gn-alex/v1/staff/redeem', ['ticket' => 'CONSTANTCODE'], [
        'confirm' => 1, 'item' => 'beer', 'proof_phone' => $phone, 'proof_name' => $name,
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'idempotency_key' => 'cookie-only-1',
    ], [], ['gn_alex_device' => 'abc', 'gn_alex_pub' => 'not-a-session']);
    expect('cookie and query ticket do not redeem', $cookieOnly['status'] === 401 && str_contains($cookieOnly['body'], 'credential_missing'), $cookieOnly['body']);
    $constant = $http->handle('POST', '/gn-alex/v1/staff/redeem', ['ticket_code' => (string) $guest['ticket_secret']], [
        'confirm' => 1, 'item' => 'beer', 'campaign' => Gn_Alex_Engine::ALEX_SLUG,
        'idempotency_key' => 'constant-ticket-1', 'ticket_code' => (string) $guest['ticket_secret'],
    ], ['authorization' => 'Bearer ' . $quay['token'], 'x-gn-csrf' => $quay['csrf']], []);
    $constantJson = json_decode($constant['body'], true);
    expect('constant ticket code is rejected', !$constantJson['ok'] && $constantJson['error'] === 'constant_ticket_not_accepted', $constant['body']);
    $staticPath = $http->handle('GET', '/su-kien/alex-dj-20261108/' . $guest['ticket_secret'], [], [], [], []);
    expect('static ticket path is not a redemption route', $staticPath['status'] === 404, (string) $staticPath['status']);
    $referralAttempt = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'referral-nope-1', 'qr_kind' => 'referral',
    ]));
    expect('referral qr never redeems', !$referralAttempt['ok'] && $referralAttempt['error'] === 'referral_qr_never_redeems', j($referralAttempt));

    $ruPage = $http->handle('GET', '/su-kien/alex-dj-20261108', ['lang' => 'ru'], [], [], []);
    $enPage = $http->handle('GET', '/su-kien/alex-dj-20261108', ['lang' => 'en'], [], [], []);
    expect('ru landing', $ruPage['status'] === 200 && str_contains($ruPage['body'], 'Не больше 3 бокалов') && str_contains($ruPage['body'], 'lang="ru"') && !str_contains(strtolower($ruPage['body']), 'unlimited'));
    expect('en landing', $enPage['status'] === 200 && str_contains($enPage['body'], 'Maximum 3 beers') && str_contains($enPage['body'], '>ALEX<') && !str_contains($enPage['body'], '<img'));
    $legacy = $http->handle('GET', '/gnv/khach', [], [], [], []);
    $legacyQuay = $http->handle('GET', '/gnv/quay', [], [], [], []);
    expect('legacy voucher paths are not served by this plugin', $legacy['status'] === 404 && str_contains($legacy['body'], 'legacy_route_not_owned') && $legacyQuay['status'] === 404, $legacy['body']);
    $tokensBefore = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_reg_token')['c'];
    $validPass = $http->handle('GET', '/gn-alex/khach', [], [], ['x-gn-guest' => (string) $guest['guest_token']], []);
    $invalidPass = $http->handle('GET', '/gn-alex/khach', [], [], ['x-gn-guest' => 'not-a-guest-token'], []);
    $missingPass = $http->handle('GET', '/gn-alex/khach', [], [], [], []);
    $tokensAfter = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_reg_token')['c'];
    expect('valid guest pass shows status and no registration form', $validPass['status'] === 200 && str_contains($validPass['body'], $name) && !str_contains($validPass['body'], 'name="csrf"') && !str_contains($validPass['body'], 'name="phone"'), $validPass['body']);
    expect('invalid guest pass does not open registration', $invalidPass['status'] === 200 && $missingPass['status'] === 200 && !str_contains($invalidPass['body'], 'name="csrf"') && str_contains($invalidPass['body'], '/su-kien/alex-dj-20261108/') && $tokensAfter === $tokensBefore, $invalidPass['body']);
    $getLogin = $http->handle('GET', '/gn-alex/v1/staff/login', [], [], [], []);
    $getRedeem = $http->handle('GET', '/gn-alex/v1/staff/redeem', [], [], [], []);
    $getGrant = $http->handle('GET', '/gn-alex/v1/admin/partner/grant', [], [], [], []);
    $postLookup = $http->handle('POST', '/gn-alex/v1/staff/lookup', [], ['campaign' => Gn_Alex_Engine::ALEX_SLUG], ['authorization' => 'Bearer not-a-session', 'x-gn-csrf' => 'csrf'], []);
    $postRedeem = $http->handle('POST', '/gn-alex/v1/staff/redeem', [], [], [], []);
    expect('state-changing routes reject GET', $getLogin['status'] === 405 && $getRedeem['status'] === 405 && $getGrant['status'] === 405, $getLogin['body'] . $getGrant['body']);
    expect('POST lookup reaches auth and redeem without a bearer stops at presence', $postLookup['status'] === 403 && str_contains($postLookup['body'], 'unauthorized') && $postRedeem['status'] === 401, $postLookup['body'] . $postRedeem['body']);
    $underscore = Gn_Alex_Http::normalizeHeaders(['X_GN_Timestamp' => ['1700000000'], 'X_GN_Signature' => ['abc'], 'X_GN_Tenant' => ['ganhngon']]);
    expect('wordpress header names map onto webhook presence checks', Gn_Alex_Http::presenceGate('/gn-alex/v1/pos/webhook', $underscore, []) === null && Gn_Alex_Http::presenceGate('/gn-alex/v1/pos/webhook', ['x_gn_timestamp' => '1'], []) === 'webhook_unauthorized');
    $splitSig = Gn_Alex_Http::normalizeHeaders(['x-gn-signature' => ['abcd', 'ef']]);
    expect('duplicate signature headers are not joined', $splitSig['x-gn-signature'] === '');
    $forwarded = Gn_Alex_Http::forwardedHeaders(['REDIRECT_HTTP_AUTHORIZATION' => 'Bearer abc', 'HTTP_X_GN_CSRF' => 'csrf-1', 'QUERY_STRING' => 'token=secret']);
    expect('redirect authorization and csrf are forwarded and query tokens are not', $forwarded['authorization'] === 'Bearer abc' && $forwarded['x-gn-csrf'] === 'csrf-1' && !isset($forwarded['token']));
    expect('rest list includes lookup queue and grant as POST only', Gn_Alex_Http::apiRoutes()['/gn-alex/v1/staff/lookup'] === 'POST' && Gn_Alex_Http::apiRoutes()['/gn-alex/v1/staff/partner-queue'] === 'POST' && Gn_Alex_Http::apiRoutes()['/gn-alex/v1/admin/partner/grant'] === 'POST' && in_array('GET /gn-alex/desk', Gn_Alex_Http::routes(), true));
    $counter = $http->handle('GET', '/gn-alex/quay', [], [], [], []);
    expect('counter has no prefilled pin or bank field', $counter['status'] === 200 && !preg_match('/name="pin"[^>]*value=/', $counter['body']) && !str_contains($counter['body'], 'name="bank"'));
    $dash = $http->handle('GET', '/gn-alex/dashboard', ['demo' => '1', 'event' => Gn_Alex_Engine::ALEX_SLUG], [], [], []);
    expect('dashboard without staff session is refused', $dash['status'] === 401 && !str_contains($dash['body'], $phone) && !str_contains($dash['body'], 'Eligible food base'), $dash['body']);
    $js = file_get_contents(dirname(__DIR__) . '/assets/gn-alex.js');
    expect('scanner does not submit a url ticket', str_contains($js, 'replaceState') && !preg_match('/payload\.ticket|ticket_code\s*:/', $js));

    $beforeScans = (int) $app->publicMetrics(Gn_Alex_Engine::ALEX_SLUG, auth($quanly))['scans'];
    for ($i = 0; $i < 100; $i++) {
        $scan = $app->recordScan(Gn_Alex_Engine::ALEX_SLUG, 'registration');
        if (!$scan['ok'] || $scan['redeems'] !== false) {
            fail('100 registration scans', j($scan));
            break;
        }
    }
    $afterScans = (int) $app->publicMetrics(Gn_Alex_Engine::ALEX_SLUG, auth($quanly))['scans'];
    $vouchers = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_voucher WHERE identity_id=?', [(int) $guest['identity_id']])['c'];
    expect('100 qr scans keep one voucher', $afterScans === $beforeScans + 100 && $vouchers === 1, "scans {$beforeScans} to {$afterScans} vouchers {$vouchers}");

    $presence = $app->issuePresenceToken((string) $guest['guest_token']);
    expect('presence token is not a url and hides the phone', $presence['ok'] && $presence['place_in_url'] === false && !str_contains($presence['presence_token'], $phone) && !str_contains($presence['presence_token'], $name), j($presence));
    $resolved = $app->resolvePresence(array_merge(auth($quay), ['presence_token' => $presence['presence_token']]));
    expect('staff resolve shows name and mask only', $resolved['ok'] && $resolved['display_name'] === $name && $resolved['phone_mask'] === Gn_Alex_Phone::mask($canon) && $resolved['redeems'] === false && !str_contains(j($resolved), $canon), j($resolved));

    $sequence = [(int) $guest['remaining']];
    $firstIdem = 'beer-seq-1';
    $first = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => '+84 912 000 001', 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => $firstIdem, 'presence_token' => $presence['presence_token'],
        'qty' => 99, 'status' => 'committed', 'amount' => 500000, 'bill_total' => 999999,
    ]));
    $sequence[] = (int) ($first['remaining_after'] ?? -1);
    $replay = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => $firstIdem, 'qty' => 4,
    ]));
    $second = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'beer-seq-2',
    ]));
    $sequence[] = (int) ($second['remaining_after'] ?? -1);
    $third = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'beer-seq-3',
    ]));
    $sequence[] = (int) ($third['remaining_after'] ?? -1);
    $issued = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'];
    expect('three beers move remaining 3 to 2 to 1 to 0', $sequence === [3, 2, 1, 0] && $issued === 3, j($sequence) . ' issued ' . $issued . ' first ' . j($first));
    expect('replay returns the first result and does not consume more', $replay['ok'] && !empty($replay['replay']) && (int) $replay['remaining_after'] === 2 && $issued === 3, j($replay));
    $fourth = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'beer-seq-4', 'status' => 'committed', 'qty' => 1,
    ]));
    $issuedAfter = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'];
    expect('fourth beer denied', !$fourth['ok'] && $fourth['error'] === 'quota_exhausted' && $issuedAfter === 3, j($fourth));
    $history = $app->history(auth($quay), (int) $guest['voucher_id']);
    expect('history keeps three committed beers', $history['ok'] && $history['beer_sequence'] === [3, 2, 1, 0] && count($history['history']) === 3, j($history));
    $tokenReplay = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'beer-seq-replay-token', 'presence_token' => $presence['presence_token'],
    ]));
    expect('used presence token cannot redeem again', !$tokenReplay['ok'] && $tokenReplay['error'] === 'nonce_replay', j($tokenReplay));

    $salad = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'salad', 'idempotency_key' => 'salad-seq-1',
    ]));
    $salad2 = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'salad', 'idempotency_key' => 'salad-seq-2',
    ]));
    $beerStill = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'];
    expect('one salad confirmed and a second denied', $salad['ok'] && (int) $salad['remaining_after'] === 0 && !$salad2['ok'] && $beerStill === 3, j($salad) . j($salad2));

    $mismatch = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => '0912000091', 'proof_name' => 'Other Person',
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'mismatch-beer-1',
    ]));
    expect('counter re-asks identity', !$mismatch['ok'] && $mismatch['error'] === 'identity_mismatch', j($mismatch));

    $rotated = $app->staffRotate(auth($quay));
    $oldReplay = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => $firstIdem,
    ]));
    $oldNew = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'after-rotate-new',
    ]));
    $newDenied = $app->redeem([
        'session_token' => $rotated['token'], 'csrf' => $rotated['csrf'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'after-rotate-quota',
    ]);
    expect('rotated session rejects a new redeem and replay stays idempotent', $rotated['ok'] && $oldReplay['ok'] && !empty($oldReplay['replay']) && !$oldNew['ok'] && $newDenied['error'] === 'quota_exhausted', j($oldNew) . j($newDenied));
    $app->staffRevoke(['session_token' => $quanly['token'], 'csrf' => $quanly['csrf']], (int) $rotated['session_id']);
    $revokedNew = $app->redeem([
        'session_token' => $rotated['token'], 'csrf' => $rotated['csrf'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'after-revoke-new',
    ]);
    expect('revoked session cannot start a redemption', !$revokedNew['ok'] && $revokedNew['error'] === 'unauthorized', j($revokedNew));
    $app->setNow($start + Gn_Alex_Engine::SESSION_TTL + 5);
    $ttl = $app->redeem(array_merge(auth($quanly), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'ttl-expired-1',
    ]));
    expect('staff session ttl expires', !$ttl['ok'] && $ttl['error'] === 'unauthorized', j($ttl));
    $app->setNow($start);
    $ttlReplay = $app->redeem(array_merge(auth($quay), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $phone, 'proof_name' => $name,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => $firstIdem,
    ]));
    expect('offline retry of the first key still returns the initial result', $ttlReplay['ok'] && !empty($ttlReplay['replay']) && (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'] === 3, j($ttlReplay));

    $reissue = $app->reissue(array_merge(auth($quanly), [
        'voucher_id' => (int) $guest['voucher_id'], 'proof_phone' => $phone, 'proof_name' => $name,
    ]));
    $voucherNow = $db->fetch('SELECT beer_quota, beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']]);
    $voucherCount = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_voucher WHERE identity_id=? AND campaign_family=?', [(int) $guest['identity_id'], Gn_Alex_Engine::ALEX_FAMILY])['c'];
    expect('reissue rotates secret without new quota', $reissue['ok'] && $reissue['created'] === false && $reissue['ticket_secret'] !== $guest['ticket_secret'] && (int) $voucherNow['beer_quota'] === 3 && (int) $voucherNow['beer_issued'] === 3 && $voucherCount === 1, j($reissue));
    expect('old guest session revoked', !$app->guestStatus((string) $guest['guest_token'])['ok']);

    $sameFamily = $app->createCampaign(auth($quanly), [
        'slug' => 'alex-qr-switch', 'family' => Gn_Alex_Engine::ALEX_FAMILY, 'starts_at' => $start, 'ends_at' => $start + 28800, 'beer_quota' => 3, 'salad_quota' => 1,
    ]);
    $gate2 = csrf($app);
    $otp2 = $app->requestOtp(array_merge($gate2, [
        'locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => $name, 'campaign' => 'alex-qr-switch', 'ip' => '10.4.4.4',
    ]));
    $code2 = $mock->lastOtp($hmac);
    $again = $app->verifyOtp(['csrf' => $otp2['verify_csrf'], 'phone' => $phone, 'campaign' => 'alex-qr-switch', 'code' => $code2]);
    expect('same family qr does not mint another voucher', $sameFamily['ok'] && $again['ok'] && $again['created'] === false && (int) $again['voucher_id'] === (int) $guest['voucher_id'] && (int) $again['identity_id'] === (int) $guest['identity_id'], j($again));
    $other = $app->createCampaign(auth($quanly), [
        'slug' => 'other-night', 'family' => 'other-family', 'starts_at' => $start, 'ends_at' => $start + 28800, 'beer_quota' => 3, 'salad_quota' => 1,
    ]);
    $gate3 = csrf($app);
    $otp3 = $app->requestOtp(array_merge($gate3, [
        'locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => $name, 'campaign' => 'other-night', 'ip' => '10.4.4.5',
    ]));
    $code3 = $mock->lastOtp($hmac);
    $otherGuest = $app->verifyOtp(['csrf' => $otp3['verify_csrf'], 'phone' => $phone, 'campaign' => 'other-night', 'code' => $code3]);
    expect('cross campaign shares identity and separates family quota', $other['ok'] && $otherGuest['ok'] && $otherGuest['created'] === true && (int) $otherGuest['identity_id'] === (int) $guest['identity_id'] && (int) $otherGuest['voucher_id'] !== (int) $guest['voucher_id'], j($otherGuest));

    $policy = $app->updatePolicy(auth($quanly), Gn_Alex_Engine::ALEX_SLUG, 4, 1);
    $quotaStill = (int) $db->fetch('SELECT beer_quota FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_quota'];
    $auditPolicy = $db->fetch("SELECT * FROM gn_alex_audit WHERE action='policy_edit' ORDER BY id DESC LIMIT 1");
    expect('policy edit is audited and does not raise an issued voucher', $policy['ok'] && $quotaStill === 3 && $auditPolicy && str_contains((string) $auditPolicy['after_json'], '"beer_quota":4'), j($policy));
    $self = $app->requestOverride(auth($quanly), (int) $guest['voucher_id'], 1, 'guest shorted one beer');
    $selfApprove = $app->approveOverride(auth($quanly), (int) $self['override_id']);
    expect('staff cannot self approve a quota increase', $self['ok'] && !$selfApprove['ok'] && $selfApprove['error'] === 'self_approval_denied', j($selfApprove));
    $quayOverride = $app->requestOverride(auth($quay), (int) $guest['voucher_id'], 1, 'no');
    expect('counter staff cannot raise quota', !$quayOverride['ok']);
    $approved = $app->approveOverride(auth($quanlyB), (int) $self['override_id']);
    $raised = (int) $db->fetch('SELECT beer_quota FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_quota'];
    expect('two person override raises quota once', $approved['ok'] && $raised === 4, j($approved));

    $pendingPhone = '0912000033';
    $pendingName = 'Pending Guest';
    $pgate = csrf($app);
    $pOtp = $app->requestOtp(array_merge($pgate, [
        'locale' => 'en', 'consent' => 1, 'phone' => $pendingPhone, 'name' => $pendingName, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.5.5.5',
    ]));
    $pcode = $mock->lastOtp(Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize($pendingPhone), $pepper));
    $pguest = $app->verifyOtp(['csrf' => $pOtp['verify_csrf'], 'phone' => $pendingPhone, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => $pcode]);
    $quay2 = $app->staffLogin($en['staff_id'], $quayPin, '10.5.5.6');
    $claim = $app->redeem(array_merge(auth($quay2), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $pendingPhone, 'proof_name' => $pendingName,
        'confirm' => 0, 'item' => 'beer', 'idempotency_key' => 'pending-hold-1',
    ]));
    $held = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $pguest['voucher_id']])['beer_issued'];
    $app->setNow($start + Gn_Alex_Engine::HOLD_TTL + 5);
    $afterExpire = $app->redeem(array_merge(auth($quay2), [
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'proof_phone' => $pendingPhone, 'proof_name' => $pendingName,
        'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'pending-commit-2',
    ]));
    $issuedPending = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $pguest['voucher_id']])['beer_issued'];
    $expired = $db->fetch("SELECT status FROM gn_alex_redemption WHERE idempotency_key='pending-hold-1'");
    expect('expired pending claim does not double debit', $claim['ok'] && $claim['status'] === 'pending' && $held === 0 && $afterExpire['ok'] && $issuedPending === 1 && $expired['status'] === 'expired', j($claim) . j($afterExpire));
    $app->setNow($start);

    $ratePhone = '0912000044';
    $rateOk = 0;
    $rateHmac = Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize($ratePhone), $pepper);
    for ($i = 0; $i < 3; $i++) {
        $db->exec("UPDATE gn_alex_otp SET status='expired' WHERE phone_hmac=? AND status='pending'", [$rateHmac]);
        $rg = csrf($app);
        $rr = $app->requestOtp(array_merge($rg, [
            'locale' => 'en', 'consent' => 1, 'phone' => $ratePhone, 'name' => 'Rate Guest', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.6.6.' . $i,
        ]));
        if ($rr['ok']) $rateOk++;
    }
    $db->exec("UPDATE gn_alex_otp SET status='expired' WHERE phone_hmac=? AND status='pending'", [$rateHmac]);
    $rg = csrf($app);
    $blockedOtp = $app->requestOtp(array_merge($rg, [
        'locale' => 'en', 'consent' => 1, 'phone' => $ratePhone, 'name' => 'Rate Guest', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.6.6.9',
    ]));
    expect('otp rate limit', $rateOk === 3 && !$blockedOtp['ok'] && $blockedOtp['error'] === 'rate_limited', j($blockedOtp));

    $xssPhone = '0912000055';
    $xg = csrf($app);
    $xReq = $app->requestOtp(array_merge($xg, [
        'locale' => 'en', 'consent' => 1, 'phone' => $xssPhone, 'name' => 'Ann & "Bee" <script>', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.7.7.7',
    ]));
    $xcode = $mock->lastOtp(Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize($xssPhone), $pepper));
    $xguest = $app->verifyOtp(['csrf' => $xReq['verify_csrf'], 'phone' => $xssPhone, 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => $xcode]);
    $storedName = (string) $db->fetch('SELECT display_name FROM gn_alex_identity WHERE id=?', [(int) $xguest['identity_id']])['display_name'];
    $html = Gn_Alex_View::guest('en', ['display_name' => $storedName, 'phone_mask' => '+8491****055', 'remaining' => 3]);
    expect('xss name is stripped and escaped', $xguest['ok'] && !str_contains($storedName, '<') && str_contains($html, 'Ann &amp; &quot;Bee&quot;') && !str_contains($html, '<script>'), $storedName);

    $onPin = pin();
    $onEn = $app->enrollStaffForIsolatedTest('onboarding', $onPin, 'onboarding-1');
    $onboarding = $app->staffLogin($onEn['staff_id'], $onPin, '10.8.0.1');
    $ctv = $app->applyPartner(array_merge(csrf($app, 'partner_apply'), ['consent' => 1, 'role' => 'ctv', 'phone' => '0912000011', 'name' => '=1+1']));
    $ctvAgain = $app->applyPartner(array_merge(csrf($app, 'partner_apply'), ['consent' => 1, 'role' => 'ctv', 'phone' => '0912000011', 'name' => '=1+1']));
    $driver = $app->applyPartner(array_merge(csrf($app, 'partner_apply'), ['consent' => 1, 'role' => 'driver', 'phone' => '0912000022', 'name' => 'Lan Driver']));
    $earlyInvoice = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-EARLY', 'source' => 'manual', 'partner_public_id' => $driver['public_id'], 'food_base' => 1000, 'link_verified' => 1,
    ]);
    $ctvReview = $app->approvePartner(auth($quanly), (int) $ctv['partner_id']);
    $driverReview = $app->approvePartner(auth($quanly), (int) $driver['partner_id']);
    $selfGrant = $app->grantAttribution(auth($quanly), (int) $driver['partner_id']);
    $driverGrant = $app->grantAttribution(auth($onboarding), (int) $driver['partner_id']);
    $ctvGrant = $app->grantAttribution(auth($onboarding), (int) $ctv['partner_id']);
    expect('self-selected driver stays pending until a different role grants attribution', $ctv['ok'] && $ctv['referral'] === null && $ctv['reason'] === 'requires_approval' && $ctvAgain['public_id'] === $ctv['public_id'] && $driver['referral'] === null && !$earlyInvoice['ok'] && $earlyInvoice['error'] === 'partner_not_attributable' && $ctvReview['referral'] === null && $driverReview['referral'] === null && !$selfGrant['ok'] && $driverGrant['ok'] && $ctvGrant['ok'] && is_string($ctvGrant['referral']), j($earlyInvoice) . j($selfGrant) . j($driverGrant));
    $refPage = $http->handle('GET', $ctvGrant['referral'], ['lang' => 'en'], [], [], []);
    expect('referral page does not redeem', $refPage['status'] === 200 && str_contains($refPage['body'], 'data-redeems="0"') && !str_contains($refPage['body'], '0912000011'));

    $invoice = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-CTV-1', 'source' => 'manual', 'partner_public_id' => $ctv['public_id'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'food_base' => 200000, 'tax' => 20000, 'drinks' => 80000,
        'service_fee' => 15000, 'discount' => 10000, 'bill_total' => 999999, 'status' => 'approved_paid', 'link_verified' => 1,
    ]);
    $dup = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-CTV-1', 'source' => 'manual', 'partner_public_id' => $ctv['public_id'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'food_base' => '200000', 'link_verified' => 1, 'vat_treatment' => 'unknown',
    ]);
    $conflict = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-CTV-1', 'source' => 'import', 'partner_public_id' => $driver['public_id'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'food_base' => 100, 'link_verified' => 1,
    ]);
    $stillFood = (int) $db->fetch('SELECT food_base FROM gn_alex_invoice WHERE external_id=?', ['INV-CTV-1'])['food_base'];
    $conflictAudit = (string) $db->fetch("SELECT after_json FROM gn_alex_audit WHERE action='invoice_conflict' ORDER BY id DESC LIMIT 1")['after_json'];
    expect('conflicting invoice id does not overwrite', !$conflict['ok'] && $conflict['error'] === 'invoice_conflict' && empty($conflict['replay']) && $stillFood === 200000 && str_contains($conflictAudit, 'food_base') && !str_contains($conflictAudit, '0912000011'), j($conflict) . $conflictAudit);
    $paid = $app->markApprovedPaid(auth($thungan), 'INV-CTV-1', 'paid-inv-ctv-1');
    $ledger = $db->fetch("SELECT * FROM gn_alex_ledger WHERE invoice_id=? AND entry_type='pending_unpriced'", [(int) $invoice['invoice_id']]);
    $raw = (string) $db->fetch('SELECT raw_json FROM gn_alex_invoice WHERE id=?', [(int) $invoice['invoice_id']])['raw_json'];
    expect('paid invoice stays unpriced and ignores bill total', $invoice['ok'] && !empty($dup['replay']) && (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_invoice WHERE external_id=?', ['INV-CTV-1'])['c'] === 1 && $ledger['entry_type'] === 'pending_unpriced' && $ledger['amount_minor'] === null && $ledger['reason'] === 'requires_approval' && (int) $ledger['base_minor'] === 200000 && $paid['priced'] === false && !str_contains($raw, '999999'), j($paid) . j($ledger) . $raw);
    $pricedTooSoon = $app->priceAttribution(auth($onboarding), 'INV-CTV-1');
    expect('pricing without an approved rule requires approval', !$pricedTooSoon['ok'] && $pricedTooSoon['error'] === 'requires_approval', j($pricedTooSoon));
    $rule = $app->proposeRule(auth($quanly), 'ctv', $start - 10, $start + 86400);
    $ruleOk = $app->approveRule(auth($onboarding), (int) $rule['rule_id']);
    $priced = $app->priceAttribution(auth($onboarding), 'INV-CTV-1');
    expect('one explicit food-base price uses 5 percent after rule approval', $ruleOk['ok'] && $priced['ok'] && (int) $priced['amount_minor'] === 10000 && (int) $priced['rate_bps'] === 500 && $priced['automatic_commission'] === false && $priced['reason'] === 'explicit_approval_not_every_bill', j($priced));

    $driverInv = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-DRV-1', 'source' => 'manual', 'partner_public_id' => $driver['public_id'],
        'food_base' => 200000, 'drinks' => 50000, 'link_verified' => 1,
    ]);
    $driverPaid = $app->markApprovedPaid(auth($quanly), 'INV-DRV-1', 'paid-inv-drv-1');
    $driverRule = $app->proposeRule(auth($quanly), 'driver', $start - 10, $start + 86400);
    $driverRuleOk = $app->approveRule(auth($onboarding), (int) $driverRule['rule_id']);
    $driverPriced = $app->priceAttribution(auth($onboarding), 'INV-DRV-1');
    $driverSecond = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-DRV-2', 'source' => 'manual', 'partner_public_id' => $driver['public_id'],
        'food_base' => 200000, 'link_verified' => 1,
    ]);
    $driverPaid2 = $app->markApprovedPaid(auth($thungan), 'INV-DRV-2', 'paid-inv-drv-2');
    $secondQuote = $app->priceAttribution(auth($quanly), 'INV-DRV-2');
    $unpricedSecond = (int) $db->fetch("SELECT COUNT(*) AS c FROM gn_alex_ledger l JOIN gn_alex_invoice i ON i.id=l.invoice_id WHERE i.external_id='INV-DRV-2' AND l.entry_type='priced'")['c'];
    expect('driver 15 percent is not applied to every bill', $driverSecond['ok'] && $driverPaid['reason'] === 'requires_approval' && $driverPaid2['priced'] === false && $driverRuleOk['ok'] && $driverPriced['ok'] && (int) $driverPriced['amount_minor'] === 30000 && !$secondQuote['ok'] && $unpricedSecond === 0, j($driverPriced) . j($driverPaid2));
    expect('alex commission is never calculated', $app->alexCommission()['calculated'] === false && $app->alexCommission()['amount'] === null && $paid['alex_commission'] === null);

    $stay = $app->recordInvoice(auth($thungan), [
        'external_id' => 'INV-STAY-1', 'source' => 'import', 'partner_public_id' => $ctv['public_id'],
        'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'food_base' => 50000, 'link_verified' => 1,
    ]);
    $app->markApprovedPaid(auth($thungan), 'INV-STAY-1', 'paid-inv-stay-1');
    $paidReplay = $app->markApprovedPaid(auth($thungan), 'INV-CTV-1', 'paid-inv-ctv-1');
    $paidCross = $app->markApprovedPaid(auth($thungan), 'INV-STAY-1', 'paid-inv-ctv-1');
    expect('paid idempotency replays only the same invoice', !empty($paidReplay['replay']) && (int) $paidReplay['invoice_id'] === (int) $invoice['invoice_id'] && !$paidCross['ok'] && $paidCross['error'] === 'invoice_conflict', j($paidReplay) . j($paidCross));
    $metricsBeforeRefund = $app->publicMetrics(Gn_Alex_Engine::ALEX_SLUG, auth($quanly));
    expect('dashboard labels eligible food base from linked invoices only', $metricsBeforeRefund['linked_invoice_count'] === 2 && $metricsBeforeRefund['eligible_food_base'] === 250000 && !array_key_exists('pos_revenue', $metricsBeforeRefund), j($metricsBeforeRefund));
    $refundAsPaid = $app->reverseInvoice(auth($quanly), 'INV-CTV-1', 'refunded', 'paid-inv-ctv-1');
    expect('refund cannot reuse a paid idempotency key', !$refundAsPaid['ok'] && $refundAsPaid['error'] === 'invoice_conflict', j($refundAsPaid));
    $refund = $app->reverseInvoice(auth($quanly), 'INV-CTV-1', 'refunded', 'refund-inv-ctv-1');
    $refundAgain = $app->reverseInvoice(auth($quanly), 'INV-CTV-1', 'refunded', 'refund-inv-ctv-2');
    $net = 0;
    foreach ($db->fetchAll('SELECT entry_type, amount_minor FROM gn_alex_ledger WHERE invoice_id=?', [(int) $invoice['invoice_id']]) as $entry) {
        $net += $entry['entry_type'] === 'reversal' ? -(int) $entry['amount_minor'] : (int) $entry['amount_minor'];
    }
    $metricsAfter = $app->publicMetrics(Gn_Alex_Engine::ALEX_SLUG, auth($quanly));
    expect('refund drops the eligible food base', $refund['ok'] && !$refundAgain['ok'] && $refundAgain['error'] === 'already_reversed' && $metricsAfter['eligible_food_base'] === 50000, j($refundAgain) . ' net ' . $net . ' ' . j($metricsAfter));

    $cipher = 'enc:v1:' . bin2hex(random_bytes(12));
    $bankDenied = $app->requestBank(auth($thungan), (int) $ctv['partner_id'], $cipher);
    $plainBank = $app->requestBank(auth($quanly), (int) $ctv['partner_id'], '123456789');
    $bank = $app->requestBank(auth($quanly), (int) $ctv['partner_id'], $cipher);
    $bankSelf = $app->approveBank(auth($quanly), (int) ($bank['bank_id'] ?? 0), $cipher);
    $bankOk = $app->approveBank(auth($quanlyB), (int) $bank['bank_id'], $cipher);
    $auditText = '';
    foreach ($db->fetchAll('SELECT before_json, after_json FROM gn_alex_audit') as $auditRow) {
        $auditText .= $auditRow['before_json'] . $auditRow['after_json'];
    }
    $bankRows = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_bank')['c'];
    expect('bank collection stays blocked and stores nothing', !$bankDenied['ok'] && $bankDenied['error'] === 'payout_provider_pending' && !$plainBank['ok'] && !$bank['ok'] && !$bankOk['ok'] && $bankRows === 0 && !str_contains($auditText, $cipher), j($bankDenied));
    $csv = $app->exportCsv(auth($quanly));
    $csvDenied = $app->exportCsv(['session_token' => '', 'csrf' => '']);
    expect('csv escapes formula injection and hides full phone', $csv['ok'] && str_contains($csv['csv'], "'=1+1") && !str_contains($csv['csv'], '0912000011') && !$csvDenied['ok'], $csv['csv'] ?? '');
    $listDenied = $app->listCustomers([], Gn_Alex_Engine::ALEX_SLUG);
    $list = $app->listCustomers(auth($quanly), Gn_Alex_Engine::ALEX_SLUG);
    expect('customer list is not public and is masked for managers', !$listDenied['ok'] && $listDenied['error'] === 'customer_list_not_public' && $list['ok'] && !str_contains(j($list), $canon), j($listDenied));

    $outageClient = new class implements Gn_Alex_Get_Client {
        public function get(string $url): string { throw new RuntimeException('down'); }
    };
    $emptyPos = engine($db, $mock, false, true, null, '');
    $emptyPos->setNow($start);
    $noEndpoint = $emptyPos->posRead();
    expect('missing pos endpoint does not pretend success', !$noEndpoint['ok'] && $noEndpoint['error'] === 'no_supported_endpoint' && $noEndpoint['simulated'] === false && $noEndpoint['wrote'] === false, j($noEndpoint));
    expect('pos write is unsupported', $app->posWrite()['error'] === 'pos_write_unsupported' && $app->posWrite()['simulated'] === false);
    $okClient = new class implements Gn_Alex_Get_Client {
        public function get(string $url): string {
            return json_encode(['items' => [
                ['kind' => 'food', 'amount' => 12345, 'name' => '=food'],
                ['kind' => 'drink', 'amount' => 999, 'name' => 'beer'],
            ]]);
        }
    };
    $pos = engine($db, $mock, false, true, $okClient, 'https://cukcuk.example/read');
    $pos->setNow($start);
    $snap = $pos->posRead();
    $down = engine($db, $mock, false, true, $outageClient, 'https://cukcuk.example/read');
    $down->setNow($start);
    $fallback = $down->posRead();
    $recon = $down->posReconcileCsv();
    $metricsPos = $app->publicMetrics(Gn_Alex_Engine::ALEX_SLUG, auth($quanly));
    expect('pos outage falls back to the last snapshot and does not invent food base', $snap['ok'] && $snap['invoices_created'] === 0 && $snap['wrote'] === false && !$fallback['ok'] && $fallback['error'] === 'pos_outage' && (int) $fallback['fallback_snapshot_at'] === (int) $snap['fetched_at'] && str_contains($recon['csv'], "'=food") && $recon['vat_unresolved'] === true && $recon['food_total'] === null && !str_contains($recon['csv'], 'eligible_food_lines') && $metricsPos['eligible_food_base'] === 50000, j($fallback));

    $crm = $app->crmBoundary();
    expect('crm stays pending wordpress facts without automatic commission', $crm['writes'] === false && $crm['crm_posts'] === false && $crm['automatic_commission'] === false && $crm['staged_attribution_only'] === true && $crm['p1_production_proven'] === false && $crm['beautyos_present'] === false, j($crm));

    $unauth = $app->redeem(['campaign' => Gn_Alex_Engine::ALEX_SLUG, 'confirm' => 1, 'item' => 'beer', 'idempotency_key' => 'no-auth-1']);
    expect('unauthorized redeem denied', !$unauth['ok'] && $unauth['error'] === 'unauthorized');

    $boot = $app->bootstrapQuanly(['manage_options' => false], pin());
    expect('bootstrap requires a wordpress administrator', !$boot['ok']);
    $secondManager = $app->bootstrapQuanly(['manage_options' => true, 'id' => 44], pin(), 'quanly');
    $enrollTooEarly = new Gn_Alex_Engine(Gn_Alex_Storage::sqlite(sys_get_temp_dir() . '/gn-alex-empty-' . bin2hex(random_bytes(3)) . '.sqlite'), $pepper, $tokenKey, $mock, false, true);
    $enrollTooEarly->db->install();
    $earlyStaff = $enrollTooEarly->enrollFromWpAdmin(['manage_options' => true, 'id' => 7], 'quay', pin(), 'early');
    $enrollDenied = $app->enrollFromWpAdmin(['manage_options' => false], 'quay', pin(), 'nope');
    $extraPin = pin();
    $enrolled = $app->enrollFromWpAdmin(['manage_options' => true, 'id' => 44], 'quay', $extraPin, 'counter-extra');
    $enrollAudit = $db->fetch("SELECT before_json, after_json FROM gn_alex_audit WHERE action='staff_enroll' ORDER BY id DESC LIMIT 1");
    expect('wordpress admin can enroll another counter person', !$secondManager['ok'] && $secondManager['error'] === 'already_bootstrapped' && !$earlyStaff['ok'] && $earlyStaff['error'] === 'bootstrap_required' && !$enrollDenied['ok'] && $enrolled['ok'] && $enrolled['role'] === 'quay' && !str_contains(j($enrollAudit), $extraPin), j($secondManager) . j($earlyStaff) . j($enrolled));
    expect('admin notices name success and denial', Gn_Alex_Http::outcomeMessage('gn_alex', 'ok') === 'Manager created.' && str_contains(Gn_Alex_Http::outcomeMessage('gn_alex_staff', 'denied'), 'first manager'));
    $queueDenied = $app->partnerQueue(auth($quay));
    $queue = $app->partnerQueue(auth($quanly));
    expect('partner queue is private to review roles', !$queueDenied['ok'] && $queue['ok'] && $queue['payout_enabled'] === false && !str_contains(j($queue), '0912000011'), j($queueDenied));
    $desk = $http->handle('GET', '/gn-alex/desk', [], [], [], []);
    expect('onboarding desk has no public application list', $desk['status'] === 200 && !str_contains($desk['body'], '0912000011') && str_contains($desk['body'], 'Payout is disabled'), $desk['body']);

    $unsigned = $app->ingestWebhook('{"event_key":"unsigned-key-1","tenant":"ganhngon"}', []);
    expect('webhook without a server secret stores nothing', !$unsigned['ok'] && $unsigned['error'] === 'webhook_secret_missing' && (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_webhook')['c'] === 0, j($unsigned));
    $secret = bin2hex(random_bytes(16));
    $signed = new Gn_Alex_Engine($db, $pepper, $tokenKey, $mock, false, true, null, '', $secret, 'ganhngon');
    $signed->setNow($start);
    $body1 = json_encode(['event_key' => 'signed-key-1', 'tenant' => 'ganhngon', 'source' => 'manual', 'link_verified' => true, 'partner_public_id' => $ctv['public_id'], 'food_base' => 70000, 'bill_total' => 999999], JSON_UNESCAPED_SLASHES);
    $headers1 = [
        'x-gn-timestamp' => (string) $start,
        'x-gn-signature' => hash_hmac('sha256', $start . '.' . $body1, $secret),
        'x-gn-tenant' => 'ganhngon',
    ];
    $badSig = $signed->ingestWebhook($body1, ['x-gn-timestamp' => (string) $start, 'x-gn-signature' => str_repeat('ab', 32), 'x-gn-tenant' => 'ganhngon']);
    $shortSig = $signed->ingestWebhook($body1, ['x-gn-timestamp' => (string) $start, 'x-gn-signature' => 'abc', 'x-gn-tenant' => 'ganhngon']);
    $oldTs = $signed->ingestWebhook($body1, ['x-gn-timestamp' => (string) ($start - 301), 'x-gn-signature' => hash_hmac('sha256', ($start - 301) . '.' . $body1, $secret), 'x-gn-tenant' => 'ganhngon']);
    $badTenant = $signed->ingestWebhook($body1, ['x-gn-timestamp' => (string) $start, 'x-gn-signature' => hash_hmac('sha256', $start . '.' . $body1, $secret), 'x-gn-tenant' => 'other']);
    $okHook = $signed->ingestWebhook($body1, $headers1);
    $replayHook = $signed->ingestWebhook($body1, $headers1);
    $body2 = json_encode(['event_key' => 'signed-key-1', 'tenant' => 'ganhngon', 'food_base' => 1], JSON_UNESCAPED_SLASHES);
    $conflict = $signed->ingestWebhook($body2, [
        'x-gn-timestamp' => (string) $start,
        'x-gn-signature' => hash_hmac('sha256', $start . '.' . $body2, $secret),
        'x-gn-tenant' => 'ganhngon',
    ]);
    $hookRows = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_webhook')['c'];
    $hookInvoice = (int) $db->fetch("SELECT COUNT(*) AS c FROM gn_alex_invoice WHERE external_id='signed-key-1'")['c'];
    $stored = $db->fetch('SELECT result_json, body_hash, signed_timestamp FROM gn_alex_webhook WHERE idempotency_key=?', ['signed-key-1']);
    expect('signed webhook rejects bad signature, stale time, and wrong tenant', !$badSig['ok'] && $badSig['error'] === 'webhook_signature' && !$shortSig['ok'] && $shortSig['error'] === 'webhook_signature' && !$oldTs['ok'] && $oldTs['error'] === 'webhook_timestamp' && !$badTenant['ok'] && $badTenant['error'] === 'webhook_tenant', j($badSig) . j($oldTs) . j($badTenant));
    expect('signed replay returns the first result and a different body conflicts', $okHook['ok'] && $okHook['attributed'] === false && $okHook['link_verified'] === false && $okHook['reason'] === 'manual_spoof_rejected' && !empty($replayHook['replay']) && !$conflict['ok'] && $conflict['error'] === 'webhook_conflict' && $hookRows === 1 && $hookInvoice === 0 && !str_contains((string) $stored['result_json'], $secret) && (int) $stored['signed_timestamp'] === $start, j($okHook) . j($conflict));
    $forged = $app->requestOtp(['csrf' => bin2hex(random_bytes(16)), 'locale' => 'en', 'consent' => 1, 'phone' => '0912000066', 'name' => 'Forged', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.0.1']);
    $once = csrf($app);
    $firstUse = $app->requestOtp(array_merge($once, ['locale' => 'en', 'consent' => 1, 'phone' => '0912000066', 'name' => 'Once', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.0.2']));
    $secondUse = $app->requestOtp(array_merge($once, ['locale' => 'en', 'consent' => 1, 'phone' => '0912000067', 'name' => 'Twice', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.0.3']));
    $stale = csrf($app);
    $app->setNow($start + 601);
    $staleUse = $app->requestOtp(array_merge($stale, ['locale' => 'en', 'consent' => 1, 'phone' => '0912000068', 'name' => 'Stale', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.0.4']));
    $app->setNow($start);
    expect('registration token is stored, single use, and expires', !$forged['ok'] && $forged['error'] === 'csrf_failed' && $firstUse['ok'] && !$secondUse['ok'] && !$staleUse['ok'], j($forged) . j($secondUse) . j($staleUse));
    $pendingProbe = csrf($app);
    $live = $app->requestOtp(array_merge($pendingProbe, ['locale' => 'en', 'consent' => 1, 'phone' => '0912000088', 'name' => 'Live', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.0.8']));
    $againOtp = $app->requestOtp(array_merge(csrf($app), ['locale' => 'en', 'consent' => 1, 'phone' => '0912000088', 'name' => 'Live', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.0.9']));
    $liveCount = (int) $db->fetch("SELECT COUNT(*) AS c FROM gn_alex_otp WHERE phone_hmac=? AND status='pending'", [Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize('0912000088'), $pepper)])['c'];
    expect('a second otp is not sent while one is still pending', $live['ok'] && !$againOtp['ok'] && $againOtp['error'] === 'otp_already_pending' && $liveCount === 1, j($againOtp));

    $gwBox = new class implements Gn_Alex_Otp_Transport {
        public string $otp = '';
        public function deliver(string $phoneHmac, string $otp, array $credentials): array {
            $this->otp = $otp;
            return ['ack_id' => 'gw-ack-77'];
        }
    };
    $gwDb = Gn_Alex_Storage::sqlite(sys_get_temp_dir() . '/gn-alex-gw-' . bin2hex(random_bytes(3)) . '.sqlite');
    $gwDb->install();
    $gw = new Gn_Alex_Otp_Provider_VerifiedGateway(['endpoint' => 'https://sms.example/send', 'secret' => bin2hex(random_bytes(16))], $gwBox);
    $gwApp = engine($gwDb, $gw, true, false);
    $gwApp->ensureAlexCampaign();
    $gwGate = csrf($gwApp);
    $gwReq = $gwApp->requestOtp(array_merge($gwGate, [
        'locale' => 'en', 'consent' => 1, 'phone' => '0912000077', 'name' => 'Gateway Guest', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.8.8.8',
    ]));
    $gwRow = $gwDb->fetch('SELECT * FROM gn_alex_otp ORDER BY id DESC LIMIT 1');
    $gwVerify = $gwApp->verifyOtp(['csrf' => $gwReq['verify_csrf'], 'phone' => '0912000077', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'code' => $gwBox->otp]);
    expect('verified gateway still checks the otp hash', $gw->isProductionVerified() && $gwReq['ok'] && $gwBox->otp !== '' && !str_contains(j($gwRow), $gwBox->otp) && $gwVerify['ok'], j($gwReq));
    $silent = new class implements Gn_Alex_Otp_Transport {
        public function deliver(string $phoneHmac, string $otp, array $credentials): array { return []; }
    };
    $silentApp = engine($gwDb, new Gn_Alex_Otp_Provider_VerifiedGateway(['endpoint' => 'https://sms.example/send', 'secret' => bin2hex(random_bytes(16))], $silent), true, false);
    $silentApp->setNow($start);
    $silentRes = $silentApp->requestOtp(array_merge(csrf($silentApp), [
        'locale' => 'en', 'consent' => 1, 'phone' => '0912000078', 'name' => 'Silent', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.8.8.9',
    ]));
    expect('gateway without acknowledgement fail closed', !$silentRes['ok'] && $silentRes['error'] === 'otp_provider_required', j($silentRes));

    recordConcurrency($dbPath);
    recordMysql();
    $racePhone = '0912555001';
    $raceHmac = Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize($racePhone), $pepper);
    $db->exec('DELETE FROM gn_alex_rate WHERE bucket=?', ['otp:' . $raceHmac]);
    $db->insert('INSERT INTO gn_alex_rate (bucket, n, reset_at) VALUES (?,?,?)', ['otp:' . $raceHmac, 2, $start + 600]);
    $tokA = csrf($app);
    $tokB = csrf($app);
    $raceCfg = ['driver' => 'sqlite', 'sqlite' => $dbPath, 'pepper' => $pepper, 'token_key' => $tokenKey, 'now' => $start, 'action' => 'otp'];
    $race = runPair([
        $raceCfg + ['request' => $tokA + ['locale' => 'en', 'consent' => 1, 'phone' => $racePhone, 'name' => 'Race Otp', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.4.1']],
        $raceCfg + ['request' => $tokB + ['locale' => 'en', 'consent' => 1, 'phone' => $racePhone, 'name' => 'Race Otp', 'campaign' => Gn_Alex_Engine::ALEX_SLUG, 'ip' => '10.9.4.2']],
    ]);
    $openReconcile = $http->handle('GET', '/gn-alex/v1/pos/reconcile', [], [], [], []);
    $bearerOnly = $http->handle('GET', '/gn-alex/v1/pos/reconcile', [], [], ['authorization' => 'Bearer ' . $quanly['token']], []);
    $quayReconcile = $http->handle('GET', '/gn-alex/v1/pos/reconcile', [], [], ['authorization' => 'Bearer ' . $quay['token'], 'x-gn-csrf' => $quay['csrf']], []);
    $managerReconcile = $http->handle('GET', '/gn-alex/v1/pos/reconcile', [], [], ['authorization' => 'Bearer ' . $quanly['token'], 'x-gn-csrf' => $quanly['csrf']], []);
    $dashNoCsrf = $http->handle('GET', '/gn-alex/dashboard', [], [], ['authorization' => 'Bearer ' . $quanly['token']], []);
    expect('reconcile and dashboard require a manager session and csrf', $openReconcile['status'] === 401 && $bearerOnly['status'] === 401 && $quayReconcile['status'] === 403 && $managerReconcile['status'] === 200 && $dashNoCsrf['status'] === 401, $openReconcile['body'] . $quayReconcile['body'] . $managerReconcile['body']);
    expect('staff enrollment is not a public REST route', !isset(Gn_Alex_Http::apiRoutes()['/gn-alex/v1/staff/enroll']));
    $noSessionBank = $app->approveBank([], 1, 'enc:v1:' . bin2hex(random_bytes(8)));
    expect('bank approval stays closed with no stored account', !$noSessionBank['ok'] && $noSessionBank['error'] === 'payout_provider_pending' && (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_bank')['c'] === 0, j($noSessionBank));
    $paidRace = runPair([
        ['driver' => 'sqlite', 'sqlite' => $dbPath, 'pepper' => $pepper, 'token_key' => $tokenKey, 'now' => $start, 'action' => 'paid', 'auth' => auth($thungan), 'external_id' => 'INV-STAY-1', 'idem' => 'paid-race-stay-1'],
        ['driver' => 'sqlite', 'sqlite' => $dbPath, 'pepper' => $pepper, 'token_key' => $tokenKey, 'now' => $start, 'action' => 'paid', 'auth' => auth($thungan), 'external_id' => 'INV-STAY-1', 'idem' => 'paid-race-stay-1'],
    ]);
    $paidEvents = (int) $db->fetch("SELECT COUNT(*) AS c FROM gn_alex_invoice_event WHERE idempotency_key='paid-race-stay-1'")['c'];
    $paidReplayCount = (int) (!empty($paidRace[0]['replay'])) + (int) (!empty($paidRace[1]['replay']));
    expect('sqlite paid idempotency race keeps one event', !empty($paidRace[0]['ok']) && !empty($paidRace[1]['ok']) && $paidReplayCount === 1 && $paidEvents === 1, j($paidRace[0]) . j($paidRace[1]));

    $raceOk = (int) (!empty($race[0]['ok'])) + (int) (!empty($race[1]['ok']));
    $racePending = (int) $db->fetch("SELECT COUNT(*) AS c FROM gn_alex_otp WHERE phone_hmac=? AND status='pending'", [$raceHmac])['c'];
    expect('concurrent otp requests cannot pass the identity quota', $raceOk === 1 && $racePending === 1, j($race[0]) . j($race[1]));

    blocked('live commercial sms otp', 'No SMS credential or verified transport is configured in this repository. Registration stays fail closed. The isolated mock and gateway double above are test-only.');
    blocked('live wordpress bootstrap', 'wp-config.php is not part of this workspace and this run does not connect the plugin to a production WordPress database. Route behavior is covered by Gn_Alex_Http, which the plugin delegates to.');
} catch (Throwable $e) {
    fail('suite exception', $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}

$pass = 0; $failN = 0; $blockN = 0;
foreach ($results as $row) {
    if ($row[0] === 'PASS') $pass++;
    elseif ($row[0] === 'FAIL') $failN++;
    else $blockN++;
}
echo "SUMMARY pass={$pass} fail={$failN} blocked={$blockN}" . PHP_EOL;
exit($failN > 0 ? 1 : 0);

function recordConcurrency(string $ignored): void {
    global $start;
    $path = sys_get_temp_dir() . '/gn-alex-race-' . bin2hex(random_bytes(3)) . '.sqlite';
    $pepper = bin2hex(random_bytes(24));
    $tokenKey = bin2hex(random_bytes(24));
    $db = Gn_Alex_Storage::sqlite($path);
    $db->install();
    $mock = new Gn_Alex_Otp_Provider_IsolatedTestMock(true, false);
    $app = new Gn_Alex_Engine($db, $pepper, $tokenKey, $mock, false, true);
    $app->setNow($start);
    $app->ensureAlexCampaign();
    $pin = (string) random_int(30000000, 39999999);
    $staff = $app->enrollStaffForIsolatedTest('quay', $pin, 'race-quay');
    $login = $app->staffLogin($staff['staff_id'], $pin, '10.9.9.1');
    $quanlyPin = (string) random_int(40000000, 49999999);
    $quanly = $app->enrollStaffForIsolatedTest('quanly', $quanlyPin, 'race-quanly');
    $qLogin = $app->staffLogin($quanly['staff_id'], $quanlyPin, '10.9.9.2');
    $app->createCampaign(['session_token' => $qLogin['token'], 'csrf' => $qLogin['csrf']], [
        'slug' => 'race-night', 'family' => 'race-family', 'starts_at' => $start, 'ends_at' => $start + 86400, 'beer_quota' => 1, 'salad_quota' => 0,
    ]);
    $phone = '0933000001';
    $reqTok = $app->issueRegistrationToken('otp_request');
    $sent = $app->requestOtp(['csrf' => $reqTok['csrf'], 'locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => 'Race Guest', 'campaign' => 'race-night', 'ip' => '10.9.9.3']);
    $code = $mock->lastOtp(Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize($phone), $pepper));
    $guest = $app->verifyOtp(['csrf' => $sent['verify_csrf'], 'phone' => $phone, 'campaign' => 'race-night', 'code' => $code]);
    if (!$guest['ok']) {
        fail('sqlite concurrent redemption', j($guest));
        return;
    }
    $base = [
        'session_token' => $login['token'], 'csrf' => $login['csrf'], 'campaign' => 'race-night',
        'proof_phone' => $phone, 'proof_name' => 'Race Guest', 'confirm' => 1, 'item' => 'beer',
    ];
    [$a, $b] = runPair([
        ['driver' => 'sqlite', 'sqlite' => $path, 'pepper' => $pepper, 'token_key' => $tokenKey, 'now' => $start, 'request' => $base + ['idempotency_key' => 'race-key-aaa']],
        ['driver' => 'sqlite', 'sqlite' => $path, 'pepper' => $pepper, 'token_key' => $tokenKey, 'now' => $start, 'request' => $base + ['idempotency_key' => 'race-key-bbb']],
    ]);
    $okCount = (int) (!empty($a['ok'])) + (int) (!empty($b['ok']));
    $issued = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'];
    expect('sqlite transaction allows one of two concurrent redeems', $okCount === 1 && $issued === 1, j($a) . j($b));
    expect('sqlite lock sql documents the immediate transaction limit', !str_contains($db->lockSql('voucher'), 'FOR UPDATE'));
}

function recordMysql(): void {
    global $start;
    $pass = bin2hex(random_bytes(9));
    $sql = "DROP DATABASE IF EXISTS gn_alex_it; CREATE DATABASE gn_alex_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; "
        . "CREATE USER IF NOT EXISTS 'gn_alex_it'@'127.0.0.1' IDENTIFIED BY '{$pass}'; "
        . "ALTER USER 'gn_alex_it'@'127.0.0.1' IDENTIFIED BY '{$pass}'; "
        . "GRANT ALL PRIVILEGES ON gn_alex_it.* TO 'gn_alex_it'@'127.0.0.1'; FLUSH PRIVILEGES;";
    exec('sudo mysql --batch -e ' . escapeshellarg($sql), $out, $code);
    if ($code !== 0) {
        blocked('mysql transaction integration', 'MariaDB root client did not accept the local test database command.');
        return;
    }
    try {
        $db = Gn_Alex_Storage::mysql('mysql:host=127.0.0.1;dbname=gn_alex_it;charset=utf8mb4', 'gn_alex_it', $pass);
        $db->install();
    } catch (Throwable $e) {
        blocked('mysql transaction integration', $e->getMessage());
        return;
    }
    expect('mysql lock sql uses FOR UPDATE', str_contains($db->lockSql('voucher'), 'FOR UPDATE') && str_contains($db->lockSql('invoice', 'external_id=?'), 'FOR UPDATE'));
    $db->begin();
    $db->insert('INSERT INTO gn_alex_identity (phone_hmac, phone_mask, display_name, created_at) VALUES (?,?,?,?)', [str_repeat('a', 64), '+8491****000', 'Rollback', $start]);
    $db->rollback();
    $left = (int) $db->fetch('SELECT COUNT(*) AS c FROM gn_alex_identity')['c'];
    expect('mysql transaction rollback removes the uncommitted identity', $left === 0, 'left ' . $left);

    $pepper = bin2hex(random_bytes(24));
    $tokenKey = bin2hex(random_bytes(24));
    $mock = new Gn_Alex_Otp_Provider_IsolatedTestMock(true, false);
    $app = new Gn_Alex_Engine($db, $pepper, $tokenKey, $mock, false, true);
    $app->setNow($start);
    $app->ensureAlexCampaign();
    $pin = (string) random_int(51000000, 51999999);
    $staff = $app->enrollStaffForIsolatedTest('quay', $pin, 'my-quay');
    $login = $app->staffLogin($staff['staff_id'], $pin, '10.9.1.1');
    $qpin = (string) random_int(52000000, 52999999);
    $q = $app->enrollStaffForIsolatedTest('quanly', $qpin, 'my-quanly');
    $qLogin = $app->staffLogin($q['staff_id'], $qpin, '10.9.1.2');
    $app->createCampaign(['session_token' => $qLogin['token'], 'csrf' => $qLogin['csrf']], [
        'slug' => 'mysql-race', 'family' => 'mysql-family', 'starts_at' => $start, 'ends_at' => $start + 86400, 'beer_quota' => 1, 'salad_quota' => 0,
    ]);
    $phone = '0933000002';
    $reqTok = $app->issueRegistrationToken('otp_request');
    $sent = $app->requestOtp(['csrf' => $reqTok['csrf'], 'locale' => 'en', 'consent' => 1, 'phone' => $phone, 'name' => 'Mysql Guest', 'campaign' => 'mysql-race', 'ip' => '10.9.1.3']);
    $code = $mock->lastOtp(Gn_Alex_Phone::hmac(Gn_Alex_Phone::canonicalize($phone), $pepper));
    $guest = $app->verifyOtp(['csrf' => $sent['verify_csrf'], 'phone' => $phone, 'campaign' => 'mysql-race', 'code' => $code]);
    if (!$guest['ok']) {
        fail('mysql concurrent redemption', j($guest));
        return;
    }
    $base = [
        'session_token' => $login['token'], 'csrf' => $login['csrf'], 'campaign' => 'mysql-race',
        'proof_phone' => $phone, 'proof_name' => 'Mysql Guest', 'confirm' => 1, 'item' => 'beer',
    ];
    $cfg = ['driver' => 'mysql', 'dsn' => 'mysql:host=127.0.0.1;dbname=gn_alex_it;charset=utf8mb4', 'user' => 'gn_alex_it', 'pass' => $pass, 'pepper' => $pepper, 'token_key' => $tokenKey, 'now' => $start];
    $bodies = runPair([
        $cfg + ['request' => $base + ['idempotency_key' => 'race-my-aaa']],
        $cfg + ['request' => $base + ['idempotency_key' => 'race-my-bbb']],
    ]);
    $okCount = (int) (!empty($bodies[0]['ok'])) + (int) (!empty($bodies[1]['ok']));
    $issued = (int) $db->fetch('SELECT beer_issued FROM gn_alex_voucher WHERE id=?', [(int) $guest['voucher_id']])['beer_issued'];
    expect('mysql transaction allows one of two concurrent redeems', $okCount === 1 && $issued === 1, j($bodies[0]) . j($bodies[1]));
    $ident = $db->insert('INSERT INTO gn_alex_identity (phone_hmac, phone_mask, display_name, created_at) VALUES (?,?,?,?)', [str_repeat('b', 64), '+8491****111', 'Pay', $start]);
    $partnerId = $db->insert(
        'INSERT INTO gn_alex_partner (public_id, identity_id, role, status, attribution, reviewer_id, campaign_family, display_name, created_at) VALUES (?,?,?,?,?,?,?,?,?)',
        ['GNPTESTPAID', $ident, 'ctv', 'attributable', 'granted', (int) $q['staff_id'], 'referral-food', 'Pay', $start]
    );
    $db->insert(
        'INSERT INTO gn_alex_invoice (external_id, partner_id, campaign_id, source, food_base, tax, drinks, service_fee, discount, link_verified, raw_json, created_at) VALUES (?,?,NULL,?,?,0,0,0,0,1,?,?)',
        ['INV-MY-PAID', $partnerId, 'manual', 1000, '{}', $start]
    );
    $mysqlPaid = runPair([
        $cfg + ['action' => 'paid', 'auth' => ['session_token' => $qLogin['token'], 'csrf' => $qLogin['csrf']], 'external_id' => 'INV-MY-PAID', 'idem' => 'paid-my-race-1'],
        $cfg + ['action' => 'paid', 'auth' => ['session_token' => $qLogin['token'], 'csrf' => $qLogin['csrf']], 'external_id' => 'INV-MY-PAID', 'idem' => 'paid-my-race-1'],
    ]);
    $mysqlEvents = (int) $db->fetch("SELECT COUNT(*) AS c FROM gn_alex_invoice_event WHERE idempotency_key='paid-my-race-1'")['c'];
    $mysqlReplays = (int) (!empty($mysqlPaid[0]['replay'])) + (int) (!empty($mysqlPaid[1]['replay']));
    expect('mysql paid idempotency race keeps one event', !empty($mysqlPaid[0]['ok']) && !empty($mysqlPaid[1]['ok']) && $mysqlReplays === 1 && $mysqlEvents === 1, j($mysqlPaid[0]) . j($mysqlPaid[1]));
}

/** @param list<array<string,mixed>> $configs @return list<array<string,mixed>> */
function runPair(array $configs): array {
    $procs = [];
    $pipesAll = [];
    $files = [];
    foreach ($configs as $cfg) {
        $file = tempnam(sys_get_temp_dir(), 'gnw');
        file_put_contents($file, json_encode($cfg));
        $files[] = $file;
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/worker.php') . ' ' . escapeshellarg($file);
        $pipes = [];
        $procs[] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $pipesAll[] = $pipes;
    }
    $out = [];
    foreach ($procs as $i => $proc) {
        $raw = stream_get_contents($pipesAll[$i][1]);
        $err = stream_get_contents($pipesAll[$i][2]);
        proc_close($proc);
        unlink($files[$i]);
        $decoded = json_decode($raw, true);
        $out[] = is_array($decoded) ? $decoded : ['ok' => false, 'error' => 'worker', 'raw' => $raw, 'stderr' => $err];
    }
    return $out;
}

/** @param array<string,mixed> $cfg */
function runWorker(array $cfg): array {
    $file = tempnam(sys_get_temp_dir(), 'gnw');
    file_put_contents($file, json_encode($cfg));
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/worker.php') . ' ' . escapeshellarg($file);
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $raw = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    proc_close($proc);
    unlink($file);
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'error' => 'worker', 'raw' => $raw, 'stderr' => $err];
}
