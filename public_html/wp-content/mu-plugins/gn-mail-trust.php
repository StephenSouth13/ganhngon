<?php
/**
 * Plugin Name: GN Mail Trust
 * Description: Chen nut "Kiem tra ma cua toi" + dong canh bao gia mao + khoi phap nhan
 *   vao email voucher va email dat ban (viec E.6). Khong sua functions.php cua theme:
 *   moc vao filter wp_mail, chen ngay truoc chan email.
 *   Neu da dien ten cong ty / MST trong gn-footer-legal.php thi tu dong hien them.
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

add_filter('wp_mail', function ($atts) {
    if (empty($atts['message']) || !is_string($atts['message'])) return $atts;

    $body = $atts['message'];
    if (strpos($body, 'gn-mail-trust') !== false) return $atts;   // da chen roi, khong chen hai lan

    // Chi dong vao email HTML cua Ganh Ngon gui cho khach.
    $is_html = stripos($body, '<table') !== false || stripos($body, '<div') !== false;
    if (!$is_html) return $atts;

    $is_voucher = stripos($body, 'VOUCHER TIỀN MẶT') !== false;
    $is_booking = stripos($body, 'đặt bàn') !== false || stripos($body, 'ĐẶT BÀN') !== false;
    if (!$is_voucher && !$is_booking) return $atts;

    $tra_cuu   = home_url('/tra-cuu/');
    $xac_minh  = home_url('/xac-minh/');
    $company   = defined('GN_LEGAL_COMPANY') ? trim(GN_LEGAL_COMPANY) : '';
    $tax       = defined('GN_LEGAL_TAX_ID')  ? trim(GN_LEGAL_TAX_ID)  : '';

    $legal = '';
    if ($company !== '') $legal .= '<div style="font-weight:700;color:#5A4B3E">' . esc_html($company) . '</div>';
    if ($tax !== '')     $legal .= '<div>Mã số thuế: ' . esc_html($tax) . '</div>';

    $btn = $is_voucher
        ? '<tr><td style="padding:0 24px 4px" class="gn-mail-trust">
             <a href="' . esc_url($tra_cuu) . '"
                style="display:block;text-align:center;padding:15px 20px;border-radius:10px;
                       background:#D4AF37;color:#1F1710;font:700 15px/1.3 Arial,sans-serif;
                       text-decoration:none">Kiểm tra mã của tôi</a>
             <div style="font:400 12px/1.6 Arial,sans-serif;color:#7A6A5A;text-align:center;margin-top:8px">
               Xem mã đã thanh toán chưa, đã dùng chưa và còn lại bao nhiêu — không cần đăng nhập.
             </div>
           </td></tr>'
        : '';

    $warn = '<tr><td style="padding:18px 24px 0" class="gn-mail-trust">
        <div style="border:1px solid #E4D9C4;background:#FBF6EC;border-radius:10px;padding:14px 16px;
                    font:400 12.5px/1.7 Arial,sans-serif;color:#5A4B3E">
          ' . $legal . '
          <div style="margin-top:' . ($legal !== '' ? '8px' : '0') . '">
            <b>Cảnh giác giả mạo.</b> Mọi liên kết chính thức của Gánh Ngon đều nằm trên tên miền
            <b>ganhngon.com</b>. Nhà hàng không bao giờ nhắn tin yêu cầu quý khách chuyển tiền vào
            tài khoản cá nhân. Nhận được tin nhắn mang tên Gánh Ngon nhưng tên miền khác,
            vui lòng gọi <b>0979 232 233</b> để xác minh trước khi thanh toán.
          </div>
          <div style="margin-top:8px">
            Tra cứu voucher: <a href="' . esc_url($tra_cuu) . '" style="color:#8A6D1F">ganhngon.com/tra-cuu</a>
            &nbsp;·&nbsp;
            Xác minh đối tác: <a href="' . esc_url($xac_minh) . '" style="color:#8A6D1F">ganhngon.com/xac-minh</a>
          </div>
        </div>
      </td></tr>';

    $block = $btn . $warn;

    // Chen ngay TRUOC hang chan email mau toi cua mau Ganh Ngon.
    $anchor = '<tr><td style="background:#1A0E07;';
    $at = strpos($body, $anchor);
    if ($at !== false) {
        $atts['message'] = substr($body, 0, $at) . $block . substr($body, $at);
        return $atts;
    }

    // Khong tim thay chan quen thuoc: chen truoc the dong bang ngoai cung.
    $at = strrpos($body, '</table>');
    if ($at !== false) {
        $atts['message'] = substr($body, 0, $at) . $block . substr($body, $at);
    }
    return $atts;
}, 20);
