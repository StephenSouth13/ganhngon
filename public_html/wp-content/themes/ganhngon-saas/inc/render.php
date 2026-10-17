<?php
/**
 * Baked SaaS page bodies. Tokens are filled with WordPress URLs and plugin data.
 * The HTML never calls the SaaS menu, form, ticket, or lead APIs.
 */
if (!defined('ABSPATH')) exit;

function gns_main_path($key) {
    $key = preg_replace('/[^a-z0-9\-]/', '', (string) $key);
    if ($key === '') return '';
    $file = get_stylesheet_directory() . '/parts/mains/' . $key . '.html';
    return is_readable($file) ? $file : '';
}

function gns_table_ready($short) {
    global $wpdb;
    if (!function_exists('gn_tbl')) return false;
    $table = gn_tbl($short);
    $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)));
    return $found === $table;
}

function gns_render_main($key) {
    if ($key === '' || $key === 'home') {
        $lang = function_exists('gn_cur_lang') ? gn_cur_lang() : 'vi';
        if ($lang && $lang !== 'vi' && gns_main_path('home-' . $lang)) {
            $key = 'home-' . $lang;
        } else {
            $key = 'home';
        }
    }
    $file = gns_main_path($key);
    if (!$file) return false;
    $html = file_get_contents($file);
    if ($html === false) return false;
    echo gns_fill_main($html);
    return true;
}

function gns_fill_main($html) {
    $assets = esc_url(get_stylesheet_directory_uri() . '/assets');
    $html = preg_replace_callback('/\{\{U:([a-z0-9\-]+)\}\}/', function ($m) {
        return esc_url(gns_url($m[1]));
    }, $html);
    $html = str_replace('{{HOME}}', esc_url(gns_url('')), $html);
    $html = preg_replace_callback('/\{\{LANG:(en|zh|ko|ru|ar)\}\}/', function ($m) {
        return esc_url(home_url('/' . $m[1] . '/'));
    }, $html);
    $html = str_replace('{{ASSETS}}', $assets, $html);
    if (strpos($html, '{{MENU_CATS}}') !== false) {
        $html = str_replace('{{MENU_CATS}}', gns_menu_cats_html(), $html);
        $html = str_replace('{{MENU_CARDS}}', gns_menu_cards_html(), $html);
    }

    if (strpos($html, '{{BOOKING_FORM}}') !== false) {
        $form = function_exists('gn_dat_ban_form_html') ? gn_dat_ban_form_html() : '';
        $form = gns_booking_form_with_guest_fields($form);
        $html = str_replace('{{BOOKING_FORM}}', $form, $html);
    }
    if (strpos($html, '{{GUEST_FIELDS}}') !== false) {
        $html = str_replace('{{GUEST_FIELDS}}', gns_contact_guest_fields_html(), $html);
    }
    if (strpos($html, '{{KHU_B_GALLERY}}') !== false) {
        $html = str_replace('{{KHU_B_GALLERY}}', gns_khu_b_gallery_html(), $html);
    }
    if (strpos($html, '{{PARTNER_FORM}}') !== false) {
        $html = str_replace('{{PARTNER_FORM}}', gns_partner_form_html(), $html);
    }
    if (strpos($html, '{{VOUCHER_BLOCK}}') !== false) {
        $html = str_replace('{{VOUCHER_BLOCK}}', gns_voucher_block_html(), $html);
    }
    if (strpos($html, '{{LOOKUP_RESULT}}') !== false) {
        $html = str_replace('{{LOOKUP_RESULT}}', gns_lookup_result_html(), $html);
    }
    if (strpos($html, '{{VERIFY_RESULT}}') !== false) {
        $ma = isset($_GET['ma']) ? sanitize_text_field(wp_unslash($_GET['ma'])) : '';
        if ($ma !== '') {
            $html = str_replace('name="ma" required', 'name="ma" required value="' . esc_attr($ma) . '"', $html);
        }
        $html = str_replace('{{VERIFY_RESULT}}', gns_verify_result_html(), $html);
    }
    if (strpos($html, '{{PORTAL_OR_FORM}}') !== false) {
        $token = isset($_GET['t']) ? sanitize_text_field(wp_unslash($_GET['t'])) : '';
        if (strlen($token) >= 20 && function_exists('gn_portal_html')) {
            $html = preg_replace('#<form id="portal-request\b.*?</form>#s', '', $html, 1);
            $html = str_replace('{{PORTAL_OR_FORM}}', gn_portal_html(), $html);
        } else {
            $html = str_replace('{{PORTAL_OR_FORM}}', '', $html);
        }
    }
    return $html;
}

