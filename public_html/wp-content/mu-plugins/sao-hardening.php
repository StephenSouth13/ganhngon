<?php
/**
 * Plugin Name: SAO PHU QUOC Hardening
 * Description: Patches CSRF/overbooking/SQLi holes in theme booking-engine.php + injects GA4/Meta Pixel + security headers. MU-plugin = auto-loaded, cannot be deactivated by accident.
 * Version: 1.0.0
 * Author: Audit run 2026-06-11
 *
 * Place at: /wp-content/mu-plugins/sao-hardening.php
 * (mu-plugins dir auto-loaded before regular plugins; if dir doesn't exist, create it.)
 */
if (!defined('ABSPATH')) exit;

// =================================================================
// 1. Security headers (defense in depth — also at .htaccess level)
// =================================================================
add_action('send_headers', function () {
    if (is_admin()) return;
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
        }
    }
});

// =================================================================
// 2. GA4 + Meta Pixel + Search Console (read IDs from wp_options)
// Set with: wp option update sao_ga4_id "G-XXXXXX"
//           wp option update sao_meta_pixel_id "1234567"
//           wp option update sao_gsc_verification "ABC..."
// Or via Tools → Options Editor / WP CLI.
// =================================================================
add_action('wp_head', function () {
    $ga4   = ''; // 2026-06-20: tắt thẻ GA cũ G-CX5G613B9G (của Sao Phu Quoc) để ganhngon không đổ data sang; GA4 Gánh Ngon G-9EL1C2FJGF gắn ở mu-plugin gn-analytics.php
    $pixel = get_option('sao_meta_pixel_id', '');
    $gsc   = get_option('sao_gsc_verification', '');
    $ttq   = get_option('sao_tiktok_pixel_id', '');

    if ($gsc) {
        echo '<meta name="google-site-verification" content="' . esc_attr($gsc) . '">' . "\n";
    }

    if ($ga4) {
        echo '<!-- GA4 -->' . "\n";
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($ga4) . '"></script>' . "\n";
        echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());gtag(\'config\',' . wp_json_encode($ga4) . ');window.saoTrack=function(e,p){try{gtag(\'event\',e,p||{});}catch(e){}};</script>' . "\n";
    } else {
        echo '<script>window.saoTrack=function(){};</script>' . "\n";
    }

    if ($pixel) {
        echo '<!-- Meta Pixel -->' . "\n";
        echo "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', " . wp_json_encode($pixel) . ");fbq('track','PageView');</script>" . "\n";
        echo '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . esc_attr($pixel) . '&ev=PageView&noscript=1"/></noscript>' . "\n";
    }

    if ($ttq) {
        echo '<!-- TikTok Pixel -->' . "\n";
        echo "<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load(" . wp_json_encode($ttq) . ");ttq.page();}(window,document,'ttq');</script>" . "\n";
    }

    // Always expose CSRF nonce for booking
    echo '<meta name="sao-booking-nonce" content="' . esc_attr(wp_create_nonce('sao_booking')) . '">' . "\n";
}, 1);

// =================================================================
// 3. Override theme's vulnerable AJAX handlers
// Theme registers them in SAO_Booking::hooks() with wp_ajax_nopriv_ for ALL endpoints.
// We let theme register first, then remove + replace.
// =================================================================
add_action('init', function () {
    if (!class_exists('SAO_Booking')) return;
    $ins = SAO_Booking::init();

    // Sensitive endpoints — REMOVE public access entirely
    remove_action('wp_ajax_nopriv_sao_check_qr',        [$ins, 'ajax_check_qr']);
    remove_action('wp_ajax_nopriv_sao_dashboard_data',  [$ins, 'ajax_dashboard_data']);

    // Booking creation — replace with hardened version
    remove_action('wp_ajax_sao_create_booking',         [$ins, 'ajax_create_booking']);
    remove_action('wp_ajax_nopriv_sao_create_booking',  [$ins, 'ajax_create_booking']);
    add_action('wp_ajax_sao_create_booking',            'sao_safe_create_booking');
    add_action('wp_ajax_nopriv_sao_create_booking',     'sao_safe_create_booking');

    // Inventory listing — replace (was vulnerable to SQLi)
    remove_action('wp_ajax_sao_get_inventory',          [$ins, 'ajax_get_inventory']);
    remove_action('wp_ajax_nopriv_sao_get_inventory',   [$ins, 'ajax_get_inventory']);
    add_action('wp_ajax_sao_get_inventory',             'sao_safe_get_inventory');
    add_action('wp_ajax_nopriv_sao_get_inventory',      'sao_safe_get_inventory');

    // QR check — admin only, with nonce
    remove_action('wp_ajax_sao_check_qr',               [$ins, 'ajax_check_qr']);
    add_action('wp_ajax_sao_check_qr',                  'sao_safe_check_qr');

    // Dashboard — admin only, with nonce, prepared queries
    remove_action('wp_ajax_sao_dashboard_data',         [$ins, 'ajax_dashboard_data']);
    add_action('wp_ajax_sao_dashboard_data',            'sao_safe_dashboard_data');

    // VNPay return — verify signature
    remove_action('wp_ajax_sao_vnpay_return',           [$ins, 'ajax_vnpay_return']);
    remove_action('wp_ajax_nopriv_sao_vnpay_return',    [$ins, 'ajax_vnpay_return']);
    // The theme's VNPay return is also hooked via init() on URL match — see sao_vnpay_redirect in functions.php.
    // We patch that flow separately below.
}, 50);

// =================================================================
// Hardened AJAX handlers
// =================================================================

function sao_safe_rate_limit(string $bucket, int $max = 20, int $window = 60): bool {
    $ip = '';
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) { $ip = trim(explode(',', $_SERVER[$k])[0]); break; }
    }
    $ip = filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
    $key = md5($bucket . '|' . $ip);
    $dir = WP_CONTENT_DIR . '/sao-ratelimit';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $file = $dir . '/' . substr($key, 0, 2) . '/' . $key . '.json';
    $sub = dirname($file);
    if (!is_dir($sub)) @mkdir($sub, 0750, true);
    $now = time();
    $cutoff = $now - $window;
    $hits = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $hits = $raw ? (json_decode($raw, true) ?: []) : [];
        $hits = array_filter($hits, fn($t) => $t >= $cutoff);
    }
    if (count($hits) >= $max) return false;
    $hits[] = $now;
    @file_put_contents($file, json_encode(array_values($hits)), LOCK_EX);
    return true;
}

function sao_safe_get_inventory() {
    if (!sao_safe_rate_limit('inventory', 60, 60)) {
        wp_send_json_error(['message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'], 429);
    }
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';

    $date = sanitize_text_field($_GET['date'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(['message' => 'Ngày không hợp lệ.']);
    }
    $brand = sanitize_text_field($_GET['brand'] ?? '');

    $brandClause = $brand ? $wpdb->prepare(' AND pr.brand = %s', $brand) : '';
    $prods = $wpdb->get_results($wpdb->prepare(
        "SELECT pr.id, pr.name, pr.slug, pr.type, pr.brand, pr.base_price, pr.short_desc,
                COALESCE(inv.total_capacity, pr.capacity) AS cap,
                COALESCE(inv.reserved, 0) AS sold,
                COALESCE(inv.status, 'open') AS slot_status
         FROM {$p}products pr
         LEFT JOIN {$p}inventory inv ON pr.id = inv.product_id AND inv.booking_date = %s
         WHERE pr.status = 'active' $brandClause
         ORDER BY pr.id",
        $date
    ));
    $out = [];
    foreach ($prods as $pr) {
        $avail = max(0, (int)$pr->cap - (int)$pr->sold);
        $out[] = [
            'id'              => (int)$pr->id,
            'name'            => $pr->name,
            'price'           => (int)$pr->base_price,
            'price_formatted' => number_format((int)$pr->base_price, 0, ',', '.') . 'đ',
            'available'       => $avail,
            'type'            => $pr->type,
        ];
    }
    wp_send_json_success(['products' => $out, 'date' => $date, 'brand' => $brand]);
}

function sao_safe_create_booking() {
    // Honeypot
    if (!empty($_POST['website_hp'])) {
        wp_send_json_success(['booking_id' => 0, 'code' => '', 'message' => 'OK']);
    }
    // Rate limit per IP (5 attempts / minute, 20 / hour)
    if (!sao_safe_rate_limit('create_booking_min', 5, 60) ||
        !sao_safe_rate_limit('create_booking_hour', 20, 3600)) {
        wp_send_json_error(['message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau ít phút.'], 429);
    }
    // CSRF nonce — front-end JS reads from <meta name="sao-booking-nonce">
    $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? '';
    if (!$nonce || !wp_verify_nonce($nonce, 'sao_booking')) {
        wp_send_json_error(['message' => 'Phiên làm việc đã hết hạn. Tải lại trang.'], 403);
    }

    global $wpdb;
    $p = $wpdb->prefix . 'sao_';

    // Validate inputs
    $name   = sanitize_text_field($_POST['name']   ?? '');
    $email  = sanitize_email($_POST['email']       ?? '');
    $phone  = sanitize_text_field($_POST['phone']  ?? '');
    $date   = sanitize_text_field($_POST['date']   ?? '');
    $guests = (int)($_POST['guests'] ?? 0);
    $gtype  = sanitize_text_field($_POST['gtype']  ?? 'individual');
    $brand  = sanitize_text_field($_POST['brand']  ?? 'bai_sao');
    $notes  = sanitize_textarea_field($_POST['notes'] ?? '');
    $source = sanitize_text_field($_POST['source'] ?? 'web');

    if ($name === '' || $phone === '' || $date === '' || $guests < 1) {
        wp_send_json_error(['message' => 'Vui lòng nhập đầy đủ họ tên, số điện thoại, ngày đi và số khách.']);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_send_json_error(['message' => 'Ngày đi không đúng định dạng.']);
    }
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        wp_send_json_error(['message' => 'Không thể đặt cho ngày đã qua.']);
    }
    if ($email !== '' && !is_email($email)) {
        wp_send_json_error(['message' => 'Email không hợp lệ.']);
    }
    $phoneNorm = preg_replace('/[\s\-\.]/', '', $phone);
    if (!preg_match('/^(0\d{9,10}|\+84\d{9,10})$/', $phoneNorm)) {
        wp_send_json_error(['message' => 'Số điện thoại không hợp lệ.']);
    }

    $itemsJson = stripslashes($_POST['products'] ?? '[]');
    $items = json_decode($itemsJson, true);
    if (!is_array($items) || empty($items)) {
        wp_send_json_error(['message' => 'Chưa chọn sản phẩm nào.']);
    }

    $bookingCode = 'SAO' . date('Ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    $wpdb->query('START TRANSACTION');
    try {
        $resolved = [];
        $total = 0;
        foreach ($items as $line) {
            $pid = (int)($line['id'] ?? 0);
            $qty = max(1, (int)($line['qty'] ?? 1));
            if ($pid <= 0) continue;

            $prod = $wpdb->get_row($wpdb->prepare(
                "SELECT id, name, base_price, capacity FROM {$p}products WHERE id = %d AND status = 'active'",
                $pid
            ));
            if (!$prod) throw new Exception("Sản phẩm #$pid không tồn tại.");

            $inv = $wpdb->get_row($wpdb->prepare(
                "SELECT id, total_capacity, reserved, status FROM {$p}inventory
                 WHERE product_id = %d AND booking_date = %s LIMIT 1 FOR UPDATE",
                $prod->id, $date
            ));

            if ($inv) {
                if ($inv->status !== 'open' && $inv->status !== '') {
                    throw new Exception("Sản phẩm '{$prod->name}' không nhận đặt cho ngày này.");
                }
                $avail = (int)$inv->total_capacity - (int)$inv->reserved;
                if ($avail < $qty) {
                    throw new Exception("Sản phẩm '{$prod->name}' chỉ còn $avail chỗ.");
                }
                $wpdb->update($p . 'inventory',
                    ['reserved' => (int)$inv->reserved + $qty],
                    ['id' => $inv->id]
                );
            } else {
                $cap = (int)$prod->capacity ?: 100;
                if ($cap < $qty) throw new Exception("Sản phẩm '{$prod->name}' không đủ chỗ.");
                $wpdb->insert($p . 'inventory', [
                    'product_id'     => $prod->id,
                    'booking_date'   => $date,
                    'total_capacity' => $cap,
                    'reserved'       => $qty,
                    'status'         => 'open',
                ]);
            }

            $lineTotal = (int)$prod->base_price * $qty;
            $total += $lineTotal;
            $resolved[] = [
                'product_id' => $prod->id,
                'name'       => $prod->name,
                'qty'        => $qty,
                'unit_price' => (int)$prod->base_price,
                'total'      => $lineTotal,
            ];
        }
        if (empty($resolved)) throw new Exception("Không có sản phẩm hợp lệ.");

        // Pricing: weekend +20%, early bird (≥30d) -10%
        $dow = (int)date('N', strtotime($date));
        $advance = (strtotime($date) - strtotime(date('Y-m-d'))) / 86400;
        $mult = 1.0;
        if ($dow >= 6) $mult *= 1.20;
        if ($advance >= 30) $mult *= 0.90;
        $totalAdj = (int) round($total * $mult);

        // Apply coupon code if provided
        $couponCode = strtoupper(sanitize_text_field($_POST['coupon_code'] ?? ''));
        $couponResult = ['discount' => 0, 'new_total' => $totalAdj, 'message' => ''];
        if ($couponCode && function_exists('sao_apply_coupon')) {
            $couponResult = sao_apply_coupon($couponCode, $totalAdj);
            $totalAdj = $couponResult['new_total'];
        }

        $deposit  = (int) round($totalAdj * 0.30);

        $wpdb->insert($p . 'bookings', [
            'booking_code'   => $bookingCode,
            'customer_name'  => $name,
            'customer_email' => $email,
            'customer_phone' => $phoneNorm,
            'booking_date'   => $date,
            'guests'         => $guests,
            'guest_type'     => $gtype,
            'subtotal'       => $total,
            'total'          => $totalAdj,
            'deposit'        => $deposit,
            'paid'           => 0,
            'booking_status' => 'pending',
            'payment_status' => 'pending',
            'source'         => $source,
            'notes'          => $notes . ($brand ? " | brand:$brand" : ''),
        ]);
        $bid = (int)$wpdb->insert_id;

        foreach ($resolved as $it) {
            $wpdb->insert($p . 'booking_items', [
                'booking_id'   => $bid,
                'product_id'   => $it['product_id'],
                'product_name' => $it['name'],
                'qty'          => $it['qty'],
                'unit_price'   => $it['unit_price'],
                'total_price'  => $it['total'],
            ]);
        }

        $wpdb->query('COMMIT');

        // Notify CRM / email (theme hooks listen)
        do_action('sao_booking_created', $bid, [
            'name' => $name, 'email' => $email, 'phone' => $phoneNorm,
            'brand' => $brand, 'gtype' => $gtype, 'date' => $date,
            'code' => $bookingCode, 'total' => $totalAdj,
            'coupon_code' => $couponCode,
        ]);

        wp_send_json_success([
            'booking_id'  => $bid,
            'code'        => $bookingCode,
            'subtotal'    => $total,
            'total'       => $totalAdj,
            'deposit'     => $deposit,
            'qr'          => '',
            'pricing'     => ['weekend' => $dow>=6, 'early_bird' => $advance>=30, 'mult' => $mult],
            'coupon'      => $couponResult,
            'payment_url' => home_url('/sao-pay/?bid=' . $bid),
            'message'     => 'Đặt chỗ thành công.' . ($couponResult['discount'] > 0 ? ' Đã áp dụng ' . $couponResult['code'] . ' (-' . number_format($couponResult['discount']) . 'đ).' : ''),
        ]);
    } catch (Throwable $e) {
        $wpdb->query('ROLLBACK');
        $debug = (defined('WP_DEBUG') && WP_DEBUG);
        wp_send_json_error(['message' => $debug ? $e->getMessage() : 'Có lỗi khi đặt chỗ. Vui lòng thử lại hoặc gọi hotline.']);
    }
}

function sao_safe_check_qr() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Bạn không có quyền.'], 403);
    }
    $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? '';
    if (!$nonce || !wp_verify_nonce($nonce, 'sao_qr')) {
        wp_send_json_error(['message' => 'CSRF token không hợp lệ.'], 403);
    }
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $code = sanitize_text_field($_POST['code'] ?? '');
    if ($code === '') wp_send_json_error(['message' => 'Mã booking trống.']);
    $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}bookings WHERE booking_code = %s", $code));
    if (!$b) wp_send_json_error(['message' => 'Không tìm thấy booking.']);
    if ($b->qr_scanned) {
        wp_send_json_error(['message' => 'QR đã sử dụng lúc ' . esc_html($b->qr_scanned_at ?? '—'), 'booking' => $b]);
    }
    if ($b->booking_status === 'cancelled') {
        wp_send_json_error(['message' => 'Booking đã hủy.']);
    }
    if (!in_array($b->payment_status, ['paid','deposit_paid'], true)) {
        wp_send_json_error(['message' => 'Booking chưa thanh toán cọc.']);
    }
    $wpdb->update($p . 'bookings', [
        'qr_scanned'    => 1,
        'qr_scanned_at' => current_time('mysql'),
        'booking_status'=> 'checked_in',
    ], ['id' => $b->id]);
    do_action('sao_booking_checked_in', $b->id, $b);
    wp_send_json_success(['booking' => $b, 'message' => 'Check-in thành công - ' . esc_html($b->customer_name)]);
}

function sao_safe_dashboard_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Forbidden'], 403);
    }
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $today = date('Y-m-d');
    $bookings = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date = %s", $today));
    $revenue  = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date = %s AND payment_status IN ('paid','deposit_paid')", $today));
    $checked  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date = %s AND qr_scanned = 1", $today));
    $cap      = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_capacity),0) FROM {$p}inventory WHERE booking_date = %s", $today));
    $sold     = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(reserved),0) FROM {$p}inventory WHERE booking_date = %s", $today));
    $noShow   = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date < %s AND booking_status NOT IN ('checked_in','cancelled','refunded','no_show')", $today));
    wp_send_json_success([
        'bookings'    => $bookings,
        'revenue'     => $revenue,
        'checked_in'  => $checked,
        'capacity'    => $cap,
        'sold'        => $sold,
        'no_show_risk'=> $noShow,
        'fill_rate'   => $cap > 0 ? round($sold / $cap * 100, 1) : 0,
    ]);
}

// =================================================================
// 4. VNPay return signature verification
// Theme's functions.php hooks `sao_vnpay_redirect` on init that handles
// /sao-vnpay-return/ — but it does NOT verify vnp_SecureHash. An attacker
// could craft a URL with vnp_ResponseCode=00 and mark a booking as paid.
// We intercept earlier and verify the signature.
// =================================================================
add_action('init', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/sao-vnpay-return') === false) return;

    $hashSecret = get_option('sao_vnpay_hash_secret', 'SAOPHUQUOCVNP@2026');
    if ($hashSecret === 'SAOPHUQUOCVNP@2026') {
        // Default/placeholder secret — refuse to process
        wp_die('VNPay merchant chưa được cấu hình. Liên hệ admin.', 'Payment error', ['response' => 503]);
    }

    // Reconstruct signature from query params (excluding the hash itself)
    $params = $_GET;
    $secureHash = $params['vnp_SecureHash'] ?? '';
    unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
    ksort($params);
    $hashdata = '';
    foreach ($params as $k => $v) {
        if (strpos($k, 'vnp_') === 0) {
            $hashdata .= ($hashdata ? '&' : '') . $k . '=' . $v;
        }
    }
    $expect = hash_hmac('sha512', $hashdata, $hashSecret);
    if (!hash_equals($expect, strtolower((string)$secureHash))) {
        wp_die('Sai chữ ký giao dịch. Liên hệ admin.', 'Payment error', ['response' => 400]);
    }

    // Signature valid — let theme handler proceed (it'll update DB). Or process here.
    // For safety, mark request as verified so theme can trust it:
    $_REQUEST['_sao_vnpay_verified'] = 1;
}, 1);

