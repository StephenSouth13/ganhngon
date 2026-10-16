<?php
class SAO_Booking_Core {
    private static $ins = null;
    public static function instance() { if(!self::$ins) self::$ins = new self(); return self::$ins; }
    
    public function init() {
        require_once SAO_BOOKING_DIR . 'includes/database.php';
        require_once SAO_BOOKING_DIR . 'includes/shortcodes.php';
        require_once SAO_BOOKING_DIR . 'includes/ajax.php';
        
        add_shortcode('sao_booking', ['SAO_Booking_Shortcodes', 'booking_form']);
        add_shortcode('sao_my_bookings', ['SAO_Booking_Shortcodes', 'my_bookings']);
        add_shortcode('sao_partner', ['SAO_Booking_Shortcodes', 'partner_portal']);
        
        $a = new SAO_Booking_Ajax();
        foreach(['sao_get_inventory','sao_create_booking','sao_check_qr','sao_dashboard','sao_partner_login'] as $act) {
            add_action("wp_ajax_$act", [$a, str_replace('sao_','',$act)]);
            add_action("wp_ajax_nopriv_$act", [$a, str_replace('sao_','',$act)]);
        }
        
        if(is_admin()) { require_once SAO_BOOKING_DIR . 'admin/admin-page.php'; $adm = new SAO_Booking_Admin(); add_action('admin_menu',[$adm,'menu']); }
    }
}