function gns_partner_form_html() {
    $ajax = esc_url(admin_url('admin-ajax.php'));
    $options = [
        'gioi-thieu' => 'Giới thiệu bằng link/mã',
        'doan-dia-phuong' => 'Đưa đoàn / hợp tác địa phương',
        'tu-van' => 'Cần tư vấn thêm',
    ];
    if (function_exists('gn_partner_signup_field')) {
        $field = gn_partner_signup_field('partner_type');
        if (!empty($field['options']) && is_array($field['options'])) {
            $options = $field['options'];
        }
    }
    ob_start(); ?>
    <form class="form" data-gns-partner action="<?php echo $ajax; ?>?action=gn_partner_register" aria-label="Đăng ký đối tác">
      <input type="text" name="website" class="sr-only" tabindex="-1" autocomplete="off" aria-hidden="true">
      <label>Họ tên *<input name="full_name" required autocomplete="name"></label>
      <label>Số điện thoại / Zalo *<input name="phone" type="tel" required autocomplete="tel"></label>
      <label>Bạn muốn hợp tác thế nào? *
        <select name="partner_type" required>
          <option value="">Chọn một cách</option>
          <?php foreach ($options as $value => $label): ?>
            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Email<input name="email" type="email" autocomplete="email"></label>
      <label>Khu vực hoạt động<input name="area" placeholder="Dương Đông, sân bay, resort…"></label>
      <label>Website / Facebook / TikTok<input name="social_link" placeholder="https://"></label>
      <button class="btn btn-dark" type="submit">Gửi đăng ký</button>
      <p class="form-note" data-form-status role="status">Gánh Ngon đã nhận đăng ký sẽ liên hệ qua số đã để lại. Số đã đăng ký trước đó không tạo hồ sơ mới.</p>
    </form>
    <?php
    return ob_get_clean();
}

function gns_money_label($n) {
    if (function_exists('gn_money')) return gn_money($n);
    return number_format((float) $n, 0, ',', '.') . 'đ';
}

function gns_voucher_block_html() {
    $tiers = function_exists('gn_voucher_tiers') ? gn_voucher_tiers() : [];
    ob_start();
    echo '<ul class="ticks">';
    foreach ($tiers as $face => $price) {
        echo '<li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-check"></use></svg> '
            . esc_html(gns_money_label($face)) . ' — mua ' . esc_html(gns_money_label($price)) . '</li>';
    }
    echo '</ul>';
    if ($tiers) {
        echo '<form class="form" data-gns-voucher>';
        echo '<p class="form-note">Chọn mệnh giá nhà hàng đang phát hành. Số tiền mua lấy từ bảng này, không lấy từ trình duyệt.</p>';
        $i = 0;
        foreach ($tiers as $face => $price) {
            $i++;
            echo '<label class="voucher-tier"><input type="radio" name="face_value" value="' . (int) $face . '"' . ($i === 2 ? ' checked' : '') . '> '
                . '<strong>' . esc_html(gns_money_label($face)) . '</strong> <span>mua ' . esc_html(gns_money_label($price)) . '</span></label>';
        }
        echo '<label>Họ và tên *<input name="buyer_name" required autocomplete="name"></label>';
        echo '<label>Email nhận voucher *<input name="buyer_email" type="email" required autocomplete="email"></label>';
        echo '<label>Số điện thoại / Zalo<input name="buyer_phone" type="tel" autocomplete="tel"></label>';
        echo '<button class="btn btn-dark" type="submit">Nhận voucher giữ chỗ</button>';
        echo '<p class="form-note">Chưa mất tiền lúc này. Voucher gửi về email ở trạng thái chưa thanh toán. Đến nhà hàng trả tại quầy là dùng được.</p>';
        echo '<p class="form-note" data-voucher-out role="status"></p>';
        echo '</form>';
    }
    $code = isset($_GET['c']) ? strtoupper(sanitize_text_field(wp_unslash($_GET['c']))) : '';
    if ($code !== '') {
        echo gns_voucher_lookup_html($code);
    }
    return ob_get_clean();
}

