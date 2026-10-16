<?php if (!defined('ABSPATH')) exit; ?>
<section class="gn-section alt">
  <div class="gn-container">
    <h2><?php echo esc_html(gn_t('ct_h2')); ?></h2>
    <p class="section-intro"><?php echo esc_html(gn_t('hero_note')); ?></p>
    <div class="gn-menu-cats">
      <div class="gn-menu-cat"><h3><?php echo esc_html(gn_t('ct_family')); ?></h3><a href="<?php echo gn_page_url('dat-ban'); ?>"><?php echo esc_html(gn_t('hero_cta1')); ?> →</a></div>
      <div class="gn-menu-cat"><h3><?php echo esc_html(gn_t('ct_group')); ?></h3><a href="<?php echo gn_page_url('khach-doan-tiec-rieng'); ?>"><?php echo esc_html(gn_t('hero_cta3')); ?> →</a></div>
      <div class="gn-menu-cat"><h3><?php echo esc_html(gn_t('ct_biz')); ?></h3><a href="<?php echo gn_page_url('khach-doan-tiec-rieng'); ?>">VIP →</a></div>
      <div class="gn-menu-cat"><h3><?php echo esc_html(gn_t('ct_intl')); ?></h3><a href="<?php echo gn_page_url('thuc-don'); ?>"><?php echo esc_html(gn_t('hero_cta2')); ?> →</a></div>
      <div class="gn-menu-cat"><h3><?php echo esc_html(gn_t('ct_party')); ?></h3><a href="<?php echo gn_page_url('khach-doan-tiec-rieng'); ?>">→</a></div>
      <div class="gn-menu-cat"><h3><?php echo esc_html(gn_t('ct_vip')); ?></h3><a href="<?php echo gn_page_url('khach-doan-tiec-rieng'); ?>">→</a></div>
    </div>
    <div style="text-align:center;margin-top:36px">
      <a class="gn-cta-btn" href="#gn-book-today"><?php echo esc_html(gn_t('hero_cta1')); ?></a>
      <a class="gn-cta-btn ghost" href="<?php echo esc_url(gn_page_url('lau-ga-sau-rieng')); ?>"><?php echo esc_html(gn_t('hero_h1')); ?></a>
    </div>
  </div>
</section>
