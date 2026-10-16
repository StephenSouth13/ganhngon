<?php
/**
 * SAO PHU QUOC - Partner Portal + Affiliate System + Ops Dashboard
 * Complete system with Google/Phone registration, referral tracking, real-time KPIs
 */
class SAO_Partner {
    private static $ins = null;
    public static function init() { if(!self::$ins){self::$ins=new self();self::$ins->hooks();} return self::$ins; }

    public function hooks() {
        add_shortcode('sao_partner_full', [$this, 'partner_portal']);
        add_shortcode('sao_ops_dashboard', [$this, 'ops_dashboard']);
        foreach(['sao_partner_register','sao_partner_login','sao_partner_data','sao_partner_bookings','sao_ops_kpi','sao_ops_chart','sao_ops_export','sao_aff_link'] as $a) {
            add_action("wp_ajax_$a", [$this, str_replace('sao_','ajax_',$a)]);
            add_action("wp_ajax_nopriv_$a", [$this, str_replace('sao_','ajax_',$a)]);
        }
        $this->maybe_create_tables();
        // Track referral clicks
        if(isset($_GET['ref']) && !isset($_COOKIE['sao_ref'])) {
            setcookie('sao_ref', sanitize_text_field($_GET['ref']), time()+86400*30, '/');
        }
    }

    public function maybe_create_tables() {
        global $wpdb; $p = $wpdb->prefix . 'sao_'; $c = $wpdb->get_charset_collate();
        $tables = [
            "CREATE TABLE IF NOT EXISTS {$p}affiliates (id INT AUTO_INCREMENT PRIMARY KEY, wp_user_id INT, google_id VARCHAR(255), full_name VARCHAR(255), email VARCHAR(255), phone VARCHAR(50), ref_code VARCHAR(30) UNIQUE, commission DECIMAL(5,2) DEFAULT 10, tier INT DEFAULT 1, total_earnings DECIMAL(12,2) DEFAULT 0, paid_earnings DECIMAL(12,2) DEFAULT 0, pending_earnings DECIMAL(12,2) DEFAULT 0, status VARCHAR(20) DEFAULT 'active', payment_method VARCHAR(30), payment_info TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
            "CREATE TABLE IF NOT EXISTS {$p}affiliate_clicks (id INT AUTO_INCREMENT PRIMARY KEY, ref_code VARCHAR(30), ip VARCHAR(50), user_agent TEXT, landing_page VARCHAR(500), converted TINYINT DEFAULT 0, booking_id INT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
            "CREATE TABLE IF NOT EXISTS {$p}affiliate_commissions (id INT AUTO_INCREMENT PRIMARY KEY, affiliate_id INT, booking_id INT, booking_total DECIMAL(12,2), commission_amount DECIMAL(12,2), status VARCHAR(20) DEFAULT 'pending', paid_at DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
            "CREATE TABLE IF NOT EXISTS {$p}affiliate_payouts (id INT AUTO_INCREMENT PRIMARY KEY, affiliate_id INT, amount DECIMAL(12,2), method VARCHAR(30), status VARCHAR(20) DEFAULT 'pending', notes TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
        ];
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach($tables as $t) dbDelta($t);
    }

    /* ═══════════ PARTNER PORTAL ═══════════ */
    public function partner_portal() {
        // If not logged in, show register/login
        if(!is_user_logged_in()) {
            return $this->partner_register_form();
        }
        return $this->partner_dashboard();
    }

