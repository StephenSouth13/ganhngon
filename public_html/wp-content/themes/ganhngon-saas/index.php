<?php
if (!defined('ABSPATH')) exit;
get_header();
?>
<section class="page-hero page-hero-plain">
  <div class="wrap page-hero-inner">
    <h1 class="display"><?php
      if (is_search()) echo 'Tìm: ' . esc_html(get_search_query());
      elseif (is_archive()) the_archive_title();
      else echo 'Bài viết';
    ?></h1>
  </div>
</section>
<section class="band band-ivory">
  <div class="wrap">
    <?php if (have_posts()): ?>
      <div class="news-grid news-grid-3">
        <?php while (have_posts()): the_post(); ?>
          <article class="news-card">
            <a class="news-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
              <?php if (has_post_thumbnail()) the_post_thumbnail('medium_large', ['alt' => '', 'loading' => 'lazy']);
              else echo gns_slot_markup('', get_the_title(), 'i-flame'); ?>
            </a>
            <div class="news-body">
              <p class="news-meta"><span><?php echo esc_html(get_the_date()); ?></span></p>
              <h3 class="news-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <p class="news-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <div class="entry" style="margin-top:32px"><?php the_posts_pagination(); ?></div>
    <?php else: ?>
      <p>Chưa có bài viết nào.</p>
    <?php endif; ?>
  </div>
</section>
<?php get_footer(); ?>
