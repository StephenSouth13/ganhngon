<?php
class Gn_Alex_View {
    public static function page(string $lang, string $title, string $body, string $kind): string {
        $copy = Gn_Alex_Copy::bundle($lang);
        $esc = static function (string $v): string {
            return Gn_Alex_Text::esc($v);
        };
        $langAttr = $esc($copy['html_lang']);
        $titleEsc = $esc($title);
        return '<!DOCTYPE html><html lang="' . $langAttr . '"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $titleEsc . '</title>'
            . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&amp;family=Be+Vietnam+Pro:wght@400;600&amp;display=swap">'
            . '<link rel="stylesheet" href="/assets/gn-alex.css">'
            . '</head><body class="gn-alex kind-' . $esc($kind) . '">'
            . '<a class="skip" href="#main">' . $esc($lang === 'ru' ? 'К содержанию' : 'Skip to content') . '</a>'
            . '<header class="top"><p class="brand">Gánh Ngon</p><nav><a href="' . $esc($copy['switch_href']) . '">' . $esc($copy['switch']) . '</a></nav></header>'
            . '<main id="main">' . $body . '</main></body></html>';
    }

    /** @param array<string,mixed> $state */
    public static function landing(string $lang, array $state): string {
        $c = Gn_Alex_Copy::bundle($lang);
        $esc = [Gn_Alex_Text::class, 'esc'];
        $action = $esc($state['action']);
        $body = '<section class="poster" aria-labelledby="event-title">'
            . '<p class="eyebrow">' . $esc($c['eyebrow']) . '</p>'
            . '<h1 id="event-title" class="alex">' . $esc($c['poster']) . '</h1>'
            . '<p class="when">' . $esc($c['when']) . '</p>'
            . '<p class="where">' . $esc($c['where']) . '</p>'
            . '<p class="lead">' . $esc($c['lead']) . '</p>'
            . '<svg class="wave" viewBox="0 0 360 48" aria-hidden="true"><path d="M0 24 Q15 4 30 24 T60 24 T90 24 T120 24 T150 24 T180 24 T210 24 T240 24 T270 24 T300 24 T330 24 T360 24" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
            . '<ul class="rules"><li>' . $esc($c['c1']) . '</li><li>' . $esc($c['c2']) . '</li><li>' . $esc($c['c3']) . '</li><li>' . $esc($c['c4']) . '</li></ul>'
            . '</section>';
        if (!empty($state['referral'])) {
            $body .= '<p class="notice" data-redeems="0">' . $esc($c['referral']) . '</p>';
        }
        $body .= '<form class="card" method="post" action="' . $action . '">'
            . '<input type="hidden" name="campaign" value="' . $esc((string) $state['campaign']) . '">'
            . '<input type="hidden" name="locale" value="' . $esc($c['html_lang']) . '">'
            . '<input type="hidden" name="csrf_session" value="' . $esc((string) $state['csrf_session']) . '">'
            . '<input type="hidden" name="csrf" value="' . $esc((string) $state['csrf']) . '">'
            . '<label for="gn-name">' . $esc($c['name']) . '</label>'
            . '<input id="gn-name" name="name" autocomplete="name" required maxlength="80">'
            . '<label for="gn-phone">' . $esc($c['phone']) . '</label>'
            . '<input id="gn-phone" name="phone" inputmode="tel" autocomplete="tel" required>'
            . '<label class="check"><input type="checkbox" name="consent" value="1" required> <span>' . $esc($c['consent']) . '</span></label>'
            . '<button type="submit">' . $esc($c['submit']) . '</button>'
            . '</form>';
        return self::page($lang, 'ALEX · Gánh Ngon', $body, 'dj');
    }

    /** @param array<string,mixed> $status */
    public static function guest(string $lang, array $status): string {
        $c = Gn_Alex_Copy::bundle($lang);
        $esc = [Gn_Alex_Text::class, 'esc'];
        $body = '<section class="card"><h1>' . $esc($c['guest_title']) . '</h1>'
            . '<p class="name">' . $esc((string) ($status['display_name'] ?? '')) . '</p>'
            . '<p class="mask">' . $esc((string) ($status['phone_mask'] ?? '')) . '</p>'
            . '<p class="remain" aria-live="polite"><span>' . $esc($c['remaining']) . '</span> <strong>' . $esc((string) ($status['remaining'] ?? '')) . '</strong></p>'
            . '<p>' . $esc($c['c3']) . '</p></section>';
        return self::page($lang, $c['guest_title'], $body, 'dj');
    }

