<?php
/**
 * SAO PHU QUOC Theme - Functions + Multi-language + Booking Engine
 */
function sao_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption']);
    register_nav_menus(['primary' => 'Menu chính', 'footer' => 'Menu footer']);
}
add_action('after_setup_theme', 'sao_setup');

function sao_scripts() {
    wp_enqueue_style('sao-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap', [], null);
    wp_enqueue_style('sao-style', get_stylesheet_uri(), [], '1.0');
}
add_action('wp_enqueue_scripts', 'sao_scripts');

// Load Booking Engine
require_once __DIR__ . '/booking-engine.php';

// Load CRM System
require_once __DIR__ . '/crm.php';

// Load Partner Portal + Ops Dashboard
require_once __DIR__ . '/partner.php';

// Load Admin CPT + Meta Boxes
require_once __DIR__ . '/admin-setup.php';

// Google Translate widget + VNPay redirect
function sao_gt_widget() {
?>
<div id="sao-gt-bar" style="display:none;position:fixed;top:60px;left:0;right:0;z-index:9999;background:#1B2A4A;padding:8px 16px;text-align:center">
<span style="color:#fff;margin-right:12px">🌐 Translate:</span>
<div id="google_translate_element"></div>
<button onclick="document.getElementById('sao-gt-bar').style.display='none'" style="background:none;border:none;color:#C9A96E;cursor:pointer;margin-left:12px;font-size:1.2rem">✕</button>
</div>
<script>
var _gt_loaded=false;
function loadGoogleTranslate(){
    if(_gt_loaded){document.getElementById('sao-gt-bar').style.display='block';return;}
    _gt_loaded=true;document.getElementById('sao-gt-bar').style.display='block';
    var s=document.createElement('script');s.src='https://translate.google.com/translate_a/element.js?cb=gtCallback';
    window.gtCallback=function(){new google.translate.TranslateElement({pageLanguage:'vi',includedLanguages:'en,zh-CN,zh-TW,ko,ja,ru,fr,de,es,it,th,hi,id,ms,ar,pt,nl,sv,da,no,fi,pl,tr,cs,hu,el,he,mn,km,my,tl,uk',layout:google.translate.TranslateElement.InlineLayout.SIMPLE,autoDisplay:true},'google_translate_element')};
    document.head.appendChild(s);
}
</script>
<?php
}
add_action('wp_footer', 'sao_gt_widget', 99);

// VNPay redirect endpoint
function sao_vnpay_redirect() {
    if(strpos($_SERVER['REQUEST_URI'], '/sao-pay/') !== false) {
        $bid = (int)($_GET['bid'] ?? 0);
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $b = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$p}bookings WHERE id=%d", $bid));
        if(!$b) { wp_die('Booking not found'); return; }
        
        $vnp_TmnCode = 'SAOPHUQUOC'; $vnp_HashSecret = 'SAOPHUQUOCVNP@2026';
        $vnp_Url = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
        $vnp_ReturnUrl = home_url('/sao-vnpay-return/');
        $amount = (int)$b->deposit * 100;
        
        $params = ['vnp_Version'=>'2.1.0','vnp_Command'=>'pay','vnp_TmnCode'=>$vnp_TmnCode,'vnp_Amount'=>$amount,'vnp_CurrCode'=>'VND','vnp_TxnRef'=>$b->booking_code.'_'.time(),'vnp_OrderInfo'=>'SAO PHU QUOC Booking '.$b->booking_code,'vnp_OrderType'=>'billpayment','vnp_Locale'=>'vn','vnp_ReturnUrl'=>$vnp_ReturnUrl,'vnp_IpAddr'=>$_SERVER['REMOTE_ADDR']??'127.0.0.1','vnp_CreateDate'=>date('YmdHis')];
        ksort($params);
        $hash = ''; $query = '';
        foreach($params as $k=>$v) { if($hash!='') { $hash.='&'; $query.='&'; } $hash.=$k.'='.$v; $query.=$k.'='.urlencode($v); }
        $secure = hash_hmac('sha512', $hash, $vnp_HashSecret);
        wp_redirect($vnp_Url.'?'.$query.'&vnp_SecureHash='.$secure);
        exit;
    }
    if(strpos($_SERVER['REQUEST_URI'], '/sao-vnpay-return/') !== false) {
        global $wpdb; $p = $wpdb->prefix . 'sao_';
        $code = explode('_', $_GET['vnp_TxnRef'] ?? '')[0];
        if(($_GET['vnp_ResponseCode'] ?? '') == '00') {
            $bid = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$p}bookings WHERE booking_code=%s", $code));
            $wpdb->update($p.'bookings', ['payment_status'=>'paid','booking_status'=>'confirmed'], ['booking_code'=>$code]);
            $wpdb->insert($p.'payments', ['booking_id'=>$bid,'txn_id'=>sanitize_text_field($_GET['vnp_TransactionNo']??''),'method'=>'vnpay','amount'=>(int)($_GET['vnp_Amount']??0)/100,'status'=>'success']);
            wp_redirect(home_url('/dat-cho/?payment=success&code='.$code));
        } else {
            wp_redirect(home_url('/dat-cho/?payment=failed'));
        }
        exit;
    }
}
add_action('init', 'sao_vnpay_redirect');

