<?php
/**
 * Customizer panel “Gánh Ngon”.
 * A value changes the front end only after it is saved (or while the preview is dirty).
 * Unset mods keep the copy already in the templates.
 */
if (!defined('ABSPATH')) exit;

function gns_saved_text($key) {
    if (!is_customize_preview()) {
        $mods = get_theme_mods();
        if (!is_array($mods) || !array_key_exists($key, $mods)) return '';
        $value = $mods[$key];
        return is_string($value) ? $value : '';
    }
    $value = get_theme_mod($key, '');
    return is_string($value) ? $value : '';
}

function gns_saved_int($key) {
    if (!is_customize_preview()) {
        $mods = get_theme_mods();
        if (!is_array($mods) || !array_key_exists($key, $mods)) return 0;
        return absint($mods[$key]);
    }
    return absint(get_theme_mod($key, 0));
}

function gns_flag_on($key) {
    if (!is_customize_preview()) {
        $mods = get_theme_mods();
        if (!is_array($mods) || !array_key_exists($key, $mods)) return true;
        return (string) $mods[$key] !== '0';
    }
    return (string) get_theme_mod($key, '1') !== '0';
}

function gns_sanitize_checkbox($value) {
    return empty($value) ? '0' : '1';
}

function gns_kses_line($value) {
    return wp_kses((string) $value, [
        'br' => [],
        'em' => [],
        'strong' => [],
        'a' => ['href' => true, 'rel' => true, 'target' => true],
    ]);
}

function gns_footer_about() {
    $default = 'Nhà hàng Việt giữa trung tâm Dương Đông: hải sản và bò Úc nướng than hồng, lẩu gà sầu riêng, cơm niêu trưa và quầy bar ngoài trời mở đến 2 giờ sáng.';
    $value = gns_saved_text('gns_footer_about');
    return $value !== '' ? $value : $default;
}

function gns_footer_legal_line() {
    $default = 'Giá món, combo và phòng VIP được báo trực tiếp khi đặt bàn.';
    $value = gns_saved_text('gns_footer_legal');
    return $value !== '' ? $value : $default;
}

function gns_public_email() {
    $value = gns_saved_text('gns_email');
    if ($value !== '' && is_email($value)) return $value;
    return 'contact@ganhngon.com';
}

function gns_social_links() {
    $map = [
        'gns_facebook' => 'Facebook',
        'gns_instagram' => 'Instagram',
        'gns_tiktok' => 'TikTok',
        'gns_youtube' => 'YouTube',
    ];
    $out = [];
    foreach ($map as $key => $label) {
        $url = esc_url(gns_saved_text($key));
        if ($url === '') continue;
        $out[] = ['label' => $label, 'url' => $url];
    }
    return $out;
}

function gns_mod_image_url($key) {
    $id = gns_saved_int($key);
    if (!$id) return '';
    $url = wp_get_attachment_image_url($id, 'large');
    return $url ? $url : '';
}

/** Attachment images chosen in the Customizer. Empty until the owner picks one. */
function gns_mod_gallery($prefix, $slots, $fallback_caption) {
    $out = [];
    for ($i = 1; $i <= $slots; $i++) {
        $id = gns_saved_int($prefix . $i);
        if (!$id) continue;
        $src = wp_get_attachment_image_url($id, 'large');
        if (!$src) continue;
        $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        $out[] = [
            'src' => $src,
            'caption' => $alt !== '' ? $alt : $fallback_caption,
            'alt' => $alt !== '' ? $alt : $fallback_caption,
            'w' => 1600,
            'h' => 900,
        ];
    }
    return $out;
}

class GNS_Nav_Walker extends Walker_Nav_Menu {
    public function start_lvl(&$output, $depth = 0, $args = null) {}
    public function end_lvl(&$output, $depth = 0, $args = null) {}
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = (array) $item->classes;
        $current = in_array('current-menu-item', $classes, true)
            || in_array('current_page_item', $classes, true)
            || in_array('current-menu-ancestor', $classes, true);
        $output .= '<a href="' . esc_url($item->url) . '"' . ($current ? ' aria-current="page"' : '') . '>' . esc_html($item->title) . '</a>';
    }
    public function end_el(&$output, $item, $depth = 0, $args = null) {}
}

add_action('after_setup_theme', function () {
    register_nav_menus([
        'primary' => 'Menu chính',
        'footer' => 'Menu chân trang',
    ]);
}, 11);

function gns_nav_html($location) {
    if (!has_nav_menu($location)) return '';
    return wp_nav_menu([
        'theme_location' => $location,
        'container' => false,
        'items_wrap' => '%3$s',
        'depth' => 1,
        'echo' => false,
        'fallback_cb' => false,
        'walker' => new GNS_Nav_Walker(),
    ]);
}

