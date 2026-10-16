<?php if (!defined('ABSPATH')) exit; ?>
<section class="gn-section gn-reservation" id="giu-ban">
  <div class="gn-container">
    <h2><?php echo esc_html(gn_t('rv_h2')); ?></h2>
    <p class="section-intro"><?php echo esc_html(gn_t('rv_intro')); ?> <?php echo esc_html(gn_t('hero_note')); ?></p>
    <form class="reservation-form gn-book-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=sao_create_booking" data-gn-book>
      <input type="hidden" name="source" value="homepage_reservation">
      <div class="gn-form-row">
        <input type="text" name="customer_name" placeholder="<?php echo esc_attr(gn_t('rv_name')); ?>" required autocomplete="name">
        <input type="tel" name="customer_phone" placeholder="<?php echo esc_attr(gn_t('rv_phone')); ?>" required autocomplete="tel" inputmode="tel">
      </div>
      <div class="gn-form-row">
        <input type="date" name="booking_date" required min="<?php echo esc_attr(gn_today_ymd()); ?>" value="<?php echo esc_attr(gn_today_ymd()); ?>">
        <select name="booking_time" required>
          <option value=""><?php echo esc_html(gn_t('qb_time')); ?></option>
          <?php foreach (['06:30','07:30','09:00','11:30','12:00','12:30','14:00','17:30','18:00','18:30','19:00','19:30','20:00','21:00','22:00'] as $hm) echo '<option>' . esc_html($hm) . '</option>'; ?>
        </select>
        <select name="guests" required>
          <option value=""><?php echo esc_html(gn_t('qb_guests')); ?></option>
          <?php for ($i = 1; $i <= 12; $i++) echo '<option value="' . $i . '">' . $i . ' ' . esc_html(gn_t('people')) . '</option>'; ?>
        </select>
      </div>
      <div class="gn-form-row">
        <select name="space_type">
          <option value="individual"><?php echo esc_html(gn_t('qb_same')); ?></option>
          <option value="vip">VIP</option>
          <option value="doan"><?php echo esc_html(gn_t('ct_group')); ?></option>
        </select>
        <label class="gn-check"><input type="checkbox" name="signature_dish" value="1"> <?php echo esc_html(gn_t('qb_dish')); ?></label>
      </div>
      <div class="gn-form-row">
        <textarea name="note" placeholder="<?php echo esc_attr(gn_t('rv_note')); ?>" rows="3" style="grid-column:1/-1"></textarea>
      </div>
      <div style="text-align:center;margin-top:20px">
        <button type="submit" class="gn-cta-btn" style="min-width:240px"><?php echo esc_html(gn_t('qb_submit')); ?></button>
      </div>
      <p class="gn-book-out" role="status" hidden></p>
    </form>
  </div>
</section>
