<?php
/**
 * Plugin Name: GN Trust Pages
 * Description: Ba tin hieu niem tin chong lua dao cho Ganh Ngon (6 ngon ngu).
 *   1) /p/{ma} — trang dich QR cua doi tac: hien ro AI gioi thieu truoc khi chuyen tiep.
 *   2) /xac-minh/ — khach tu kiem tra ma doi tac in tren the QR co that khong.
 *   3) /tra-cuu/ — khach tra cuu eVoucher: tra loi TACH RIENG hai cau hoi
 *      "da thanh toan chua" va "da su dung chua".
 *   4) Khoi niem tin tren /voucher/.
 *   Khong sua functions.php cua theme. Go bo = xoa file nay.
 *   Ngon ngu: vi/en viet thang trong file; zh/ko/ru/ar lay tu gn-trust-dict.php.
 * Version: 1.2
 */
if (!defined('ABSPATH')) exit;

define('GN_TP_HOTLINE', '0979 232 233');
define('GN_TP_HOTLINE_TEL', '+84979232233');

/* ---------------------------------------------------------------------------
 * Tien ich
 * ------------------------------------------------------------------------- */

function gn_tp_tbl($name) {
    if (function_exists('gn_tbl')) return gn_tbl($name);
    global $wpdb;
    return $wpdb->prefix . 'sao_' . $name;
}

function gn_tp_money($n) {
    return number_format((float) $n, 0, ',', '.') . 'đ';
}

function gn_tp_mask_phone($p) {
    if (function_exists('gn_mask_phone')) return gn_mask_phone($p);
    $p = preg_replace('/\s+/', '', (string) $p);
    if (strlen($p) < 7) return $p;
    return substr($p, 0, 4) . str_repeat('*', max(0, strlen($p) - 7)) . substr($p, -3);
}

/** Ngon ngu dang hien thi: 'en' hoac 'vi'. */
function gn_tp_lang($set = null) {
    static $lang = 'vi';
    if ($set !== null) $lang = in_array($set, array('en','zh','ko','ru','ar'), true) ? $set : 'vi';
    return $lang;
}
/**
 * Chon chuoi theo ngon ngu. Khoa la chuoi tieng Viet goc.
 * Thu tu: tieng Viet -> tu dien (gn-trust-dict.php) -> tieng Anh truyen vao -> tieng Viet.
 */
function gn_tp_t($vi, $en = null) {
    $lang = gn_tp_lang();
    if ($lang === 'vi') return $vi;
    if (function_exists('gn_tp_dict')) {
        $d = gn_tp_dict();
        if (isset($d[$vi][$lang])) return $d[$vi][$lang];
        if ($lang !== 'en' && isset($d[$vi]['en'])) return $d[$vi]['en'];
    }
    if ($en !== null) return $en;
    return $vi;
}

function gn_tp_partner_type_label($t) {
    // Gom ca ma ngan (them tay trong wp-admin) lan gia tri tho cua o select
    // tren form dang ky ngoai web — hai duong ghi vao cung mot bang.
    $map = array(
        'tai-xe'          => 'Tài xế taxi / xe công nghệ',
        'taixe'           => 'Tài xế taxi / xe công nghệ',
        'ks'              => 'Khách sạn / resort',
        'khachsan'        => 'Khách sạn / resort',
        'khach-san'       => 'Khách sạn / resort',
        'hdv'             => 'Hướng dẫn viên du lịch',
        'huong-dan-vien'  => 'Hướng dẫn viên du lịch',
        'kol'             => 'KOL / Travel blogger',
        'cty'             => 'Công ty du lịch',
        'cong-ty-du-lich' => 'Công ty du lịch',
        'hiep-hoi'        => 'Hiệp hội du lịch',
        'agent'           => 'Đại lý',
        'gioi-thieu'      => 'Giới thiệu bằng link/mã',
        'doan-dia-phuong' => 'Đưa đoàn / hợp tác địa phương',
        'tu-van'          => 'Cần tư vấn thêm',
        'khac'            => 'Đối tác',
    );
    $en = array(
        'Tài xế taxi / xe công nghệ' => 'Taxi / ride-hailing driver',
        'Khách sạn / resort'         => 'Hotel / resort',
        'Hướng dẫn viên du lịch'     => 'Tour guide',
        'KOL / Travel blogger'       => 'KOL / travel blogger',
        'Công ty du lịch'            => 'Travel agency',
        'Hiệp hội du lịch'           => 'Tourism association',
        'Đại lý'                     => 'Agent',
        'Giới thiệu bằng link/mã'    => 'Introduce guests with a link or code',
        'Đưa đoàn / hợp tác địa phương' => 'Bring a group or a local partnership',
        'Cần tư vấn thêm'            => 'I need advice',
        'Đối tác'                    => 'Partner',
    );
    $vi = isset($map[$t]) ? $map[$t] : 'Đối tác';
    return gn_tp_t($vi, isset($en[$vi]) ? $en[$vi] : 'Partner');
}

/** Chan do ma: dem so lan HONG theo khoa, cua so 1 gio. */
function gn_tp_rate_ok($bucket, $limit) {
    return (int) get_transient('gn_tp_' . md5($bucket)) < $limit;
}
function gn_tp_rate_bump($bucket) {
    $key = 'gn_tp_' . md5($bucket);
    set_transient($key, ((int) get_transient($key)) + 1, HOUR_IN_SECONDS);
}
function gn_tp_client_ip() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR') as $k) {
        if (!empty($_SERVER[$k])) {
            $parts = explode(',', $_SERVER[$k]);
            $ip = trim($parts[0]);
            break;
        }
    }
    return $ip;
}