/**
 * Page editor content, under the baked layout, when the page actually has text.
 * Skips slugs whose content the parent theme replaces with a form.
 */
function gns_editor_band() {
    if (!function_exists('is_singular') || !is_singular('page')) return;
    $post = get_queried_object();
    if (!$post || trim(wp_strip_all_tags((string) $post->post_content)) === '') return;
    $slug = function_exists('gns_base_slug') ? gns_base_slug() : '';
    $skip = ['dat-ban', 'voucher', 'cong-doi-tac', 'ai-tu-van', 'advisor'];
    if (in_array($slug, $skip, true)) return;
    echo '<section class="band band-ivory gns-editor"><div class="wrap gns-editor-body">';
    echo apply_filters('the_content', $post->post_content);
    echo '</div></section>';
}

function gns_swap_img($html, $class, $url) {
    if ($url === '') return $html;
    $pattern = '/<img\b[^>]*\bclass="' . preg_quote($class, '/') . '"[^>]*>/';
    $swapped = preg_replace_callback($pattern, function ($m) use ($url) {
        return preg_replace('/\ssrc="[^"]+"/', ' src="' . esc_url($url) . '"', $m[0], 1);
    }, $html, 1);
    return is_string($swapped) ? $swapped : $html;
}

/**
 * Charcoal-and-gold tile used wherever a photo is not a real restaurant picture.
 * $class keeps the layout class the old <img> had (hero-img, booking-bg, …).
 */
function gns_slot_markup($class, $label, $icon = 'i-flame') {
    $classes = trim('gns-slot ' . (string) $class);
    $icon = preg_replace('/[^a-z0-9-]/', '', (string) $icon);
    if ($icon === '') $icon = 'i-flame';
    $aria = $label !== '' ? ' role="img" aria-label="' . esc_attr($label) . '"' : ' aria-hidden="true"';
    $mark = function_exists('gns_mark_html') ? gns_mark_html($icon) : '';
    return '<div class="' . esc_attr($classes) . '"' . $aria . '>' . $mark . '</div>';
}

function gns_slot_or_img($setting, $class, $alt, $icon = 'i-flame') {
    $url = gns_mod_image_url($setting);
    if ($url !== '') {
        return '<img class="' . esc_attr($class) . '" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy" decoding="async">';
    }
    return gns_slot_markup($class, $alt, $icon);
}

/**
 * Filenames that are not photographs of this restaurant.
 * September 2026 root webps are AI illustrations from the previous site.
 * su-kien-*.webp are Unsplash stock, not the dining room.
 * One Customizer control per file, shared by every place that file was used.
 * Empty control = the patterned tile. photos/* is never in this list.
 */
function gns_stock_image_slots() {
    $flame = 'i-flame';
    $spark = 'i-spark';
    $leaf = 'i-leaf';
    $rows = [
        ['khong-gian-quan.webp', 'Sảnh nhà hàng', $flame, 'Ảnh minh họa sảnh (đèn lồng) từ site tháng 9/2026, không phải ảnh chụp quán.'],
        ['bo-nuong-tang.webp', 'Bò nướng tảng', $flame, 'Ảnh minh họa thịt bò từ site tháng 9/2026, không phải ảnh món.'],
        ['banh-xeo-nam-bo.webp', 'Bánh xèo', $flame, 'Ảnh minh họa bánh xèo từ site tháng 9/2026.'],
        ['tom-sot-hoang-kim.webp', 'Tôm sốt', $flame, 'Ảnh minh họa tôm từ site tháng 9/2026.'],
        ['ruou-sim-phu-quoc.webp', 'Quầy bar / rượu sim', $spark, 'Ảnh minh họa chai rượu từ site tháng 9/2026, dùng như ảnh quầy bar.'],
        ['pho-bo-ha-noi.webp', 'Phở bò', $flame, 'Ảnh minh họa phở từ site tháng 9/2026.'],
        ['cha-ca-la-vong.webp', 'Chả cá', $flame, 'Ảnh minh họa chả cá từ site tháng 9/2026.'],
        ['bun-bo-hue.webp', 'Bún bò Huế', $flame, 'Ảnh minh họa bún bò từ site tháng 9/2026.'],
        ['com-nieu-dap.webp', 'Cơm niêu đập', $flame, 'Ảnh minh họa tháng 9/2026. Không thay bằng ảnh cơm niêu thật (photos/com-nieu.webp) vì đó là món khác.'],
        ['nem-lui-hue.webp', 'Nem lụi', $flame, 'Ảnh minh họa nem lụi từ site tháng 9/2026.'],
        ['trai-cay-nhiet-doi.webp', 'Trái cây', $leaf, 'Ảnh minh họa đĩa trái cây từ site tháng 9/2026.'],
        ['su-kien-ban-tiec.webp', 'Bàn tiệc / phòng VIP', $flame, 'Ảnh Unsplash (photo-1759335514530), không phải phòng VIP của nhà hàng.'],
        ['su-kien-ban-nhac.webp', 'Ban nhạc', $spark, 'Ảnh Unsplash (photo-1632054553195), không phải ban nhạc tại quán.'],
        ['su-kien-dj.webp', 'DJ', $spark, 'Ảnh Unsplash (photo-1470225620780), không phải DJ tại quán.'],
        ['su-kien-sinh-nhat.webp', 'Tiệc sinh nhật', $spark, 'Ảnh Unsplash (photo-1674076442296), không phải tiệc tại quán.'],
        ['su-kien-tiec-dai.webp', 'Bàn tiệc dài', $flame, 'Ảnh Unsplash (photo-1767070805821), không phải bàn VIP của nhà hàng.'],
        ['su-kien-san-khau.webp', 'Sân khấu', $spark, 'Ảnh Unsplash (photo-1553819282), không phải sân khấu tại quán.'],
    ];
    $out = [];
    foreach ($rows as $row) {
        $id = 'gns_img_' . str_replace('-', '_', preg_replace('/\.[a-z0-9]+$/', '', $row[0]));
        $out[$row[0]] = [
            'id' => $id,
            'label' => $row[1],
            'icon' => $row[2],
            'why' => $row[3],
        ];
    }
    return $out;
}

