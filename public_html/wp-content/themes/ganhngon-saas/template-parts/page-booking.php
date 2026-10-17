<?php if (!defined('ABSPATH')) exit; ?>
<section class="booking" aria-labelledby="booking-title">
  <img src="<?php echo esc_url(gns_photo('2026/09/ganhngon-khong-gian-quan.webp')); ?>" alt="Sảnh nhà hàng Gánh Ngon" width="1600" height="1066" loading="eager" decoding="async" class="booking-bg">
  <div class="booking-shade" aria-hidden="true"></div>
  <div class="wrap booking-inner">
    <div class="booking-copy">
      <p class="eyebrow eyebrow-light">Đặt bàn</p>
      <h1 id="booking-title" class="display">Giữ bàn cho<br><em>bữa ăn của anh chị</em>.</h1>
      <p class="hero-lead">Để lại tên và số điện thoại, nhà hàng gọi lại xác nhận trong giờ mở cửa. Bàn thường nhận trong ngày.</p>
      <ul class="booking-facts">
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-clock"></use></svg><span><strong><?php echo esc_html(gns_hours()); ?></strong>Xác nhận nhanh trong giờ mở cửa</span></li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-flame"></use></svg><span><strong>Lẩu gà sầu riêng</strong>Báo trước khoảng 1 tiếng</span></li>
        <li><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-users"></use></svg><span><strong>Phòng VIP &amp; đoàn</strong>VIP khoảng 24 giờ · đoàn từ 20 khách 48–72 giờ</span></li>
      </ul>
      <div class="booking-quick">
        <a class="btn btn-ghost-light" href="<?php echo esc_url(gns_phone_href()); ?>"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg> <?php echo esc_html(gns_phone_label()); ?></a>
        <a class="btn btn-ghost-light" href="<?php echo esc_url(gns_zalo()); ?>" rel="noopener" target="_blank"><svg class="icon" aria-hidden="true" focusable="false"><use href="#i-chat"></use></svg> Zalo</a>
      </div>
    </div>
    <div class="booking-card">
      <div class="booking-card-head">
        <p class="booking-card-kicker">Gánh Ngon Phú Quốc</p>
        <h2 class="booking-card-title">Gửi yêu cầu đặt bàn</h2>
        <p class="booking-card-sub">Nhà hàng gọi lại xác nhận trong giờ mở cửa. Form này gửi về hệ thống đặt bàn hiện tại.</p>
      </div>
      <div class="form booking-form">
        <?php
        if (function_exists('gn_dat_ban_form_html')) {
            echo gns_booking_form_with_guest_fields(gn_dat_ban_form_html()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- form HTML from the parent theme
        } else {
            echo do_shortcode('[sao_booking]');
        }
        ?>
      </div>
    </div>
  </div>
</section>