// =================================================================
// 5. Disable wp_ajax_nopriv_ for any sao_* action we don't explicitly want public
// Belt-and-suspenders: even if theme adds new endpoints, this catches them.
// =================================================================
add_action('plugins_loaded', function () {
    $publicOk = ['sao_get_inventory', 'sao_create_booking', 'sao_vnpay_return'];
    global $wp_filter;
    foreach ($wp_filter as $tag => $filter) {
        if (strpos($tag, 'wp_ajax_nopriv_sao_') !== 0) continue;
        $action = substr($tag, strlen('wp_ajax_nopriv_'));
        if (!in_array($action, $publicOk, true)) {
            $wp_filter[$tag] = new WP_Hook(); // wipe
        }
    }
}, 99);

// =================================================================
// 6. Cleanup helper: schedule rate-limit file pruning daily
// =================================================================
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('sao_cleanup_ratelimit')) {
        wp_schedule_event(time(), 'daily', 'sao_cleanup_ratelimit');
    }
});
add_action('sao_cleanup_ratelimit', function () {
    $dir = WP_CONTENT_DIR . '/sao-ratelimit';
    if (!is_dir($dir)) return;
    $cutoff = time() - 86400;
    $files = glob($dir . '/*/*.json');
    foreach ($files as $f) {
        if (filemtime($f) < $cutoff) @unlink($f);
    }
});

// =================================================================
// 7. Inject CSRF nonce + honeypot into AJAX requests via fetch monkey-patch
// We don't modify the theme files. Instead, we wrap window.fetch so any POST
// to admin-ajax.php with action=sao_* auto-includes the nonce + honeypot check.
// =================================================================
add_action('wp_footer', function () {
    $nonce = wp_create_nonce('sao_booking');
    ?>
    <script id="sao-hardening-patch">
    (function(){
        var SAO_NONCE = <?= wp_json_encode($nonce) ?>;
        var origFetch = window.fetch;
        window.fetch = function(input, init){
            try {
                var url = (typeof input === 'string') ? input : (input && input.url) || '';
                var method = (init && init.method) ? init.method.toUpperCase() : 'GET';
                if (method === 'POST' && url.indexOf('admin-ajax.php') >= 0) {
                    // Inject nonce + check honeypot
                    if (init.body) {
                        if (typeof init.body === 'string') {
                            if (init.body.indexOf('action=sao_') >= 0) {
                                if (init.body.indexOf('nonce=') < 0) {
                                    init.body += '&nonce=' + encodeURIComponent(SAO_NONCE);
                                }
                            }
                        } else if (init.body instanceof URLSearchParams) {
                            if ((init.body.get('action') || '').indexOf('sao_') === 0) {
                                if (!init.body.get('nonce')) init.body.append('nonce', SAO_NONCE);
                                if (!init.body.get('website_hp')) init.body.append('website_hp', '');
                            }
                        } else if (init.body instanceof FormData) {
                            if ((init.body.get('action') || '').indexOf('sao_') === 0) {
                                if (!init.body.get('nonce')) init.body.append('nonce', SAO_NONCE);
                                if (!init.body.get('website_hp')) init.body.append('website_hp', '');
                            }
                        }
                    }
                }
            } catch(e) { /* swallow */ }
            return origFetch(input, init);
        };
    })();
    </script>
    <?php
}, 999);

// =================================================================
// 8. Admin settings page — let CEO enter tracking/payment IDs via UI
// Access: WP admin → Settings → SAO Hardening
// =================================================================
add_action('admin_menu', function () {
    add_options_page(
        'SAO Hardening',
        'SAO Hardening',
        'manage_options',
        'sao-hardening',
        function () {
            if (!current_user_can('manage_options')) return;
            $fields = [
                'sao_ga4_id'              => ['GA4 Measurement ID',          'G-XXXXXXXXXX',     'analytics.google.com → Admin → Data Streams'],
                'sao_meta_pixel_id'       => ['Meta Pixel ID',               '1234567890',       'business.facebook.com → Events Manager'],
                'sao_tiktok_pixel_id'     => ['TikTok Pixel ID',             '',                 'TikTok Ads Manager → Assets → Events'],
                'sao_gsc_verification'    => ['Google Search Console code',  'ABCDEF...',        'search.google.com/search-console → HTML tag method'],
                'sao_vnpay_merchant_code' => ['VNPay TmnCode (live)',        'TMN12345',         'Email VNPay sau khi ký hợp đồng'],
                'sao_vnpay_hash_secret'   => ['VNPay Hash Secret (live)',    '',                 'Email VNPay — KHÔNG share với ai'],
                'sao_hotline'             => ['Hotline thật',                '02973999999',      'Hiển thị trên header + chatbot'],
                'sao_zalo'                => ['Zalo số',                     '0901234567',       'Link zalo.me/{number}'],
                'sao_whatsapp'            => ['WhatsApp số (E.164)',         '+84901234567',     'Link wa.me/{number}'],
                'sao_smtp_host'           => ['SMTP Host',                   'smtp.hostinger.com', ''],
                'sao_smtp_port'           => ['SMTP Port',                   '587',              'TLS=587, SSL=465'],
                'sao_smtp_user'           => ['SMTP User',                   'noreply@taichinhachau.com', ''],
                'sao_smtp_pass'           => ['SMTP Password',               '',                 'KHÔNG share — chỉ điền trên trang này'],
            ];

            // Handle save
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('sao_hardening_save')) {
                foreach ($fields as $key => $_) {
                    if (isset($_POST[$key])) {
                        update_option($key, sanitize_text_field($_POST[$key]));
                    }
                }
                update_option('sao_hardening_saved_at', current_time('mysql'));
                echo '<div class="notice notice-success is-dismissible"><p>✅ Đã lưu cấu hình. <strong>Reload trang frontend để xem tracking active.</strong></p></div>';
            }

            $savedAt = get_option('sao_hardening_saved_at', 'chưa lưu');
            ?>
            <div class="wrap">
                <h1>🛡️ SAO Hardening — Cấu hình tracking &amp; payment</h1>
                <p>Điền các ID/secret bên dưới. <strong>Mu-plugin sẽ tự inject vào site frontend.</strong></p>
                <p>Lần lưu gần nhất: <code><?= esc_html($savedAt) ?></code></p>

                <form method="POST" autocomplete="off">
                    <?php wp_nonce_field('sao_hardening_save'); ?>
                    <table class="form-table">
                        <?php foreach ($fields as $key => [$label, $placeholder, $hint]):
                            $val = get_option($key, '');
                            $isSecret = (str_contains($key, 'secret') || str_contains($key, 'pass'));
                            $inputType = $isSecret ? 'password' : 'text';
                            $displayVal = $isSecret && $val ? '••••••••' : $val;
                        ?>
                        <tr>
                            <th><label for="<?= esc_attr($key) ?>"><?= esc_html($label) ?></label></th>
                            <td>
                                <input type="<?= $inputType ?>" id="<?= esc_attr($key) ?>" name="<?= esc_attr($key) ?>"
                                       value="<?= esc_attr($isSecret && $val ? '' : $val) ?>"
                                       placeholder="<?= esc_attr($isSecret && $val ? '(đã có giá trị, để trống nếu không đổi)' : $placeholder) ?>"
                                       class="regular-text">
                                <?php if ($hint): ?><p class="description"><?= esc_html($hint) ?></p><?php endif; ?>
                                <?php if ($isSecret && $val): ?><p class="description" style="color:#888">✓ Đã lưu (ẩn vì là secret)</p><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                    <h2>Feature Flags</h2>
                    <table class="form-table">
                        <tr>
                            <th>VNPay Enabled</th>
                            <td><label><input type="checkbox" name="sao_vnpay_enabled" value="1" <?php checked(get_option('sao_vnpay_enabled') == '1'); ?>> Bật thanh toán VNPay (chỉ khi đã điền TmnCode + Hash Secret thật)</label></td>
                        </tr>
                        <tr>
                            <th>Google Translate fallback</th>
                            <td><label><input type="checkbox" name="sao_disable_gt" value="1" <?php checked(get_option('sao_disable_gt') == '1'); ?>> Tắt Google Translate widget (chỉ giữ VI/EN — chống dịch máy SEO)</label></td>
                        </tr>
                    </table>

                    <?php submit_button('💾 Lưu cấu hình'); ?>
                </form>

                <hr>
                <h2>📊 Trạng thái hiện tại</h2>
                <ul>
                    <li>GA4 active: <?= get_option('sao_ga4_id') ? '✅ ' . esc_html(get_option('sao_ga4_id')) : '❌ chưa cấu hình' ?></li>
                    <li>Meta Pixel active: <?= get_option('sao_meta_pixel_id') ? '✅ ' . esc_html(get_option('sao_meta_pixel_id')) : '❌ chưa cấu hình' ?></li>
                    <li>Search Console verification: <?= get_option('sao_gsc_verification') ? '✅ đã set' : '❌ chưa cấu hình' ?></li>
                    <li>VNPay live: <?= (get_option('sao_vnpay_enabled') && get_option('sao_vnpay_hash_secret') && get_option('sao_vnpay_hash_secret') !== 'SAOPHUQUOCVNP@2026') ? '✅ active' : '❌ chưa enable hoặc dùng placeholder' ?></li>
                    <li>SMTP: <?= get_option('sao_smtp_pass') ? '✅ password đã set' : '❌ chưa set password' ?></li>
                </ul>

                <hr>
                <h2>🧪 Smoke test</h2>
                <p>
                    <a href="<?= esc_url(home_url('/?sao_hardening_test=1')) ?>" target="_blank" class="button">Mở trang test</a>
                    <a href="<?= esc_url(home_url('/dat-cho/')) ?>" target="_blank" class="button">Mở trang đặt chỗ</a>
                </p>
            </div>
            <?php
        }
    );
});

add_action('init', function () {
    if (!isset($_GET['sao_hardening_test'])) return;
    if (!current_user_can('manage_options')) return;
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== SAO Hardening — Smoke test ===\n";
    echo "GA4 ID:         " . (get_option('sao_ga4_id') ?: '(empty)') . "\n";
    echo "Meta Pixel:     " . (get_option('sao_meta_pixel_id') ?: '(empty)') . "\n";
    echo "VNPay TmnCode:  " . (get_option('sao_vnpay_merchant_code') ?: '(empty)') . "\n";
    echo "VNPay enabled:  " . (get_option('sao_vnpay_enabled') ? 'yes' : 'no') . "\n";
    echo "Hotline:        " . (get_option('sao_hotline') ?: '(empty, fallback 090xxxxxxx in theme)') . "\n";
    echo "DB driver:      mysql\n";
    echo "Hardening time: " . (get_option('sao_hardening_saved_at') ?: '(not saved)') . "\n";
    echo "\n";
    echo "Tables (sao_*):\n";
    global $wpdb;
    $rows = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}sao%'", ARRAY_N);
    foreach ($rows as $r) echo "  " . $r[0] . "\n";
    echo "\n";
    echo "Bookings today: " . (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sao_bookings WHERE booking_date = CURDATE()") . "\n";
    echo "Inventory rows (today): " . (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sao_inventory WHERE booking_date = CURDATE()") . "\n";
    exit;
}, 1);

// =================================================================
// 9. Disable Google Translate widget when admin opts out
// =================================================================
add_action('init', function () {
    if (get_option('sao_disable_gt') == '1') {
        remove_action('wp_footer', 'sao_gt_widget', 99);
    }
}, 100);

// =================================================================
// 10. Disable broken plugin sao-booking-engine if present (missing shortcodes.php)
// =================================================================
add_filter('option_active_plugins', function ($plugins) {
    if (!is_array($plugins)) return $plugins;
    return array_values(array_filter($plugins, function ($p) {
        return true; /* [GN 2026-06-13] booking plugin re-enabled */
    }));
});

// =================================================================
// 11. Apply hotline/zalo/whatsapp overrides if set
// =================================================================
add_filter('the_content', function ($c) {
    static $h, $z, $w;
    if ($h === null) {
        $h = get_option('sao_hotline', '');
        $z = get_option('sao_zalo', '');
        $w = get_option('sao_whatsapp', '');
    }
    if ($h && str_contains($c, '02973999999')) $c = str_replace(['02973999999','0297 3 999 999'], [$h, $h], $c);
    if ($h && str_contains($c, '090xxxxxxx')) $c = str_replace('090xxxxxxx', $h, $c);
    return $c;
}, 20);

// =================================================================
// 12. SMTP configuration via phpmailer_init filter
// Reads sao_smtp_* options and configures wp_mail() automatically.
// =================================================================
add_action('phpmailer_init', function ($phpmailer) {
    $host = get_option('sao_smtp_host', '');
    $port = (int) get_option('sao_smtp_port', 587);
    $user = get_option('sao_smtp_user', '');
    $pass = get_option('sao_smtp_pass', '');
    $from_name = get_option('sao_smtp_from_name', 'SAO PHU QUOC');
    $from_email = get_option('sao_smtp_from_email', 'noreply@taichinhachau.com');

    if (!$host || !$user || !$pass) return; // Not configured yet — let WP fall back

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = $port;
    $phpmailer->Username = $user;
    $phpmailer->Password = $pass;
    $phpmailer->SMTPSecure = $port == 465 ? 'ssl' : 'tls';
    $phpmailer->From = $from_email;
    $phpmailer->FromName = $from_name;
});

add_filter('wp_mail_from', fn($e) => get_option('sao_smtp_from_email', $e));
add_filter('wp_mail_from_name', fn($n) => get_option('sao_smtp_from_name', $n));

// =================================================================
// 13. Email confirmation when booking is created
// Triggers on sao_booking_created action (fired by hardened ajax handler).
// Also fired by theme's vnpay_return on payment success — we wrap that.
// =================================================================
add_action('sao_booking_created', function ($booking_id, $data) {
    if (empty($data['email']) || !is_email($data['email'])) return;
    $site = get_option('blogname', 'SAO PHU QUOC');
    $hotline = get_option('sao_hotline', '');
    $code = $data['code'] ?? '';
    $total = number_format((int)($data['total'] ?? 0), 0, ',', '.') . 'đ';
    $deposit = number_format((int)round(($data['total'] ?? 0) * 0.3), 0, ',', '.') . 'đ';
    $payUrl = home_url('/sao-pay/?bid=' . (int)$booking_id);
    $bookingUrl = home_url('/?p=&booking=' . urlencode($code));

    $subject = "[$site] Xác nhận đặt chỗ #$code";
    $body = sao_render_booking_email([
        'name' => $data['name'] ?? '',
        'code' => $code,
        'brand' => $data['brand'] ?? '',
        'date' => $data['date'] ?? '',
        'guests' => $data['guests'] ?? 1,
        'total' => $total,
        'deposit' => $deposit,
        'pay_url' => $payUrl,
        'hotline' => $hotline,
        'is_paid' => false,
    ]);

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent = wp_mail($data['email'], $subject, $body, $headers);

    // Log to automation_log table if exists
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0, %s, %s, %s, %s)",
        'email_sent', $subject, "to: " . $data['email'], $sent ? 'success' : 'failed'
    ));
}, 10, 2);

if (!function_exists('sao_render_booking_email')) {
    function sao_render_booking_email(array $v): string {
        $brand_label = [
            'bai_sao' => 'Bãi Sao Phú Quốc',
            'ganh_ngon' => 'Gánh Ngon — Nhà hàng đặc sản Việt',
            'paghera_yacht' => 'Paghera Yacht',
            'combo' => 'Combo SAO PHU QUOC',
        ][$v['brand'] ?? ''] ?? 'SAO PHU QUOC';

        $qrUrl = sao_qr_image_url($v['code']);
        $paid_section = $v['is_paid']
            ? '<p style="background:#d4edda;color:#155724;padding:12px;border-radius:4px"><strong>✅ Đã thanh toán</strong></p>'
            : '<p style="background:#fff3cd;color:#856404;padding:12px;border-radius:4px"><strong>⏳ Vui lòng thanh toán cọc 30% để xác nhận:</strong> <a href="' . esc_url($v['pay_url']) . '">Thanh toán ngay →</a></p>';

        return '
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>SAO PHU QUOC — Xác nhận</title></head>
<body style="font-family:Arial,sans-serif;background:#F5F0E8;margin:0;padding:20px">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08)">
    <div style="background:linear-gradient(135deg,#1B2A4A,#0A7E8C);padding:24px;text-align:center">
        <h1 style="color:#fff;margin:0;font-size:24px">✦ SAO PHU QUOC</h1>
        <p style="color:#C9A96E;margin:6px 0 0">Beach. Taste. Yacht.</p>
    </div>
    <div style="padding:28px">
        <h2 style="color:#1B2A4A;margin-top:0">Xin chào ' . esc_html($v['name']) . ',</h2>
        <p>Cảm ơn bạn đã đặt chỗ tại SAO PHU QUOC. Dưới đây là chi tiết booking của bạn:</p>

        <table style="width:100%;border-collapse:collapse;margin:16px 0">
            <tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Mã booking:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;font-family:monospace;color:#C9A96E">' . esc_html($v['code']) . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Trải nghiệm:</strong></td><td style="padding:8px;border-bottom:1px solid #eee">' . esc_html($brand_label) . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Ngày đi:</strong></td><td style="padding:8px;border-bottom:1px solid #eee">' . esc_html($v['date']) . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Số khách:</strong></td><td style="padding:8px;border-bottom:1px solid #eee">' . (int)$v['guests'] . '</td></tr>
            <tr><td style="padding:8px;border-bottom:1px solid #eee"><strong>Tổng tiền:</strong></td><td style="padding:8px;border-bottom:1px solid #eee;font-weight:600">' . esc_html($v['total']) . '</td></tr>
            <tr><td style="padding:8px"><strong>Cọc cần trả:</strong></td><td style="padding:8px;color:#C9A96E;font-weight:600">' . esc_html($v['deposit']) . '</td></tr>
        </table>

        ' . $paid_section . '

        <div style="text-align:center;margin:24px 0">
            <p style="color:#718096;font-size:13px">Vé QR code (xuất trình tại cổng):</p>
            <img src="' . esc_url($qrUrl) . '" alt="QR ' . esc_attr($v['code']) . '" style="border:1px solid #eee;border-radius:4px;padding:8px;background:#fff">
            <p style="color:#1B2A4A;font-family:monospace;font-size:14px;margin:8px 0">' . esc_html($v['code']) . '</p>
        </div>

        <h3 style="color:#1B2A4A">Thông tin liên hệ</h3>
        <ul style="color:#555;line-height:1.7">
            <li>Hotline: <strong>' . esc_html($v['hotline'] ?: '0901234567') . '</strong></li>
            <li>Email: noreply@taichinhachau.com</li>
            <li>Website: <a href="' . esc_url(home_url('/')) . '">' . esc_html(parse_url(home_url('/'), PHP_URL_HOST)) . '</a></li>
        </ul>

        <h3 style="color:#1B2A4A">Lưu ý quan trọng</h3>
        <ul style="color:#555;font-size:14px">
            <li>Vui lòng đến đúng giờ và xuất trình QR tại cổng</li>
            <li>Mang theo CCCD/passport để check-in</li>
            <li>Hủy/đổi ngày trước 48h được hoàn cọc 100%</li>
            <li>Mưa to: SAO PHU QUOC sẽ liên hệ đổi ngày miễn phí</li>
        </ul>
    </div>
    <div style="background:#1B2A4A;color:#aaa;padding:16px;text-align:center;font-size:12px">
        © SAO PHU QUOC — Bãi Sao Phú Quốc<br>
        Bạn nhận email này vì đã đặt chỗ qua website. <a href="' . esc_url(home_url('/chinh-sach-bao-mat/')) . '" style="color:#C9A96E">Chính sách bảo mật</a>
    </div>
</div>
</body></html>';
    }
}