function gns_hide_stock_images($html) {
    $html = str_replace(
        '{{ASSETS}}/img/lau-ga-sau-rieng.webp',
        '{{ASSETS}}/img/photos/lau-ga-sau-rieng.webp',
        $html
    );
    $slots = gns_stock_image_slots();
    $replaced = preg_replace_callback('/<img\b[^>]*>/i', function ($m) use ($slots) {
        $tag = $m[0];
        if (!preg_match('/\ssrc="([^"]+)"/', $tag, $src)) return $tag;
        $url = $src[1];
        if (strpos($url, '/img/photos/') !== false) return $tag;
        if (!preg_match('#/img/([a-z0-9-]+\.(?:webp|jpe?g|png))#i', $url, $file)) return $tag;
        $name = strtolower($file[1]);
        if (!isset($slots[$name])) return $tag;
        $slot = $slots[$name];
        $custom = gns_mod_image_url($slot['id']);
        if ($custom !== '') {
            $swapped = preg_replace('/\ssrc="[^"]+"/', ' src="' . esc_url($custom) . '"', $tag, 1);
            return is_string($swapped) ? $swapped : $tag;
        }
        $class = '';
        if (preg_match('/\sclass="([^"]*)"/', $tag, $c)) $class = $c[1];
        $label = '';
        if (preg_match('/\salt="([^"]*)"/', $tag, $a)) $label = $a[1];
        return gns_slot_markup($class, $label, $slot['icon']);
    }, $html);
    return is_string($replaced) ? $replaced : $html;
}

function gns_swap_once($html, $needle, $value, $html_ok = false) {
    if ($value === '' || $value === $needle || strpos($html, $needle) === false) return $html;
    $safe = $html_ok ? gns_kses_line($value) : esc_html($value);
    $pos = strpos($html, $needle);
    return substr_replace($html, $safe, $pos, strlen($needle));
}

