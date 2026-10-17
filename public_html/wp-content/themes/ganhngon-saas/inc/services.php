<?php
/**
 * Dịch vụ / Không gian phục vụ.
 * Defaults are the owner's confirmed facts. Space cards stay on the pattern
 * tile until an admin picks a photo in the Customizer.
 */
if (!defined('ABSPATH')) exit;

function gns_svc_text($key, $default) {
    $value = function_exists('gns_saved_text') ? gns_saved_text($key) : '';
    return $value !== '' ? $value : $default;
}

function gns_service_defs() {
    return [
        'bar' => [
            'title' => 'Quầy bar',
            'text' => 'Cocktail, đồ uống.',
            'cta' => 'Đặt bàn tại quầy',
            'page' => 'dat-ban',
            'query' => 'nhom=khach-le',
            'image' => 'real/bar-cocktail-do.webp',
            'alt' => 'Cocktail tại quầy bar Gánh Ngon',
            'w' => 1200,
            'h' => 1200,
        ],
        'bep' => [
            'title' => 'Bếp ngoài',
            'text' => 'Bếp mở ngoài trời, nấu và bán đồ ăn tại chỗ.',
            'cta' => 'Đặt món tại chỗ',
            'page' => 'dat-ban',
            'query' => 'nhom=khach-le',
            'image' => 'real/than-bo-uc-nuong-tang.webp',
            'alt' => 'Món nấu tại bếp ngoài Gánh Ngon',
            'w' => 900,
            'h' => 1200,
        ],
        'khua' => [
            'title' => 'Khu A',
            'text' => 'Không gian ăn uống, nhậu, tụ họp bạn bè, gia đình và khách đoàn. Thoáng đãng, gần gũi, sạch đẹp, chân thực.',
            'cta' => 'Giữ bàn Khu A',
            'page' => 'dat-ban',
            'query' => 'nhom=gia-dinh&khu=khu-a',
            'image' => '',
            'alt' => 'Khu A',
            'w' => 1200,
            'h' => 900,
        ],
        'khub' => [
            'title' => 'Khu B',
            'text' => 'Tổ chức sinh nhật, thôi nôi, tiệc riêng. Có màn hình LED, DJ, trang trí theo chủ đề.',
            'cta' => 'Đặt tiệc Khu B',
            'page' => 'khach-doan-tiec-rieng',
            'query' => 'nhom=dat-tiec&khu=khu-b',
            'image' => '',
            'alt' => 'Khu B',
            'w' => 1200,
            'h' => 900,
        ],
        'vip' => [
            'title' => '9 phòng VIP',
            'text' => '3 phòng tầng dưới, 6 phòng tầng trên.',
            'cta' => 'Giữ phòng VIP',
            'page' => 'dat-ban',
            'query' => 'nhom=khach-le&khu=phong-vip',
            'image' => '',
            'alt' => 'Phòng VIP',
            'w' => 1200,
            'h' => 900,
        ],
    ];
}

function gns_service_url($page, $query) {
    $base = function_exists('gns_url') ? gns_url($page) : home_url('/' . $page . '/');
    $join = strpos($base, '?') === false ? '?' : '&';
    return $base . $join . $query;
}

function gns_service_media($key, $def) {
    $custom = function_exists('gns_mod_image_url') ? gns_mod_image_url('gns_svc_' . $key . '_image') : '';
    $alt = gns_svc_text('gns_svc_' . $key . '_title', $def['title']);
    if ($def['image'] !== '') $alt = $def['alt'];
    if ($custom !== '') {
        return '<img src="' . esc_url($custom) . '" alt="' . esc_attr($alt) . '" width="1200" height="900" loading="lazy" decoding="async">';
    }
    if ($def['image'] !== '') {
        $url = get_stylesheet_directory_uri() . '/assets/img/' . $def['image'];
        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($def['alt']) . '" width="' . (int) $def['w'] . '" height="' . (int) $def['h'] . '" loading="lazy" decoding="async">';
    }
    return function_exists('gns_slot_markup') ? gns_slot_markup('svc-slot', '', 'i-flame') : '';
}

