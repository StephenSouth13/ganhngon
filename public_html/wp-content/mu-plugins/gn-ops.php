<?php
/**
 * Plugin Name: GN Ops Alerts (Telegram nội bộ)
 * Description: Báo nội bộ cho nhân viên qua Telegram.
 *   A6 SLA lead chưa xử lý (mỗi 15') · H4 Đoàn lớn sắp đến · J2 Báo cáo ngày (07:35) · J3 Báo cáo tuần (T2).
 *   Chỉ gửi NỘI BỘ (gn_tg_chat_id), không gửi khách.
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

define('GN_OPS_ENABLED', false); // TẮT cron chờ CEO bật; DEMO vẫn gửi 1 mẫu nội bộ
define('GN_OPS_DEMO', 'v1');
define('GN_OPS_SLA_MIN', 30);    // lead 'pending' quá 30' chưa xử lý → cảnh báo
define('GN_OPS_GROUP', 10);      // >=10 khách coi là đoàn

add_filter('cron_schedules', function ($s) { $s['gn_15min'] = ['interval' => 900, 'display' => '15 phút']; return $s; });

add_action('init', function () {
    if (GN_OPS_DEMO && get_option('gn_ops_demo_tag') !== GN_OPS_DEMO) gn_ops_demo();
    if (!GN_OPS_ENABLED) return;
    if (!wp_next_scheduled('gn_ops_daily')) {
        $now = time(); $t = strtotime(gmdate('Y-m-d', $now) . ' 00:35:00 UTC'); if ($now >= $t) $t += DAY_IN_SECONDS;
        wp_schedule_event($t, 'daily', 'gn_ops_daily');
    }
    if (!wp_next_scheduled('gn_ops_sla')) wp_schedule_event(time() + 300, 'gn_15min', 'gn_ops_sla');
});
add_action('gn_ops_daily', 'gn_ops_daily_run');
add_action('gn_ops_sla', 'gn_ops_sla_run');

function gn_ops_tg($t) { return function_exists('gn_send_telegram') ? gn_send_telegram($t) : false; }

// J2 báo cáo ngày + H4 đoàn lớn + (T2) J3 tuần
function gn_ops_daily_run() {
    if (!GN_OPS_ENABLED) return;
    gn_ops_tg(gn_ops_daily_text());
    $grp = gn_ops_group_text(); if ($grp) gn_ops_tg($grp);
    if (date('N') == 1) gn_ops_tg(gn_ops_weekly_text());
}
function gn_ops_daily_text() {
    global $wpdb; $p = $wpdb->prefix . 'sao_';
    $cnt = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date=CURDATE() AND booking_status NOT IN ('cancelled','no_show')");
    $gst = (int) $wpdb->get_var("SELECT COALESCE(SUM(guests),0) FROM {$p}bookings WHERE booking_date=CURDATE() AND booking_status NOT IN ('cancelled','no_show')");
    $rev = (int) $wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date=CURDATE()");
    $pend = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_status='pending'");
    return "📊 <b>BÁO CÁO NGÀY</b> " . date('d/m/Y') . "\n"
         . "• Đặt bàn hôm nay: <b>$cnt</b> ($gst khách)\n"
         . "• Doanh thu dự kiến: <b>" . number_format($rev) . "đ</b>\n"
         . "• Lead/booking đang chờ xử lý: <b>$pend</b>\n"
         . "👉 Mở wp-admin → Đặt bàn để xử lý.";
}
function gn_ops_group_text() {
    global $wpdb; $p = $wpdb->prefix . 'sao_';
    $rows = $wpdb->get_results($wpdb->prepare("SELECT booking_code,customer_name,guests,booking_date FROM {$p}bookings WHERE booking_date IN (CURDATE(), DATE_ADD(CURDATE(),INTERVAL 1 DAY)) AND guests>=%d AND booking_status NOT IN ('cancelled','no_show') ORDER BY booking_date", GN_OPS_GROUP));
    if (!$rows) return '';
    $t = "🚌 <b>ĐOÀN LỚN SẮP ĐẾN</b> — báo bếp chuẩn bị:\n";
    foreach ($rows as $r) $t .= "• {$r->booking_date}: <b>{$r->guests} khách</b> — {$r->customer_name} (#{$r->booking_code})\n";
    return $t;
}
function gn_ops_weekly_text() {
    global $wpdb; $p = $wpdb->prefix . 'sao_';
    $cnt = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)");
    $rev = (int) $wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)");
    $ns  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND booking_status='no_show'");
    return "🗓️ <b>BÁO CÁO TUẦN</b> (7 ngày)\n• Booking: <b>$cnt</b>\n• Doanh thu: <b>" . number_format($rev) . "đ</b>\n• No-show: <b>$ns</b>";
}

// A6 SLA — lead pending quá hạn chưa xử lý
function gn_ops_sla_run() {
    if (!GN_OPS_ENABLED) return;
    global $wpdb; $p = $wpdb->prefix . 'sao_';
    $rows = $wpdb->get_results($wpdb->prepare("SELECT id,booking_code,customer_name,customer_phone,created_at FROM {$p}bookings WHERE booking_status='pending' AND created_at < (NOW() - INTERVAL %d MINUTE) ORDER BY id DESC LIMIT 20", GN_OPS_SLA_MIN));
    $done = get_option('gn_ops_sla_done', []);
    foreach ((array)$rows as $r) {
        if (in_array($r->id, $done, true)) continue;
        $ok = gn_ops_tg("⏰ <b>LEAD CHƯA XỬ LÝ >" . GN_OPS_SLA_MIN . "'</b>\n👤 {$r->customer_name}\n📞 {$r->customer_phone}\n#{$r->booking_code} (lúc {$r->created_at})\n👉 Liên hệ khách ngay!");
        if ($ok) { $done[] = $r->id; }
    }
    if (count($done) > 2000) $done = array_slice($done, -2000);
    update_option('gn_ops_sla_done', $done, false);
}

// DEMO: gửi mẫu báo cáo ngày + SLA vào Telegram nội bộ (1 lần)
function gn_ops_demo() {
    $ok = gn_ops_tg("🧪 <b>[DEMO] Ops Alerts đã hoạt động</b>\n\n" . gn_ops_daily_text() . "\n\n— Mẫu: cứ 07:35 sáng gửi báo cáo ngày; mỗi 15' cảnh báo lead chưa xử lý; T2 có báo cáo tuần.");
    // đánh dấu done kể cả khi tg chưa cấu hình (tránh lặp); nếu false do chưa có token thì vẫn set để không spam thử
    update_option('gn_ops_demo_tag', GN_OPS_DEMO, false);
    update_option('gn_ops_demo_result', $ok ? 'sent' : 'no_tg_config', false);
}
