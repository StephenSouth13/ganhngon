<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main>
  <section class="gn-page-hero">
    <div class="gn-container">
      <h1>Bài viết mới nhất</h1>
    </div>
  </section>
  <section class="gn-content">
    <div class="gn-container">
      <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article>
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <p style="color:var(--gn-text-soft);font-size:.9rem"><?php echo get_the_date(); ?></p>
          <?php the_excerpt(); ?>
        </article>
      <?php endwhile; else : ?>
        <p>Chưa có bài viết.</p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php get_footer(); ?>