function gns_bar_strip_html() {
    $shots = [
        ['real/bar-cocktail-do.webp', 'Ly cocktail đỏ tại quầy bar Gánh Ngon', 1200, 1200],
        ['real/bar-cocktail-xanh.webp', 'Ly cocktail xanh tại quầy bar Gánh Ngon', 900, 1200],
        ['real/bar-cocktail-xanh-duong.webp', 'Ly cocktail xanh dương tại quầy bar Gánh Ngon', 900, 1200],
        ['real/bar-cocktail-cam.webp', 'Ly cocktail cam tại quầy bar Gánh Ngon', 900, 1200],
        ['real/bar-cocktail-dem.webp', 'Ly cocktail tại quầy bar Gánh Ngon', 900, 1200],
        ['real/bar-cocktail-toi.webp', 'Ly cocktail tối màu tại quầy bar Gánh Ngon', 900, 1200],
    ];
    $base = get_stylesheet_directory_uri() . '/assets/img/';
    $html = '<ul class="svc-strip" aria-label="Quầy bar">';
    foreach ($shots as $shot) {
        $html .= '<li><img src="' . esc_url($base . $shot[0]) . '" alt="' . esc_attr($shot[1]) . '" width="' . (int) $shot[2] . '" height="' . (int) $shot[3] . '" loading="lazy" decoding="async"></li>';
    }
    $html .= '</ul>';
    return $html;
}

/** Unnamed kitchen photos. Names stay blank until the owner fills a slot. */
function gns_bep_slot_defs() {
    return [
        1 => ['file' => 'real/bep-01.webp', 'w' => 1200, 'h' => 900],
        2 => ['file' => 'real/bep-02.webp', 'w' => 900, 'h' => 1200],
        3 => ['file' => 'real/bep-03.webp', 'w' => 900, 'h' => 1200],
        4 => ['file' => 'real/bep-04.webp', 'w' => 900, 'h' => 1200],
        5 => ['file' => 'real/bep-05.webp', 'w' => 900, 'h' => 1200],
    ];
}

function gns_bep_strip_html() {
    $base = get_stylesheet_directory_uri() . '/assets/img/';
    $html = '<div class="svc-bep">';
    $html .= '<figure class="svc-named"><img src="' . esc_url($base . 'real/muc-vuon-xanh.webp') . '" alt="Mực Vườn Xanh tại Gánh Ngon" width="900" height="1200" loading="lazy" decoding="async"><figcaption>Mực Vườn Xanh</figcaption></figure>';
    $html .= '<div class="svc-unnamed"><p class="svc-unnamed-label">Món tại bếp</p><ul class="svc-strip" aria-label="Món tại bếp">';
    foreach (gns_bep_slot_defs() as $i => $slot) {
        $name = function_exists('gns_saved_text') ? trim(gns_saved_text('gns_bep_slot_' . $i . '_name')) : '';
        $custom = function_exists('gns_mod_image_url') ? gns_mod_image_url('gns_bep_slot_' . $i . '_image') : '';
        $src = $custom !== '' ? $custom : $base . $slot['file'];
        $alt = $name !== '' ? $name . ' tại Gánh Ngon' : 'Món tại bếp Gánh Ngon';
        $html .= '<li><figure>';
        $html .= '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" width="' . (int) $slot['w'] . '" height="' . (int) $slot['h'] . '" loading="lazy" decoding="async">';
        if ($name !== '') $html .= '<figcaption>' . esc_html($name) . '</figcaption>';
        $html .= '</figure></li>';
    }
    $html .= '</ul></div></div>';
    return $html;
}

