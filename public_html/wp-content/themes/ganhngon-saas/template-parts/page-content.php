<?php if (!defined('ABSPATH')) exit; ?>
<section class="page-hero page-hero-plain">
  <div class="wrap page-hero-inner">
    <h1 class="display display-dark"><?php the_title(); ?></h1>
  </div>
</section>
<section class="band band-ivory">
  <div class="wrap entry post-body">
    <?php
    while (have_posts()) {
        the_post();
        the_content();
    }
    ?>
  </div>
</section>
