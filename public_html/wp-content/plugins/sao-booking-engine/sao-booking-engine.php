<?php
/**
 * Plugin Name: SAO PHU QUOC Booking Engine
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;
define('SAO_BOOKING_DIR', plugin_dir_path(__FILE__));
define('SAO_BOOKING_URL', plugin_dir_url(__FILE__));

// Activation
register_activation_hook(__FILE__, function() {
    require_once SAO_BOOKING_DIR . 'includes/database.php';
    SAO_Booking_Database::create_tables();
    SAO_Booking_Database::seed();
});

// Load core
add_action('plugins_loaded', function() {
    require_once SAO_BOOKING_DIR . 'includes/core.php';
    SAO_Booking_Core::instance()->init();
}, 20);