function gns_services_html() {
    $title = gns_svc_text('gns_services_title', 'Không gian phục vụ');
    $html = '<section class="band band-ivory gns-services" id="dich-vu" aria-labelledby="services-title">';
    $html .= '<div class="wrap">';
    $html .= '<div class="section-head"><p class="eyebrow">Dịch vụ</p><h2 id="services-title" class="title">' . esc_html($title) . '</h2></div>';
    $html .= '<div class="svc-grid">';
    $shown_bar = false;
    $shown_bep = false;
    foreach (gns_service_defs() as $key => $def) {
        if (function_exists('gns_flag_on') && !gns_flag_on('gns_svc_' . $key . '_show')) continue;
        if ($key === 'bar') $shown_bar = true;
        if ($key === 'bep') $shown_bep = true;
        $card_title = gns_svc_text('gns_svc_' . $key . '_title', $def['title']);
        $card_text = gns_svc_text('gns_svc_' . $key . '_text', $def['text']);
        $cta = gns_svc_text('gns_svc_' . $key . '_cta', $def['cta']);
        $html .= '<article class="svc-card">';
        $html .= '<div class="svc-media">' . gns_service_media($key, $def) . '</div>';
        $html .= '<div class="svc-body"><h3>' . esc_html($card_title) . '</h3><p>' . esc_html($card_text) . '</p>';
        $html .= '<a class="btn btn-ember" href="' . esc_url(gns_service_url($def['page'], $def['query'])) . '">' . esc_html($cta) . '</a>';
        $html .= '</div></article>';
    }
    $html .= '</div>';
    if ($shown_bar) $html .= gns_bar_strip_html();
    if ($shown_bep) $html .= gns_bep_strip_html();
    $html .= '</div></section>';
    return $html;
}

function gns_signature_defaults() {
    return [
        1 => [
            'name' => 'Lẩu gà sầu riêng Phú Quốc',
            'kicker' => 'Món mũi nhọn',
            'text' => 'Nồi lẩu gà sầu riêng.',
            'image' => 'real/lau-ga-sau-rieng.webp',
            'alt' => 'Nồi lẩu gà sầu riêng tại Gánh Ngon Phú Quốc',
            'w' => 1200,
            'h' => 900,
        ],
        2 => [
            'name' => 'Thăn bò Úc nướng tảng',
            'kicker' => 'Bếp ngoài',
            'text' => 'Thăn bò Úc nướng tảng.',
            'image' => 'real/than-bo-uc-nuong-tang.webp',
            'alt' => 'Thăn bò Úc nướng tảng tại Gánh Ngon',
            'w' => 900,
            'h' => 1200,
        ],
        3 => [
            'name' => 'Chả giò',
            'kicker' => 'Bếp ngoài',
            'text' => 'Chả giò.',
            'image' => 'real/cha-gio.webp',
            'alt' => 'Chả giò tại Gánh Ngon',
            'w' => 900,
            'h' => 1200,
        ],
    ];
}