// =================================================================
// 14. QR code generator
// Returns URL to QR image. Uses quickchart.io (free, no API key) by default.
// Admin can swap to local generator by setting sao_qr_service option.
// =================================================================
if (!function_exists('sao_qr_image_url')) {
    function sao_qr_image_url(string $text, int $size = 200): string {
        $service = get_option('sao_qr_service', 'quickchart');
        $payload = home_url('/sao-checkin/?code=' . urlencode($text));
        switch ($service) {
            case 'qrserver':
                return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($payload);
            case 'local':
                return home_url('/?sao_qr=' . urlencode($text) . '&s=' . $size);
            case 'quickchart':
            default:
                return 'https://quickchart.io/qr?text=' . urlencode($payload) . '&size=' . $size . '&margin=2';
        }
    }
}

// Local QR generator endpoint (uses GD library + inline QR-PHP)
// Simple fallback: returns SVG QR that ENCODES THE URL ONLY (not actual QR matrix).
// Phones with QR reader will scan the URL printed below the SVG.
// For production, install endroid/qr-code via composer.
add_action('init', function () {
    if (!isset($_GET['sao_qr'])) return;
    $code = preg_replace('/[^A-Z0-9-]/i', '', $_GET['sao_qr']);
    if (!$code) { http_response_code(400); exit('Invalid code'); }

    // Verify code exists
    global $wpdb;
    $b = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sao_bookings WHERE booking_code = %s", $code));
    if (!$b) { http_response_code(404); exit('Booking not found'); }

    // Redirect to quickchart for image (since we don't have local QR library)
    $url = sao_qr_image_url($code, (int)($_GET['s'] ?? 200));
    if (strpos($url, '?sao_qr=') === false) {
        header('Location: ' . $url);
        exit;
    }
    // Fallback: plain text page with the URL
    header('Content-Type: text/plain; charset=utf-8');
    echo "QR data: " . home_url('/sao-checkin/?code=' . $code);
    exit;
}, 1);

// =================================================================
// 15. Partner portal hardening — CSRF + rate limit on register/login
// =================================================================
add_action('init', function () {
    if (!class_exists('SAO_Partner')) return;
    $ins = SAO_Partner::init();

    // Remove unsafe handlers
    remove_action('wp_ajax_sao_partner_register',         [$ins, 'ajax_partner_register']);
    remove_action('wp_ajax_nopriv_sao_partner_register',  [$ins, 'ajax_partner_register']);
    remove_action('wp_ajax_sao_partner_login',            [$ins, 'ajax_partner_login']);
    remove_action('wp_ajax_nopriv_sao_partner_login',     [$ins, 'ajax_partner_login']);

    // Add hardened versions
    add_action('wp_ajax_nopriv_sao_partner_register', 'sao_safe_partner_register');
    add_action('wp_ajax_sao_partner_register',        'sao_safe_partner_register');
    add_action('wp_ajax_nopriv_sao_partner_login',    'sao_safe_partner_login');
    add_action('wp_ajax_sao_partner_login',           'sao_safe_partner_login');
}, 60);

function sao_safe_partner_register() {
    if (!empty($_POST['website_hp'])) wp_send_json_success(['ok' => true]);
    if (!sao_safe_rate_limit('partner_reg', 3, 600)) wp_send_json_error(['message' => 'Đăng ký quá nhiều lần. Vui lòng thử lại sau 10 phút.'], 429);

    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $pw = $_POST['pw'] ?? '';
    $type = sanitize_text_field($_POST['type'] ?? 'agent');

    if (!$name || !$email || !$phone || !$pw) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin.']);
    }
    if (!is_email($email)) wp_send_json_error(['message' => 'Email không hợp lệ.']);
    if (strlen($pw) < 6) wp_send_json_error(['message' => 'Mật khẩu tối thiểu 6 ký tự.']);
    $phoneNorm = preg_replace('/[\s\-\.]/', '', $phone);
    if (!preg_match('/^(0\d{9,10}|\+84\d{9,10})$/', $phoneNorm)) {
        wp_send_json_error(['message' => 'Số điện thoại không hợp lệ.']);
    }

    if (email_exists($email) || username_exists($email)) {
        wp_send_json_error(['message' => 'Email đã được đăng ký. Vui lòng đăng nhập.']);
    }

    $uid = wp_create_user($email, $pw, $email);
    if (is_wp_error($uid)) wp_send_json_error(['message' => 'Lỗi đăng ký: ' . $uid->get_error_message()]);

    wp_update_user(['ID' => $uid, 'display_name' => $name, 'first_name' => $name, 'role' => 'subscriber']);
    update_user_meta($uid, 'phone', $phoneNorm);
    update_user_meta($uid, 'partner_type', $type);

    // Create affiliate record
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $ref = 'SAO' . strtoupper(substr(md5($email . time()), 0, 6));
    $wpdb->insert($p . 'affiliates', [
        'wp_user_id' => $uid,
        'full_name' => $name,
        'email' => $email,
        'phone' => $phoneNorm,
        'ref_code' => $ref,
        'commission' => 10,
        'status' => 'pending',
    ]);

    wp_send_json_success([
        'message' => 'Đăng ký thành công! Tài khoản đang chờ phê duyệt. Mã giới thiệu: ' . $ref,
        'ref_code' => $ref,
    ]);
}

function sao_safe_partner_login() {
    if (!empty($_POST['website_hp'])) wp_send_json_success(['ok' => true]);
    if (!sao_safe_rate_limit('partner_login', 5, 300)) wp_send_json_error(['message' => 'Đăng nhập sai nhiều lần. Đợi 5 phút.'], 429);

    $login = sanitize_text_field($_POST['login'] ?? '');
    $pw = $_POST['pw'] ?? '';
    if (!$login || !$pw) wp_send_json_error(['message' => 'Vui lòng nhập đầy đủ.']);

    $user = is_email($login) ? get_user_by('email', $login) : get_user_by('login', $login);
    if (!$user) {
        sao_safe_rate_limit('partner_login_failed:' . md5($login), 999, 300); // bump counter
        wp_send_json_error(['message' => 'Tài khoản không tồn tại.']);
    }
    if (!wp_check_password($pw, $user->user_pass, $user->ID)) {
        wp_send_json_error(['message' => 'Sai mật khẩu.']);
    }
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);
    wp_send_json_success(['message' => 'Đăng nhập thành công.', 'redirect' => home_url('/partner/')]);
}

// =================================================================
// 16. Add /sao-checkin/ endpoint that admins use to scan QR
// Maps to admin booking detail page
// =================================================================
add_action('init', function () {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/sao-checkin') !== 0) return;
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        auth_redirect();
    }
    $code = sanitize_text_field($_GET['code'] ?? '');
    if (!$code) {
        wp_die('Missing booking code', 'Check-in', ['response' => 400]);
    }
    global $wpdb;
    $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sao_bookings WHERE booking_code = %s", $code));
    if (!$b) wp_die('Booking not found: ' . esc_html($code), 'Check-in', ['response' => 404]);

    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sao_booking_items WHERE booking_id = %d", $b->id));
    echo '<!DOCTYPE html><html><head><title>Check-in ' . esc_html($code) . '</title>';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:Inter,Arial;padding:20px;background:#F5F0E8}h2{color:#1B2A4A}.card{background:#fff;padding:24px;border-radius:8px;max-width:500px;margin:20px auto;box-shadow:0 4px 12px rgba(0,0,0,.08)}.row{padding:8px 0;border-bottom:1px solid #eee}.btn{display:inline-block;padding:12px 24px;background:#C9A96E;color:#fff;text-decoration:none;border-radius:4px;margin-top:12px;font-weight:600}.btn-danger{background:#dc3545}</style></head><body>';
    echo '<div class="card">';
    echo '<h2>📱 Check-in #' . esc_html($code) . '</h2>';
    echo '<div class="row"><strong>Khách:</strong> ' . esc_html($b->customer_name) . '</div>';
    echo '<div class="row"><strong>SĐT:</strong> ' . esc_html($b->customer_phone) . '</div>';
    echo '<div class="row"><strong>Ngày:</strong> ' . esc_html($b->booking_date) . '</div>';
    echo '<div class="row"><strong>Số khách:</strong> ' . (int)$b->guests . '</div>';
    echo '<div class="row"><strong>Tổng:</strong> ' . number_format((int)$b->total, 0, ',', '.') . 'đ</div>';
    echo '<div class="row"><strong>TT thanh toán:</strong> ' . esc_html($b->payment_status) . '</div>';
    echo '<div class="row"><strong>TT booking:</strong> ' . esc_html($b->booking_status) . '</div>';
    if ($b->qr_scanned) {
        echo '<p style="background:#f8d7da;color:#721c24;padding:12px;border-radius:4px">⚠️ QR đã sử dụng lúc <strong>' . esc_html($b->qr_scanned_at ?? '—') . '</strong></p>';
    } else {
        // Mark scanned now
        $wpdb->update($wpdb->prefix . 'sao_bookings', [
            'qr_scanned' => 1,
            'qr_scanned_at' => current_time('mysql'),
            'booking_status' => 'checked_in',
        ], ['id' => $b->id]);
        echo '<p style="background:#d4edda;color:#155724;padding:12px;border-radius:4px;font-size:18px"><strong>✅ Check-in thành công!</strong></p>';
    }
    echo '<h3>Items:</h3>';
    foreach ($items as $it) {
        echo '<div class="row">' . esc_html($it->product_name) . ' × ' . (int)$it->qty . ' = ' . number_format((int)$it->total_price, 0, ',', '.') . 'đ</div>';
    }
    echo '<a href="' . esc_url(admin_url('admin.php?page=sao-bookings')) . '" class="btn">← Về Bookings</a>';
    echo '</div></body></html>';
    exit;
}, 1);

// =================================================================
// 17. Admin Dashboard — 18 KPI per spec mục 10
// Adds menu under "SAO Dashboard" with full operational metrics.
// =================================================================
add_action('admin_menu', function () {
    add_menu_page(
        'SAO Dashboard',
        '📊 SAO Dashboard',
        'manage_options',
        'sao-dashboard',
        'sao_render_dashboard',
        'dashicons-chart-area',
        3
    );
    add_submenu_page('sao-dashboard', 'KPI Tổng quan', 'KPI Tổng quan', 'manage_options', 'sao-dashboard', 'sao_render_dashboard');
    add_submenu_page('sao-dashboard', 'Bookings', 'Bookings', 'manage_options', 'sao-dashboard-bookings', 'sao_render_dashboard_bookings');
    add_submenu_page('sao-dashboard', 'Công suất', 'Công suất', 'manage_options', 'sao-dashboard-capacity', 'sao_render_dashboard_capacity');
    add_submenu_page('sao-dashboard', 'Partners', 'Partners', 'manage_options', 'sao-dashboard-partners', 'sao_render_dashboard_partners');
});

function sao_dashboard_kpi(): array {
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $month_start = date('Y-m-01');

    // 2. Booking — today, week, month
    $bk_today = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date = %s", $today));
    $bk_week = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date >= %s AND booking_date < DATE_ADD(%s, INTERVAL 7 DAY)", $week_start, $week_start));
    $bk_month = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date >= %s", $month_start));

    // 3. Doanh thu (only paid)
    $rev_today = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date = %s AND payment_status IN ('paid','deposit_paid')", $today));
    $rev_week  = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date >= %s AND payment_status IN ('paid','deposit_paid')", $week_start));
    $rev_month = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date >= %s AND payment_status IN ('paid','deposit_paid')", $month_start));

    // 5. Nguồn khách
    $sources = $wpdb->get_results($wpdb->prepare("SELECT COALESCE(source,'direct') src, COUNT(*) cnt FROM {$p}bookings WHERE booking_date >= %s GROUP BY src ORDER BY cnt DESC LIMIT 10", $month_start));

    // 6-8. Capacity today
    $cap_today_total = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_capacity),0) FROM {$p}inventory WHERE booking_date = %s", $today));
    $cap_today_sold  = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(reserved),0) FROM {$p}inventory WHERE booking_date = %s", $today));

    // Capacity next 7 days
    $cap_7days = $wpdb->get_results($wpdb->prepare(
        "SELECT booking_date, SUM(total_capacity) cap, SUM(reserved) sold FROM {$p}inventory
         WHERE booking_date >= %s AND booking_date < DATE_ADD(%s, INTERVAL 7 DAY)
         GROUP BY booking_date ORDER BY booking_date",
        $today, $today
    ));

    // 9. Doanh thu theo sản phẩm — top 10 (this month)
    $rev_by_product = $wpdb->get_results($wpdb->prepare(
        "SELECT bi.product_name, SUM(bi.qty) qty, SUM(bi.total_price) total FROM {$p}booking_items bi
         JOIN {$p}bookings b ON b.id = bi.booking_id
         WHERE b.booking_date >= %s AND b.payment_status IN ('paid','deposit_paid')
         GROUP BY bi.product_name ORDER BY total DESC LIMIT 10",
        $month_start
    ));

    // 12. No-show rate (past bookings not checked-in and not cancelled)
    $past_total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date < %s AND booking_date >= DATE_SUB(%s, INTERVAL 30 DAY) AND booking_status != 'cancelled'", $today, $today));
    $past_no_show = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date < %s AND booking_date >= DATE_SUB(%s, INTERVAL 30 DAY) AND booking_status NOT IN ('checked_in','cancelled','refunded')", $today, $today));
    $no_show_rate = $past_total > 0 ? round($past_no_show / $past_total * 100, 1) : 0;

    // 13. Cancel/refund rate
    $past_cancel = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date >= DATE_SUB(%s, INTERVAL 30 DAY) AND booking_status IN ('cancelled','refunded')", $today));
    $cancel_rate = $past_total > 0 ? round($past_cancel / ($past_total + $past_cancel) * 100, 1) : 0;

    // 14. Payment error rate (pending after 24h vs success)
    $pmt_total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}payments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $pmt_failed = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}payments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status IN ('failed','pending')");
    $pmt_err_rate = $pmt_total > 0 ? round($pmt_failed / $pmt_total * 100, 1) : 0;

    // 17. Top partners
    $top_partners = $wpdb->get_results($wpdb->prepare(
        "SELECT a.full_name, a.company_name, a.ref_code, a.commission,
                COUNT(b.id) bookings, COALESCE(SUM(b.total),0) revenue
         FROM {$p}affiliates a
         LEFT JOIN {$p}bookings b ON b.notes LIKE CONCAT('%%', a.ref_code, '%%')
         WHERE b.booking_date >= %s OR b.booking_date IS NULL
         GROUP BY a.id ORDER BY revenue DESC LIMIT 10",
        $month_start
    ));

    // 18. Lead response time (avg time between contact and first interaction)
    $lead_resp = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(TIMESTAMPDIFF(MINUTE, c.created_at, i.created_at))
         FROM {$wpdb->prefix}sao_crm_customers c
         JOIN {$wpdb->prefix}sao_crm_interactions i ON i.customer_id = c.id
         WHERE c.created_at >= %s", $month_start
    ));

    // Customer count
    $cust_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sao_crm_customers");
    $cust_new_month = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sao_crm_customers WHERE created_at >= %s", $month_start));

    return [
        'bk_today' => $bk_today, 'bk_week' => $bk_week, 'bk_month' => $bk_month,
        'rev_today' => $rev_today, 'rev_week' => $rev_week, 'rev_month' => $rev_month,
        'sources' => $sources,
        'cap_today_total' => $cap_today_total, 'cap_today_sold' => $cap_today_sold,
        'cap_7days' => $cap_7days,
        'rev_by_product' => $rev_by_product,
        'no_show_rate' => $no_show_rate, 'no_show_count' => $past_no_show,
        'cancel_rate' => $cancel_rate,
        'pmt_err_rate' => $pmt_err_rate,
        'top_partners' => $top_partners,
        'lead_resp_min' => $lead_resp ? round((float)$lead_resp, 1) : null,
        'cust_count' => $cust_count, 'cust_new_month' => $cust_new_month,
    ];
}

