<?php
/**
 * Guest group, party size and seating area for the contact form (/lien-he/)
 * and the booking form (/dat-ban/). Values are fixed lists; anything else
 * posted is dropped on the server.
 */
if (!defined('ABSPATH')) exit;

function gns_guest_groups() {
    return [
        'khach-le' => ['Khách lẻ', 'Cá nhân, cặp đôi, nhóm bạn nhỏ đến ăn hoặc đặt bàn'],
        'gia-dinh' => ['Khách gia đình', 'Ăn uống gia đình, có trẻ em/người lớn tuổi, cần không gian phù hợp'],
        'doan-truc-tiep' => ['Khách đoàn trực tiếp', 'Đoàn đông tự liên hệ đặt ăn, cần set menu và phục vụ đúng lịch'],
        'doanh-nghiep' => ['Khách doanh nghiệp', 'Tiếp đối tác, liên hoan, họp mặt công ty; cần hợp đồng, hóa đơn'],
        'dat-tiec' => ['Khách đặt tiệc', 'Sinh nhật, thôi nôi, mừng thọ, kỷ niệm; cần trang trí và dịch vụ bổ sung'],
        'doi-tac-dua-khach' => ['Đối tác đưa khách', 'Công ty du lịch, hướng dẫn viên, khách sạn hoặc người giới thiệu; cần giá hợp tác, chiết khấu/hoa hồng'],
    ];
}

function gns_guest_areas() {
    return [
        'khu-a' => 'Khu A',
        'khu-b' => 'Khu B',
        'phong-vip' => 'Phòng VIP',
        'khong-yeu-cau' => 'Không yêu cầu',
    ];
}

/** Read and validate the three fields from $_POST. Unknown values become ''. */
function gns_guest_fields_from_post() {
    $groups = gns_guest_groups();
    $areas = gns_guest_areas();
    $group = sanitize_key(wp_unslash($_POST['guest_group'] ?? ''));
    $area = sanitize_key(wp_unslash($_POST['area'] ?? ''));
    $count = isset($_POST['guest_count']) ? absint(wp_unslash($_POST['guest_count'])) : 0;
    if ($count > 5000) $count = 0;
    return [
        'group' => isset($groups[$group]) ? $group : '',
        'group_label' => isset($groups[$group]) ? $groups[$group][0] : '',
        'area' => isset($areas[$area]) ? $area : '',
        'area_label' => isset($areas[$area]) ? $areas[$area] : '',
        'count' => $count,
    ];
}

function gns_guest_group_select($required = true, $selected = '') {
    $groups = gns_guest_groups();
    $selected = isset($groups[$selected]) ? $selected : '';
    $hint_default = function_exists('gns_t') ? gns_t('form_group_hint', 'Chọn nhóm để nhà hàng chuẩn bị đúng nhu cầu.') : 'Chọn nhóm để nhà hàng chuẩn bị đúng nhu cầu.';
    $hint = $selected !== '' ? $groups[$selected][1] : $hint_default;
    if ($selected !== '' && function_exists('gns_t')) {
        $translated = gns_ui_text('group_' . $selected . '_hint');
        if ($translated === null) $translated = $groups[$selected][1];
        $hint = $translated;
    }
    $id = 'gns-group-' . wp_rand(100, 999999);
    $group_label = function_exists('gns_t') ? gns_t('form_group', 'Nhóm khách hàng') : 'Nhóm khách hàng';
    $group_ph = function_exists('gns_t') ? gns_t('form_group_ph', 'Chọn nhóm khách') : 'Chọn nhóm khách';
    ob_start(); ?>
    <label class="gns-guest-group"><?php echo esc_html($group_label); ?><?php echo $required ? ' *' : ''; ?>
      <select name="guest_group" data-gns-group aria-describedby="<?php echo esc_attr($id); ?>"<?php echo $required ? ' required' : ''; ?>>
        <option value="" data-hint="<?php echo esc_attr($hint_default); ?>"><?php echo esc_html($group_ph); ?></option>
        <?php foreach ($groups as $key => $g): ?>
          <option value="<?php echo esc_attr($key); ?>" data-hint="<?php echo esc_attr($g[1]); ?>"<?php selected($selected, $key); ?>><?php echo esc_html(function_exists('gns_t') ? gns_t('group_' . $key, $g[0]) : $g[0]); ?></option>
        <?php endforeach; ?>
      </select>
      <small class="gns-hint" id="<?php echo esc_attr($id); ?>" data-gns-hint><?php echo esc_html($hint); ?></small>
    </label>
    <?php
    return ob_get_clean();
}

