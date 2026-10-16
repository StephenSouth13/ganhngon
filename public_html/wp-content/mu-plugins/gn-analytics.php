<?php
/**
 * Plugin Name: GN Analytics & Pixels
 * Description: Gắn GA4 + (Meta/TikTok pixel khi có ID) cho ganhngon.com. Tập trung 1 chỗ.
 * Version: 1.0
 * Author: Growth System
 */
if (!defined('ABSPATH')) exit;

// ===== CẤU HÌNH ID (điền vào khi có) =====
define('GN_GA4_ID', 'G-9EL1C2FJGF');   // GA4 property "Gánh Ngon Phú Quốc" (tạo 2026-06-20)
define('GN_META_PIXEL_ID', '');         // điền Pixel ID khi tạo Meta Business
define('GN_TIKTOK_PIXEL_ID', '');       // điền TikTok Pixel ID khi có

// ===== First-touch source capture (cookie gn_src) → đọc bởi booking engine để gắn nguồn vào lead =====
add_action('wp_head', function () {
    echo "\n<!-- GN source capture -->\n<script>(function(){try{var p=new URLSearchParams(location.search);if(document.cookie.indexOf('gn_src=')>-1)return;var v='';if(p.get('ref')){v='partner:'+p.get('ref');}else if(p.get('utm_source')){v=p.get('utm_source');var m=p.get('utm_medium');var c=p.get('utm_campaign');if(m)v+='/'+m;if(c)v+='/'+c;}else if(document.referrer&&document.referrer.indexOf(location.hostname)===-1){v='ref:'+document.referrer;}if(v){document.cookie='gn_src='+encodeURIComponent(v.slice(0,180))+';path=/;max-age=2592000;samesite=lax';}}catch(e){}})();</script>\n";
}, 1);

// ===== GA4 (gtag.js) =====
add_action('wp_head', function () {
    if (!GN_GA4_ID) return;
    $id = esc_js(GN_GA4_ID);
    echo "\n<!-- GA4 Gánh Ngon -->\n";
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr(GN_GA4_ID) . '"></script>' . "\n";
    echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$id}');</script>\n";
}, 1);

// ===== Meta Pixel (chỉ chạy khi có ID) =====
add_action('wp_head', function () {
    if (!GN_META_PIXEL_ID) return;
    $id = esc_js(GN_META_PIXEL_ID);
    echo "\n<!-- Meta Pixel -->\n<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{$id}');fbq('track','PageView');</script>\n";
}, 2);

// ===== TikTok Pixel (chỉ chạy khi có ID) =====
add_action('wp_head', function () {
    if (!GN_TIKTOK_PIXEL_ID) return;
    $id = esc_js(GN_TIKTOK_PIXEL_ID);
    echo "\n<!-- TikTok Pixel -->\n<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=d.createElement('script');o.type='text/javascript';o.async=!0;o.src=i+'?sdkid='+e+'&lib='+t;var a=d.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};ttq.load('{$id}');ttq.page();}(window,document,'ttq');</script>\n";
}, 3);
