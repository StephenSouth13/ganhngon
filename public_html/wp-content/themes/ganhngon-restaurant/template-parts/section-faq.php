<?php /* GN-CONV-GUARD */ if (!defined('ABSPATH')) exit; ?>
<section class="gn-section">
  <div class="gn-container">
    <h2><?php echo esc_html(gn_t('faq_h2')); ?></h2>
    <p class="section-intro"><?php echo esc_html(gn_t('faq_intro')); ?> <a href="tel:<?php echo esc_attr(gn_phone_raw()); ?>" style="color:var(--gn-accent);font-weight:600"><?php echo esc_html(gn_phone_display()); ?></a> · <a href="https://zalo.me/<?php echo esc_attr(gn_zalo_raw()); ?>" style="color:var(--gn-accent);font-weight:600">Zalo</a>.</p>

    <div style="max-width:820px;margin:0 auto" class="gn-faq-list">
      <?php foreach (gn_faqs_i18n() as $f): ?>
      <details style="background:#fff;border-radius:8px;padding:18px 24px;margin-bottom:12px;box-shadow:0 4px 12px rgba(44,24,16,.06)">
        <summary style="cursor:pointer;font-weight:600;color:var(--gn-primary);font-size:1.05rem;list-style:none;display:flex;justify-content:space-between;align-items:center"><span><?php echo esc_html($f['q']); ?></span><span style="color:var(--gn-accent);font-size:1.4rem;line-height:1">+</span></summary>
        <p style="margin:14px 0 0;color:var(--gn-text-soft);line-height:1.7"><?php echo esc_html($f['a']); ?></p>
      </details>
      <?php endforeach; ?>
    </div>

    <div style="text-align:center;margin-top:36px">
      <a class="gn-cta-btn" href="#gn-book-today"><?php echo esc_html(gn_t('hero_cta1')); ?></a>
      <a class="gn-cta-btn ghost" href="<?php echo esc_url(gn_page_url('ai-tu-van')); ?>"><?php echo esc_html(gn_t('adv_link')); ?></a>
    </div>
  </div>
</section>
