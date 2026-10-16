<?php
/**
 * Plugin Name: GN Footer Legal
 * Description: Khoi phap nhan + canh bao gia mao o chan MOI trang (viec P1-1).
 *
 *   ==> KHI ANH ACHAU CHO TEN CONG TY VA MA SO THUE, SUA DUNG HAI DONG NGAY BEN DUOI. <==
 *   De trong thi khoi van chay, chi khong hien hai dong do — tuyet doi khong tu bia
 *   ten phap nhan hay ma so thue.
 *
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

/* ===== HAI DONG CAN DIEN ===== */
define('GN_LEGAL_COMPANY', 'CÔNG TY TNHH GÁNH NGON');
define('GN_LEGAL_TAX_ID',  '1702362299');
/* ============================= */

define('GN_LEGAL_ADDRESS', '113 Trần Hưng Đạo, Khu phố 7, Phường Dương Đông, Thành phố Phú Quốc, Tỉnh Kiên Giang');
define('GN_LEGAL_HOTLINE', '0979 232 233');
define('GN_LEGAL_TEL',     '+84979232233');
define('GN_LEGAL_EMAIL',   'contact@ganhngon.com');
define('GN_LEGAL_HOURS',   '6:30 – 02:00 tất cả các ngày');

function gn_legal_is_en() {
    return function_exists('pll_current_language') && pll_current_language() === 'en';
}

add_action('wp_footer', function () {
    $en = gn_legal_is_en();
    $company = trim(GN_LEGAL_COMPANY);
    $tax     = trim(GN_LEGAL_TAX_ID);
    ?>
    <style>
    .gn-legal{border-top:1px solid rgba(0,0,0,.08);background:#faf7f2;color:#4a5568;
      font-size:13.5px;line-height:1.85;padding:26px 18px 30px}
    .gn-legal .in{max-width:900px;margin:0 auto}
    .gn-legal b{color:#1B2A4A}
    .gn-legal a{color:#1B2A4A}
    .gn-legal .warn{margin-top:14px;padding-top:14px;border-top:1px dashed rgba(0,0,0,.12)}
    .gn-legal .todo{color:#8a5a00;background:#fdf1dc;padding:2px 8px;border-radius:6px;font-size:12px}
    </style>
    <div class="gn-legal">
      <div class="in">
        <?php if ($company !== '') { ?>
          <div><b><?php echo esc_html($company); ?></b></div>
        <?php } ?>
        <?php if ($tax !== '') { ?>
          <div><?php echo $en ? 'Tax code: ' : 'Mã số thuế: '; ?><?php echo esc_html($tax); ?></div>
        <?php } ?>
        <?php if ($company === '' && $tax === '' && current_user_can('manage_options')) { ?>
          <div><span class="todo">Chỉ quản trị viên thấy dòng này: chưa điền tên công ty và mã số thuế
            trong mu-plugins/gn-footer-legal.php</span></div>
        <?php } ?>

        <div><?php echo $en ? 'Address: ' : 'Địa chỉ: '; ?><?php echo esc_html(GN_LEGAL_ADDRESS); ?></div>
        <div>
          <?php echo $en ? 'Hotline / Zalo: ' : 'Hotline / Zalo: '; ?>
          <a href="tel:<?php echo GN_LEGAL_TEL; ?>"><b><?php echo GN_LEGAL_HOTLINE; ?></b></a>
          &nbsp;·&nbsp;
          <a href="mailto:<?php echo GN_LEGAL_EMAIL; ?>"><?php echo GN_LEGAL_EMAIL; ?></a>
        </div>
        <div><?php echo $en ? 'Opening hours: 6:30 – 02:00 daily' : 'Giờ mở cửa: ' . GN_LEGAL_HOURS; ?></div>

        <div class="warn"><?php
          if ($en) {
            echo 'Every official link of ours lives on the <b>ganhngon.com</b> domain. '
               . 'If you receive a message using the Gánh Ngon name but containing a different domain, '
               . 'please call <a href="tel:' . GN_LEGAL_TEL . '"><b>' . GN_LEGAL_HOTLINE . '</b></a> to verify before paying. '
               . 'Check a voucher at <a href="' . esc_url(home_url('/en/tra-cuu-en/')) . '">ganhngon.com/tra-cuu</a> · '
               . 'verify a partner at <a href="' . esc_url(home_url('/en/xac-minh-en/')) . '">ganhngon.com/xac-minh</a>.';
          } else {
            echo 'Mọi liên kết chính thức của chúng tôi đều nằm trên tên miền <b>ganhngon.com</b>. '
               . 'Nếu quý khách nhận được tin nhắn mang tên Gánh Ngon nhưng chứa tên miền khác, '
               . 'vui lòng gọi <a href="tel:' . GN_LEGAL_TEL . '"><b>' . GN_LEGAL_HOTLINE . '</b></a> để xác minh trước khi thanh toán. '
               . 'Tra cứu voucher tại <a href="' . esc_url(home_url('/tra-cuu/')) . '">ganhngon.com/tra-cuu</a> · '
               . 'xác minh đối tác tại <a href="' . esc_url(home_url('/xac-minh/')) . '">ganhngon.com/xac-minh</a>.';
          }
        ?></div>
      </div>
    </div>
    <?php
}, 30);
