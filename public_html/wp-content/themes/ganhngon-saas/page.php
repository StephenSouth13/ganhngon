<?php
if (!defined('ABSPATH')) exit;
get_header();
if (!gns_render_main(gns_base_slug())) {
    get_template_part('template-parts/page', 'content');
} else {
    gns_editor_band();
}
get_footer();