    private function partner_register_form() {
        ob_start(); ?>
<div id="partner-auth" style="max-width:500px;margin:40px auto">
    <div class="partner-tabs" style="display:flex;gap:0;margin-bottom:0">
        <button class="ptab active" onclick="P.switchTab('login')" style="flex:1;padding:14px;border:none;background:#1B2A4A;color:#fff;cursor:pointer;font-weight:600;border-radius:8px 8px 0 0">🔐 Đăng nhập</button>
        <button class="ptab" onclick="P.switchTab('register')" style="flex:1;padding:14px;border:none;background:#2D3748;color:#aaa;cursor:pointer;font-weight:600;border-radius:8px 8px 0 0">📝 Đăng ký</button>
    </div>
    <div style="background:#fff;padding:32px;border-radius:0 0 12px 12px;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <!-- Login Form -->
        <div id="p-login">
            <h3 style="margin-bottom:20px">🤝 Đăng nhập Đối tác</h3>
            <div class="partner-field"><label>Email hoặc SĐT</label><input type="text" id="p-login-id" class="sao-in" placeholder="email@example.com"></div>
            <div class="partner-field"><label>Mật khẩu</label><input type="password" id="p-login-pw" class="sao-in"></div>
            <button class="sao-bt" onclick="P.login()" style="margin-top:12px">Đăng nhập</button>
            <div style="margin-top:16px;text-align:center;color:#718096">— hoặc —</div>
            <button onclick="P.googleLogin()" style="width:100%;padding:12px;margin-top:8px;background:#fff;border:1px solid #ddd;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:500">
                <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Đăng nhập với Google
            </button>
            <div id="p-login-msg" style="margin-top:8px"></div>
        </div>
        <!-- Register Form -->
        <div id="p-register" style="display:none">
            <h3 style="margin-bottom:20px">📝 Đăng ký Đối tác</h3>
            <div class="partner-field"><label>Họ và tên *</label><input type="text" id="p-reg-name" class="sao-in" placeholder="Nguyễn Văn A"></div>
            <div class="partner-field"><label>Email *</label><input type="email" id="p-reg-email" class="sao-in" placeholder="email@example.com"></div>
            <div class="partner-field"><label>Số điện thoại *</label><input type="tel" id="p-reg-phone" class="sao-in" placeholder="090xxxxxxx"></div>
            <div class="partner-field"><label>Mật khẩu *</label><input type="password" id="p-reg-pw" class="sao-in" placeholder="Tối thiểu 6 ký tự"></div>
            <div class="partner-field"><label>Loại đối tác</label><select id="p-reg-type" class="sao-in"><option value="agent">Đại lý du lịch</option><option value="hotel">Khách sạn/Resort</option><option value="tour_operator">Tour Operator</option><option value="concierge">Concierge</option><option value="kol">KOL / Affiliate</option><option value="ota">OTA</option></select></div>
            <button class="sao-bt" onclick="P.register()" style="margin-top:12px">Đăng ký & Nhận mã giới thiệu</button>
            <div id="p-reg-msg" style="margin-top:8px"></div>
        </div>
    </div>
</div>
<style>
.partner-field{margin-bottom:12px}.partner-field label{display:block;font-weight:600;color:#1B2A4A;margin-bottom:4px;font-size:.85rem}
.partner-tabs button:first-child.active{background:#1B2A4A;color:#fff}.partner-tabs button:last-child.active{background:#1B2A4A;color:#fff}
</style>
<script>
var P={tab:'login',
    switchTab:function(t){this.tab=t;document.querySelectorAll('.ptab').forEach(function(e,i){e.style.background=t===['login','register'][i]?'#1B2A4A':'#2D3748';e.style.color=t===['login','register'][i]?'#fff':'#aaa'});document.getElementById('p-login').style.display=t==='login'?'block':'none';document.getElementById('p-register').style.display=t==='register'?'block':'none';},
    login:function(){var d={action:'sao_partner_login',login:document.getElementById('p-login-id').value,pw:document.getElementById('p-login-pw').value};fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(d)}).then(r=>r.text()).then(function(t){try{var j=JSON.parse(t);document.getElementById('p-login-msg').innerHTML=j.success?'<span style=color:green>✅ '+j.message+'</span>':'<span style=color:red>❌ '+j.message+'</span>';if(j.success)setTimeout(function(){location.reload()},1000);}catch(e){document.getElementById('p-login-msg').innerHTML=t;}});},
    register:function(){var d={action:'sao_partner_register',name:document.getElementById('p-reg-name').value,email:document.getElementById('p-reg-email').value,phone:document.getElementById('p-reg-phone').value,pw:document.getElementById('p-reg-pw').value,type:document.getElementById('p-reg-type').value};fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(d)}).then(r=>r.json()).then(function(j){document.getElementById('p-reg-msg').innerHTML=j.success?'<span style=color:green>✅ '+j.message+'</span>':'<span style=color:red>❌ '+j.message+'</span>';if(j.success){document.getElementById('p-reg-name').value='';document.getElementById('p-reg-email').value='';document.getElementById('p-reg-phone').value='';document.getElementById('p-reg-pw').value='';P.switchTab('login');}});},
    googleLogin:function(){alert('Google Sign-In sẽ được kích hoạt khi thêm Google Client ID.\n\nHiện tại vui lòng đăng ký bằng email/SĐT.');}
};
</script>
<?php return ob_get_clean(); }

    private function partner_dashboard() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $aff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}affiliates WHERE wp_user_id=%d", get_current_user_id()));
        if(!$aff) {
            // Auto-create affiliate profile
            $user = wp_get_current_user();
            $ref = 'SAO' . strtoupper(substr(md5($user->user_email.time()),0,6));
            $wpdb->insert($p.'affiliates', ['wp_user_id'=>$user->ID, 'full_name'=>$user->display_name, 'email'=>$user->user_email, 'phone'=>get_user_meta($user->ID,'phone',true)?:'', 'ref_code'=>$ref, 'commission'=>10, 'status'=>'active']);
            $aff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}affiliates WHERE wp_user_id=%d", get_current_user_id()));
        }
        
        $bk_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE notes LIKE %s", '%ref:'.$aff->ref_code.'%'));
        $month_sales = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE notes LIKE %s AND MONTH(booking_date)=MONTH(NOW())", '%ref:'.$aff->ref_code.'%'));
        $ref_link = home_url('/?ref='.$aff->ref_code);
        $commission_amt = round($month_sales * $aff->commission / 100);
        
        ob_start(); ?>