function sao_render_dashboard() {
    if (!current_user_can('manage_options')) return;
    $k = sao_dashboard_kpi();
    $today = date('Y-m-d');
    $ga4 = get_option('sao_ga4_id', '');
    ?>
    <div class="wrap">
        <h1>📊 SAO Dashboard — KPI Tổng quan</h1>
        <p>Ngày: <code><?= $today ?></code> · Cập nhật real-time mỗi lần load trang.</p>

        <h2>📈 Booking &amp; Doanh thu</h2>
        <div class="sao-kpi-grid">
            <div class="sao-kpi"><div class="v"><?= number_format($k['bk_today']) ?></div><div class="l">Booking hôm nay</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['bk_week']) ?></div><div class="l">Booking tuần này</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['bk_month']) ?></div><div class="l">Booking tháng này</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['rev_today']) ?>đ</div><div class="l">Doanh thu hôm nay</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['rev_week']) ?>đ</div><div class="l">Doanh thu tuần</div></div>
            <div class="sao-kpi sao-kpi-hi"><div class="v"><?= number_format($k['rev_month']) ?>đ</div><div class="l">Doanh thu tháng</div></div>
        </div>

        <h2>🎫 Công suất hôm nay</h2>
        <div class="sao-kpi-grid">
            <div class="sao-kpi"><div class="v"><?= number_format($k['cap_today_total']) ?></div><div class="l">Tổng sức chứa</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['cap_today_sold']) ?></div><div class="l">Đã bán</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['cap_today_total'] - $k['cap_today_sold']) ?></div><div class="l">Còn trống</div></div>
            <div class="sao-kpi"><div class="v"><?= $k['cap_today_total'] > 0 ? round($k['cap_today_sold'] / $k['cap_today_total'] * 100, 1) : 0 ?>%</div><div class="l">Fill rate</div></div>
        </div>

        <h2>📅 Công suất 7 ngày tới</h2>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Ngày</th><th>Tổng</th><th>Đã bán</th><th>Còn trống</th><th>Fill rate</th></tr></thead>
            <tbody>
            <?php foreach ($k['cap_7days'] as $r):
                $cap = (int)$r->cap; $sold = (int)$r->sold; $pct = $cap > 0 ? round($sold/$cap*100,1) : 0;
                $color = $pct > 80 ? '#dc3545' : ($pct > 50 ? '#fd7e14' : '#28a745');
            ?>
                <tr>
                    <td><?= esc_html($r->booking_date) ?></td>
                    <td><?= number_format($cap) ?></td>
                    <td><?= number_format($sold) ?></td>
                    <td><?= number_format($cap-$sold) ?></td>
                    <td><span style="color:<?= $color ?>;font-weight:600"><?= $pct ?>%</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2>💰 Top sản phẩm theo doanh thu (30 ngày)</h2>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Sản phẩm</th><th>Số lượng bán</th><th>Doanh thu</th></tr></thead>
            <tbody>
            <?php foreach ($k['rev_by_product'] as $r): ?>
                <tr><td><?= esc_html($r->product_name) ?></td><td><?= number_format((int)$r->qty) ?></td><td><?= number_format((int)$r->total) ?>đ</td></tr>
            <?php endforeach; ?>
            <?php if (empty($k['rev_by_product'])): ?><tr><td colspan="3" style="color:#888">Chưa có doanh thu trong 30 ngày qua</td></tr><?php endif; ?>
            </tbody>
        </table>

        <h2>🔔 Nguồn khách (tháng này)</h2>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Source</th><th>Booking</th></tr></thead>
            <tbody>
            <?php foreach ($k['sources'] as $s): ?>
                <tr><td><?= esc_html($s->src) ?></td><td><?= number_format((int)$s->cnt) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2>⚠️ Operational health</h2>
        <div class="sao-kpi-grid">
            <div class="sao-kpi <?= $k['no_show_rate']>15?'sao-kpi-warn':'' ?>"><div class="v"><?= $k['no_show_rate'] ?>%</div><div class="l">No-show rate (30 ngày)</div></div>
            <div class="sao-kpi <?= $k['cancel_rate']>20?'sao-kpi-warn':'' ?>"><div class="v"><?= $k['cancel_rate'] ?>%</div><div class="l">Cancel/refund rate</div></div>
            <div class="sao-kpi <?= $k['pmt_err_rate']>2?'sao-kpi-warn':'' ?>"><div class="v"><?= $k['pmt_err_rate'] ?>%</div><div class="l">Payment error rate</div></div>
            <div class="sao-kpi"><div class="v"><?= $k['lead_resp_min'] ?? '—' ?><?= $k['lead_resp_min'] ? ' phút' : '' ?></div><div class="l">Tốc độ phản hồi lead (avg)</div></div>
        </div>

        <h2>🤝 Top partners (tháng này)</h2>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Tên</th><th>Mã ref</th><th>Hoa hồng %</th><th>Booking</th><th>Doanh thu</th></tr></thead>
            <tbody>
            <?php foreach ($k['top_partners'] as $r): ?>
                <tr>
                    <td><?= esc_html($r->full_name ?: $r->company_name) ?></td>
                    <td><code><?= esc_html($r->ref_code) ?></code></td>
                    <td><?= esc_html($r->commission) ?>%</td>
                    <td><?= (int)$r->bookings ?></td>
                    <td><?= number_format((int)$r->revenue) ?>đ</td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($k['top_partners'])): ?><tr><td colspan="5" style="color:#888">Chưa có partner nào</td></tr><?php endif; ?>
            </tbody>
        </table>

        <h2>👥 Khách hàng</h2>
        <div class="sao-kpi-grid">
            <div class="sao-kpi"><div class="v"><?= number_format($k['cust_count']) ?></div><div class="l">Tổng khách hàng</div></div>
            <div class="sao-kpi"><div class="v"><?= number_format($k['cust_new_month']) ?></div><div class="l">Khách mới tháng này</div></div>
        </div>

        <h2>📊 Tracking (cần creds)</h2>
        <div class="notice notice-info inline" style="padding:10px;margin:10px 0">
            <strong>Traffic, Top landing, Top keyword, CAC, ROAS</strong> — sẽ hiển thị khi anh điền GA4 ID + GSC verification trong
            <a href="<?= admin_url('options-general.php?page=sao-hardening') ?>">SAO Hardening Settings</a>.
            Hiện tại GA4: <code><?= esc_html($ga4 ?: '(chưa cấu hình)') ?></code>
        </div>

        <hr>
        <p><a href="<?= admin_url('admin.php?page=sao-dashboard-bookings') ?>" class="button button-primary">📋 Bookings chi tiết</a>
           <a href="<?= admin_url('admin.php?page=sao-dashboard-capacity') ?>" class="button">🎫 Quản lý công suất</a>
           <a href="<?= admin_url('admin.php?page=sao-dashboard-partners') ?>" class="button">🤝 Partners</a></p>
    </div>

    <style>
    .sao-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:16px 0}
    .sao-kpi{background:#fff;padding:18px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.06);text-align:center;border-top:3px solid #C9A96E}
    .sao-kpi .v{font-size:1.7rem;font-weight:700;color:#1B2A4A;line-height:1.1}
    .sao-kpi .l{font-size:.82rem;color:#718096;margin-top:6px}
    .sao-kpi-hi{border-top-color:#0A7E8C;background:linear-gradient(135deg,#fff,#F5F0E8)}
    .sao-kpi-warn{border-top-color:#dc3545}
    .sao-kpi-warn .v{color:#dc3545}
    </style>
    <?php
}

function sao_render_dashboard_bookings() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $filter_status = sanitize_text_field($_GET['s'] ?? '');
    $where = $filter_status ? $wpdb->prepare(" WHERE booking_status = %s", $filter_status) : '';
    $bookings = $wpdb->get_results("SELECT * FROM {$p}bookings $where ORDER BY created_at DESC LIMIT 100");
    ?>
    <div class="wrap">
        <h1>📋 Bookings (100 mới nhất)</h1>
        <p>Filter:
            <a href="?page=sao-dashboard-bookings" class="button">All</a>
            <a href="?page=sao-dashboard-bookings&s=pending" class="button">Pending</a>
            <a href="?page=sao-dashboard-bookings&s=confirmed" class="button">Confirmed</a>
            <a href="?page=sao-dashboard-bookings&s=checked_in" class="button">Checked-in</a>
            <a href="?page=sao-dashboard-bookings&s=cancelled" class="button">Cancelled</a>
        </p>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Code</th><th>Khách</th><th>SĐT</th><th>Ngày</th><th>Khách</th><th>Tổng</th><th>Cọc</th><th>PT</th><th>BK</th><th>QR</th><th>Tạo</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><code><?= esc_html($b->booking_code) ?></code></td>
                    <td><?= esc_html($b->customer_name) ?></td>
                    <td><?= esc_html($b->customer_phone) ?></td>
                    <td><?= esc_html($b->booking_date) ?></td>
                    <td><?= (int)$b->guests ?></td>
                    <td><?= number_format((int)$b->total) ?>đ</td>
                    <td><?= number_format((int)$b->deposit) ?>đ</td>
                    <td><span class="sao-badge sao-<?= esc_attr($b->payment_status) ?>"><?= esc_html($b->payment_status) ?></span></td>
                    <td><span class="sao-badge sao-<?= esc_attr($b->booking_status) ?>"><?= esc_html($b->booking_status) ?></span></td>
                    <td><?= $b->qr_scanned ? '✅' : '⏳' ?></td>
                    <td><?= esc_html($b->created_at) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($bookings)): ?><tr><td colspan="11" style="color:#888">Không có booking</td></tr><?php endif; ?>
            </tbody>
        </table>
        <style>
        .sao-badge{padding:2px 8px;border-radius:10px;font-size:11px;background:#eee}
        .sao-badge.sao-paid,.sao-badge.sao-deposit_paid,.sao-badge.sao-confirmed,.sao-badge.sao-checked_in{background:#d4edda;color:#155724}
        .sao-badge.sao-pending,.sao-badge.sao-unpaid{background:#fff3cd;color:#856404}
        .sao-badge.sao-cancelled,.sao-badge.sao-refunded,.sao-badge.sao-failed{background:#f8d7da;color:#721c24}
        </style>
    </div>
    <?php
}

function sao_render_dashboard_capacity() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $days_ahead = max(7, min(60, (int)($_GET['d'] ?? 30)));
    $start = date('Y-m-d');
    $end = date('Y-m-d', strtotime("+$days_ahead days"));

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT i.booking_date, p.name, i.total_capacity, i.reserved, i.status
         FROM {$p}inventory i JOIN {$p}products p ON p.id = i.product_id
         WHERE i.booking_date >= %s AND i.booking_date < %s
         ORDER BY i.booking_date, p.id", $start, $end));
    ?>
    <div class="wrap">
        <h1>🎫 Quản lý công suất — <?= $days_ahead ?> ngày tới</h1>
        <p>
            <a href="?page=sao-dashboard-capacity&d=7" class="button">7 ngày</a>
            <a href="?page=sao-dashboard-capacity&d=14" class="button">14 ngày</a>
            <a href="?page=sao-dashboard-capacity&d=30" class="button">30 ngày</a>
            <a href="?page=sao-dashboard-capacity&d=60" class="button">60 ngày</a>
        </p>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>Ngày</th><th>Sản phẩm</th><th>Tổng</th><th>Đã bán</th><th>Còn</th><th>Fill</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
                $cap = (int)$r->total_capacity; $sold = (int)$r->reserved; $pct = $cap > 0 ? round($sold/$cap*100,1) : 0;
                $color = $pct > 80 ? '#dc3545' : ($pct > 50 ? '#fd7e14' : '#28a745');
            ?>
                <tr>
                    <td><?= esc_html($r->booking_date) ?></td>
                    <td><?= esc_html($r->name) ?></td>
                    <td><?= number_format($cap) ?></td>
                    <td><?= number_format($sold) ?></td>
                    <td><?= number_format($cap - $sold) ?></td>
                    <td><span style="color:<?= $color ?>;font-weight:600"><?= $pct ?>%</span></td>
                    <td><?= esc_html($r->status) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function sao_render_dashboard_partners() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $partners = $wpdb->get_results("SELECT a.*, COUNT(b.id) bk_count, COALESCE(SUM(b.total),0) bk_total FROM {$p}affiliates a LEFT JOIN {$p}bookings b ON b.notes LIKE CONCAT('%', a.ref_code, '%') GROUP BY a.id ORDER BY bk_total DESC");
    ?>
    <div class="wrap">
        <h1>🤝 Partners — Đại lý / Affiliate</h1>
        <table class="wp-list-table widefat striped">
            <thead><tr><th>ID</th><th>Tên</th><th>Email</th><th>Phone</th><th>Mã ref</th><th>Hoa hồng %</th><th>Bookings</th><th>Doanh thu</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($partners as $r): ?>
                <tr>
                    <td>#<?= (int)$r->id ?></td>
                    <td><?= esc_html($r->full_name) ?></td>
                    <td><?= esc_html($r->email) ?></td>
                    <td><?= esc_html($r->phone) ?></td>
                    <td><code><?= esc_html($r->ref_code) ?></code></td>
                    <td><?= esc_html($r->commission) ?>%</td>
                    <td><?= (int)$r->bk_count ?></td>
                    <td><?= number_format((int)$r->bk_total) ?>đ</td>
                    <td><span class="sao-badge sao-<?= esc_attr($r->status) ?>"><?= esc_html($r->status) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($partners)): ?><tr><td colspan="9" style="color:#888">Chưa có partner</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// =================================================================
// 18. CRM Automation — wp_cron workflows
// Daily run: T-1 reminder, T+1 NPS survey, T+30 upsell
// =================================================================
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('sao_crm_daily')) {
        wp_schedule_event(strtotime('tomorrow 08:00:00'), 'daily', 'sao_crm_daily');
    }
});
// Also schedule via init if not set (mu-plugins don't fire activation hook)
add_action('init', function () {
    if (!wp_next_scheduled('sao_crm_daily')) {
        wp_schedule_event(strtotime('tomorrow 08:00:00'), 'daily', 'sao_crm_daily');
    }
});

add_action('sao_crm_daily', 'sao_crm_run_workflows');

function sao_crm_run_workflows() {
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';

    // 1. T-1 REMINDER: bookings with visit_date = tomorrow, confirmed/deposit_paid, not yet reminded
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $reminders = $wpdb->get_results($wpdb->prepare(
        "SELECT b.* FROM {$p}bookings b
         WHERE b.booking_date = %s
         AND b.payment_status IN ('paid','deposit_paid')
         AND b.booking_status NOT IN ('cancelled','refunded')
         AND NOT EXISTS (
            SELECT 1 FROM {$wpdb->prefix}sao_crm_interactions i
            WHERE i.notes LIKE CONCAT('%%reminder:', b.booking_code, '%%')
         )", $tomorrow));

    foreach ($reminders as $b) {
        if (!$b->customer_email) continue;
        $body = sao_render_reminder_email([
            'name' => $b->customer_name,
            'code' => $b->booking_code,
            'date' => $b->booking_date,
            'guests' => $b->guests,
            'hotline' => get_option('sao_hotline', ''),
        ]);
        wp_mail($b->customer_email, "[SAO PHU QUOC] Nhắc lịch ngày mai #{$b->booking_code}",
                $body, ['Content-Type: text/html; charset=UTF-8']);
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'reminder_sent', 'T-1 reminder', "reminder:{$b->booking_code}", 'sent'));
    }

    // 2. T+1 NPS SURVEY: bookings yesterday, checked_in, not yet surveyed
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $surveys = $wpdb->get_results($wpdb->prepare(
        "SELECT b.* FROM {$p}bookings b
         WHERE b.booking_date = %s
         AND b.booking_status = 'checked_in'
         AND NOT EXISTS (
            SELECT 1 FROM {$wpdb->prefix}sao_crm_interactions i
            WHERE i.notes LIKE CONCAT('%%nps:', b.booking_code, '%%')
         )", $yesterday));

    foreach ($surveys as $b) {
        if (!$b->customer_email) continue;
        $body = sao_render_nps_email([
            'name' => $b->customer_name,
            'code' => $b->booking_code,
        ]);
        wp_mail($b->customer_email, "[SAO PHU QUOC] Cảm ơn anh/chị đã đến!",
                $body, ['Content-Type: text/html; charset=UTF-8']);
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'nps_sent', 'NPS survey', "nps:{$b->booking_code}", 'sent'));
    }

    // 3. T+30 UPSELL: bookings from 30 days ago, no return booking yet, not yet upsold
    $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
    $upsells = $wpdb->get_results($wpdb->prepare(
        "SELECT b.* FROM {$p}bookings b
         WHERE b.booking_date = %s
         AND b.booking_status = 'checked_in'
         AND NOT EXISTS (
            SELECT 1 FROM {$p}bookings b2
            WHERE b2.customer_phone = b.customer_phone
            AND b2.booking_date > b.booking_date
            AND b2.id != b.id
         )
         AND NOT EXISTS (
            SELECT 1 FROM {$wpdb->prefix}sao_crm_interactions i
            WHERE i.notes LIKE CONCAT('%%upsell:', b.booking_code, '%%')
         )", $thirty_days_ago));

    foreach ($upsells as $b) {
        if (!$b->customer_email) continue;
        $body = sao_render_upsell_email(['name' => $b->customer_name]);
        wp_mail($b->customer_email, "[SAO PHU QUOC] Ưu đãi 10% cho khách quay lại",
                $body, ['Content-Type: text/html; charset=UTF-8']);
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'upsell_sent', 'T+30 upsell', "upsell:{$b->booking_code}", 'sent'));
    }

    // Log run
    update_option('sao_crm_last_run', current_time('mysql'));
    update_option('sao_crm_last_counts', json_encode([
        'reminders' => count($reminders),
        'nps' => count($surveys),
        'upsells' => count($upsells),
    ]));
}

function sao_render_reminder_email(array $v): string {
    return '<div style="font-family:Arial;max-width:600px;margin:0 auto;padding:24px;background:#F5F0E8">
<div style="background:#fff;padding:28px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.06)">
<h2 style="color:#1B2A4A">🌴 Hẹn gặp anh/chị ngày mai!</h2>
<p>Xin chào ' . esc_html($v['name']) . ',</p>
<p>Booking <strong>#' . esc_html($v['code']) . '</strong> của anh/chị đã sẵn sàng cho ngày mai (<strong>' . esc_html($v['date']) . '</strong>).</p>
<h3>Chuẩn bị checklist:</h3>
<ul>
<li>✅ Mang theo CCCD / passport để check-in</li>
<li>✅ Xuất trình QR code (trong email xác nhận)</li>
<li>✅ Kem chống nắng SPF 50+</li>
<li>✅ Đồ tắm + khăn (resort/khách sạn có thể cho mượn)</li>
<li>✅ Mặc đồ thoáng, đi giày dép thoải mái</li>
</ul>
<h3>Giờ đến lý tưởng:</h3>
<p>8:00 - 10:00 sáng để có ghế đẹp, ít người. Mang theo mũ rộng vành.</p>
<h3>Có gì thắc mắc?</h3>
<p>Gọi hotline <strong>' . esc_html($v['hotline'] ?: '0901234567') . '</strong> hoặc reply email này.</p>
<p style="margin-top:24px;color:#718096;font-size:13px">— Team SAO PHU QUOC</p>
</div></div>';
}

function sao_render_nps_email(array $v): string {
    $survey = home_url('/?nps=' . urlencode($v['code']));
    return '<div style="font-family:Arial;max-width:600px;margin:0 auto;padding:24px;background:#F5F0E8">
<div style="background:#fff;padding:28px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.06)">
<h2 style="color:#1B2A4A">Cảm ơn anh/chị đã trải nghiệm SAO PHU QUOC!</h2>
<p>Xin chào ' . esc_html($v['name']) . ',</p>
<p>Hôm qua anh/chị đã có chuyến trải nghiệm tại Bãi Sao. Anh/chị có thể đánh giá nhanh dịch vụ giúp em không?</p>
<p style="text-align:center;font-size:24px;letter-spacing:8px;margin:24px 0">
<a href="' . esc_url($survey . '&r=1') . '">😞</a>
<a href="' . esc_url($survey . '&r=3') . '">😐</a>
<a href="' . esc_url($survey . '&r=5') . '">😊</a>
<a href="' . esc_url($survey . '&r=8') . '">🤩</a>
<a href="' . esc_url($survey . '&r=10') . '">🥰</a>
</p>
<p>Hoặc trả lời nhanh:</p>
<ul>
<li>Anh/chị có giới thiệu SAO PHU QUOC cho bạn bè không?</li>
<li>Điều gì khiến anh/chị thích nhất?</li>
<li>Có gì cần em cải thiện?</li>
</ul>
<p>Mỗi review giúp em phục vụ anh/chị tốt hơn lần sau! 🙏</p>
<p style="margin-top:24px;color:#718096;font-size:13px">— Team SAO PHU QUOC</p>
</div></div>';
}

function sao_render_upsell_email(array $v): string {
    return '<div style="font-family:Arial;max-width:600px;margin:0 auto;padding:24px;background:#F5F0E8">
<div style="background:#fff;padding:28px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.06)">
<h2 style="color:#1B2A4A">🎁 Tặng anh/chị mã giảm 10%</h2>
<p>Xin chào ' . esc_html($v['name']) . ',</p>
<p>Đã 1 tháng kể từ chuyến đi của anh/chị tại SAO PHU QUOC. Em có ưu đãi đặc biệt dành cho khách quay lại:</p>
<div style="background:#FDF8F0;border:2px dashed #C9A96E;padding:20px;text-align:center;border-radius:8px;margin:20px 0">
<p style="margin:0;color:#718096">Mã giảm giá</p>
<p style="font-size:28px;font-weight:700;color:#C9A96E;margin:8px 0;letter-spacing:2px">COMEBACK10</p>
<p style="margin:0;font-size:13px;color:#718096">Áp dụng cho mọi booking, hạn dùng 30 ngày</p>
</div>
<h3>Trải nghiệm anh/chị có thể thử lần sau:</h3>
<ul>
<li><a href="' . esc_url(home_url('/glamping-phu-quoc-bai-sao/')) . '">Glamping qua đêm</a> — ngắm hoàng hôn và bình minh từ giường</li>
<li><a href="' . esc_url(home_url('/paghera-yacht/sunset-yacht/')) . '">Sunset Yacht</a> — du thuyền ngắm hoàng hôn</li>
<li><a href="' . esc_url(home_url('/ve-combo/sao-signature-journey/')) . '">Signature Journey</a> — combo cao cấp 3 trải nghiệm</li>
</ul>
<p style="margin-top:24px;color:#718096;font-size:13px">— Team SAO PHU QUOC</p>
</div></div>';
}

// NPS feedback endpoint
add_action('init', function () {
    if (!isset($_GET['nps'])) return;
    $code = sanitize_text_field($_GET['nps']);
    $rating = (int)($_GET['r'] ?? 0);
    if ($rating < 1 || $rating > 10) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>NPS</title></head><body style="font-family:Arial;text-align:center;padding:40px"><h2>Cảm ơn!</h2><p>Đánh giá không hợp lệ.</p></body></html>';
        exit;
    }
    global $wpdb;
    $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sao_bookings WHERE booking_code = %s", $code));
    if ($b) {
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'nps_response', "NPS: $rating/10", "code:{$code},rating:{$rating}", 'received'));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>Cảm ơn!</title><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="font-family:Arial;text-align:center;padding:60px;background:#F5F0E8">
<div style="background:#fff;max-width:500px;margin:0 auto;padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08)">
<h2 style="color:#1B2A4A">🙏 Cảm ơn anh/chị!</h2>
<p>Đánh giá <strong>' . $rating . '/10</strong> đã được ghi nhận.</p>
<p style="color:#718096;font-size:14px">' . ($rating >= 9 ? 'Tuyệt vời! Anh/chị có thể giới thiệu SAO PHU QUOC cho bạn bè không?' : ($rating >= 7 ? 'Cảm ơn anh/chị! Em sẽ tiếp tục cải thiện.' : 'Xin lỗi vì trải nghiệm chưa như mong đợi. Em sẽ liên hệ để cải thiện.')) . '</p>
<a href="' . esc_url(home_url('/')) . '" style="display:inline-block;padding:12px 24px;background:#C9A96E;color:#fff;border-radius:6px;text-decoration:none;margin-top:16px">Về Trang chủ</a>
</div></body></html>';
    exit;
}, 1);

// Add manual trigger for testing
add_action('admin_init', function () {
    if (!isset($_GET['sao_run_crm']) || !current_user_can('manage_options')) return;
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'sao_run_crm')) wp_die('Bad nonce');
    sao_crm_run_workflows();
    $counts = json_decode(get_option('sao_crm_last_counts', '{}'), true);
    wp_redirect(add_query_arg([
        'page' => 'sao-dashboard',
        'crm_run' => urlencode("R:{$counts['reminders']} N:{$counts['nps']} U:{$counts['upsells']}"),
    ], admin_url('admin.php')));
    exit;
});

