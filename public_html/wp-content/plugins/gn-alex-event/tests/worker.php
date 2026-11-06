<?php
require __DIR__ . '/bootstrap.php';
$cfg = json_decode((string) file_get_contents($argv[1]), true);
if (!is_array($cfg)) {
    fwrite(STDERR, "bad config\n");
    exit(2);
}
if ($cfg['driver'] === 'mysql') {
    $db = Gn_Alex_Storage::mysql($cfg['dsn'], $cfg['user'], $cfg['pass'], 'gn_alex_');
} else {
    $db = Gn_Alex_Storage::sqlite($cfg['sqlite']);
}
$engine = new Gn_Alex_Engine(
    $db,
    $cfg['pepper'],
    $cfg['token_key'],
    new Gn_Alex_Otp_Provider_Unconfigured(),
    false,
    false
);
$engine->setNow((int) $cfg['now']);
if (($cfg['action'] ?? 'redeem') === 'paid') {
    echo json_encode($engine->markApprovedPaid($cfg['auth'], (string) $cfg['external_id'], (string) $cfg['idem']));
    exit;
}
if (($cfg['action'] ?? 'redeem') === 'otp') {
    $engine = new Gn_Alex_Engine(
        $db,
        $cfg['pepper'],
        $cfg['token_key'],
        new Gn_Alex_Otp_Provider_IsolatedTestMock(true, false),
        false,
        true
    );
    $engine->setNow((int) $cfg['now']);
    echo json_encode($engine->requestOtp($cfg['request']));
    exit;
}
echo json_encode($engine->redeem($cfg['request']));