function gns_apply_customizer($html, $page) {
    $sections = [
        'hero' => 'gns_show_hero',
        'kitchens' => 'gns_show_kitchens',
        'signature' => 'gns_show_signature',
        'spaces' => 'gns_show_spaces',
        'entertainment' => 'gns_show_entertainment',
        'who' => 'gns_show_who',
        'gallery' => 'gns_show_gallery',
        'quotes' => 'gns_show_quotes',
        'visit' => 'gns_show_visit',
        'news' => 'gns_show_news',
        'faq' => 'gns_show_faq',
    ];
    if ($page === 'home') {
        foreach ($sections as $id => $mod) {
            if (gns_flag_on($mod)) continue;
            $stripped = preg_replace('/<!--gns:' . preg_quote($id, '/') . '-->.*?<!--\/gns:' . preg_quote($id, '/') . '-->/s', '', $html);
            if (is_string($stripped)) $html = $stripped;
        }
    }

    $with_intros = preg_replace_callback('/\{\{P:([a-z0-9_]+)\}\}/', function ($m) {
        $text = gns_saved_text('gns_' . $m[1]);
        if ($text === '') return '';
        return '<p class="section-lead">' . esc_html($text) . '</p>';
    }, $html);
    if (is_string($with_intros)) $html = $with_intros;

    if ($page === 'home') {
        $html = gns_swap_img($html, 'hero-img', gns_mod_image_url('gns_hero_image'));
        $html = gns_swap_img($html, 'gns-signature-img', gns_mod_image_url('gns_signature_image'));
        $cta = esc_url(gns_saved_text('gns_hero_cta_url'));
        if ($cta !== '') {
            $html = preg_replace(
                '/(<a class="btn btn-ember" href=")(?:\{\{U:dat-ban\}\}|[^"]+)(">)/',
                '$1' . $cta . '$2',
                $html,
                1
            );
        }
        $cta2 = esc_url(gns_saved_text('gns_hero_cta2_url'));
        if ($cta2 !== '') {
            $html = preg_replace(
                '/(<a class="btn btn-ghost-light" href=")(?:\{\{U:thuc-don\}\}|[^"]+)(">Xem thực đơn)/',
                '$1' . $cta2 . '$2',
                $html,
                1
            );
        }
        $html = gns_swap_once($html, 'Nướng than hồng · Lẩu gà sầu riêng · Phú Quốc', gns_saved_text('gns_hero_kicker'));
        $html = gns_swap_once($html, 'Lửa than hồng,<br><em>vị sầu riêng</em> của đảo.', gns_saved_text('gns_hero_title'), true);
        $html = gns_swap_once($html, 'Nhà hàng Việt giữa trung tâm Dương Đông. Hải sản và bò Úc nướng tại quầy bếp mở, lẩu gà sầu riêng cho bàn đông người, cơm niêu buổi trưa và quầy bar ngoài trời đến 2 giờ sáng.', gns_saved_text('gns_hero_lead'));
        $html = gns_swap_once($html, 'Đặt bàn hôm nay', gns_saved_text('gns_hero_cta_label'));
        $html = gns_swap_once($html, 'Xem thực đơn', gns_saved_text('gns_hero_cta2_label'));
        $html = gns_swap_once($html, 'Mỗi bữa ở Gánh Ngon<br>là một kiểu ăn riêng', gns_saved_text('gns_kitchens_title'), true);
        $html = gns_swap_once($html, 'Từ than hồng đến nồi lẩu, từ cơm niêu trưa đến ly cocktail lúc nửa đêm — chọn kiểu ăn hợp với bàn của anh chị.', gns_saved_text('gns_kitchens_intro'));
        $html = gns_swap_once($html, 'Sầu riêng không nằm ở phần tráng miệng.', gns_saved_text('gns_signature_title'));
        $html = gns_swap_once($html, 'Nó nằm trong nồi nước dùng, tan vào nước gà, để lại vị béo ngậy, thơm sâu và ngọt hậu mà không gia vị nào thay được. Người sợ sầu riêng thường ngạc nhiên nhất ở điểm này.', gns_saved_text('gns_signature_text'));
        $html = gns_swap_once($html, 'Đẹp mà không xa cách', gns_saved_text('gns_spaces_title'));
        $html = gns_swap_once($html, 'Bữa tiệc có nhạc,<br>có người dẫn, có kỷ niệm.', gns_saved_text('gns_ent_title'), true);
        $html = gns_swap_once($html, 'Ca nhạc live, DJ, MC song ngữ, âm thanh ánh sáng, màn hình chiếu lời chúc và chụp ảnh — nhà mình lo trọn để anh chị chỉ việc nâng ly.', gns_saved_text('gns_ent_intro'));
        $html = gns_swap_once($html, 'Một nhà hàng, nhiều kiểu bữa', gns_saved_text('gns_who_title'));
        $html = gns_swap_once($html, 'Bếp, bàn tiệc và món trên đĩa', gns_saved_text('gns_gallery_title'));
        $html = gns_swap_once($html, 'Niềm tin từ thực khách', gns_saved_text('gns_quotes_title'));
        $html = gns_swap_once($html, '113 Trần Hưng Đạo,<br>Dương Đông', gns_saved_text('gns_visit_title'), true);
        $html = gns_swap_once($html, 'Trung tâm Dương Đông — từ sân bay hay resort, mở bản đồ là tới.', gns_saved_text('gns_visit_intro'));
        $html = gns_swap_once($html, 'Chuyện bếp, chuyện tiệc', gns_saved_text('gns_news_title'));
        $html = gns_swap_once($html, 'Câu hỏi thường gặp', gns_saved_text('gns_faq_title'));
        $html = gns_swap_once($html, 'Còn câu khác? Gọi hoặc Zalo <a href="tel:+84979232233">0979 232 233</a>.', gns_saved_text('gns_faq_intro'), true);
    }

    if ($page === 'thuc-don') {
        $html = gns_swap_img($html, 'gns-menu-signature-img', gns_mod_image_url('gns_menu_signature_image'));
        $html = gns_swap_once($html, 'Nhà hàng phục vụ hơn 220 món Việt ba miền. Trang này liệt kê món đang mở trong thực đơn workspace — hải sản và bò nướng than hồng, lẩu gà sầu riêng, cơm niêu, món Âu và quầy bar. Giá không niêm yết trên web.', gns_saved_text('gns_menu_intro'));
        $html = gns_swap_once($html, 'Lẩu gà sầu riêng Phú Quốc', gns_saved_text('gns_menu_signature_title'));
        $html = gns_swap_once($html, 'Gà ta, sầu riêng chín cây đánh nhuyễn vào nước hầm xương, rau đảo theo mùa và bún tươi. Báo trước khoảng 1 tiếng để nồi nóng khi ngồi xuống. Giá báo tại nhà hàng.', gns_saved_text('gns_menu_signature_text'));
    }

    if ($page === 'lien-he') {
        $html = gns_swap_once($html, 'Mở cửa hằng ngày 6:30 – 2:00. Xác nhận nhanh qua điện thoại, Zalo hoặc WhatsApp.', gns_saved_text('gns_contact_intro'));
    }

    $html = gns_replace_saved_facts($html);
    $html = gns_hide_stock_images($html);
    return $html;
}

