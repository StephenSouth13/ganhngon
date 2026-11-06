<?php
/**
 * Plugin Name: GN Alex Event
 * Description: Standalone registration, counter redemption, and food-base partner ledger for the Alex DJ party. Does not read gn-voucher-online tables or calculate Alex commission.
 * Version: 1.0.0
 * Author: Gánh Ngon
 * Text Domain: gn-alex-event
 * Requires at least: 6.0
 * Requires PHP: 8.1
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-gn-alex-schema.php';
require_once __DIR__ . '/includes/class-gn-alex-storage.php';
require_once __DIR__ . '/includes/class-gn-alex-phone.php';
require_once __DIR__ . '/includes/class-gn-alex-providers.php';
require_once __DIR__ . '/includes/class-gn-alex-copy.php';
require_once __DIR__ . '/includes/class-gn-alex-engine.php';
require_once __DIR__ . '/includes/class-gn-alex-view.php';
require_once __DIR__ . '/includes/class-gn-alex-http.php';
require_once __DIR__ . '/includes/class-gn-alex-plugin.php';

register_activation_hook(__FILE__, ['Gn_Alex_Plugin', 'activate']);
add_action('init', ['Gn_Alex_Plugin', 'routes']);
add_action('template_redirect', ['Gn_Alex_Plugin', 'render'], 2);
add_action('rest_api_init', ['Gn_Alex_Plugin', 'rest']);
add_action('admin_menu', ['Gn_Alex_Plugin', 'adminMenu']);
add_action('admin_post_gn_alex_bootstrap', ['Gn_Alex_Plugin', 'adminPost']);
add_action('init', static function () {
    load_plugin_textdomain('gn-alex-event', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
