<?php if (!function_exists('gn_dish_alt')) { function gn_dish_alt($s) { return 'Gánh Ngon Phú Quốc'; } } ?>
<?php if (!defined('ABSPATH')) exit; ?>
<section class="gn-section">
  <div class="gn-container">
    <div class="gn-about">
      <div>
        <h2 style="text-align:left"><?php echo wp_kses(gn_t('about_h2'), ['br'=>[]]); ?></h2>
        <p><?php echo esc_html(gn_t('about_p')); ?></p>

        <h3 style="font-size:1.1rem;margin-top:24px"><?php echo esc_html(gn_t('about_reasons_title')); ?></h3>
        <ol style="padding-left:18px;margin-bottom:0">
          <li><?php echo esc_html(gn_t('about_r1')); ?></li>
          <li><?php echo esc_html(gn_t('about_r2')); ?></li>
          <li><?php echo esc_html(gn_t('about_r3')); ?></li>
          <li><?php echo esc_html(gn_t('about_r4')); ?></li>
          <li><?php echo esc_html(gn_t('about_r5')); ?></li>
          <li><?php echo esc_html(gn_t('about_r6')); ?></li>
        </ol>

        <div class="stats">
          <div class="stat"><strong>220+</strong><span><?php echo esc_html(gn_t('stat_dishes')); ?></span></div>
          <div class="stat"><strong>10</strong><span><?php echo esc_html(gn_t('stat_vip')); ?></span></div>
          <div class="stat"><strong>4 NN</strong><span><?php echo esc_html(gn_t('stat_lang')); ?></span></div>
        </div>
        <div style="margin-top:28px;display:flex;gap:14px;flex-wrap:wrap">
          <a class="gn-cta-btn" href="<?php echo esc_url(gn_page_url('dat-ban')); ?>"><?php echo esc_html(gn_t('hero_cta1')); ?></a>
          <a class="gn-cta-btn ghost" href="<?php echo esc_url(gn_page_url('ve-ganh-ngon')); ?>"><?php echo esc_html(gn_t('about_learn')); ?></a>
        </div>
      </div>
      <div>
        <img src="https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-com-tam-suon-bi-cha.webp" srcset="https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-com-tam-suon-bi-cha-300x171.webp 300w, https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-com-tam-suon-bi-cha-768x439.webp 768w, https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-com-tam-suon-bi-cha-1024x585.webp 1024w, https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-com-tam-suon-bi-cha.webp 1600w" sizes="(max-width:900px) 92vw, 45vw" alt="<?php echo esc_attr(gn_dish_alt('com-tam-suon-bi-cha')); ?>" width="1600" height="914">
      </div>
    </div>
  </div>
</section>
