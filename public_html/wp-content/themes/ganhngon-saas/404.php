<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<section class="page-hero" aria-labelledby="nf-title">
  <div class="wrap page-hero-inner page-hero-plain nf">
    <p class="eyebrow">404</p>
    <h1 id="nf-title" class="display display-dark">Món này<br><em>không có trên thực đơn</em>.</h1>
    <p class="section-lead">Trang không tồn tại hoặc đã đổi địa chỉ. Thực đơn, đặt bàn và liên hệ vẫn ở các đường dẫn cũ.</p>
    <div class="actions">
      <a class="btn btn-ember" href="<?php echo esc_url(gns_url('')); ?>">Về trang chủ</a>
      <a class="btn btn-outline" href="<?php echo esc_url(gns_url('thuc-don')); ?>">Xem thực đơn</a>
    </div>
  </div>
</section>
<?php get_footer(); ?>
