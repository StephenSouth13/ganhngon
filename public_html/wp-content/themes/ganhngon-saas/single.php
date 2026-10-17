<?php
if (!defined('ABSPATH')) exit;
get_header();
while (have_posts()): the_post();
$thumb = get_the_post_thumbnail_url(null, 'large');
if ($thumb && function_exists('gns_is_hidden_media_url') && gns_is_hidden_media_url($thumb)) $thumb = '';
?>
<section class="post-hero">
  <?php if ($thumb): ?>
  <div class="post-cover" style="background-image:url('<?php echo esc_url($thumb); ?>')"></div>
  <?php else: ?>
  <?php echo gns_slot_markup('post-cover', get_the_title(), 'i-flame'); ?>
  <?php endif; ?>
  <div class="hero-shade" aria-hidden="true"></div>
  <div class="wrap post-hero-inner">
    <a class="post-back" href="<?php echo esc_url(gns_url('tin-tuc')); ?>">← Tin tức</a>
    <h1 class="display post-title"><?php the_title(); ?></h1>
    <p class="post-meta"><?php echo gns_post_date_html(); ?></p>
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
        <p>Giá báo tại nhà hàng. Gọi hoặc Zalo <bdi dir="ltr"><?php echo esc_html(gns_phone_label()); ?></bdi>.</p>
        <div class="actions">
          <a class="btn btn-ember" href="<?php echo esc_url(gns_url('dat-ban')); ?>">Đặt bàn</a>
        </div>
      </div>
    </aside>
  </div>
  <div class="wrap">
    <?php echo gns_related_posts_html(get_the_ID()); ?>
  </div>
</section>
<?php echo gns_post_cta_html(); ?>
<?php endwhile; get_footer(); ?>
