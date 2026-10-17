<?php if (!defined('ABSPATH')) exit; ?>
</main>
<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <a class="brand brand-light" href="<?php echo esc_url(gns_url('')); ?>" aria-label="Gánh Ngon — trang chủ">
        <img class="brand-mark" src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/logo-mark.svg'); ?>" alt="" width="36" height="42">
        <span class="brand-text"><span class="brand-name">Gánh Ngon</span><span class="brand-sub">Phú Quốc</span></span>
      </a>
      <p>Nhà hàng Việt giữa trung tâm Dương Đông: hải sản và bò Úc nướng than hồng, lẩu gà sầu riêng, cơm niêu trưa và quầy bar ngoài trời mở đến 2 giờ sáng.</p>
    </div>
    <nav class="footer-col" aria-label="Khám phá">
      <p class="footer-title">Khám phá</p>
      <?php foreach (gns_nav_items() as $slug => $label): ?>
        <a href="<?php echo esc_url(gns_url($slug)); ?>"><?php echo esc_html($label); ?></a>
      <?php endforeach; ?>
      <a href="<?php echo esc_url(gns_url('dat-ban')); ?>"><?php echo esc_html(gns_t('nav_book', 'Đặt bàn')); ?></a>
      <a href="<?php echo esc_url(gns_url('lau-ga-sau-rieng')); ?>"><?php echo esc_html(function_exists('gn_t') ? gn_t('hero_h1') : 'Lẩu gà sầu riêng'); ?></a>
      <a href="<?php echo esc_url(home_url('/thuc-don/do-uong/')); ?>">Đồ uống</a>
    </nav>
    <div class="footer-col">
      <p class="footer-title">Liên hệ</p>
      <a href="<?php echo esc_url(gns_phone_href()); ?>"><?php echo esc_html(gns_phone_label()); ?> · Gọi / Zalo</a>
      <a href="<?php echo esc_url(gns_zalo()); ?>" rel="noopener" target="_blank">Zalo</a>
      <a href="<?php echo esc_url(gns_wa()); ?>" rel="noopener" target="_blank">WhatsApp (khách quốc tế)</a>
      <?php if (function_exists('gn_telegram')): ?>
        <a href="<?php echo esc_url(gn_telegram()); ?>" rel="noopener" target="_blank">Telegram</a>
      <?php endif; ?>
      <a href="mailto:contact@ganhngon.com">contact@ganhngon.com</a>
      <a href="mailto:sales@ganhngon.com">sales@ganhngon.com · Đoàn &amp; tiệc</a>
      <span>WeChat ID: <?php echo esc_html(function_exists('gn_wechat_id') ? gn_wechat_id() : 'ganhngon'); ?></span>
    </div>
    <div class="footer-col">
      <p class="footer-title">Ghé nhà hàng</p>
      <address><?php echo esc_html(gns_addr()); ?></address>
      <span><?php echo esc_html(gns_hours()); ?></span>
      <a href="<?php echo esc_url(gns_maps()); ?>" rel="noopener" target="_blank">Mở Google Maps →</a>
      <a href="<?php echo esc_url(home_url('/cam-nang/')); ?>">Cẩm nang</a>
      <a href="<?php echo esc_url(home_url('/du-lich-phu-quoc/')); ?>">Du lịch Phú Quốc</a>
      <a href="<?php echo esc_url(gns_url('chinh-sach-dat-ban')); ?>">Chính sách đặt bàn</a>
      <?php if (function_exists('gn_page_url')): ?>
        <a href="<?php echo esc_url(gn_page_url('ai-tu-van')); ?>"><?php echo esc_html(function_exists('gn_t') ? gn_t('adv_link') : 'Hỏi món'); ?></a>
      <?php endif; ?>
    </div>
  </div>
  <div class="wrap footer-base">
    <span>© <?php echo esc_html(gmdate('Y')); ?> Gánh Ngon Phú Quốc</span>
    <span>Giá món, combo và phòng VIP được báo trực tiếp khi đặt bàn.</span>
  </div>
</footer>
<nav class="action-bar" aria-label="Liên hệ nhanh">
  <a href="<?php echo esc_url(gns_phone_href()); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg><span>Gọi</span></a>
  <a href="<?php echo esc_url(gns_zalo()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chat"></use></svg><span>Zalo</span></a>
  <a href="<?php echo esc_url(gns_maps()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-pin"></use></svg><span>Chỉ đường</span></a>
  <a class="action-primary" href="<?php echo esc_url(gns_url('dat-ban')); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg><span><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></span></a>
</nav>
<?php wp_footer(); ?>
</body>
</html>
