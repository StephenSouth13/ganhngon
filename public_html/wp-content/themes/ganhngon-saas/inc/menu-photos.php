<?php
/**
 * Menu photos.
 *
 * A card shows a photo only when that photo is the dish, or a platter that
 * actually depicts the group (the skewer platter for xiên). A photo of one
 * dish is never reused as the picture of a different dish or of a whole group.
 * Everything else gets a branded pattern tile — not a photograph, and not an
 * empty box. September illustrations and Unsplash are never used here.
 *
 * Adding a photo later: upload it in wp-admin → Media. The file name (or the
 * attachment slug, or the title) must be the dish slug. Example: the dish
 * "Tôm nướng" has slug tom-nuong, so tom-nuong.jpg attaches itself on the
 * next page view and replaces the tile. No theme edit.
 * Slug = lowercase, no accents, spaces become hyphens.
 */
if (!defined('ABSPATH')) exit;

function gns_fold($value) {
    $value = mb_strtolower((string) $value, 'UTF-8');
    $value = strtr($value, [
        'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'đ' => 'd',
    ]);
    return $value;
}

function gns_slug($value) {
    $value = gns_fold($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string) $value, '-');
}

function gns_menu_snapshot() {
    static $data;
    if ($data !== null) return $data;
    $file = get_stylesheet_directory() . '/inc/menu-snapshot.json';
    $json = is_readable($file) ? file_get_contents($file) : false;
    $data = $json ? json_decode($json, true) : [];
    if (!is_array($data)) $data = [];
    return $data;
}

/** Photos of a named dish. October 2026 restaurant files only. Never a stand-in. */
function gns_dish_photo_rules() {
    return [
        ['test' => 'lau ga sau rieng', 'image' => 'photos/lau-ga-sau-rieng.webp', 'alt' => 'Nồi lẩu gà sầu riêng tại Gánh Ngon Phú Quốc'],
        ['test' => 'sau rieng chien', 'image' => 'photos/sau-rieng-chien.webp', 'alt' => 'Sầu riêng chiên giòn tại Gánh Ngon Phú Quốc'],
        ['test' => 'bap nuong mo hanh|bap mo hanh', 'image' => 'photos/bap-mo-hanh.webp', 'alt' => 'Bắp nướng mỡ hành tại Gánh Ngon Phú Quốc'],
        ['test' => 'com nieu dap', 'image' => 'photos/com-nieu-tam.webp', 'alt' => 'Cơm niêu tại Gánh Ngon Phú Quốc'],
        ['test' => 'xien heo|heo xien|ba chi heo', 'image' => 'photos/heo-xien-rau-cu.webp', 'alt' => 'Heo xiên nướng rau củ tại Gánh Ngon'],
        ['test' => 'margarita', 'image' => 'photos/cocktail-margarita-doi.webp', 'alt' => 'Margarita tại quầy bar Gánh Ngon'],
    ];
}

/**
 * Group photos that depict the whole group, not one other dish.
 * The skewer platter is the xiên group. Cơm niêu Wikimedia file is not used.
 */
function gns_menu_groups() {
    return [
        'xien-nuong-bbq' => ['photos/mam-xien-nuong.webp', 'Mâm xiên nướng tại Gánh Ngon'],
    ];
}

/** A skewer dish may use the skewer platter. A bánh mì that contains xiên may not. */
function gns_is_skewer_dish($folded) {
    if (strpos($folded, 'banh mi') !== false) return false;
    return (bool) preg_match('/(^| )xien( |$)/', $folded);
}

function gns_menu_drink_slugs() {
    return ['ganh-ngon-signature', 'cocktail', 'ca-phe-tra', 'sinh-to-nuoc-ep-soda', 'bia-ruou-combo'];
}

function gns_theme_photo_url($rel) {
    return get_stylesheet_directory_uri() . '/assets/img/' . ltrim($rel, '/');
}

/**
 * Media library index: dish slug => image URL.
 * Keys come from the attachment slug, the file name, and the title.
 */
function gns_media_photo_index() {
    static $index;
    if ($index !== null) return $index;
    $index = [];
    if (!function_exists('get_posts')) return $index;
    $posts = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'post_mime_type' => 'image',
        'fields' => 'ids',
    ]);
    foreach ($posts as $id) {
        $id = (int) $id;
        $url = wp_get_attachment_image_url($id, 'large');
        if (!$url) continue;
        $file = get_attached_file($id);
        $stem = $file ? pathinfo($file, PATHINFO_FILENAME) : '';
        $stem = preg_replace('/-\d+x\d+$/', '', (string) $stem);
        $stem = preg_replace('/^(ganh-ngon-|ganhngon-)/', '', $stem);
        $keys = [
            gns_slug(get_post_field('post_name', $id)),
            gns_slug($stem),
            gns_slug(get_the_title($id)),
        ];
        foreach ($keys as $key) {
            if ($key !== '' && !isset($index[$key])) $index[$key] = $url;
        }
    }
    return $index;
}

function gns_mark_icon($cat_slug) {
    if (in_array($cat_slug, gns_menu_drink_slugs(), true)) return 'i-spark';
    if (in_array($cat_slug, ['rau-cu-nuong', 'salad-goi-cuon', 'mon-dac-trung-sau-rieng', 'dac-san-ba-mien'], true)) return 'i-leaf';
    return 'i-flame';
}

function gns_mark_html($icon) {
    $icon = preg_replace('/[^a-z0-9-]/', '', (string) $icon);
    return '<span class="mark-tile" aria-hidden="true"><svg class="icon" focusable="false"><use href="#' . $icon . '"></use></svg></span>';
}

