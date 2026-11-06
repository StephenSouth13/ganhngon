<?php
if (!defined('ABSPATH')) exit;
get_header();
if (!gns_render_main('404')) {
    echo '<section class="page-hero"><div class="wrap page-hero-inner"><h1>Không tìm thấy trang</h1></div></section>';
}
get_footer();
