<?php if (!defined('ABSPATH')) exit;
$dish_alt = function_exists('gn_dish_alt') ? gn_dish_alt('lau-ga-sau-rieng') : gn_t('hero_h1');
$img = 'https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-lau-ga-sau-rieng.webp';
?>
<section class="gn-hero gn-hero-dish">
  <div class="gn-container gn-hero-grid">
    <div class="gn-hero-copy">
      <p class="eyebrow"><?php echo esc_html(gn_t('hero_eyebrow')); ?></p>
      <p class="gn-arrival-kicker"><?php echo esc_html(gn_t('arrival_walk')); ?></p>
      <h1><?php echo esc_html(gn_t('hero_h1')); ?></h1>
      <p class="lead"><?php echo esc_html(gn_t('hero_lead')); ?></p>
      <ul class="gn-arrival" aria-label="<?php echo esc_attr(gn_t('arrival_walk')); ?>">
        <li><a href="<?php echo esc_url(gn_maps_url()); ?>" target="_blank" rel="noopener"><?php echo esc_html(gn_t('arrival_addr')); ?></a></li>
        <li><?php echo esc_html(gn_t('hours_clock')); ?></li>
        <li><a href="tel:<?php echo esc_attr(gn_phone_raw()); ?>"><?php echo esc_html(gn_phone_display()); ?></a></li>
        <li><a href="https://zalo.me/<?php echo esc_attr(gn_zalo_raw()); ?>">Zalo</a></li>
        <li><a href="https://wa.me/<?php echo esc_attr(gn_whatsapp_raw()); ?>">WhatsApp</a></li>
        <li><a href="<?php echo esc_url(gn_maps_url()); ?>" target="_blank" rel="noopener"><?php echo esc_html(gn_t('loc_maps')); ?></a></li>
      </ul>
      <div class="gn-actions">
        <a class="gn-cta-btn" href="#gn-book-today"><?php echo esc_html(gn_t('hero_cta1')); ?></a>
        <a class="gn-cta-btn ghost" href="<?php echo esc_url(gn_page_url('lau-ga-sau-rieng')); ?>"><?php echo esc_html(gn_t('hero_cta2')); ?></a>
        <a class="gn-cta-btn ghost" href="tel:<?php echo esc_attr(gn_phone_raw()); ?>"><?php echo esc_html(gn_t('sticky_call')); ?></a>
      </div>
      <p class="gn-hero-note"><?php echo esc_html(gn_t('hero_note')); ?></p>
    </div>
    <figure class="gn-hero-photo">
      <img src="<?php echo esc_url($img); ?>"
           srcset="https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-lau-ga-sau-rieng-768x439.webp 768w, https://ganhngon.com/wp-content/uploads/2026/09/ganhngon-lau-ga-sau-rieng-1024x585.webp 1024w, <?php echo esc_url($img); ?> 1600w"
           sizes="(max-width:860px) 92vw, 46vw"
           alt="<?php echo esc_attr($dish_alt); ?>"
           width="1600" height="914" fetchpriority="high" decoding="async">
      <figcaption><?php echo esc_html(gn_t('hero_h1')); ?> · <?php echo esc_html(gn_t('sig_price')); ?></figcaption>
    </figure>
  </div>
</section>
<?php gn_quick_booking_strip(); ?>
