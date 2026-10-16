<?php if (!defined('ABSPATH')) exit;
$gn_rtl = (gn_cur_lang() === 'ar');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?><?php echo $gn_rtl ? ' dir="rtl"' : ''; ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class($gn_rtl ? 'gn-body rtl' : 'gn-body'); ?>>
<?php wp_body_open(); ?>
<header class="gn-header">
  <div class="gn-container">
    <a href="<?php echo esc_url(gn_home('/')); ?>" class="gn-logo">
      GÁNH NGON<span class="sub">Đặc sản Việt Nam · Phú Quốc</span>
    </a>
    <button class="gn-nav-toggle" type="button" aria-expanded="false" aria-controls="gn-primary-nav">Menu</button>
    <nav id="gn-primary-nav" class="gn-nav"><?php gn_default_menu(); ?></nav>
    <div class="gn-header-right">
      <?php gn_lang_switcher(); ?>
      <a href="<?php echo esc_url(gn_page_url('dat-ban')); ?>" class="gn-cta-btn"><?php echo esc_html(gn_t('btn_book')); ?></a>
    </div>
  </div>
</header>