function gns_signature_rail_html() {
    $base = get_stylesheet_directory_uri() . '/assets/img/';
    $html = '<ul class="sig-rail" aria-label="Món nhà hàng đã đăng ảnh">';
    foreach (gns_signature_defaults() as $i => $def) {
        $name = gns_svc_text('gns_sig_' . $i . '_name', $def['name']);
        $custom = function_exists('gns_mod_image_url') ? gns_mod_image_url('gns_sig_' . $i . '_image') : '';
        $src = $custom !== '' ? $custom : $base . $def['image'];
        $alt = $custom !== '' ? $name . ' tại Gánh Ngon' : $def['alt'];
        $html .= '<li class="sig-card">';
        $html .= '<img src="' . esc_url($src) . '" alt="' . esc_attr($alt) . '" width="' . (int) $def['w'] . '" height="' . (int) $def['h'] . '" loading="lazy" decoding="async">';
        $html .= '<div><p class="sig-kicker">' . esc_html($def['kicker']) . '</p><h3>' . esc_html($name) . '</h3><p>' . esc_html($def['text']) . '</p></div>';
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

add_action('customize_register', function ($wp) {
    $wp->add_section('gns_services', [
        'title' => 'Dịch vụ & Không gian',
        'panel' => 'gns_panel',
        'priority' => 35,
        'description' => 'Thẻ Quầy bar, Bếp ngoài, Khu A, Khu B và 9 phòng VIP. Ảnh để trống: bar và bếp giữ ảnh nhà hàng; Khu A, Khu B và phòng VIP hiện họa tiết.',
    ]);
    gns_customize_add_text($wp, 'gns_services_title', 'Tiêu đề mục', 'gns_services', 'Không gian phục vụ');
    $labels = [
        'bar' => 'Quầy bar',
        'bep' => 'Bếp ngoài',
        'khua' => 'Khu A',
        'khub' => 'Khu B',
        'vip' => '9 phòng VIP',
    ];
    foreach (gns_service_defs() as $key => $def) {
        $name = $labels[$key];
        gns_customize_add_check($wp, 'gns_svc_' . $key . '_show', $name . ' — hiện thẻ', 'gns_services');
        gns_customize_add_text($wp, 'gns_svc_' . $key . '_title', $name . ' — tiêu đề', 'gns_services', $def['title']);
        gns_customize_add_text($wp, 'gns_svc_' . $key . '_text', $name . ' — đoạn ngắn', 'gns_services', $def['text'], true);
        gns_customize_add_media($wp, 'gns_svc_' . $key . '_image', $name . ' — ảnh', 'gns_services');
        gns_customize_add_text($wp, 'gns_svc_' . $key . '_cta', $name . ' — chữ nút', 'gns_services', $def['cta']);
    }
    foreach (gns_bep_slot_defs() as $i => $slot) {
        gns_customize_add_text($wp, 'gns_bep_slot_' . $i . '_name', 'Món tại bếp ' . $i . ' — tên món', 'gns_services', '');
        gns_customize_add_media($wp, 'gns_bep_slot_' . $i . '_image', 'Món tại bếp ' . $i . ' — ảnh', 'gns_services');
    }
    foreach (gns_signature_defaults() as $i => $def) {
        gns_customize_add_text($wp, 'gns_sig_' . $i . '_name', 'Món mũi nhọn ' . $i . ' — tên', 'gns_home', $def['name']);
        gns_customize_add_media($wp, 'gns_sig_' . $i . '_image', 'Món mũi nhọn ' . $i . ' — ảnh', 'gns_home');
    }
});

function gns_editor_section_for_slug($slug) {
    $slug = preg_replace('/-(en|zh|ko|ru|ar)$/', '', (string) $slug);
    $map = [
        've-ganh-ngon' => ['gns_services', 'Dịch vụ & Không gian'],
        'thuc-don' => ['gns_menu', 'Thực đơn'],
        'lau-ga-sau-rieng' => ['gns_menu', 'Thực đơn'],
        'lien-he' => ['gns_contact', 'Liên hệ'],
        'dat-ban' => ['gns_contact', 'Liên hệ'],
        'khach-doan-tiec-rieng' => ['gns_services', 'Dịch vụ & Không gian'],
    ];
    if (isset($map[$slug])) return $map[$slug];
    return ['gns_home', 'Trang chủ'];
}

add_action('admin_notices', function () {
    if (!function_exists('get_current_screen')) return;
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') return;
    $post = get_post();
    if (!$post || !current_user_can('edit_post', $post->ID)) return;
    $slug = (string) $post->post_name;
    $front = (int) get_option('page_on_front');
    $is_front = $front && (int) $post->ID === $front;
    $baked = $is_front || $slug === '';
    if (!$baked && function_exists('gns_main_path')) {
        $check = preg_replace('/-(en|zh|ko|ru|ar)$/', '', $slug);
        $baked = gns_main_path($check) !== '' || gns_main_path($slug) !== '';
    }
    if (!$baked) return;
    $target = $is_front ? ['gns_home', 'Trang chủ'] : gns_editor_section_for_slug($slug);
    $url = add_query_arg(['autofocus[section]' => $target[0]], admin_url('customize.php'));
    $extra = '';
    if ($is_front || $slug === 've-ganh-ngon') {
        $svc = add_query_arg(['autofocus[section]' => 'gns_services'], admin_url('customize.php'));
        if ($target[0] !== 'gns_services') {
            $extra = ' Thẻ dịch vụ: <a href="' . esc_url($svc) . '">Dịch vụ &amp; Không gian</a>.';
        }
    }
    echo '<div class="notice notice-info"><p>Nội dung trang này hiển thị từ giao diện — sửa tại Giao diện → Tùy biến → Gánh Ngon → <a href="' . esc_url($url) . '">' . esc_html($target[1]) . '</a>.' . $extra . '</p></div>';
});