function gns_guest_area_select($with_vip = true) {
    $areas = gns_guest_areas();
    if (!$with_vip) unset($areas['phong-vip']);
    ob_start(); ?>
    <label class="gns-guest-area"><?php echo esc_html(function_exists('gns_t') ? gns_t('form_area', 'Khu vực') : 'Khu vực'); ?>
      <select name="area">
        <?php foreach ($areas as $key => $label): ?>
          <option value="<?php echo esc_attr($key); ?>"<?php selected($key, 'khong-yeu-cau'); ?>><?php echo esc_html(function_exists('gns_t') ? gns_t('area_' . $key, $label) : $label); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <?php
    return ob_get_clean();
}

/** Block placed inside the /lien-he/ form. */
function gns_contact_guest_fields_html() {
    ob_start(); ?>
    <div class="form-grid gns-guest-fields">
      <div class="span-2"><?php echo gns_guest_group_select(true); ?></div>
      <label>Số lượng khách<input name="guest_count" type="number" min="1" max="5000" step="1" inputmode="numeric" placeholder="Ví dụ: 4"></label>
      <?php echo gns_guest_area_select(true); ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Add guest group and area to the parent theme's booking form. Guest count
 * already exists there (name="guests"). Phòng VIP already exists in the
 * parent's "Chỗ ngồi" select, so the area list here leaves it out.
 */
function gns_booking_form_with_guest_fields($html) {
    if (!is_string($html) || $html === '' || strpos($html, 'name="guest_group"') !== false) return $html;
    $extra = gns_guest_group_select(false) . gns_guest_area_select(false);
    $marker = '<label>Ghi chú';
    if (strpos($html, $marker) !== false) {
        return preg_replace('/' . preg_quote($marker, '/') . '/', $extra . $marker, $html, 1);
    }
    $button = '<button type="submit"';
    if (strpos($html, $button) !== false) {
        return preg_replace('/' . preg_quote($button, '/') . '/', $extra . $button, $html, 1);
    }
    return $html;
}

/**
 * Before the parent booking handler runs, fold the two new fields into the
 * fields it already stores: guest group goes into "need" (saved as
 * "Nhu cầu: …" in wp_sao_bookings.notes, staff email, Telegram and CRM),
 * area goes into the note.
 */
function gns_booking_prefill_post() {
    if (empty($_POST['guest_group']) && empty($_POST['area'])) return;
    $f = gns_guest_fields_from_post();
    if ($f['group_label'] !== '') {
        $need = sanitize_text_field(wp_unslash($_POST['need'] ?? ''));
        $_POST['need'] = $need !== '' ? $f['group_label'] . ', ' . $need : $f['group_label'];
    }
    if ($f['area'] !== '' && $f['area'] !== 'khong-yeu-cau') {
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
        $_POST['note'] = trim('Khu vực: ' . $f['area_label'] . '. ' . $note);
    }
    unset($_POST['guest_group'], $_POST['area']);
}
add_action('wp_ajax_sao_create_booking', 'gns_booking_prefill_post', 1);
add_action('wp_ajax_nopriv_sao_create_booking', 'gns_booking_prefill_post', 1);

/** Khu B photos bundled in assets/img. Prints nothing until the files exist. */
function gns_khu_b_photos() {
    $extra = function_exists('gns_mod_gallery') ? gns_mod_gallery('gns_khu_b_', 3, 'Khu B') : [];
    $dir = get_stylesheet_directory() . '/assets/img/';
    $uri = get_stylesheet_directory_uri() . '/assets/img/';
    $all = [
        'khu-b-tiec-thoi-noi.jpg' => ['Khu B set tiệc thôi nôi', 'Khu B của Gánh Ngon trang trí tiệc thôi nôi, bàn gỗ dài và đèn mây'],
        'khu-b-tiec-sinh-nhat.jpg' => ['Khu B set tiệc sinh nhật', 'Khu B của Gánh Ngon trang trí tiệc sinh nhật với bàn gỗ dài'],
    ];
    $out = [];
    foreach ($all as $file => $text) {
        if (!is_readable($dir . $file)) continue;
        $size = @getimagesize($dir . $file);
        $out[] = [
            'src' => $uri . $file . '?v=' . (int) @filemtime($dir . $file),
            'caption' => $text[0],
            'alt' => $text[1],
            'w' => $size ? (int) $size[0] : 1600,
            'h' => $size ? (int) $size[1] : 900,
        ];
    }
    return array_merge($extra, $out);
}

function gns_space_copy($title_key, $text_key, $title, $text) {
    $custom_title = function_exists('gns_saved_text') ? gns_saved_text($title_key) : '';
    $custom_text = function_exists('gns_saved_text') ? gns_saved_text($text_key) : '';
    return [
        $custom_title !== '' ? $custom_title : $title,
        $custom_text !== '' ? $custom_text : $text,
    ];
}

function gns_khu_a_photos() {
    $extra = function_exists('gns_mod_gallery') ? gns_mod_gallery('gns_khu_a_', 3, 'Khu A') : [];
    $dir = get_stylesheet_directory() . '/assets/img/';
    $uri = get_stylesheet_directory_uri() . '/assets/img/';
    $out = [];
    $files = [];
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $found = glob($dir . 'khu-a-*.' . $ext);
        if (is_array($found)) $files = array_merge($files, $found);
    }
    foreach ($files as $file) {
        if (!is_readable($file)) continue;
        $base = basename($file);
        $size = @getimagesize($file);
        $out[] = [
            'src' => $uri . $base . '?v=' . (int) @filemtime($file),
            'caption' => 'Khu A',
            'alt' => 'Khu A của Gánh Ngon',
            'w' => $size ? (int) $size[0] : 1600,
            'h' => $size ? (int) $size[1] : 900,
        ];
    }
    return array_merge($extra, $out);
}