// ═══════════ SAO CHATBOT — Bilingual Keyword-Response System ═══════════
// Frontend widget HTML
function sao_chatbot_widget() {
    ob_start(); ?>
<!-- SAO Chatbot Widget -->
<div id="sao-chatbot" style="position:fixed;bottom:20px;right:20px;z-index:99999;font-family:'Inter',sans-serif">
    <button id="sao-chatbot-toggle" onclick="SAOChatbot.toggle()" style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#1B2A4A,#C9A96E);border:none;color:#fff;font-size:28px;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,.3);transition:transform .3s;display:flex;align-items:center;justify-content:center" title="SAO Assistant">💬</button>
    <div id="sao-chatbot-window" style="display:none;position:absolute;bottom:70px;right:0;width:360px;max-height:500px;background:#fff;border-radius:16px;box-shadow:0 8px 40px rgba(0,0,0,.15);overflow:hidden;flex-direction:column">
        <div style="background:linear-gradient(135deg,#1B2A4A,#2D4A7A);padding:16px;color:#fff;display:flex;justify-content:space-between;align-items:center">
            <div>
                <strong style="font-size:1rem">🤖 SAO Assistant</strong>
                <div style="font-size:.75rem;opacity:.85">Hỏi đáp Bãi Sao • Gánh Ngon • Paghera Yacht</div>
            </div>
            <div style="display:flex;gap:8px">
                <button onclick="SAOChatbot.toggleLang()" id="sao-chatbot-lang-btn" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:14px" title="Switch language">🌐</button>
                <button onclick="SAOChatbot.toggle()" style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:18px">✕</button>
            </div>
        </div>
        <div id="sao-chatbot-messages" style="flex:1;overflow-y:auto;padding:16px;max-height:340px;background:#F7F8FA;display:flex;flex-direction:column;gap:10px">
            <div class="sao-chatbot-msg bot"><div class="sao-chatbot-bubble" id="sao-chatbot-greeting"></div></div>
        </div>
        <div id="sao-chatbot-quick-replies" style="padding:8px 16px;display:flex;gap:8px;flex-wrap:wrap;border-top:1px solid #eee;background:#fff"></div>
        <div style="display:flex;padding:12px;border-top:1px solid #eee;background:#fff;gap:8px">
            <input type="text" id="sao-chatbot-input" placeholder="Nhập câu hỏi..." style="flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:20px;font-size:.9rem;outline:none;font-family:inherit" onkeydown="if(event.key==='Enter')SAOChatbot.send()">
            <button onclick="SAOChatbot.send()" style="background:#C9A96E;border:none;color:#fff;width:44px;height:44px;border-radius:50%;cursor:pointer;font-size:18px">➤</button>
        </div>
    </div>
</div>
<style>
.sao-chatbot-msg{display:flex;max-width:90%}.sao-chatbot-msg.user{align-self:flex-end}.sao-chatbot-msg.bot{align-self:flex-start}
.sao-chatbot-bubble{padding:10px 14px;border-radius:14px;font-size:.88rem;line-height:1.45}
.sao-chatbot-msg.user .sao-chatbot-bubble{background:#C9A96E;color:#fff;border-bottom-right-radius:4px}
.sao-chatbot-msg.bot .sao-chatbot-bubble{background:#fff;color:#1B2A4A;border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.sao-chatbot-chip{padding:6px 14px;border-radius:16px;border:1px solid #C9A96E;background:#fff;color:#1B2A4A;font-size:.8rem;cursor:pointer;transition:all .2s;white-space:nowrap}
.sao-chatbot-chip:hover{background:#C9A96E;color:#fff}
</style>
<?php return ob_get_clean();
}
add_action('wp_footer', 'sao_chatbot_widget', 100);

// AJAX handler for chatbot keyword matching
function sao_chatbot_ajax() {
    $msg = mb_strtolower(sanitize_text_field($_POST['msg'] ?? ''));
    $lang = sanitize_text_field($_POST['lang'] ?? 'vi');

    // ═══ BILINGUAL KEYWORD DATABASE ═══
    $keywords = [
        // Vietnamese keywords → [vi_response, en_response]
        'xin chào|hello|chào|hi|hey' => [
            'vi' => "👋 Chào anh/chị! Em là SAO Assistant, trợ lý AI của Bãi Sao, Gánh Ngon & Paghera Yacht. Anh/chị muốn tìm hiểu về dịch vụ nào ạ?\n\nEm có thể tư vấn: Vé tham quan, Cabana, Glamping, Yacht Tour, Combo, Sự kiện...",
            'en' => "👋 Hello! I'm SAO Assistant, AI consultant for Bai Sao, Ganh Ngon & Paghera Yacht. What service would you like to know about?\n\nI can help with: Day Passes, Cabanas, Glamping, Yacht Tours, Combos, Events..."
        ],
        'bãi sao|bai sao|bãi tắm|beach' => [
            'vi' => "🏖️ <strong>Bãi Sao</strong> là bãi biển đẹp nhất Phú Quốc với cát trắng mịn, nước trong xanh.\n\n🎫 Vé vào cổng: <strong>150.000đ/người</strong> (bao gồm ghế nằm + nước uống)\n🛋️ Lounger: <strong>350.000đ/người</strong>\n🏠 Cabana: <strong>1.200.000đ/cabana</strong> (2-4 người)\n\nAnh/chị muốn đặt vé ngày nào và đi mấy người ạ?",
            'en' => "🏖️ <strong>Bai Sao</strong> is Phu Quoc's most beautiful beach with white sand and crystal-clear water.\n\n🎫 Day Pass: <strong>150,000 VND/person</strong> (includes lounger + drink)\n🛋️ Lounger: <strong>350,000 VND/person</strong>\n🏠 Cabana: <strong>1,200,000 VND/cabana</strong> (2-4 guests)\n\nWhat date and how many guests?"
        ],
        'gánh ngon|ganh ngon|nhà hàng|restaurant|ăn|food|dining' => [
            'vi' => "🍽️ <strong>Gánh Ngon</strong> — Nhà hàng hải sản view biển tại Bãi Sao\n\n🦞 Hải sản tươi sống từ biển Phú Quốc\n🍜 Đặc sản: Gỏi cá trích, Ghẹ Hàm Ninh, Nhím biển nướng\n💰 Tầm giá: 200.000đ - 500.000đ/người\n\nĐặt bàn trước để có vị trí đẹp! Anh/chị muốn đặt bàn cho mấy người ạ?",
            'en' => "🍽️ <strong>Ganh Ngon</strong> — Ocean-view seafood restaurant at Bai Sao\n\n🦞 Fresh seafood from Phu Quoc waters\n🍜 Specialties: Herring salad, Ham Ninh crab, Grilled sea urchin\n💰 Price range: 200,000 - 500,000 VND/person\n\nReserve a table for the best spot! How many guests?"
        ],
        'paghera|yacht|du thuyền|boat|cruise' => [
            'vi' => "⛵ <strong>Paghera Yacht</strong> — Du thuyền sang trọng khám phá quần đảo Phú Quốc\n\n🕐 Tour nửa ngày: <strong>1.500.000đ/người</strong> (9:00-13:00 hoặc 14:00-18:00)\n🕐 Tour cả ngày: <strong>2.500.000đ/người</strong> (9:00-17:00)\n🍾 Bao gồm: Đồ uống, trái cây, snorkeling, câu cá\n\nAnh/chị muốn đi tour nửa ngày hay cả ngày ạ?",
            'en' => "⛵ <strong>Paghera Yacht</strong> — Luxury yacht exploring Phu Quoc archipelago\n\n🕐 Half-day tour: <strong>1,500,000 VND/person</strong> (9AM-1PM or 2PM-6PM)\n🕐 Full-day tour: <strong>2,500,000 VND/person</strong> (9AM-5PM)\n🍾 Includes: Drinks, fruits, snorkeling, fishing\n\nHalf-day or full-day tour?"
        ],
        'combo|combo|trọn gói|package|bundle' => [
            'vi' => "🎁 <strong>Combo Trọn Gói</strong> — Tiết kiệm đến 20%!\n\n🌟 Combo Cơ Bản: Vé Bãi Sao + Gánh Ngon = <strong>350.000đ/người</strong>\n🌟 Combo Premium: Vé + Ăn + Yacht Tour nửa ngày = <strong>1.700.000đ/người</strong>\n🌟 Combo VIP: Vé + Ăn + Yacht Tour cả ngày + Cabana = <strong>3.200.000đ/người</strong>\n\nAnh/chị thích combo nào ạ?",
            'en' => "🎁 <strong>All-in-One Combos</strong> — Save up to 20%!\n\n🌟 Basic Combo: Bai Sao Pass + Ganh Ngon = <strong>350,000 VND/person</strong>\n🌟 Premium Combo: Pass + Dining + Half-day Yacht = <strong>1,700,000 VND/person</strong>\n🌟 VIP Combo: Pass + Dining + Full-day Yacht + Cabana = <strong>3,200,000 VND/person</strong>\n\nWhich combo interests you?"
        ],
        'giá|price|cost|how much|bao nhiêu' => [
            'vi' => "💰 <strong>Bảng giá tham khảo:</strong>\n• Vé Bãi Sao: 150.000đ/người\n• Lounger: 350.000đ/người\n• Cabana: 1.200.000đ/cabana\n• Gánh Ngon: ~300.000đ/người\n• Yacht nửa ngày: 1.500.000đ/người\n• Yacht cả ngày: 2.500.000đ/người\n• Combo tiết kiệm 20%!\n\nAnh/chị muốn đặt dịch vụ nào?",
            'en' => "💰 <strong>Price Reference:</strong>\n• Bai Sao Pass: 150,000 VND/person\n• Lounger: 350,000 VND/person\n• Cabana: 1,200,000 VND/cabana\n• Ganh Ngon: ~300,000 VND/person\n• Half-day Yacht: 1,500,000 VND/person\n• Full-day Yacht: 2,500,000 VND/person\n• Combos save 20%!\n\nWhich service would you like to book?"
        ],
        'đặt|book|reserve|reservation|booking|đặt chỗ' => [
            'vi' => "📅 Để đặt chỗ, anh/chị vui lòng:\n1️⃣ Chọn dịch vụ (Bãi Sao, Yacht, Combo...)\n2️⃣ Chọn ngày đi\n3️⃣ Cho em biết số lượng khách\n\nHoặc đặt ngay tại: <a href='/dat-cho' target='_blank'>Trang đặt chỗ</a>\n\n📞 Hotline hỗ trợ: <a href='tel:02973999999'>0297 3 999 999</a>",
            'en' => "📅 To book, please:\n1️⃣ Choose a service (Bai Sao, Yacht, Combo...)\n2️⃣ Pick a date\n3️⃣ Tell me the number of guests\n\nOr book directly: <a href='/dat-cho' target='_blank'>Booking page</a>\n\n📞 Hotline: <a href='tel:02973999999'>0297 3 999 999</a>"
        ],
        'sự kiện|event|team building|tiệc|party|wedding|cưới' => [
            'vi' => "🎉 <strong>Tổ chức sự kiện tại SAO PHU QUOC</strong>\n• Team Building: từ 200 khách\n• Tiệc cưới bãi biển\n• Sinh nhật, họp lớp\n• Gala dinner\n\n📞 Liên hệ team sự kiện: <a href='tel:02973999999'>0297 3 999 999</a>\nhoặc để lại SĐT, em sẽ nhắn team gọi lại ngay!",
            'en' => "🎉 <strong>Events at SAO PHU QUOC</strong>\n• Team Building: from 200 guests\n• Beach weddings\n• Birthdays, reunions\n• Gala dinners\n\n📞 Contact events team: <a href='tel:02973999999'>0297 3 999 999</a>\nor leave your phone number and we'll call back!"
        ],
        'liên hệ|contact|hotline|phone|sđt|số điện thoại|zalo' => [
            'vi' => "📞 <strong>Liên hệ SAO PHU QUOC:</strong>\n• Hotline: <a href='tel:02973999999'>0297 3 999 999</a>\n• Zalo: <a href='https://zalo.me/090xxxxxxx'>090xxxxxxx</a>\n• Email: info@saophuquoc.vn\n• Địa chỉ: Bãi Sao, An Thới, Phú Quốc\n\nAnh/chị cần hỗ trợ thêm gì ạ?",
            'en' => "📞 <strong>Contact SAO PHU QUOC:</strong>\n• Hotline: <a href='tel:02973999999'>0297 3 999 999</a>\n• Zalo: 090xxxxxxx\n• Email: info@saophuquoc.vn\n• Address: Bai Sao, An Thoi, Phu Quoc\n\nAnything else I can help with?"
        ],
        'di chuyển|transport|taxi|bus|airport|sân bay|get there|how to go' => [
            'vi' => "🚗 <strong>Di chuyển đến Bãi Sao:</strong>\n• Từ sân bay Phú Quốc: ~30 phút taxi (~200.000đ)\n• Từ thị trấn Dương Đông: ~25 phút\n• Xe điện miễn phí từ cổng vào bãi\n• Có bãi đỗ xe ô tô & xe máy miễn phí\n\nAnh/chị cần đặt xe đưa đón không ạ?",
            'en' => "🚗 <strong>Getting to Bai Sao:</strong>\n• From Phu Quoc Airport: ~30 min taxi (~200,000 VND)\n• From Duong Dong town: ~25 min\n• Free electric shuttle from entrance\n• Free car & motorbike parking\n\nNeed airport transfer?"
        ],
        'thời tiết|weather|mưa|rain|nắng|sun' => [
            'vi' => "☀️ <strong>Thời tiết Phú Quốc:</strong>\n• Mùa khô (11-4): Nắng đẹp, biển êm — lý tưởng nhất!\n• Mùa mưa (5-10): Mưa rào ngắn, vẫn đi được\n• Nhiệt độ quanh năm: 25-32°C\n\n💡 Tip: Mùa mưa vẫn đi Bãi Sao được vì mưa thường vào chiều tối, sáng nắng đẹp!",
            'en' => "☀️ <strong>Phu Quoc Weather:</strong>\n• Dry season (Nov-Apr): Sunny, calm sea — best time!\n• Rainy season (May-Oct): Brief showers, still visitable\n• Year-round temp: 25-32°C\n\n💡 Tip: Rainy season mornings are usually sunny — Bai Sao is still great!"
        ],
        'glamping|cắm trại|camp|lều' => [
            'vi' => "🏕️ <strong>Glamping Bãi Sao</strong> — Cắm trại sang trọng bên biển\n\n• Lều đầy đủ tiện nghi: giường, quạt, đèn, ổ điện\n• View biển trực diện\n• Giá: <strong>1.800.000đ/đêm</strong> (2 người)\n• Bao gồm: Bữa sáng + Vé Bãi Sao\n\nAnh/chị muốn đặt glamping ngày nào ạ?",
            'en' => "🏕️ <strong>Bai Sao Glamping</strong> — Luxury beach camping\n\n• Fully equipped tent: bed, fan, lights, power\n• Direct beach view\n• Price: <strong>1,800,000 VND/night</strong> (2 guests)\n• Includes: Breakfast + Bai Sao Pass\n\nWhat date would you like?"
        ],
        'cảm ơn|thanks|thank you|bye|tạm biệt|goodbye' => [
            'vi' => "😊 Cảm ơn anh/chị đã quan tâm đến SAO PHU QUOC!\n\nNếu cần gì thêm, cứ nhắn em nhé. Chúc anh/chị một ngày tuyệt vời! 🌴\n\n📞 Hotline: 0297 3 999 999",
            'en' => "😊 Thank you for your interest in SAO PHU QUOC!\n\nFeel free to message me anytime. Have a wonderful day! 🌴\n\n📞 Hotline: 0297 3 999 999"
        ],
    ];

    $matched = false;
    $response_vi = "Em chưa hiểu ý anh/chị lắm. Anh/chị thử hỏi về: Bãi Sao, Gánh Ngon, Paghera Yacht, giá vé, đặt chỗ, sự kiện, liên hệ... ạ!";
    $response_en = "I'm not sure I understand. Try asking about: Bai Sao, Ganh Ngon, Paghera Yacht, pricing, booking, events, contact...!";
    $ctas = [];
    $response = $lang === 'en' ? $response_en : $response_vi;

    foreach ($keywords as $pattern => $reply) {
        if (preg_match('/' . $pattern . '/ui', $msg)) {
            $response = $reply[$lang] ?? $reply['vi'];
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $ctas = $lang === 'vi'
            ? [['label' => '🏖️ Bãi Sao', 'action' => 'reply'], ['label' => '⛵ Yacht', 'action' => 'reply'], ['label' => '🍽️ Nhà hàng', 'action' => 'reply'], ['label' => '💰 Giá vé', 'action' => 'reply']]
            : [['label' => '🏖️ Bai Sao', 'action' => 'reply'], ['label' => '⛵ Yacht', 'action' => 'reply'], ['label' => '🍽️ Restaurant', 'action' => 'reply'], ['label' => '💰 Pricing', 'action' => 'reply']];
    }

    wp_send_json_success(['message' => $response, 'ctas' => $ctas, 'lang' => $lang]);
}
add_action('wp_ajax_sao_chatbot', 'sao_chatbot_ajax');
add_action('wp_ajax_nopriv_sao_chatbot', 'sao_chatbot_ajax');

// Chatbot JS initialization
function sao_chatbot_js() {
    ?>
<script>
var SAOChatbot = {
    lang: 'vi',
    open: false,
    toggle: function() {
        this.open = !this.open;
        var w = document.getElementById('sao-chatbot-window');
        w.style.display = this.open ? 'flex' : 'none';
        if (this.open) { this.showGreeting(); document.getElementById('sao-chatbot-input').focus(); }
    },
    toggleLang: function() {
        this.lang = this.lang === 'vi' ? 'en' : 'vi';
        var b = document.getElementById('sao-chatbot-lang-btn');
        b.textContent = this.lang === 'vi' ? '🇻🇳' : '🇬🇧';
        var inp = document.getElementById('sao-chatbot-input');
        inp.placeholder = this.lang === 'vi' ? 'Nhập câu hỏi...' : 'Type your question...';
        this.showGreeting();
    },
    showGreeting: function() {
        var g = document.getElementById('sao-chatbot-greeting');
        g.innerHTML = this.lang === 'vi'
            ? '👋 Chào anh/chị! Em là SAO Assistant, hỏi em bất cứ điều gì về Bãi Sao, Gánh Ngon & Paghera Yacht nhé!'
            : '👋 Hello! I\'m SAO Assistant, ask me anything about Bai Sao, Ganh Ngon & Paghera Yacht!';
        this.showQuickReplies(['🏖️ ' + (this.lang === 'vi' ? 'Bãi Sao' : 'Bai Sao'), '⛵ ' + (this.lang === 'vi' ? 'Yacht Tour' : 'Yacht Tour'), '🍽️ ' + (this.lang === 'vi' ? 'Nhà hàng' : 'Restaurant'), '💰 ' + (this.lang === 'vi' ? 'Giá vé' : 'Pricing')]);
    },
    send: function() {
        var inp = document.getElementById('sao-chatbot-input');
        var msg = inp.value.trim();
        if (!msg) return;
        this.addMessage(msg, 'user');
        inp.value = '';
        this.showTyping();
        var self = this;
        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=sao_chatbot&msg=' + encodeURIComponent(msg) + '&lang=' + self.lang
        }).then(r => r.json()).then(function(d) {
            self.hideTyping();
            if (d.success) {
                self.addMessage(d.data.message, 'bot');
                if (d.data.ctas && d.data.ctas.length > 0) {
                    self.showQuickReplies(d.data.ctas.map(function(c) { return c.label; }));
                }
            }
        }).catch(function() {
            self.hideTyping();
            self.addMessage(self.lang === 'vi' ? '😅 Xin lỗi, em đang gặp chút trục trặc. Thử lại sau nhé!' : '😅 Sorry, having a small issue. Please try again!', 'bot');
        });
    },
    addMessage: function(text, role) {
        var container = document.getElementById('sao-chatbot-messages');
        var div = document.createElement('div');
        div.className = 'sao-chatbot-msg ' + role;
        var bubble = document.createElement('div');
        bubble.className = 'sao-chatbot-bubble';
        bubble.innerHTML = text.replace(/\n/g, '<br>');
        div.appendChild(bubble);
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    },
    showTyping: function() {
        var container = document.getElementById('sao-chatbot-messages');
        var div = document.createElement('div');
        div.className = 'sao-chatbot-msg bot';
        div.id = 'sao-chatbot-typing';
        div.innerHTML = '<div class="sao-chatbot-bubble" style="color:#718096">...</div>';
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    },
    hideTyping: function() {
        var el = document.getElementById('sao-chatbot-typing');
        if (el) el.remove();
    },
    showQuickReplies: function(labels) {
        var container = document.getElementById('sao-chatbot-quick-replies');
        container.innerHTML = '';
        var self = this;
        labels.forEach(function(l) {
            var chip = document.createElement('button');
            chip.className = 'sao-chatbot-chip';
            chip.textContent = l;
            chip.onclick = function() {
                document.getElementById('sao-chatbot-input').value = l;
                self.send();
            };
            container.appendChild(chip);
        });
    }
};
</script>
    <?php
}
add_action('wp_footer', 'sao_chatbot_js', 101);
