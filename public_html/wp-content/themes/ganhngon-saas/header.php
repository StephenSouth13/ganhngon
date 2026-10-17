<?php
if (!defined('ABSPATH')) exit;
$gns_rtl = function_exists('gn_cur_lang') && gn_cur_lang() === 'ar';
$gns_items = gns_nav_items();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?><?php echo $gns_rtl ? ' dir="rtl"' : ''; ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if (defined('GNS_LOCAL_SAMPLE') && GNS_LOCAL_SAMPLE): ?>
<p class="gns-sample" role="note">Bản xem thử trên máy local. Trang chủ và thực đơn dùng nội dung đã đăng trên ganhngon.com. Các trang khác trong bản này là nội dung mẫu để chụp ảnh — không phải cơ sở dữ liệu production.</p>
<?php endif; ?>
<?php get_template_part('template-parts/icons'); ?>
<a class="skip" href="#main">Bỏ qua đến nội dung</a>
<header class="site-header" data-header>
  <div class="wrap header-inner">
    <a class="brand" href="<?php echo esc_url(gns_url('')); ?>" aria-label="Gánh Ngon — trang chủ">
      <img class="brand-mark" src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/img/logo-mark.svg'); ?>" alt="" width="36" height="42">
      <span class="brand-text"><span class="brand-name">Gánh Ngon</span><span class="brand-sub">Phú Quốc</span></span>
    </a>
    <nav class="main-nav" aria-label="Điều hướng chính">
      <?php
      $gns_nav = gns_nav_html('primary');
      if (is_string($gns_nav) && trim($gns_nav) !== '') {
          echo $gns_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- walker escapes
      } else {
          foreach ($gns_items as $slug => $label) {
              echo '<a href="' . esc_url(gns_url($slug)) . '"' . (gns_nav_current($slug) ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
          }
      }
      ?>
    </nav>
    <div class="header-actions">
      <?php if (function_exists('gn_lang_switcher')) gn_lang_switcher(); ?>
      <a class="header-phone" href="<?php echo esc_url(gns_phone_href()); ?>">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg>
        <span><?php echo esc_html(gns_phone_label()); ?></span>
      </a>
      <a class="btn btn-ember btn-sm" href="<?php echo esc_url(gns_url('dat-ban')); ?>"><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></a>
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="drawer" data-nav-toggle>
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-menu"></use></svg>
        <span class="sr-only">Mở menu</span>
      </button>
    </div>
  </div>
</header>
<div class="drawer" id="drawer" hidden data-drawer>
  <div class="drawer-panel">
    <button class="drawer-close" type="button" data-nav-close>
      <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-close"></use></svg>
      <span class="sr-only">Đóng menu</span>
    </button>
    <nav aria-label="Điều hướng trên điện thoại">
      <?php
      if (is_string($gns_nav) && trim($gns_nav) !== '') {
          echo $gns_nav; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- walker escapes
      } else {
          foreach ($gns_items as $slug => $label) {
              echo '<a href="' . esc_url(gns_url($slug)) . '"' . (gns_nav_current($slug) ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
          }
      }
      ?>
    </nav>
    <div class="drawer-foot">
      <a class="btn btn-ember" href="<?php echo esc_url(gns_url('dat-ban')); ?>"><?php echo esc_html(gns_t('btn_book', 'Đặt bàn')); ?></a>
      <a class="btn btn-ghost-light" href="<?php echo esc_url(gns_phone_href()); ?>">
        <svg class="icon" aria-hidden="true" focusable="false"><use href="#i-phone"></use></svg>
        <?php echo esc_html(gns_phone_label()); ?>
      </a>
      <p><?php echo esc_html(gns_addr()); ?><br><?php echo esc_html(gns_hours()); ?></p>
    </div>
  </div>
</div>
<main id="main">