function gns_voucher_lookup_html($code) {
    $code = strtoupper(trim((string) $code));
    if ($code === '') return '';
    if (!preg_match('/^[A-Z0-9\-]{4,40}$/', $code)) {
        return '<p class="form-note" role="status">Mã không đúng định dạng.</p>';
    }
    if (!gns_table_ready('vouchers') || !function_exists('gn_voucher_view_html')) {
        return '<p class="form-note" role="status">Chưa tra được mã. Gọi 0979 232 233.</p>';
    }
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . gn_tbl('vouchers') . ' WHERE code = %s', $code));
    if (!$row) {
        return '<p class="form-note" role="status">Không thấy mã này. Mã đối tác (GN-P…) kiểm tra ở trang xác minh, hoặc gọi 0979 232 233.</p>';
    }
    return gn_voucher_view_html($row);
}

function gns_lookup_result_html() {
    $code = '';
    if (isset($_GET['code'])) $code = sanitize_text_field(wp_unslash($_GET['code']));
    if ($code === '' && isset($_GET['c'])) $code = sanitize_text_field(wp_unslash($_GET['c']));
    if ($code === '') return '<p class="form-note" data-lookup-result role="status"></p>';
    return gns_voucher_lookup_html($code);
}

function gns_verify_result_html() {
    $code = isset($_GET['ma']) ? sanitize_text_field(wp_unslash($_GET['ma'])) : '';
    if ($code === '') return '';
    if (!function_exists('gn_partner_by_code') || !gns_table_ready('partners')) {
        return '<p class="form-note" role="status">Chưa kiểm được mã. Gọi 0979 232 233 trước khi thanh toán.</p>';
    }
    $partner = gn_partner_by_code($code);
    if (!$partner) {
        return '<p class="form-note" role="status">Nhà hàng chưa xác nhận mã này đang hoạt động. Gọi 0979 232 233 trước khi thanh toán.</p>';
    }
    $name = '';
    if (!empty($partner->contact)) $name = $partner->contact;
    elseif (!empty($partner->company)) $name = $partner->company;
    $area = !empty($partner->area) ? $partner->area : '';
    $text = 'Mã ' . $partner->ref_code . ' đang hoạt động';
    if ($name !== '') $text .= ' · ' . $name;
    if ($area !== '') $text .= ' · ' . $area;
    $text .= '. Trang này không hiện doanh thu.';
    return '<p class="form-note" role="status">' . esc_html($text) . '</p>';
}

function gns_seed_pages() {
    if (get_option('gns_seeded_pages') === '1.2.0') return;
    $pages = [
        'thuc-don' => 'Thực đơn',
        'dat-ban' => 'Đặt bàn',
        'khach-doan-tiec-rieng' => 'Tiệc & Sự kiện',
        've-ganh-ngon' => 'Về Gánh Ngon',
        'lien-he' => 'Liên hệ',
        'tin-tuc' => 'Tin tức',
        'hinh-anh-review' => 'Hình ảnh & review',
        'ai-tu-van' => 'Hỏi món',
        'doi-tac' => 'Hợp tác kinh doanh',
        'cong-doi-tac' => 'Cổng đối tác',
        'xac-minh' => 'Xác minh đối tác',
        'voucher' => 'Voucher',
        'tra-cuu' => 'Tra cứu mã',
        'du-lich-phu-quoc' => 'Du lịch Phú Quốc',
        'cam-nang' => 'Cẩm nang',
        'chinh-sach-dat-ban' => 'Chính sách đặt bàn',
        'chinh-sach-huy-doi' => 'Chính sách hủy đổi',
        'dieu-khoan-dich-vu' => 'Điều khoản dịch vụ',
        'chinh-sach-bao-mat' => 'Chính sách bảo mật',
        'lau-ga-sau-rieng' => 'Lẩu gà sầu riêng',
    ];
    foreach ($pages as $slug => $title) {
        if (get_page_by_path($slug)) continue;
        $id = wp_insert_post([
            'post_title' => $title,
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '',
        ]);
        if ($id && !is_wp_error($id) && function_exists('pll_set_post_language')) {
            pll_set_post_language($id, 'vi');
        }
    }
    update_option('gns_seeded_pages', '1.2.0');
}
add_action('init', 'gns_seed_pages', 20);