function gns_resolve_dish_photo($name, $cat_slug) {
    $slug = gns_slug($name);
    $library = gns_media_photo_index();
    if (isset($library[$slug]) && !(function_exists('gns_is_hidden_media_url') && gns_is_hidden_media_url($library[$slug]))) {
        return [
            'scope' => 'dish',
            'url' => $library[$slug],
            'alt' => $name . ' tại Gánh Ngon',
        ];
    }
    $folded = gns_fold($name);
    foreach (gns_dish_photo_rules() as $rule) {
        if (preg_match('/' . $rule['test'] . '/i', $folded)) {
            $url = gns_theme_photo_url($rule['image']);
            if (function_exists('gns_is_hidden_media_url') && gns_is_hidden_media_url($url)) continue;
            return [
                'scope' => 'dish',
                'url' => $url,
                'alt' => $rule['alt'],
            ];
        }
    }
    if (gns_is_skewer_dish($folded)) {
        return [
            'scope' => 'group',
            'url' => gns_theme_photo_url('photos/mam-xien-nuong.webp'),
            'alt' => 'Mâm xiên nướng tại Gánh Ngon',
        ];
    }
    $groups = gns_menu_groups();
    if (isset($groups[$cat_slug]) && $cat_slug !== 'com-nieu-trua') {
        $url = gns_theme_photo_url($groups[$cat_slug][0]);
        if (!(function_exists('gns_is_hidden_media_url') && gns_is_hidden_media_url($url))) {
            return [
                'scope' => 'group',
                'url' => $url,
                'alt' => $groups[$cat_slug][1],
            ];
        }
    }
    return [
        'scope' => 'mark',
        'url' => '',
        'alt' => '',
        'icon' => gns_mark_icon($cat_slug),
    ];
}

function gns_menu_cats_html() {
    $drinks = gns_menu_drink_slugs();
    $html = '<div class="cat-grid" data-cat-grid>';
    foreach (gns_menu_snapshot() as $cat) {
        $name = (string) ($cat['name'] ?? '');
        $slug = gns_slug($name);
        $kind = in_array($slug, $drinks, true) ? 'drink' : 'food';
        $count = isset($cat['dishes']) && is_array($cat['dishes']) ? count($cat['dishes']) : 0;
        $groups = gns_menu_groups();
        $desc = trim((string) ($cat['description'] ?? ''));
        $html .= '<button type="button" class="cat-card" data-cat="' . esc_attr($slug) . '" data-kind="' . esc_attr($kind) . '">';
        $group_photo = isset($groups[$slug]) ? gns_theme_photo_url($groups[$slug][0]) : '';
        if ($group_photo !== '' && !(function_exists('gns_is_hidden_media_url') && gns_is_hidden_media_url($group_photo))) {
            $html .= '<span class="cat-media"><img src="' . esc_url($group_photo) . '" alt="' . esc_attr($groups[$slug][1]) . '" width="1600" height="914" loading="lazy" decoding="async">';
        } else {
            $html .= '<span class="cat-media cat-media-mark">' . gns_mark_html(gns_mark_icon($slug));
        }
        $html .= '<span class="cat-count">' . (int) $count . ' món</span></span>';
        $kind_label = function_exists('gns_t') ? gns_t($kind === 'drink' ? 'drinks' : 'food', $kind === 'drink' ? 'Đồ uống' : 'Đồ ăn') : ($kind === 'drink' ? 'Đồ uống' : 'Đồ ăn');
        $html .= '<span class="cat-body"><strong class="cat-name">' . esc_html($name) . '</strong><span class="cat-kind">' . esc_html($kind_label) . '</span>';
        $html .= '<span class="cat-desc">' . esc_html($desc) . '</span>';
        $html .= '</span></button>';
    }
    $html .= '</div>';
    return $html;
}

function gns_menu_cards_html() {
    $drinks = gns_menu_drink_slugs();
    $html = '';
    foreach (gns_menu_snapshot() as $cat) {
        $cat_name = (string) ($cat['name'] ?? '');
        $cat_slug = gns_slug($cat_name);
        $kind = in_array($cat_slug, $drinks, true) ? 'drink' : 'food';
        foreach (($cat['dishes'] ?? []) as $dish) {
            $name = (string) ($dish['name'] ?? '');
            if ($name === '') continue;
            $photo = gns_resolve_dish_photo($name, $cat_slug);
            $desc = trim((string) ($dish['description'] ?? ''));
            $tag = $photo['scope'] === 'group'
                ? '<span class="dish-scope-tag">Ảnh nhóm</span>'
                : '';
            $html .= '<article class="dish-card has-img" data-cat="' . esc_attr($cat_slug) . '" data-kind="' . esc_attr($kind) . '" data-scope="' . esc_attr($photo['scope']) . '" data-dish="' . esc_attr(gns_slug($name)) . '">';
            if ($photo['scope'] === 'mark') {
                $html .= '<div class="dish-media dish-media-mark">' . gns_mark_html($photo['icon']) . '</div>';
            } else {
                $html .= '<div class="dish-media"><img class="dish-photo" src="' . esc_url($photo['url']) . '" alt="' . esc_attr($photo['alt']) . '" width="1400" height="875" loading="lazy" decoding="async">' . $tag . '</div>';
            }
            $html .= '<div class="dish-body"><p class="dish-group">' . esc_html($cat_name) . '</p><h4 class="dish-name">' . esc_html($name) . '</h4>';
            $html .= '<p class="dish-desc">' . esc_html($desc) . '</p>';
            $html .= '<p class="dish-foot"><span class="dish-price dish-ask">Giá báo khi đặt bàn</span></p></div></article>';
        }
    }
    return $html;
}
