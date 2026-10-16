<?php
class SAO_Booking_Database {
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix . 'sao_';
        $sql = [];
        
        $sql[] = "CREATE TABLE IF NOT EXISTS {$p}products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, slug VARCHAR(255) UNIQUE, type ENUM('day_pass','lounger','cabana','glamping','fb','event','combo') DEFAULT 'day_pass', brand VARCHAR(50) DEFAULT 'bai_sao', short_desc VARCHAR(500), base_price INT DEFAULT 0, price_type ENUM('fixed','per_person') DEFAULT 'per_person', capacity INT DEFAULT 0, sort_order INT DEFAULT 0, status ENUM('active','inactive') DEFAULT 'active', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset";
        
        $sql[] = "CREATE TABLE IF NOT EXISTS {$p}inventory (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, booking_date DATE NOT NULL, time_slot VARCHAR(10) DEFAULT 'all_day', zone VARCHAR(50) DEFAULT 'general', total_capacity INT DEFAULT 100, reserved INT DEFAULT 0, agent_quota INT DEFAULT 0, agent_used INT DEFAULT 0, status ENUM('open','full','closed') DEFAULT 'open', UNIQUE KEY slot (product_id, booking_date, time_slot, zone)) $charset";
        
        $sql[] = "CREATE TABLE IF NOT EXISTS {$p}bookings (id INT AUTO_INCREMENT PRIMARY KEY, booking_code VARCHAR(20) UNIQUE, customer_name VARCHAR(255), customer_email VARCHAR(255), customer_phone VARCHAR(50), booking_date DATE NOT NULL, guests INT DEFAULT 1, guest_type VARCHAR(20) DEFAULT 'individual', subtotal DECIMAL(12,2) DEFAULT 0, total DECIMAL(12,2) DEFAULT 0, deposit DECIMAL(12,2) DEFAULT 0, paid DECIMAL(12,2) DEFAULT 0, payment_status VARCHAR(20) DEFAULT 'pending', booking_status VARCHAR(20) DEFAULT 'pending', qr_code VARCHAR(255), qr_scanned TINYINT DEFAULT 0, source VARCHAR(50) DEFAULT 'direct', notes TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset";
        
        $sql[] = "CREATE TABLE IF NOT EXISTS {$p}booking_items (id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT, product_id INT, product_name VARCHAR(255), qty INT DEFAULT 1, unit_price DECIMAL(12,2), total_price DECIMAL(12,2)) $charset";
        
        $sql[] = "CREATE TABLE IF NOT EXISTS {$p}payments (id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT, txn_id VARCHAR(255), method VARCHAR(20) DEFAULT 'vnpay', amount DECIMAL(12,2), status VARCHAR(20) DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset";
        
        $sql[] = "CREATE TABLE IF NOT EXISTS {$p}partners (id INT AUTO_INCREMENT PRIMARY KEY, wp_user_id INT, company VARCHAR(255), contact VARCHAR(255), email VARCHAR(255), phone VARCHAR(50), type VARCHAR(30) DEFAULT 'agent', commission DECIMAL(5,2) DEFAULT 10, ref_code VARCHAR(50) UNIQUE, status VARCHAR(20) DEFAULT 'active', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach($sql as $q) dbDelta($q);
        update_option('sao_booking_db', SAO_BOOKING_DB_VERSION);
    }
    
    public static function seed() {
        global $wpdb;
        $p = $wpdb->prefix . 'sao_';
        if($wpdb->get_var("SELECT COUNT(*) FROM {$p}products") > 0) return;
        
        $items = [
            ['Vé Bãi Sao - Day Pass','day-pass','day_pass','bai_sao',150000,'per_person',5000,'Vé tham quan trọn ngày'],
            ['Ghế Lounger','lounger','lounger','bai_sao',250000,'fixed',200,'Ghế nằm thư giãn bên biển'],
            ['Cabana','cabana','cabana','bai_sao',800000,'fixed',30,'Cabana riêng tư 2-4 người'],
            ['Glamping','glamping','glamping','bai_sao',1500000,'fixed',20,'Cắm trại sang trọng'],
            ['Team Building','team','event','bai_sao',500000,'per_person',500,'20-50 người'],
            ['Set menu Gánh Ngon','set-menu','fb','ganh_ngon',400000,'per_person',200,'Set menu gia đình'],
            ['Tour Paghera Yacht','yacht','event','paghera_yacht',2500000,'per_person',30,'Du ngoạn du thuyền'],
        ];
        foreach($items as $i) {
            $wpdb->insert($p.'products',['name'=>$i[0],'slug'=>$i[1],'type'=>$i[2],'brand'=>$i[3],'base_price'=>$i[4],'price_type'=>$i[5],'capacity'=>$i[6],'short_desc'=>$i[7],'status'=>'active']);
        }
        
        // Inventory for 30 days
        $ids = $wpdb->get_col("SELECT id FROM {$p}products");
        foreach($ids as $pid) {
            for($d=0;$d<30;$d++) {
                $wpdb->insert($p.'inventory',['product_id'=>$pid,'booking_date'=>date('Y-m-d',strtotime("+$d days")),'total_capacity'=>rand(50,300),'reserved'=>0,'status'=>'open']);
            }
        }
    }
}
