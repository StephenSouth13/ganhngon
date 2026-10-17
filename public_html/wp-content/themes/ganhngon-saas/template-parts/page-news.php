<?php
if (!defined('ABSPATH')) exit;
$spots = [
    [
        'url' => gns_url('lau-ga-sau-rieng'),
        'img' => '2026/09/ganhngon-lau-ga-sau-rieng.webp',
        'title' => 'Lẩu gà sầu riêng Phú Quốc',
        'excerpt' => 'Vì sao nồi lẩu cần báo trước khoảng một tiếng, và trong nồi có gì.',
    ],
    [
        'url' => home_url('/khach-doan-tiec-rieng/tiec-sinh-nhat/'),
        'img' => '2026/10/ganh-ngon-phong-vip-trang-tri-sinh-nhat.jpg',
        'title' => 'Tiệc sinh nhật trong phòng VIP',
        'excerpt' => 'Phòng riêng, trang trí và cách đặt tiệc đang đăng trên website.',
    ],
    [
        'url' => home_url('/thuc-don/do-uong/'),
        'img' => '2026/10/ganh-ngon-khong-gian-quay-bar-ngoai-troi.jpg',
        'title' => 'Quầy bar ngoài trời',
        'excerpt' => 'Cocktail, trà signature, cà phê, bia — giá báo tại quầy.',
    ],
    [
        'url' => gns_url('thuc-don'),
        'img' => '2026/09/ganhngon-bo-nuong-tang.webp',
        'title' => 'Thực đơn nướng than hồng',
        'excerpt' => 'Hải sản đảo, bò Úc và các nhóm món. Không niêm yết giá.',
    ],
];
?>
<section class="page-hero page-hero-plain">
  <div class="wrap page-hero-inner">
    <p class="eyebrow">Tin tức</p>
    <h1 class="display"><?php the_title(); ?></h1>
    <p class="section-lead">Bài trên website và các trang nhà hàng đang có. Không có giá món.</p>
  </div>
</section>
<section class="band band-ivory">
  <div class="wrap">
    <?php if (trim(get_post_field('post_content', get_queried_object_id())) !== ''): ?>
      <div class="entry post-body" style="margin-bottom:40px">
        <?php while (have_posts()) { the_post(); the_content(); } ?>
      </div>
    <?php endif; ?>
    <div class="news-grid news-grid-3">
      <?php foreach ($spots as $spot): ?>
        <article class="news-card">
          <a class="news-media" href="<?php echo esc_url($spot['url']); ?>" tabindex="-1" aria-hidden="true"><?php
            if (strpos($spot['img'], '2026/09/') === 0) echo gns_slot_markup('', $spot['title'], 'i-flame');
            else echo '<img src="' . esc_url(gns_photo($spot['img'])) . '" alt="" loading="lazy" decoding="async">';
          ?></a>
          <div class="news-body">
            <h3 class="news-title"><a href="<?php echo esc_url($spot['url']); ?>"><?php echo esc_html($spot['title']); ?></a></h3>
            <p class="news-excerpt"><?php echo esc_html($spot['excerpt']); ?></p>
            <a class="link news-more" href="<?php echo esc_url($spot['url']); ?>">Đọc tiếp <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php
    $q = new WP_Query([
        'post_type' => 'post',
        'posts_per_page' => 9,
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
    ]);
    if ($q->have_posts()):
    ?>
      <h2 class="title" style="margin:64px 0 28px">Bài viết</h2>
      <div class="news-grid news-grid-3">
        <?php while ($q->have_posts()): $q->the_post(); ?>
          <article class="news-card">
            <a class="news-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
              <?php if (has_post_thumbnail()) {
                  the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => '']);
              } else {
                  echo gns_slot_markup('', get_the_title(), 'i-flame');
              } ?>
            </a>
            <div class="news-body">
              <p class="news-meta"><span><?php echo esc_html(get_the_date()); ?></span></p>
              <h3 class="news-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <p class="news-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26)); ?></p>
              <a class="link news-more" href="<?php the_permalink(); ?>">Đọc tiếp <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-arrow"></use></svg></a>
            </div>
          </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php endif; ?>
  </div>
</section>
