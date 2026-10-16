<?php
/**
 * SAO PHU QUOC - Booking Engine (Consolidated)
 * Drop this in theme folder and include in functions.php
 */
class SAO_Booking {

    private static $ins = null;
    public static function init() { if(!self::$ins) { self::$ins = new self(); self::$ins->hooks(); } return self::$ins; }

    public function hooks() {
        add_shortcode('sao_booking', [$this, 'booking_form']);
        add_shortcode('sao_dashboard', [$this, 'dashboard_page']);
        add_shortcode('sao_partner', [$this, 'partner_page']);
        foreach(['sao_get_inventory','sao_create_booking','sao_check_qr','sao_dashboard_data','sao_vnpay_return'] as $a) {
            add_action("wp_ajax_$a", [$this, str_replace('sao_','ajax_',$a)]);
            add_action("wp_ajax_nopriv_$a", [$this, str_replace('sao_','ajax_',$a)]);
        }
        $this->maybe_create_tables();
    }

    /* ── DATABASE ── */
    public function maybe_create_tables() {
        global $wpdb; $p = $wpdb->prefix . 'sao_'; $c = $wpdb->get_charset_collate();
        $tables = [
            "products" => "CREATE TABLE IF NOT EXISTS {$p}products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255), type VARCHAR(30) DEFAULT 'day_pass', brand VARCHAR(30) DEFAULT 'bai_sao', short_desc VARCHAR(500), base_price INT DEFAULT 0, capacity INT DEFAULT 100, status VARCHAR(20) DEFAULT 'active') $c",
            "inventory" => "CREATE TABLE IF NOT EXISTS {$p}inventory (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, booking_date DATE, time_slot VARCHAR(10) DEFAULT 'all_day', total_capacity INT DEFAULT 100, reserved INT DEFAULT 0, status VARCHAR(20) DEFAULT 'open', UNIQUE KEY slot (product_id, booking_date, time_slot)) $c",
            "bookings" => "CREATE TABLE IF NOT EXISTS {$p}bookings (id INT AUTO_INCREMENT PRIMARY KEY, booking_code VARCHAR(30) UNIQUE, customer_name VARCHAR(255), customer_email VARCHAR(255), customer_phone VARCHAR(50), booking_date DATE, guests INT DEFAULT 1, guest_type VARCHAR(30) DEFAULT 'individual', subtotal DECIMAL(12,2) DEFAULT 0, total DECIMAL(12,2) DEFAULT 0, deposit DECIMAL(12,2) DEFAULT 0, paid DECIMAL(12,2) DEFAULT 0, payment_status VARCHAR(20) DEFAULT 'pending', booking_status VARCHAR(20) DEFAULT 'pending', qr_code VARCHAR(255), qr_scanned TINYINT DEFAULT 0, source VARCHAR(50) DEFAULT 'direct', notes TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
            "booking_items" => "CREATE TABLE IF NOT EXISTS {$p}booking_items (id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT, product_id INT, product_name VARCHAR(255), qty INT DEFAULT 1, unit_price DECIMAL(12,2), total_price DECIMAL(12,2)) $c",
            "payments" => "CREATE TABLE IF NOT EXISTS {$p}payments (id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT, txn_id VARCHAR(255), method VARCHAR(20) DEFAULT 'vnpay', amount DECIMAL(12,2), status VARCHAR(20) DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c",
            "partners" => "CREATE TABLE IF NOT EXISTS {$p}partners (id INT AUTO_INCREMENT PRIMARY KEY, wp_user_id INT, company VARCHAR(255), email VARCHAR(255), phone VARCHAR(50), type VARCHAR(30) DEFAULT 'agent', commission DECIMAL(5,2) DEFAULT 10, ref_code VARCHAR(50) UNIQUE, status VARCHAR(20) DEFAULT 'active') $c",
        ];
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach($tables as $t) dbDelta($t);
        
        // Seed products if empty
        if($wpdb->get_var("SELECT COUNT(*) FROM {$p}products") == 0) {
            $items = [
                ['Vé Bãi Sao - Day Pass','day-pass','day_pass','bai_sao',150000,5000,'Vé tham quan trọn ngày'],
                ['Ghế Lounger','lounger','lounger','bai_sao',250000,200,'Ghế nằm thư giãn bên biển'],
                ['Cabana','cabana','cabana','bai_sao',800000,30,'Cabana riêng tư 2-4 người'],
                ['Glamping','glamping','glamping','bai_sao',1500000,20,'Cắm trại sang trọng'],
                ['Team Building','team','event','bai_sao',500000,500,'Team building 20-50 người'],
                ['Set menu Gánh Ngon','set-menu','fb','ganh_ngon',400000,200,'Set menu gia đình'],
                ['Tour Paghera Yacht','yacht','event','paghera_yacht',2500000,30,'Du ngoạn du thuyền'],
            ];
            foreach($items as $i) $wpdb->insert($p.'products',['name'=>$i[0],'slug'=>$i[1],'type'=>$i[2],'brand'=>$i[3],'base_price'=>$i[4],'capacity'=>$i[5],'short_desc'=>$i[6],'status'=>'active']);
            $ids = $wpdb->get_col("SELECT id FROM {$p}products");
            foreach($ids as $pid) for($d=0;$d<30;$d++) $wpdb->insert($p.'inventory',['product_id'=>$pid,'booking_date'=>date('Y-m-d',strtotime("+$d days")),'total_capacity'=>rand(50,300),'reserved'=>0]);
        }
    }