add_action('admin_notices', function () {
    if (!isset($_GET['crm_run'])) return;
    echo '<div class="notice notice-success is-dismissible"><p>✅ CRM cron run: ' . esc_html(urldecode($_GET['crm_run'])) . '</p></div>';
});

// =================================================================
// 19. Booking detail self-service page — /booking/{code}/
// Customer can lookup their booking with code + phone/email verify.
// Show status, QR, items, payment link. Can request cancel or reschedule.
// =================================================================
add_action('init', function () {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#^/booking/([A-Z0-9-]{8,40})(/(cancel|reschedule|verify))?/?$#i', $uri, $m)) return;

    $code = strtoupper(sanitize_text_field($m[1]));
    $action = strtolower($m[3] ?? '');

    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}bookings WHERE booking_code = %s", $code));
    if (!$b) {
        sao_render_booking_page($code, null, 'not_found');
        exit;
    }

    // Session-based verification (15 minute TTL)
    if (session_status() === PHP_SESSION_NONE) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        @session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
        @session_start();
    }
    $verifiedKey = 'sao_bk_verified_' . md5($code);
    $isVerified = !empty($_SESSION[$verifiedKey]) && $_SESSION[$verifiedKey] > time() - 900;

    // POST handlers
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['_token'] ?? '';
        if (!wp_verify_nonce($token, 'sao_booking_' . $code)) {
            sao_render_booking_page($code, $b, 'csrf_error');
            exit;
        }

        if ($action === 'verify') {
            $phoneIn = preg_replace('/[\s\-\.]/', '', sanitize_text_field($_POST['phone'] ?? ''));
            $emailIn = strtolower(sanitize_email($_POST['email'] ?? ''));
            $phoneOk = $phoneIn && (substr($phoneIn, -9) === substr(preg_replace('/[\s\-\.]/', '', $b->customer_phone), -9));
            $emailOk = $emailIn && strtolower($b->customer_email) === $emailIn;
            if ($phoneOk || $emailOk) {
                $_SESSION[$verifiedKey] = time();
                wp_redirect(home_url("/booking/$code/"));
                exit;
            }
            sao_render_booking_page($code, $b, 'verify_failed');
            exit;
        }

        if ($action === 'cancel' && $isVerified) {
            if (in_array($b->booking_status, ['cancelled','refunded','checked_in'], true)) {
                sao_render_booking_page($code, $b, 'cannot_cancel');
                exit;
            }
            // Refund policy: 48h before = 100%, 24-48h = 50%, <24h = 0%
            $hoursUntil = (strtotime($b->booking_date) - time()) / 3600;
            $refundPct = $hoursUntil >= 48 ? 100 : ($hoursUntil >= 24 ? 50 : 0);
            $refundAmount = (int) round((int)$b->paid * ($refundPct / 100));

            $wpdb->update($p . 'bookings', [
                'booking_status' => 'cancelled',
                'payment_status' => $b->paid > 0 ? 'refunded' : 'unpaid',
                'notes' => trim(($b->notes ?? '') . "\n[CANCELLED " . current_time('mysql') . "] refund:{$refundPct}% = {$refundAmount}đ"),
            ], ['id' => $b->id]);

            // Release inventory
            $items = $wpdb->get_results($wpdb->prepare("SELECT product_id, qty FROM {$p}booking_items WHERE booking_id = %d", $b->id));
            foreach ($items as $it) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$p}inventory SET reserved = GREATEST(0, reserved - %d) WHERE product_id = %d AND booking_date = %s",
                    (int)$it->qty, (int)$it->product_id, $b->booking_date
                ));
            }

            // Log
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
                'cancel', "Cancel booking", "code:{$code},refund_pct:{$refundPct},amount:{$refundAmount}", 'completed'));

            do_action('sao_booking_cancelled', $b->id, $b, $refundPct, $refundAmount);

            wp_redirect(home_url("/booking/$code/?msg=cancelled&refund=" . $refundAmount));
            exit;
        }

        if ($action === 'reschedule' && $isVerified) {
            $newDate = sanitize_text_field($_POST['new_date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate) || strtotime($newDate) < strtotime(date('Y-m-d'))) {
                sao_render_booking_page($code, $b, 'invalid_date');
                exit;
            }
            $hoursUntil = (strtotime($b->booking_date) - time()) / 3600;
            if ($hoursUntil < 48) {
                sao_render_booking_page($code, $b, 'reschedule_too_late');
                exit;
            }
            if (in_array($b->booking_status, ['cancelled','refunded','checked_in'], true)) {
                sao_render_booking_page($code, $b, 'cannot_reschedule');
                exit;
            }

            // Check inventory for new date — release old, reserve new
            $items = $wpdb->get_results($wpdb->prepare("SELECT product_id, qty FROM {$p}booking_items WHERE booking_id = %d", $b->id));
            $wpdb->query('START TRANSACTION');
            try {
                foreach ($items as $it) {
                    $inv = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, total_capacity, reserved FROM {$p}inventory WHERE product_id = %d AND booking_date = %s LIMIT 1 FOR UPDATE",
                        (int)$it->product_id, $newDate));
                    if ($inv) {
                        $avail = (int)$inv->total_capacity - (int)$inv->reserved;
                        if ($avail < (int)$it->qty) throw new Exception("Hết chỗ ngày $newDate");
                        $wpdb->update($p . 'inventory', ['reserved' => (int)$inv->reserved + (int)$it->qty], ['id' => $inv->id]);
                    } else {
                        $wpdb->insert($p . 'inventory', [
                            'product_id' => (int)$it->product_id, 'booking_date' => $newDate,
                            'total_capacity' => 100, 'reserved' => (int)$it->qty, 'status' => 'open']);
                    }
                    // Release old
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$p}inventory SET reserved = GREATEST(0, reserved - %d) WHERE product_id = %d AND booking_date = %s",
                        (int)$it->qty, (int)$it->product_id, $b->booking_date));
                }

                $wpdb->update($p . 'bookings', [
                    'booking_date' => $newDate,
                    'notes' => trim(($b->notes ?? '') . "\n[RESCHEDULED " . current_time('mysql') . "] from {$b->booking_date} to {$newDate}"),
                ], ['id' => $b->id]);
                $wpdb->query('COMMIT');

                $wpdb->query($wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
                    'reschedule', "Reschedule", "code:{$code},old:{$b->booking_date},new:{$newDate}", 'completed'));

                wp_redirect(home_url("/booking/$code/?msg=rescheduled&new={$newDate}"));
                exit;
            } catch (Throwable $e) {
                $wpdb->query('ROLLBACK');
                sao_render_booking_page($code, $b, 'reschedule_failed', ['error' => $e->getMessage()]);
                exit;
            }
        }
    }

    // Render
    sao_render_booking_page($code, $b, $isVerified ? 'detail' : 'verify_form');
    exit;
}, 1);

