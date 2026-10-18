<?php
/**
 * Old paths on ganhngon.com.
 * Each gone URL 301s once to a final path that already returns 200.
 * A language prefix is only used to look the path up. The Location is the final URL, query string kept.
 * /p/{code} stays on the parent theme.
 */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/redirect-map.php';

function gns_redirect_home($path) {
    $path = '/' . trim((string) $path, '/');
    if ($path !== '/') $path = trailingslashit($path);
    return home_url($path);
}

/** Language homepages that already return 200. Do not send these to /en/ — Polylang sends /en/ back here. */
function gns_exact_redirects() {
    return [
        'home-en' => '/en/home-en/',
        'home-zh' => '/zh/home-zh/',
        'home-ko' => '/ko/home-ko/',
        'home-ru' => '/ru/home-ru/',
        'home-ar' => '/ar/home-ar/',
    ];
}

/**
 * Always 301, even when a page with that slug is still published.
 * Targets are final paths that return 200.
 */
function gns_internal_redirects() {
    return [
        've-combo' => '/thuc-don/',
        'privacy-policy' => '/chinh-sach-bao-mat/',
        'dat-cho' => '/dat-ban/',
        'ganh-ngon' => '/ve-ganh-ngon/',
        'cam-nang/dac-san-phu-quoc' => '/cam-nang/cam-nang-dac-san-phu-quoc/',
        'cam-nang/dac-san-viet-nam' => '/cam-nang/cam-nang-dac-san-viet-nam/',
        'thuc-don/dac-san-phu-quoc' => '/thuc-don/',
        'thuc-don/dac-san-viet-nam' => '/thuc-don/',
        'thuc-don/mon-cho-khach-quoc-te' => '/thuc-don/',
        'thuc-don/mon-gia-dinh' => '/thuc-don/',
        'du-lich-phu-quoc/foreign-travelers' => '/du-lich-phu-quoc/',
        'lau-ga-sau-rieng' => '/lau-ga-sau-rieng-phu-quoc/',
    ];
}

/** Where this path 301s, or '' when the path should render. */
function gns_request_redirect_target($path) {
    $path = trim((string) $path, '/');
    $exact = gns_exact_redirects();
    if (isset($exact[$path])) return $exact[$path];
    $bare = preg_replace('#^(en|zh|ko|ru|ar)/#', '', $path);
    if (!is_string($bare)) $bare = $path;
    $removed = gns_removed_url_map();
    if (isset($removed[$bare]) && !gns_removed_path_is_live($bare)) return $removed[$bare];
    $internal = gns_internal_redirects();
    if (isset($internal[$bare])) return $internal[$bare];
    return '';
}

function gns_redirect_now($path) {
    $target = gns_redirect_home($path);
    $qs = isset($_SERVER['QUERY_STRING']) ? (string) wp_unslash($_SERVER['QUERY_STRING']) : '';
    if ($qs !== '') {
        $target .= (strpos($target, '?') === false ? '?' : '&') . $qs;
    }
    wp_safe_redirect($target, 301);
    exit;
}

/**
 * A restored post or a category that still has published posts keeps its URL.
 * Author archives never count as live.
 */
function gns_removed_path_is_live($path) {
    $path = trim((string) $path, '/');
    if ($path === '' || strpos($path, 'author/') === 0) return false;
    if (strpos($path, 'category/') === 0) {
        $slug = basename($path);
        $term = get_term_by('slug', $slug, 'category');
        return ($term && !is_wp_error($term) && (int) $term->count > 0);
    }
    $found = get_page_by_path($path, OBJECT, ['post', 'page']);
    return ($found instanceof WP_Post && $found->post_status === 'publish');
}

add_action('template_redirect', function () {
    if (is_admin()) return;
    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') return;

    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
    $author_q = isset($_GET['author']) ? (string) wp_unslash($_GET['author']) : '';
    if ($author_q !== '' || preg_match('#^(?:(?:en|zh|ko|ru|ar)/)?author(?:/|$)#', $path)) {
        wp_safe_redirect(gns_redirect_home('/tin-tuc/'), 301);
        exit;
    }

    $target = gns_request_redirect_target($path);
    if ($target !== '') {
        gns_redirect_now($target);
    }
}, 0);
