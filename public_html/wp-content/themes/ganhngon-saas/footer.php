<?php if (!defined('ABSPATH')) exit; ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <a class="brand brand-light" href="<?php echo esc_url(gns_url('')); ?>" aria-label="Gánh Ngon — trang chủ">
        <img class="brand-mark" src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/logo-mark.svg'); ?>" alt="" width="36" height="42">
        <span class="brand-text"><span class="brand-name">Gánh Ngon</span><span class="brand-sub">Phú Quốc</span></span>
      </a>
      <p><?php echo esc_html(gns_footer_about()); ?></p>
      <?php echo gns_social_icons_html(); ?>
    </div>
    <nav class="footer-col" aria-label="<?php echo esc_attr(gns_t('footer_explore', 'Khám phá')); ?>">
      <p class="footer-title"><?php echo esc_html(gns_t('footer_explore', 'Khám phá')); ?></p>
      <?php
      $gns_footer_nav = function_exists('gns_nav_html') ? gns_nav_html('footer') : '';
      if (is_string($gns_footer_nav) && trim($gns_footer_nav) !== '') {
          echo $gns_footer_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- walker escapes
      } else {
          foreach (gns_nav_items() as $slug => $label) {
              echo '<a href="' . esc_url(gns_url($slug)) . '">' . esc_html($label) . '</a>';
          }
      ?>
      <a href="<?php echo esc_url(gns_url('dat-ban')); ?>"><?php echo esc_html(gns_t('nav_book', 'Đặt bàn')); ?></a>
      <a href="<?php echo esc_url(gns_url('lau-ga-sau-rieng')); ?>"><?php echo esc_html(function_exists('gn_t') ? gn_t('hero_h1') : 'Lẩu gà sầu riêng'); ?></a>
      <a href="<?php echo esc_url(home_url('/thuc-don/do-uong/')); ?>"><?php echo esc_html(gns_t('drinks', 'Đồ uống')); ?></a>
      <?php } ?>
    </nav>
    <div class="footer-col">
      <p class="footer-title"><?php echo esc_html(gns_t('footer_contact', 'Liên hệ')); ?></p>
      <a href="<?php echo esc_url(gns_phone_href()); ?>"><bdi dir="ltr"><?php echo esc_html(gns_phone_label()); ?></bdi> · <?php echo esc_html(gns_t('action_call', 'Gọi')); ?></a>
      <a href="mailto:<?php echo esc_attr(gns_public_email()); ?>"><?php echo esc_html(gns_public_email()); ?></a>
      <a href="mailto:sales@ganhngon.com">sales@ganhngon.com · Đoàn &amp; tiệc</a>
    </div>
    <div class="footer-col">
      <p class="footer-title"><?php echo esc_html(gns_t('footer_visit', 'Ghé nhà hàng')); ?></p>
      <address><?php echo esc_html(gns_addr()); ?></address>
      <span><?php echo esc_html(gns_hours()); ?></span>
      <a href="<?php echo esc_url(gns_maps()); ?>" rel="noopener" target="_blank">Mở Google Maps →</a>
      <a href="<?php echo esc_url(gns_url('hinh-anh-review')); ?>">Hình ảnh &amp; review</a>
      <a href="<?php echo esc_url(home_url('/cam-nang/')); ?>">Cẩm nang</a>
      <a href="<?php echo esc_url(home_url('/du-lich-phu-quoc/')); ?>">Du lịch Phú Quốc</a>
      <a href="<?php echo esc_url(gns_url('doi-tac')); ?>">Hợp tác</a>
      <a href="<?php echo esc_url(gns_url('voucher')); ?>">Voucher</a>
      <a href="<?php echo esc_url(gns_url('tra-cuu')); ?>">Tra cứu mã</a>
      <a href="<?php echo esc_url(gns_url('xac-minh')); ?>">Xác minh đối tác</a>
      <a href="<?php echo esc_url(gns_url('chinh-sach-dat-ban')); ?>">Chính sách đặt bàn</a>
      <a href="<?php echo esc_url(gns_url('chinh-sach-huy-doi')); ?>">Hủy / đổi</a>
      <a href="<?php echo esc_url(gns_url('dieu-khoan-dich-vu')); ?>">Điều khoản</a>
      <a href="<?php echo esc_url(gns_url('chinh-sach-bao-mat')); ?>">Bảo mật</a>
      <?php if (function_exists('gn_page_url')): ?>
        <a href="<?php echo esc_url(gn_page_url('ai-tu-van')); ?>"><?php echo esc_html(function_exists('gn_t') ? gn_t('adv_link') : 'Hỏi món'); ?></a>
      <?php endif; ?>
    </div>
  </div>
  <?php
  $gns_en = (function_exists('pll_current_language') && pll_current_language() === 'en')
      || (function_exists('gn_cur_lang') && gn_cur_lang() === 'en');
  $gns_company = defined('GN_LEGAL_COMPANY') ? trim(GN_LEGAL_COMPANY) : 'CÔNG TY TNHH GÁNH NGON';
  $gns_tax = defined('GN_LEGAL_TAX_ID') ? trim(GN_LEGAL_TAX_ID) : '1702362299';
  $gns_legal_addr = '113 Trần Hưng Đạo, Dương Đông, Phú Quốc';
  $gns_hotline = defined('GN_LEGAL_HOTLINE') ? GN_LEGAL_HOTLINE : gns_phone_label();
  $gns_tel = defined('GN_LEGAL_TEL') ? GN_LEGAL_TEL : '+84979232233';
  $gns_email = defined('GN_LEGAL_EMAIL') ? GN_LEGAL_EMAIL : 'contact@ganhngon.com';
  $gns_legal_hours = defined('GN_LEGAL_HOURS') ? GN_LEGAL_HOURS : '10:00 – 23:00 tất cả các ngày';
  if (function_exists('gns_refresh_hours_copy')) $gns_legal_hours = gns_refresh_hours_copy($gns_legal_hours);
  $gns_voucher = $gns_en ? home_url('/en/tra-cuu-en/') : home_url('/tra-cuu/');
  $gns_partner = $gns_en ? home_url('/en/xac-minh-en/') : home_url('/xac-minh/');
  ?>
  <div class="wrap footer-legal">
    <p class="footer-legal-id">
      <?php if ($gns_company !== ''): ?><strong><?php echo esc_html($gns_company); ?></strong><?php endif; ?>
      <?php if ($gns_tax !== ''): ?><span><?php echo esc_html($gns_en ? 'Tax code ' : 'Mã số thuế '); ?><?php echo esc_html($gns_tax); ?></span><?php endif; ?>
    </p>
    <p><?php echo esc_html($gns_en ? 'Address: ' : 'Địa chỉ: '); ?><?php echo esc_html($gns_legal_addr); ?></p>
    <p>
      <?php echo esc_html($gns_en ? 'Hotline / Zalo: ' : 'Hotline / Zalo: '); ?>
      <a href="tel:<?php echo esc_attr($gns_tel); ?>"><bdi dir="ltr"><?php echo esc_html($gns_hotline); ?></bdi></a>
      · <a href="mailto:<?php echo esc_attr($gns_email); ?>"><?php echo esc_html($gns_email); ?></a>
      · <?php echo esc_html($gns_en ? 'Open 10:00 – 23:00 daily' : 'Giờ mở cửa: ' . $gns_legal_hours); ?>
    </p>
    <p class="footer-legal-warn">
      <?php if ($gns_en): ?>
        Every official link of ours lives on <strong>ganhngon.com</strong>. If a message uses the Gánh Ngon name but another domain, call <a href="tel:<?php echo esc_attr($gns_tel); ?>"><bdi dir="ltr"><?php echo esc_html($gns_hotline); ?></bdi></a> before paying.
        Check a voucher at <a href="<?php echo esc_url($gns_voucher); ?>">ganhngon.com/tra-cuu</a>
        · verify a partner at <a href="<?php echo esc_url($gns_partner); ?>">ganhngon.com/xac-minh</a>.
      <?php else: ?>
        Mọi liên kết chính thức của chúng tôi đều nằm trên tên miền <strong>ganhngon.com</strong>. Nếu quý khách nhận được tin nhắn mang tên Gánh Ngon nhưng chứa tên miền khác, vui lòng gọi <a href="tel:<?php echo esc_attr($gns_tel); ?>"><bdi dir="ltr"><?php echo esc_html($gns_hotline); ?></bdi></a> để xác minh trước khi thanh toán.
        Tra cứu voucher tại <a href="<?php echo esc_url($gns_voucher); ?>">ganhngon.com/tra-cuu</a>
        · xác minh đối tác tại <a href="<?php echo esc_url($gns_partner); ?>">ganhngon.com/xac-minh</a>.
      <?php endif; ?>
    </p>
  </div>
  <div class="wrap footer-base">
    <span>© <?php echo esc_html(gmdate('Y')); ?> Gánh Ngon Phú Quốc</span>
    <span><?php echo esc_html(gns_footer_legal_line()); ?></span>
  </div>
</footer>
<?php get_template_part('template-parts/contact-dock'); ?>
<nav class="action-bar" aria-label="<?php echo esc_attr(gns_t('action_quick', 'Liên hệ nhanh')); ?>">
  <a href="<?php echo esc_url(gns_phone_href()); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg><span><?php echo esc_html(gns_t('action_call', 'Gọi')); ?></span></a>
  <?php if (gns_zalo() !== ''): ?>
  <a href="<?php echo esc_url(gns_zalo()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chat"></use></svg><span>Zalo</span></a>
  <?php endif; ?>
  <a href="<?php echo esc_url(gns_maps()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-pin"></use></svg><span><?php echo esc_html(gns_t('action_map', 'Chỉ đường')); ?></span></a>
  <a class="action-primary" href="<?php echo esc_url(gns_url('dat-ban')); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg><span><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></span></a>
</nav>
<?php wp_footer(); ?>
</body>
</html>
