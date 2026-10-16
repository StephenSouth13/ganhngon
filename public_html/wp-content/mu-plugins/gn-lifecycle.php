<?php
/**
 * Plugin Name: GN Lifecycle Automation (Email)
 * Description: Chăm sóc khách tự động qua email — cron 07:30 sáng (giờ VN).
 *   B1 Nhắc lịch (ngày khách đến) · B2 Cảm ơn+đánh giá+voucher+referral (hôm sau) ·
 *   B5 Win-back 30 ngày · B8 Rebooking no-show.
 *   An toàn: chỉ booking đúng cửa sổ ngày, chống gửi trùng, có dòng huỷ nhận.
 * Version: 1.2
 */
if (!defined('ABSPATH')) exit;

define('GN_LC_ENABLED', false);  // TẮT gửi khách + cron (chờ CEO xác nhận riêng "bật gửi khách thật"). DEMO vẫn gửi mẫu cho CEO.
define('GN_LC_DEMO',    'v12');  // đổi chuỗi này để gửi lại bộ email DEMO cho CEO; '' = tắt demo
define('GN_LC_TEST_TO', 'bluebedeli@gmail.com');

define('GN_LC_HOTLINE', '0979232233');
define('GN_LC_ADDR', '113 Trần Hưng Đạo, Dương Đông, Phú Quốc');
define('GN_LC_MAP', 'https://maps.google.com/?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc');
define('GN_LC_BOOK', 'https://ganhngon.com/dat-ban/');
define('GN_LC_REVIEW_URL', '');
define('GN_LC_CAP', 200);

add_action('init', function () {
    if (GN_LC_DEMO && get_option('gn_lc_demo_tag') !== GN_LC_DEMO) gn_lc_demo();
    if (!GN_LC_ENABLED) return;
    if (!wp_next_scheduled('gn_lc_daily')) {
        $now = time(); $t = strtotime(gmdate('Y-m-d', $now) . ' 00:30:00 UTC'); // 07:30 VN
        if ($now >= $t) $t += DAY_IN_SECONDS;
        wp_schedule_event($t, 'daily', 'gn_lc_daily');
    }
});
add_action('gn_lc_daily', 'gn_lc_run');

function gn_lc_wrap($b) {
    return '<div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;border:1px solid #eee;border-radius:10px;overflow:hidden">'
        . '<div style="background:#2C1810;color:#D4AF37;padding:18px 24px;font-size:18px;font-weight:700">Gánh Ngon Phú Quốc</div>'
        . '<div style="padding:24px;color:#2C1810;font-size:15px;line-height:1.6">' . $b . '</div>'
        . '<div style="background:#F5F1E8;padding:16px 24px;color:#6b5b4d;font-size:12px;line-height:1.5">'
        . '📍 ' . esc_html(GN_LC_ADDR) . ' · 🕒 6:30–02:00 hằng ngày<br>📞/Zalo: ' . esc_html(GN_LC_HOTLINE)
        . ' · <a href="' . esc_url(GN_LC_MAP) . '" style="color:#6b5b4d">Chỉ đường</a><br>'
        . 'Không muốn nhận email chăm sóc? Phản hồi "HỦY".</div></div>';
}
function gn_lc_send($to, $sub, $b) {
    if (!is_email($to)) return false;
    return wp_mail($to, $sub, gn_lc_wrap($b), ['Content-Type: text/html; charset=UTF-8', 'From: Gánh Ngon Phú Quốc <cskh@ganhngon.com>']);
}
function gn_lc_ref($code) { return 'GN-REF-' . strtoupper(substr(md5($code . 'ref'), 0, 6)); }

function gn_lc_body_reminder($n, $g, $c) {
    return "Chào <b>" . esc_html($n) . "</b>,<br><br>Hôm nay Gánh Ngon hân hạnh đón anh/chị" . ($g > 1 ? " và đoàn <b>{$g} khách</b>" : "") . " 🍤<br>Mã đặt bàn: <b>" . esc_html($c) . "</b><br><br>Hẹn gặp tại <b>" . esc_html(GN_LC_ADDR) . "</b>. Cần đổi giờ/số khách, nhắn Zalo <b>" . esc_html(GN_LC_HOTLINE) . "</b> giúp em nhé.<br><br>Trân trọng,<br>Gánh Ngon Phú Quốc";
}
function gn_lc_body_thanks($n, $c) {
    $v = 'GN-TY-' . strtoupper(substr(md5($c . 'ty'), 0, 6));
    $rv = GN_LC_REVIEW_URL ? '<a href="' . esc_url(GN_LC_REVIEW_URL) . '" style="color:#D4AF37;font-weight:700">đánh giá tại đây</a>' : 'tìm "<b>Gánh Ngon Phú Quốc</b>" trên Google Maps và để lại đánh giá';
    return "Chào <b>" . esc_html($n) . "</b>,<br><br>Cảm ơn anh/chị đã dùng bữa tại Gánh Ngon 🌸<br><br>Nếu thấy ngon, anh/chị giúp em " . $rv . " nhé — động lực lớn cho cả đội.<br><br>🎁 <b>Ưu đãi quay lại</b>: mã <b>" . esc_html($v) . "</b> cho lần đặt bàn sau.<br>👥 <b>Giới thiệu bạn bè</b>: bạn anh/chị đặt bàn kèm mã <b>" . esc_html(gn_lc_ref($c)) . "</b> — cả hai cùng có ưu đãi.<br><br>Hẹn gặp lại!<br>Gánh Ngon Phú Quốc";
}
function gn_lc_body_winback($n) {
    return "Chào <b>" . esc_html($n) . "</b>,<br><br>Đã một tháng kể từ lần anh/chị ghé Gánh Ngon — em nhớ anh/chị! 🌷<br><br>Bếp vừa có thêm món & set menu mới. Mời anh/chị quay lại trải nghiệm: <a href='" . esc_url(GN_LC_BOOK) . "' style='color:#D4AF37;font-weight:700'>đặt bàn tại đây</a> hoặc Zalo <b>" . esc_html(GN_LC_HOTLINE) . "</b>.<br><br>Hẹn gặp lại anh/chị!<br>Gánh Ngon Phú Quốc";
}
function gn_lc_body_noshow($n, $c) {
    return "Chào <b>" . esc_html($n) . "</b>,<br><br>Gánh Ngon tiếc vì chưa được đón anh/chị theo lịch đặt (#" . esc_html($c) . "). Mọi việc vẫn ổn chứ ạ?<br><br>Nếu anh/chị muốn dời lịch, em giữ chỗ lại ngay: <a href='" . esc_url(GN_LC_BOOK) . "' style='color:#D4AF37;font-weight:700'>đặt lại tại đây</a> hoặc nhắn Zalo <b>" . esc_html(GN_LC_HOTLINE) . "</b>.<br><br>Trân trọng,<br>Gánh Ngon Phú Quốc";
}

