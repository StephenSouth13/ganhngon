<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main>
  <section class="gn-page-hero">
    <div class="gn-container">
      <h1><?php the_title(); ?></h1>
    </div>
  </section>
  <section class="gn-content">
    <div class="gn-container">
      <article>
        <?php while (have_posts()) : the_post(); the_content(); endwhile; ?>
      </article>
    </div>
  </section>
</main>
<?php get_footer(); ?>