    /* ── SHORTCODES ── */
    public function booking_form() {
        ob_start(); ?>
<div id="sao-booking-app" style="max-width:700px;margin:0 auto">
    <div id="sao-step-1" class="sao-bs active">
        <h3 id="sao-form-title">📅 Đặt trải nghiệm SAO PHU QUOC</h3>
        <script>document.getElementById('sao-form-title').textContent=SBO.brand==='ganh_ngon'?'🍜 Đặt bàn Gánh Ngon':SBO.brand==='paghera_yacht'?'🛥️ Đặt du thuyền Paghera':SBO.brand==='bai_sao'?'🏖️ Đặt vé Bãi Sao':'📅 Đặt trải nghiệm SAO PHU QUOC';</script>
        <div class="sao-row"><div class="sao-f"><label>Ngày đi</label><input type="date" id="sao-date" min="<?=date('Y-m-d')?>" value="<?=date('Y-m-d')?>" class="sao-in"></div><div class="sao-f"><label>Số khách</label><input type="number" id="sao-guests" min="1" max="9999" value="2" class="sao-in" placeholder="Nhập số lượng khách"></div></div>
        <div class="sao-row"><div class="sao-f"><label>Loại khách</label><select id="sao-gtype" class="sao-in"><option value="individual">Cá nhân</option><option value="family">Gia đình</option><option value="couple">Cặp đôi</option><option value="group">Nhóm bạn</option><option value="corporate">Công ty / Đoàn</option><option value="vip">Khách VIP</option></select></div><div class="sao-f"><label>Ngân sách dự kiến</label><select id="sao-budget" class="sao-in"><option value="">Chọn</option><option value="under1m">< 1 triệu</option><option value="1-3m">1-3 triệu</option><option value="3-5m">3-5 triệu</option><option value="above5m">> 5 triệu</option></select></div></div>
        <button class="sao-bt" onclick="SBO.step2()">Gợi ý combo phù hợp →</button>
    </div>
    
    <div id="sao-step-2" class="sao-bs" style="display:none">
        <h3>🎫 Sản phẩm có sẵn</h3>
        <div id="sao-products"></div>
        <div id="sao-summary" style="display:none;background:#F5F0E8;padding:16px;border-radius:8px;margin:12px 0"></div>
        <div style="display:flex;gap:12px"><button class="sao-bt-out" onclick="SBO.back(1)">← Quay lại</button><button class="sao-bt" onclick="SBO.step3()">Tiếp tục →</button></div>
    </div>
    
    <div id="sao-step-3" class="sao-bs" style="display:none">
        <h3>👤 Thông tin liên hệ</h3>
        <div class="sao-row"><div class="sao-f"><label>Họ và tên *</label><input type="text" id="sao-name" class="sao-in" required></div><div class="sao-f"><label>Email *</label><input type="email" id="sao-email" class="sao-in" required></div></div>
        <div class="sao-row"><div class="sao-f"><label>Số điện thoại *</label><input type="tel" id="sao-phone" class="sao-in" required></div><div class="sao-f"><label>Ghi chú / Yêu cầu đặc biệt</label><textarea id="sao-notes" class="sao-in" rows="2"></textarea></div></div>
        <div style="display:flex;gap:12px"><button class="sao-bt-out" onclick="SBO.back(2)">← Quay lại</button><button class="sao-bt" onclick="SBO.submit()">Đặt chỗ →</button></div>
    </div>
    
    <div id="sao-step-4" class="sao-bs" style="display:none">
        <h3>💳 Thanh toán</h3>
        <div class="sao-pay-opts">
            <label class="sao-rc"><input type="radio" name="pmt" value="vnpay" checked> 🏦 VNPay (Thẻ ATM, Visa, Mastercard, QR)</label>
            <label class="sao-rc"><input type="radio" name="pmt" value="transfer"> 🏧 Chuyển khoản ngân hàng</label>
            <label class="sao-rc"><input type="radio" name="pmt" value="counter"> 💵 Thanh toán tại quầy (cọc 30%)</label>
        </div>
        <div id="sao-result" style="display:none;padding:20px;border-radius:8px;margin:16px 0"></div>
        <button class="sao-bt" id="sao-pay-btn" onclick="SBO.pay()">Thanh toán ngay</button>
    </div>
    
    <div id="sao-loading" style="display:none;text-align:center;padding:40px"><div class="sao-spin"></div><p style="margin-top:12px;color:#718096">Đang xử lý...</p></div>
</div>

<style>
.sao-bs{padding:24px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.06);margin-bottom:20px}
.sao-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.sao-f label{display:block;font-weight:600;color:#1B2A4A;margin-bottom:6px;font-size:.9rem}
.sao-in{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:.95rem;font-family:inherit;box-sizing:border-box}
.sao-bt{padding:14px 36px;background:#C9A96E;color:#fff;border:none;border-radius:6px;font-weight:600;font-size:1rem;cursor:pointer;font-family:inherit;width:100%}
.sao-bt:hover{background:#A88B50}
.sao-bt-out{padding:14px 24px;border:2px solid #1B2A4A;background:transparent;color:#1B2A4A;border-radius:6px;font-weight:600;cursor:pointer;font-family:inherit}
.sao-pc{border:2px solid #eee;border-radius:8px;padding:16px;cursor:pointer;transition:.2s;margin:8px 0}
.sao-pc.sel{border-color:#C9A96E;background:#FDF8F0}
.sao-pc h4{margin:0 0 4px}.sao-pc .pr{color:#C9A96E;font-weight:700;font-size:1.1rem}.sao-pc .av{font-size:.85rem;color:#38A169}
.sao-rc{display:flex;align-items:center;gap:10px;padding:16px;border:2px solid #eee;border-radius:8px;margin:8px 0;cursor:pointer}
.sao-rc:has(input:checked){border-color:#C9A96E;background:#FDF8F0}
.sao-spin{width:40px;height:40px;border:3px solid #eee;border-top-color:#C9A96E;border-radius:50%;animation:spin .6s linear infinite;margin:0 auto}@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:768px){.sao-row{grid-template-columns:1fr}}
</style>

<script>
var SBO={step:1,products:[],total:0,bid:0,code:'',brand:new URLSearchParams(window.location.search).get('brand')||''};
SBO.step2=function(){
    var d=document.getElementById('sao-date').value;
    document.getElementById('sao-loading').style.display='block';
    document.getElementById('sao-step-1').style.display='none';
    fetch('/wp-admin/admin-ajax.php?action=sao_get_inventory&date='+d+'&brand='+(SBO.brand||'')).then(r=>r.json()).then(function(r){
        if(!r.success){alert('Lỗi tải sản phẩm');return;}
        var h='';r.products.forEach(function(p){h+='<div class="sao-pc" data-id="'+p.id+'" data-price="'+p.price+'" onclick="SBO.toggle(this)"><h4>'+p.name+'</h4><div class="pr">'+p.price_formatted+'</div><div class="av">Còn '+p.available+' chỗ</div><div class="qty" style="display:none;margin-top:8px">SL: <input type="number" min="1" max="'+p.available+'" value="1" onchange="SBO.update(event,'+p.id+')" style="width:70px;padding:4px" onclick="event.stopPropagation()"></div></div>';});
        document.getElementById('sao-products').innerHTML=h||'<p style="color:#718096">Không có sản phẩm cho ngày này</p>';
        document.getElementById('sao-loading').style.display='none';
        document.getElementById('sao-step-2').style.display='block';
    });
};
SBO.toggle=function(el){
    var q=el.querySelector('.qty'),id=parseInt(el.dataset.id),price=parseInt(el.dataset.price);
    if(el.classList.contains('sel')){el.classList.remove('sel');q.style.display='none';SBO.products=SBO.products.filter(function(p){return p.id!=id;});}
    else{el.classList.add('sel');q.style.display='block';SBO.products.push({id:id,price:price,qty:1});}
    SBO.updateSummary();
};
SBO.update=function(e,id){var p=SBO.products.find(function(p){return p.id==id;});if(p)p.qty=parseInt(e.target.value);SBO.updateSummary();};
SBO.updateSummary=function(){SBO.total=0;SBO.products.forEach(function(p){SBO.total+=p.price*p.qty;});var s=document.getElementById('sao-summary');if(SBO.total>0){s.style.display='block';s.innerHTML='<strong>Tổng tạm tính:</strong> <span style="font-size:1.3rem;color:#C9A96E;font-weight:700">'+SBO.total.toLocaleString('vi-VN')+'đ</span><br><span style="font-size:.85rem;color:#718096">Cọc 30%: '+Math.round(SBO.total*0.3).toLocaleString('vi-VN')+'đ</span>';}else{s.style.display='none';}};
SBO.step3=function(){if(SBO.products.length===0){alert('Vui lòng chọn ít nhất 1 sản phẩm');return;}document.getElementById('sao-step-2').style.display='none';document.getElementById('sao-step-3').style.display='block';};
SBO.back=function(s){document.getElementById('sao-step-'+SBO.step).style.display='none';SBO.step=s;document.getElementById('sao-step-'+s).style.display='block';};
SBO.submit=function(){
    var d={action:'sao_create_booking',brand:SBO.brand,date:document.getElementById('sao-date').value,guests:document.getElementById('sao-guests').value,gtype:document.getElementById('sao-gtype').value,products:JSON.stringify(SBO.products),name:document.getElementById('sao-name').value,email:document.getElementById('sao-email').value,phone:document.getElementById('sao-phone').value,notes:document.getElementById('sao-notes').value};
    if(!d.name||!d.email||!d.phone){alert('Vui lòng điền đầy đủ thông tin');return;}
    document.getElementById('sao-loading').style.display='block';document.getElementById('sao-step-3').style.display='none';
    fetch('/wp-admin/admin-ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(d)}).then(r=>r.json()).then(function(r){
        document.getElementById('sao-loading').style.display='none';
        if(r.success){SBO.bid=r.booking_id;SBO.code=r.code;SBO.total=r.total;document.getElementById('sao-step-4').style.display='block';}
        else{alert('Lỗi: '+r.message);document.getElementById('sao-step-3').style.display='block';}
    });
};
SBO.pay=function(){
    var pmt=document.querySelector('input[name=pmt]:checked').value,r=document.getElementById('sao-result'),b=document.getElementById('sao-pay-btn');
    if(pmt=='vnpay'){window.location.href='/sao-pay/?bid='+SBO.bid;}
    else{r.style.display='block';r.style.background='#E6F7E6';r.style.color='#1B5E20';r.style.border='1px solid #A5D6A7';r.innerHTML='<h4>✅ Đặt chỗ thành công!</h4><p><strong>Mã đặt chỗ:</strong> '+SBO.code+'</p><p><strong>Tổng tiền:</strong> '+SBO.total.toLocaleString('vi-VN')+'đ</p><p><strong>Cọc 30%:</strong> '+Math.round(SBO.total*0.3).toLocaleString('vi-VN')+'đ</p><p style="margin-top:8px">'+ (pmt=='transfer'?'Vui lòng chuyển khoản theo hướng dẫn bên dưới.<br>Ngân hàng: Vietcombank<br>STK: xxxxxxxxxx<br>Chủ TK: SAO PHU QUOC':'Quý khách vui lòng đến đúng giờ và thanh toán tại quầy.')+'</p>';b.style.display='none';}
};
</script>
<?php return ob_get_clean(); }

    public function dashboard_page() {
        if(!current_user_can('manage_options')) return '<p>Bạn không có quyền truy cập.</p>';
        ob_start(); ?>
<div style="max-width:1200px;margin:0 auto">
    <h2>🏖️ SAO PHU QUOC - Dashboard</h2>
    <div id="sao-dash-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:24px 0"><div class="sao-load">Đang tải...</div></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04)"><h3>📋 Booking hôm nay</h3><table class="wp-list-table widefat" id="sao-today-table"><thead><tr><th>Mã</th><th>Khách</th><th>SL</th><th>Tiền</th><th>TT</th></tr></thead><tbody id="sao-today-list"><tr><td colspan="5">Đang tải...</td></tr></tbody></table></div>
        <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04)"><h3>📱 QR Check-in</h3><input type="text" id="qr-input" placeholder="Quét hoặc nhập mã booking..." style="width:100%;padding:12px;font-size:1.1rem;border:2px solid #C9A96E;border-radius:6px" onchange="SaoDash.checkQR(this.value)"><div id="qr-result" style="margin-top:12px"></div></div>
    </div>
</div>
<style>.sao-stat{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);text-align:center}.sao-stat .v{font-size:2rem;font-weight:700;color:#1B2A4A}.sao-stat .l{color:#718096;font-size:.85rem;margin-top:4px}</style>
<script>
var SaoDash={init:function(){fetch(ajaxurl+'?action=sao_dashboard_data').then(r=>r.json()).then(function(d){if(!d.success)return;var s=d.data,h='';h+='<div class="sao-stat"><div class="v">'+s.bookings+'</div><div class="l">Booking</div></div>';h+='<div class="sao-stat"><div class="v">'+s.revenue.toLocaleString('vi-VN')+'đ</div><div class="l">Doanh thu</div></div>';h+='<div class="sao-stat"><div class="v">'+s.checked_in+'</div><div class="l">Check-in</div></div>';h+='<div class="sao-stat"><div class="v">'+s.fill_rate+'%</div><div class="l">Công suất</div></div>';h+='<div class="sao-stat"><div class="v">'+(s.capacity-s.sold)+'/'+s.capacity+'</div><div class="l">Còn trống</div></div>';document.getElementById('sao-dash-stats').innerHTML=h;});},checkQR:function(c){var r=document.getElementById('qr-result');r.innerHTML='Đang kiểm tra...';fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=sao_check_qr&code='+encodeURIComponent(c)}).then(r=>r.json()).then(function(d){r.innerHTML=d.success?'<div style="color:green;font-size:1.1rem">✅ '+d.data.message+' - Khách: '+d.data.booking.customer_name+'</div>':'<div style="color:red">❌ '+d.data.message+'</div>';document.getElementById('qr-input').value='';});}};
document.addEventListener('DOMContentLoaded',function(){SaoDash.init();});
</script>
<?php return ob_get_clean(); }

    public function partner_page() {
        if(!is_user_logged_in()) return '<div style="text-align:center;padding:40px"><h3>🔐 Portal Đối Tác</h3>'.wp_login_form(['echo'=>false]).'</div>';
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $partner = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}partners WHERE wp_user_id=%d", get_current_user_id()));
        if(!$partner) return '<div style="text-align:center;padding:40px"><h3>🤝 Đăng ký Đối Tác</h3><p>Tài khoản của bạn chưa được đăng ký làm đối tác. Vui lòng liên hệ admin.</p></div>';
        ob_start(); ?>
<div style="max-width:1000px;margin:0 auto">
    <h3>🤝 Portal Đối Tác - <?=esc_html($partner->company)?></h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:24px 0">
        <div class="sao-stat"><div class="v"><?=esc_html($partner->ref_code)?></div><div class="l">Mã giới thiệu</div></div>
        <div class="sao-stat"><div class="v"><?=esc_html($partner->commission)?>%</div><div class="l">Hoa hồng</div></div>
        <div class="sao-stat"><div class="v"><?=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$p}bookings WHERE source='partner' AND notes LIKE %s", '%'.$partner->ref_code.'%'))?></div><div class="l">Booking</div></div>
        <div class="sao-stat"><div class="v"><?=number_format($wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE source='partner' AND notes LIKE %s", '%'.$partner->ref_code.'%')))?>đ</div><div class="l">Doanh số</div></div>
    </div>
    <p><strong>Link giới thiệu:</strong> <code>https://dulich.taichinhachau.com/?ref=<?=esc_html($partner->ref_code)?></code></p>
    <h4>📊 Booking của bạn</h4>
    <table class="wp-list-table widefat"><thead><tr><th>Mã</th><th>Ngày</th><th>Khách</th><th>SL</th><th>Tiền</th><th>Hoa hồng</th><th>TT</th></tr></thead><tbody id="partner-bookings"><tr><td colspan="7">Đang tải...</td></tr></tbody></table>
</div>
<script>
fetch(ajaxurl+'?action=sao_dashboard_data&ref=<?=esc_js($partner->ref_code)?>').then(r=>r.json()).then(function(d){
    var t=document.getElementById('partner-bookings'),h='';
    if(d.success&&d.data.bookings_list){d.data.bookings_list.forEach(function(b){h+='<tr><td>'+b.code+'</td><td>'+b.date+'</td><td>'+b.name+'</td><td>'+b.guests+'</td><td>'+b.total.toLocaleString()+'đ</td><td>'+Math.round(b.total*<?=$partner->commission?>/100).toLocaleString()+'đ</td><td>'+b.status+'</td></tr>';});}
    t.innerHTML=h||'<tr><td colspan="7" style="text-align:center;color:#718096">Chưa có booking</td></tr>';
});
</script>
<?php return ob_get_clean(); }

    /* ── AJAX HANDLERS ── */
    public function ajax_get_inventory() {
        global $wpdb; $p = $wpdb->prefix . 'sao_'; $date = $_GET['date'] ?? date('Y-m-d');
        $brand = $_GET['brand'] ?? '';
        $brand_sql = $brand ? $wpdb->prepare(" AND pr.brand=%s", $brand) : '';
        $prods = $wpdb->get_results("SELECT pr.*, COALESCE(inv.total_capacity,pr.capacity) as cap, COALESCE(inv.reserved,0) as sold FROM {$p}products pr LEFT JOIN {$p}inventory inv ON pr.id=inv.product_id AND inv.booking_date='$date' WHERE pr.status='active'$brand_sql ORDER BY pr.id");
        $out = [];
        foreach($prods as $pr) $out[] = ['id'=>(int)$pr->id,'name'=>$pr->name,'price'=>(int)$pr->base_price,'price_formatted'=>number_format($pr->base_price).'đ','available'=>max(0,$pr->cap-$pr->sold),'type'=>$pr->type];
        wp_send_json_success(['products'=>$out,'date'=>$date,'brand'=>$brand]);
    }

    public function ajax_create_booking() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $brand = $_POST['brand'] ?? ($_GET['brand'] ?? 'bai_sao');
        $code = 'SAO' . date('Ymd') . strtoupper(substr(md5(uniqid()),0,4));
        $total = 0;
        $wpdb->insert($p.'bookings',['booking_code'=>$code,'customer_name'=>sanitize_text_field($_POST['name']),'customer_email'=>sanitize_email($_POST['email']),'customer_phone'=>sanitize_text_field($_POST['phone']),'booking_date'=>$_POST['date'],'guests'=>(int)$_POST['guests'],'guest_type'=>$_POST['gtype']??'individual','notes'=>sanitize_textarea_field($_POST['notes']??''),'booking_status'=>'pending','payment_status'=>'pending']);
        $bid = $wpdb->insert_id;
        $products = json_decode(stripslashes($_POST['products']), true);
        if(is_array($products)) foreach($products as $pr) {
            $prod = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}products WHERE id=%d", $pr['id']));
            if($prod) { $line = $prod->base_price * (int)$pr['qty']; $total += $line;
                $wpdb->insert($p.'booking_items',['booking_id'=>$bid,'product_id'=>$prod->id,'product_name'=>$prod->name,'qty'=>(int)$pr['qty'],'unit_price'=>$prod->base_price,'total_price'=>$line]);
                $wpdb->query("UPDATE {$p}inventory SET reserved=reserved+1 WHERE product_id={$prod->id} AND booking_date='{$_POST['date']}'"); }
        }
        $deposit = round($total * 0.3);
        $wpdb->update($p.'bookings',['total'=>$total,'subtotal'=>$total,'deposit'=>$deposit],['id'=>$bid]);
        // Auto-create CRM customer record (custom CRM)
        $wpdb->query("INSERT IGNORE INTO {$wpdb->prefix}sao_crm_customers (full_name, phone, email, customer_type, source, notes) VALUES ('".esc_sql(sanitize_text_field($_POST['name']))."','".esc_sql(sanitize_text_field($_POST['phone']))."','".esc_sql(sanitize_email($_POST['email']))."','".esc_sql($_POST['gtype']??'individual')."','website','Booking: $code')");
        
        // Also sync to Jetpack CRM
        if(function_exists('zeroBS_addCustomer')) {
            $cust_id = zeroBS_addCustomer([
                'fname' => sanitize_text_field($_POST['name']),
                'email' => sanitize_email($_POST['email']),
                'mobtel' => sanitize_text_field($_POST['phone']),
                'tags' => ['booking','website',$brand],
                'notes' => 'Booking: ' . $code . ' — Guests: ' . (int)$_POST['guests'],
            ]);
        }
        // Generate QR code
        $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($code);
        $wpdb->update($p.'bookings',['qr_code'=>$qr],['id'=>$bid]);
        wp_send_json_success(['booking_id'=>$bid,'code'=>$code,'total'=>$total,'deposit'=>$deposit,'qr'=>$qr]);
    }

    public function ajax_check_qr() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $code = sanitize_text_field($_POST['code'] ?? '');
        $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}bookings WHERE booking_code=%s", $code));
        if(!$b) wp_send_json_error(['message'=>'Không tìm thấy booking']);
        if($b->qr_scanned) wp_send_json_error(['message'=>'QR đã được sử dụng lúc '.$b->qr_scanned_at]);
        $wpdb->update($p.'bookings',['qr_scanned'=>1,'qr_scanned_at'=>current_time('mysql'),'booking_status'=>'checked_in'],['id'=>$b->id]);
        wp_send_json_success(['booking'=>$b,'message'=>'Check-in thành công - Chào mừng '.$b->customer_name.'!']);
    }

    public function ajax_dashboard_data() {
        global $wpdb; $p = $wpdb->prefix . 'sao_'; $today = date('Y-m-d');
        $bookings = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today'");
        $revenue = (int)$wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date='$today'");
        $checked = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today' AND qr_scanned=1");
        $cap = (int)$wpdb->get_var("SELECT COALESCE(SUM(total_capacity),0) FROM {$p}inventory WHERE booking_date='$today'");
        $sold = (int)$wpdb->get_var("SELECT COALESCE(SUM(reserved),0) FROM {$p}inventory WHERE booking_date='$today'");
        wp_send_json_success(['bookings'=>$bookings,'revenue'=>$revenue,'checked_in'=>$checked,'capacity'=>$cap,'sold'=>$sold,'fill_rate'=>$cap>0?round($sold/$cap*100,1):0]);
    }

    public function ajax_vnpay_return() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $code = $_GET['vnp_TxnRef'] ?? '';
        $code = explode('_', $code)[0];
        $status = $_GET['vnp_ResponseCode'] ?? '';
        if($status == '00') {
            $wpdb->update($p.'bookings',['payment_status'=>'paid','paid'=>$wpdb->get_var($wpdb->prepare("SELECT total FROM {$p}bookings WHERE booking_code=%s",$code)),'booking_status'=>'confirmed'],['booking_code'=>$code]);
            $wpdb->insert($p.'payments',['booking_id'=>$wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}bookings WHERE booking_code=%s",$code)),'txn_id'=>sanitize_text_field($_GET['vnp_TransactionNo']??''),'method'=>'vnpay','amount'=>(int)($_GET['vnp_Amount']??0)/100,'status'=>'success']);
            wp_redirect(home_url('/dat-cho/?payment=success&code='.$code)); exit;
        }
        wp_redirect(home_url('/dat-cho/?payment=failed')); exit;
    }
}
SAO_Booking::init();