<div id="partner-dash" style="max-width:1100px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px">
        <h2>🤝 Portal Đối tác — <?=esc_html($aff->full_name)?></h2>
        <div style="display:flex;gap:8px">
            <a href="<?=wp_logout_url(home_url('/partner/'))?>" class="sao-bt-out" style="text-decoration:none">Đăng xuất</a>
        </div>
    </div>
    
    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px">
        <div class="partner-stat"><div class="pv"><?=esc_html($aff->ref_code)?></div><div class="pl">Mã giới thiệu</div></div>
        <div class="partner-stat"><div class="pv"><?=esc_html($aff->commission)?>%</div><div class="pl">Hoa hồng</div></div>
        <div class="partner-stat"><div class="pv"><?=$bk_count?></div><div class="pl">Tổng booking</div></div>
        <div class="partner-stat"><div class="pv"><?=number_format($month_sales)?>đ</div><div class="pl">Doanh số tháng</div></div>
        <div class="partner-stat"><div class="pv" style="color:#C9A96E"><?=number_format($commission_amt)?>đ</div><div class="pl">Hoa hồng tháng</div></div>
        <div class="partner-stat"><div class="pv" style="color:#38A169"><?=number_format($aff->pending_earnings)?>đ</div><div class="pl">Chưa thanh toán</div></div>
    </div>
    
    <!-- Referral Link -->
    <div style="background:#FDF8F0;border:2px solid #C9A96E;border-radius:12px;padding:24px;margin-bottom:24px">
        <h4 style="margin-bottom:8px">🔗 Link giới thiệu của bạn</h4>
        <div style="display:flex;gap:8px">
            <input type="text" value="<?=esc_attr($ref_link)?>" readonly style="flex:1;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem;font-family:monospace" id="ref-link">
            <button class="sao-bt" onclick="navigator.clipboard.writeText(document.getElementById('ref-link').value);this.textContent='✅ Đã copy!'" style="white-space:nowrap">📋 Copy</button>
        </div>
        <p style="margin-top:8px;color:#718096;font-size:.85rem">Chia sẻ link này. Khi khách đặt qua link, bạn nhận <strong><?=esc_html($aff->commission)?>%</strong> hoa hồng.</p>
    </div>
    
    <!-- Bookings Table -->
    <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
        <h3 style="margin-bottom:16px">📋 Booking của bạn</h3>
        <div style="overflow-x:auto">
        <table class="wp-list-table widefat"><thead><tr><th>Mã</th><th>Ngày</th><th>Khách</th><th>SL</th><th>Tổng tiền</th><th>Hoa hồng</th><th>Trạng thái</th></tr></thead>
        <tbody id="partner-bk-list"><tr><td colspan="7" style="text-align:center;color:#718096">Đang tải...</td></tr></tbody></table>
        </div>
    </div>
    
    <!-- Payout History -->
    <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04);margin-top:20px">
        <h3 style="margin-bottom:16px">💰 Lịch sử thanh toán</h3>
        <div style="overflow-x:auto">
        <table class="wp-list-table widefat"><thead><tr><th>Ngày</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th></tr></thead>
        <tbody>
        <?php
        $payouts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$p}affiliate_payouts WHERE affiliate_id=%d ORDER BY created_at DESC LIMIT 10", $aff->id));
        if($payouts) foreach($payouts as $po) {
            $st = $po->status=='completed'?'✅':'⏳';
            echo "<tr><td>{$po->created_at}</td><td>".number_format($po->amount)."đ</td><td>{$po->method}</td><td>$st {$po->status}</td></tr>";
        } else echo "<tr><td colspan='4' style='text-align:center;color:#718096'>Chưa có thanh toán</td></tr>";
        ?>
        </tbody></table>
        </div>
        <button class="sao-bt" onclick="P.requestPayout()" style="margin-top:16px">💸 Yêu cầu thanh toán</button>
    </div>