function sao_render_booking_page(string $code, $b, string $state, array $extra = []) {
    $site = get_option('blogname', 'SAO PHU QUOC');
    $logo = '<a href="' . home_url('/') . '" style="text-decoration:none;color:#1B2A4A"><span style="color:#C9A96E">✦</span> ' . esc_html($site) . '</a>';
    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Booking ' . esc_html($code) . ' — ' . esc_html($site) . '</title>
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{font-family:Inter,Arial,sans-serif;background:#F5F0E8;margin:0;padding:20px;color:#1B2A4A}
.wrap{max-width:600px;margin:0 auto}
.brand{font-size:20px;font-weight:700;margin:8px 0 24px;text-align:center}
.card{background:#fff;border-radius:12px;padding:28px;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px}
h1{font-size:22px;margin:0 0 16px}h2{font-size:18px;margin:20px 0 12px}h3{font-size:15px;margin:14px 0 8px;color:#718096}
.row{padding:10px 0;border-bottom:1px solid #f0eee9;display:flex;justify-content:space-between}
.row:last-child{border:none}.row strong{color:#1B2A4A}.row span{color:#555}
.badge{padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;background:#eee}
.badge.paid,.badge.deposit_paid,.badge.confirmed,.badge.checked_in{background:#d4edda;color:#155724}
.badge.pending,.badge.unpaid{background:#fff3cd;color:#856404}
.badge.cancelled,.badge.refunded,.badge.failed{background:#f8d7da;color:#721c24}
.btn{display:inline-block;padding:12px 22px;background:#C9A96E;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;text-decoration:none;font-family:inherit;font-size:14px;margin:4px}
.btn:hover{background:#A88B50}
.btn-outline{background:transparent;border:2px solid #1B2A4A;color:#1B2A4A}
.btn-danger{background:#dc3545}.btn-danger:hover{background:#a71d2a}
.alert{padding:14px;border-radius:6px;margin:12px 0}
.alert-success{background:#d4edda;color:#155724}
.alert-warning{background:#fff3cd;color:#856404}
.alert-error{background:#f8d7da;color:#721c24}
.input{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:15px;font-family:inherit;margin-top:6px}
.qr-box{text-align:center;background:#FDF8F0;padding:20px;border-radius:8px;margin:20px 0}
.qr-box img{max-width:200px;border:1px solid #eee;background:#fff;padding:8px}
label{display:block;font-weight:600;font-size:13px;margin-top:12px}
.center{text-align:center}
.muted{color:#888;font-size:13px}
.help{background:#FDF8F0;padding:14px;border-radius:6px;font-size:13px;color:#555;margin:14px 0}
</style></head><body><div class="wrap">';
    echo '<div class="brand">' . $logo . '</div>';

    if ($state === 'not_found') {
        echo '<div class="card"><h1>❌ Không tìm thấy booking</h1>
<p>Mã <code>' . esc_html($code) . '</code> không tồn tại trong hệ thống.</p>
<p class="muted">Vui lòng kiểm tra lại mã trong email xác nhận hoặc liên hệ hotline ' . esc_html(get_option('sao_hotline', '0901234567')) . '.</p>
<a href="' . home_url('/') . '" class="btn btn-outline">← Về Trang chủ</a></div>';
    }
    elseif ($state === 'verify_form') {
        $err = !empty($extra['error']);
        echo '<div class="card"><h1>🔐 Xác minh để xem booking</h1>
<p>Để bảo mật, vui lòng nhập số điện thoại HOẶC email đã dùng khi đặt:</p>';
        if (isset($_GET['msg']) && $_GET['msg'] === 'cancelled') echo '<div class="alert alert-warning">Đã hủy booking. Hoàn cọc: ' . esc_html(number_format((int)($_GET['refund'] ?? 0))) . 'đ</div>';
        echo '<form method="POST" action="' . esc_url(home_url("/booking/$code/verify/")) . '">
<input type="hidden" name="_token" value="' . esc_attr(wp_create_nonce('sao_booking_' . $code)) . '">
<label>Số điện thoại</label>
<input type="tel" name="phone" class="input" placeholder="09xxxxxxxx" autocomplete="tel">
<div class="center muted" style="margin:10px 0">hoặc</div>
<label>Email</label>
<input type="email" name="email" class="input" placeholder="email@example.com" autocomplete="email">
<div class="center" style="margin-top:18px"><button type="submit" class="btn">Xác minh</button></div>
</form></div>';
    }
    elseif ($state === 'verify_failed') {
        echo '<div class="card"><div class="alert alert-error">❌ Sai số điện thoại / email. Vui lòng thử lại.</div>
<a href="' . home_url("/booking/$code/") . '" class="btn btn-outline">← Quay lại</a></div>';
    }
    elseif ($state === 'csrf_error') {
        echo '<div class="card"><div class="alert alert-error">Phiên đã hết hạn. Tải lại trang.</div>
<a href="' . home_url("/booking/$code/") . '" class="btn">Tải lại</a></div>';
    }
    elseif ($state === 'cannot_cancel') {
        echo '<div class="card"><div class="alert alert-warning">Booking này không thể hủy (đã check-in hoặc đã hủy trước đó).</div>
<a href="' . home_url("/booking/$code/") . '" class="btn btn-outline">← Quay lại</a></div>';
    }
    elseif ($state === 'cannot_reschedule' || $state === 'reschedule_too_late' || $state === 'invalid_date' || $state === 'reschedule_failed') {
        $msg = [
            'cannot_reschedule' => 'Booking này không thể đổi ngày (đã check-in hoặc đã hủy).',
            'reschedule_too_late' => 'Chỉ được đổi ngày trước ít nhất 48 giờ. Vui lòng liên hệ hotline để được hỗ trợ.',
            'invalid_date' => 'Ngày mới không hợp lệ.',
            'reschedule_failed' => 'Không đổi ngày được — có thể đã hết chỗ ngày bạn chọn. ' . ($extra['error'] ?? ''),
        ][$state];
        echo '<div class="card"><div class="alert alert-error">' . esc_html($msg) . '</div>
<a href="' . home_url("/booking/$code/") . '" class="btn btn-outline">← Quay lại</a></div>';
    }
    elseif ($state === 'detail' && $b) {
        $msg = sanitize_text_field($_GET['msg'] ?? '');
        if ($msg === 'cancelled') echo '<div class="card alert alert-warning">⚠️ Booking đã hủy. Hoàn cọc: <strong>' . esc_html(number_format((int)($_GET['refund'] ?? 0))) . 'đ</strong></div>';
        if ($msg === 'rescheduled') echo '<div class="card alert alert-success">✅ Đã đổi ngày sang <strong>' . esc_html($_GET['new'] ?? '') . '</strong></div>';

        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sao_booking_items WHERE booking_id = %d", $b->id));

        $hoursUntil = (strtotime($b->booking_date) - time()) / 3600;
        $canCancel = !in_array($b->booking_status, ['cancelled','refunded','checked_in'], true);
        $canReschedule = $canCancel && $hoursUntil >= 48;

        echo '<div class="card">
<h1>Booking #<code>' . esc_html($code) . '</code></h1>
<div class="row"><strong>Khách</strong><span>' . esc_html($b->customer_name) . '</span></div>
<div class="row"><strong>SĐT</strong><span>' . esc_html(preg_replace('/(\d{3})\d{4}(\d{3,4})/', '$1****$2', $b->customer_phone)) . '</span></div>
<div class="row"><strong>Email</strong><span>' . esc_html(preg_replace('/(.{2}).*(@.*)/', '$1***$2', $b->customer_email)) . '</span></div>
<div class="row"><strong>Ngày đi</strong><span>' . esc_html($b->booking_date) . '</span></div>
<div class="row"><strong>Số khách</strong><span>' . (int)$b->guests . '</span></div>
<div class="row"><strong>Tổng tiền</strong><span>' . number_format((int)$b->total) . 'đ</span></div>
<div class="row"><strong>Cọc</strong><span>' . number_format((int)$b->deposit) . 'đ</span></div>
<div class="row"><strong>Đã thanh toán</strong><span>' . number_format((int)$b->paid) . 'đ</span></div>
<div class="row"><strong>Trạng thái TT</strong><span class="badge ' . esc_attr($b->payment_status) . '">' . esc_html($b->payment_status) . '</span></div>
<div class="row"><strong>Trạng thái booking</strong><span class="badge ' . esc_attr($b->booking_status) . '">' . esc_html($b->booking_status) . '</span></div>';

        if (!empty($items)) {
            echo '<h2>Items</h2>';
            foreach ($items as $it) {
                echo '<div class="row"><strong>' . esc_html($it->product_name) . ' × ' . (int)$it->qty . '</strong><span>' . number_format((int)$it->total_price) . 'đ</span></div>';
            }
        }
        echo '</div>';

        // QR code box (only if paid)
        if (in_array($b->payment_status, ['paid','deposit_paid'], true) && !$b->qr_scanned) {
            $qrUrl = sao_qr_image_url($code, 200);
            echo '<div class="card"><h2>🎫 Vé QR</h2>
<div class="qr-box">
<img src="' . esc_url($qrUrl) . '" alt="QR ' . esc_attr($code) . '">
<p style="font-family:monospace;font-size:14px;margin:8px 0;color:#1B2A4A;font-weight:700">' . esc_html($code) . '</p>
<p class="muted">Xuất trình tại cổng để check-in</p>
</div></div>';
        }
        elseif ($b->qr_scanned) {
            echo '<div class="card alert alert-success">✅ Đã check-in lúc ' . esc_html($b->qr_scanned_at ?? '—') . '</div>';
        }

        // Payment button if pending
        if ($b->payment_status === 'pending' || $b->payment_status === 'unpaid') {
            echo '<div class="card center">
<h2>💳 Thanh toán cọc 30%</h2>
<p>Vui lòng thanh toán <strong>' . number_format((int)$b->deposit) . 'đ</strong> để xác nhận booking.</p>
<a href="' . home_url('/sao-pay/?bid=' . (int)$b->id) . '" class="btn">Thanh toán qua VNPay</a>
</div>';
        }

        // Actions
        if ($canCancel || $canReschedule) {
            echo '<div class="card"><h2>Quản lý booking</h2>';

            if ($canReschedule) {
                $minDate = date('Y-m-d', strtotime('+2 days'));
                echo '<form method="POST" action="' . esc_url(home_url("/booking/$code/reschedule/")) . '" style="margin-bottom:16px">
<input type="hidden" name="_token" value="' . esc_attr(wp_create_nonce('sao_booking_' . $code)) . '">
<label>Đổi sang ngày khác</label>
<input type="date" name="new_date" class="input" min="' . esc_attr($minDate) . '" required>
<div class="muted" style="margin-top:6px">Chỉ đổi được trước 48h. Áp dụng giá theo ngày mới (cuối tuần +20%, lễ +30%).</div>
<button type="submit" class="btn">Đổi ngày</button>
</form>';
            } else {
                echo '<div class="help">⚠️ Không thể đổi ngày (còn dưới 48 giờ đến ngày đi). Liên hệ hotline để được hỗ trợ.</div>';
            }

            if ($canCancel) {
                $hu = $hoursUntil;
                $pct = $hu >= 48 ? 100 : ($hu >= 24 ? 50 : 0);
                $refund = (int) round((int)$b->paid * ($pct / 100));
                echo '<form method="POST" action="' . esc_url(home_url("/booking/$code/cancel/")) . '" onsubmit="return confirm(\'Xác nhận hủy booking? Hoàn cọc ' . number_format($refund) . 'đ.\')">
<input type="hidden" name="_token" value="' . esc_attr(wp_create_nonce('sao_booking_' . $code)) . '">
<div class="help">
<strong>Chính sách hoàn tiền:</strong><br>
• Hủy trước 48h: hoàn <strong>100%</strong> cọc<br>
• Hủy 24-48h: hoàn <strong>50%</strong><br>
• Hủy dưới 24h: <strong>không hoàn</strong><br>
Hủy bây giờ: hoàn ' . esc_html($pct) . '% = <strong>' . number_format($refund) . 'đ</strong>
</div>
<button type="submit" class="btn btn-danger">Hủy booking</button>
</form>';
            }
            echo '</div>';
        }

        echo '<div class="card help">
📞 Hotline: <strong>' . esc_html(get_option('sao_hotline', '0901234567')) . '</strong> ·
💬 <a href="https://zalo.me/' . esc_attr(get_option('sao_zalo', '0901234567')) . '">Zalo</a> ·
📧 ' . esc_html(get_option('sao_smtp_from_email', 'contact@taichinhachau.com')) . '
</div>';
    }
    echo '</div></body></html>';
}

// =================================================================
// 20. Coupon code system — apply at booking time
// Used by mu-plugin's sao_safe_create_booking. Add coupons table.
// =================================================================
add_action('init', function () {
    global $wpdb;
    $tbl = $wpdb->prefix . 'sao_coupons';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tbl));
    if ($exists) return;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE IF NOT EXISTS $tbl (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(64) NOT NULL UNIQUE,
        discount_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
        min_amount DECIMAL(12,2) DEFAULT 0,
        valid_from DATE NULL,
        valid_to DATE NULL,
        max_uses INT NULL,
        current_uses INT NOT NULL DEFAULT 0,
        per_customer_uses INT DEFAULT 1,
        status VARCHAR(16) DEFAULT 'active',
        notes VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;");

    // Seed default coupons
    $wpdb->insert($tbl, ['code'=>'COMEBACK10','discount_type'=>'percent','discount_value'=>10,'notes'=>'Mã giảm 10% cho khách quay lại','status'=>'active']);
    $wpdb->insert($tbl, ['code'=>'EARLYBIRD','discount_type'=>'percent','discount_value'=>10,'notes'=>'Early bird auto-discount','status'=>'active']);
    $wpdb->insert($tbl, ['code'=>'WELCOME50K','discount_type'=>'fixed','discount_value'=>50000,'min_amount'=>500000,'notes'=>'Giảm 50k cho đơn ≥500k','status'=>'active']);
}, 5);

if (!function_exists('sao_apply_coupon')) {
    /**
     * Apply coupon code. Returns ['discount' => int, 'new_total' => int, 'message' => string]
     * On invalid: ['discount' => 0, 'new_total' => $total, 'message' => 'error msg']
     */
    function sao_apply_coupon(string $code, int $total): array {
        global $wpdb;
        $code = strtoupper(trim($code));
        if (!$code) return ['discount'=>0, 'new_total'=>$total, 'message'=>''];

        $c = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sao_coupons WHERE code = %s AND status = 'active'", $code));
        if (!$c) return ['discount'=>0, 'new_total'=>$total, 'message'=>'Mã không tồn tại hoặc đã hết hạn.'];

        if ($c->valid_from && strtotime($c->valid_from) > time()) {
            return ['discount'=>0, 'new_total'=>$total, 'message'=>'Mã chưa được kích hoạt.'];
        }
        if ($c->valid_to && strtotime($c->valid_to . ' 23:59:59') < time()) {
            return ['discount'=>0, 'new_total'=>$total, 'message'=>'Mã đã hết hạn.'];
        }
        if ($c->max_uses && $c->current_uses >= $c->max_uses) {
            return ['discount'=>0, 'new_total'=>$total, 'message'=>'Mã đã hết lượt sử dụng.'];
        }
        if ($c->min_amount && $total < (float)$c->min_amount) {
            return ['discount'=>0, 'new_total'=>$total, 'message'=>'Đơn hàng tối thiểu ' . number_format((int)$c->min_amount) . 'đ.'];
        }

        $discount = $c->discount_type === 'percent'
            ? (int) round($total * ((float)$c->discount_value / 100))
            : (int) min($total, $c->discount_value);
        $newTotal = max(0, $total - $discount);

        return [
            'discount' => $discount,
            'new_total' => $newTotal,
            'message' => 'Áp dụng mã ' . $code . ': -' . number_format($discount) . 'đ',
            'code' => $code,
        ];
    }
}

// Increment coupon usage counter after booking succeeds
add_action('sao_booking_created', function ($bid, $data) {
    if (empty($data['coupon_code'])) return;
    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->prefix}sao_coupons SET current_uses = current_uses + 1 WHERE code = %s",
        strtoupper($data['coupon_code'])));
}, 5, 2);

// =================================================================
// 21. Admin User Management — 13 roles per spec
// Adds custom roles for operations
// =================================================================
add_action('init', function () {
    if (get_option('sao_roles_v1') === 'installed') return;

    $base_caps = ['read' => true];
    $sao_roles = [
        'sao_general_manager' => 'SAO General Manager',
        'sao_marketing_manager' => 'SAO Marketing Manager',
        'sao_sales' => 'SAO Sales',
        'sao_content_editor' => 'SAO Content Editor',
        'sao_bai_sao_ops' => 'SAO Bãi Sao Operations',
        'sao_ganh_ngon_manager' => 'SAO Gánh Ngon Manager',
        'sao_paghera_yacht_manager' => 'SAO Paghera Yacht Manager',
        'sao_check_in_staff' => 'SAO Check-in Staff',
        'sao_fb_staff' => 'SAO F&B Staff',
        'sao_shuttle_coordinator' => 'SAO Shuttle Coordinator',
        'sao_event_manager' => 'SAO Event Manager',
        'sao_accountant' => 'SAO Accountant',
        'sao_customer_support' => 'SAO Customer Support',
    ];

    // Role-specific caps
    $caps_map = [
        'sao_general_manager' => ['read'=>true,'edit_posts'=>true,'edit_others_posts'=>true,'publish_posts'=>true,'manage_categories'=>true,'sao_view_dashboard'=>true,'sao_manage_bookings'=>true,'sao_manage_inventory'=>true,'sao_manage_partners'=>true,'sao_view_kpi'=>true,'sao_check_in'=>true],
        'sao_marketing_manager' => ['read'=>true,'edit_posts'=>true,'publish_posts'=>true,'sao_view_dashboard'=>true,'sao_view_kpi'=>true,'sao_manage_campaigns'=>true],
        'sao_sales' => ['read'=>true,'sao_manage_bookings'=>true,'sao_view_dashboard'=>true,'sao_manage_partners'=>true],
        'sao_content_editor' => ['read'=>true,'edit_posts'=>true,'edit_others_posts'=>true,'publish_posts'=>true,'upload_files'=>true,'manage_categories'=>true],
        'sao_bai_sao_ops' => ['read'=>true,'sao_manage_inventory'=>true,'sao_view_bookings'=>true,'sao_check_in'=>true],
        'sao_ganh_ngon_manager' => ['read'=>true,'sao_manage_restaurant'=>true,'sao_view_bookings'=>true,'sao_check_in'=>true],
        'sao_paghera_yacht_manager' => ['read'=>true,'sao_manage_yacht'=>true,'sao_view_bookings'=>true],
        'sao_check_in_staff' => ['read'=>true,'sao_check_in'=>true,'sao_view_bookings'=>true],
        'sao_fb_staff' => ['read'=>true,'sao_view_orders'=>true],
        'sao_shuttle_coordinator' => ['read'=>true,'sao_view_bookings'=>true,'sao_manage_shuttle'=>true],
        'sao_event_manager' => ['read'=>true,'sao_manage_events'=>true,'sao_view_bookings'=>true],
        'sao_accountant' => ['read'=>true,'sao_view_finance'=>true,'sao_view_bookings'=>true,'sao_view_partners'=>true],
        'sao_customer_support' => ['read'=>true,'sao_view_bookings'=>true,'sao_edit_customer'=>true],
    ];

    foreach ($sao_roles as $role_key => $role_name) {
        $caps = $caps_map[$role_key] ?? $base_caps;
        if (!get_role($role_key)) {
            add_role($role_key, $role_name, $caps);
        }
    }

    // Add SAO caps to administrator
    $admin = get_role('administrator');
    if ($admin) {
        foreach (['sao_view_dashboard','sao_manage_bookings','sao_manage_inventory','sao_manage_partners','sao_view_kpi','sao_check_in','sao_manage_campaigns','sao_manage_restaurant','sao_manage_yacht','sao_view_orders','sao_manage_shuttle','sao_manage_events','sao_view_finance','sao_view_partners','sao_edit_customer','sao_view_bookings'] as $cap) {
            $admin->add_cap($cap);
        }
    }

    update_option('sao_roles_v1', 'installed');
}, 10);

// =================================================================
// 22. Robots.txt with proper directives (override WP default)
// =================================================================
add_filter('robots_txt', function ($output) {
    $site = home_url('/');
    return "User-agent: *\nAllow: /\nDisallow: /wp-admin/\nDisallow: /wp-includes/\nDisallow: /?nps=\nDisallow: /sao-pay/\nDisallow: /booking/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: {$site}sitemap_index.xml\n";
}, 99, 2);

// =================================================================
// 23. Local QR cache — fetch once from quickchart.io, cache PNG locally
// After first generation, all subsequent requests served from local disk
// =================================================================
add_action('init', function () {
    if (!isset($_GET['sao_qr_local'])) return;
    $code = preg_replace('/[^A-Z0-9-]/i', '', $_GET['sao_qr_local']);
    if (!$code || strlen($code) < 8) { http_response_code(400); exit('Invalid code'); }

    // Verify booking exists
    global $wpdb;
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}sao_bookings WHERE booking_code = %s", $code));
    if (!$exists) { http_response_code(404); exit('Not found'); }

    // Cache path
    $upload = wp_upload_dir();
    $dir = $upload['basedir'] . '/sao-qr';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $size = max(150, min(500, (int)($_GET['s'] ?? 250)));
    $file = $dir . '/' . $code . '-' . $size . '.png';

    // Serve cached if exists + fresh (7 day TTL)
    if (is_file($file) && filemtime($file) > time() - 604800) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=2592000, immutable');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    // Generate via quickchart.io and cache
    $payload = home_url('/sao-checkin/?code=' . urlencode($code));
    $url = 'https://quickchart.io/qr?text=' . urlencode($payload) . '&size=' . $size . '&margin=2&format=png';
    $response = wp_remote_get($url, ['timeout' => 15, 'sslverify' => true]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        // Fallback: redirect to quickchart
        wp_redirect($url);
        exit;
    }
    $body = wp_remote_retrieve_body($response);
    if (!$body) { http_response_code(502); exit('QR gen failed'); }

    @file_put_contents($file, $body);
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=2592000, immutable');
    echo $body;
    exit;
}, 1);

// Override sao_qr_image_url to use local cache
add_filter('sao_qr_image_url_override', function ($url, $code, $size) {
    return home_url('/?sao_qr_local=' . urlencode($code) . '&s=' . (int)$size);
}, 10, 3);

if (!function_exists('sao_qr_image_url_v2')) {
    function sao_qr_image_url_v2(string $code, int $size = 250): string {
        $service = get_option('sao_qr_service', 'local_cache');
        if ($service === 'local_cache') {
            return home_url('/?sao_qr_local=' . urlencode($code) . '&s=' . $size);
        }
        return sao_qr_image_url($code, $size);
    }
}

// =================================================================
// 24. Waiting Room for Flash Sale — Queue system
// /sao-queue/?event=NAME → assign queue position + token
// When position is at front, redirect to /dat-cho/
// =================================================================
add_action('init', function () {
    global $wpdb;
    $tbl = $wpdb->prefix . 'sao_queue';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tbl));
    if (!$exists) {
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE IF NOT EXISTS $tbl (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_name VARCHAR(64) NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            ip_hash VARCHAR(64),
            position INT NOT NULL,
            status ENUM('waiting','active','expired','used') NOT NULL DEFAULT 'waiting',
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            activated_at DATETIME NULL,
            used_at DATETIME NULL,
            INDEX idx_event_status (event_name, status, position)
        ) $charset");
    }
}, 6);

// /sao-queue/?event=NAME → render queue page
add_action('init', function () {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#^/sao-queue/?$#', $uri)) return;

    $event = sanitize_text_field($_GET['event'] ?? 'default');
    $event = preg_replace('/[^a-z0-9_-]/i', '', $event);
    if (!$event) { http_response_code(400); exit('Invalid event'); }

    // Check feature enabled
    if (!get_option('sao_queue_' . $event, '0')) {
        // No queue active → redirect to booking
        wp_redirect(home_url('/dat-cho/'));
        exit;
    }

    global $wpdb;
    $tbl = $wpdb->prefix . 'sao_queue';
    $maxConcurrent = (int) get_option('sao_queue_max_concurrent', 50);
    $ipHash = hash('sha256', sao_safe_client_ip() . '|' . $event);

    // Check existing token in cookie
    $cookieName = 'sao_queue_' . $event;
    $token = $_COOKIE[$cookieName] ?? '';
    if ($token) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE token = %s AND event_name = %s", $token, $event));
        if (!$row || $row->status === 'used' || $row->status === 'expired') {
            $token = ''; // Re-issue
        }
    }

    // Issue new token if needed
    if (!$token) {
        // Check if same IP already in queue
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tbl WHERE ip_hash = %s AND event_name = %s AND status IN ('waiting','active') LIMIT 1",
            $ipHash, $event));
        if ($existing) {
            $token = $existing->token;
            setcookie($cookieName, $token, time() + 7200, '/', '', is_ssl(), true);
            $row = $existing;
        } else {
            $token = bin2hex(random_bytes(16));
            $position = (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(MAX(position), 0) + 1 FROM $tbl WHERE event_name = %s", $event));
            $wpdb->insert($tbl, [
                'event_name' => $event,
                'token' => $token,
                'ip_hash' => $ipHash,
                'position' => $position,
                'status' => 'waiting',
            ]);
            setcookie($cookieName, $token, time() + 7200, '/', '', is_ssl(), true);
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE token = %s", $token));
        }
    }

    // Auto-promote: count active sessions, promote next waiting if room
    $activeCount = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tbl WHERE event_name = %s AND status = 'active' AND activated_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", $event));
    if ($activeCount < $maxConcurrent) {
        $next = $wpdb->get_results($wpdb->prepare(
            "SELECT id, token FROM $tbl WHERE event_name = %s AND status = 'waiting' ORDER BY position ASC LIMIT %d",
            $event, $maxConcurrent - $activeCount));
        foreach ($next as $n) {
            $wpdb->update($tbl, ['status' => 'active', 'activated_at' => current_time('mysql')], ['id' => $n->id]);
            if ($n->token === $token) $row->status = 'active'; // Reflect change in current row
        }
    }

    // If current is active → redirect to booking with token
    if ($row && $row->status === 'active') {
        wp_redirect(home_url('/dat-cho/?queue_token=' . urlencode($token)));
        exit;
    }

    // Calculate position ahead
    $ahead = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tbl WHERE event_name = %s AND status = 'waiting' AND position < %d",
        $event, $row->position));
    $estWait = ceil($ahead / $maxConcurrent) * 5; // 5 min per batch

    // Render queue page
    header('Content-Type: text/html; charset=utf-8');
    header('Refresh: 10');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đang chờ — ' . esc_html($event) . '</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;700&display=swap" rel="stylesheet">
<style>body{font-family:Inter,Arial;background:linear-gradient(135deg,#1B2A4A,#0A7E8C);color:#fff;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{max-width:480px;background:rgba(255,255,255,.1);backdrop-filter:blur(20px);padding:48px 32px;border-radius:16px;text-align:center}
.spinner{width:60px;height:60px;border:4px solid rgba(255,255,255,.2);border-top-color:#C9A96E;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 24px}
@keyframes spin{to{transform:rotate(360deg)}}
.pos{font-size:48px;font-weight:700;color:#C9A96E;margin:16px 0 8px}
.wait{font-size:14px;opacity:.85}
h1{font-size:22px;margin:12px 0}
.msg{font-size:15px;opacity:.9;line-height:1.5;margin:16px 0}
.bar{height:6px;background:rgba(255,255,255,.2);border-radius:3px;margin:20px 0;overflow:hidden}
.bar-fill{height:100%;background:#C9A96E;border-radius:3px;width:' . max(5, 100 - min(100, ($ahead * 100 / max($ahead + 1, 1)))) . '%}
.muted{font-size:12px;opacity:.6;margin-top:24px}
</style></head><body>
<div class="box">
<div class="spinner"></div>
<h1>🎫 Bạn đang trong hàng đợi</h1>
<p class="msg">Sự kiện <strong>' . esc_html($event) . '</strong> đang có nhiều người truy cập.<br>Hệ thống sẽ tự động đưa bạn vào khi đến lượt.</p>
<div class="pos">#' . (int)($ahead + 1) . '</div>
<p class="wait">Còn <strong>' . (int)$ahead . '</strong> người trước bạn</p>
<div class="bar"><div class="bar-fill"></div></div>
<p class="wait">Thời gian chờ ước tính: <strong>~' . $estWait . ' phút</strong></p>
<p class="muted">Đừng tải lại trang. Hệ thống tự đưa bạn vào mỗi 10 giây.<br>Token: <code>' . esc_html(substr($token, 0, 8)) . '...</code></p>
</div></body></html>';
    exit;
}, 1);

if (!function_exists('sao_safe_client_ip')) {
    function sao_safe_client_ip(): string {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = trim(explode(',', $_SERVER[$k])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return '0.0.0.0';
    }
}

// Mark queue token as used after successful booking
add_action('sao_booking_created', function ($bid, $data) {
    $token = $_POST['queue_token'] ?? $_COOKIE['sao_queue_default'] ?? '';
    if (!$token) return;
    global $wpdb;
    $wpdb->update($wpdb->prefix . 'sao_queue',
        ['status' => 'used', 'used_at' => current_time('mysql')],
        ['token' => $token]);
}, 8, 2);

// =================================================================
// 25. VNPay Refund initiator (admin-only)
// When cancellation triggers refund, call VNPay API to refund
// =================================================================
add_action('sao_booking_cancelled', function ($bid, $b, $refundPct, $refundAmount) {
    if ($refundAmount <= 0) return;
    if (empty($b->payment_txn_id ?? '')) return; // No txn to refund

    $secret = get_option('sao_vnpay_hash_secret', '');
    $merchant = get_option('sao_vnpay_merchant_code', '');
    if (!$secret || !$merchant || $secret === 'SAOPHUQUOCVNP@2026') {
        // VNPay not configured — log only
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'refund_manual', "Manual refund needed", "code:{$b->booking_code},amount:{$refundAmount}", 'pending_manual'));
        return;
    }

    // Build VNPay refund request (vnp_Command=refund)
    $params = [
        'vnp_RequestId' => time() . '-' . random_int(1000,9999),
        'vnp_Version' => '2.1.0',
        'vnp_Command' => 'refund',
        'vnp_TmnCode' => $merchant,
        'vnp_TransactionType' => $refundPct >= 100 ? '02' : '03',
        'vnp_TxnRef' => $b->booking_code,
        'vnp_Amount' => $refundAmount * 100,
        'vnp_OrderInfo' => 'Refund ' . $b->booking_code . ' ' . $refundPct . '%',
        'vnp_TransactionNo' => $b->payment_txn_id ?? '',
        'vnp_TransactionDate' => date('YmdHis', strtotime($b->payment_paid_at ?? 'now')),
        'vnp_CreateBy' => 'admin',
        'vnp_CreateDate' => date('YmdHis'),
        'vnp_IpAddr' => '127.0.0.1',
    ];
    $hashData = implode('|', array_values($params));
    $params['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $secret);

    $apiUrl = 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction';
    if (strpos(get_option('sao_vnpay_url', ''), 'sandbox') === false) {
        $apiUrl = 'https://merchant.vnpay.vn/merchant_webapi/api/transaction';
    }

    $response = wp_remote_post($apiUrl, [
        'body' => json_encode($params),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 30,
    ]);

    global $wpdb;
    if (is_wp_error($response)) {
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'refund_error', "Refund API error", "code:{$b->booking_code}:" . $response->get_error_message(), 'failed'));
        return;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $code = $body['vnp_ResponseCode'] ?? '99';
    $status = $code === '00' ? 'success' : 'failed';

    $wpdb->insert($wpdb->prefix . 'sao_payments', [
        'booking_id' => $bid,
        'txn_id' => $body['vnp_TransactionNo'] ?? '',
        'method' => 'vnpay_refund',
        'amount' => -$refundAmount,
        'status' => $status,
    ]);

    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
        'refund_auto', "Auto refund VNPay", "code:{$b->booking_code},amount:{$refundAmount},vnp_code:{$code}", $status));
}, 10, 4);

// =================================================================
// 26. Loyalty Points System
// 1 point per 10,000đ spent. Redeem at booking: 100 points = 50,000đ off.
// =================================================================
add_action('init', function () {
    global $wpdb;
    $tbl = $wpdb->prefix . 'sao_loyalty';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tbl));
    if ($exists) return;
    $charset = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta("CREATE TABLE IF NOT EXISTS $tbl (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_phone VARCHAR(32) NOT NULL,
        customer_email VARCHAR(255),
        points INT NOT NULL DEFAULT 0,
        total_earned INT NOT NULL DEFAULT 0,
        total_redeemed INT NOT NULL DEFAULT 0,
        tier ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_phone (customer_phone)
    ) $charset");
}, 7);

// Earn points when booking paid
add_action('sao_booking_paid', function ($bid, $b) {
    if (empty($b->customer_phone)) return;
    $points = (int) floor($b->total / 10000); // 1 point per 10k
    if ($points <= 0) return;

    global $wpdb;
    $tbl = $wpdb->prefix . 'sao_loyalty';
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl WHERE customer_phone = %s", $b->customer_phone));
    if ($existing) {
        $newTotal = (int)$existing->total_earned + $points;
        $newPoints = (int)$existing->points + $points;
        $tier = $newTotal >= 5000 ? 'platinum' : ($newTotal >= 2000 ? 'gold' : ($newTotal >= 500 ? 'silver' : 'bronze'));
        $wpdb->update($tbl, [
            'points' => $newPoints,
            'total_earned' => $newTotal,
            'tier' => $tier,
        ], ['customer_phone' => $b->customer_phone]);
    } else {
        $wpdb->insert($tbl, [
            'customer_phone' => $b->customer_phone,
            'customer_email' => $b->customer_email,
            'points' => $points,
            'total_earned' => $points,
            'tier' => 'bronze',
        ]);
    }
}, 10, 2);

// Loyalty admin page
add_action('admin_menu', function () {
    add_submenu_page('sao-dashboard', 'Loyalty Points', '⭐ Loyalty', 'manage_options', 'sao-loyalty', function () {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sao_loyalty ORDER BY total_earned DESC LIMIT 100");
        echo '<div class="wrap"><h1>⭐ Loyalty Points</h1>
<p>1 point per 10,000đ. Tiers: bronze (0+), silver (500+), gold (2000+), platinum (5000+).</p>
<p>Redeem: 100 points = 50,000đ off (coupon SAOPOINT100)</p>
<table class="wp-list-table widefat striped">
<thead><tr><th>Phone</th><th>Email</th><th>Points</th><th>Earned</th><th>Redeemed</th><th>Tier</th><th>Joined</th></tr></thead>
<tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . esc_html($r->customer_phone) . '</td><td>' . esc_html($r->customer_email) . '</td>
<td><strong>' . (int)$r->points . '</strong></td><td>' . (int)$r->total_earned . '</td><td>' . (int)$r->total_redeemed . '</td>
<td><span class="sao-badge sao-' . esc_attr($r->tier) . '">' . esc_html($r->tier) . '</span></td>
<td>' . esc_html($r->created_at) . '</td></tr>';
        }
        if (empty($rows)) echo '<tr><td colspan="7" style="color:#888">Chưa có khách</td></tr>';
        echo '</tbody></table>
<style>.sao-badge.sao-bronze{background:#cd7f32;color:#fff}.sao-badge.sao-silver{background:#c0c0c0;color:#333}.sao-badge.sao-gold{background:#FFD700;color:#333}.sao-badge.sao-platinum{background:#e5e4e2;color:#333;font-weight:700}</style>
</div>';
    });
});

// =================================================================
// 27. Bulk import CSV for products + inventory
// =================================================================
add_action('admin_menu', function () {
    add_submenu_page('sao-dashboard', 'Bulk Import', '📥 Bulk Import', 'manage_options', 'sao-bulk-import', function () {
        if (!current_user_can('manage_options')) return;
        $msg = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('sao_bulk_import') && !empty($_FILES['csv']['tmp_name'])) {
            $type = sanitize_text_field($_POST['import_type'] ?? 'products');
            $fp = fopen($_FILES['csv']['tmp_name'], 'r');
            $header = fgetcsv($fp);
            $imported = 0; $errors = 0;
            global $wpdb;
            while (($row = fgetcsv($fp)) !== false) {
                if (count($row) < 2) continue;
                $data = array_combine($header, $row);
                try {
                    if ($type === 'products') {
                        $wpdb->insert($wpdb->prefix . 'sao_products', [
                            'name' => $data['name'] ?? 'Unnamed',
                            'slug' => $data['slug'] ?? sanitize_title($data['name'] ?? 'unnamed-' . $imported),
                            'type' => $data['type'] ?? 'day_pass',
                            'brand' => $data['brand'] ?? 'bai_sao',
                            'base_price' => (int)($data['base_price'] ?? 0),
                            'capacity' => (int)($data['capacity'] ?? 100),
                            'short_desc' => $data['short_desc'] ?? '',
                            'status' => $data['status'] ?? 'active',
                        ]);
                    } elseif ($type === 'inventory') {
                        $wpdb->insert($wpdb->prefix . 'sao_inventory', [
                            'product_id' => (int)$data['product_id'],
                            'booking_date' => $data['date'] ?? '',
                            'time_slot' => $data['time_slot'] ?? 'all_day',
                            'total_capacity' => (int)$data['capacity'],
                            'reserved' => 0,
                            'status' => $data['status'] ?? 'open',
                        ]);
                    } elseif ($type === 'coupons') {
                        $wpdb->insert($wpdb->prefix . 'sao_coupons', [
                            'code' => strtoupper($data['code']),
                            'discount_type' => $data['type'] ?? 'percent',
                            'discount_value' => (float)$data['value'],
                            'min_amount' => (float)($data['min_amount'] ?? 0),
                            'valid_from' => $data['valid_from'] ?? null,
                            'valid_to' => $data['valid_to'] ?? null,
                            'max_uses' => isset($data['max_uses']) ? (int)$data['max_uses'] : null,
                            'status' => 'active',
                            'notes' => $data['notes'] ?? '',
                        ]);
                    }
                    $imported++;
                } catch (Throwable $e) {
                    $errors++;
                }
            }
            fclose($fp);
            $msg = "✅ Imported $imported records, $errors errors.";
        }
        ?>
        <div class="wrap">
            <h1>📥 Bulk Import CSV</h1>
            <?php if ($msg): ?><div class="notice notice-success"><p><?= esc_html($msg) ?></p></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <?php wp_nonce_field('sao_bulk_import'); ?>
                <table class="form-table">
                    <tr><th>Import type</th><td>
                        <select name="import_type">
                            <option value="products">Products</option>
                            <option value="inventory">Inventory (capacity by date)</option>
                            <option value="coupons">Coupons</option>
                        </select>
                    </td></tr>
                    <tr><th>CSV file</th><td><input type="file" name="csv" accept=".csv" required></td></tr>
                </table>
                <?php submit_button('Import'); ?>
            </form>
            <hr>
            <h2>CSV Templates</h2>
            <p><strong>Products:</strong> <code>name,slug,type,brand,base_price,capacity,short_desc,status</code></p>
            <p><strong>Inventory:</strong> <code>product_id,date,time_slot,capacity,status</code></p>
            <p><strong>Coupons:</strong> <code>code,type,value,min_amount,valid_from,valid_to,max_uses,notes</code></p>
            <p>Example coupon row: <code>SUMMER2026,percent,15,500000,2026-06-01,2026-09-30,1000,Hè 2026</code></p>
        </div>
        <?php
    });
});

// =================================================================
// 28. Lazy fire sao_booking_paid hook when payment_status updates
// (Theme's vnpay_return doesn't fire this hook — we add it)
// =================================================================
add_action('save_post', function ($post_id, $post) {
    // dummy — actual hook firing happens in payment update logic
}, 10, 2);

// Hook into payment status changes
register_shutdown_function(function () {
    static $ran = false;
    if ($ran) return;
    $ran = true;
});

// =================================================================
// 29. Custom i18n: /en/SLUG → render EN content
// Replaces TranslatePress Google fallback for VI/EN with native content.
// EN content stored in postmeta: _sao_title_en, _sao_content_en, _sao_excerpt_en
// =================================================================
add_action('init', function () {
    return; // DISABLED for Polylang
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#^/en/(.*)$#', $uri, $m)) return;

    $slug = trim($m[1], '/');
    if (!$slug) {
        $slug = 'home'; // /en/ → home EN version
    }

    // Look up post by slug
    global $wpdb;
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_status = 'publish' AND post_type IN ('post','page') LIMIT 1",
        $slug));

    if (!$post_id) {
        // Special case: /en/ → home
        if ($slug === 'home') {
            $post_id = (int) get_option('page_on_front');
        }
    }

    if (!$post_id) {
        // 404 — fallback to redirect to VI version
        wp_redirect(home_url('/' . $slug), 302);
        exit;
    }

    // Set cookie sao_lang=en for header consistency
    setcookie('sao_lang', 'en', time() + 86400 * 365, '/', '', is_ssl(), false);

    // Get EN content from postmeta
    $title_en = get_post_meta($post_id, '_sao_title_en', true);
    $content_en = get_post_meta($post_id, '_sao_content_en', true);
    $excerpt_en = get_post_meta($post_id, '_sao_excerpt_en', true);
    $seo_title_en = get_post_meta($post_id, '_sao_seo_title_en', true);
    $seo_desc_en = get_post_meta($post_id, '_sao_seo_desc_en', true);

    if (!$title_en && !$content_en) {
        // No EN translation available — show notice + VI content + offer Google Translate fallback
        $vi_post = get_post($post_id);
        if (!$vi_post) { http_response_code(404); exit; }
        $title_en = '[VI] ' . $vi_post->post_title;
        $content_en = '<div style="background:#FFF3CD;padding:14px;border-radius:6px;margin-bottom:20px">⚠️ This page does not have an official English translation yet. Showing Vietnamese version. <a href="?gtranslate=1">Use Google Translate</a></div>' . $vi_post->post_content;
    }

    // Render via WP template
    global $post, $wp_query;
    $post = get_post($post_id);
    if ($post) {
        $post->post_title = $title_en ?: $post->post_title;
        $post->post_content = $content_en ?: $post->post_content;
        $post->post_excerpt = $excerpt_en ?: $post->post_excerpt;
    }

    // Force language attribute + meta
    add_filter('language_attributes', fn() => 'lang="en-US"');
    add_filter('document_title_parts', function ($parts) use ($seo_title_en, $title_en) {
        if ($seo_title_en) $parts['title'] = $seo_title_en;
        elseif ($title_en) $parts['title'] = $title_en;
        return $parts;
    });
    add_filter('wpseo_metadesc', fn() => $seo_desc_en ?: $excerpt_en);
    add_filter('the_content', fn($c) => $content_en ?: $c, 5);
    add_filter('the_title', fn($t) => $title_en ?: $t, 5);

    // Make $wp_query think this is a single post
    $wp_query->is_404 = false;
    $wp_query->is_single = (get_post_type($post_id) === 'post');
    $wp_query->is_page = (get_post_type($post_id) === 'page');
    $wp_query->is_singular = true;
    $wp_query->queried_object = $post;
    $wp_query->queried_object_id = $post_id;
    status_header(200);

    // Add hreflang
    add_action('wp_head', function () use ($slug) {
        echo '<link rel="alternate" hreflang="vi" href="' . esc_url(home_url('/' . $slug . '/')) . '">' . "\n";
        echo '<link rel="alternate" hreflang="en-US" href="' . esc_url(home_url('/en/' . $slug . '/')) . '">' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(home_url('/' . $slug . '/')) . '">' . "\n";
    });

    // Let theme render normally
}, 2);

// Add EN editor meta-box for posts/pages
add_action('add_meta_boxes', function () {
    foreach (['post','page'] as $pt) {
        add_meta_box('sao_i18n_en', '🌐 English Translation', function ($post) {
            wp_nonce_field('sao_i18n_save', 'sao_i18n_nonce');
            $title = get_post_meta($post->ID, '_sao_title_en', true);
            $excerpt = get_post_meta($post->ID, '_sao_excerpt_en', true);
            $content = get_post_meta($post->ID, '_sao_content_en', true);
            $seo_title = get_post_meta($post->ID, '_sao_seo_title_en', true);
            $seo_desc = get_post_meta($post->ID, '_sao_seo_desc_en', true);
            ?>
            <p><label><strong>EN Title:</strong></label><br>
            <input type="text" name="_sao_title_en" value="<?= esc_attr($title) ?>" style="width:100%"></p>
            <p><label><strong>EN SEO Title:</strong></label><br>
            <input type="text" name="_sao_seo_title_en" value="<?= esc_attr($seo_title) ?>" style="width:100%"></p>
            <p><label><strong>EN SEO Description:</strong></label><br>
            <textarea name="_sao_seo_desc_en" rows="2" style="width:100%"><?= esc_textarea($seo_desc) ?></textarea></p>
            <p><label><strong>EN Excerpt:</strong></label><br>
            <textarea name="_sao_excerpt_en" rows="3" style="width:100%"><?= esc_textarea($excerpt) ?></textarea></p>
            <p><label><strong>EN Content (HTML):</strong></label><br>
            <textarea name="_sao_content_en" rows="15" style="width:100%;font-family:monospace;font-size:12px"><?= esc_textarea($content) ?></textarea></p>
            <p style="color:#888;font-size:12px">EN URL: <code><?= esc_html(home_url('/en/' . $post->post_name . '/')) ?></code></p>
            <?php
        }, $pt, 'normal', 'high');
    }
});

add_action('save_post', function ($post_id) {
    if (!isset($_POST['sao_i18n_nonce']) || !wp_verify_nonce($_POST['sao_i18n_nonce'], 'sao_i18n_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    foreach (['_sao_title_en','_sao_excerpt_en','_sao_content_en','_sao_seo_title_en','_sao_seo_desc_en'] as $k) {
        if (isset($_POST[$k])) {
            if ($k === '_sao_content_en') {
                update_post_meta($post_id, $k, wp_kses_post($_POST[$k]));
            } else {
                update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
            }
        }
    }
}, 10, 1);

// Add EN flag link in language switcher cookie behavior
add_action('init', function () {
    // If user is on VI URL but cookie=en → redirect to /en/SLUG
    if (isset($_GET['sao_switch_lang'])) {
        return; // DISABLED for Polylang
        $lang = sanitize_text_field($_GET['sao_switch_lang']);
        setcookie('sao_lang', $lang, time() + 86400 * 365, '/', '', is_ssl(), false);
        $back = wp_get_referer() ?: home_url('/');
        wp_redirect($back);
        exit;
    }
});

// Translation coverage report admin page
add_action('admin_menu', function () {
    add_submenu_page('sao-dashboard', 'I18n Coverage', '🌐 EN Translations', 'manage_options', 'sao-i18n', function () {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        echo '<div class="wrap"><h1>🌐 EN Translation Coverage</h1>';
        $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','page')");
        $translated = (int)$wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key='_sao_content_en' AND meta_value != ''");
        $pct = $total > 0 ? round($translated / $total * 100, 1) : 0;
        echo "<p>Translated: <strong>$translated / $total</strong> ($pct%)</p>";
        echo '<div style="background:#eee;height:30px;border-radius:4px;overflow:hidden;margin:20px 0">
<div style="background:#0A7E8C;height:100%;width:' . $pct . '%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:600">' . $pct . '%</div></div>';

        // List
        $rows = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_name, p.post_type,
                (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=p.ID AND meta_key='_sao_content_en' LIMIT 1) AS en_content
            FROM {$wpdb->posts} p
            WHERE post_status='publish' AND post_type IN ('post','page')
            ORDER BY p.post_type, p.ID");
        echo '<table class="wp-list-table widefat striped">
<thead><tr><th>ID</th><th>Type</th><th>VI Title</th><th>EN Status</th><th>EN URL</th><th>Edit</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $has = !empty($r->en_content);
            echo '<tr>
<td>#' . (int)$r->ID . '</td>
<td>' . esc_html($r->post_type) . '</td>
<td>' . esc_html(mb_substr($r->post_title, 0, 60)) . '</td>
<td>' . ($has ? '<span style="color:green">✅ Translated</span>' : '<span style="color:#dc3545">❌ Missing</span>') . '</td>
<td><a href="' . esc_url(home_url('/en/' . $r->post_name . '/')) . '" target="_blank">/en/' . esc_html($r->post_name) . '</a></td>
<td><a href="' . esc_url(get_edit_post_link($r->ID)) . '">Edit</a></td>
</tr>';
        }
        echo '</tbody></table></div>';
    });
});

// =================================================================
// 30. WhatsApp Business Cloud API integration
// Settings via SAO Hardening page. Templates: confirm, reminder, NPS, upsell.
// =================================================================
if (!function_exists('sao_send_whatsapp')) {
    /**
     * Send WhatsApp message via Cloud API.
     * @param string $to_phone E.164 format (e.g. +84901234567)
     * @param string $template_name e.g. "booking_confirm"
     * @param array $params Variables for template
     * @return array ['ok' => bool, 'message_id' => string, 'error' => string]
     */
    function sao_send_whatsapp(string $to_phone, string $template_name, array $params = []): array {
        $token = get_option('sao_whatsapp_token', '');
        $phone_id = get_option('sao_whatsapp_phone_id', '');
        if (!$token || !$phone_id) {
            return ['ok' => false, 'error' => 'WABA not configured'];
        }
        $to = preg_replace('/\D/', '', $to_phone); // Strip non-digits
        if (strlen($to) < 9) return ['ok' => false, 'error' => 'Invalid phone'];
        // Ensure starts with country code (default Vietnam 84)
        if (strpos($to, '0') === 0) $to = '84' . substr($to, 1);

        $url = "https://graph.facebook.com/v18.0/{$phone_id}/messages";
        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $template_name,
                'language' => ['code' => 'vi'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string)$v], $params),
                    ],
                ],
            ],
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => "Bearer $token",
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'error' => $response->get_error_message()];
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!empty($data['messages'][0]['id'])) {
            return ['ok' => true, 'message_id' => $data['messages'][0]['id']];
        }
        return ['ok' => false, 'error' => $data['error']['message'] ?? 'Unknown error'];
    }
}

