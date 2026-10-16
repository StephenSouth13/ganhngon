<?php
class SAO_Booking_Admin {
    public function menu() {
        add_menu_page('SAO Booking', '🏖️ SAO Booking', 'manage_options', 'sao-booking', [$this,'page'], 'dashicons-tickets', 25);
        add_submenu_page('sao-booking','Dashboard','📊 Dashboard','manage_options','sao-booking',[$this,'page']);
        add_submenu_page('sao-booking','Sản phẩm','🎫 Sản phẩm','manage_options','sao-products',[$this,'products']);
        add_submenu_page('sao-booking','Bookings','📋 Bookings','manage_options','sao-bookings',[$this,'bookings']);
        add_submenu_page('sao-booking','QR Check-in','📱 QR Check-in','manage_options','sao-qr',[$this,'qr']);
        add_submenu_page('sao-booking','Đối tác','🤝 Đối tác','manage_options','sao-partners',[$this,'partners']);
    }
    
    public function page() {
        echo '<div class="wrap"><h1>🏖️ SAO PHU QUOC - Booking Dashboard</h1>';
        echo '<div id="sao-admin-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin:24px 0"><div class="loading">Đang tải...</div></div>';
        echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px"><div class="sao-card"><h3>📊 Booking hôm nay</h3><table class="wp-list-table widefat"><thead><tr><th>Mã</th><th>Khách</th><th>SL</th><th>Tiền</th><th>TT</th></tr></thead><tbody id="sao-today-list"><tr><td colspan="5">Đang tải...</td></tr></tbody></table></div><div class="sao-card"><h3>⏰ Theo khung giờ</h3><canvas id="sao-hourly-chart" style="max-height:200px"></canvas></div></div>';
        $this->dashboard_js();
        echo '</div>';
    }
    
    private function dashboard_js() {
        ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script>
        fetch(ajaxurl+'?action=sao_dashboard').then(r=>r.json()).then(function(d){
            if(!d.success) return;
            var s=d.data,h='';
            h+='<div class="sao-stat"><div class="val">'+s.bookings+'</div><div class="lbl">Booking</div></div>';
            h+='<div class="sao-stat"><div class="val">'+s.revenue.toLocaleString('vi-VN')+'đ</div><div class="lbl">Doanh thu</div></div>';
            h+='<div class="sao-stat"><div class="val">'+s.checked_in+'</div><div class="lbl">Check-in</div></div>';
            h+='<div class="sao-stat"><div class="val">'+s.fill_rate+'%</div><div class="lbl">Công suất</div></div>';
            h+='<div class="sao-stat"><div class="val">'+(s.capacity-s.sold)+'/'+s.capacity+'</div><div class="lbl">Còn trống</div></div>';
            document.getElementById('sao-admin-stats').innerHTML=h;
        });
        </script>
        <style>.sao-stat{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04);text-align:center}.sao-stat .val{font-size:1.8rem;font-weight:700;color:#1B2A4A}.sao-stat .lbl{color:#718096;font-size:.85rem;margin-top:4px}.sao-card{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.04)}</style>
        <?php
    }
    
    public function products() { echo '<div class="wrap"><h1>🎫 Quản lý Sản phẩm</h1><p>Quản lý vé, cabana, glamping, F&B, sự kiện.</p><p style="color:#718096">Tính năng quản lý sản phẩm đầy đủ đang được phát triển.</p></div>'; }
    public function bookings() { echo '<div class="wrap"><h1>📋 Quản lý Bookings</h1><p>Xem và quản lý tất cả đặt chỗ.</p></div>'; }
    public function qr() {
        echo '<div class="wrap"><h1>📱 QR Check-in</h1>';
        echo '<div style="background:#fff;padding:24px;border-radius:8px;max-width:500px"><input type="text" id="qr-scan" placeholder="Quét hoặc nhập mã booking..." style="width:100%;padding:12px;font-size:1.1rem;border:2px solid #C9A96E;border-radius:6px" onchange="SAOAdmin.checkQR(this.value)"><div id="qr-result" style="margin-top:12px"></div></div>';
        ?>
        <script>
        var SAOAdmin = {checkQR:function(c){var r=document.getElementById('qr-result');r.innerHTML='Đang kiểm tra...';fetch(ajaxurl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=sao_check_qr&code='+encodeURIComponent(c)}).then(function(x){return x.json()}).then(function(d){r.innerHTML=d.success ? '<div style="color:green;font-size:1.2rem">✅ '+d.data.message+'<br>Khách: '+d.data.booking.customer_name+'<br>Mã: '+d.data.booking.booking_code+'</div>' : '<div style="color:red">❌ '+d.data.message+'</div>';});}};
        </script>
        <?php
        echo '</div>';
    }
    public function partners() { echo '<div class="wrap"><h1>🤝 Quản lý Đối tác</h1><p>Đại lý, khách sạn, tour operator, KOL.</p></div>'; }
}