</div>

<style>
.partner-stat{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);text-align:center}
.partner-stat .pv{font-size:1.6rem;font-weight:700;color:#1B2A4A}.partner-stat .pl{color:#718096;font-size:.82rem;margin-top:4px}
</style>
<script>
P.requestPayout=function(){
    var a=prompt('Nhập số tiền muốn rút (tối thiểu 500.000đ):');
    if(!a||isNaN(a)||parseInt(a)<500000) return;
    fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=sao_partner_data&payout='+a}).then(r=>r.json()).then(function(j){alert(j.success?'Đã gửi yêu cầu thanh toán '+parseInt(a).toLocaleString()+'đ':'Lỗi: '+j.message);});
};
// Load bookings via AJAX
fetch(ajaxurl+'?action=sao_partner_bookings').then(r=>r.json()).then(function(d){
    var t=document.getElementById('partner-bk-list'),h='';
    if(d.success&&d.data.length>0)d.data.forEach(function(b){h+='<tr><td>'+b.code+'</td><td>'+b.date+'</td><td>'+b.name+'</td><td>'+b.guests+'</td><td>'+b.total.toLocaleString()+'đ</td><td style=color:#C9A96E>'+b.commission.toLocaleString()+'đ</td><td>'+b.status+'</td></tr>';});
    else h='<tr><td colspan=7 style=text-align:center;color:#718096>Chưa có booking qua link giới thiệu</td></tr>';
    t.innerHTML=h;
});
</script>
<?php return ob_get_clean(); }

    /* ═══════════ OPERATIONS DASHBOARD ═══════════ */
    public function ops_dashboard() {
        if(!current_user_can('manage_options')) return '<p style="text-align:center;padding:40px">🔐 Vui lòng đăng nhập admin</p>';
        ob_start(); ?>
<div style="max-width:1400px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px">
        <h2>📊 Dashboard Vận Hành SAO PHU QUOC</h2>
        <div style="display:flex;gap:8px;align-items:center">
            <select id="ops-date" class="sao-in" style="width:auto;display:inline" onchange="Ops.refresh()">
                <option value="<?=date('Y-m-d')?>">Hôm nay</option>
                <option value="<?=date('Y-m-d',strtotime('-1 day'))?>">Hôm qua</option>
                <option value="<?=date('Y-m-d',strtotime('+1 day'))?>">Ngày mai</option>
            </select>
            <button class="sao-bt-out" onclick="Ops.export()" style="white-space:nowrap">📥 Export</button>
        </div>
    </div>
    
    <!-- KPI Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:16px;margin-bottom:24px" id="ops-kpi">
        <div class="ops-card"><div class="ops-v">—</div><div class="ops-l">Booking</div></div>
        <div class="ops-card"><div class="ops-v">—</div><div class="ops-l">Doanh thu</div></div>
        <div class="ops-card"><div class="ops-v">—</div><div class="ops-l">Check-in</div></div>
        <div class="ops-card"><div class="ops-v">—</div><div class="ops-l">Công suất</div></div>
        <div class="ops-card"><div class="ops-v">—</div><div class="ops-l">No-show</div></div>
        <div class="ops-card"><div class="ops-v">—</div><div class="ops-l">Đang chờ</div></div>
        <div class="ops-card" style="border-left:3px solid #E53E3E"><div class="ops-v" style="color:#E53E3E" id="ops-alert">✅ OK</div><div class="ops-l">Cảnh báo</div></div>
    </div>
    
    <!-- Chart + Tables -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
        <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
            <h4 style="margin-bottom:16px">📈 Booking theo giờ</h4>
            <div id="ops-chart-container" style="height:200px;display:flex;align-items:flex-end;gap:4px;padding:10px 0;border-bottom:2px solid #ddd">
                <?php for($h=6;$h<=22;$h++): ?>
                <div style="flex:1;text-align:center"><div id="bar-<?=$h?>" style="background:#C9A96E;width:100%;min-height:4px;border-radius:4px 4px 0 0;transition:height .5s;height:4px"></div><small style="font-size:.65rem;color:#718096"><?=$h?>h</small></div>
                <?php endfor; ?>
            </div>
        </div>
        <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
            <h4 style="margin-bottom:16px">🎫 Top sản phẩm</h4>
            <table class="wp-list-table widefat"><thead><tr><th>Sản phẩm</th><th>Đã bán</th><th>Tổng</th><th>Còn</th></tr></thead>
            <tbody id="ops-products"><tr><td colspan="4" style="text-align:center;color:#718096">Đang tải...</td></tr></tbody></table>
        </div>
    </div>
    
    <!-- Booking List + QR Scanner -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px">
        <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
            <h4 style="margin-bottom:16px">📋 Booking hôm nay</h4>
            <div style="overflow-x:auto">
            <table class="wp-list-table widefat"><thead><tr><th>Mã</th><th>Khách</th><th>SĐT</th><th>SL</th><th>Tiền</th><th>TT</th><th>#</th></tr></thead>
            <tbody id="ops-bk-list"><tr><td colspan="7" style="text-align:center;color:#718096">Đang tải...</td></tr></tbody></table>
            </div>
        </div>
        <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.04)">
            <h4 style="margin-bottom:16px">📱 QR Check-in</h4>
            <input type="text" id="qr-scan" placeholder="Quét hoặc nhập mã booking..." style="width:100%;padding:14px;font-size:1.1rem;border:2px solid #C9A96E;border-radius:8px;font-family:monospace" onchange="Ops.checkQR(this.value)" autofocus>
            <div id="qr-result" style="margin-top:16px;padding:16px;border-radius:8px;display:none"></div>
            <div style="margin-top:16px;padding:12px;background:#F5F0E8;border-radius:8px;font-size:.85rem;color:#718096">
                <strong>Hướng dẫn:</strong> Nhập mã booking hoặc quét mã QR từ vé của khách. Hệ thống tự động check-in.
            </div>
        </div>
    </div>
    
    <!-- Capacity Alerts -->
    <div id="ops-capacity-alerts" style="display:none;background:#FFEBEE;border:2px solid #E53E3E;border-radius:12px;padding:20px;margin-bottom:24px">
        <h4 style="color:#B71C1C;margin-bottom:8px">⚠️ Cảnh báo công suất</h4>
        <div id="ops-alert-list"></div>
    </div>