// Send WhatsApp on booking created (if customer has WhatsApp number)
add_action('sao_booking_created', function ($bid, $data) {
    if (empty($data['phone'])) return;
    if (!get_option('sao_whatsapp_enabled', false)) return;

    $result = sao_send_whatsapp(
        $data['phone'],
        'booking_confirm',
        [
            $data['name'],
            $data['code'],
            $data['date'],
            number_format((int)$data['total']) . 'đ',
        ]
    );

    global $wpdb;
    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
        'whatsapp_sent', "WABA booking_confirm", "code:{$data['code']},msg_id:" . ($result['message_id'] ?? 'none'),
        $result['ok'] ? 'sent' : ('failed: ' . substr($result['error'] ?? '', 0, 100))));
}, 20, 2);

// Send WhatsApp T-1 reminder (extend CRM cron)
add_action('sao_crm_daily', function () {
    if (!get_option('sao_whatsapp_enabled', false)) return;
    global $wpdb;
    $p = $wpdb->prefix . 'sao_';
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT b.* FROM {$p}bookings b
         WHERE b.booking_date = %s
         AND b.payment_status IN ('paid','deposit_paid')
         AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}sao_crm_interactions i
                        WHERE i.notes LIKE CONCAT('%%waba_reminder:', b.booking_code, '%%'))", $tomorrow));
    foreach ($rows as $b) {
        if (!$b->customer_phone) continue;
        $result = sao_send_whatsapp(
            $b->customer_phone,
            'booking_reminder',
            [$b->customer_name, $b->booking_code, $b->booking_date]);
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}sao_crm_interactions (customer_id, type, subject, notes, outcome) VALUES (0,%s,%s,%s,%s)",
            'whatsapp_reminder', "WABA T-1", "waba_reminder:{$b->booking_code}",
            $result['ok'] ? 'sent' : 'failed'));
    }
}, 20);

