<?php
/* Plugin Name: GN Mail + Telegram — SMTP via Hostinger + Telegram booking alerts */
if (!defined('ABSPATH')) exit;

/* ---- SMTP (Hostinger) ---- */
add_action('phpmailer_init', function ($m) {
    $m->isSMTP();
    $m->Host       = 'smtp.hostinger.com';
    $m->SMTPAuth   = true;
    $m->Port       = 465;
    $m->SMTPSecure = 'ssl';
    $m->Username   = 'admin@ganhngon.com';
    $m->Password   = defined('GN_SMTP_PASS') ? GN_SMTP_PASS : '';
}, 10);
add_filter('wp_mail_from',      function () { return 'admin@ganhngon.com'; }, 99);
add_filter('wp_mail_from_name', function () { return 'Gánh Ngon Phú Quốc'; }, 99);

/* ---- Telegram helper ---- */
if (!function_exists('gn_send_telegram')) {
    function gn_send_telegram($text) {
        $token = trim(get_option('gn_tg_bot_token', ''));
        $chat  = trim(get_option('gn_tg_chat_id', ''));
        if ($token === '' || $chat === '') return false;
        $resp = wp_remote_post("https://api.telegram.org/bot{$token}/sendMessage", [
            'timeout' => 15,
            'body'    => [
                'chat_id'                  => $chat,
                'text'                     => $text,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ],
        ]);
        return !is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200;
    }
}
