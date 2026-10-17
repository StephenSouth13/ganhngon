<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()): the_post();
$cover = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'large') : gns_photo('2026/09/ganhngon-khong-gian-quan.webp');
?>
<section class="post-hero">
  <div class="post-cover" style="background-image:url('<?php echo esc_url($cover); ?>')"></div>
  <div class="hero-shade" aria-hidden="true"></div>
  <div class="wrap post-hero-inner">
    <a class="post-back" href="<?php echo esc_url(gns_url('tin-tuc')); ?>">← Tin tức</a>
    <h1 class="display post-title"><?php the_title(); ?></h1>
    <p class="post-meta"><?php echo esc_html(get_the_date()); ?></p>
  </div>
</section>
<section class="band band-ivory">
  <div class="wrap post-layout">
    <article class="post-body entry">
      <?php the_content(); ?>
    </article>
    <aside class="post-aside">
      <div class="post-aside-card">
        <p class="post-aside-title">Đặt bàn</p>
        <p>Giá báo tại nhà hàng. Gọi hoặc Zalo <?php echo esc_html(gns_phone_label()); ?>.</p>
        <div class="actions">
          <a class="btn btn-ember" href="<?php echo esc_url(gns_url('dat-ban')); ?>">Đặt bàn</a>
        </div>
      </div>
    </aside>
  </div>
</section>
<?php endwhile; get_footer(); ?>
