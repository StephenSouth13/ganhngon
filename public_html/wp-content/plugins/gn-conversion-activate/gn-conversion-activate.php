<?php
/**
 * Plugin Name: GN Conversion Activate
 * Description: Copies Gánh Ngon theme files, writes .preconv backups, and refuses unless guard markers are present.
 * Version: 1.0.0
 * Author: Gánh Ngon
 */
/* GN-CONV-GUARD */
if (!defined('ABSPATH')) exit;

/**
 * Theme-relative path => marker that must already exist in the live file.
 * An empty marker means the file is new and only the payload guard is required.
 *
 * @return array<string,string>
 */
function gn_conv_manifest() {
    return [
        'functions.php' => 'function gn_theme_setup',
        'style.css' => 'Theme Name: Ganh Ngon Restaurant',
        'header.php' => 'gn-header',
        'footer.php' => 'gn-footer',
        'front-page.php' => 'section-hero',
        'inc/ops.php' => 'function gn_booking_guest_message',
        'inc/i18n.php' => 'function gn_i18n_dict',
        'template-parts/section-hero.php' => 'gn-hero',
        'template-parts/section-reservation.php' => 'gn-reservation',
        'template-parts/section-faq.php' => 'gn-faq-list',
        'template-parts/section-durian.php' => '',
    ];
}

/**
 * @return array{errors:string[],jobs:array<int,array{rel:string}>}
 */
function gn_conv_plan($payload_root, $theme_root) {
    $errors = [];
    $jobs = [];
    foreach (gn_conv_manifest() as $rel => $marker) {
        $src = rtrim($payload_root, '/') . '/' . $rel;
        $dst = rtrim($theme_root, '/') . '/' . $rel;
        if (!is_file($src)) {
            $errors[] = "missing payload $rel";
            continue;
        }
        $src_txt = file_get_contents($src);
        if ($src_txt === false || strpos($src_txt, 'GN-CONV-GUARD') === false) {
            $errors[] = "payload guard missing $rel";
            continue;
        }
        if ($marker !== '') {
            if (!is_file($dst)) {
                $errors[] = "missing theme file $rel";
                continue;
            }
            $dst_txt = file_get_contents($dst);
            if ($dst_txt === false || strpos($dst_txt, $marker) === false) {
                $errors[] = "theme guard missing $rel";
                continue;
            }
        }
        $jobs[] = ['rel' => $rel];
    }
    return ['errors' => $errors, 'jobs' => $jobs];
}

/**
 * @return true|string True, or a refusal message. Copies nothing when any guard is missing.
 */
function gn_conv_apply($payload_root, $theme_root) {
    $plan = gn_conv_plan($payload_root, $theme_root);
    if ($plan['errors']) {
        return 'GN Conversion Activate refused: ' . implode('; ', $plan['errors']);
    }
    foreach ($plan['jobs'] as $job) {
        $rel = $job['rel'];
        $src = rtrim($payload_root, '/') . '/' . $rel;
        $dst = rtrim($theme_root, '/') . '/' . $rel;
        $dir = dirname($dst);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return "GN Conversion Activate refused: cannot create $rel";
        }
        if (is_file($dst)) {
            $bak = $dst . '.preconv';
            if (!is_file($bak) && !copy($dst, $bak)) {
                return "GN Conversion Activate refused: backup failed $rel";
            }
        }
        if (!copy($src, $dst)) {
            return "GN Conversion Activate refused: copy failed $rel";
        }
    }
    return true;
}

function gn_conv_activate() {
    $theme = function_exists('get_template_directory') ? get_template_directory() : '';
    $result = gn_conv_apply(dirname(__FILE__) . '/payload', $theme);
    if ($result !== true) {
        if (function_exists('deactivate_plugins')) {
            deactivate_plugins(plugin_basename(__FILE__));
        }
        if (function_exists('wp_die')) {
            wp_die(esc_html($result));
        }
        return $result;
    }
    return true;
}

if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, 'gn_conv_activate');
}
