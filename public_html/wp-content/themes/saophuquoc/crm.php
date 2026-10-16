<?php
/**
 * SAO PHU QUOC - Lightweight CRM System
 * Drop-in replacement for Vtiger. Add to theme.
 */
class SAO_CRM {
    private static $ins = null;
    public static function init() { if(!self::$ins) { self::$ins = new self(); self::$ins->hooks(); } return self::$ins; }

    public function hooks() {
        add_shortcode('sao_crm', [$this, 'render']);
        add_shortcode('sao_crm_customers', [$this, 'customers_page']);
        add_shortcode('sao_crm_lead_form', [$this, 'lead_form']);
        foreach(['sao_crm_search','sao_crm_save','sao_crm_export'] as $a) {
            add_action("wp_ajax_$a", [$this, str_replace('sao_','ajax_',$a)]);
        }
        $this->maybe_create_tables();
    }

    public function maybe_create_tables() {
        global $wpdb; $p = $wpdb->prefix . 'sao_crm_'; $c = $wpdb->get_charset_collate();
        $tables = [
            "CREATE TABLE IF NOT EXISTS {$p}customers (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(255), phone VARCHAR(50), email VARCHAR(255), zalo VARCHAR(50), whatsapp VARCHAR(50), nationality VARCHAR(50), language VARCHAR(10) DEFAULT 'vi', customer_type VARCHAR(30) DEFAULT 'individual', budget_range VARCHAR(30), source VARCHAR(50) DEFAULT 'direct', tags TEXT, notes TEXT, assigned_to VARCHAR(100), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) $c",
            "CREATE TABLE IF NOT EXISTS {$p}interactions (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT, type VARCHAR(30) DEFAULT 'call', subject VARCHAR(255), notes TEXT, outcome VARCHAR(50), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
            "CREATE TABLE IF NOT EXISTS {$p}tasks (id INT AUTO_INCREMENT PRIMARY KEY, customer_id INT, title VARCHAR(255), description TEXT, due_date DATE, priority VARCHAR(10) DEFAULT 'medium', status VARCHAR(20) DEFAULT 'pending', assigned_to VARCHAR(100), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
        ];
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach($tables as $t) dbDelta($t);
    }

    public function render() {
        if(!current_user_can('manage_options')) return '<p>Access denied</p>';
        ob_start(); 
        $today = date('Y-m-d');
        global $wpdb; $p = $wpdb->prefix;
        $cust_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}sao_crm_customers");
        $bk_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}sao_bookings WHERE booking_date='$today'");
        $rev = (int)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}sao_bookings WHERE booking_date='$today'");
        ?>
<div style="max-width:1400px;margin:0 auto">
    <h2>🏖️ SAO PHU QUOC CRM</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:24px 0">
        <div class="sao-stat"><div class="v"><?=$cust_count?></div><div class="l">Khách hàng</div></div>
        <div class="sao-stat"><div class="v"><?=$bk_count?></div><div class="l">Booking hôm nay</div></div>
        <div class="sao-stat"><div class="v"><?=number_format($rev)?>đ</div><div class="l">Doanh thu hôm nay</div></div>
        <div class="sao-stat"><div class="v" id="sao-leads-count">-</div><div class="l">Lead mới</div></div>
    </div>
    
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04)">
            <h3>🔍 Tìm kiếm khách hàng</h3>
            <input type="text" id="crm-search" placeholder="Tên, SĐT, Email..." style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px" onkeyup="SaoCRM.search(this.value)">
            <div id="crm-results" style="margin-top:12px"></div>
        </div>
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04)">
            <h3>➕ Thêm khách hàng</h3>
            <div class="sao-row"><div class="sao-f"><label>Họ tên *</label><input type="text" id="crm-name" class="sao-in"></div><div class="sao-f"><label>SĐT *</label><input type="tel" id="crm-phone" class="sao-in"></div></div>
            <div class="sao-row"><div class="sao-f"><label>Email</label><input type="email" id="crm-email" class="sao-in"></div><div class="sao-f"><label>Loại khách</label><select id="crm-type" class="sao-in"><option value="individual">Cá nhân</option><option value="family">Gia đình</option><option value="corporate">Công ty</option><option value="vip">VIP</option></select></div></div>
            <div class="sao-f" style="margin-top:8px"><label>Ghi chú</label><textarea id="crm-notes" class="sao-in" rows="2"></textarea></div>
            <button class="sao-bt" onclick="SaoCRM.save()" style="margin-top:12px">Lưu khách hàng</button>
            <div id="crm-save-msg" style="margin-top:8px"></div>
        </div>
    </div>
    
    <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);margin-top:20px">
        <h3>📋 Danh sách khách hàng gần đây</h3>
        <table class="wp-list-table widefat"><thead><tr><th>Tên</th><th>SĐT</th><th>Email</th><th>Loại</th><th>Nguồn</th><th>Ngày tạo</th></tr></thead>
        <tbody id="crm-list">
        <?php
        $customers = $wpdb->get_results("SELECT * FROM {$p}sao_crm_customers ORDER BY created_at DESC LIMIT 20");
        if($customers) foreach($customers as $c) {
            echo "<tr><td><strong>{$c->full_name}</strong></td><td>{$c->phone}</td><td>{$c->email}</td><td>{$c->customer_type}</td><td>{$c->source}</td><td>{$c->created_at}</td></tr>";
        } else echo "<tr><td colspan='6' style='text-align:center;color:#718096'>Chưa có khách hàng</td></tr>";
        ?>
        </tbody></table>
    </div>
