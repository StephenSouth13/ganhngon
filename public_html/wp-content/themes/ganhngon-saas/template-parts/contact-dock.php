<?php
if (!defined('ABSPATH')) exit;
$gns_lang = function_exists('gn_cur_lang') ? gn_cur_lang() : 'vi';
$gns_hello = function_exists('gn_advisor_reply')
    ? gn_advisor_reply('xin chao', $gns_lang)
    : 'Gánh Ngon ở 113 Trần Hưng Đạo, Dương Đông, Phú Quốc. Món đặc trưng là lẩu gà sầu riêng. Gọi 0979 232 233. Giá báo tại nhà hàng.';
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
      <?php foreach (gns_public_socials() as $gns_social): ?>
      <a class="gns-dock-row" href="<?php echo esc_url($gns_social['url']); ?>" target="_blank" rel="noopener">
        <span class="gns-mark" aria-hidden="true"><?php echo esc_html(function_exists('mb_substr') ? mb_substr($gns_social['label'], 0, 1) : substr($gns_social['label'], 0, 1)); ?></span>
        <span><strong><?php echo esc_html($gns_social['label']); ?></strong><small><?php echo $gns_social['key'] === 'facebook' ? 'Trang chính thức' : esc_html($gns_social['label']); ?></small></span>
      </a>
      <?php endforeach; ?>
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
