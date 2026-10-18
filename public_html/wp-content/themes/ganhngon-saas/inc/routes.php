<?php
/**
 * Old paths on ganhngon.com.
 * Each gone URL 301s once to a final path that already returns 200.
 * A language prefix is only used to look the path up; the Location is unprefixed.
 * /p/{code} stays on the parent theme.
 */
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/redirect-map.php';

function gns_redirect_home($path) {
    $path = '/' . trim((string) $path, '/');
    if ($path !== '/') $path = trailingslashit($path);
    return home_url($path);
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
        wp_safe_redirect(gns_redirect_home($exact[$path]), 301);
        exit;
    }

    $bare = preg_replace('#^(en|zh|ko|ru|ar)/#', '', $path);
    $removed = gns_removed_url_map();
    if (isset($removed[$bare]) && !gns_removed_path_is_live($bare)) {
        wp_safe_redirect(gns_redirect_home($removed[$bare]), 301);
        exit;
    }

    $internal = [
        've-combo' => '/thuc-don/',
        'privacy-policy' => '/chinh-sach-bao-mat/',
        'cam-nang/dac-san-phu-quoc' => '/cam-nang/cam-nang-dac-san-phu-quoc/',
        'cam-nang/dac-san-viet-nam' => '/cam-nang/cam-nang-dac-san-viet-nam/',
        'thuc-don/dac-san-phu-quoc' => '/thuc-don/',
        'thuc-don/dac-san-viet-nam' => '/thuc-don/',
        'thuc-don/mon-cho-khach-quoc-te' => '/thuc-don/',
        'du-lich-phu-quoc/foreign-travelers' => '/du-lich-phu-quoc/',
    ];
    if (isset($internal[$bare])) {
        wp_safe_redirect(gns_redirect_home($internal[$bare]), 301);
        exit;
    }
}, 0);
