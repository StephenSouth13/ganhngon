<?php
class Gn_Alex_Http {
    public function __construct(private Gn_Alex_Engine $engine) {
    }

    /** @return list<string> */
    public static function routes(): array {
        return [
            'GET /su-kien/{slug}/',
            'GET /su-kien/{slug}/r/{partner}/',
            'GET /gnv/khach',
            'GET /gnv/quay',
            'GET /gnv/dashboard',
            'GET /gnv/partner',
            'POST /gn-alex/v1/otp/request',
            'POST /gn-alex/v1/otp/verify',
            'POST /gn-alex/v1/scan',
            'POST /gn-alex/v1/staff/login',
            'POST /gn-alex/v1/staff/rotate',
            'POST /gn-alex/v1/staff/revoke',
            'POST /gn-alex/v1/staff/redeem',
            'POST /gn-alex/v1/staff/resolve',
            'POST /gn-alex/v1/dashboard',
            'POST /gn-alex/v1/partner/apply',
            'POST /gn-alex/v1/admin/partner/approve',
            'POST /gn-alex/v1/admin/policy',
            'POST /gn-alex/v1/admin/override/request',
            'POST /gn-alex/v1/admin/override/approve',
            'POST /gn-alex/v1/admin/bank/request',
            'POST /gn-alex/v1/admin/bank/approve',
            'POST /gn-alex/v1/admin/export.csv',
            'POST /gn-alex/v1/pos/webhook',
            'GET /gn-alex/v1/pos/reconcile',
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     * @param array<string,string> $headers
     * @param array<string,string> $cookies
     * @return array{status:int,headers:array<string,string>,body:string}
     */
    public function handle(string $method, string $path, array $query, array $body, array $headers, array $cookies, string $rawBody = ''): array {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            $path = '/su-kien/' . Gn_Alex_Engine::ALEX_SLUG;
        }
        $lang = ($query['lang'] ?? '') === 'ru' ? 'ru' : 'en';
        $method = strtoupper($method);

        if ($method === 'GET' && preg_match('#^/su-kien/([a-z0-9-]+)$#', $path, $m)) {
            $this->engine->recordScan($m[1], 'registration');
            return $this->html(200, Gn_Alex_View::landing($lang, $this->publicForm($m[1], false)));
        }
        if ($method === 'GET' && preg_match('#^/su-kien/([a-z0-9-]+)/r/([A-Za-z0-9]+)$#', $path, $m)) {
            $scan = $this->engine->recordScan($m[1], 'referral', $m[2]);
            $state = $this->publicForm($m[1], true);
            $state['scan_ok'] = $scan['ok'];
            return $this->html($scan['ok'] ? 200 : 404, Gn_Alex_View::landing($lang, $state));
        }
        if ($method === 'GET' && $path === '/gnv/khach') {
            $guest = (string) ($headers['x-gn-guest'] ?? '');
            if ($guest !== '') {
                $status = $this->engine->guestStatus($guest);
                if ($status['ok']) {
                    return $this->html(200, Gn_Alex_View::guest($lang, $status));
                }
            }
            return $this->html(200, Gn_Alex_View::landing($lang, $this->publicForm(Gn_Alex_Engine::ALEX_SLUG, false)));
        }
        if ($method === 'GET' && $path === '/gnv/quay') {
            return $this->html(200, Gn_Alex_View::counter($lang));
        }
        if ($method === 'GET' && $path === '/gnv/partner') {
            return $this->html(200, Gn_Alex_View::partner($lang, $this->publicForm(Gn_Alex_Engine::ALEX_SLUG, false, 'partner_apply')));
        }
        if ($method === 'GET' && ($path === '/gnv/dashboard' || $path === '/gn-alex/v1/dashboard')) {
            $blocked = self::presenceGate($path, $headers, []);
            if ($blocked !== null) {
                return $this->json(['ok' => false, 'error' => $blocked, 'http' => 401]);
            }
            $slug = (string) ($query['event'] ?? Gn_Alex_Engine::ALEX_SLUG);
            $metrics = $this->engine->publicMetrics($slug, $this->authBody([], $headers));
            if (!$metrics['ok']) {
                return $this->json($metrics);
            }
            if (($headers['accept'] ?? '') === 'application/json' || $path === '/gn-alex/v1/dashboard') {
                return $this->json($metrics);
            }
            return $this->html(200, Gn_Alex_View::dashboard($lang, $metrics, false));
        }
        if ($method === 'GET' && $path === '/gn-alex/v1/pos/reconcile') {
            $blocked = self::presenceGate($path, $headers, []);
            if ($blocked !== null) {
                return $this->json(['ok' => false, 'error' => $blocked, 'http' => 401]);
            }
            return $this->json($this->engine->posReconcileCsv());
        }

        if ($method !== 'POST') {
            return $this->json(['ok' => false, 'error' => 'not_found', 'http' => 404]);
        }

        $blocked = self::presenceGate($path, $headers, $body);
        if ($blocked !== null) {
            return $this->json(['ok' => false, 'error' => $blocked, 'http' => 401]);
        }
        $auth = $this->authBody($body, $headers);
        if ($path === '/gn-alex/v1/otp/request') {
            return $this->json($this->engine->requestOtp($body));
        }
        if ($path === '/gn-alex/v1/otp/verify') {
            return $this->json($this->engine->verifyOtp($body));
        }
        if ($path === '/gn-alex/v1/scan') {
            return $this->json($this->engine->recordScan(
                (string) ($body['campaign'] ?? ''),
                (string) ($body['kind'] ?? 'registration'),
                isset($body['partner_public_id']) ? (string) $body['partner_public_id'] : null,
                $body
            ));
        }
        if ($path === '/gn-alex/v1/staff/login') {
            return $this->json($this->engine->staffLogin((int) ($body['staff_id'] ?? 0), (string) ($body['pin'] ?? ''), (string) ($body['ip'] ?? '0')));
        }
        if ($path === '/gn-alex/v1/staff/redeem') {
            // Query-string ticket codes are never copied into the redemption.
            return $this->json($this->engine->redeem($auth));
        }
        if ($path === '/gn-alex/v1/staff/resolve') {
            return $this->json($this->engine->resolvePresence($auth));
        }
        if ($path === '/gn-alex/v1/staff/rotate') {
            return $this->json($this->engine->staffRotate($auth));
        }
        if ($path === '/gn-alex/v1/staff/revoke') {
            return $this->json($this->engine->staffRevoke($auth, (int) ($body['session_id'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/partner/apply') {
            return $this->json($this->engine->applyPartner($body));
        }
        if ($path === '/gn-alex/v1/admin/partner/approve') {
            return $this->json($this->engine->approvePartner($auth, (int) ($body['partner_id'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/admin/policy') {
            return $this->json($this->engine->updatePolicy($auth, (string) ($body['slug'] ?? ''), (int) ($body['beer_quota'] ?? 0), (int) ($body['salad_quota'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/admin/override/request') {
            return $this->json($this->engine->requestOverride($auth, (int) ($body['voucher_id'] ?? 0), (int) ($body['delta'] ?? 0), (string) ($body['reason'] ?? '')));
        }
        if ($path === '/gn-alex/v1/admin/override/approve') {
            return $this->json($this->engine->approveOverride($auth, (int) ($body['override_id'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/admin/bank/request') {
            return $this->json($this->engine->requestBank($auth, (int) ($body['partner_id'] ?? 0), (string) ($body['ciphertext'] ?? '')));
        }
        if ($path === '/gn-alex/v1/admin/bank/approve') {
            return $this->json($this->engine->approveBank($auth, (int) ($body['bank_id'] ?? 0), (string) ($body['ciphertext'] ?? '')));
        }
        if ($path === '/gn-alex/v1/admin/export.csv') {
            return $this->json($this->engine->exportCsv($auth));
        }
        if ($path === '/gn-alex/v1/pos/webhook') {
            return $this->json($this->engine->ingestWebhook($rawBody, $headers));
        }
        if ($path === '/gn-alex/v1/admin/partner/grant') {
            return $this->json($this->engine->grantAttribution($auth, (int) ($body['partner_id'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/admin/rule/propose') {
            return $this->json($this->engine->proposeRule($auth, (string) ($body['role'] ?? ''), (int) ($body['effective_from'] ?? 0), (int) ($body['effective_to'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/admin/rule/approve') {
            return $this->json($this->engine->approveRule($auth, (int) ($body['rule_id'] ?? 0)));
        }
        if ($path === '/gn-alex/v1/admin/attribution/price') {
            return $this->json($this->engine->priceAttribution($auth, (string) ($body['external_id'] ?? '')));
        }
        if ($path === '/gn-alex/v1/invoice') {
            return $this->json($this->engine->recordInvoice($auth, $body));
        }
        if ($path === '/gn-alex/v1/invoice/paid') {
            return $this->json($this->engine->markApprovedPaid($auth, (string) ($body['external_id'] ?? ''), (string) ($body['idempotency_key'] ?? '')));
        }
        if ($path === '/gn-alex/v1/invoice/reverse') {
            return $this->json($this->engine->reverseInvoice($auth, (string) ($body['external_id'] ?? ''), (string) ($body['kind'] ?? ''), (string) ($body['idempotency_key'] ?? '')));
        }
        return $this->json(['ok' => false, 'error' => 'not_found', 'http' => 404]);
    }

    /** @return array{campaign:string,csrf:string,action:string,referral:bool} */
    private function publicForm(string $campaign, bool $referral, string $purpose = 'otp_request'): array {
        $csrf = $this->engine->issueRegistrationToken($purpose);
        return [
            'campaign' => $campaign,
            'csrf' => (string) ($csrf['csrf'] ?? ''),
            'action' => $purpose === 'partner_apply' ? '/gn-alex/v1/partner/apply' : '/gn-alex/v1/otp/request',
            'referral' => $referral,
        ];
    }

    /**
     * Presence check only. A passing gate does not authenticate the caller.
     * @param array<string,string> $headers
     * @param array<string,mixed> $body
     */
    public static function presenceGate(string $path, array $headers, array $body): ?string {
        $bearer = stripos((string) ($headers['authorization'] ?? ''), 'bearer ') === 0;
        $staffPath = str_contains($path, '/staff/') && !str_ends_with($path, '/staff/login');
        $adminPath = str_contains($path, '/admin/') || str_contains($path, '/invoice');
        $metricPath = str_ends_with($path, '/dashboard') || str_ends_with($path, '/pos/reconcile');
        if (($staffPath || $adminPath || $metricPath) && !$bearer) {
            return 'credential_missing';
        }
        if (str_ends_with($path, '/pos/webhook')) {
            foreach (['x-gn-timestamp', 'x-gn-signature', 'x-gn-tenant'] as $header) {
                if (trim((string) ($headers[$header] ?? '')) === '') {
                    return 'webhook_unauthorized';
                }
            }
        }
        if (str_ends_with($path, '/otp/request') || str_ends_with($path, '/otp/verify') || str_ends_with($path, '/partner/apply')) {
            if (trim((string) ($body['csrf'] ?? '')) === '') {
                return 'csrf_failed';
            }
        }
        return null;
    }

    /**
     * Session comes from the Authorization header, never from a cookie.
     * @param array<string,mixed> $body
     * @param array<string,string> $headers
     * @return array<string,mixed>
     */
    private function authBody(array $body, array $headers): array {
        $token = '';
        $auth = (string) ($headers['authorization'] ?? '');
        if (stripos($auth, 'bearer ') === 0) {
            $token = trim(substr($auth, 7));
        }
        $body['session_token'] = $token;
        if (!empty($headers['x-gn-csrf'])) {
            $body['csrf'] = (string) $headers['x-gn-csrf'];
        }
        return $body;
    }

    /** @param array<string,mixed> $payload */
    private function json(array $payload): array {
        $status = (int) ($payload['http'] ?? ($payload['ok'] ? 200 : 422));
        return [
            'status' => $status,
            'headers' => ['content-type' => 'application/json; charset=utf-8'],
            'body' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function html(int $status, string $html): array {
        return [
            'status' => $status,
            'headers' => ['content-type' => 'text/html; charset=utf-8'],
            'body' => $html,
        ];
    }
}
