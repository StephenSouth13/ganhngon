<?php
/**
 * SAO PHU QUOC - Header
 */
$hotline = '090xxxxxxx';
$zalo = '090xxxxxxx';
$whatsapp = '+84xxxxxxxxx';
$langs = [
    'vi'=>['flag'=>'🇻🇳','name'=>'Tiếng Việt'],'en'=>['flag'=>'🇬🇧','name'=>'English'],
    'zh-CN'=>['flag'=>'🇨🇳','name'=>'简体中文'],'zh-TW'=>['flag'=>'🇹🇼','name'=>'繁體中文'],'ko'=>['flag'=>'🇰🇷','name'=>'한국어'],
    'ja'=>['flag'=>'🇯🇵','name'=>'日本語'],'ru'=>['flag'=>'🇷🇺','name'=>'Русский'],
    'fr'=>['flag'=>'🇫🇷','name'=>'Français'],'de'=>['flag'=>'🇩🇪','name'=>'Deutsch'],
    'es'=>['flag'=>'🇪🇸','name'=>'Español'],'it'=>['flag'=>'🇮🇹','name'=>'Italiano'],
    'th'=>['flag'=>'🇹🇭','name'=>'ไทย'],'hi'=>['flag'=>'🇮🇳','name'=>'हिन्दी'],
    'id'=>['flag'=>'🇮🇩','name'=>'Bahasa Indonesia'],'ms'=>['flag'=>'🇲🇾','name'=>'Bahasa Melayu'],
    'ar'=>['flag'=>'🇸🇦','name'=>'العربية'],'pt'=>['flag'=>'🇧🇷','name'=>'Português'],
    'nl'=>['flag'=>'🇳🇱','name'=>'Nederlands'],'sv'=>['flag'=>'🇸🇪','name'=>'Svenska'],
    'da'=>['flag'=>'🇩🇰','name'=>'Dansk'],'no'=>['flag'=>'🇳🇴','name'=>'Norsk'],
    'fi'=>['flag'=>'🇫🇮','name'=>'Suomi'],'pl'=>['flag'=>'🇵🇱','name'=>'Polski'],
    'tr'=>['flag'=>'🇹🇷','name'=>'Türkçe'],'cs'=>['flag'=>'🇨🇿','name'=>'Čeština'],
    'hu'=>['flag'=>'🇭🇺','name'=>'Magyar'],'el'=>['flag'=>'🇬🇷','name'=>'Ελληνικά'],
    'he'=>['flag'=>'🇮🇱','name'=>'עברית'],'mn'=>['flag'=>'🇲🇳','name'=>'Монгол'],
    'km'=>['flag'=>'🇰🇭','name'=>'ខ្មែរ'],'my'=>['flag'=>'🇲🇲','name'=>'မြန်မာဘာသာ'],
    'tl'=>['flag'=>'🇵🇭','name'=>'Filipino'],'uk'=>['flag'=>'🇺🇦','name'=>'Українська'],
];
$cl = $_COOKIE['sao_lang'] ?? 'vi';
if(!isset($langs[$cl]))$cl='vi';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
<div class="header-inner">
<a href="<?=home_url()?>" class="site-logo"><span class="star">✦</span> SAO PHU QUOC</a>
<nav class="main-nav" id="mainNav">
<a href="<?=home_url()?>" data-i18n>Trang chủ</a>
<a href="<?=home_url('/bai-sao')?>" data-i18n>Bãi Sao</a>
<a href="<?=home_url('/ve-combo')?>" data-i18n>Vé & Combo</a>
<a href="<?=home_url('/ganh-ngon')?>" data-i18n>Gánh Ngon</a>
<a href="<?=home_url('/paghera-yacht')?>" data-i18n>Paghera Yacht</a>
<a href="<?=home_url('/su-kien')?>" data-i18n>Sự kiện</a>
<a href="<?=home_url('/di-chuyen')?>" data-i18n>Di chuyển</a>
<a href="<?=home_url('/hinh-anh')?>" data-i18n>Hình ảnh</a>
<a href="<?=home_url('/cam-nang')?>" data-i18n>Cẩm nang</a>
</nav>
<div class="header-actions">
<div class="lang-switcher">
<button class="lang-btn" onclick="document.getElementById('langDropdown').classList.toggle('open');event.stopPropagation()"><?=$langs[$cl]['flag']?> <?=$langs[$cl]['name']?> ▾</button>
<div class="lang-dropdown" id="langDropdown">
<div class="lang-search"><input type="text" placeholder="🔍 Search..." oninput="var q=this.value.toLowerCase();document.querySelectorAll('.lang-option').forEach(function(e){e.style.display=!q||e.dataset.search.indexOf(q)>=0?'':'none'});" onclick="event.stopPropagation()"></div>
<div class="lang-list">
<?php foreach($langs as $c=>$l):?>
<a class="lang-option <?=$cl===$c?'active':''?>" data-lang="<?=$c?>" onclick="event.preventDefault();switchSAOLang('<?=$c?>');" data-search="<?=strtolower($l['name'])?>">
<span class="lang-flag"><?=$l['flag']?></span><span class="lang-name"><?=$l['name']?></span>
</a>
<?php endforeach;?>
</div></div></div>
<a href="<?=home_url('/dat-cho')?>" class="btn btn-gold btn-sm" data-i18n>Đặt chỗ ngay</a>
<button class="menu-toggle" aria-label="Menu" onclick="document.getElementById('mainNav').classList.toggle('open')"><span></span><span></span><span></span></button>
</div></div>
</header>

<div class="mobile-sticky">
<a href="tel:<?=$hotline?>" class="sticky-item"><span>📞</span><span data-i18n>Gọi</span></a>
<a href="https://zalo.me/<?=$zalo?>" target="_blank" class="sticky-item"><span>💬</span><span data-i18n>Zalo</span></a>
<a href="https://wa.me/<?=$whatsapp?>" target="_blank" class="sticky-item"><span>💬</span><span data-i18n>WhatsApp</span></a>
<a href="<?=home_url('/dat-cho')?>" class="sticky-item sticky-cta"><span>📅</span><span data-i18n>Đặt chỗ</span></a>
<a href="<?=home_url('/di-chuyen')?>" class="sticky-item"><span>📍</span><span data-i18n>Chỉ đường</span></a>
</div>

<script>document.addEventListener('click',function(e){if(!e.target.closest('.lang-switcher'))document.getElementById('langDropdown').classList.remove('open');});</script>