add_action('wp_ajax_nopriv_gns_send_note', 'gns_ajax_send_note');
add_action('wp_ajax_gns_send_note', 'gns_ajax_send_note');
function gns_ajax_send_note() {
    check_ajax_referer('gns_lead', 'nonce');
    if (!empty($_POST['website'])) {
        wp_send_json_success(['message' => 'Đã nhận. Nhà hàng sẽ liên hệ lại.']);
    }
    $boxes = [
        'contact' => 'contact@ganhngon.com',
        'sales' => 'sales@ganhngon.com',
        'partner' => 'partner@ganhngon.com',
    ];
    $box = sanitize_key($_POST['box'] ?? '');
    if (!isset($boxes[$box])) wp_send_json_error(['message' => 'Không gửi được. Gọi 0979 232 233.']);

    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $key = 'gns_note_' . md5($ip . wp_date('YmdHi', (int) (floor(time() / 600) * 600)));
    $hits = (int) get_transient($key);
    if ($hits >= 8) wp_send_json_error(['message' => 'Bạn vừa gửi nhiều lời nhắn. Gọi 0979 232 233.']);
    set_transient($key, $hits + 1, 10 * MINUTE_IN_SECONDS);

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if ($name === '' || $phone === '') wp_send_json_error(['message' => 'Thiếu họ tên hoặc số điện thoại.']);

    // Guest group, party size and area (contact form). Fixed lists only.
    $guest = gns_guest_fields_from_post();
    if (!empty($_POST['guest_group']) && $guest['group'] === '') {
        wp_send_json_error(['message' => 'Nhóm khách hàng không hợp lệ. Chọn lại giúp nhà hàng.']);
    }
    if ($box === 'contact' && isset($_POST['guest_group']) && $guest['group'] === '') {
        wp_send_json_error(['message' => 'Chọn nhóm khách hàng giúp nhà hàng.']);
    }
    $guest_lines = '';
    if ($guest['group_label'] !== '') {
        $groups = gns_guest_groups();
        $guest_lines .= 'Nhóm khách hàng: ' . $guest['group_label'] . ' (' . $groups[$guest['group']][1] . ")\n";
    }
    if ($guest['count'] > 0) $guest_lines .= 'Số lượng khách: ' . $guest['count'] . "\n";
    if ($guest['area_label'] !== '') $guest_lines .= 'Khu vực: ' . $guest['area_label'] . "\n";

    $skip = ['action', 'nonce', 'box', 'website', 'name', 'phone', 'email', 'message', 'guest_group', 'guest_count', 'area'];
    $extra = '';
    foreach ($_POST as $field => $value) {
        if (in_array($field, $skip, true)) continue;
        if (is_array($value)) {
            $value = implode(', ', array_map('sanitize_text_field', wp_unslash($value)));
        } else {
            $value = sanitize_text_field(wp_unslash($value));
        }
        if ($value === '') continue;
        $extra .= sanitize_key($field) . ': ' . $value . "\n";
    }
    $subjects = [
        'contact' => 'Lời nhắn website',
        'sales' => 'Báo giá tiệc',
        'partner' => 'Xin link cổng đối tác',
    ];
    $body = "Tên: $name\nĐiện thoại: $phone\nEmail: $email\n" . $guest_lines . "\n$message\n\n$extra";
    $subject = 'Gánh Ngon — ' . $subjects[$box] . ' — ' . $name;
    if ($guest['group_label'] !== '') {
        $subject .= ' — ' . $guest['group_label'] . ($guest['count'] > 0 ? ' (' . $guest['count'] . ' khách)' : '');
    }
    $sent = wp_mail($boxes[$box], $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);
    if (!$sent) wp_send_json_error(['message' => 'Không gửi được. Gọi 0979 232 233.']);
    wp_send_json_success(['message' => 'Đã nhận. Nhà hàng sẽ liên hệ lại.']);
}