function gns_khu_a_gallery_html() {
    $photos = gns_khu_a_photos();
    if (!$photos) return '';
    list($title, $lead) = gns_space_copy(
        'gns_khu_a_title',
        'gns_khu_a_text',
        'Khu A – sảnh chính',
        'Bàn thường giữa nhà. Nhận trong ngày.'
    );
    ob_start(); ?>
<section class="band band-ivory gns-khu-b" id="khu-a" aria-labelledby="khu-a-title">
  <div class="wrap">
    <p class="eyebrow">Khu A</p>
    <h2 id="khu-a-title" class="title"><?php echo esc_html($title); ?></h2>
    <p class="section-lead gns-khu-b-lead"><?php echo esc_html($lead); ?></p>
    <div class="gns-khu-b-grid">
      <?php foreach ($photos as $p): ?>
      <figure>
        <img src="<?php echo esc_url($p['src']); ?>" alt="<?php echo esc_attr($p['alt']); ?>" width="<?php echo (int) $p['w']; ?>" height="<?php echo (int) $p['h']; ?>" loading="lazy" decoding="async">
        <figcaption><?php echo esc_html($p['caption']); ?></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
    <?php
    return ob_get_clean();
}

function gns_khu_b_gallery_html() {
    $photos = gns_khu_b_photos();
    if (!$photos) return '';
    list($title, $lead) = gns_space_copy(
        'gns_khu_b_title',
        'gns_khu_b_text',
        'Khu B – không gian tiệc',
        'Bàn gỗ dài, đèn mây, có khu sân khấu. Nhà hàng trang trí theo từng buổi tiệc: thôi nôi, sinh nhật, họp mặt. Chọn “Khu B” trong form để nhà hàng giữ chỗ và tư vấn.'
    );
    ob_start(); ?>
<section class="band band-night gns-khu-b" id="khu-b" aria-labelledby="khu-b-title">
  <div class="wrap">
    <p class="eyebrow eyebrow-gold">Khu B</p>
    <h2 id="khu-b-title" class="title title-light"><?php echo esc_html($title); ?></h2>
    <p class="section-lead section-lead-light gns-khu-b-lead"><?php echo esc_html($lead); ?></p>
    <div class="gns-khu-b-grid">
      <?php foreach ($photos as $p): ?>
      <figure>
        <img src="<?php echo esc_url($p['src']); ?>" alt="<?php echo esc_attr($p['alt']); ?>" width="<?php echo (int) $p['w']; ?>" height="<?php echo (int) $p['h']; ?>" loading="lazy" decoding="async">
        <figcaption><?php echo esc_html($p['caption']); ?></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
    <?php
    return ob_get_clean();
}
