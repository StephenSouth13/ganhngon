<?php
/**
 * SAO PHU QUOC - Admin CPT + Meta Boxes + Dashboard Widgets
 * Include in functions.php
 */
class SAO_Admin_Setup {
    public static function init() {
        add_action('init', [__CLASS__, 'register_cpts']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('wp_dashboard_setup', [__CLASS__, 'dashboard_widgets']);
        add_filter('manage_sao_product_posts_columns', [__CLASS__, 'product_columns']);
        add_action('manage_sao_product_posts_custom_column', [__CLASS__, 'product_column_data'], 10, 2);
        add_filter('manage_sao_booking_posts_columns', [__CLASS__, 'booking_columns']);
        add_action('manage_sao_booking_posts_custom_column', [__CLASS__, 'booking_column_data'], 10, 2);
    }

    /* ── Custom Post Types ── */
    public static function register_cpts() {
        register_post_type('sao_product', [
            'labels' => ['name'=>'🎫 Sản phẩm','singular_name'=>'Sản phẩm','add_new'=>'Thêm sản phẩm','add_new_item'=>'Thêm sản phẩm mới'],
            'public' => false, 'show_ui' => true, 'show_in_menu' => 'sao-admin',
            'supports' => ['title','editor','thumbnail'], 'menu_position' => 26,
        ]);
        register_post_type('sao_booking', [
            'labels' => ['name'=>'📋 Bookings','singular_name'=>'Booking','add_new'=>'Thêm booking','add_new_item'=>'Thêm booking mới'],
            'public' => false, 'show_ui' => true, 'show_in_menu' => 'sao-admin',
            'supports' => ['title'], 'menu_position' => 27,
        ]);
        register_post_type('sao_partner_cpt', [
            'labels' => ['name'=>'🤝 Đối tác','singular_name'=>'Đối tác','add_new'=>'Thêm đối tác'],
            'public' => false, 'show_ui' => true, 'show_in_menu' => 'sao-admin',
            'supports' => ['title'], 'menu_position' => 28,
        ]);
    }

    /* ── Admin Menu ── */
    public static function admin_menu() {
        add_menu_page('SAO PHU QUOC', '🏖️ SAO Admin', 'manage_options', 'sao-admin', [__CLASS__,'dashboard_page'], 'dashicons-palmtree', 3);
        add_submenu_page('sao-admin','Tổng quan','📊 Tổng quan','manage_options','sao-admin',[__CLASS__,'dashboard_page']);
        add_submenu_page('sao-admin','QR Check-in','📱 QR Check-in','manage_options','sao-qr-checkin',[__CLASS__,'qr_page']);
        add_submenu_page('sao-admin','Báo cáo','📑 Báo cáo','manage_options','sao-reports',[__CLASS__,'reports_page']);
    }

    /* ── Meta Boxes ── */
    public static function add_meta_boxes() {
        add_meta_box('sao_product_meta', 'Thông tin sản phẩm', [__CLASS__,'product_meta_box'], 'sao_product', 'normal', 'high');
        add_meta_box('sao_booking_meta', 'Chi tiết booking', [__CLASS__,'booking_meta_box'], 'sao_booking', 'normal', 'high');
    }

    public static function product_meta_box($post) {
        $type = get_post_meta($post->ID, '_sao_type', true) ?: 'day_pass';
        $brand = get_post_meta($post->ID, '_sao_brand', true) ?: 'bai_sao';
        $price = get_post_meta($post->ID, '_sao_price', true) ?: '';
        $capacity = get_post_meta($post->ID, '_sao_capacity', true) ?: '';
        $status = get_post_meta($post->ID, '_sao_status', true) ?: 'active';
        wp_nonce_field('sao_product_meta', 'sao_product_nonce');
        ?>
        <table class="form-table">
        <tr><th>Loại sản phẩm</th><td><select name="_sao_type"><option value="day_pass" <?php selected($type,'day_pass')?>>Vé vào cổng</option><option value="lounger" <?php selected($type,'lounger')?>>Ghế Lounger</option><option value="cabana" <?php selected($type,'cabana')?>>Cabana</option><option value="glamping" <?php selected($type,'glamping')?>>Glamping</option><option value="fb" <?php selected($type,'fb')?>>F&B / Nhà hàng</option><option value="event" <?php selected($type,'event')?>>Sự kiện</option><option value="combo" <?php selected($type,'combo')?>>Combo</option></select></td></tr>
        <tr><th>Thương hiệu</th><td><select name="_sao_brand"><option value="bai_sao" <?php selected($brand,'bai_sao')?>>Bãi Sao</option><option value="ganh_ngon" <?php selected($brand,'ganh_ngon')?>>Gánh Ngon</option><option value="paghera_yacht" <?php selected($brand,'paghera_yacht')?>>Paghera Yacht</option></select></td></tr>
        <tr><th>Giá (VND)</th><td><input type="number" name="_sao_price" value="<?=esc_attr($price)?>" style="width:200px" step="1000"></td></tr>
        <tr><th>Sức chứa</th><td><input type="number" name="_sao_capacity" value="<?=esc_attr($capacity)?>" style="width:200px"></td></tr>
        <tr><th>Trạng thái</th><td><select name="_sao_status"><option value="active" <?php selected($status,'active')?>>Hoạt động</option><option value="inactive" <?php selected($status,'inactive')?>>Tạm dừng</option></select></td></tr>
        </table><?php
    }

    public static function booking_meta_box($post) {
        $code = get_post_meta($post->ID, '_sao_booking_code', true);
        $cname = get_post_meta($post->ID, '_sao_customer_name', true);
        $cemail = get_post_meta($post->ID, '_sao_customer_email', true);
        $cphone = get_post_meta($post->ID, '_sao_customer_phone', true);
        $bdate = get_post_meta($post->ID, '_sao_booking_date', true);
        $guests = get_post_meta($post->ID, '_sao_guests', true);
        $total = get_post_meta($post->ID, '_sao_total', true);
        $status = get_post_meta($post->ID, '_sao_booking_status', true) ?: 'pending';
        wp_nonce_field('sao_booking_meta', 'sao_booking_nonce');
        ?>
        <table class="form-table">
        <tr><th>Mã booking</th><td><input type="text" name="_sao_booking_code" value="<?=esc_attr($code)?>" style="width:200px" readonly></td></tr>
        <tr><th>Khách hàng</th><td><input type="text" name="_sao_customer_name" value="<?=esc_attr($cname)?>" style="width:300px"></td></tr>
        <tr><th>Email</th><td><input type="email" name="_sao_customer_email" value="<?=esc_attr($cemail)?>" style="width:300px"></td></tr>
        <tr><th>SĐT</th><td><input type="text" name="_sao_customer_phone" value="<?=esc_attr($cphone)?>" style="width:200px"></td></tr>
        <tr><th>Ngày đi</th><td><input type="date" name="_sao_booking_date" value="<?=esc_attr($bdate)?>"></td></tr>
        <tr><th>Số khách</th><td><input type="number" name="_sao_guests" value="<?=esc_attr($guests)?>" style="width:100px"></td></tr>
        <tr><th>Tổng tiền</th><td><input type="number" name="_sao_total" value="<?=esc_attr($total)?>" style="width:200px"> VND</td></tr>
        <tr><th>Trạng thái</th><td><select name="_sao_booking_status"><option value="pending" <?php selected($status,'pending')?>>⏳ Chờ</option><option value="confirmed" <?php selected($status,'confirmed')?>>✅ Xác nhận</option><option value="checked_in" <?php selected($status,'checked_in')?>>🟢 Check-in</option><option value="completed" <?php selected($status,'completed')?>>✔️ Hoàn tất</option><option value="cancelled" <?php selected($status,'cancelled')?>>✖️ Hủy</option></select></td></tr>
        </table><?php
    }

    public static function save_meta($post_id) {
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        $post_type = get_post_type($post_id);
        
        if($post_type == 'sao_product' && isset($_POST['sao_product_nonce']) && wp_verify_nonce($_POST['sao_product_nonce'],'sao_product_meta')) {
            foreach(['_sao_type','_sao_brand','_sao_price','_sao_capacity','_sao_status'] as $k)
                if(isset($_POST[$k])) update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
        }
        if($post_type == 'sao_booking' && isset($_POST['sao_booking_nonce']) && wp_verify_nonce($_POST['sao_booking_nonce'],'sao_booking_meta')) {
            foreach(['_sao_booking_code','_sao_customer_name','_sao_customer_email','_sao_customer_phone','_sao_booking_date','_sao_guests','_sao_total','_sao_booking_status'] as $k)
                if(isset($_POST[$k])) update_post_meta($post_id, $k, sanitize_text_field($_POST[$k]));
        }
    }

    /* ── Admin Columns ── */
    public static function product_columns($cols) { $cols['sao_type']='Loại'; $cols['sao_price']='Giá'; $cols['sao_capacity']='Sức chứa'; $cols['sao_status']='TT'; return $cols; }
    public static function product_column_data($col,$pid) {
        if($col=='sao_type') echo get_post_meta($pid,'_sao_type',true);
        if($col=='sao_price') echo number_format((int)get_post_meta($pid,'_sao_price',true)).'đ';
        if($col=='sao_capacity') echo get_post_meta($pid,'_sao_capacity',true);
        if($col=='sao_status') echo get_post_meta($pid,'_sao_status',true)=='active'?'✅':'⏸️';
    }
    public static function booking_columns($cols) { $cols['sao_code']='Mã'; $cols['sao_customer']='Khách'; $cols['sao_date']='Ngày'; $cols['sao_guests']='SL'; $cols['sao_total']='Tiền'; $cols['sao_status']='TT'; return $cols; }
    public static function booking_column_data($col,$pid) {
        if($col=='sao_code') echo get_post_meta($pid,'_sao_booking_code',true);
        if($col=='sao_customer') echo get_post_meta($pid,'_sao_customer_name',true);
        if($col=='sao_date') echo get_post_meta($pid,'_sao_booking_date',true);
        if($col=='sao_guests') echo get_post_meta($pid,'_sao_guests',true);
        if($col=='sao_total') echo number_format((int)get_post_meta($pid,'_sao_total',true)).'đ';
        if($col=='sao_status') { $s=get_post_meta($pid,'_sao_booking_status',true); echo ['pending'=>'⏳','confirmed'=>'✅','checked_in'=>'🟢','completed'=>'✔️','cancelled'=>'✖️'][$s]??$s; }
    }

    /* ── Dashboard Page ── */
    public static function dashboard_page() {
        global $wpdb; $p = $wpdb->prefix . 'sao_'; $today = date('Y-m-d');
        $bk = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today'");
        $rev = (int)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date='$today'");
        $chk = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today' AND qr_scanned=1");
        $cap = (int)$wpdb->get_var("SELECT COALESCE(SUM(total_capacity),0) FROM {$p}inventory WHERE booking_date='$today'");
        $sold = (int)$wpdb->get_var("SELECT COALESCE(SUM(reserved),0) FROM {$p}inventory WHERE booking_date='$today'");
        $cust = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sao_crm_customers");
        ?>
        <div class="wrap"><h1>🏖️ SAO PHU QUOC — Admin Dashboard</h1>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin:24px 0">
            <div class="sao-admin-stat"><div class="av"><?=$bk?></div><div class="al">Booking hôm nay</div></div>
            <div class="sao-admin-stat"><div class="av"><?=number_format($rev)?>đ</div><div class="al">Doanh thu</div></div>
            <div class="sao-admin-stat"><div class="av"><?=$chk?></div><div class="al">Check-in</div></div>
            <div class="sao-admin-stat"><div class="av"><?=$cap>0?round($sold/$cap*100,1):0?>%</div><div class="al">Công suất</div></div>
            <div class="sao-admin-stat"><div class="av"><?=$cust?></div><div class="al">CRM</div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="sao-admin-card"><h3>📋 Booking hôm nay</h3><table class="wp-list-table widefat"><thead><tr><th>Mã</th><th>Khách</th><th>SL</th><th>Tiền</th><th>TT</th></tr></thead><tbody>
            <?php $bks=$wpdb->get_results("SELECT * FROM {$p}bookings WHERE booking_date='$today' ORDER BY created_at DESC LIMIT 20");
            foreach($bks as $b) echo "<tr><td><code>{$b->booking_code}</code></td><td>{$b->customer_name}</td><td>{$b->guests}</td><td>".number_format($b->total)."đ</td><td>{$b->booking_status}</td></tr>"; ?>
            </tbody></table></div>
            <div class="sao-admin-card"><h3>🔗 Links nhanh</h3>
            <p><a href="<?=admin_url('edit.php?post_type=sao_product')?>" class="button">🎫 Quản lý Sản phẩm</a></p>
            <p><a href="<?=admin_url('edit.php?post_type=sao_booking')?>" class="button">📋 Quản lý Bookings</a></p>
            <p><a href="<?=home_url('/dashboard/')?>" class="button" target="_blank">📊 Dashboard vận hành</a></p>
            <hr>
            <h3 style="margin-top:24px">📊 Ops Dashboard (Live)</h3>
            <?php echo do_shortcode('[sao_ops_dashboard]'); ?>
            <p><a href="<?=home_url('/crm/')?>" class="button" target="_blank">🔗 CRM</a></p>
            <p><a href="<?=home_url('/partner/')?>" class="button" target="_blank">🤝 Partner Portal</a></p>
            </div>
        </div></div>
        <style>.sao-admin-stat{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);text-align:center}.sao-admin-stat .av{font-size:1.8rem;font-weight:700;color:#1B2A4A}.sao-admin-stat .al{color:#718096;font-size:.8rem;margin-top:4px}.sao-admin-card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04)}</style>
        <?php
    }

    public static function qr_page() { echo '<div class="wrap"><h1>📱 QR Check-in</h1><input type="text" id="qr-input" placeholder="Quét hoặc nhập mã booking..." style="width:100%;max-width:400px;padding:14px;font-size:1.1rem;border:2px solid #C9A96E;border-radius:8px" onchange="fetch(ajaxurl,{method:\"POST\",headers:{\"Content-Type\":\"application/x-www-form-urlencoded\"},body:\"action=sao_check_qr&code=\"+encodeURIComponent(this.value)}).then(r=>r.json()).then(d=>{var e=document.getElementById(\"qr-msg\");e.innerHTML=d.success?\"✅ \"+d.data.message+\" - \"+d.data.booking.customer_name:\"❌ \"+d.data.message;e.style.background=d.success?\"#E6F7E6\":\"#FFEBEE\"})"><div id="qr-msg" style=\"margin-top:12px;padding:12px;border-radius:8px\"></div></div>'; }
    public static function reports_page() { echo '<div class="wrap"><h1>📑 Báo cáo</h1><p>Tính năng báo cáo chi tiết đang phát triển.</p></div>'; }

    /* ── Dashboard Widgets ── */
    public static function dashboard_widgets() {
        wp_add_dashboard_widget('sao_today_widget', '🏖️ SAO PHU QUOC - Hôm nay', function() {
            global $wpdb; $p = $wpdb->prefix . 'sao_'; $today = date('Y-m-d');
            $bk = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today'");
            $rev = (int)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date='$today'");
            echo "<p><strong>📅 Booking:</strong> $bk</p><p><strong>💰 Doanh thu:</strong> ".number_format($rev)."đ</p>";
            echo "<p><a href='".home_url('/dashboard/')."' target='_blank'>→ Xem Dashboard đầy đủ</a></p>";
        });
    }
}
SAO_Admin_Setup::init();