</div>
<style>
.sao-stat{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);text-align:center}
.sao-stat .v{font-size:2rem;font-weight:700;color:#1B2A4A}.sao-stat .l{color:#718096;font-size:.85rem;margin-top:4px}
.sao-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.sao-f label{display:block;font-weight:600;color:#1B2A4A;margin-bottom:4px;font-size:.85rem}
.sao-in{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:.9rem;font-family:inherit}
.sao-bt{padding:12px 24px;background:#C9A96E;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-family:inherit}
@media(max-width:768px){.sao-row{grid-template-columns:1fr}}
</style>
<script>
var SaoCRM={
    search:function(q){
        if(q.length<2){document.getElementById('crm-results').innerHTML='';return;}
        var self=this;
        fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=sao_crm_search&q='+encodeURIComponent(q)}).then(r=>r.json()).then(function(d){
            var h='';if(d.success&&d.data.length>0){d.data.forEach(function(c){h+='<div style="padding:8px;border-bottom:1px solid #eee"><strong>'+c.name+'</strong> - '+c.phone+' - '+c.email+' <span style="color:#718096;font-size:.8rem">('+c.type+')</span></div>';});}else{h='<p style="color:#718096">Không tìm thấy</p>';}
            document.getElementById('crm-results').innerHTML=h;
        });
    },
    save:function(){
        var d='action=sao_crm_save&name='+encodeURIComponent(document.getElementById('crm-name').value)+'&phone='+encodeURIComponent(document.getElementById('crm-phone').value)+'&email='+encodeURIComponent(document.getElementById('crm-email').value)+'&type='+document.getElementById('crm-type').value+'&notes='+encodeURIComponent(document.getElementById('crm-notes').value);
        fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:d}).then(r=>r.json()).then(function(r){
            document.getElementById('crm-save-msg').innerHTML=r.success?'<span style="color:green">✅ Đã lưu!</span>':'<span style="color:red">❌ Lỗi</span>';
            if(r.success)location.reload();
        });
    }
};
</script>
<?php return ob_get_clean(); }

    public function customers_page() { return '<p>Customer list page - use [sao_crm] for full CRM</p>'; }
    public function lead_form() {
        ob_start(); ?>
<div style="max-width:500px;margin:0 auto;background:#fff;padding:24px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.06)">
    <h3>📋 Nhận tư vấn</h3>
    <div class="sao-f"><label>Họ tên *</label><input type="text" id="lead-name" class="sao-in"></div>
    <div class="sao-f"><label>SĐT *</label><input type="tel" id="lead-phone" class="sao-in"></div>
    <div class="sao-f"><label>Nhu cầu</label><select id="lead-need" class="sao-in"><option>Bãi Sao</option><option>Gánh Ngon</option><option>Paghera Yacht</option><option>Combo</option><option>Sự kiện / Team Building</option></select></div>
    <div class="sao-f"><label>Ngân sách</label><select id="lead-budget" class="sao-in"><option value="">< 1 triệu</option><option>1-3 triệu</option><option>3-5 triệu</option><option>> 5 triệu</option></select></div>
    <button class="sao-bt" onclick="SaoCRM.saveLead()" style="margin-top:12px">Gửi yêu cầu</button>
    <div id="lead-msg" style="margin-top:8px"></div>
</div>
<script>
SaoCRM.saveLead=function(){
    var d='action=sao_crm_save&name='+document.getElementById('lead-name').value+'&phone='+document.getElementById('lead-phone').value+'&email=&type=lead&notes=Need:'+document.getElementById('lead-need').value+',Budget:'+document.getElementById('lead-budget').value;
    fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:d}).then(r=>r.json()).then(function(r){
        document.getElementById('lead-msg').innerHTML=r.success?'<span style="color:green">✅ Đã gửi! Chúng tôi sẽ liên hệ sớm</span>':'<span style="color:red">Lỗi</span>';
    });
};
</script>
<?php return ob_get_clean(); }

    /* AJAX */
    public function ajax_crm_search() {
        global $wpdb; $p = $wpdb->prefix . 'sao_crm_';
        $q = sanitize_text_field($_POST['q'] ?? '');
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$p}customers WHERE full_name LIKE %s OR phone LIKE %s OR email LIKE %s LIMIT 10", "%$q%","%$q%","%$q%"));
        $out = [];
        foreach($results as $r) $out[] = ['name'=>$r->full_name,'phone'=>$r->phone,'email'=>$r->email,'type'=>$r->customer_type];
        wp_send_json_success($out);
    }
    public function ajax_crm_save() {
        global $wpdb; $p = $wpdb->prefix . 'sao_crm_';
        $wpdb->insert($p.'customers', [
            'full_name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'customer_type' => sanitize_text_field($_POST['type'] ?? 'individual'),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'source' => 'website',
        ]);
        wp_send_json_success(['id'=>$wpdb->insert_id]);
    }
    public function ajax_crm_export() { wp_send_json_success([]); }
}
SAO_CRM::init();