/** Icon ve tay, cung net 1.75 — khong dung ky tu Unicode lam icon. */
function gn_tp_icon($name) {
    $o = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
       . ' stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="tp-ico" aria-hidden="true"';
    if ($name === 'check') {
        return '<svg ' . $o . '><path d="M20 6 9 17l-5-5"/></svg>';
    }
    if ($name === 'alert') {
        return '<svg ' . $o . '><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>'
             . '<path d="M12 9v4"/><path d="M12 17h.01"/></svg>';
    }
    return '';
}

function gn_tp_css() {
    static $done = false;
    if ($done) return '';
    $done = true;
    return '<style>
    .gntp{--tp-ink:var(--gn-text,#1B2A4A);--tp-soft:var(--gn-text-soft,#6b7280);
      --tp-line:var(--gn-border,#e5e0d6);--tp-gold:var(--gn-gold,#C9A96E);--tp-cream:var(--gn-cream,#F5F0E8);
      max-width:660px;margin:0 auto;padding:8px 0 40px;color:var(--tp-ink)}
    .gntp h2{margin:0 0 6px;font-size:26px;line-height:1.25}
    .gntp .tp-lead{color:var(--tp-soft);margin:0 0 22px;line-height:1.6}
    .gntp > .tp-lead:first-child{font-size:17px;color:var(--tp-ink)}
    .gntp form{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 8px}
    .gntp input[type=text]{flex:1;min-width:220px;padding:14px 16px;font-size:17px;letter-spacing:.06em;
      border:1px solid var(--tp-line);border-radius:12px;background:#fff;color:var(--tp-ink)}
    .gntp input[type=text]:focus{outline:2px solid var(--tp-gold);outline-offset:1px}
    .gntp button{padding:14px 22px;font-size:16px;font-weight:600;border:0;border-radius:12px;
      background:var(--tp-gold);color:#fff;cursor:pointer}
    .gntp .tp-hint{font-size:13px;color:var(--tp-soft);margin:0 0 26px}
    .gntp .tp-card{border:1px solid var(--tp-line);border-radius:16px;padding:22px;background:#fff;margin:0 0 18px;
      box-shadow:0 1px 2px rgba(0,0,0,.04),0 8px 20px -14px rgba(0,0,0,.16)}
    .gntp .tp-ok{border-color:#8fc4a6;background:#f6fbf8}
    .gntp .tp-bad{border-color:#dda79f;background:#fdf7f6}
    .gntp .tp-warn{border-color:#e0bd84;background:#fdfaf3}
    .gntp .tp-badge{display:inline-block;font-size:12px;font-weight:700;letter-spacing:.08em;
      text-transform:uppercase;padding:5px 11px;border-radius:999px;background:var(--tp-cream);color:var(--tp-ink)}
    .gntp .tp-badge.is-ok{background:#e4f3ea;color:#136c3a}
    .gntp .tp-badge.is-warn{background:#fdf1dc;color:#8a5a00}
    .gntp .tp-badge.is-err{background:#fbe6e3;color:#96271a}
    .gntp .tp-badge.is-off{background:#eceff3;color:#4a5568}
    .gntp .tp-two{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:0 0 18px}
    @media(max-width:560px){.gntp .tp-two{grid-template-columns:1fr}}
    .gntp .tp-q{border:1px solid var(--tp-line);border-radius:16px;padding:18px 20px;background:#fff}
    .gntp .tp-q p{margin:0 0 10px;font-size:13px;color:var(--tp-soft);text-transform:uppercase;letter-spacing:.06em}
    .gntp .tp-q b{display:block;font-size:19px;line-height:1.35}
    .gntp dl{display:grid;grid-template-columns:auto 1fr;gap:10px 18px;margin:16px 0 0;font-size:15px}
    .gntp dt{color:var(--tp-soft)}
    .gntp dd{margin:0;text-align:right;font-variant-numeric:tabular-nums}
    .gntp table{width:100%;border-collapse:collapse;margin-top:10px;font-size:14px}
    .gntp th,.gntp td{padding:9px 6px;border-bottom:1px solid var(--tp-line);text-align:left}
    .gntp td:last-child,.gntp th:last-child{text-align:right;font-variant-numeric:tabular-nums}
    .gntp .tp-call{display:inline-block;margin-top:14px;padding:13px 22px;border-radius:12px;
      background:var(--tp-ink);color:#fff;text-decoration:none;font-weight:600}
    .gntp .tp-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;letter-spacing:.05em;
      font-variant-numeric:tabular-nums}
    .gntp p,.gntp li{max-width:68ch}
    .gntp[dir=rtl] p,.gntp[dir=rtl] li{margin-left:auto}
    .gntp .tp-ico{width:18px;height:18px;flex:0 0 18px;vertical-align:-3px;margin-right:7px}
    .gntp[dir=rtl] .tp-ico{margin:0 0 0 7px}
    .gntp .tp-badge{display:inline-flex;align-items:center;gap:6px}
    .gntp ::selection{background:var(--tp-gold);color:#fff}
    .gntp{caret-color:var(--tp-gold);scrollbar-color:var(--tp-line) transparent;scrollbar-width:thin}
    .gntp :focus-visible{outline:2px solid var(--tp-gold);outline-offset:2px;border-radius:6px}
    .gntp table{font-variant-numeric:tabular-nums}
    .gntp[dir=rtl]{text-align:right}
    .gntp[dir=rtl] dd{text-align:left}
    .gntp[dir=rtl] th,.gntp[dir=rtl] td{text-align:right}
    .gntp[dir=rtl] td:last-child,.gntp[dir=rtl] th:last-child{text-align:left}
    .gntp[dir=rtl] ul,.gntp[dir=rtl] ol{padding-left:0;padding-right:20px}
    </style>';
}

/** Tieng A-rap doc tu phai sang trai. */
function gn_tp_dir_attr() {
    return gn_tp_lang() === 'ar' ? ' dir="rtl"' : '';
}

/** Dong cuoi ve ten mien chinh thuc — dung o ca ba trang. */
function gn_tp_domain_note() {
    ob_start(); ?>
    <div class="tp-card">
      <p class="tp-lead" style="margin:0"><?php
        echo sprintf(gn_tp_t(
          'Mọi liên kết chính thức của Gánh Ngon đều nằm trên tên miền <b>ganhngon.com</b>. Nếu quý khách nhận được tin nhắn mang tên Gánh Ngon nhưng chứa tên miền khác, vui lòng gọi %s để xác minh trước khi thanh toán.',
          'Every official Gánh Ngon link lives on the <b>ganhngon.com</b> domain. If you receive a message using our name but a different domain, please call %s to verify before paying anything.'
        ), '<b>' . GN_TP_HOTLINE . '</b>'); ?></p>
    </div>
    <?php
    return ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * 1. /p/{ma} — trang dich QR cua doi tac  (viec P0-2)
 *    Uu tien 9, chay truoc handler cua theme (uu tien 10) de thay the hanh vi
 *    chuyen huong thang. Van ghi luot quet va dat cookie quy nhu cu.
 * ------------------------------------------------------------------------- */

add_action('template_redirect', function () {
    $code = get_query_var('gn_ref_code');
    if (!$code) return;

    if (function_exists('pll_current_language')) {
        gn_tp_lang(pll_current_language());
    }

    $target = get_query_var('gn_ref_target');
    if (!$target) $target = 'dat-ban';
    $map = array('dat-ban' => '/dat-ban/', 'voucher' => '/voucher/', 'thuc-don' => '/thuc-don/');
    $dest = home_url(isset($map[$target]) ? $map[$target] : '/dat-ban/');

    $partner = function_exists('gn_partner_by_code') ? gn_partner_by_code($code) : null;

    if ($partner) {
        global $wpdb;
        if (function_exists('gn_set_ref_cookie')) gn_set_ref_cookie($partner->ref_code);
        $wpdb->insert(gn_tp_tbl('scans'), array(
            'partner_id' => $partner->id,
            'medium'     => sanitize_text_field(isset($_GET['m']) ? $_GET['m'] : 'digital'),
            'target'     => $target,
            'ua_hash'    => md5((isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . date('Y-m-d')),
            'scanned_on' => current_time('Y-m-d'),
        ));
    }

    status_header($partner ? 200 : 404);
    nocache_headers();

    $ten = $partner ? ($partner->contact ? $partner->contact : $partner->company) : '';
    $title = $partner
        ? sprintf(gn_tp_t('Bạn được giới thiệu bởi %s', 'You were referred by %s'), $ten)
        : gn_tp_t('Không tìm thấy mã đối tác', 'Partner code not found');

    gn_tp_render_shell($title, gn_tp_scan_html($partner, $code, $dest, $target));
    exit;
}, 9);

function gn_tp_scan_html($partner, $code, $dest, $target) {
    ob_start();
    echo gn_tp_css();
    echo '<div class="gntp"' . gn_tp_dir_attr() . '>';

    if (!$partner) {
        ?>
        <div class="tp-card tp-bad">
          <span class="tp-badge is-err"><?php echo gn_tp_icon('alert'); ?><?php echo gn_tp_t('Cảnh báo', 'Warning'); ?></span>
          <h2 style="margin-top:12px"><?php echo gn_tp_t('Không có đối tác nào mang mã này', 'No partner holds this code'); ?></h2>
          <p class="tp-lead"><?php echo sprintf(gn_tp_t(
              'Mã %s không nằm trong danh sách đối tác chính thức của Gánh Ngon.',
              'The code %s is not on the official Gánh Ngon partner list.'
          ), '<b class="tp-code">' . esc_html($code) . '</b>'); ?></p>
          <p class="tp-lead"><?php echo gn_tp_t(
              'Nếu quý khách đang cầm một thẻ QR mang mã này, rất có thể đó là ấn phẩm giả mạo. <b>Vui lòng KHÔNG thanh toán cho người đưa thẻ</b> và gọi ngay cho nhà hàng để xác minh.',
              'If you are holding a QR card with this code, it is most likely a counterfeit. <b>Please DO NOT pay the person who gave it to you</b> and call the restaurant to verify.'
          ); ?></p>
          <a class="tp-call" href="tel:<?php echo GN_TP_HOTLINE_TEL; ?>"><?php
            echo sprintf(gn_tp_t('Gọi Gánh Ngon: %s', 'Call Gánh Ngon: %s'), GN_TP_HOTLINE); ?></a>
        </div>
        <div class="tp-card">
          <p class="tp-lead" style="margin:0"><?php echo gn_tp_t(
              'Quý khách vẫn có thể đặt bàn trực tiếp với nhà hàng — không cần qua bất kỳ trung gian nào.',
              'You can still book directly with the restaurant — no middleman required.'
          ); ?></p>
          <a class="tp-call" href="<?php echo esc_url(home_url('/dat-ban/')); ?>"><?php echo gn_tp_t('Đặt bàn tại ganhngon.com', 'Book at ganhngon.com'); ?></a>
        </div>
        <?php
    } else {
        $ten = $partner->contact ? $partner->contact : $partner->company;
        $since = !empty($partner->created_at) ? date_i18n('m/Y', strtotime($partner->created_at)) : '';
        ?>
        <div class="tp-card tp-ok">
          <span class="tp-badge is-ok"><?php echo gn_tp_icon('check'); ?><?php echo gn_tp_t('Đối tác chính thức của Gánh Ngon', 'Official Gánh Ngon partner'); ?></span>
          <h2 style="margin-top:14px"><?php
            echo sprintf(gn_tp_t('Bạn được giới thiệu bởi %s', 'You were referred by %s'), esc_html($ten)); ?></h2>
          <p class="tp-lead" style="margin-bottom:0">
            <?php echo esc_html(gn_tp_partner_type_label($partner->type)); ?>
            <?php if (!empty($partner->area)) { echo ' · ' . esc_html($partner->area); } ?>
          </p>
          <dl>
            <dt><?php echo gn_tp_t('Mã đối tác', 'Partner code'); ?></dt>
            <dd><b class="tp-code"><?php echo esc_html($partner->ref_code); ?></b></dd>
            <?php if ($since) { ?>
              <dt><?php echo gn_tp_t('Tham gia từ', 'Partner since'); ?></dt><dd><?php echo esc_html($since); ?></dd>
            <?php } ?>
            <dt><?php echo gn_tp_t('Trạng thái', 'Status'); ?></dt>
            <dd><span class="tp-badge is-ok"><?php echo gn_tp_t('Đang hoạt động', 'Active'); ?></span></dd>
          </dl>
        </div>
        <div class="tp-card">
          <p class="tp-lead" style="margin-bottom:14px"><?php echo gn_tp_t(
              'Đơn của quý khách sẽ được ghi nhận cho người giới thiệu. Quý khách <b>không phải trả thêm đồng nào</b> vì điều này.',
              'Your booking will be credited to this partner. It costs you <b>nothing extra</b>.'
          ); ?></p>
          <a class="tp-call" href="<?php echo esc_url($dest); ?>" style="background:var(--tp-gold)">
            <?php
            if ($target === 'voucher')      echo gn_tp_t('Xem voucher Gánh Ngon', 'View Gánh Ngon vouchers');
            elseif ($target === 'thuc-don') echo gn_tp_t('Xem thực đơn', 'View the menu');
            else                            echo gn_tp_t('Tiếp tục đặt bàn', 'Continue to booking');
            ?>
          </a>
          <p class="tp-hint" style="margin:14px 0 0"><?php echo sprintf(gn_tp_t(
              'Mọi liên kết chính thức của Gánh Ngon đều nằm trên tên miền <b>ganhngon.com</b>. Nghi ngờ ấn phẩm giả? Gọi %s.',
              'Every official Gánh Ngon link lives on <b>ganhngon.com</b>. Suspect a fake card? Call %s.'
          ), GN_TP_HOTLINE); ?></p>
        </div>
        <?php
    }
    echo '</div>';
    return ob_get_clean();
}

function gn_tp_render_shell($title, $html) {
    add_filter('pre_get_document_title', function () use ($title) {
        return $title . ' — Gánh Ngon Phú Quốc';
    });
    add_filter('wp_robots', function ($r) { $r['noindex'] = true; return $r; });
    get_header();
    echo '<main style="padding:34px 18px">' . $html . '</main>';
    get_footer();
}

/* ---------------------------------------------------------------------------
 * 2. /xac-minh/  (P1-3)   3. /tra-cuu/  (P1-2)
 *    Gan noi dung theo duong dan, giong cach theme lam voi /voucher/.
 *    Ho tro ca ban dich: tra-cuu-en, xac-minh-en, ...
 * ------------------------------------------------------------------------- */

add_filter('the_content', function ($content) {
    if (is_admin() || !in_the_loop() || !is_main_query() || !is_page()) return $content;
    $slug = get_post_field('post_name', get_queried_object_id());
    if (!preg_match('/^(tra-cuu|xac-minh)(?:-(en|zh|ko|ru|ar))?$/', $slug, $m)) return $content;

    gn_tp_lang(isset($m[2]) && $m[2] !== '' ? $m[2] : 'vi');
    return $m[1] === 'xac-minh' ? gn_tp_verify_page() : gn_tp_lookup_page();
}, 6);

function gn_tp_verify_page() {
    $code = isset($_GET['ma']) ? strtoupper(trim(sanitize_text_field($_GET['ma']))) : '';
    $self = get_permalink(get_queried_object_id());   // dung cho ca 6 ban dich
    ob_start();
    echo gn_tp_css();
    ?>
    <div class="gntp"<?php echo gn_tp_dir_attr(); ?>>
      <p class="tp-lead"><?php echo gn_tp_t(
          'Nhập mã đối tác in trên thẻ QR quý khách đang cầm để kiểm tra người giới thiệu có thật hay không. Không cần đăng nhập.',
          'Enter the partner code printed on the QR card you are holding to check whether the referrer is genuine. No login needed.'
      ); ?></p>

      <form method="get" action="<?php echo esc_url($self); ?>">
        <input type="text" name="ma" value="<?php echo esc_attr($code); ?>"
               placeholder="GN-P001" autocomplete="off" spellcheck="false"
               aria-label="<?php echo esc_attr(gn_tp_t('Mã đối tác', 'Partner code')); ?>">
        <button type="submit"><?php echo gn_tp_t('Kiểm tra', 'Check'); ?></button>
      </form>
      <p class="tp-hint"><?php echo sprintf(gn_tp_t(
          'Mã đối tác có dạng %s, in ngay dưới mã QR trên thẻ.',
          'Partner codes look like %s and are printed just below the QR code.'
      ), '<b class="tp-code">GN-Pxxx</b>'); ?></p>

      <?php
      if ($code !== '') {
          $ip = gn_tp_client_ip();
          if (!gn_tp_rate_ok('vip:' . $ip, 40)) {
              echo '<div class="tp-card tp-warn"><b>' . gn_tp_t('Quý khách đã tra quá nhiều lần.', 'Too many lookups.')
                 . '</b><p class="tp-lead" style="margin:8px 0 0">'
                 . sprintf(gn_tp_t('Vui lòng thử lại sau một giờ, hoặc gọi %s để được hỗ trợ ngay.',
                                   'Please try again in an hour, or call %s for immediate help.'), GN_TP_HOTLINE)
                 . '</p></div>';
          } else {
              $p = function_exists('gn_partner_by_code') ? gn_partner_by_code($code) : null;
              if ($p) {
                  $ten = $p->contact ? $p->contact : $p->company;
                  $since = !empty($p->created_at) ? date_i18n('m/Y', strtotime($p->created_at)) : '';
                  ?>
                  <div class="tp-card tp-ok">
                    <span class="tp-badge is-ok"><?php echo gn_tp_icon('check'); ?><?php echo gn_tp_t('Đối tác chính thức của Gánh Ngon', 'Official Gánh Ngon partner'); ?></span>
                    <h2 style="margin-top:14px"><?php echo esc_html($ten); ?></h2>
                    <p class="tp-lead" style="margin-bottom:0"><?php echo esc_html(gn_tp_partner_type_label($p->type)); ?>
                      <?php if (!empty($p->area)) { echo ' · ' . esc_html($p->area); } ?></p>
                    <dl>
                      <dt><?php echo gn_tp_t('Mã đối tác', 'Partner code'); ?></dt>
                      <dd><b class="tp-code"><?php echo esc_html($p->ref_code); ?></b></dd>
                      <?php if ($since) { ?>
                        <dt><?php echo gn_tp_t('Tham gia từ', 'Partner since'); ?></dt><dd><?php echo esc_html($since); ?></dd>
                      <?php } ?>
                      <dt><?php echo gn_tp_t('Trạng thái', 'Status'); ?></dt>
                      <dd><span class="tp-badge is-ok"><?php echo gn_tp_t('Đang hoạt động', 'Active'); ?></span></dd>
                    </dl>
                    <a class="tp-call" href="tel:<?php echo GN_TP_HOTLINE_TEL; ?>"><?php
                      echo sprintf(gn_tp_t('Gọi Gánh Ngon xác minh: %s', 'Call Gánh Ngon to confirm: %s'), GN_TP_HOTLINE); ?></a>
                  </div>
                  <?php
              } else {
                  gn_tp_rate_bump('vip:' . $ip);
                  ?>
                  <div class="tp-card tp-bad">
                    <span class="tp-badge is-err"><?php echo gn_tp_icon('alert'); ?><?php echo gn_tp_t('Cảnh báo', 'Warning'); ?></span>
                    <h2 style="margin-top:12px"><?php echo gn_tp_t('Không có đối tác nào mang mã này', 'No partner holds this code'); ?></h2>
                    <p class="tp-lead"><?php echo sprintf(gn_tp_t(
                        'Nếu quý khách đang cầm một thẻ QR mang mã %s, rất có thể đó là ấn phẩm giả mạo.',
                        'If you are holding a QR card with the code %s, it is most likely a counterfeit.'
                    ), '<b class="tp-code">' . esc_html($code) . '</b>'); ?></p>
                    <p class="tp-lead"><?php echo gn_tp_t(
                        '<b>Vui lòng KHÔNG thanh toán</b> và gọi ngay cho nhà hàng.',
                        '<b>Please DO NOT pay</b> and call the restaurant right away.'
                    ); ?></p>
                    <a class="tp-call" href="tel:<?php echo GN_TP_HOTLINE_TEL; ?>"><?php
                      echo sprintf(gn_tp_t('Gọi ngay %s', 'Call now %s'), GN_TP_HOTLINE); ?></a>
                  </div>
                  <?php
              }
          }
      }
      echo gn_tp_domain_note();
      ?>
    </div>
    <?php
    return ob_get_clean();
}

function gn_tp_lookup_page() {
    global $wpdb;
    $code = isset($_GET['ma']) ? strtoupper(trim(sanitize_text_field($_GET['ma']))) : '';
    $self = get_permalink(get_queried_object_id());   // dung cho ca 6 ban dich
    ob_start();
    echo gn_tp_css();
    ?>
    <div class="gntp"<?php echo gn_tp_dir_attr(); ?>>
      <p class="tp-lead"><?php echo gn_tp_t(
          'Nhập mã để xem <b>đã thanh toán chưa</b>, <b>đã sử dụng chưa</b> và còn lại bao nhiêu. Không cần đăng nhập.',
          'Enter your code to see <b>whether it is paid</b>, <b>whether it has been used</b>, and how much is left. No login needed.'
      ); ?></p>

      <form method="get" action="<?php echo esc_url($self); ?>">
        <input type="text" name="ma" value="<?php echo esc_attr($code); ?>"
               placeholder="GN-XXXX-XXXX-XXXX" autocomplete="off" spellcheck="false"
               aria-label="<?php echo esc_attr(gn_tp_t('Mã eVoucher', 'eVoucher code')); ?>">
        <button type="submit"><?php echo gn_tp_t('Kiểm tra mã', 'Check code'); ?></button>
      </form>
      <p class="tp-hint"><?php echo gn_tp_t(
          'Mã eVoucher nằm trong email hoặc tin nhắn nhà hàng gửi khi quý khách đặt mua.',
          'Your eVoucher code is in the email or SMS we sent when you ordered.'
      ); ?></p>

      <?php
      if ($code !== '') {
          $ip = gn_tp_client_ip();
          if (!gn_tp_rate_ok('vip:' . $ip, 20) || !gn_tp_rate_ok('vcode:' . $code, 5)) {
              echo '<div class="tp-card tp-warn"><b>' . gn_tp_t('Đã tra sai quá nhiều lần.', 'Too many failed lookups.')
                 . '</b><p class="tp-lead" style="margin:8px 0 0">'
                 . sprintf(gn_tp_t('Vui lòng thử lại sau một giờ, hoặc gọi %s để nhân viên tra giúp ngay.',
                                   'Please try again in an hour, or call %s and our staff will look it up for you.'), GN_TP_HOTLINE)
                 . '</p></div>';
          } else {
              $v = $wpdb->get_row($wpdb->prepare(
                  "SELECT * FROM " . gn_tp_tbl('vouchers') . " WHERE code = %s", $code));
              if (!$v) {
                  gn_tp_rate_bump('vip:' . $ip);
                  gn_tp_rate_bump('vcode:' . $code);
                  ?>
                  <div class="tp-card tp-bad">
                    <span class="tp-badge is-err"><?php echo gn_tp_t('Không tìm thấy', 'Not found'); ?></span>
                    <h2 style="margin-top:12px"><?php echo gn_tp_t('Không tìm thấy mã này', 'We could not find this code'); ?></h2>
                    <p class="tp-lead"><?php echo gn_tp_t(
                        'Kiểm tra lại từng ký tự, hoặc gọi nhà hàng để được tra giúp.',
                        'Check each character again, or call the restaurant and we will look it up for you.'
                    ); ?></p>
                    <a class="tp-call" href="tel:<?php echo GN_TP_HOTLINE_TEL; ?>"><?php
                      echo sprintf(gn_tp_t('Gọi %s', 'Call %s'), GN_TP_HOTLINE); ?></a>
                  </div>
                  <?php
              } else {
                  echo gn_tp_voucher_result($v);
              }
          }
      }
      echo gn_tp_domain_note();
      ?>
    </div>
    <?php
    return ob_get_clean();
}

/** Hai cau hoi TACH RIENG — diem cot loi cua trang tra cuu. */
function gn_tp_voucher_result($v) {
    global $wpdb;
    $today = current_time('Y-m-d');
    $expired = !empty($v->expires_on) && $v->expires_on < $today;

    // Cau 1: tien da vao chua?
    if ($v->status === 'refunded')   $pay = array(gn_tp_t('Đã hoàn tiền', 'Refunded'), 'is-err');
    elseif ($v->status === 'unpaid') $pay = array(gn_tp_t('Chưa — trả tại quán', 'Not yet — pay at the restaurant'), 'is-warn');
    else                             $pay = array(gn_tp_t('Đã thanh toán', 'Paid'), 'is-ok');

    // Cau 2: voucher con dung duoc khong?
    // Luu y: voucher CHUA THANH TOAN co balance = 0 vi so du chi ghi khi khach tra tien.
    // Khong duoc hieu nham thanh "da dung het" — khach se tuong mat tien.
    $face = (float) $v->face_value;
    $bal  = (float) $v->balance;
    if ($v->status === 'refunded')                $use = array(gn_tp_t('Đã hoàn tiền', 'Refunded'), 'is-err');
    elseif ($expired || $v->status === 'expired') $use = array(gn_tp_t('Hết hạn', 'Expired'), 'is-err');
    elseif ($v->status === 'unpaid')              $use = array(gn_tp_t('Chưa sử dụng', 'Not used yet'), 'is-ok');
    elseif ($v->status === 'used' || $bal <= 0)   $use = array(gn_tp_t('Đã dùng hết', 'Fully used'), 'is-off');
    elseif ($face > 0 && $bal < $face)            $use = array(gn_tp_t('Đã dùng một phần', 'Partially used'), 'is-warn');
    else                                          $use = array(gn_tp_t('Chưa sử dụng', 'Not used yet'), 'is-ok');

    $txn = $wpdb->get_results($wpdb->prepare(
        "SELECT kind, note, amount, happened_on FROM " . gn_tp_tbl('voucher_txn') . "
         WHERE voucher_id = %d ORDER BY id DESC LIMIT 30", $v->id));

    $kindLabel = array(
        'issue'  => gn_tp_t('Phát hành', 'Issued'),
        'pay'    => gn_tp_t('Thanh toán', 'Paid'),
        'topup'  => gn_tp_t('Nạp thêm', 'Top-up'),
        'redeem' => gn_tp_t('Trừ khi dùng', 'Redeemed'),
        'refund' => gn_tp_t('Đã hoàn tiền', 'Refunded'),
        'expire' => gn_tp_t('Hết hạn', 'Expired'),
    );

    ob_start(); ?>
    <div class="tp-two">
      <div class="tp-q">
        <p><?php echo gn_tp_t('Đã thanh toán chưa?', 'Is it paid?'); ?></p>
        <b><span class="tp-badge <?php echo esc_attr($pay[1]); ?>"><?php echo esc_html($pay[0]); ?></span></b>
      </div>
      <div class="tp-q">
        <p><?php echo gn_tp_t('Đã sử dụng chưa?', 'Has it been used?'); ?></p>
        <b><span class="tp-badge <?php echo esc_attr($use[1]); ?>"><?php echo esc_html($use[0]); ?></span></b>
      </div>
    </div>

    <div class="tp-card">
      <span class="tp-badge"><?php echo gn_tp_t('Mã ', 'Code '); ?><b class="tp-code"><?php echo esc_html($v->code); ?></b></span>
      <dl>
        <?php if ($v->status === 'unpaid') { ?>
          <dt><?php echo gn_tp_t('Giá trị khi thanh toán', 'Value once paid'); ?></dt>
          <dd><b style="font-size:19px"><?php echo esc_html(gn_tp_money($face)); ?></b></dd>
          <dt><?php echo gn_tp_t('Số dư hiện tại', 'Current balance'); ?></dt>
          <dd><?php echo gn_tp_t('Chưa có — số dư được ghi khi quý khách trả tiền',
                                 'None yet — the balance is credited when you pay'); ?></dd>
        <?php } else { ?>
          <dt><?php echo gn_tp_t('Số dư còn lại', 'Remaining balance'); ?></dt>
          <dd><b style="font-size:19px"><?php echo esc_html(gn_tp_money($bal)); ?></b></dd>
          <dt><?php echo gn_tp_t('Mệnh giá', 'Face value'); ?></dt>
          <dd><?php echo esc_html(gn_tp_money($face)); ?></dd>
        <?php } ?>
        <?php if ($v->status === 'unpaid' && !empty($v->hold_until)) { ?>
          <dt><?php echo gn_tp_t('Giữ chỗ đến', 'Held until'); ?></dt>
          <dd><?php echo esc_html(date_i18n('d/m/Y', strtotime($v->hold_until))); ?></dd>
        <?php } ?>
        <?php if (!empty($v->expires_on)) { ?>
          <dt><?php echo gn_tp_t('Hạn sử dụng', 'Valid until'); ?></dt>
          <dd><?php echo esc_html(date_i18n('d/m/Y', strtotime($v->expires_on))); ?></dd>
        <?php } ?>
        <?php if (!empty($v->buyer_phone)) { ?>
          <dt><?php echo gn_tp_t('Điện thoại chủ thẻ', 'Holder phone'); ?></dt>
          <dd><?php echo esc_html(gn_tp_mask_phone($v->buyer_phone)); ?></dd>
        <?php } ?>
      </dl>
      <?php if ($v->status === 'unpaid') { ?>
        <p class="tp-hint" style="margin:16px 0 0"><?php echo gn_tp_t(
            'Voucher đang ở trạng thái <b>giữ chỗ</b>. Quý khách trả tiền tại quầy khi đến là dùng được ngay — không mất phí nếu đổi ý.',
            'This voucher is <b>on hold</b>. Pay at the counter when you arrive and it works immediately — no charge if you change your mind.'
        ); ?></p>
      <?php } ?>
    </div>

    <?php if ($txn) { ?>
    <div class="tp-card">
      <b><?php echo gn_tp_t('Lịch sử giao dịch', 'Transaction history'); ?></b>
      <table>
        <thead><tr>
          <th><?php echo gn_tp_t('Loại', 'Type'); ?></th>
          <th><?php echo gn_tp_t('Ghi chú', 'Note'); ?></th>
          <th><?php echo gn_tp_t('Thời gian', 'Date'); ?></th>
          <th><?php echo gn_tp_t('Số tiền', 'Amount'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($txn as $t) { ?>
          <tr>
            <td><?php echo esc_html(isset($kindLabel[$t->kind]) ? $kindLabel[$t->kind] : $t->kind); ?></td>
            <td style="color:var(--tp-soft)"><?php echo esc_html($t->note); ?></td>
            <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($t->happened_on))); ?></td>
            <td><?php echo esc_html(gn_tp_money($t->amount)); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
    <?php } ?>

    <div class="tp-card">
      <p class="tp-lead" style="margin:0 0 4px"><?php echo gn_tp_t(
          'Số liệu chưa đúng? Gọi nhà hàng, nhân viên tra lại ngay tại quầy.',
          'Numbers look wrong? Call us and our staff will re-check at the counter.'
      ); ?></p>
      <a class="tp-call" href="tel:<?php echo GN_TP_HOTLINE_TEL; ?>"><?php
        echo sprintf(gn_tp_t('Gọi %s', 'Call %s'), GN_TP_HOTLINE); ?></a>
    </div>
    <?php
    return ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * 4. Khoi niem tin tren trang /voucher/  (viec P1-4)
 *    Chen NGAY TREN phan chon menh gia, tuc la tren nut mua.
 * ------------------------------------------------------------------------- */

add_filter('the_content', function ($content) {
    if (is_admin() || !in_the_loop() || !is_main_query() || !is_page()) return $content;
    if (get_post_field('post_name', get_queried_object_id()) !== 'voucher') return $content;
    if (strpos($content, 'gn-tp-trust') !== false) return $content;

    $block = gn_tp_css() . '
    <div class="gntp" style="max-width:760px">
      <div class="tp-card gn-tp-trust" style="background:var(--tp-cream)">
        <b style="font-size:18px">Yên tâm khi đặt</b>
        <ul style="margin:12px 0 0;padding-left:20px;line-height:1.85">
          <li><b>Giữ chỗ 0đ</b> — quý khách có thể chọn thanh toán tại quán, không cần trả trước đồng nào.</li>
          <li>Huỷ miễn phí trước giờ hẹn 4 tiếng. Hoàn 100% nếu nhà hàng không phục vụ được.</li>
          <li>Voucher có hạn 12 tháng, dùng được nhiều lần đến hết số dư.</li>
          <li>Chúng tôi khuyến khích quý khách gọi <b>' . GN_TP_HOTLINE . '</b> kiểm tra trước khi thanh toán.</li>
        </ul>
        <p style="margin:14px 0 0">Đã có mã eVoucher?
          <a href="' . esc_url(home_url('/tra-cuu/')) . '"><b>Kiểm tra tại ganhngon.com/tra-cuu</b></a></p>
      </div>
      ' . gn_tp_payment_block() . '
    </div>';

    $anchor = '<section class="gnp-sec">';
    $at = strpos($content, $anchor);
    if ($at === false) return $block . $content;
    return substr($content, 0, $at) . $block . substr($content, $at);
}, 7);

/* ---------------------------------------------------------------------------
 * 5. Hai cach tra tien + tai khoan nhan tien  (viec P2-5)
 *    Ten thu huong va ma so thue hien TO truoc khi khach chuyen mot dong nao.
 *    Day la tin hieu ke lua dao khong sao chep duoc: chung chi co tai khoan ca nhan.
 * ------------------------------------------------------------------------- */

function gn_tp_payment_block() {
    $bank  = defined('GN_BANK_NAME')  ? GN_BANK_NAME  : '';
    $acc   = defined('GN_BANK_ACC')   ? GN_BANK_ACC   : '';
    $owner = defined('GN_BANK_OWNER') ? GN_BANK_OWNER : '';
    $company = defined('GN_LEGAL_COMPANY') ? trim(GN_LEGAL_COMPANY) : '';
    $tax     = defined('GN_LEGAL_TAX_ID')  ? trim(GN_LEGAL_TAX_ID)  : '';

    ob_start(); ?>
    <div class="tp-card" style="margin-top:0">
      <b style="font-size:18px">Hai cách trả tiền — quý khách chọn</b>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:16px 0 0">
        <div style="border:2px solid var(--tp-gold);border-radius:14px;padding:18px 20px;background:#fff">
          <span class="tp-badge is-ok">Khuyên dùng</span>
          <b style="display:block;font-size:17px;margin:10px 0 6px">Giữ chỗ 0đ</b>
          <p style="margin:0;font-size:14.5px;color:var(--tp-soft);line-height:1.65">
            Nhận mã ngay, <b>không trả trước đồng nào</b>. Đến nhà hàng trả tại quầy là dùng được.
            Đổi ý thì thôi, không mất phí.
          </p>
        </div>
        <div style="border:1px solid var(--tp-line);border-radius:14px;padding:18px 20px;background:#fff">
          <span class="tp-badge">Tuỳ chọn</span>
          <b style="display:block;font-size:17px;margin:10px 0 6px">Chuyển khoản ngay</b>
          <p style="margin:0;font-size:14.5px;color:var(--tp-soft);line-height:1.65">
            Sau khi nhận mã, quét mã VietQR trong email. Nhân viên xác nhận là voucher
            có hiệu lực ngay.
          </p>
        </div>
      </div>

      <?php if ($acc !== '') { ?>
      <div style="margin-top:18px;border:1px solid var(--tp-line);border-radius:14px;
                  padding:18px 20px;background:var(--tp-cream)">
        <div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;
                    color:var(--tp-soft);font-weight:700">Tài khoản nhận tiền duy nhất</div>
        <div style="font-size:22px;font-weight:700;margin:8px 0 2px;letter-spacing:.02em">
          <?php echo esc_html($bank); ?> — <?php echo esc_html($acc); ?>
        </div>
        <div style="font-size:16px;font-weight:700">
          <?php echo esc_html($company !== '' ? $company : $owner); ?>
        </div>
        <?php if ($tax !== '') { ?>
          <div style="font-size:14px;color:var(--tp-soft)">Mã số thuế: <?php echo esc_html($tax); ?></div>
        <?php } ?>
        <p style="margin:12px 0 0;font-size:14px;line-height:1.7">
          Gánh Ngon <b>không bao giờ</b> nhận tiền vào tài khoản cá nhân.
          Nếu màn hình chuyển khoản hiện tên người nhận là một cá nhân, hãy <b>dừng lại</b>
          và gọi <b><?php echo GN_TP_HOTLINE; ?></b> trước khi chuyển.
        </p>
      </div>
      <?php } ?>
    </div>
    <?php
    return ob_get_clean();
}