</div>

<style>
.ops-card{background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.04);text-align:center}
.ops-card .ops-v{font-size:1.8rem;font-weight:700;color:#1B2A4A}.ops-card .ops-l{color:#718096;font-size:.8rem;margin-top:4px}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
var Ops={
    refresh:function(){
        var d=document.getElementById('ops-date').value;
        fetch(ajaxurl+'?action=sao_ops_kpi&date='+d).then(r=>r.json()).then(function(j){
            if(!j.success)return;
            var s=j.data,kpi=document.getElementById('ops-kpi').children;
            kpi[0].querySelector('.ops-v').textContent=s.bookings||0;
            kpi[1].querySelector('.ops-v').textContent=(s.revenue||0).toLocaleString('vi-VN')+'đ';
            kpi[2].querySelector('.ops-v').textContent=s.checked_in||0;
            kpi[3].querySelector('.ops-v').textContent=(s.fill_rate||0)+'%';
            kpi[4].querySelector('.ops-v').textContent=(s.no_show||0);
            kpi[5].querySelector('.ops-v').textContent=(s.pending||0);
            // Alert
            var alert=document.getElementById('ops-alert');
            if(s.fill_rate>80){alert.textContent='⚠️ GẦN FULL';alert.style.color='#E53E3E';}
            else if(s.fill_rate>95){alert.textContent='🚫 FULL';alert.style.color='#B71C1C';}
            else{alert.textContent='✅ OK';alert.style.color='#38A169';}
            // Chart
            if(s.hourly)for(var h in s.hourly){var bar=document.getElementById('bar-'+h);if(bar){var max=Math.max(1,...Object.values(s.hourly));bar.style.height=(s.hourly[h]/max*180+4)+'px';bar.title=s.hourly[h]+' booking';}}
        });
        // Products
        fetch(ajaxurl+'?action=sao_ops_chart&date='+d).then(r=>r.json()).then(function(j){
            if(!j.success)return;
            var t=document.getElementById('ops-products'),h='';
            j.data.forEach(function(p){h+='<tr><td><strong>'+p.name+'</strong></td><td>'+p.sold+'</td><td>'+p.capacity+'</td><td style=color:'+(p.capacity-p.sold<10?'#E53E3E':'#38A169')+'>'+(p.capacity-p.sold)+'</td></tr>';});
            t.innerHTML=h||'<tr><td colspan=4>Không có dữ liệu</td></tr>';
        });
        // Bookings
        fetch(ajaxurl+'?action=sao_partner_bookings&date='+d).then(r=>r.json()).then(function(j){
            var t=document.getElementById('ops-bk-list'),h='';
            if(j.success&&j.data.length>0)j.data.forEach(function(b){h+='<tr><td><code>'+b.code+'</code></td><td>'+b.name+'</td><td>'+b.phone+'</td><td>'+b.guests+'</td><td>'+b.total.toLocaleString()+'đ</td><td>'+b.status+'</td><td>'+(b.status!=='checked_in'?'<button class=sao-bt style=padding:4px 8px;font-size:.75rem onclick=\"Ops.checkQR(\\''+b.code+'\\')\">Check-in</button>':'✅')+'</td></tr>';});
            else h='<tr><td colspan=7 style=text-align:center;color:#718096>Chưa có booking</td></tr>';
            t.innerHTML=h;
        });
    },
    checkQR:function(c){
        var r=document.getElementById('qr-result');
        r.style.display='block';
        fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=sao_check_qr&code='+encodeURIComponent(c)}).then(r=>r.json()).then(function(d){
            var el=document.getElementById('qr-result');
            if(d.success){el.style.background='#E6F7E6';el.style.border='1px solid #A5D6A7';el.style.color='#1B5E20';el.innerHTML='<strong>✅ CHECK-IN THÀNH CÔNG!</strong><br>Khách: '+d.data.booking.customer_name+'<br>Mã: '+d.data.booking.booking_code+'<br>SL: '+d.data.booking.guests+' người';}
            else{el.style.background='#FFEBEE';el.style.border='1px solid #EF9A9A';el.style.color='#B71C1C';el.innerHTML='<strong>❌ LỖI</strong><br>'+d.data.message;}
            document.getElementById('qr-scan').value='';Ops.refresh();
        });
    },
    export:function(){window.open(ajaxurl+'?action=sao_ops_export&date='+document.getElementById('ops-date').value);}
};
document.addEventListener('DOMContentLoaded',function(){Ops.refresh();setInterval(Ops.refresh,60000);});
</script>
<?php return ob_get_clean(); }

    /* ═══════════ AJAX HANDLERS ═══════════ */
    public function ajax_partner_register() {
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $pw = $_POST['pw'] ?? '';
        if(!$email || strlen($pw) < 6) wp_send_json_error(['message'=>'Email và mật khẩu (tối thiểu 6 ký tự) là bắt buộc']);
        if(email_exists($email)) wp_send_json_error(['message'=>'Email đã được sử dụng']);
        
        $uid = wp_create_user($email, $pw, $email);
        if(is_wp_error($uid)) wp_send_json_error(['message'=>$uid->get_error_message()]);
        
        wp_update_user(['ID'=>$uid, 'display_name'=>sanitize_text_field($_POST['name'] ?? '')]);
        update_user_meta($uid, 'phone', $phone);
        update_user_meta($uid, 'partner_type', sanitize_text_field($_POST['type'] ?? 'agent'));
        
        // Create affiliate record
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $ref = 'SAO' . strtoupper(substr(md5($email.time()),0,6));
        $wpdb->insert($p.'affiliates', ['wp_user_id'=>$uid, 'full_name'=>sanitize_text_field($_POST['name'] ?? ''), 'email'=>$email, 'phone'=>$phone, 'ref_code'=>$ref, 'commission'=>10, 'status'=>'active']);
        
        // Auto-login
        wp_set_current_user($uid); wp_set_auth_cookie($uid);
        wp_send_json_success(['message'=>'Đăng ký thành công! Mã giới thiệu: '.$ref, 'ref_code'=>$ref]);
    }
    
    public function ajax_partner_login() {
        $login = sanitize_text_field($_POST['login'] ?? '');
        $pw = $_POST['pw'] ?? '';
        $user = wp_authenticate($login, $pw);
        if(is_wp_error($user)) wp_send_json_error(['message'=>'Sai email/mật khẩu']);
        wp_set_current_user($user->ID); wp_set_auth_cookie($user->ID);
        wp_send_json_success(['message'=>'Đăng nhập thành công! Đang chuyển hướng...']);
    }
    
    public function ajax_partner_data() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $aff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}affiliates WHERE wp_user_id=%d", get_current_user_id()));
        if(!$aff) wp_send_json_error(['message'=>'Không tìm thấy']);
        
        if(isset($_POST['payout'])) {
            $amt = (int)$_POST['payout'];
            if($amt < 500000) wp_send_json_error(['message'=>'Tối thiểu 500.000đ']);
            if($amt > $aff->pending_earnings) wp_send_json_error(['message'=>'Số dư không đủ']);
            $wpdb->insert($p.'affiliate_payouts', ['affiliate_id'=>$aff->id, 'amount'=>$amt, 'method'=>'bank', 'status'=>'pending']);
            $wpdb->query("UPDATE {$p}affiliates SET pending_earnings=pending_earnings-$amt WHERE id={$aff->id}");
            wp_send_json_success(['message'=>'Đã gửi yêu cầu thanh toán '.number_format($amt).'đ']);
        }
        wp_send_json_success(['affiliate'=>$aff]);
    }
    
    public function ajax_partner_bookings() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $date = $_GET['date'] ?? date('Y-m-d');
        $aff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}affiliates WHERE wp_user_id=%d", get_current_user_id()));
        
        if($aff) {
            $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$p}bookings WHERE notes LIKE %s AND booking_date=%s ORDER BY created_at DESC", '%ref:'.$aff->ref_code.'%', $date));
        } else {
            $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$p}bookings WHERE booking_date=%s ORDER BY created_at DESC LIMIT 50", $date));
        }
        
        $out = [];
        foreach($bookings as $b) {
            $commission = 0;
            if($aff) $commission = round($b->total * $aff->commission / 100);
            $status_labels = ['pending'=>'⏳ Chờ','confirmed'=>'✅ Xác nhận','checked_in'=>'🟢 Check-in','completed'=>'✔️ Xong','no_show'=>'❌ No-show','cancelled'=>'✖️ Hủy'];
            $out[] = ['code'=>$b->booking_code,'date'=>$b->booking_date,'name'=>$b->customer_name,'phone'=>$b->customer_phone,'guests'=>(int)$b->guests,'total'=>(int)$b->total,'status'=>$status_labels[$b->booking_status]??$b->booking_status,'commission'=>$commission];
        }
        wp_send_json_success($out);
    }
    
    public function ajax_ops_kpi() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $date = $_GET['date'] ?? date('Y-m-d');
        $bookings = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date=%s", $date));
        $revenue = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date=%s", $date));
        $checked = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date=%s AND qr_scanned=1", $date));
        $no_show = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date=%s AND booking_status='no_show'", $date));
        $pending = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date=%s AND booking_status='pending'", $date));
        $cap = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_capacity),0) FROM {$p}inventory WHERE booking_date=%s", $date));
        $sold = (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(reserved),0) FROM {$p}inventory WHERE booking_date=%s", $date));
        
        // Hourly breakdown
        $hourly = [];
        for($h=6;$h<=22;$h++) {
            $hourly[$h] = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date=%s AND HOUR(created_at)=%d", $date, $h));
        }
        
        wp_send_json_success(['bookings'=>$bookings,'revenue'=>$revenue,'checked_in'=>$checked,'no_show'=>$no_show,'pending'=>$pending,'capacity'=>$cap,'sold'=>$sold,'fill_rate'=>$cap>0?round($sold/$cap*100,1):0,'hourly'=>$hourly]);
    }
    
    public function ajax_ops_chart() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $date = $_GET['date'] ?? date('Y-m-d');
        $products = $wpdb->get_results("SELECT pr.name, COALESCE(inv.total_capacity,pr.capacity) as capacity, COALESCE(inv.reserved,0) as sold FROM {$p}products pr LEFT JOIN {$p}inventory inv ON pr.id=inv.product_id AND inv.booking_date='$date' WHERE pr.status='active' ORDER BY sold DESC");
        $out = [];
        foreach($products as $pr) $out[] = ['name'=>$pr->name,'capacity'=>(int)$pr->capacity,'sold'=>(int)$pr->sold];
        wp_send_json_success($out);
    }
    
    public function ajax_ops_export() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $date = $_GET['date'] ?? date('Y-m-d');
        $bookings = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$p}bookings WHERE booking_date=%s", $date));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bookings-'.$date.'.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Mã','Tên khách','Email','SĐT','Ngày','SL khách','Tổng tiền','Trạng thái']);
        foreach($bookings as $b) fputcsv($out, [$b->booking_code,$b->customer_name,$b->customer_email,$b->customer_phone,$b->booking_date,$b->guests,$b->total,$b->booking_status]);
        fclose($out); exit;
    }
    
    public function ajax_aff_link() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $aff = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}affiliates WHERE wp_user_id=%d", get_current_user_id()));
        wp_send_json_success(['link'=>home_url('/?ref='.($aff->ref_code??''))]);
    }
}
SAO_Partner::init();
