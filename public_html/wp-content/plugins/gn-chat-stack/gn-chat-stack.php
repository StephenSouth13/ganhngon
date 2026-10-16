<?php
/**
 * Plugin Name: GN Chat Stack
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function gn_chat_stack_activate() {
    $plugin_dir = __DIR__;
    $payload_dir = $plugin_dir . '/payload';
    $payload_files = [
        'functions.php' => 'functions.php',
        'style.css' => 'style.css',
        'assets/main.js' => 'assets/main.js',
    ];

    $functions_payload = $payload_dir . '/functions.php';
    $style_payload = $payload_dir . '/style.css';
    if (!is_readable($functions_payload) || strpos(file_get_contents($functions_payload), 'function gn_public_chat_stack') === false || strpos(file_get_contents($functions_payload), 'gn-chat-fab') === false) {
        wp_die('GN Chat Stack refused activation: functions.php validation failed.');
    }
    if (!is_readable($style_payload) || strpos(file_get_contents($style_payload), '.gn-chat-stack') === false) {
        wp_die('GN Chat Stack refused activation: style.css validation failed.');
    }

    $theme_dir = get_stylesheet_directory();
    $destinations = [];
    foreach ($payload_files as $relative => $payload_relative) {
        $source = $theme_dir . '/' . $relative;
        $backup = $source . '.prechat';
        if (!is_file($source) || !is_readable($source) || !copy($source, $backup)) {
            wp_die('GN Chat Stack activation stopped: backup failed for ' . esc_html($relative) . '.');
        }
        if (filesize($source) !== filesize($backup) || hash_file('sha256', $source) !== hash_file('sha256', $backup)) {
            wp_die('GN Chat Stack activation stopped: backup verification failed for ' . esc_html($relative) . '.');
        }
        $destinations[$relative] = $source;
    }

    foreach ($payload_files as $relative => $payload_relative) {
        $destination = $destinations[$relative];
        $destination_dir = dirname($destination);
        if (!is_dir($destination_dir) && !wp_mkdir_p($destination_dir)) {
            wp_die('GN Chat Stack activation failed: could not create destination directory.');
        }
        if (!copy($payload_dir . '/' . $payload_relative, $destination)) {
            wp_die('GN Chat Stack activation failed: could not install ' . esc_html($relative) . '.');
        }
    }

    $upload_dir = WP_CONTENT_DIR . '/uploads';
    if (!is_dir($upload_dir) && !wp_mkdir_p($upload_dir)) {
        wp_die('GN Chat Stack activation failed: could not create the uploads directory.');
    }
    $report = '';
    foreach ($destinations as $relative => $destination) {
        $report .= $relative . ': ' . filesize($destination) . " bytes\n";
    }
    if (file_put_contents($upload_dir . '/gn-chat-deploy.txt', $report, LOCK_EX) === false) {
        wp_die('GN Chat Stack activation failed: could not write the deployment report.');
    }
}

register_activation_hook(__FILE__, 'gn_chat_stack_activate');
