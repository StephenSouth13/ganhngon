<?php
/**
 * Plugin Name: GN Partner & Pipeline Automation
 * Description: Chạy trên dữ liệu CRM, báo nội bộ Telegram (không gửi khách).
 *   A5 Tự đánh dấu no-show booking quá hạn · G3 Tính hoa hồng đối tác · G4 Báo cáo payout tháng (ngày 5).
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

define('GN_PT_ENABLED', false);  // TẮT chờ CEO bật (A5 sửa trạng thái booking, G nên chạy khi có đối tác thật)
define('GN_PT_DEMO', 'v1');

add_action('init', function () {
    if (GN_PT_DEMO && get_option('gn_pt_demo_tag') !== GN_PT_DEMO) gn_pt_demo();
    if (!GN_PT_ENABLED) return;
    if (!wp_next_scheduled('gn_pt_daily')) {
        $now = time(); $t = strtotime(gmdate('Y-m-d', $now) . ' 00:40:00 UTC'); if ($now >= $t) $t += DAY_IN_SECONDS;
        wp_schedule_event($t, 'daily', 'gn_pt_daily');
    }
});
add_action('gn_pt_daily', 'gn_pt_daily_run');
function gn_pt_tg($t) { return function_exists('gn_send_telegram') ? gn_send_telegram($t) : false; }

function gn_pt_daily_run() {
    if (!GN_PT_ENABLED) return;
    gn_pt_noShowReport();   // báo-cáo-only, KHÔNG tự sửa dữ liệu
    gn_pt_commission();
    if (date('j') == 5) gn_pt_tg(gn_pt_payout_text()); // payout ngày 5
}

// A5 — báo cáo booking nghi no-show (quá 1 ngày, vẫn 'pending', chưa check-in) để NHÂN VIÊN tự xử lý.
// KHÔNG tự đổi trạng thái (tránh sửa dữ liệu thật tự động). Nhân viên xác nhận trong wp-admin.
function gn_pt_noShowReport() {
    global $wpdb; $p = $wpdb->prefix . 'sao_';
    $rows = $wpdb->get_results("SELECT booking_code,customer_name,booking_date FROM {$p}bookings WHERE booking_date < DATE_SUB(CURDATE(),INTERVAL 1 DAY) AND booking_status='pending' AND qr_scanned=0 ORDER BY booking_date LIMIT 50");
    if (!$rows) return 0;
    $t = "🔁 <b>Nghi NO-SHOW</b> (" . count($rows) . " booking quá hạn vẫn 'chờ' & chưa check-in) — vào wp-admin xác nhận:\n";
    foreach ($rows as $r) $t .= "• {$r->booking_date}: {$r->customer_name} (#{$r->booking_code})\n";
    gn_pt_tg($t);
    return count($rows);
}

// G3 — tính hoa hồng cho booking có mã đối tác (notes chứa 'partner:CODE'), khớp partners.ref_code
function gn_pt_commission() {
    global $wpdb; $p = $wpdb->prefix . 'sao_';
    $rows = $wpdb->get_results("SELECT id,booking_code,total,notes FROM {$p}bookings WHERE booking_status IN ('checked_in','completed') AND notes LIKE '%partner:%' LIMIT 300");
    $log = get_option('gn_commission_log', []);
    $seen = array_column($log, 'bid');
    foreach ((array)$rows as $b) {
        if (in_array($b->id, $seen)) continue;
        if (!preg_match('/partner:([A-Za-z0-9_-]+)/', $b->notes, $m)) continue;
        $code = $m[1];
        $pt = $wpdb->get_row($wpdb->prepare("SELECT id,company,commission FROM {$p}partners WHERE ref_code=%s", $code));
        if (!$pt) continue;
        $com = round(((float)$b->total) * ((float)$pt->commission) / 100);
        $log[] = ['bid' => (int)$b->id, 'code' => $b->booking_code, 'partner' => $pt->company, 'ref' => $code, 'total' => (float)$b->total, 'pct' => (float)$pt->commission, 'commission' => $com, 'ts' => current_time('mysql')];
    }
    update_option('gn_commission_log', $log, false);
}

function gn_pt_payout_text() {
    $log = get_option('gn_commission_log', []);
    if (!$log) return "💸 <b>PAYOUT THÁNG</b>: chưa có hoa hồng nào.";
    $byp = [];
    foreach ($log as $r) { $byp[$r['partner']] = ($byp[$r['partner']] ?? 0) + $r['commission']; }
    $t = "💸 <b>PAYOUT THÁNG</b> (hoa hồng đối tác):\n";
    foreach ($byp as $name => $sum) $t .= "• {$name}: <b>" . number_format($sum) . "đ</b>\n";
    return $t;
}

// DEMO: tính hoa hồng mẫu trên dữ liệu giả + báo Telegram (không đụng dữ liệu thật)
function gn_pt_demo() {
    $total = 5000000; $pct = 10; $com = $total * $pct / 100;
    $msg = "🧪 <b>[DEMO] Partner/Pipeline đã hoạt động</b>\n\n"
         . "Ví dụ tính hoa hồng:\n• Đối tác: <b>Tour ABC</b> (Bronze " . $pct . "%)\n• Booking #GN-DEMO-001: " . number_format($total) . "đ\n• Hoa hồng: <b>" . number_format($com) . "đ</b>\n\n"
         . "Khi bật: A5 tự đánh dấu no-show booking quá hạn; G3 tự tính hoa hồng theo mã ref; ngày 5 gửi báo cáo payout.";
    gn_pt_tg($msg);
    update_option('gn_pt_demo_tag', GN_PT_DEMO, false);
}
