<?php
/**
 * Child theme: SaaS presentation on top of ganhngon-restaurant.
 * Parent functions (booking, Polylang helpers, SEO hooks, i18n) stay loaded.
 */
if (!defined('ABSPATH')) exit;

require_once get_stylesheet_directory() . '/inc/menu-data.php';
require_once get_stylesheet_directory() . '/inc/menu-photos.php';
require_once get_stylesheet_directory() . '/inc/routes.php';
require_once get_stylesheet_directory() . '/inc/render.php';
require_once get_stylesheet_directory() . '/inc/guest-fields.php';
require_once get_stylesheet_directory() . '/inc/mods.php';
require_once get_stylesheet_directory() . '/inc/services.php';
require_once get_stylesheet_directory() . '/inc/leads.php';
require_once get_stylesheet_directory() . '/inc/request-form.php';
require_once get_stylesheet_directory() . '/inc/news.php';
require_once get_stylesheet_directory() . '/inc/public-fixes.php';

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
    wp_enqueue_style('gns-ui', get_stylesheet_directory_uri() . '/assets/styles.css', ['gns-fonts'], '1.4.3');
    wp_enqueue_style('gns-wp', get_stylesheet_directory_uri() . '/assets/wp.css', ['gns-ui'], '1.4.3');
    wp_enqueue_script('gns-ui', get_stylesheet_directory_uri() . '/assets/script.js', [], '1.4.3', true);
    wp_enqueue_script('gns-book', get_stylesheet_directory_uri() . '/assets/booking.js', [], '1.4.3', true);
    wp_enqueue_script('gns-dock', get_stylesheet_directory_uri() . '/assets/contact.js', [], '1.4.3', true);
    wp_enqueue_script('gns-forms', get_stylesheet_directory_uri() . '/assets/forms.js', [], '1.4.3', true);
    wp_localize_script('gns-forms', 'GNS', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gns_lead'),
        'requestNonce' => wp_create_nonce('gns_request'),
    ]);
}, 30);

/**
 * Parent theme and mu-plugins print a second footer and a stack of chat
 * buttons. Detach those callbacks; this theme prints the same facts once.
 */
function gns_detach_parent_chrome() {
    remove_action('wp_footer', 'gn_sticky_bar', 5);
    remove_action('wp_footer', 'gn_chat_widget', 6);
    remove_action('wp_footer', 'gn_public_chat_stack', 7);

    global $wp_filter;
    if (empty($wp_filter['wp_footer']) || !isset($wp_filter['wp_footer']->callbacks)) return;
    foreach ($wp_filter['wp_footer']->callbacks as $priority => $callbacks) {
        foreach ($callbacks as $cb) {
            $fn = $cb['function'] ?? null;
            if (!$fn instanceof Closure) continue;
            try {
                $file = (new ReflectionFunction($fn))->getFileName();
            } catch (ReflectionException $e) {
                continue;
            }
            $base = $file ? basename($file) : '';
            if ($base === 'gn-footer-legal.php' || $base === 'achau-eco-pq.php') {
                remove_action('wp_footer', $fn, (int) $priority);
            }
        }
    }
}
add_action('wp', 'gns_detach_parent_chrome', 1);
add_action('wp_footer', 'gns_detach_parent_chrome', 1);

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
    $slug = trim((string) $slug, '/');
    if ($slug === '' || $slug === 'home') {
        return function_exists('gn_home') ? gn_home('/') : home_url('/');
    }
    if ($slug === 'lau-ga-sau-rieng') {
        return home_url('/lau-ga-sau-rieng-phu-quoc/');
    }
    if (function_exists('gn_page_url')) return gn_page_url($slug);
    return home_url('/' . $slug . '/');
}

function gns_phone_label() {
    $custom = function_exists('gns_saved_text') ? gns_saved_text('gns_hotline') : '';
    if ($custom !== '') return $custom;
    return function_exists('gn_phone_display') ? gn_phone_display() : '0979 232 233';
}

function gns_phone_href() {
    $label = gns_phone_label();
    if ($label === '0979 232 233') {
        $raw = function_exists('gn_phone_raw') ? gn_phone_raw() : '+84979232233';
        return 'tel:' . preg_replace('/\s+/', '', $raw);
    }
    $tel = preg_replace('/[^\d+]/', '', $label);
    return 'tel:' . $tel;
}

function gns_facebook_url() {
    $saved = function_exists('gns_saved_text') ? esc_url(trim(gns_saved_text('gns_facebook'))) : '';
    if ($saved !== '') return $saved;
    return 'https://www.facebook.com/ganhngon';
}

function gns_saved_url($key) {
    if (!function_exists('gns_saved_text')) return '';
    return esc_url(trim(gns_saved_text($key)));
}

/** Facebook is official. Every other network appears only after its Customizer URL is saved. */
function gns_public_socials() {
    $items = [
        ['key' => 'facebook', 'label' => 'Facebook', 'url' => gns_facebook_url()],
    ];
    $optional = [
        'gns_instagram' => ['instagram', 'Instagram'],
        'gns_tiktok' => ['tiktok', 'TikTok'],
        'gns_youtube' => ['youtube', 'YouTube'],
        'gns_zalo_url' => ['zalo', 'Zalo'],
        'gns_wa_url' => ['whatsapp', 'WhatsApp'],
    ];
    foreach ($optional as $key => $meta) {
        $url = gns_saved_url($key);
        if ($url === '') continue;
        $items[] = ['key' => $meta[0], 'label' => $meta[1], 'url' => $url];
    }
    return $items;
}

function gns_same_as_urls() {
    $urls = [];
    foreach (gns_public_socials() as $item) {
        if (!empty($item['url'])) $urls[] = $item['url'];
    }
    return array_values(array_unique($urls));
}

function gns_zalo() {
    return gns_saved_url('gns_zalo_url');
}

function gns_wa() {
    return gns_saved_url('gns_wa_url');
}

function gns_maps() {
    $parent = function_exists('gn_maps_url') ? gn_maps_url() : 'https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc';
    $custom = function_exists('gns_saved_text') ? esc_url(gns_saved_text('gns_maps')) : '';
    if ($custom !== '' && $custom !== $parent) return $custom;
    return $parent;
}

function gns_addr() {
    $custom = function_exists('gns_saved_text') ? gns_saved_text('gns_address') : '';
    if ($custom !== '') return $custom;
    return function_exists('gn_address') ? gn_address() : '113 Trần Hưng Đạo, Dương Đông, Phú Quốc';
}

function gns_hours() {
    $custom = function_exists('gns_saved_text') ? gns_saved_text('gns_hours') : '';
    if (function_exists('gns_refresh_hours_copy')) $custom = gns_refresh_hours_copy($custom);
    if ($custom !== '') return $custom;
    return 'Mở cửa hằng ngày 10:00 – 23:00';
}

function gns_t($key, $fallback) {
    if (function_exists('gns_ui_text')) {
        $own = gns_ui_text($key);
        if ($own !== null && $own !== '') return $own;
    }
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
        'tin-tuc' => gns_t('nav_news', 'Tin tức'),
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

add_action('after_switch_theme', 'gns_seed_pages');
