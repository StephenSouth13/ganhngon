<?php
class SAO_Booking_Ajax {
    public function get_inventory() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $products = $wpdb->get_results("SELECT pr.*, COALESCE(inv.total_capacity,pr.capacity) as cap, COALESCE(inv.reserved,0) as sold FROM {$p}products pr LEFT JOIN {$p}inventory inv ON pr.id=inv.product_id AND inv.booking_date='$date' WHERE pr.status='active' ORDER BY pr.sort_order");
        
        $out = [];
        foreach($products as $pr) {
            $avail = max(0, $pr->cap - $pr->sold);
            $out[] = ['id'=>(int)$pr->id,'name'=>$pr->name,'price'=>(int)$pr->base_price,'price_formatted'=>number_format($pr->base_price).'đ','available'=>$avail,'type'=>$pr->type,'desc'=>$pr->short_desc];
        }
        wp_send_json_success(['products'=>$out,'date'=>$date]);
    }
    
    public function create_booking() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $code = 'SAO' . date('Ymd') . strtoupper(substr(md5(uniqid()),0,4));
        $total = 0;

        // Accept BOTH the engine's short field names and the /dat-ban/ form names
        $g = function($keys, $default='') {
            foreach((array)$keys as $k) if (isset($_POST[$k]) && $_POST[$k] !== '') return $_POST[$k];
            return $default;
        };
        $name   = sanitize_text_field($g(['customer_name','name']));
        $email  = sanitize_email($g(['customer_email','email']));
        $phone  = sanitize_text_field($g(['customer_phone','phone']));
        $date   = sanitize_text_field($g(['booking_date','date'], date('Y-m-d')));
        $guests = (int) $g(['guests'], 1);
        $type   = sanitize_text_field($g(['space_type','type'], 'individual'));
        $need   = sanitize_text_field($g(['need'], ''));
        $note   = sanitize_textarea_field($g(['note','notes'], ''));
        $tags   = (isset($_POST['note_tags']) && is_array($_POST['note_tags']))
                  ? implode(', ', array_map('sanitize_text_field', $_POST['note_tags'])) : '';
        $notes_full = trim(($need ? "Nhu cầu: $need. " : '') . ($tags ? "Lưu ý: $tags. " : '') . $note);

        // Capture marketing source (first-touch cookie gn_src set by gn-analytics.php) for attribution
        $src = isset($_COOKIE['gn_src']) ? sanitize_text_field(wp_unslash($_COOKIE['gn_src'])) : '';
        if ($src !== '') $notes_full = trim($notes_full . ' [Nguồn: ' . $src . ']');

        $wpdb->insert($p.'bookings', [
            'booking_code'=>$code, 'customer_name'=>$name,
            'customer_email'=>$email, 'customer_phone'=>$phone,
            'booking_date'=>$date, 'guests'=>$guests,
            'guest_type'=>$type, 'notes'=>$notes_full,
            'booking_status'=>'pending', 'payment_status'=>'pending'
        ]);
        $bid = $wpdb->insert_id;

        if (!empty($_POST['products'])) {
            $products = json_decode(stripslashes($_POST['products']), true);
            if(is_array($products)) {
                foreach($products as $pr) {
                    $prod = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}products WHERE id=%d", $pr['id']));
                    if($prod) {
                        $line = $prod->base_price * (int)$pr['qty'];
                        $total += $line;
                        $wpdb->insert($p.'booking_items', ['booking_id'=>$bid,'product_id'=>$prod->id,'product_name'=>$prod->name,'qty'=>(int)$pr['qty'],'unit_price'=>$prod->base_price,'total_price'=>$line]);
                        $wpdb->query($wpdb->prepare("UPDATE {$p}inventory SET reserved=reserved+1 WHERE product_id=%d AND booking_date=%s", $prod->id, $date));
                    }
                }
            }
        }
        $wpdb->update($p.'bookings', ['total'=>$total,'deposit'=>round($total*0.3),'subtotal'=>$total], ['id'=>$bid]);

        // Notify staff (sale + customer care) and create a CRM lead — never block the booking
        $this->sao_notify_booking(compact('code','name','phone','email','date','guests','type','notes_full','total'));
        $this->sao_crm_lead($name, $email, $phone, $notes_full, $code);

        wp_send_json_success(['booking_id'=>$bid,'code'=>$code,'total'=>$total,'deposit'=>round($total*0.3)]);
    }

    private function sao_notify_booking($d) {
        $to   = ['sale@ganhngon.com', 'cskh@ganhngon.com'];
        $subj = "🍤 Đặt bàn mới #{$d['code']} — {$d['name']} ({$d['guests']} khách)";
        $body = "ĐẶT BÀN MỚI — Gánh Ngon Phú Quốc\n\n"
              . "Mã booking: {$d['code']}\n"
              . "Khách: {$d['name']}\n"
              . "SĐT/Zalo: {$d['phone']}\n"
              . "Email: {$d['email']}\n"
              . "Ngày: {$d['date']}\n"
              . "Số khách: {$d['guests']}\n"
              . "Không gian: {$d['type']}\n"
              . "Ghi chú: {$d['notes_full']}\n"
              . ($d['total'] > 0 ? "Tạm tính: " . number_format($d['total']) . "đ\n" : '')
              . "\n→ Vào wp-admin → Đặt bàn để xử lý. Liên hệ khách trong 2 phút.";
        @wp_mail($to, $subj, $body, ['Content-Type: text/plain; charset=UTF-8']);
        // Telegram notification to staff (if configured)
        if (function_exists('gn_send_telegram')) {
            gn_send_telegram(
                "🍤 <b>ĐẶT BÀN MỚI</b> #{$d['code']}\n"
              . "👤 {$d['name']}\n"
              . "📞 {$d['phone']}\n"
              . "📅 {$d['date']} · {$d['guests']} khách\n"
              . "🏠 {$d['type']}\n"
              . ($d['notes_full'] ? "📝 {$d['notes_full']}\n" : '')
              . ($d['total'] > 0 ? "💰 " . number_format($d['total']) . "đ\n" : '')
            );
        }
        // confirmation to customer if email provided
        if (!empty($d['email'])) {
            @wp_mail($d['email'], "Gánh Ngon đã nhận yêu cầu đặt bàn #{$d['code']}",
                "Chào {$d['name']},\n\nGánh Ngon đã nhận yêu cầu đặt bàn của anh/chị (mã {$d['code']}, ngày {$d['date']}, {$d['guests']} khách). "
              . "Nhân viên sẽ liên hệ xác nhận trong ít phút.\n\nHotline/Zalo: 0979232233\n— Gánh Ngon Phú Quốc",
                ['Content-Type: text/plain; charset=UTF-8']);
        }
    }

    private function sao_crm_lead($name, $email, $phone, $notes, $code) {
        if (!$email && !$phone) return;
        global $zbs;
        if (isset($zbs) && is_object($zbs) && isset($zbs->DAL) && is_object($zbs->DAL) && method_exists($zbs->DAL->contacts, 'addUpdateContact')) {
            $parts = explode(' ', trim($name), 2);
            try {
                $zbs->DAL->contacts->addUpdateContact(['data'=>[
                    'fname'   => $parts[0] !== '' ? $parts[0] : ($name ?: 'Khách'),
                    'lname'   => $parts[1] ?? '',
                    'email'   => $email,
                    'maintel' => $phone,
                    'status'  => 'Lead',
                    'notes'   => "Đặt bàn $code. $notes",
                ]]);
            } catch (\Throwable $e) { /* never break booking */ }
        }
    }
    
    public function check_qr() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $code = $_POST['code'] ?? '';
        $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}bookings WHERE booking_code=%s", $code));
        if(!$b) wp_send_json_error(['message'=>'Không tìm thấy booking']);
        if($b->qr_scanned) wp_send_json_error(['message'=>'QR đã được sử dụng']);
        $wpdb->update($p.'bookings', ['qr_scanned'=>1,'qr_scanned_at'=>current_time('mysql'),'booking_status'=>'checked_in'], ['id'=>$b->id]);
        wp_send_json_success(['booking'=>$b,'message'=>'Check-in thành công!']);
    }
    
    public function dashboard() {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $today = date('Y-m-d');
        $bookings = $wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today'");
        $revenue = $wpdb->get_var("SELECT COALESCE(SUM(total),0) FROM {$p}bookings WHERE booking_date='$today'");
        $checked = $wpdb->get_var("SELECT COUNT(*) FROM {$p}bookings WHERE booking_date='$today' AND qr_scanned=1");
        $totalCap = $wpdb->get_var("SELECT COALESCE(SUM(total_capacity),0) FROM {$p}inventory WHERE booking_date='$today'");
        $totalSold = $wpdb->get_var("SELECT COALESCE(SUM(reserved),0) FROM {$p}inventory WHERE booking_date='$today'");
        wp_send_json_success(['bookings'=>(int)$bookings,'revenue'=>(int)$revenue,'checked_in'=>(int)$checked,'capacity'=>(int)$totalCap,'sold'=>(int)$totalSold,'fill_rate'=> $totalCap>0 ? round($totalSold/$totalCap*100,1) : 0]);
    }
    
    public function partner_login() { wp_send_json_error('Use WordPress login'); }
}
