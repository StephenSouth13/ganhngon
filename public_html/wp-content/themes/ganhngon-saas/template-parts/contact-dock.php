<?php
if (!defined('ABSPATH')) exit;
$gns_wechat = function_exists('gn_wechat_id') ? gn_wechat_id() : 'ganhngon';
$gns_lang = function_exists('gn_cur_lang') ? gn_cur_lang() : 'vi';
$gns_hello = function_exists('gn_advisor_reply')
    ? gn_advisor_reply('xin chao', $gns_lang)
    : 'Gánh Ngon ở 113 Trần Hưng Đạo, Dương Đông, Phú Quốc. Món đặc trưng là lẩu gà sầu riêng. Gọi hoặc Zalo 0979 232 233. Giá báo tại nhà hàng.';
$gns_tg = function_exists('gn_telegram') ? gn_telegram() : 'https://t.me/ganhngon';
?>
<div class="gns-dock" data-gns-dock>
  <div class="gns-dock-sheet" id="gns-dock-sheet" hidden>
    <div data-gns-menu>
      <p class="gns-dock-kicker">Phú Quốc · <?php echo esc_html(gns_hours()); ?></p>
      <p class="gns-dock-title">Liên hệ nhà hàng</p>
      <button type="button" class="gns-dock-row" data-gns-open-chat>
        <span class="gns-mark gns-mark-chat" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 3C6.5 3 2 6.6 2 11c0 2.4 1.3 4.5 3.4 6-.1 1-.6 2.4-1.2 3.4-.2.3.1.7.5.6 1.9-.5 3.5-1.3 4.5-2 .9.2 1.8.3 2.8.3 5.5 0 10-3.6 10-8.3S17.5 3 12 3z"/></svg>
        </span>
        <span><strong>Chat với nhà hàng</strong><small>Hỏi món, bàn, giờ mở cửa</small></span>
      </button>
      <a class="gns-dock-row" href="<?php echo esc_url(gns_zalo()); ?>" target="_blank" rel="noopener">
        <span class="gns-mark gns-mark-zalo" aria-hidden="true">Z</span>
        <span><strong>Zalo</strong><small><?php echo esc_html(gns_phone_label()); ?></small></span>
      </a>
      <a class="gns-dock-row" href="<?php echo esc_url(gns_wa()); ?>" target="_blank" rel="noopener">
        <span class="gns-mark gns-mark-wa" aria-hidden="true">W</span>
        <span><strong>WhatsApp</strong><small>Khách quốc tế</small></span>
      </a>
      <a class="gns-dock-row" href="<?php echo esc_url($gns_tg); ?>" target="_blank" rel="noopener">
        <span class="gns-mark gns-mark-tg" aria-hidden="true">T</span>
        <span><strong>Telegram</strong><small>@ganhngon</small></span>
      </a>
      <button type="button" class="gns-dock-row" data-gns-wechat>
        <span class="gns-mark gns-mark-wx" aria-hidden="true">微</span>
        <span><strong>WeChat</strong><small><?php echo esc_html($gns_wechat); ?></small></span>
      </button>
    </div>
    <div data-gns-chat hidden>
      <button type="button" class="gns-dock-back" data-gns-back>Các kênh khác</button>
      <div class="gns-dock-log" data-gns-log>
        <p class="gns-dock-msg"><?php echo esc_html($gns_hello); ?></p>
      </div>
      <form class="gns-dock-form" data-gns-form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('gn_ai')); ?>" data-lang="<?php echo esc_attr($gns_lang); ?>">
        <label class="sr-only" for="gns-chat-text">Nhập tin nhắn</label>
        <input id="gns-chat-text" type="text" name="message" autocomplete="off" placeholder="Nhập câu hỏi của anh/chị…">
        <button type="submit">Gửi</button>
      </form>
    </div>
  </div>
  <button class="gns-dock-toggle" type="button" aria-expanded="false" aria-controls="gns-dock-sheet">
    <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M12 3C6.5 3 2 6.6 2 11c0 2.4 1.3 4.5 3.4 6-.1 1-.6 2.4-1.2 3.4-.2.3.1.7.5.6 1.9-.5 3.5-1.3 4.5-2 .9.2 1.8.3 2.8.3 5.5 0 10-3.6 10-8.3S17.5 3 12 3zm-4 9a1.15 1.15 0 1 1 0-2.3A1.15 1.15 0 0 1 8 12zm4 0a1.15 1.15 0 1 1 0-2.3A1.15 1.15 0 0 1 12 12zm4 0a1.15 1.15 0 1 1 0-2.3A1.15 1.15 0 0 1 16 12z"/></svg>
    <span class="sr-only">Mở liên hệ</span>
  </button>
</div>
<div class="gns-wechat" id="gns-wechat" hidden>
  <div class="gns-wechat-card" role="dialog" aria-labelledby="gns-wechat-title">
    <button type="button" class="gns-wechat-close" data-gns-wechat-close aria-label="Đóng">×</button>
    <h2 id="gns-wechat-title">WeChat — Gánh Ngon</h2>
    <p>Tìm ID: <strong><?php echo esc_html($gns_wechat); ?></strong></p>
    <button type="button" class="gns-wechat-copy" data-gns-copy="<?php echo esc_attr($gns_wechat); ?>">Sao chép ID</button>
    <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode('weixin://contacts/profile/' . $gns_wechat)); ?>" alt="Mã WeChat <?php echo esc_attr($gns_wechat); ?>" width="180" height="180">
  </div>
</div>
