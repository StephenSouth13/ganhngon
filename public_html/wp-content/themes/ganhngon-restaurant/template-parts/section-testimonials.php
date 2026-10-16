<?php if (!defined('ABSPATH')) exit; ?>
<section class="gn-section alt">
  <div class="gn-container">
    <h2><?php echo esc_html(gn_t('tm_h2')); ?></h2>
    <p class="section-intro">&nbsp;</p>
    <div class="gn-testimonials">
      <?php foreach (gn_testimonials_i18n() as $t): ?>
      <div class="gn-test-card">
        <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
        <p><?php echo esc_html($t['t']); ?></p>
        <div class="author"><?php echo esc_html($t['a']); ?><span class="role"><?php echo esc_html($t['r']); ?></span></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
