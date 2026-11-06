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
        add_rewrite_rule('^gnv/khach/?$', 'index.php?gn_alex_screen=khach', 'top');
        add_rewrite_rule('^gnv/quay/?$', 'index.php?gn_alex_screen=quay', 'top');
        add_rewrite_tag('%gn_alex_event%', '([^&]+)');
        add_rewrite_tag('%gn_alex_screen%', '([^&]+)');
    }

    public static function render(): void {
        if (is_admin()) {
            return;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
        $watched = (bool) preg_match('#^/su-kien/[^/]+#', $path) || str_starts_with($path, '/gnv/');
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
        $qs = (string) strtok('');
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);
        $headers = [];
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!empty($_SERVER['HTTP_X_GN_GUEST'])) {
            $headers['x-gn-guest'] = (string) $_SERVER['HTTP_X_GN_GUEST'];
        }
        if (!empty($_SERVER['HTTP_ACCEPT'])) {
            $headers['accept'] = (string) $_SERVER['HTTP_ACCEPT'];
        }
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
        $routes = [
            'otp/request' => 'POST',
            'otp/verify' => 'POST',
            'scan' => 'POST',
            'staff/login' => 'POST',
            'staff/redeem' => 'POST',
            'staff/resolve' => 'POST',
            'staff/rotate' => 'POST',
            'staff/revoke' => 'POST',
            'partner/apply' => 'POST',
            'admin/partner/approve' => 'POST',
            'admin/policy' => 'POST',
            'admin/override/request' => 'POST',
            'admin/override/approve' => 'POST',
            'admin/bank/request' => 'POST',
            'admin/bank/approve' => 'POST',
            'admin/export.csv' => 'POST',
            'invoice' => 'POST',
            'invoice/paid' => 'POST',
            'invoice/reverse' => 'POST',
            'pos/webhook' => 'POST',
            'pos/reconcile' => 'GET',
            'dashboard' => 'GET',
        ];
        foreach ($routes as $route => $method) {
            register_rest_route('gn-alex/v1', '/' . $route, [
                'methods' => $method,
                'permission_callback' => static function () {
                    return true;
                },
                'callback' => static function (WP_REST_Request $request) use ($route, $method) {
                    $engine = self::engine();
                    if (!$engine) {
                        return new WP_REST_Response(['ok' => false, 'error' => 'storage_unavailable', 'simulated' => false], 503);
                    }
                    $headers = [];
                    foreach ($request->get_headers() as $key => $value) {
                        $headers[strtolower((string) $key)] = is_array($value) ? implode(', ', $value) : (string) $value;
                    }
                    $body = $request->get_json_params();
                    if (!is_array($body) || $body === []) {
                        $body = $request->get_body_params();
                    }
                    $http = new Gn_Alex_Http($engine);
                    $res = $http->handle($method, '/gn-alex/v1/' . $route, $request->get_query_params(), is_array($body) ? $body : [], $headers, []);
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
        echo '<div class="wrap"><h1>Alex event</h1>';
        echo '<p>First manager PIN is typed here once. It is hashed. There is no default PIN and this screen does not collect bank accounts.</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gn_alex_bootstrap');
        echo '<input type="hidden" name="action" value="gn_alex_bootstrap">';
        echo '<label for="gn-alex-pin">Manager PIN</label><br>';
        echo '<input id="gn-alex-pin" name="pin" type="password" autocomplete="new-password" required minlength="8" maxlength="12">';
        submit_button('Create manager');
        echo '</form></div>';
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
            $engine = new Gn_Alex_Engine(
                $storage,
                self::key('gn_alex_pepper'),
                self::key('gn_alex_token_key'),
                Gn_Alex_Provider_Factory::make(),
                (bool) $production,
                false
            );
            $engine->ensureAlexCampaign();
        } catch (Throwable $e) {
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
