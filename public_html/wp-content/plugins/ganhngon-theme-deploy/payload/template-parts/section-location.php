<?php if (!defined('ABSPATH')) exit; ?>
<section class="gn-section" id="den-nha-hang">
  <div class="gn-container">
    <p class="section-eyebrow"><?php echo esc_html(gn_t('loc_eyebrow')); ?></p>
    <h2><?php echo esc_html(gn_t('arrival_addr')); ?></h2>
    <p class="section-intro"><?php echo esc_html(gn_t('loc_intro')); ?></p>

    <div class="gn-loc-grid">
      <div>
        <h3><?php echo esc_html(gn_t('loc_addr_h')); ?></h3>
        <p class="gn-loc-address"><?php echo esc_html(gn_address()); ?></p>
        <p><?php echo esc_html(gn_t('hours_clock')); ?></p>
        <p><?php echo esc_html(gn_t('hours')); ?></p>

        <h3><?php echo esc_html(gn_t('loc_contact_h')); ?></h3>
        <p><?php echo esc_html(gn_t('sticky_call')); ?> / Zalo: <a href="tel:<?php echo esc_attr(gn_phone_raw()); ?>"><?php echo esc_html(gn_phone_display()); ?></a> · <a href="https://zalo.me/<?php echo esc_attr(gn_zalo_raw()); ?>">Zalo</a></p>
        <p>WhatsApp: <a href="https://wa.me/<?php echo esc_attr(gn_whatsapp_raw()); ?>"><?php echo esc_html(gn_phone_display()); ?></a></p>
        <p>Email: <a href="mailto:contact@ganhngon.com">contact@ganhngon.com</a></p>

        <div class="gn-actions gn-actions-left">
          <a class="gn-cta-btn" href="<?php echo esc_url(gn_maps_url()); ?>" target="_blank" rel="noopener"><?php echo esc_html(gn_t('loc_maps')); ?></a>
          <a class="gn-cta-btn ghost" href="#gn-book-today"><?php echo esc_html(gn_t('hero_cta1')); ?></a>
        </div>
      </div>
      <div>
        <iframe title="<?php echo esc_attr(gn_address()); ?>" src="https://maps.google.com/maps?q=113+Tran+Hung+Dao+Duong+Dong+Phu+Quoc&output=embed" width="100%" height="380" style="border:0;border-radius:8px;box-shadow:var(--gn-shadow)" loading="lazy"></iframe>
      </div>
    </div>
  </div>
</section>