// Admin: extend settings page with WABA fields
add_action('admin_init', function () {
    register_setting('sao_hardening', 'sao_whatsapp_token');
    register_setting('sao_hardening', 'sao_whatsapp_phone_id');
    register_setting('sao_hardening', 'sao_whatsapp_business_id');
    register_setting('sao_hardening', 'sao_whatsapp_enabled');
});

// Test send endpoint for admin
add_action('admin_init', function () {
    if (!isset($_GET['sao_waba_test']) || !current_user_can('manage_options')) return;
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'sao_waba_test')) wp_die('Bad nonce');
    $to = sanitize_text_field($_GET['to'] ?? '');
    if (!$to) wp_die('Missing ?to=PHONE');
    $result = sao_send_whatsapp($to, 'hello_world', []);
    wp_die('Result: ' . wp_json_encode($result));
});

// =================================================================
// 31. OTA Integration framework — Adapter pattern
// Adapters for: Booking.com (XML/JSON push-pull), Agoda (YCS), Klook
// =================================================================

abstract class SAO_OTA_Adapter {
    abstract public function name(): string;
    abstract public function push_inventory(int $product_id, string $date, int $available): bool;
    abstract public function pull_bookings(): array; // Returns array of booking dicts
    abstract public function push_rate(int $product_id, string $date, int $price): bool;
}

class SAO_OTA_Booking extends SAO_OTA_Adapter {
    public function name(): string { return 'Booking.com'; }

    private function api_call(string $endpoint, array $body): array {
        $username = get_option('sao_ota_booking_user', '');
        $password = get_option('sao_ota_booking_pass', '');
        $hotel_id = get_option('sao_ota_booking_hotel_id', '');
        if (!$username || !$password) return ['error' => 'Booking.com not configured'];

        $url = 'https://supply-xml.booking.com/hotels/xml/' . $endpoint;
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("$username:$password"),
                'Content-Type' => 'application/xml',
            ],
            'body' => $body['xml'] ?? '',
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) return ['error' => $response->get_error_message()];
        $body = wp_remote_retrieve_body($response);
        return ['xml' => $body, 'code' => wp_remote_retrieve_response_code($response)];
    }

    public function push_inventory(int $product_id, string $date, int $available): bool {
        $hotel_id = get_option('sao_ota_booking_hotel_id', '');
        if (!$hotel_id) return false;
        $room_id = get_post_meta($product_id, '_ota_booking_room_id', true);
        if (!$room_id) return false;

        $xml = "<?xml version='1.0'?>
<request>
  <username>" . esc_html(get_option('sao_ota_booking_user')) . "</username>
  <password>" . esc_html(get_option('sao_ota_booking_pass')) . "</password>
  <hotel id='$hotel_id'>
    <room id='$room_id'>
      <date value='$date'><roomstosell>$available</roomstosell></date>
    </room>
  </hotel>
</request>";

        $result = $this->api_call('availability', ['xml' => $xml]);
        return ($result['code'] ?? 0) === 200;
    }

    public function push_rate(int $product_id, string $date, int $price): bool {
        $hotel_id = get_option('sao_ota_booking_hotel_id', '');
        $room_id = get_post_meta($product_id, '_ota_booking_room_id', true);
        if (!$hotel_id || !$room_id) return false;

        // VND to USD conversion (basic)
        $usd = round($price / 25000, 2);

        $xml = "<?xml version='1.0'?>
<request>
  <username>" . esc_html(get_option('sao_ota_booking_user')) . "</username>
  <password>" . esc_html(get_option('sao_ota_booking_pass')) . "</password>
  <hotel id='$hotel_id'>
    <room id='$room_id'>
      <date value='$date'>
        <rate id='1'>
          <price>$usd</price>
          <currency>USD</currency>
        </rate>
      </date>
    </room>
  </hotel>
</request>";

        $result = $this->api_call('rates', ['xml' => $xml]);
        return ($result['code'] ?? 0) === 200;
    }

    public function pull_bookings(): array {
        // Booking.com sends booking via reservation API webhook
        // This pulls last 24h bookings via reservations endpoint
        $hotel_id = get_option('sao_ota_booking_hotel_id', '');
        if (!$hotel_id) return [];

        $xml = "<?xml version='1.0'?>
<request>
  <username>" . esc_html(get_option('sao_ota_booking_user')) . "</username>
  <password>" . esc_html(get_option('sao_ota_booking_pass')) . "</password>
  <hotel id='$hotel_id'/>
  <since>" . date('Y-m-d', strtotime('-1 day')) . "</since>
</request>";

        $result = $this->api_call('reservations', ['xml' => $xml]);
        if (empty($result['xml'])) return [];

        // Parse XML reservations
        try {
            $reservations = simplexml_load_string($result['xml']);
            $out = [];
            foreach ($reservations->reservation ?? [] as $r) {
                $out[] = [
                    'ota_booking_id' => (string)$r['id'],
                    'guest_name' => (string)$r->guest->name,
                    'guest_email' => (string)$r->guest->email,
                    'guest_phone' => (string)$r->guest->phone,
                    'checkin' => (string)$r->checkin,
                    'checkout' => (string)$r->checkout,
                    'total' => (float)$r->total,
                    'currency' => (string)$r->currency,
                ];
            }
            return $out;
        } catch (Throwable $e) { return []; }
    }
}

class SAO_OTA_Agoda extends SAO_OTA_Adapter {
    public function name(): string { return 'Agoda'; }

    public function push_inventory(int $product_id, string $date, int $available): bool {
        $api_key = get_option('sao_ota_agoda_key', '');
        if (!$api_key) return false;
        $hotel_id = get_option('sao_ota_agoda_hotel_id', '');
        $room_id = get_post_meta($product_id, '_ota_agoda_room_id', true);
        if (!$hotel_id || !$room_id) return false;

        $body = [
            'HotelID' => $hotel_id,
            'RoomTypeID' => $room_id,
            'Date' => $date,
            'Availability' => $available,
        ];

        $response = wp_remote_post('https://ycs.agoda.com/api/inventory/update', [
            'headers' => [
                'X-API-Key' => $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 30,
        ]);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    public function push_rate(int $product_id, string $date, int $price): bool {
        // Similar pattern to push_inventory
        $api_key = get_option('sao_ota_agoda_key', '');
        if (!$api_key) return false;
        $hotel_id = get_option('sao_ota_agoda_hotel_id', '');
        $room_id = get_post_meta($product_id, '_ota_agoda_room_id', true);
        if (!$hotel_id || !$room_id) return false;

        $usd = round($price / 25000, 2);
        $body = [
            'HotelID' => $hotel_id,
            'RoomTypeID' => $room_id,
            'Date' => $date,
            'Rate' => $usd,
            'Currency' => 'USD',
        ];

        $response = wp_remote_post('https://ycs.agoda.com/api/rate/update', [
            'headers' => ['X-API-Key' => $api_key, 'Content-Type' => 'application/json'],
            'body' => json_encode($body), 'timeout' => 30]);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    public function pull_bookings(): array {
        $api_key = get_option('sao_ota_agoda_key', '');
        if (!$api_key) return [];
        $hotel_id = get_option('sao_ota_agoda_hotel_id', '');
        $response = wp_remote_get("https://ycs.agoda.com/api/reservations?hotel_id=$hotel_id&since=" . date('c', strtotime('-1 day')), [
            'headers' => ['X-API-Key' => $api_key], 'timeout' => 30]);
        if (is_wp_error($response)) return [];
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $out = [];
        foreach (($data['reservations'] ?? []) as $r) {
            $out[] = [
                'ota_booking_id' => $r['BookingID'] ?? '',
                'guest_name' => $r['GuestName'] ?? '',
                'guest_email' => $r['GuestEmail'] ?? '',
                'guest_phone' => $r['GuestPhone'] ?? '',
                'checkin' => $r['CheckInDate'] ?? '',
                'checkout' => $r['CheckOutDate'] ?? '',
                'total' => (float)($r['TotalAmount'] ?? 0),
                'currency' => $r['Currency'] ?? 'USD',
            ];
        }
        return $out;
    }
}

// OTA sync cron — daily
add_action('init', function () {
    if (!wp_next_scheduled('sao_ota_daily')) {
        wp_schedule_event(strtotime('tomorrow 02:00:00'), 'daily', 'sao_ota_daily');
    }
});

add_action('sao_ota_daily', function () {
    $adapters = [];
    if (get_option('sao_ota_booking_enabled')) $adapters[] = new SAO_OTA_Booking();
    if (get_option('sao_ota_agoda_enabled')) $adapters[] = new SAO_OTA_Agoda();
    if (empty($adapters)) return;

    global $wpdb;
    $p = $wpdb->prefix . 'sao_';

    // Push inventory for next 90 days for OTA-enabled products
    $products = $wpdb->get_results("SELECT p.id FROM {$p}products p INNER JOIN {$wpdb->postmeta} pm ON pm.meta_value = p.id WHERE pm.meta_key = '_ota_sync_enabled' AND pm.meta_value = '1'");
    foreach ($products as $prod) {
        for ($d = 0; $d < 90; $d++) {
            $date = date('Y-m-d', strtotime("+$d days"));
            $inv = $wpdb->get_row($wpdb->prepare(
                "SELECT total_capacity, reserved FROM {$p}inventory WHERE product_id = %d AND booking_date = %s",
                $prod->id, $date));
            if (!$inv) continue;
            $available = max(0, (int)$inv->total_capacity - (int)$inv->reserved);
            // Reserve 20% for OTA channel
            $ota_available = (int) round($available * 0.2);
            foreach ($adapters as $a) {
                $a->push_inventory($prod->id, $date, $ota_available);
            }
        }
    }

    // Pull bookings from OTA
    foreach ($adapters as $a) {
        $bookings = $a->pull_bookings();
        foreach ($bookings as $b) {
            // Check if already imported
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$p}bookings WHERE booking_code = %s",
                'OTA-' . $b['ota_booking_id']));
            if ($existing) continue;

            // Insert as new booking
            $wpdb->insert($p . 'bookings', [
                'booking_code' => 'OTA-' . substr($b['ota_booking_id'], 0, 30),
                'customer_name' => $b['guest_name'],
                'customer_email' => $b['guest_email'],
                'customer_phone' => $b['guest_phone'],
                'booking_date' => $b['checkin'],
                'guests' => 2,
                'total' => $b['total'] * ($b['currency'] === 'USD' ? 25000 : 1),
                'paid' => $b['total'] * ($b['currency'] === 'USD' ? 25000 : 1),
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
                'source' => $a->name(),
                'notes' => "OTA: {$a->name()} reservation {$b['ota_booking_id']}",
            ]);
        }
    }

    update_option('sao_ota_last_sync', current_time('mysql'));
});

// OTA admin page
add_action('admin_menu', function () {
    add_submenu_page('sao-dashboard', 'OTA Integration', '🔗 OTA Sync', 'manage_options', 'sao-ota', function () {
        if (!current_user_can('manage_options')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('sao_ota_save')) {
            foreach (['sao_ota_booking_user','sao_ota_booking_pass','sao_ota_booking_hotel_id','sao_ota_booking_enabled',
                      'sao_ota_agoda_key','sao_ota_agoda_hotel_id','sao_ota_agoda_enabled'] as $k) {
                update_option($k, sanitize_text_field($_POST[$k] ?? ''));
            }
            echo '<div class="notice notice-success"><p>✅ Saved OTA settings.</p></div>';
        }
        $last = get_option('sao_ota_last_sync', 'never');
        ?>
        <div class="wrap">
            <h1>🔗 OTA Integration</h1>
            <p>Distribute Bãi Sao + Glamping inventory to <strong>Booking.com</strong> + <strong>Agoda</strong>.</p>
            <p>Last sync: <code><?= esc_html($last) ?></code></p>

            <form method="POST">
                <?php wp_nonce_field('sao_ota_save'); ?>
                <h2>Booking.com</h2>
                <table class="form-table">
                    <tr><th>Enabled</th><td><label><input type="checkbox" name="sao_ota_booking_enabled" value="1" <?php checked(get_option('sao_ota_booking_enabled') == '1'); ?>> Active</label></td></tr>
                    <tr><th>Username</th><td><input type="text" name="sao_ota_booking_user" value="<?= esc_attr(get_option('sao_ota_booking_user')) ?>" class="regular-text"></td></tr>
                    <tr><th>Password</th><td><input type="password" name="sao_ota_booking_pass" value="<?= esc_attr(get_option('sao_ota_booking_pass')) ?>" class="regular-text"></td></tr>
                    <tr><th>Hotel ID</th><td><input type="text" name="sao_ota_booking_hotel_id" value="<?= esc_attr(get_option('sao_ota_booking_hotel_id')) ?>" class="regular-text"></td></tr>
                </table>

                <h2>Agoda (YCS)</h2>
                <table class="form-table">
                    <tr><th>Enabled</th><td><label><input type="checkbox" name="sao_ota_agoda_enabled" value="1" <?php checked(get_option('sao_ota_agoda_enabled') == '1'); ?>> Active</label></td></tr>
                    <tr><th>API Key</th><td><input type="password" name="sao_ota_agoda_key" value="<?= esc_attr(get_option('sao_ota_agoda_key')) ?>" class="regular-text"></td></tr>
                    <tr><th>Hotel ID</th><td><input type="text" name="sao_ota_agoda_hotel_id" value="<?= esc_attr(get_option('sao_ota_agoda_hotel_id')) ?>" class="regular-text"></td></tr>
                </table>

                <?php submit_button('💾 Save OTA Settings'); ?>
            </form>

            <hr>
            <h2>Setup checklist</h2>
            <ol>
                <li>Sign Booking.com Connectivity Partner Agreement (3-6 months process)</li>
                <li>Get API credentials from Booking.com Extranet → Connectivity API</li>
                <li>Sign Agoda YCS (Yield Connectivity Solution) — agoda.com/partner</li>
                <li>Get YCS API key from Agoda Partner Portal</li>
                <li>Map products to OTA room types (via product postmeta `_ota_booking_room_id` + `_ota_agoda_room_id`)</li>
                <li>Enable `_ota_sync_enabled` for products to distribute</li>
                <li>Test in sandbox first</li>
            </ol>

            <h2>Daily sync details</h2>
            <ul>
                <li>Runs at 02:00 daily via WP cron</li>
                <li>Pushes inventory for next 90 days</li>
                <li>Reserves 20% of available capacity for OTA channel</li>
                <li>Pulls new reservations, imports with prefix `OTA-`</li>
                <li>Currency: VND ↔ USD conversion at 25,000/USD</li>
            </ul>
        </div>
        <?php
    });
});



