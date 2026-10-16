<?php /* GN-CONV-GUARD */ if (!defined('ABSPATH')) exit; get_header(); ?>
<?php get_template_part('template-parts/section-hero'); ?>
<?php if (function_exists('gn_cur_lang') && gn_cur_lang() === 'en') get_template_part('template-parts/section-durian'); ?>
<?php get_template_part('template-parts/section-menu'); ?>
<?php get_template_part('template-parts/section-location'); ?>
<?php get_template_part('template-parts/section-reservation'); ?>
<?php get_template_part('template-parts/section-about'); ?>
<?php get_template_part('template-parts/section-customer-types'); ?>
<?php get_template_part('template-parts/section-gallery'); ?>
<?php get_template_part('template-parts/section-testimonials'); ?>
<?php get_template_part('template-parts/section-faq'); ?>
<?php get_footer(); ?>