function gns_replace_saved_facts($html) {
    $phone = gns_saved_text('gns_hotline');
    if ($phone !== '' && $phone !== '0979 232 233') {
        $html = str_replace('0979 232 233', esc_html($phone), $html);
        $tel = preg_replace('/[^\d+]/', '', $phone);
        if ($tel !== '') $html = str_replace('tel:+84979232233', 'tel:' . esc_attr($tel), $html);
    }
    $email = gns_saved_text('gns_email');
    if ($email !== '' && is_email($email) && $email !== 'contact@ganhngon.com') {
        $html = str_replace('contact@ganhngon.com', esc_html($email), $html);
        $html = str_replace('mailto:contact@ganhngon.com', 'mailto:' . esc_attr($email), $html);
    }
    $address = gns_saved_text('gns_address');
    if ($address !== '' && $address !== '113 Trần Hưng Đạo, Dương Đông, Phú Quốc') {
        $html = str_replace('113 Trần Hưng Đạo, Dương Đông, Phú Quốc', esc_html($address), $html);
    }
    $hours = gns_saved_text('gns_hours');
    if ($hours !== '' && $hours !== 'Mở cửa hằng ngày 6:30 – 2:00') {
        $html = str_replace('Mở cửa hằng ngày 6:30 – 2:00', esc_html($hours), $html);
        $html = str_replace('Hằng ngày 6:30 – 2:00', esc_html($hours), $html);
    }
    $zalo = esc_url(gns_saved_text('gns_zalo_url'));
    $zalo_now = 'https://zalo.me/2216004914337654583';
    if ($zalo !== '' && $zalo !== $zalo_now) {
        $html = str_replace($zalo_now, $zalo, $html);
    }
    $wa = esc_url(gns_saved_text('gns_wa_url'));
    if ($wa !== '' && $wa !== 'https://wa.me/84979232233') {
        $html = str_replace('https://wa.me/84979232233', $wa, $html);
    }
    $maps = esc_url(gns_saved_text('gns_maps'));
    $maps_parent = function_exists('gn_maps_url') ? gn_maps_url() : '';
    $maps_baked = 'https://www.google.com/maps/search/?api=1&query=G%C3%A1nh+Ngon+113+Tr%E1%BA%A7n+H%C6%B0ng+%C4%90%E1%BA%A1o+D%C6%B0%C6%A1ng+%C4%90%C3%B4ng+Ph%C3%BA+Qu%E1%BB%91c';
    if ($maps !== '' && $maps !== $maps_parent && $maps !== $maps_baked) {
        $html = str_replace($maps_baked, $maps, $html);
        if ($maps_parent !== '') $html = str_replace($maps_parent, $maps, $html);
    }
    return $html;
}

function gns_customize_add_text($wp, $id, $label, $section, $default, $textarea = false, $html = false) {
    $wp->add_setting($id, [
        'default' => $default,
        'sanitize_callback' => $html ? 'gns_kses_line' : ($textarea ? 'sanitize_textarea_field' : 'sanitize_text_field'),
        'transport' => 'refresh',
    ]);
    $wp->add_control($id, [
        'label' => $label,
        'section' => $section,
        'type' => $textarea ? 'textarea' : 'text',
    ]);
}

function gns_customize_add_url($wp, $id, $label, $section, $default = '') {
    $wp->add_setting($id, [
        'default' => $default,
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'refresh',
    ]);
    $wp->add_control($id, [
        'label' => $label,
        'section' => $section,
        'type' => 'url',
    ]);
}

