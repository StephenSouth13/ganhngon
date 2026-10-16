<?php
/**
 * Plugin Name: GN Trust Pages
 * Description: Trust and verification pages for partner links and eVouchers.
 * Version: 1.2
 */
if (!defined('ABSPATH')) exit;

define('GN_TP_HOTLINE', '0979 232 233');
define('GN_TP_HOTLINE_TEL', '+84979232233');

function gn_tp_tbl($name) {
    if (function_exists('gn_tbl')) return gn_tbl($name);
    global $wpdb;
    return $wpdb->prefix . 'sao_' . $name;
}
function gn_tp_money($n) { return number_format((float)$n, 0, ',', '.') . 'đ'; }
function gn_tp_mask_phone($p) {
    $p = preg_replace('/\s+/', '', (string)$p);
    if (strlen($p) < 7) return $p;
    return substr($p, 0, 4) . str_repeat('*', max(0, strlen($p)-7)) . substr($p, -3);
}
function gn_tp_lang($set = null) {
    static $lang = 'vi';
    if ($set !== null) $lang = in_array($set, array('en','zh','ko','ru','ar'), true) ? $set : 'vi';
    return $lang;
}
function gn_tp_t($vi, $en = null) {
    $lang = gn_tp_lang();
    if ($lang === 'vi') return $vi;
    if (function_exists('gn_tp_dict')) {
        $dict = gn_tp_dict();
        if (isset($dict[$vi][$lang])) return $dict[$vi][$lang];
        if ($lang !== 'en' && isset($dict[$vi]['en'])) return $dict[$vi]['en'];
    }
    return $en !== null ? $en : $vi;
}
function gn_tp_rate_ok($bucket, $limit) { return (int)get_transient('gn_tp_' . md5($bucket)) < $limit; }
function gn_tp_rate_bump($bucket) {
    $key = 'gn_tp_' . md5($bucket);
    set_transient($key, ((int)get_transient($key)) + 1, HOUR_IN_SECONDS);
}
function gn_tp_client_ip() { return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; }

function gn_tp_partner_type_label($type) {
    $map = array(
        'tai-xe'=>'Tài xế taxi / xe công nghệ', 'taixe'=>'Tài xế taxi / xe công nghệ',
        'ks'=>'Khách sạn / resort', 'khachsan'=>'Khách sạn / resort', 'khach-san'=>'Khách sạn / resort',
        'hdv'=>'Hướng dẫn viên du lịch', 'huong-dan-vien'=>'Hướng dẫn viên du lịch',
        'kol'=>'KOL / Travel blogger', 'cty'=>'Công ty du lịch', 'cong-ty-du-lich'=>'Công ty du lịch',
        'hiep-hoi'=>'Hiệp hội du lịch', 'agent'=>'Đại lý', 'gioi-thieu'=>'Giới thiệu bằng link/mã',
        'doan-dia-phuong'=>'Đưa đoàn / hợp tác địa phương', 'tu-van'=>'Cần tư vấn thêm', 'khac'=>'Đối tác',
    );
    return isset($map[$type]) ? $map[$type] : 'Đối tác';
}
function gn_tp_domain_note() {
    return '<div class="tp-card"><p>Mọi liên kết chính thức của Gánh Ngon đều nằm trên tên miền <b>ganhngon.com</b>. Nếu nhận được tên miền khác, gọi ' . GN_TP_HOTLINE . ' để xác minh.</p></div>';
}
function gn_tp_scan_html($partner, $code, $dest, $target) {
    if (!$partner) return '<div class="tp-card tp-bad"><h2>Không tìm thấy mã đối tác</h2><p>Mã này không nằm trong danh sách đối tác chính thức của Gánh Ngon.</p><a href="' . esc_url(home_url('/dat-ban/')) . '">Đặt bàn tại ganhngon.com</a></div>' . gn_tp_domain_note();
    $name = !empty($partner->contact) ? $partner->contact : $partner->company;
    return '<div class="tp-card tp-ok"><b>Đối tác chính thức của Gánh Ngon</b><h2>' . esc_html($name) . '</h2><p>' . esc_html(gn_tp_partner_type_label($partner->type)) . '</p></div><div class="tp-card"><p>Đơn của quý khách sẽ được ghi nhận cho người giới thiệu. Không phải trả thêm.</p><a href="' . esc_url($dest) . '">Tiếp tục đặt bàn</a></div>' . gn_tp_domain_note();
}
function gn_tp_render_shell($title, $html) {
    add_filter('pre_get_document_title', function () use ($title) { return $title . ' — Gánh Ngon Phú Quốc'; });
    add_filter('wp_robots', function ($robots) { $robots['noindex'] = true; return $robots; });
    get_header(); echo '<main style="padding:34px 18px"><div class="gntp">' . $html . '</div></main>'; get_footer();
}

add_action('template_redirect', function () {
    $code = get_query_var('gn_ref_code');
    if (!$code) return;
    $target = get_query_var('gn_ref_target') ?: 'dat-ban';
    $map = array('dat-ban'=>'/dat-ban/','voucher'=>'/voucher/','thuc-don'=>'/thuc-don/');
    $dest = home_url(isset($map[$target]) ? $map[$target] : '/dat-ban/');
    $partner = function_exists('gn_partner_by_code') ? gn_partner_by_code($code) : null;
    status_header($partner ? 200 : 404); nocache_headers();
    gn_tp_render_shell($partner ? 'Đối tác chính thức' : 'Không tìm thấy mã đối tác', gn_tp_scan_html($partner, $code, $dest, $target));
    exit;
}, 9);

function gn_tp_voucher_result($voucher) {
    $paid = $voucher->status === 'unpaid' ? 'Chưa — trả tại quán' : ($voucher->status === 'refunded' ? 'Đã hoàn tiền' : 'Đã thanh toán');
    $used = $voucher->status === 'used' ? 'Đã dùng hết' : ($voucher->status === 'expired' ? 'Hết hạn' : 'Chưa sử dụng');
    return '<div class="tp-two"><div class="tp-q"><p>Đã thanh toán chưa?</p><b>' . esc_html($paid) . '</b></div><div class="tp-q"><p>Đã sử dụng chưa?</p><b>' . esc_html($used) . '</b></div></div><div class="tp-card"><b>Mã ' . esc_html($voucher->code) . '</b><p>Số dư còn lại: ' . esc_html(gn_tp_money($voucher->balance)) . '</p></div>' . gn_tp_domain_note();
}
