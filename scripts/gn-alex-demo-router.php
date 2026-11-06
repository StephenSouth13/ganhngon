<?php
/**
 * Local no-auth page server. OTP stays fail-closed. No staff PIN is seeded.
 * php -S 127.0.0.1:8765 scripts/gn-alex-demo-router.php
 */
$root = dirname(__DIR__) . '/public_html/wp-content/plugins/gn-alex-event';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (str_starts_with($uri, '/assets/')) {
    $file = $root . $uri;
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($ext === 'css' ? 'text/css' : 'text/javascript') . '; charset=utf-8');
        readfile($file);
        return true;
    }
}
require $root . '/includes/class-gn-alex-schema.php';
require $root . '/includes/class-gn-alex-storage.php';
require $root . '/includes/class-gn-alex-phone.php';
require $root . '/includes/class-gn-alex-providers.php';
require $root . '/includes/class-gn-alex-copy.php';
require $root . '/includes/class-gn-alex-engine.php';
require $root . '/includes/class-gn-alex-view.php';
require $root . '/includes/class-gn-alex-http.php';
$dir = dirname(__DIR__) . '/var';
if (!is_dir($dir)) {
    mkdir($dir, 0700, true);
}
$storage = Gn_Alex_Storage::sqlite($dir . '/gn-alex-demo.sqlite');
$storage->install();
$keyFile = $dir . '/demo-keys.php';
if (!is_file($keyFile)) {
    file_put_contents($keyFile, '<?php return ' . var_export([
        'pepper' => bin2hex(random_bytes(32)),
        'token' => bin2hex(random_bytes(32)),
    ], true) . ';');
}
$keys = require $keyFile;
$engine = new Gn_Alex_Engine(
    $storage,
    $keys['pepper'],
    $keys['token'],
    new Gn_Alex_Otp_Provider_Unconfigured(),
    false,
    false
);
$engine->ensureAlexCampaign();
$http = new Gn_Alex_Http($engine);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = [];
if (!empty($_SERVER['HTTP_ACCEPT'])) {
    $headers['accept'] = $_SERVER['HTTP_ACCEPT'];
}
if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
}
if (!empty($_SERVER['HTTP_X_GN_CSRF'])) {
    $headers['x-gn-csrf'] = $_SERVER['HTTP_X_GN_CSRF'];
}
$raw = file_get_contents('php://input');
$decoded = is_string($raw) ? json_decode($raw, true) : null;
$body = is_array($decoded) ? $decoded : ($_POST ?: []);
$res = $http->handle($method, $uri, $_GET, $body, $headers, [], is_string($raw) ? $raw : '');
http_response_code($res['status']);
foreach ($res['headers'] as $name => $value) {
    header($name . ': ' . $value);
}
echo $res['body'];
return true;