function gns_customize_add_check($wp, $id, $label, $section) {
    $wp->add_setting($id, [
        'default' => '1',
        'sanitize_callback' => 'gns_sanitize_checkbox',
        'transport' => 'refresh',
    ]);
    $wp->add_control($id, [
        'label' => $label,
        'section' => $section,
        'type' => 'checkbox',
    ]);
}

function gns_customize_add_media($wp, $id, $label, $section) {
    $wp->add_setting($id, [
        'default' => 0,
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ]);
    $wp->add_control(new WP_Customize_Media_Control($wp, $id, [
        'label' => $label,
        'section' => $section,
        'mime_type' => 'image',
    ]));
}

add_action('customize_register', function ($wp) {
    $wp->add_panel('gns_panel', [
        'title' => 'Gánh Ngon',
        'priority' => 30,
    ]);

    $sections = [
        'gns_info' => 'Thông tin chung',
        'gns_home' => 'Trang chủ',
        'gns_menu' => 'Thực đơn',
        'gns_contact' => 'Liên hệ',
        'gns_footer' => 'Footer',
        'gns_spaces' => 'Không gian',
        'gns_photos' => 'Ảnh chờ thay',
        'gns_leads' => 'Lead cho bot',
    ];
    $i = 10;
    foreach ($sections as $id => $title) {
        $wp->add_section($id, [
            'title' => $title,
            'panel' => 'gns_panel',
            'priority' => $i,
            'description' => $id === 'gns_photos'
                ? 'Ảnh minh họa phòng, sảnh và sự kiện đang được ẩn. Chọn ảnh thật từ Thư viện. Để trống thì ô đó hiện họa tiết than và vàng, giống thực đơn.'
                : '',
        ]);
        $i += 10;
    }

    gns_customize_add_text($wp, 'gns_hotline', 'Hotline', 'gns_info', '0979 232 233');
    gns_customize_add_url($wp, 'gns_zalo_url', 'Zalo', 'gns_info', 'https://zalo.me/2216004914337654583');
    gns_customize_add_url($wp, 'gns_wa_url', 'WhatsApp', 'gns_info', 'https://wa.me/84979232233');
    gns_customize_add_text($wp, 'gns_email', 'Email', 'gns_info', 'contact@ganhngon.com');
    gns_customize_add_text($wp, 'gns_address', 'Địa chỉ', 'gns_info', '113 Trần Hưng Đạo, Dương Đông, Phú Quốc');
    gns_customize_add_text($wp, 'gns_hours', 'Giờ mở cửa', 'gns_info', 'Mở cửa hằng ngày 6:30 – 2:00');
    gns_customize_add_url($wp, 'gns_maps', 'Google Maps', 'gns_info', function_exists('gn_maps_url') ? gn_maps_url() : '');
    gns_customize_add_url($wp, 'gns_facebook', 'Facebook', 'gns_info', '');
    gns_customize_add_url($wp, 'gns_instagram', 'Instagram', 'gns_info', '');
    gns_customize_add_url($wp, 'gns_tiktok', 'TikTok', 'gns_info', '');
    gns_customize_add_url($wp, 'gns_youtube', 'YouTube', 'gns_info', '');

    gns_customize_add_text($wp, 'gns_hero_kicker', 'Hero — dòng nhỏ', 'gns_home', 'Nướng than hồng · Lẩu gà sầu riêng · Phú Quốc');
    gns_customize_add_text($wp, 'gns_hero_title', 'Hero — tiêu đề', 'gns_home', 'Lửa than hồng,<br><em>vị sầu riêng</em> của đảo.', true, true);
    gns_customize_add_text($wp, 'gns_hero_lead', 'Hero — mô tả', 'gns_home', 'Nhà hàng Việt giữa trung tâm Dương Đông. Hải sản và bò Úc nướng tại quầy bếp mở, lẩu gà sầu riêng cho bàn đông người, cơm niêu buổi trưa và quầy bar ngoài trời đến 2 giờ sáng.', true);
    gns_customize_add_media($wp, 'gns_hero_image', 'Hero — ảnh', 'gns_home');
    gns_customize_add_text($wp, 'gns_hero_cta_label', 'Nút chính — chữ', 'gns_home', 'Đặt bàn hôm nay');
    gns_customize_add_url($wp, 'gns_hero_cta_url', 'Nút chính — link', 'gns_home', '');
    gns_customize_add_text($wp, 'gns_hero_cta2_label', 'Nút phụ — chữ', 'gns_home', 'Xem thực đơn');
    gns_customize_add_url($wp, 'gns_hero_cta2_url', 'Nút phụ — link', 'gns_home', '');
    gns_customize_add_check($wp, 'gns_show_hero', 'Hiện hero', 'gns_home');

    $blocks = [
        ['gns_kitchens_title', 'Sáu căn bếp — tiêu đề', 'Mỗi bữa ở Gánh Ngon<br>là một kiểu ăn riêng', true],
        ['gns_kitchens_intro', 'Sáu căn bếp — mô tả', 'Từ than hồng đến nồi lẩu, từ cơm niêu trưa đến ly cocktail lúc nửa đêm — chọn kiểu ăn hợp với bàn của anh chị.', false],
        ['gns_signature_title', 'Món mũi nhọn — tiêu đề', 'Sầu riêng không nằm ở phần tráng miệng.', false],
        ['gns_signature_text', 'Món mũi nhọn — đoạn', 'Nó nằm trong nồi nước dùng, tan vào nước gà, để lại vị béo ngậy, thơm sâu và ngọt hậu mà không gia vị nào thay được. Người sợ sầu riêng thường ngạc nhiên nhất ở điểm này.', false],
        ['gns_spaces_title', 'Không gian — tiêu đề', 'Đẹp mà không xa cách', false],
        ['gns_spaces_intro', 'Không gian — mô tả (để trống nếu không hiện)', '', false],
        ['gns_ent_title', 'Giải trí — tiêu đề', 'Bữa tiệc có nhạc,<br>có người dẫn, có kỷ niệm.', true],
        ['gns_ent_intro', 'Giải trí — mô tả', 'Ca nhạc live, DJ, MC song ngữ, âm thanh ánh sáng, màn hình chiếu lời chúc và chụp ảnh — nhà mình lo trọn để anh chị chỉ việc nâng ly.', false],
        ['gns_who_title', 'Khách — tiêu đề', 'Một nhà hàng, nhiều kiểu bữa', false],
        ['gns_who_intro', 'Khách — mô tả (để trống nếu không hiện)', '', false],
        ['gns_gallery_title', 'Hình ảnh — tiêu đề', 'Bếp, bàn tiệc và món trên đĩa', false],
        ['gns_gallery_intro', 'Hình ảnh — mô tả (để trống nếu không hiện)', '', false],
        ['gns_quotes_title', 'Thực khách — tiêu đề', 'Niềm tin từ thực khách', false],
        ['gns_quotes_intro', 'Thực khách — mô tả (để trống nếu không hiện)', '', false],
        ['gns_visit_title', 'Đường đến — tiêu đề', '113 Trần Hưng Đạo,<br>Dương Đông', true],
        ['gns_visit_intro', 'Đường đến — mô tả', 'Trung tâm Dương Đông — từ sân bay hay resort, mở bản đồ là tới.', false],
        ['gns_news_title', 'Tin tức — tiêu đề', 'Chuyện bếp, chuyện tiệc', false],
        ['gns_news_intro', 'Tin tức — mô tả (để trống nếu không hiện)', '', false],
        ['gns_faq_title', 'Hỏi nhanh — tiêu đề', 'Câu hỏi thường gặp', false],
        ['gns_faq_intro', 'Hỏi nhanh — mô tả', 'Còn câu khác? Gọi hoặc Zalo <a href="tel:+84979232233">0979 232 233</a>.', true],
    ];
    foreach ($blocks as $block) {
        gns_customize_add_text($wp, $block[0], $block[1], 'gns_home', $block[2], true, $block[3]);
    }
    gns_customize_add_media($wp, 'gns_signature_image', 'Món mũi nhọn — ảnh', 'gns_home');
    foreach ([
        'gns_show_kitchens' => 'Hiện mục sáu căn bếp',
        'gns_show_signature' => 'Hiện món mũi nhọn',
        'gns_show_spaces' => 'Hiện không gian',
        'gns_show_entertainment' => 'Hiện giải trí',
        'gns_show_who' => 'Hiện kiểu khách',
        'gns_show_gallery' => 'Hiện hình ảnh',
        'gns_show_quotes' => 'Hiện lời thực khách',
        'gns_show_visit' => 'Hiện đường đến',
        'gns_show_news' => 'Hiện tin tức',
        'gns_show_faq' => 'Hiện hỏi nhanh',
    ] as $id => $label) {
        gns_customize_add_check($wp, $id, $label, 'gns_home');
    }

    gns_customize_add_text($wp, 'gns_menu_intro', 'Đoạn mở đầu', 'gns_menu', 'Nhà hàng phục vụ hơn 220 món Việt ba miền. Trang này liệt kê món đang mở trong thực đơn workspace — hải sản và bò nướng than hồng, lẩu gà sầu riêng, cơm niêu, món Âu và quầy bar. Giá không niêm yết trên web.', true);
    gns_customize_add_text($wp, 'gns_menu_signature_title', 'Món mũi nhọn — tiêu đề', 'gns_menu', 'Lẩu gà sầu riêng Phú Quốc');
    gns_customize_add_text($wp, 'gns_menu_signature_text', 'Món mũi nhọn — đoạn', 'gns_menu', 'Gà ta, sầu riêng chín cây đánh nhuyễn vào nước hầm xương, rau đảo theo mùa và bún tươi. Báo trước khoảng 1 tiếng để nồi nóng khi ngồi xuống. Giá báo tại nhà hàng.', true);
    gns_customize_add_media($wp, 'gns_menu_signature_image', 'Món mũi nhọn — ảnh', 'gns_menu');

    gns_customize_add_text($wp, 'gns_contact_intro', 'Đoạn mở đầu', 'gns_contact', 'Mở cửa hằng ngày 6:30 – 2:00. Xác nhận nhanh qua điện thoại, Zalo hoặc WhatsApp.', true);
    gns_customize_add_text($wp, 'gns_contact_hint', 'Ghi chú dưới form', 'gns_contact', '', true);

    gns_customize_add_text($wp, 'gns_footer_about', 'Đoạn giới thiệu', 'gns_footer', 'Nhà hàng Việt giữa trung tâm Dương Đông: hải sản và bò Úc nướng than hồng, lẩu gà sầu riêng, cơm niêu trưa và quầy bar ngoài trời mở đến 2 giờ sáng.', true);
    gns_customize_add_text($wp, 'gns_footer_legal', 'Dòng cuối', 'gns_footer', 'Giá món, combo và phòng VIP được báo trực tiếp khi đặt bàn.', true);

    gns_customize_add_text($wp, 'gns_khu_a_title', 'Khu A — tiêu đề', 'gns_spaces', 'Khu A – sảnh chính');
    gns_customize_add_text($wp, 'gns_khu_a_text', 'Khu A — mô tả', 'gns_spaces', 'Bàn thường giữa nhà. Nhận trong ngày.', true);
    gns_customize_add_media($wp, 'gns_khu_a_1', 'Khu A — ảnh 1', 'gns_spaces');
    gns_customize_add_media($wp, 'gns_khu_a_2', 'Khu A — ảnh 2', 'gns_spaces');
    gns_customize_add_media($wp, 'gns_khu_a_3', 'Khu A — ảnh 3', 'gns_spaces');
    gns_customize_add_text($wp, 'gns_khu_b_title', 'Khu B — tiêu đề', 'gns_spaces', 'Khu B – không gian tiệc');
    gns_customize_add_text($wp, 'gns_khu_b_text', 'Khu B — mô tả', 'gns_spaces', 'Bàn gỗ dài, đèn mây, có khu sân khấu. Nhà hàng trang trí theo từng buổi tiệc: thôi nôi, sinh nhật, họp mặt. Chọn “Khu B” trong form để nhà hàng giữ chỗ và tư vấn.', true);
    gns_customize_add_media($wp, 'gns_khu_b_1', 'Khu B — ảnh 1', 'gns_spaces');
    gns_customize_add_media($wp, 'gns_khu_b_2', 'Khu B — ảnh 2', 'gns_spaces');
    gns_customize_add_media($wp, 'gns_khu_b_3', 'Khu B — ảnh 3', 'gns_spaces');

    foreach (gns_stock_image_slots() as $slot) {
        gns_customize_add_media($wp, $slot['id'], $slot['label'], 'gns_photos');
    }

    $wp->add_setting('gns_lead_email', [
        'type' => 'option',
        'default' => '',
        'sanitize_callback' => 'sanitize_email',
        'transport' => 'refresh',
    ]);
    $wp->add_control('gns_lead_email', [
        'label' => 'Email nhận lead cho bot',
        'description' => 'Để trống thì không gửi email này. Email đặt bàn của nhà hàng vẫn gửi như cũ.',
        'section' => 'gns_leads',
        'type' => 'email',
    ]);
    $wp->add_setting('gns_lead_webhook', [
        'type' => 'option',
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'refresh',
    ]);
    $wp->add_control('gns_lead_webhook', [
        'label' => 'Webhook lead',
        'description' => 'Để trống thì không gọi webhook. Gửi POST JSON, timeout 5 giây, không chặn form.',
        'section' => 'gns_leads',
        'type' => 'url',
    ]);
    $wp->add_setting('gns_lead_secret', [
        'type' => 'option',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ]);
    $wp->add_control('gns_lead_secret', [
        'label' => 'Mã bí mật webhook (X-GN-Secret)',
        'section' => 'gns_leads',
        'type' => 'text',
    ]);
});