function gn_lc_mark($t, $id) { $s = get_option('gn_lc_sent', []); $s[] = "$t:$id"; if (count($s) > 5000) $s = array_slice($s, -5000); update_option('gn_lc_sent', $s, false); }
function gn_lc_done($t, $id) { return in_array("$t:$id", get_option('gn_lc_sent', []), true); }

function gn_lc_run() {
    if (!GN_LC_ENABLED) return;
    global $wpdb; $p = $wpdb->prefix . 'sao_'; $cap = (int) GN_LC_CAP;
    $q = function ($where) use ($wpdb, $p, $cap) {
        return $wpdb->get_results("SELECT id,booking_code,customer_name,customer_email,guests,booking_status,booking_date FROM {$p}bookings WHERE customer_email <> '' AND $where ORDER BY id DESC LIMIT $cap");
    };
    // B1 nhắc lịch — hôm nay
    foreach ((array)$q("booking_date = CURDATE() AND booking_status NOT IN ('cancelled','no_show')") as $b)
        if (!gn_lc_done('r', $b->id) && gn_lc_send($b->customer_email, "Hẹn gặp anh/chị hôm nay tại Gánh Ngon 🍤 (#{$b->booking_code})", gn_lc_body_reminder($b->customer_name ?: 'Quý khách', (int)$b->guests, $b->booking_code))) gn_lc_mark('r', $b->id);
    // B2 cảm ơn — hôm qua
    foreach ((array)$q("booking_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND booking_status NOT IN ('cancelled','no_show')") as $b)
        if (!gn_lc_done('t', $b->id) && gn_lc_send($b->customer_email, "Cảm ơn anh/chị đã đến Gánh Ngon 🌸 (#{$b->booking_code})", gn_lc_body_thanks($b->customer_name ?: 'Quý khách', $b->booking_code))) gn_lc_mark('t', $b->id);
    // B5 win-back — 30 ngày trước
    foreach ((array)$q("booking_date = DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND booking_status NOT IN ('cancelled','no_show')") as $b)
        if (!gn_lc_done('w', $b->id) && gn_lc_send($b->customer_email, "Gánh Ngon nhớ anh/chị 🌷 — có món mới rồi!", gn_lc_body_winback($b->customer_name ?: 'Quý khách'))) gn_lc_mark('w', $b->id);
    // B8 rebooking no-show — đã qua ngày & no_show
    foreach ((array)$q("booking_date < CURDATE() AND booking_status = 'no_show'") as $b)
        if (!gn_lc_done('n', $b->id) && gn_lc_send($b->customer_email, "Gánh Ngon giữ chỗ lại cho anh/chị nhé? (#{$b->booking_code})", gn_lc_body_noshow($b->customer_name ?: 'Quý khách', $b->booking_code))) gn_lc_mark('n', $b->id);
}

// DEMO: gửi 4 email mẫu (B1,B2,B5,B8) cho CEO — KHÔNG đụng dữ liệu/khách thật
function gn_lc_demo() {
    $to = GN_LC_TEST_TO; if (!is_email($to)) return;
    $tag = '<div style="background:#FFF8E1;padding:10px 14px;border-radius:6px;margin-bottom:14px;font-size:13px">⬇️ Email MẪU (demo dữ liệu giả). Khách thật nhận đúng nội dung này khi flow chạy.</div>';
    $ok = false;
    $ok |= gn_lc_send($to, '[DEMO B1] Nhắc lịch hôm nay 🍤', $tag . gn_lc_body_reminder('Anh Khiêm', 6, 'GN-DEMO-001'));
    $ok |= gn_lc_send($to, '[DEMO B2] Cảm ơn + đánh giá + voucher + referral 🌸', $tag . gn_lc_body_thanks('Anh Khiêm', 'GN-DEMO-001'));
    $ok |= gn_lc_send($to, '[DEMO B5] Win-back 30 ngày 🌷', $tag . gn_lc_body_winback('Anh Khiêm'));
    $ok |= gn_lc_send($to, '[DEMO B8] Rebooking no-show', $tag . gn_lc_body_noshow('Anh Khiêm', 'GN-DEMO-001'));
    if ($ok) update_option('gn_lc_demo_tag', GN_LC_DEMO, false);
}
