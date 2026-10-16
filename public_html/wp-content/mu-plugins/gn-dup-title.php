<?php
/**
 * Plugin Name: GN Dup Title
 * Description: Bo heading dau tien trong noi dung khi no lap lai tieu de trang.
 *   Theme da in H1 (tieu de) trong hero cua moi trang; 55/262 trang lai mo dau noi dung
 *   bang mot H1/H2 giong het hoac = tieu de + duoi ngan, nen khach thay tieu de hai lan
 *   trong cung mot man. Sua o runtime thay vi viet lai 55 noi dung: go bo = xoa file nay,
 *   noi dung luu trong database khong doi.
 *   Chi bo khi: heading dau tien la h1/h2, va (bang tieu de) hoac (bat dau bang tron ven
 *   tieu de) hoac (tieu de bat dau bang heading, heading >= 12 ky tu). Tieu de phai >= 3 tu
 *   de tranh bat nham heading hop le o trang co tieu de ngan.
 * Version: 1.0
 */
if (!defined('ABSPATH')) exit;

function gn_dt_norm($s) {
    $s = wp_strip_all_tags((string) $s);
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    $s = remove_accents($s);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9\p{L}\p{N}]+/u', ' ', $s);
    return trim($s);
}

add_filter('the_content', function ($content) {
    if (is_admin() || !in_the_loop() || !is_main_query() || !is_singular()) return $content;
    if (!preg_match('/<h([12])\b[^>]*>([\s\S]*?)<\/h\1>/iu', $content, $m, PREG_OFFSET_CAPTURE)) return $content;

    // Chi xet khi heading do la khoi text dau tien (truoc no khong co chu, chi co the / khoang trang).
    $before = substr($content, 0, $m[0][1]);
    if (trim(wp_strip_all_tags($before)) !== '') return $content;

    $t = gn_dt_norm(get_the_title());
    $h = gn_dt_norm($m[2][0]);
    if ($t === '' || $h === '' || str_word_count($t) < 3) return $content;

    $dup = ($h === $t)
        || (strpos($h, $t) === 0)
        || (mb_strlen($h, 'UTF-8') >= 12 && strpos($t, $h) === 0);
    if (!$dup) return $content;

    return substr($content, 0, $m[0][1]) . substr($content, $m[0][1] + strlen($m[0][0]));
}, 22);
