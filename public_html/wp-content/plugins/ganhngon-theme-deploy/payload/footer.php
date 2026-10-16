<?php if (!defined('ABSPATH')) exit; ?>
<footer class="gn-footer">
  <div class="gn-footer-top">
    <div class="gn-container">
      <div class="gn-footer-cta">
        <h3><?php echo esc_html(gn_t("ft_cta_h")); ?></h3>
        <p><?php echo esc_html(gn_tagline_vi()); ?></p>
        <div class="gn-footer-cta-buttons">
          <a class="gn-cta-btn" href="<?php echo esc_url(gn_page_url('dat-ban')); ?>">Đặt bàn ngay</a>
          <a class="gn-cta-btn ghost" href="<?php echo esc_url(gn_page_url('khach-doan-tiec-rieng')); ?>">Đoàn / Tiệc / Phòng VIP</a>
          <a class="gn-cta-btn ghost" href="<?php echo esc_url(gn_page_url('ai-tu-van')); ?>"><?php echo esc_html(gn_t('adv_link')); ?></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Du lịch Phú Quốc — Multi-language SEO hub -->
  <div class="gn-footer-dlpq">
    <div class="gn-container">
      <div style="text-align:center;margin-bottom:30px">
        <h3 style="color:#fff;margin:0"><?php echo esc_html(gn_t("ft_travel")); ?></h3>
        <p style="color:rgba(255,255,255,.75);max-width:680px;margin:8px auto 0;font-size:.92rem"><?php echo esc_html(gn_t("dlpq_intro")); ?></p>
      </div>

      <div class="gn-dlpq-grid">
        <a href="<?php echo home_url('/category/an-uong-phu-quoc/'); ?>" class="gn-dlpq-card"><strong><?php echo esc_html(gn_t("dlpq_food")); ?></strong><span>Hải sản · Đặc sản · Quán ngon</span></a>
        <a href="<?php echo home_url('/category/diem-tham-quan-phu-quoc/'); ?>" class="gn-dlpq-card"><strong><?php echo esc_html(gn_t("dlpq_see")); ?></strong><span>Bãi biển · Hòn đảo · Sun World</span></a>
        <a href="<?php echo home_url('/category/luu-tru-phu-quoc/'); ?>" class="gn-dlpq-card"><strong><?php echo esc_html(gn_t("dlpq_stay")); ?></strong><span>Resort · Hotel · Homestay</span></a>
        <a href="<?php echo home_url('/category/di-chuyen-phu-quoc/'); ?>" class="gn-dlpq-card"><strong><?php echo esc_html(gn_t("dlpq_move")); ?></strong><span>Sân bay · Taxi · Ferry</span></a>
        <a href="<?php echo home_url('/category/kinh-nghiem-du-lich/'); ?>" class="gn-dlpq-card"><strong><?php echo esc_html(gn_t("dlpq_tips")); ?></strong><span>Tips · Lịch trình · Mùa đi</span></a>
        <a href="<?php echo home_url('/du-lich-phu-quoc/'); ?>" class="gn-dlpq-card gn-dlpq-card-all"><strong><?php echo esc_html(gn_t("dlpq_all")); ?></strong><span><?php echo esc_html(gn_t("dlpq_viewhub")); ?></span></a>
      </div>

      
    </div>
  </div>

  <div class="gn-container">
    <div class="gn-footer-grid">
      <div class="gn-footer-brand">
        <div class="gn-logo">GÁNH NGON<span class="sub">Đặc sản Việt Nam · Phú Quốc</span></div>
        <p style="opacity:.85;margin-top:14px"><strong><?php echo esc_html(gn_tagline_vi()); ?></strong></p>
        <p style="opacity:.7;font-size:.85rem;font-style:italic;margin-bottom:18px"><?php echo esc_html(gn_tagline_en()); ?></p>

        <h4 style="font-size:.85rem;text-transform:uppercase;letter-spacing:.15em;margin-bottom:14px"><?php echo esc_html(gn_t("ft_connect")); ?></h4>
        <?php gn_render_social_row(false, 20); ?>
      </div>

      <div>
        <h4><?php echo esc_html(gn_t("ft_explore")); ?></h4>
        <a href="<?php echo gn_page_url('ve-ganh-ngon'); ?>">Về Gánh Ngon</a>
        <a href="<?php echo gn_page_url('thuc-don'); ?>">Thực đơn</a>
        <a href="<?php echo gn_page_url('dat-ban'); ?>">Đặt bàn</a>
        <a href="<?php echo gn_page_url('khach-doan-tiec-rieng'); ?>">Đoàn / Tiệc / VIP</a>
        <a href="<?php echo home_url('/doi-tac/'); ?>"><strong><?php echo esc_html(gn_t("ft_partner")); ?></strong></a>
        <a href="<?php echo home_url('/cam-nang/'); ?>"><?php echo esc_html(gn_t("ft_guide")); ?></a>
        <a href="<?php echo home_url('/du-lich-phu-quoc/'); ?>"><?php echo esc_html(gn_t("ft_travel")); ?></a>
        <a href="<?php echo home_url('/hinh-anh-review/'); ?>">Gallery &amp; Review</a>
        <a href="<?php echo esc_url(gn_page_url('ai-tu-van')); ?>"><?php echo esc_html(gn_t('adv_link')); ?></a>
        <a href="<?php echo esc_url(gn_page_url('lau-ga-sau-rieng')); ?>"><?php echo esc_html(gn_t('hero_h1')); ?></a>
      </div>

      <div>
        <h4><?php echo esc_html(gn_t('ft_partner_h')); ?></h4>
        <?php foreach (gn_partner_footer_links() as $gn_ft): ?>
          <a href="<?php echo esc_url(home_url($gn_ft['path'])); ?>"><?php echo esc_html(gn_t($gn_ft['label'])); ?></a>
        <?php endforeach; ?>
      </div>

      <div>
        <h4><?php echo esc_html(gn_t("ft_contact")); ?></h4>
        <a href="tel:<?php echo esc_attr(gn_phone_raw()); ?>"><?php echo esc_html(gn_phone_display()); ?></a>
        <a href="https://zalo.me/<?php echo esc_attr(gn_zalo_raw()); ?>">Zalo: <?php echo esc_html(gn_phone_display()); ?></a>
        <a href="https://wa.me/<?php echo esc_attr(gn_whatsapp_raw()); ?>">WhatsApp (foreign guests)</a>
        <a href="<?php echo esc_url(gn_telegram()); ?>" target="_blank">Telegram</a>
        <a href="#wechat-qr" onclick="gnWechatPopup(event)">WeChat (ID: <?php echo esc_html(gn_wechat_id()); ?>)</a>
        <a href="mailto:contact@ganhngon.com">contact@ganhngon.com</a>
        <a href="mailto:sales@ganhngon.com">sales@ganhngon.com (đoàn)</a>
      </div>

      <div>
        <h4><?php echo esc_html(gn_t("ft_addr")); ?></h4>
        <p style="color:rgba(255,255,255,.85);font-size:.92rem;line-height:1.6"><strong><?php echo esc_html(gn_address()); ?></strong></p>
        <p style="color:rgba(255,255,255,.65);font-size:.85rem"><?php echo esc_html(gn_hours_short()); ?></p>
        <a href="<?php echo esc_url(gn_maps_url()); ?>" target="_blank" style="display:inline-block;background:rgba(212,175,55,.15);border:1px solid var(--gn-accent);padding:8px 14px;border-radius:6px;color:var(--gn-accent)!important;font-size:.88rem;margin-top:10px">Mở Google Maps</a>

        <h4 style="margin-top:22px"><?php echo esc_html(gn_t("ft_policy")); ?></h4>
        <a href="/chinh-sach-dat-ban/">Chính sách đặt bàn</a>
        <a href="/chinh-sach-huy-doi/">Hủy / đổi</a>
        <a href="/dieu-khoan-dich-vu/">Điều khoản</a>
        <a href="/chinh-sach-bao-mat/">Bảo mật</a>
      </div>
    </div>

    <div class="gn-footer-bottom">
      <div>
        © <?php echo date('Y'); ?> <strong>Gánh Ngon</strong> — Nhà hàng đặc sản Việt Nam sang trọng tại Phú Quốc.
        Một phần của hệ sinh thái <a href="https://saophuquoc.com" style="color:var(--gn-accent)" target="_blank">SAO PHU QUOC</a>.
      </div>
      <div class="gn-footer-langs">
        🇻🇳 Tiếng Việt · 🇬🇧 English · 🇨🇳 中文 · 🇰🇷 한국어 · 🇷🇺 Русский · 🇸🇦 العربية
      </div>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
