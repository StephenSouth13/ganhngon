<?php
/**
 * Menu photos.
 *
 * A card is either a photo of that dish, or a real group photo marked "Ảnh nhóm".
 * September illustrations and Unsplash event photos are never used as a dish.
 *
 * Adding a photo later: upload it in wp-admin → Media. The file name (or the
 * attachment slug, or the title) must be the dish slug. Example: the dish
 * "Tôm nướng" has slug tom-nuong, so tom-nuong.jpg attaches itself on the
 * next page view and replaces the group photo. No theme edit.
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

/** Photos of a named dish. October 2026 restaurant files only. */
function gns_dish_photo_rules() {
    return [
        ['test' => 'lau ga sau rieng', 'image' => 'photos/lau-ga-sau-rieng.webp', 'alt' => 'Nồi lẩu gà sầu riêng tại Gánh Ngon Phú Quốc', 'scope' => 'dish'],
        ['test' => 'sau rieng chien', 'image' => 'photos/sau-rieng-chien.webp', 'alt' => 'Sầu riêng chiên giòn tại Gánh Ngon Phú Quốc', 'scope' => 'dish'],
        ['test' => 'bap nuong mo hanh|bap mo hanh', 'image' => 'photos/bap-mo-hanh.webp', 'alt' => 'Bắp nướng mỡ hành tại Gánh Ngon Phú Quốc', 'scope' => 'dish'],
        ['test' => 'com nieu dap', 'image' => 'photos/com-nieu-tam.webp', 'alt' => 'Cơm niêu tại Gánh Ngon Phú Quốc', 'scope' => 'dish'],
        ['test' => 'com nieu', 'image' => 'photos/com-nieu.webp', 'alt' => 'Cơm niêu tại Gánh Ngon Phú Quốc', 'scope' => 'dish'],
        ['test' => 'xien heo|heo xien|ba chi heo', 'image' => 'photos/heo-xien-rau-cu.webp', 'alt' => 'Heo xiên nướng rau củ tại Gánh Ngon', 'scope' => 'dish'],
        ['test' => 'bo luc lac', 'image' => 'photos/bo-luc-lac-sau-rieng.webp', 'alt' => 'Bò lúc lắc sầu riêng tại Gánh Ngon Phú Quốc', 'scope' => 'dish'],
        ['test' => 'margarita', 'image' => 'photos/cocktail-margarita-doi.webp', 'alt' => 'Margarita tại quầy bar Gánh Ngon', 'scope' => 'dish'],
        ['test' => 'combo ruou|whisky|macallan', 'image' => 'photos/whisky-macallan.webp', 'alt' => 'Rượu whisky tại quầy bar Gánh Ngon', 'scope' => 'dish'],
        ['test' => 'ruou sim', 'image' => 'photos/whisky-macallan.webp', 'alt' => 'Quầy rượu tại Gánh Ngon', 'scope' => 'group'],
        ['test' => 'combo 4 cocktail', 'image' => 'photos/cocktail-xanh-quay-bar.webp', 'alt' => 'Cocktail tại quầy bar Gánh Ngon', 'scope' => 'group'],
    ];
}

/** One real photo per category, used when that dish has no photo of its own. */
function gns_menu_groups() {
    $grill = ['photos/mam-xien-nuong.webp', 'Mâm nướng tại Gánh Ngon'];
    $beef = ['photos/bo-luc-lac-sau-rieng.webp', 'Bò tại Gánh Ngon'];
    $platter = ['photos/cocktail-dem-nhiet-doi.webp', 'Mâm khai vị tại quầy Gánh Ngon'];
    $rice = ['photos/com-nieu.webp', 'Cơm niêu tại Gánh Ngon'];
    $bar = ['photos/cocktail-bar.webp', 'Quầy bar Gánh Ngon'];
    return [
        'mon-dac-trung-sau-rieng' => ['photos/lau-ga-sau-rieng.webp', 'Lẩu gà sầu riêng tại Gánh Ngon'],
        'hai-san-nuong-than-hong' => $grill,
        'bo-nuong' => $beef,
        'heo-ga-nuong' => $grill,
        'xien-nuong-bbq' => $grill,
        'rau-cu-nuong' => $grill,
        'combo-nuong' => $grill,
        'salad-goi-cuon' => $platter,
        'banh-xeo' => $rice,
        'mon-nhanh-banh-mi' => $platter,
        'mon-au' => $platter,
        'com-nieu-trua' => $rice,
        'ganh-ngon-signature' => ['photos/cocktail-dem-nhiet-doi.webp', 'Cocktail tại quầy bar Gánh Ngon'],
        'cocktail' => ['photos/cocktail-xanh-quay-bar.webp', 'Cocktail tại quầy bar Gánh Ngon'],
        'ca-phe-tra' => $bar,
        'sinh-to-nuoc-ep-soda' => $bar,
        'bia-ruou-combo' => ['photos/whisky-macallan.webp', 'Quầy rượu tại Gánh Ngon'],
        'dac-san-ba-mien' => $rice,
    ];
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

function gns_resolve_dish_photo($name, $cat_slug) {
    $slug = gns_slug($name);
    $library = gns_media_photo_index();
    if (isset($library[$slug])) {
        return [
            'scope' => 'dish',
            'url' => $library[$slug],
            'alt' => $name . ' tại Gánh Ngon',
        ];
    }
    $folded = gns_fold($name);
    foreach (gns_dish_photo_rules() as $rule) {
        if (preg_match('/' . $rule['test'] . '/i', $folded)) {
            return [
                'scope' => $rule['scope'],
                'url' => gns_theme_photo_url($rule['image']),
                'alt' => $rule['alt'],
            ];
        }
    }
    $groups = gns_menu_groups();
    $group = $groups[$cat_slug] ?? ['photos/mam-xien-nuong.webp', 'Món tại Gánh Ngon'];
    return [
        'scope' => 'group',
        'url' => gns_theme_photo_url($group[0]),
        'alt' => $group[1],
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
        $photo = $groups[$slug][0] ?? 'photos/lau-ga-sau-rieng.webp';
        $desc = trim((string) ($cat['description'] ?? ''));
        $html .= '<button type="button" class="cat-card" data-cat="' . esc_attr($slug) . '" data-kind="' . esc_attr($kind) . '">';
        $html .= '<span class="cat-media"><img src="' . esc_url(gns_theme_photo_url($photo)) . '" alt="" width="1600" height="914" loading="lazy" decoding="async"><span class="cat-count">' . (int) $count . ' món</span></span>';
        $html .= '<span class="cat-body"><strong class="cat-name">' . esc_html($name) . '</strong><span class="cat-kind">' . ($kind === 'drink' ? 'Đồ uống' : 'Đồ ăn') . '</span>';
        if ($desc !== '') $html .= '<span class="cat-desc">' . esc_html($desc) . '</span>';
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
            $html .= '<div class="dish-media"><img class="dish-photo" src="' . esc_url($photo['url']) . '" alt="' . esc_attr($photo['alt']) . '" width="1400" height="875" loading="lazy" decoding="async">' . $tag . '</div>';
            $html .= '<div class="dish-body"><p class="dish-group">' . esc_html($cat_name) . '</p><h4 class="dish-name">' . esc_html($name) . '</h4>';
            if ($desc !== '') $html .= '<p class="dish-desc">' . esc_html($desc) . '</p>';
            $html .= '<p class="dish-foot"><span class="dish-price dish-ask">Giá báo khi đặt bàn</span></p></div></article>';
        }
    }
    return $html;
}
