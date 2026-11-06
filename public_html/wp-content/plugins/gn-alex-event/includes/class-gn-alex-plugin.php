<?php
class Gn_Alex_Plugin {
    public static function activate(): void {
        $engine = self::engine();
        if (!$engine) {
            return;
        }
        $engine->ensureAlexCampaign();
        $admin = get_role('administrator');
        if ($admin) {
            foreach (['gn_alex_staff', 'gn_alex_admin', 'gn_alex_bank'] as $cap) {
                $admin->add_cap($cap);
            }
        }
        flush_rewrite_rules();
    }

    public static function routes(): void {
        add_rewrite_rule('^su-kien/([^/]+)/?$', 'index.php?gn_alex_event=$matches[1]', 'top');
        add_rewrite_rule('^gn-alex/khach/?$', 'index.php?gn_alex_screen=khach', 'top');
        add_rewrite_rule('^gn-alex/quay/?$', 'index.php?gn_alex_screen=quay', 'top');
        add_rewrite_rule('^gn-alex/desk/?$', 'index.php?gn_alex_screen=desk', 'top');
        add_rewrite_rule('^gn-alex/dashboard/?$', 'index.php?gn_alex_screen=dashboard', 'top');
        add_rewrite_rule('^gn-alex/partner/?$', 'index.php?gn_alex_screen=partner', 'top');
        add_rewrite_rule('^gn-alex/referral/([A-Za-z0-9]+)/?$', 'index.php?gn_alex_screen=referral&gn_alex_event=$matches[1]', 'top');
        add_rewrite_tag('%gn_alex_event%', '([^&]+)');
        add_rewrite_tag('%gn_alex_screen%', '([^&]+)');
    }

