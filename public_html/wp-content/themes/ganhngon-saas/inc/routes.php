<?php
/**
 * Old WordPress paths from the SaaS redirects.json.
 * /p/{code} stays on the parent theme: it records a partner scan and sends the guest to booking.
 */
if (!defined('ABSPATH')) exit;

add_action('template_redirect', function () {
    if (is_admin()) return;
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');

    $exact = [
        'home-en' => '/en/',
        'home-zh' => '/zh/',
        'home-ko' => '/ko/',
        'home-ru' => '/ru/',
        'home-ar' => '/ar/',
        'tra-cuu-en' => '/tra-cuu/',
        'xac-minh-en' => '/xac-minh/',
        'en/tra-cuu-en' => '/tra-cuu/',
        'en/xac-minh-en' => '/xac-minh/',
    ];
    if (isset($exact[$path])) {
        wp_safe_redirect(home_url($exact[$path]), 301);
        exit;
    }

    $prefixes = [
        'category/an-uong-phu-quoc' => '/tin-tuc/',
        'category/diem-tham-quan-phu-quoc' => '/du-lich-phu-quoc/',
        'category/luu-tru-phu-quoc' => '/du-lich-phu-quoc/',
        'category/di-chuyen-phu-quoc' => '/du-lich-phu-quoc/',
        'category/kinh-nghiem-du-lich' => '/cam-nang/',
    ];
    foreach ($prefixes as $from => $to) {
        if ($path === $from || strpos($path, $from . '/') === 0) {
            wp_safe_redirect(home_url($to), 301);
            exit;
        }
    }
}, 0);
