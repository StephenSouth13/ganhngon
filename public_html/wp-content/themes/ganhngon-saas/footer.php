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
    </div>
    <nav class="footer-col" aria-label="Khám phá">
      <p class="footer-title">Khám phá</p>
      <?php
      $gns_footer_nav = gns_nav_html('footer');
      if (is_string($gns_footer_nav) && trim($gns_footer_nav) !== '') {
          echo $gns_footer_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- walker escapes
      } else {
          foreach (gns_nav_items() as $slug => $label) {
              echo '<a href="' . esc_url(gns_url($slug)) . '">' . esc_html($label) . '</a>';
          }
      ?>
      <a href="<?php echo esc_url(gns_url('dat-ban')); ?>"><?php echo esc_html(gns_t('nav_book', 'Đặt bàn')); ?></a>
      <a href="<?php echo esc_url(gns_url('lau-ga-sau-rieng')); ?>"><?php echo esc_html(function_exists('gn_t') ? gn_t('hero_h1') : 'Lẩu gà sầu riêng'); ?></a>
      <a href="<?php echo esc_url(home_url('/thuc-don/do-uong/')); ?>">Đồ uống</a>
      <?php } ?>
    </nav>
    <div class="footer-col">
      <p class="footer-title">Liên hệ</p>
      <a href="<?php echo esc_url(gns_phone_href()); ?>"><?php echo esc_html(gns_phone_label()); ?> · Gọi / Zalo</a>
      <a href="<?php echo esc_url(gns_zalo()); ?>" rel="noopener" target="_blank">Zalo</a>
      <a href="<?php echo esc_url(gns_wa()); ?>" rel="noopener" target="_blank">WhatsApp (khách quốc tế)</a>
      <?php if (function_exists('gn_telegram')): ?>
        <a href="<?php echo esc_url(gn_telegram()); ?>" rel="noopener" target="_blank">Telegram</a>
      <?php endif; ?>
      <a href="mailto:<?php echo esc_attr(gns_public_email()); ?>"><?php echo esc_html(gns_public_email()); ?></a>
      <a href="mailto:sales@ganhngon.com">sales@ganhngon.com · Đoàn &amp; tiệc</a>
      <span>WeChat ID: <?php echo esc_html(function_exists('gn_wechat_id') ? gn_wechat_id() : 'ganhngon'); ?></span>
      <?php foreach (gns_social_links() as $gns_social): ?>
        <a href="<?php echo esc_url($gns_social['url']); ?>" rel="noopener" target="_blank"><?php echo esc_html($gns_social['label']); ?></a>
      <?php endforeach; ?>
    </div>
    <div class="footer-col">
      <p class="footer-title">Ghé nhà hàng</p>
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
  $gns_legal_addr = defined('GN_LEGAL_ADDRESS') ? GN_LEGAL_ADDRESS : '113 Trần Hưng Đạo, Khu phố 7, Phường Dương Đông, Thành phố Phú Quốc, Tỉnh Kiên Giang';
  $gns_hotline = defined('GN_LEGAL_HOTLINE') ? GN_LEGAL_HOTLINE : gns_phone_label();
  $gns_tel = defined('GN_LEGAL_TEL') ? GN_LEGAL_TEL : '+84979232233';
  $gns_email = defined('GN_LEGAL_EMAIL') ? GN_LEGAL_EMAIL : 'contact@ganhngon.com';
  $gns_legal_hours = defined('GN_LEGAL_HOURS') ? GN_LEGAL_HOURS : '6:30 – 02:00 tất cả các ngày';
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
      <a href="tel:<?php echo esc_attr($gns_tel); ?>"><?php echo esc_html($gns_hotline); ?></a>
      · <a href="mailto:<?php echo esc_attr($gns_email); ?>"><?php echo esc_html($gns_email); ?></a>
      · <?php echo esc_html($gns_en ? 'Open 6:30 – 02:00 daily' : 'Giờ mở cửa: ' . $gns_legal_hours); ?>
    </p>
    <p class="footer-legal-warn">
      <?php if ($gns_en): ?>
        Every official link of ours lives on <strong>ganhngon.com</strong>. If a message uses the Gánh Ngon name but another domain, call <a href="tel:<?php echo esc_attr($gns_tel); ?>"><?php echo esc_html($gns_hotline); ?></a> before paying.
        Check a voucher at <a href="<?php echo esc_url($gns_voucher); ?>">ganhngon.com/tra-cuu</a>
        · verify a partner at <a href="<?php echo esc_url($gns_partner); ?>">ganhngon.com/xac-minh</a>.
      <?php else: ?>
        Mọi liên kết chính thức của chúng tôi đều nằm trên tên miền <strong>ganhngon.com</strong>. Nếu quý khách nhận được tin nhắn mang tên Gánh Ngon nhưng chứa tên miền khác, vui lòng gọi <a href="tel:<?php echo esc_attr($gns_tel); ?>"><?php echo esc_html($gns_hotline); ?></a> để xác minh trước khi thanh toán.
        Tra cứu voucher tại <a href="<?php echo esc_url($gns_voucher); ?>">ganhngon.com/tra-cuu</a>
        · xác minh đối tác tại <a href="<?php echo esc_url($gns_partner); ?>">ganhngon.com/xac-minh</a>.
      <?php endif; ?>
    </p>
    <p class="footer-eco">Hệ sinh thái du lịch Phú Quốc: <a href="https://saophuquoc.com/" rel="noopener" target="_blank">Sao Phú Quốc — tour, du thuyền &amp; nghỉ dưỡng</a></p>
  </div>
  <div class="wrap footer-base">
    <span>© <?php echo esc_html(gmdate('Y')); ?> Gánh Ngon Phú Quốc</span>
    <span><?php echo esc_html(gns_footer_legal_line()); ?></span>
  </div>
</footer>
<?php get_template_part('template-parts/contact-dock'); ?>
<nav class="action-bar" aria-label="Liên hệ nhanh">
  <a href="<?php echo esc_url(gns_phone_href()); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg><span>Gọi</span></a>
  <a href="<?php echo esc_url(gns_zalo()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chat"></use></svg><span>Zalo</span></a>
  <a href="<?php echo esc_url(gns_maps()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-pin"></use></svg><span>Chỉ đường</span></a>
  <a class="action-primary" href="<?php echo esc_url(gns_url('dat-ban')); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg><span><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></span></a>
</nav>
<?php wp_footer(); ?>
</body>
</html>
