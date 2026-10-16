<?php
if (!defined('ABSPATH')) exit;

class SAO_Booking_Shortcodes {

    public static function booking_form($atts = []) {
        global $wpdb;
        $px = $wpdb->prefix;
        $products = [];
        $rows = $wpdb->get_results("SELECT * FROM {$px}sao_products WHERE active=1 ORDER BY id ASC LIMIT 12", ARRAY_A);
        if ($rows) $products = $rows;
        ob_start();
        ?>
        <div id="sao-booking-app" class="sao-booking-wrap" style="background:#fff;border-radius:8px;box-shadow:0 8px 24px rgba(44,24,16,.08);padding:30px;max-width:760px;margin:0 auto">
            <h3 style="margin-top:0;color:#2C1810;font-family:'Playfair Display',Georgia,serif">Form đặt bàn — Gánh Ngon</h3>
            <form id="sao-booking-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" style="display:grid;gap:14px">
                <input type="hidden" name="action" value="sao_create_booking">
                <?php wp_nonce_field('sao_booking_nonce', 'sao_nonce'); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <label>Họ tên *<input type="text" name="customer_name" required style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px"></label>
                    <label>Số điện thoại *<input type="tel" name="customer_phone" required style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px"></label>
                </div>
                <label>Email<input type="email" name="customer_email" style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px"></label>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px">
                    <label>Ngày đặt *<input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>" style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px"></label>
                    <label>Giờ *
                        <select name="booking_time" required style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px">
                            <option value="">Chọn giờ</option>
                            <?php foreach (['11:30','12:00','12:30','17:30','18:00','18:30','19:00','19:30','20:00'] as $t): ?>
                                <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Số khách *
                        <select name="guests" required style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px">
                            <?php for ($i=1;$i<=12;$i++) echo '<option value="'.$i.'">'.$i.' người</option>'; ?>
                        </select>
                    </label>
                </div>
                <?php if ($products): ?>
                <label>Sản phẩm / Combo
                    <select name="product_id" style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px">
                        <option value="">— Chỉ đặt bàn (không chọn combo) —</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo esc_attr($p['id']); ?>"><?php echo esc_html(($p['name'] ?? 'Combo') . ' — ' . number_format($p['price'] ?? 0).'đ'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <label>Ghi chú (món, dị ứng, vị trí bàn...)<textarea name="note" rows="3" style="width:100%;padding:12px;border:1px solid #E8DFCB;border-radius:6px;margin-top:4px;font-family:inherit"></textarea></label>
                <button type="submit" style="background:#D4AF37;color:#2C1810;border:none;border-radius:8px;padding:14px 32px;font-weight:600;font-size:1.05rem;cursor:pointer;margin-top:8px">Giữ bàn ngay</button>
                <p style="color:#5A5A5A;font-size:.85rem;margin:0">* Xác nhận trong 2 phút qua SMS + Email. Đặt cọc 30% qua VNPay.</p>
            </form>
            <div id="sao-booking-result" style="margin-top:20px"></div>
        </div>
        <script>
        document.getElementById('sao-booking-form')?.addEventListener('submit', async function(e){
            e.preventDefault();
            var data = new FormData(this);
            var btn = this.querySelector('button');
            btn.disabled = true; btn.textContent = 'Đang xử lý...';
            try {
                var res = await fetch(this.action, {method:'POST', body:data, credentials:'same-origin'});
                var j = await res.json();
                document.getElementById('sao-booking-result').innerHTML = '<div style="background:'+(j.success?'#E8F5E9':'#FFEBEE')+';padding:16px;border-radius:6px;color:'+(j.success?'#2E7D32':'#C62828')+'">' + (j.data?.message || (j.success ? 'Đặt bàn thành công!' : 'Có lỗi xảy ra, vui lòng thử lại')) + (j.data?.code ? '<br>Mã đặt bàn: <strong>'+j.data.code+'</strong>' : '') + '</div>';
                if (j.success) this.reset();
            } catch(err) {
                document.getElementById('sao-booking-result').innerHTML = '<div style="background:#FFEBEE;padding:16px;border-radius:6px;color:#C62828">Lỗi kết nối. Vui lòng thử lại hoặc gọi 0979 232 233.</div>';
            }
            btn.disabled = false; btn.textContent = 'Giữ bàn ngay';
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public static function my_bookings($atts = []) {
        ob_start();
        ?>
        <div class="sao-my-bookings" style="background:#fff;padding:24px;border-radius:8px;box-shadow:0 4px 12px rgba(44,24,16,.06)">
            <h3 style="margin-top:0">Tra cứu đặt bàn</h3>
            <form method="get" style="display:flex;gap:10px;flex-wrap:wrap">
                <input type="text" name="code" placeholder="Mã đặt bàn (vd: SAO20260615ABC12)" required style="flex:1;min-width:240px;padding:12px;border:1px solid #E8DFCB;border-radius:6px">
                <button type="submit" style="background:#D4AF37;color:#2C1810;border:none;border-radius:6px;padding:12px 24px;font-weight:600">Tra cứu</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function partner_portal($atts = []) {
        ob_start();
        ?>
        <div class="sao-partner-portal" style="background:#fff;padding:24px;border-radius:8px">
            <h3 style="margin-top:0">Portal Đối tác Gánh Ngon</h3>
            <p>Vui lòng đăng nhập để xem dashboard, tỉ lệ chia hoa hồng, và lịch sử booking.</p>
            <a href="<?php echo esc_url(wp_login_url('/partner/')); ?>" style="display:inline-block;background:#D4AF37;color:#2C1810;padding:12px 28px;border-radius:6px;font-weight:600;text-decoration:none">Đăng nhập Đối tác</a>
        </div>
        <?php
        return ob_get_clean();
    }
}