    public static function counter(string $lang): string {
        $c = Gn_Alex_Copy::bundle($lang);
        $esc = [Gn_Alex_Text::class, 'esc'];
        $body = '<section class="saas"><h1>' . $esc($c['counter_title']) . '</h1>'
            . '<form id="gn-login" method="post" action="/gn-alex/v1/staff/login">'
            . '<label for="staff-id">' . $esc($c['staff_id']) . '</label>'
            . '<input id="staff-id" name="staff_id" inputmode="numeric" autocomplete="username" required>'
            . '<label for="staff-pin">' . $esc($c['pin']) . '</label>'
            . '<input id="staff-pin" name="pin" type="password" autocomplete="current-password" required>'
            . '<button type="submit">' . $esc($c['login']) . '</button></form>'
            . '<form id="gn-redeem" method="post" action="/gn-alex/v1/staff/redeem" hidden>'
            . '<label for="proof-name">' . $esc($c['name']) . '</label><input id="proof-name" name="proof_name" autocomplete="off" required>'
            . '<label for="proof-phone">' . $esc($c['phone']) . '</label><input id="proof-phone" name="proof_phone" inputmode="tel" autocomplete="off" required>'
            . '<input type="hidden" name="campaign" value="' . $esc(Gn_Alex_Engine::ALEX_SLUG) . '">'
            . '<input type="hidden" name="confirm" value="1">'
            . '<button type="submit" name="item" value="beer">' . $esc($c['beer_btn']) . '</button>'
            . '<button type="submit" name="item" value="salad">' . $esc($c['salad_btn']) . '</button>'
            . '<p id="gn-result" role="status" aria-live="polite"></p></form>'
            . '<script src="/assets/gn-alex.js"></script></section>';
        return self::page($lang, $c['counter_title'], $body, 'saas');
    }

    /** @param array<string,mixed> $metrics */
    public static function dashboard(string $lang, array $metrics, bool $demo): string {
        $c = Gn_Alex_Copy::bundle($lang);
        $esc = [Gn_Alex_Text::class, 'esc'];
        $cards = '';
        foreach ([
            'scans' => 'Scans',
            'eligible_registrations' => 'Eligible',
            'checkins' => 'Check-ins',
            'beer_issued' => 'Beer',
            'salad' => 'Salad',
            'suspicious_flags' => 'Flags',
            'pos_invoice_count' => 'POS invoices',
            'pos_revenue' => 'POS revenue',
        ] as $key => $label) {
            $cards .= '<article><h2>' . $esc($label) . '</h2><p>' . $esc((string) ($metrics[$key] ?? '0')) . '</p></article>';
        }
        $demoNote = $demo ? '<p class="demo">' . $esc($c['demo']) . '</p>' : '';
        $body = '<section class="saas"><p class="eyebrow">' . $esc($c['dash_title']) . '</p><h1>' . $esc($c['poster']) . '</h1>'
            . $demoNote
            . '<div class="grid">' . $cards . '</div>'
            . '<p class="commission">' . $esc($lang === 'ru' ? 'Комиссия Alex не рассчитывается.' : 'Alex commission is not calculated.') . '</p>'
            . '<form method="get" action="/gnv/dashboard"><label for="event">Event</label>'
            . '<select id="event" name="event"><option value="' . $esc(Gn_Alex_Engine::ALEX_SLUG) . '">ALEX 2026-11-08</option></select>'
            . '<button type="submit">' . $esc($lang === 'ru' ? 'Показать' : 'Show') . '</button></form></section>';
        return self::page($lang, $c['dash_title'], $body, 'saas');
    }

    public static function partner(string $lang, array $state): string {
        $c = Gn_Alex_Copy::bundle($lang);
        $esc = [Gn_Alex_Text::class, 'esc'];
        $body = '<section class="saas"><h1>' . $esc($c['partner_title']) . '</h1><p>' . $esc($c['no_bank']) . '</p>'
            . '<form method="post" action="/gn-alex/v1/partner/apply">'
            . '<input type="hidden" name="csrf_session" value="' . $esc((string) $state['csrf_session']) . '">'
            . '<input type="hidden" name="csrf" value="' . $esc((string) $state['csrf']) . '">'
            . '<label for="p-name">' . $esc($c['name']) . '</label><input id="p-name" name="name" required>'
            . '<label for="p-phone">' . $esc($c['phone']) . '</label><input id="p-phone" name="phone" inputmode="tel" required>'
            . '<label for="p-role">' . $esc($c['role']) . '</label><select id="p-role" name="role"><option value="ctv">' . $esc($c['ctv']) . '</option><option value="driver">' . $esc($c['driver']) . '</option></select>'
            . '<label class="check"><input type="checkbox" name="consent" value="1" required> <span>' . $esc($c['consent']) . '</span></label>'
            . '<button type="submit">' . $esc($c['submit']) . '</button></form></section>';
        return self::page($lang, $c['partner_title'], $body, 'saas');
    }
}
