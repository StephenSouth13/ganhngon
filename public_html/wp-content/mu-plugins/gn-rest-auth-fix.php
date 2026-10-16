<?php
/**
 * Plugin Name: GN REST Auth Fix
 * Description: Khoi phuc Basic auth cho REST API tren Hostinger/LiteSpeed.
 *   1) TLS ket thuc o proxy nen is_ssl() co the sai -> WordPress tu tat Application Passwords.
 *   2) PHP chay CGI/FastCGI nen header Authorization khong duoc do vao PHP_AUTH_USER / PHP_AUTH_PW.
 *   Can dung kem chi thi "CGIPassAuth On" o dau file .htaccess.
 *   KHONG bo qua xac thuc: WordPress van tu kiem tra mat khau ung dung nhu binh thuong.
 * Version: 1.2
 */
if (!defined('ABSPATH')) exit;

(static function () {
    // 1. Bao cho WordPress biet ket noi thuc su la HTTPS (proxy da ket thuc TLS).
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            ? $_SERVER['HTTP_X_FORWARDED_PROTO']
            : (isset($_SERVER['HTTP_X_FORWARDED_SSL']) ? $_SERVER['HTTP_X_FORWARDED_SSL'] : '');
        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
        if (stripos((string) $proto, 'https') !== false || $proto === 'on' || $port === '443') {
            $_SERVER['HTTPS'] = 'on';
        }
    }

    // 2. Nap lai Basic auth cho request REST.
    if (!empty($_SERVER['PHP_AUTH_USER'])) return;
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (strpos($uri, '/wp-json/') === false && strpos($uri, 'rest_route=') === false) return;

    $header = '';
    foreach (array(
        'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
    ) as $key) {
        if (!empty($_SERVER[$key])) { $header = $_SERVER[$key]; break; }
    }
    if ($header === '' && function_exists('apache_request_headers')) {
        foreach ((array) apache_request_headers() as $name => $value) {
            if (strtolower($name) === 'authorization') { $header = $value; break; }
        }
    }
    if ($header === '' || stripos($header, 'basic ') !== 0) return;

    $raw = base64_decode(substr($header, 6), true);
    if ($raw === false || strpos($raw, ':') === false) return;

    list($user, $pass) = explode(':', $raw, 2);
    $_SERVER['PHP_AUTH_USER'] = $user;
    $_SERVER['PHP_AUTH_PW']   = $pass;
})();
