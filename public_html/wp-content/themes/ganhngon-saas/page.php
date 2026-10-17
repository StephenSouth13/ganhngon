<?php
if (!defined('ABSPATH')) exit;
get_header();
$slug = gns_base_slug();
if ($slug === 'thuc-don') {
    get_template_part('template-parts/page', 'menu');
} elseif ($slug === 'dat-ban' || (function_exists('gn_is_dat_ban_page') && gn_is_dat_ban_page())) {
    get_template_part('template-parts/page', 'booking');
} elseif ($slug === 'tin-tuc') {
    get_template_part('template-parts/page', 'news');
} else {
    get_template_part('template-parts/page', 'content');
}
get_footer();
