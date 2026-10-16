<?php
/**
 * Plugin Name: Achau Eco PhuQuoc
 * Description: Footer lien ket noi bo he sinh thai Phu Quoc (saophuquoc <-> ganhngon).
 */
if (!defined('ABSPATH')) exit;
add_action('wp_footer', function () { ?>
<div id="achau-eco-pq" style="text-align:center;padding:16px 12px;font-size:14px;line-height:1.8;color:#555;border-top:1px solid #eee">Hệ sinh thái du lịch Phú Quốc: <a href="https://saophuquoc.com/">Sao Phú Quốc — tour, du thuyền & nghỉ dưỡng</a></div>
<?php }, 20);