    public static function render(): void {
        if (is_admin()) {
            return;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $watched = (bool) preg_match('#^/su-kien/[^/]+#', $path) || str_starts_with($path, '/gn-alex/');
        if (!$watched) {
            return;
        }
        $engine = self::engine();
        if (!$engine) {
            status_header(503);
            echo 'storage unavailable';
            exit;
        }
        $query = [];
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        unset($query['token'], $query['csrf'], $query['guest'], $query['session']);
        $headers = Gn_Alex_Http::forwardedHeaders($_SERVER);
        $http = new Gn_Alex_Http($engine);
        $res = $http->handle((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path, $query, [], $headers, []);
        status_header((int) $res['status']);
        foreach ($res['headers'] as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $res['body'];
        exit;
    }

    public static function rest(): void {
        foreach (Gn_Alex_Http::apiRoutes() as $path => $method) {
            $route = substr($path, strlen('/gn-alex/v1/'));
            register_rest_route('gn-alex/v1', '/' . $route, [
                'methods' => $method,
                'permission_callback' => static function (WP_REST_Request $request) use ($route) {
                    $body = $request->get_json_params();
                    $gate = Gn_Alex_Http::presenceGate('/gn-alex/v1/' . $route, $request->get_headers(), is_array($body) ? $body : []);
                    return $gate === null;
                },
                'callback' => static function (WP_REST_Request $request) use ($route, $method) {
                    $engine = self::engine();
                    if (!$engine) {
                        return new WP_REST_Response(['ok' => false, 'error' => 'storage_unavailable', 'simulated' => false], 503);
                    }
                    $body = $request->get_json_params();
                    if (!is_array($body) || $body === []) {
                        $body = $request->get_body_params();
                    }
                    $http = new Gn_Alex_Http($engine);
                    $res = $http->handle($method, '/gn-alex/v1/' . $route, $request->get_query_params(), is_array($body) ? $body : [], Gn_Alex_Http::normalizeHeaders($request->get_headers()), [], (string) $request->get_body());
                    $decoded = json_decode($res['body'], true);
                    return new WP_REST_Response(is_array($decoded) ? $decoded : ['ok' => false], (int) $res['status']);
                },
            ]);
        }
    }

    public static function adminMenu(): void {
        add_management_page('Alex event', 'Alex event', 'manage_options', 'gn-alex-event', [self::class, 'adminPage']);
    }

    public static function adminPage(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $engine = self::engine();
        $status = $engine ? $engine->providerStatus() : ['provider' => 'unconfigured', 'production_verified' => false, 'webhook_configured' => false, 'payout_enabled' => false];
        echo '<div class="wrap"><h1>Alex event staging</h1>';
        foreach (['gn_alex', 'gn_alex_staff'] as $noticeKey) {
            $noticeValue = isset($_GET[$noticeKey]) ? sanitize_text_field(wp_unslash($_GET[$noticeKey])) : '';
            $notice = Gn_Alex_Http::outcomeMessage($noticeKey, $noticeValue);
            if ($notice !== '') {
                $class = $noticeValue === 'ok' ? 'notice-success' : 'notice-error';
                echo '<div class="notice ' . esc_attr($class) . '"><p>' . esc_html($notice) . '</p></div>';
            }
        }
        $activation = get_option('gn_alex_activation_error');
        if (is_string($activation) && $activation !== '') {
            echo '<div class="notice notice-error"><p>' . esc_html($activation) . '</p></div>';
        }
        echo '<h2>Route ownership</h2><ul>';
        echo '<li>' . esc_html('Owned: /gn-alex/khach, /gn-alex/quay, /gn-alex/desk, /gn-alex/dashboard, /gn-alex/partner, /gn-alex/referral/{code}') . '</li>';
        echo '<li>' . esc_html('A referral visit is provisional. It does not verify a phone, issue an event pass, or create money. Staff still link a booking by hand.') . '</li>';
        echo '<li>' . esc_html('Not owned and not redirected: /gnv/khach, /gnv/quay. This plugin does not disable another voucher plugin.') . '</li>';
        echo '</ul>';
        echo '<h2>Money</h2><p>' . esc_html('Payout is disabled. Alex commission is not calculated. Existing gross commission code is not called and is not switched off by this screen.') . '</p>';
        echo '<h2>Server config</h2><ul>';
        echo '<li>' . esc_html('OTP provider id: ' . (string) ($status['provider'] ?? 'unconfigured')) . '</li>';
        echo '<li>' . esc_html('OTP production verified: ' . (!empty($status['production_verified']) ? 'yes' : 'no')) . '</li>';
        echo '<li>' . esc_html('Webhook secret configured: ' . (!empty($status['webhook_configured']) ? 'yes' : 'no')) . '</li>';
        echo '</ul><p>' . esc_html('Set GN_ALEX_WEBHOOK_SECRET and the OTP gateway in server config. This screen does not accept or display those values.') . '</p>';
        $staffCount = $engine ? $engine->staffCount() : 0;
        if ($staffCount === 0) {
            echo '<h2>First manager</h2>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('gn_alex_bootstrap');
            echo '<input type="hidden" name="action" value="gn_alex_bootstrap">';
            echo '<label for="gn-alex-pin">Manager PIN</label><br>';
            echo '<input id="gn-alex-pin" name="pin" type="password" autocomplete="new-password" required minlength="8" maxlength="12">';
            submit_button('Create manager');
            echo '</form>';
        }
        echo '<h2>Additional staff</h2>';
        if ($staffCount === 0) {
            echo '<p>' . esc_html('Create the first manager before adding quay, cashier, or onboarding people.') . '</p>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gn_alex_enroll');
        echo '<input type="hidden" name="action" value="gn_alex_enroll">';
        echo '<label for="gn-alex-role">Role</label><br><select id="gn-alex-role" name="role">';
        foreach (['quay', 'thungan', 'quanly', 'onboarding'] as $role) {
            echo '<option value="' . esc_attr($role) . '">' . esc_html($role) . '</option>';
        }
        echo '</select><br><label for="gn-alex-label">Label</label><br>';
        echo '<input id="gn-alex-label" name="label" type="text" maxlength="40" required>';
        echo '<br><label for="gn-alex-staff-pin">PIN</label><br>';
        echo '<input id="gn-alex-staff-pin" name="pin" type="password" autocomplete="new-password" required minlength="8" maxlength="12">';
        submit_button('Create staff');
        echo '</form>';
        echo '<p><a href="' . esc_url(home_url('/gn-alex/desk/')) . '">' . esc_html('Open the private onboarding desk') . '</a></p>';
        echo '</div>';
    }

    public static function adminPost(): void {
        if (!current_user_can('manage_options')) {
            wp_die('unauthorized', 403);
        }
        check_admin_referer('gn_alex_bootstrap');
        $engine = self::engine();
        if (!$engine) {
            wp_die('storage unavailable');
        }
        $pin = isset($_POST['pin']) ? (string) wp_unslash($_POST['pin']) : '';
        $result = $engine->bootstrapQuanly(['manage_options' => true, 'id' => get_current_user_id()], $pin, 'quanly');
        wp_safe_redirect(add_query_arg(['page' => 'gn-alex-event', 'gn_alex' => $result['ok'] ? 'ok' : 'denied'], admin_url('tools.php')));
        exit;
    }

    public static function enrollPost(): void {
        if (!current_user_can('manage_options')) {
            wp_die('unauthorized', 403);
        }
        check_admin_referer('gn_alex_enroll');
        $engine = self::engine();
        if (!$engine) {
            wp_die('storage unavailable');
        }
        $pin = isset($_POST['pin']) ? (string) wp_unslash($_POST['pin']) : '';
        $role = isset($_POST['role']) ? sanitize_text_field(wp_unslash($_POST['role'])) : '';
        $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';
        $result = $engine->enrollFromWpAdmin(['manage_options' => true, 'id' => get_current_user_id()], $role, $pin, $label);
        wp_safe_redirect(add_query_arg(['page' => 'gn-alex-event', 'gn_alex_staff' => $result['ok'] ? 'ok' : 'denied'], admin_url('tools.php')));
        exit;
    }

    public static function engine(): ?Gn_Alex_Engine {
        static $engine = null;
        static $tried = false;
        if ($tried) {
            return $engine;
        }
        $tried = true;
        if (!defined('DB_NAME') || !isset($GLOBALS['wpdb'])) {
            return null;
        }
        try {
            $host = (string) DB_HOST;
            $dsn = 'mysql:dbname=' . DB_NAME . ';charset=utf8mb4';
            if (str_contains($host, ':') && str_ends_with($host, '.sock')) {
                $dsn .= ';unix_socket=' . substr($host, strpos($host, ':') + 1);
            } else {
                $dsn .= ';host=' . $host;
            }
            $prefix = $GLOBALS['wpdb']->prefix . 'gn_alex_';
            $storage = Gn_Alex_Storage::mysql($dsn, (string) DB_USER, (string) DB_PASSWORD, $prefix);
            $storage->install();
            $production = defined('GN_ALEX_PRODUCTION') && GN_ALEX_PRODUCTION;
            $webhookSecret = '';
            if (defined('GN_ALEX_WEBHOOK_SECRET') && is_string(GN_ALEX_WEBHOOK_SECRET) && strlen(GN_ALEX_WEBHOOK_SECRET) >= 16) {
                $webhookSecret = GN_ALEX_WEBHOOK_SECRET;
            }
            $tenant = defined('GN_ALEX_TENANT') && is_string(GN_ALEX_TENANT) && GN_ALEX_TENANT !== '' ? GN_ALEX_TENANT : 'ganhngon';
            $engine = new Gn_Alex_Engine(
                $storage,
                self::key('gn_alex_pepper'),
                self::key('gn_alex_token_key'),
                Gn_Alex_Provider_Factory::make(),
                (bool) $production,
                false,
                null,
                '',
                $webhookSecret,
                $tenant
            );
            $engine->ensureAlexCampaign();
        } catch (Throwable $e) {
            update_option('gn_alex_activation_error', 'storage_unavailable', false);
            $engine = null;
        }
        return $engine;
    }

    private static function key(string $option): string {
        $current = get_option($option);
        if (is_string($current) && strlen($current) >= 32) {
            return $current;
        }
        $next = bin2hex(random_bytes(32));
        update_option($option, $next, false);
        return $next;
    }
}
