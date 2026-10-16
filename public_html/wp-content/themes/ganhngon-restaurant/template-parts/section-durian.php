<?php /* GN-CONV-GUARD */ if (!defined('ABSPATH')) exit;
$faqs = function_exists('gn_en_durian_faqs') ? gn_en_durian_faqs() : [];
?>
<section class="gn-section gn-durian" id="durian-chicken-hotpot">
  <div class="gn-container">
    <p class="eyebrow">Signature dish · Phu Quoc, Duong Dong</p>
    <h2>Durian chicken hotpot</h2>
    <p class="section-intro">Ripe durian is blended into chicken broth at Gánh Ngon, 113 Tran Hung Dao — not whole segments, and not a dessert. Beside this pot: 220+ Vietnamese dishes. A regular table can be requested the same day. The hotpot needs about one hour’s notice. Open daily 6:30–2:00. Prices are quoted at the restaurant.</p>
    <p><?php echo esc_html(gn_t('hero_grab')); ?></p>
    <div class="gn-durian-faq">
      <?php foreach ($faqs as $f): ?>
      <details>
        <summary><?php echo esc_html($f['q']); ?></summary>
        <p><?php echo esc_html($f['a']); ?></p>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
