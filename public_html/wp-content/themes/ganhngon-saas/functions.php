<?php
/**
 * Child theme: SaaS presentation on top of ganhngon-restaurant.
 * Parent functions (booking, Polylang helpers, SEO hooks, i18n) stay loaded.
 */
if (!defined('ABSPATH')) exit;

require_once get_stylesheet_directory() . '/inc/menu-data.php';

add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('gn-fonts');
    wp_dequeue_style('gn-style');
    wp_dequeue_script('gn-main');

    wp_enqueue_style(
        'gns-fonts',
        'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap&subset=vietnamese',
        [],
        null
    );
    wp_enqueue_style('gns-ui', get_stylesheet_directory_uri() . '/assets/styles.css', ['gns-fonts'], '1.0.7');
    wp_enqueue_style('gns-wp', get_stylesheet_directory_uri() . '/assets/wp.css', ['gns-ui'], '1.0.2');
    wp_enqueue_script('gns-ui', get_stylesheet_directory_uri() . '/assets/script.js', [], '1.0.7', true);
    wp_enqueue_script('gns-book', get_stylesheet_directory_uri() . '/assets/booking.js', [], '1.0.0', true);
}, 30);

add_action('wp_head', function () {
    echo '<link rel="icon" href="' . esc_url(get_stylesheet_directory_uri() . '/assets/favicon.svg') . '" type="image/svg+xml">' . "\n";
    echo '<meta name="theme-color" content="#16110d">' . "\n";
}, 2);

function gns_photo($rel) {
    $rel = ltrim((string) $rel, '/');
    if ($rel === '') return '';
    return content_url('uploads/' . $rel);
}

function gns_base_slug() {
    if (function_exists('is_front_page') && is_front_page()) return '';
    if (!function_exists('is_singular') || !is_singular()) return '';
    $id = (int) get_queried_object_id();
    if (!$id) return '';
    $slug = (string) get_post_field('post_name', $id);
    if (function_exists('pll_get_post_language') && function_exists('pll_get_post')) {
        $lang = pll_get_post_language($id, 'slug');
        if ($lang && $lang !== 'vi') {
            $vi = pll_get_post($id, 'vi');
            if ($vi) $slug = (string) get_post_field('post_name', $vi);
        }
    }
    return $slug;
}

function gns_url($slug = '') {
    if ($slug === '' || $slug === 'home') {
        return function_exists('gn_home') ? gn_home('/') : home_url('/');
    }
    if (function_exists('gn_page_url')) return gn_page_url($slug);
    return home_url('/' . trim($slug, '/') . '/');
}

function gns_phone_href() {
    $raw = function_exists('gn_phone_raw') ? gn_phone_raw() : '+84979232233';
    return 'tel:' . preg_replace('/\s+/', '', $raw);
}

function gns_phone_label() {
    return function_exists('gn_phone_display') ? gn_phone_display() : '0979 232 233';
}

function gns_zalo() {
    $id = function_exists('gn_zalo_raw') ? gn_zalo_raw() : '0979232233';
    return 'https://zalo.me/' . rawurlencode($id);
}

function gns_wa() {
    $id = function_exists('gn_whatsapp_raw') ? gn_whatsapp_raw() : '84979232233';
    return 'https://wa.me/' . rawurlencode($id);
}

function gns_maps() {
    if (function_exists('gn_maps_url')) return gn_maps_url();
    return 'https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc';
}

function gns_addr() {
    return function_exists('gn_address') ? gn_address() : '113 Trần Hưng Đạo, Dương Đông, Phú Quốc';
}

function gns_hours() {
    return function_exists('gn_t') ? gn_t('hours_clock') : 'Mở cửa hằng ngày 6:30–2:00';
}

function gns_t($key, $fallback) {
    if (function_exists('gn_cur_lang') && gn_cur_lang() !== 'vi' && function_exists('gn_t')) {
        $value = gn_t($key);
        if ($value !== $key) return $value;
    }
    return $fallback;
}

function gns_nav_items() {
    return [
        '' => gns_t('nav_home', 'Trang chủ'),
        'thuc-don' => gns_t('nav_menu', 'Thực đơn'),
        'khach-doan-tiec-rieng' => gns_t('nav_groups', 'Tiệc & Sự kiện'),
        'tin-tuc' => 'Tin tức',
        've-ganh-ngon' => gns_t('nav_about', 'Về Gánh Ngon'),
        'lien-he' => gns_t('nav_contact', 'Liên hệ'),
    ];
}

function gns_nav_current($slug) {
    if ($slug === '') return is_front_page() || (function_exists('is_home') && is_home() && !is_paged());
    $base = gns_base_slug();
    if ($base === $slug) return true;
    if ($slug === 'thuc-don' && $base === 'do-uong') return true;
    if ($slug === 'khach-doan-tiec-rieng' && is_page()) {
        $post = get_queried_object();
        if ($post && !empty($post->post_parent)) {
            $parent = (string) get_post_field('post_name', $post->post_parent);
            if ($parent === 'khach-doan-tiec-rieng') return true;
        }
    }
    return false;
}

add_filter('body_class', function ($classes) {
    $slug = gns_base_slug();
    $classes[] = 'gns';
    if ($slug === 'dat-ban') $classes[] = 'page-booking';
    if ($slug === 'thuc-don' || $slug === 'do-uong') $classes[] = 'page-menu';
    if (function_exists('gn_cur_lang') && gn_cur_lang() === 'ar') $classes[] = 'rtl';
    return $classes;
});

/**
 * Translated language roots are blog homes in Polylang. Render this theme's
 * front page before the parent theme prints its own sections.
 */
add_action('template_redirect', function () {
    if (is_admin()) return;
    if (!function_exists('gn_cur_lang') || gn_cur_lang() === 'vi') return;
    if (!is_home() && !is_front_page()) return;
    if (is_paged() || is_archive() || is_search() || is_singular()) return;
    status_header(200);
    include get_stylesheet_directory() . '/front-page.php';
    exit;
}, 0);

add_action('after_switch_theme', function () {
    if (get_page_by_path('tin-tuc')) return;
    $id = wp_insert_post([
        'post_title'   => 'Tin tức',
        'post_name'    => 'tin-tuc',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ]);
    if ($id && !is_wp_error($id) && function_exists('pll_set_post_language')) {
        pll_set_post_language($id, 'vi');
    }
});
