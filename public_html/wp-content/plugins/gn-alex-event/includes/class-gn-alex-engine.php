<?php
class Gn_Alex_Engine {
    public const SESSION_TTL = 900;
    public const OTP_TTL = 300;
    public const PRESENCE_TTL = 120;
    public const HOLD_TTL = 120;
    public const ALEX_SLUG = 'alex-dj-20261108';
    public const ALEX_FAMILY = 'alex-dj-pq-20261108';
    public const ALEX_PARTNER = 'alex';
    public const REFERRAL_FAMILY = 'referral-food';

    private ?int $nowFixed = null;
    private string $dummyHash;
    /** @var list<string> */
    private array $sink = [];

    public function __construct(
        public Gn_Alex_Storage $db,
        private string $pepper,
        private string $tokenKey,
        private Gn_Alex_Otp_Provider $otp,
        private bool $production,
        private bool $testHarness,
        private ?Gn_Alex_Get_Client $httpClient = null,
        private string $posEndpoint = ''
    ) {
        if (strlen($pepper) < 16 || strlen($tokenKey) < 16) {
            throw new InvalidArgumentException('key length');
        }
        $this->dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    }

    public function setNow(?int $unix): void {
        $this->nowFixed = $unix;
    }

    public function now(): int {
        return $this->nowFixed ?? time();
    }

    /** @return list<string> */
    public function debugSink(): array {
        return $this->sink;
    }

    public function registrationQr(string $slug): string {
        return '/su-kien/' . rawurlencode($slug) . '/';
    }

    public function referralQr(string $slug, string $publicId): string {
        return '/su-kien/' . rawurlencode($slug) . '/r/' . rawurlencode($publicId) . '/';
    }

    public function guestPath(): string {
        return '/gnv/khach';
    }

    public function counterPath(): string {
        return '/gnv/quay';
    }

    public function tokenUrl(string $token): array {
        return $this->err('token_not_placeable_in_url');
    }

    public function issuePublicCsrf(string $sid): array {
        if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $sid)) {
            return $this->err('csrf_session');
        }
        return $this->ok(['csrf' => hash_hmac('sha256', 'pub|' . $sid, $this->tokenKey), 'csrf_session' => $sid]);
    }

    public function ensureAlexCampaign(): array {
        $found = $this->campaignBySlug(self::ALEX_SLUG);
        if ($found) {
            return $found;
        }
        $start = new DateTimeImmutable('2026-11-08 18:00:00', new DateTimeZone('Asia/Bangkok'));
        $end = new DateTimeImmutable('2026-11-09 02:00:00', new DateTimeZone('Asia/Bangkok'));
        $en = Gn_Alex_Copy::bundle('en');
        $ru = Gn_Alex_Copy::bundle('ru');
        $id = $this->db->insert(
            "INSERT INTO {$this->t('campaign')} (slug, campaign_family, partner_code, starts_at, ends_at, tz, beer_quota, salad_quota, policy_version, title_en, title_ru, conditions_en, conditions_ru, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                self::ALEX_SLUG, self::ALEX_FAMILY, self::ALEX_PARTNER, $start->getTimestamp(), $end->getTimestamp(),
                'Asia/Bangkok', 3, 1, 1, $en['poster'] . ' DJ party', $ru['poster'] . ' DJ',
                $en['c1'] . ' ' . $en['c2'] . ' ' . $en['c3'] . ' ' . $en['c4'],
                $ru['c1'] . ' ' . $ru['c2'] . ' ' . $ru['c3'] . ' ' . $ru['c4'],
                'scheduled', $this->now(),
            ]
        );
        return $this->campaignById($id);
    }

    public function createCampaign(array $req, array $in): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $slug = (string) ($in['slug'] ?? '');
        $family = (string) ($in['family'] ?? '');
        if (!preg_match('/^[a-z0-9-]{4,80}$/', $slug) || !preg_match('/^[a-z0-9-]{4,80}$/', $family)) {
            return $this->err('campaign_invalid');
        }
        if ($this->campaignBySlug($slug)) {
            return $this->err('campaign_exists');
        }
        $beer = (int) ($in['beer_quota'] ?? 3);
        $salad = (int) ($in['salad_quota'] ?? 1);
        if ($beer < 1 || $beer > 10 || $salad < 0 || $salad > 1) {
            return $this->err('policy_invalid');
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('campaign')} (slug, campaign_family, partner_code, starts_at, ends_at, tz, beer_quota, salad_quota, policy_version, title_en, title_ru, conditions_en, conditions_ru, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,1,?,?,?,?,?,?)",
            [
                $slug, $family, (string) ($in['partner_code'] ?? 'event'),
                (int) ($in['starts_at'] ?? $this->now()), (int) ($in['ends_at'] ?? ($this->now() + 3600)),
                'Asia/Bangkok', $beer, $salad, $slug, $slug, 'conditions', 'conditions', 'scheduled', $this->now(),
            ]
        );
        $this->audit('staff:' . $staff['staff_id'], 'campaign_create', $slug, [], ['id' => $id]);
        return $this->ok(['campaign_id' => $id]);
    }

    public function requestOtp(array $in): array {
        if (!$this->checkPublicCsrf((string) ($in['csrf_session'] ?? ''), (string) ($in['csrf'] ?? ''))) {
            return $this->err('csrf_failed', 403);
        }
        $locale = (string) ($in['locale'] ?? '');
        if (!in_array($locale, ['ru', 'en'], true)) {
            return $this->err('locale_required');
        }
        if (empty($in['consent'])) {
            return $this->err('consent_required');
        }
        $phone = Gn_Alex_Phone::canonicalize((string) ($in['phone'] ?? ''));
        if ($phone === null) {
            return $this->err('phone_invalid');
        }
        $name = Gn_Alex_Text::sanitize((string) ($in['name'] ?? ''), 80);
        if ($name === '') {
            return $this->err('name_required');
        }
        $campaign = $this->campaignBySlug((string) ($in['campaign'] ?? ''));
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        $hmac = Gn_Alex_Phone::hmac($phone, $this->pepper);
        $ip = substr((string) ($in['ip'] ?? '0'), 0, 64);
        if (!$this->rateHit('otp:' . $hmac, 3, 600) || !$this->rateHit('otp-ip:' . $ip, 8, 600)) {
            return $this->err('rate_limited', 429);
        }
        if ($this->otp instanceof Gn_Alex_Otp_Provider_IsolatedTestMock) {
            if (!$this->testHarness || $this->production || getenv(Gn_Alex_Otp_Provider_IsolatedTestMock::FLAG) !== '1') {
                return $this->err('isolated_mock_refused');
            }
        }
        if ($this->production && !$this->otp->isProductionVerified()) {
            return $this->err('otp_provider_required');
        }
        $otp = (string) random_int(100000, 999999);
        $sent = $this->otp->send($hmac, $otp, ['locale' => $locale, 'campaign' => $campaign['slug']]);
        $ack = (string) ($sent['ack_id'] ?? '');
        if (empty($sent['acknowledged']) || $ack === '' || ($this->production && !empty($sent['test_only']))) {
            $this->note('otp_provider_rejected');
            return $this->err('otp_provider_required');
        }
        $hash = hash_hmac('sha256', $otp . '|' . $hmac, $this->pepper);
        $id = $this->db->insert(
            "INSERT INTO {$this->t('otp')} (phone_hmac, campaign_id, otp_hash, expires_at, attempts, max_attempts, provider_id, provider_ack, status, locale, display_name, created_at)
             VALUES (?,?,?,?,0,5,?,?, 'pending', ?,?,?)",
            [$hmac, (int) $campaign['id'], $hash, $this->now() + self::OTP_TTL, $this->otp->id(), $ack, $locale, $name, $this->now()]
        );
        $this->note('otp_requested');
        return $this->ok([
            'challenge_id' => $id,
            'expires_in' => self::OTP_TTL,
            'phone_mask' => Gn_Alex_Phone::mask($phone),
            'provider' => $this->otp->id(),
            'provider_ack' => $ack,
        ]);
    }

    public function verifyOtp(array $in): array {
        if (!$this->checkPublicCsrf((string) ($in['csrf_session'] ?? ''), (string) ($in['csrf'] ?? ''))) {
            return $this->err('csrf_failed', 403);
        }
        $phone = Gn_Alex_Phone::canonicalize((string) ($in['phone'] ?? ''));
        $campaign = $this->campaignBySlug((string) ($in['campaign'] ?? ''));
        if ($phone === null || !$campaign) {
            return $this->err('phone_invalid');
        }
        $hmac = Gn_Alex_Phone::hmac($phone, $this->pepper);
        $row = $this->db->fetch(
            "SELECT * FROM {$this->t('otp')} WHERE phone_hmac=? AND campaign_id=? AND status='pending' ORDER BY id DESC LIMIT 1",
            [$hmac, (int) $campaign['id']]
        );
        if (!$row) {
            return $this->err('otp_missing');
        }
        if ((int) $row['expires_at'] < $this->now()) {
            $this->db->exec("UPDATE {$this->t('otp')} SET status='expired' WHERE id=?", [(int) $row['id']]);
            return $this->err('otp_expired');
        }
        $code = (string) ($in['code'] ?? '');
        $expect = hash_hmac('sha256', $code . '|' . $hmac, $this->pepper);
        if (!hash_equals((string) $row['otp_hash'], $expect)) {
            $attempts = (int) $row['attempts'] + 1;
            $status = $attempts >= (int) $row['max_attempts'] ? 'locked' : 'pending';
            $this->db->exec("UPDATE {$this->t('otp')} SET attempts=?, status=? WHERE id=?", [$attempts, $status, (int) $row['id']]);
            $this->flag((int) $campaign['id'], $hmac, 'otp_mismatch');
            return $this->err($status === 'locked' ? 'otp_locked' : 'otp_invalid');
        }
        $this->db->exec("UPDATE {$this->t('otp')} SET status='verified' WHERE id=?", [(int) $row['id']]);
        $identity = $this->ensureIdentity($hmac, Gn_Alex_Phone::mask($phone), (string) $row['display_name']);
        $this->ensureRegistration((int) $campaign['id'], (int) $identity['id'], (string) $row['locale']);
        $issued = $this->issueVoucher((int) $identity['id'], $campaign);
        $guest = $this->newGuestSession((int) $issued['voucher']['id']);
        $this->note('otp_verified');
        $out = [
            'identity_id' => (int) $identity['id'],
            'voucher_id' => (int) $issued['voucher']['id'],
            'created' => $issued['created'],
            'beer_quota' => (int) $issued['voucher']['beer_quota'],
            'beer_issued' => (int) $issued['voucher']['beer_issued'],
            'remaining' => (int) $issued['voucher']['beer_quota'] - (int) $issued['voucher']['beer_issued'],
            'guest_token' => $guest['token'],
            'guest_expires_in' => 1800,
            'phone_mask' => Gn_Alex_Phone::mask($phone),
            'display_name' => (string) $identity['display_name'],
        ];
        if ($issued['ticket_secret'] !== null) {
            $out['ticket_secret'] = $issued['ticket_secret'];
            $out['secret_disposition'] = 'show_once_not_a_url';
        } else {
            $out['secret_already_issued'] = true;
        }
        return $this->ok($out);
    }

    public function recordScan(string $slug, string $kind, ?string $partnerPublicId = null, array $extra = []): array {
        $campaign = $this->campaignBySlug($slug);
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        if ($kind !== 'registration' && $kind !== 'referral') {
            return $this->err('scan_kind');
        }
        if (isset($extra['phone']) || isset($extra['name']) || isset($extra['ticket']) || isset($extra['ticket_code'])) {
            $this->flag((int) $campaign['id'], null, 'pii_or_ticket_on_scan');
        }
        if ($kind === 'referral' && $partnerPublicId) {
            $partner = $this->db->fetch("SELECT id, status FROM {$this->t('partner')} WHERE public_id=?", [$partnerPublicId]);
            if (!$partner || $partner['status'] !== 'approved') {
                return $this->err('unknown_partner');
            }
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('scan')} (campaign_id, kind, partner_public_id, created_at) VALUES (?,?,?,?)",
            [(int) $campaign['id'], $kind, $partnerPublicId, $this->now()]
        );
        return $this->ok(['scan_id' => $id, 'redeems' => false]);
    }

    public function guestStatus(string $guestToken): array {
        $row = $this->guestByToken($guestToken, true);
        if (!$row) {
            return $this->err('unauthorized', 403);
        }
        $voucher = $this->db->fetch("SELECT * FROM {$this->t('voucher')} WHERE id=?", [(int) $row['voucher_id']]);
        $identity = $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE id=?", [(int) $voucher['identity_id']]);
        return $this->ok([
            'display_name' => (string) $identity['display_name'],
            'phone_mask' => (string) $identity['phone_mask'],
            'remaining' => (int) $voucher['beer_quota'] - (int) $voucher['beer_issued'],
            'salad_remaining' => (int) $voucher['salad_quota'] - (int) $voucher['salad_issued'],
            'guest_path' => $this->guestPath(),
        ]);
    }

    public function issuePresenceToken(string $guestToken): array {
        $guest = $this->guestByToken($guestToken, true);
        if (!$guest) {
            return $this->err('unauthorized', 403);
        }
        $jti = bin2hex(random_bytes(16));
        $exp = $this->now() + self::PRESENCE_TTL;
        $vid = (int) $guest['voucher_id'];
        $payload = $vid . '.' . $exp . '.' . $jti;
        $sig = hash_hmac('sha256', $payload, $this->tokenKey);
        $token = rtrim(strtr(base64_encode($payload . '.' . $sig), '+/', '-_'), '=');
        $this->db->insert(
            "INSERT INTO {$this->t('nonce')} (voucher_id, jti_hash, expires_at, consumed_at, created_at) VALUES (?,?,?,NULL,?)",
            [$vid, hash('sha256', $jti), $exp, $this->now()]
        );
        return $this->ok(['presence_token' => $token, 'expires_in' => self::PRESENCE_TTL, 'place_in_url' => false]);
    }

    public function resolvePresence(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $parsed = $this->parsePresence((string) ($req['presence_token'] ?? ''));
        if (!$parsed) {
            return $this->err('nonce_invalid');
        }
        $voucher = $this->db->fetch("SELECT * FROM {$this->t('voucher')} WHERE id=?", [(int) $parsed['voucher_id']]);
        $identity = $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE id=?", [(int) $voucher['identity_id']]);
        return $this->ok([
            'display_name' => (string) $identity['display_name'],
            'phone_mask' => (string) $identity['phone_mask'],
            'remaining' => (int) $voucher['beer_quota'] - (int) $voucher['beer_issued'],
            'redeems' => false,
        ]);
    }

    public function reissue(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !in_array($staff['role'], ['quay', 'quanly'], true) || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $voucherId = (int) ($req['voucher_id'] ?? 0);
        $this->db->begin();
        try {
            $voucher = $this->db->lockRow('voucher', $voucherId);
            if (!$voucher) {
                $this->db->rollback();
                return $this->err('not_found', 404);
            }
            $identity = $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE id=?", [(int) $voucher['identity_id']]);
            if (!$this->proofMatches($req, $identity)) {
                $this->db->rollback();
                $this->flag((int) $voucher['campaign_id'], (string) $identity['phone_hmac'], 'reissue_identity_mismatch');
                return $this->err('identity_mismatch');
            }
            $before = ['beer_quota' => (int) $voucher['beer_quota'], 'beer_issued' => (int) $voucher['beer_issued']];
            $secret = bin2hex(random_bytes(16));
            $this->db->exec(
                "UPDATE {$this->t('ticket')} SET secret_hash=?, created_at=? WHERE voucher_id=?",
                [hash('sha256', $secret . '|' . $this->pepper), $this->now(), $voucherId]
            );
            $this->db->exec(
                "UPDATE {$this->t('guest_session')} SET revoked_at=? WHERE voucher_id=? AND revoked_at IS NULL",
                [$this->now(), $voucherId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            throw $e;
        }
        $this->audit('staff:' . $staff['staff_id'], 'reissue', 'voucher:' . $voucherId, $before, $before);
        return $this->ok([
            'voucher_id' => $voucherId,
            'ticket_secret' => $secret,
            'secret_disposition' => 'show_once_not_a_url',
            'beer_quota' => $before['beer_quota'],
            'beer_issued' => $before['beer_issued'],
            'created' => false,
        ]);
    }

    public function redeem(array $req): array {
        $tokenRow = $this->sessionByToken((string) ($req['session_token'] ?? ''));
        if (!$tokenRow) {
            return $this->err('unauthorized', 403);
        }
        $idem = (string) ($req['idempotency_key'] ?? '');
        $existing = null;
        if (preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $idem)) {
            $existing = $this->db->fetch("SELECT * FROM {$this->t('redemption')} WHERE idempotency_key=?", [$idem]);
        }
        if ($existing) {
            if ((int) $existing['staff_id'] !== (int) $tokenRow['staff_id'] || !$this->csrfOk($tokenRow, $req)) {
                return $this->err('unauthorized', 403);
            }
            $stored = json_decode((string) $existing['result_json'], true) ?: [];
            $stored['replay'] = true;
            $stored['ok'] = true;
            $stored['http'] = 200;
            return $stored;
        }
        if ($this->hasConstantTicket($req)) {
            $this->flag(null, null, 'constant_ticket_rejected');
            return $this->err('constant_ticket_not_accepted');
        }
        if (!empty($req['qr_kind']) && $req['qr_kind'] === 'referral') {
            return $this->err('referral_qr_never_redeems');
        }
        if (!$tokenRow['active'] || !$this->csrfOk($tokenRow, $req)) {
            return $this->err('unauthorized', 403);
        }
        if (!in_array($tokenRow['role'], ['quay', 'thungan', 'quanly'], true)) {
            return $this->err('unauthorized', 403);
        }
        if (!$this->rateHit('redeem:' . $tokenRow['staff_id'], 60, 60)) {
            return $this->err('rate_limited', 429);
        }
        $item = (string) ($req['item'] ?? '');
        if ($item !== 'beer' && $item !== 'salad') {
            return $this->err('item_invalid');
        }
        $confirm = $req['confirm'] ?? null;
        $isConfirm = $confirm === 1 || $confirm === true || $confirm === '1';
        $isClaim = $confirm === 0 || $confirm === false || $confirm === '0';
        if (!$isConfirm && !$isClaim) {
            return $this->err('confirmation_required');
        }
        if (!preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $idem)) {
            return $this->err('idempotency_required');
        }
        $campaign = $this->campaignBySlug((string) ($req['campaign'] ?? ''));
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        if ($this->now() < (int) $campaign['starts_at']) {
            return $this->err('event_not_started');
        }
        if ($this->now() > (int) $campaign['ends_at']) {
            return $this->err('event_ended');
        }
        try {
            $this->db->begin();
            $this->expirePending();
            $phone = Gn_Alex_Phone::canonicalize((string) ($req['proof_phone'] ?? ''));
            if ($phone === null) {
                $this->db->rollback();
                return $this->err('identity_required');
            }
            $hmac = Gn_Alex_Phone::hmac($phone, $this->pepper);
            $identity = $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE phone_hmac=?", [$hmac]);
            $proofName = mb_strtolower(Gn_Alex_Text::sanitize((string) ($req['proof_name'] ?? '')));
            if (!$identity || $proofName === '' || $proofName !== mb_strtolower((string) $identity['display_name'])) {
                $this->db->rollback();
                $this->flag((int) $campaign['id'], $hmac, 'identity_mismatch');
                return $this->err('identity_mismatch');
            }
            $voucher = $this->db->fetch(
                "SELECT * FROM {$this->t('voucher')} WHERE identity_id=? AND campaign_family=?",
                [(int) $identity['id'], (string) $campaign['campaign_family']]
            );
            if (!$voucher || $voucher['status'] !== 'active') {
                $this->db->rollback();
                return $this->err('not_eligible');
            }
            $voucher = $this->db->lockRow('voucher', (int) $voucher['id']);
            $reg = $this->db->fetch(
                "SELECT r.id FROM {$this->t('registration')} r JOIN {$this->t('campaign')} c ON c.id=r.campaign_id
                 WHERE r.identity_id=? AND c.campaign_family=? AND r.status='eligible' LIMIT 1",
                [(int) $identity['id'], (string) $campaign['campaign_family']]
            );
            if (!$reg) {
                $this->db->rollback();
                return $this->err('not_eligible');
            }
            if (array_key_exists('version', $req) && $req['version'] !== '' && $req['version'] !== null && (int) $req['version'] !== (int) $voucher['version']) {
                $this->db->rollback();
                return $this->err('version_conflict', 409);
            }
            $nonceId = null;
            if (!empty($req['presence_token'])) {
                $parsed = $this->parsePresence((string) $req['presence_token']);
                if (!$parsed || (int) $parsed['voucher_id'] !== (int) $voucher['id']) {
                    $this->db->rollback();
                    return $this->err('nonce_invalid');
                }
                if (!empty($parsed['consumed_at'])) {
                    $this->db->rollback();
                    $this->flag((int) $campaign['id'], $hmac, 'nonce_replay');
                    return $this->err('nonce_replay');
                }
                $nonceId = (int) $parsed['id'];
            }
            $vid = (int) $voucher['id'];
            $hold = $this->db->fetch(
                "SELECT * FROM {$this->t('redemption')} WHERE voucher_id=? AND item_type=? AND status='pending' AND hold_key IS NOT NULL",
                [$vid, $item]
            );
            if ($hold && (string) $hold['idempotency_key'] !== $idem) {
                $this->db->rollback();
                return $this->err('pending_holds_quota', 409);
            }
            $count = (int) $this->db->fetch(
                "SELECT COUNT(*) AS c FROM {$this->t('redemption')} WHERE voucher_id=? AND item_type=? AND status='committed'",
                [$vid, $item]
            )['c'];
            $quota = $item === 'beer' ? (int) $voucher['beer_quota'] : (int) $voucher['salad_quota'];
            if ($isClaim) {
                if ($hold) {
                    $this->db->commit();
                    $stored = json_decode((string) $hold['result_json'], true) ?: [];
                    $stored['replay'] = true;
                    $stored['ok'] = true;
                    return $stored;
                }
                if ($count >= $quota) {
                    $this->db->rollback();
                    $this->audit('staff:' . $tokenRow['staff_id'], 'redeem_denied', 'voucher:' . $vid, [], ['item' => $item, 'reason' => 'quota']);
                    return $this->err('quota_exhausted', 409);
                }
                $result = [
                    'ok' => true,
                    'status' => 'pending',
                    'item' => $item,
                    'voucher_id' => $vid,
                    'remaining_after' => $quota - $count,
                    'replay' => false,
                ];
                $rid = $this->db->insert(
                    "INSERT INTO {$this->t('redemption')} (voucher_id, item_type, slot, status, commit_key, hold_key, idempotency_key, staff_id, session_id, confirmed, version_at, result_json, created_at, expires_at)
                     VALUES (?,?,0,'pending', NULL, ?,?,?,?,0,?, ?, ?, ?)",
                    [$vid, $item, 'hold:' . $vid . ':' . $item, $idem, (int) $tokenRow['staff_id'], (int) $tokenRow['session_id'], (int) $voucher['version'], json_encode($result), $this->now(), $this->now() + self::HOLD_TTL]
                );
                $result['redemption_id'] = $rid;
                $this->db->exec("UPDATE {$this->t('redemption')} SET result_json=? WHERE id=?", [json_encode($result), $rid]);
                $this->db->commit();
                return $result;
            }
            if ($count >= $quota) {
                $this->db->rollback();
                $this->audit('staff:' . $tokenRow['staff_id'], 'redeem_denied', 'voucher:' . $vid, [], ['item' => $item, 'reason' => 'quota']);
                return $this->err('quota_exhausted', 409);
            }
            $slot = $count + 1;
            $commitKey = 'v' . $vid . ':' . $item . ':' . $slot;
            $remainingAfter = $quota - $slot;
            $result = [
                'ok' => true,
                'status' => 'committed',
                'item' => $item,
                'slot' => $slot,
                'voucher_id' => $vid,
                'remaining_after' => $remainingAfter,
                'replay' => false,
                'http' => 200,
            ];
            if ($hold && (string) $hold['idempotency_key'] === $idem) {
                $this->db->exec(
                    "UPDATE {$this->t('redemption')} SET status='committed', slot=?, commit_key=?, hold_key=NULL, confirmed=1, result_json=?, expires_at=NULL WHERE id=?",
                    [$slot, $commitKey, json_encode($result), (int) $hold['id']]
                );
                $result['redemption_id'] = (int) $hold['id'];
                $this->db->exec("UPDATE {$this->t('redemption')} SET result_json=? WHERE id=?", [json_encode($result), (int) $hold['id']]);
            } else {
                $rid = $this->db->insert(
                    "INSERT INTO {$this->t('redemption')} (voucher_id, item_type, slot, status, commit_key, hold_key, idempotency_key, staff_id, session_id, confirmed, version_at, result_json, created_at, expires_at)
                     VALUES (?,?,?,?,?,NULL,?,?,?,1,?,?,?,NULL)",
                    [$vid, $item, $slot, 'committed', $commitKey, $idem, (int) $tokenRow['staff_id'], (int) $tokenRow['session_id'], (int) $voucher['version'], json_encode($result), $this->now()]
                );
                $result['redemption_id'] = $rid;
                $this->db->exec("UPDATE {$this->t('redemption')} SET result_json=? WHERE id=?", [json_encode($result), $rid]);
            }
            $col = $item === 'beer' ? 'beer_issued' : 'salad_issued';
            $changed = $this->db->exec(
                "UPDATE {$this->t('voucher')} SET {$col}=?, version=version+1 WHERE id=? AND version=?",
                [$count + 1, $vid, (int) $voucher['version']]
            );
            if ($changed !== 1) {
                $this->db->rollback();
                return $this->err('concurrent_conflict', 409);
            }
            $seen = $this->db->fetch("SELECT id FROM {$this->t('checkin')} WHERE voucher_id=?", [$vid]);
            if (!$seen) {
                $this->db->insert(
                    "INSERT INTO {$this->t('checkin')} (voucher_id, staff_id, created_at) VALUES (?,?,?)",
                    [$vid, (int) $tokenRow['staff_id'], $this->now()]
                );
            }
            if ($nonceId) {
                $this->db->exec("UPDATE {$this->t('nonce')} SET consumed_at=? WHERE id=? AND consumed_at IS NULL", [$this->now(), $nonceId]);
            }
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            $msg = $e->getMessage();
            if ($e->getCode() === '23000' || str_contains($msg, 'UNIQUE') || str_contains($msg, 'Deadlock') || str_contains($msg, 'Lock wait') || str_contains($msg, 'locked') || str_contains($msg, 'busy')) {
                return $this->err('concurrent_conflict', 409);
            }
            throw $e;
        }
    }

    public function history(array $req, int $voucherId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $voucher = $this->db->fetch("SELECT * FROM {$this->t('voucher')} WHERE id=?", [$voucherId]);
        if (!$voucher) {
            return $this->err('not_found', 404);
        }
        $rows = $this->db->fetchAll(
            "SELECT id, item_type, slot, status, created_at FROM {$this->t('redemption')} WHERE voucher_id=? AND status='committed' ORDER BY id ASC",
            [$voucherId]
        );
        $left = (int) $voucher['beer_quota'];
        $seq = [$left];
        foreach ($rows as $row) {
            if ($row['item_type'] === 'beer') {
                $left--;
                $seq[] = $left;
            }
        }
        return $this->ok(['history' => $rows, 'beer_sequence' => $seq, 'remaining' => (int) $voucher['beer_quota'] - (int) $voucher['beer_issued']]);
    }

    public function bootstrapQuanly(array $wpUser, string $pin, string $label = 'quanly'): array {
        if (empty($wpUser['manage_options'])) {
            return $this->err('unauthorized', 403);
        }
        $n = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('staff')}")['c'];
        if ($n > 0) {
            return $this->err('already_bootstrapped');
        }
        return $this->insertStaff('quanly', $pin, $label, 'wp:' . (int) ($wpUser['id'] ?? 0));
    }

    public function enrollStaffForIsolatedTest(string $role, string $pin, string $label): array {
        if (!$this->testHarness || $this->production || getenv(Gn_Alex_Otp_Provider_IsolatedTestMock::FLAG) !== '1') {
            return $this->err('isolated_test_enroll_refused');
        }
        return $this->insertStaff($role, $pin, $label, 'isolated-test');
    }

    public function enrollStaff(array $req, string $role, string $pin, string $label): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        return $this->insertStaff($role, $pin, $label, 'staff:' . $staff['staff_id']);
    }

    public function staffLogin(int $staffId, string $pin, string $ip): array {
        if (!$this->rateHit('login:' . $staffId . ':' . substr($ip, 0, 64), 5, 600)) {
            return $this->err('rate_limited', 429);
        }
        $row = $this->db->fetch("SELECT * FROM {$this->t('staff')} WHERE id=?", [$staffId]);
        $hash = $row['pin_hash'] ?? $this->dummyHash;
        $match = password_verify($pin, $hash);
        if (!$row || $row['status'] !== 'active' || !$match) {
            $this->audit('login', 'staff_login_failed', 'staff:' . $staffId, [], ['ip' => substr($ip, 0, 64)]);
            return $this->err('unauthorized', 403);
        }
        $token = $this->newStaffToken((int) $row['id']);
        $this->note('staff_login');
        return $this->ok($token);
    }

    public function staffRotate(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $this->db->exec("UPDATE {$this->t('staff_session')} SET revoked_at=? WHERE id=?", [$this->now(), (int) $staff['session_id']]);
        return $this->ok($this->newStaffToken((int) $staff['staff_id']));
    }

    public function staffRevoke(array $req, int $sessionId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $target = $this->db->fetch("SELECT * FROM {$this->t('staff_session')} WHERE id=?", [$sessionId]);
        if (!$target) {
            return $this->err('not_found', 404);
        }
        $own = (int) $target['staff_id'] === (int) $staff['staff_id'];
        if (!$own && $staff['role'] !== 'quanly') {
            return $this->err('unauthorized', 403);
        }
        $this->db->exec("UPDATE {$this->t('staff_session')} SET revoked_at=? WHERE id=? AND revoked_at IS NULL", [$this->now(), $sessionId]);
        $this->audit('staff:' . $staff['staff_id'], 'session_revoke', 'session:' . $sessionId, [], ['revoked' => true]);
        return $this->ok(['revoked' => true]);
    }

    public function updatePolicy(array $req, string $slug, int $beer, int $salad): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if ($beer < 1 || $beer > 10 || $salad < 0 || $salad > 1) {
            return $this->err('policy_invalid');
        }
        $campaign = $this->campaignBySlug($slug);
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        $before = ['beer_quota' => (int) $campaign['beer_quota'], 'salad_quota' => (int) $campaign['salad_quota'], 'policy_version' => (int) $campaign['policy_version']];
        $this->db->exec(
            "UPDATE {$this->t('campaign')} SET beer_quota=?, salad_quota=?, policy_version=policy_version+1 WHERE id=?",
            [$beer, $salad, (int) $campaign['id']]
        );
        $after = ['beer_quota' => $beer, 'salad_quota' => $salad, 'policy_version' => $before['policy_version'] + 1];
        $this->audit('staff:' . $staff['staff_id'], 'policy_edit', $slug, $before, $after);
        return $this->ok($after);
    }

    public function requestOverride(array $req, int $voucherId, int $delta, string $reason): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if ($delta !== 1) {
            return $this->err('override_delta');
        }
        $reason = Gn_Alex_Text::sanitize($reason, 160);
        if ($reason === '') {
            return $this->err('reason_required');
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('override')} (voucher_id, requester_id, approver_id, delta, status, reason, created_at, decided_at) VALUES (?,?,NULL,?,'pending',?,?,NULL)",
            [$voucherId, (int) $staff['staff_id'], $delta, $reason, $this->now()]
        );
        $this->audit('staff:' . $staff['staff_id'], 'override_request', 'override:' . $id, [], ['voucher_id' => $voucherId, 'delta' => $delta]);
        return $this->ok(['override_id' => $id, 'status' => 'pending']);
    }

    public function approveOverride(array $req, int $overrideId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $this->db->begin();
        try {
            $row = $this->db->fetch($this->db->lockSql('override'), [$overrideId]);
            if (!$row || $row['status'] !== 'pending') {
                $this->db->rollback();
                return $this->err('not_found', 404);
            }
            if ((int) $row['requester_id'] === (int) $staff['staff_id']) {
                $this->db->rollback();
                return $this->err('self_approval_denied', 403);
            }
            $voucher = $this->db->lockRow('voucher', (int) $row['voucher_id']);
            $changed = $this->db->exec(
                "UPDATE {$this->t('voucher')} SET beer_quota=beer_quota+?, version=version+1 WHERE id=? AND version=?",
                [(int) $row['delta'], (int) $voucher['id'], (int) $voucher['version']]
            );
            if ($changed !== 1) {
                $this->db->rollback();
                return $this->err('version_conflict', 409);
            }
            $this->db->exec(
                "UPDATE {$this->t('override')} SET status='approved', approver_id=?, decided_at=? WHERE id=?",
                [(int) $staff['staff_id'], $this->now(), $overrideId]
            );
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            throw $e;
        }
        $this->audit('staff:' . $staff['staff_id'], 'override_approve', 'override:' . $overrideId, [], ['approver' => (int) $staff['staff_id']]);
        return $this->ok(['status' => 'approved']);
    }

    public function applyPartner(array $in): array {
        if (!$this->checkPublicCsrf((string) ($in['csrf_session'] ?? ''), (string) ($in['csrf'] ?? ''))) {
            return $this->err('csrf_failed', 403);
        }
        if (empty($in['consent'])) {
            return $this->err('consent_required');
        }
        $role = (string) ($in['role'] ?? '');
        if ($role !== 'ctv' && $role !== 'driver') {
            return $this->err('role_invalid');
        }
        $phone = Gn_Alex_Phone::canonicalize((string) ($in['phone'] ?? ''));
        $name = Gn_Alex_Text::sanitize((string) ($in['name'] ?? ''), 80);
        if ($phone === null || $name === '') {
            return $this->err('phone_invalid');
        }
        $hmac = Gn_Alex_Phone::hmac($phone, $this->pepper);
        $identity = $this->ensureIdentity($hmac, Gn_Alex_Phone::mask($phone), $name);
        $public = $this->partnerPublicId($role, $hmac);
        $existing = $this->db->fetch(
            "SELECT * FROM {$this->t('partner')} WHERE identity_id=? AND role=? AND campaign_family=?",
            [(int) $identity['id'], $role, self::REFERRAL_FAMILY]
        );
        if ($existing) {
            return $this->ok(['partner_id' => (int) $existing['id'], 'public_id' => (string) $existing['public_id'], 'status' => (string) $existing['status'], 'created' => false]);
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('partner')} (public_id, identity_id, role, status, campaign_family, display_name, created_at) VALUES (?,?,?,'pending',?,?,?)",
            [$public, (int) $identity['id'], $role, self::REFERRAL_FAMILY, $name, $this->now()]
        );
        return $this->ok(['partner_id' => $id, 'public_id' => $public, 'status' => 'pending', 'created' => true, 'referral' => null]);
    }

    public function approvePartner(array $req, int $partnerId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE id=?", [$partnerId]);
        if (!$partner) {
            return $this->err('not_found', 404);
        }
        $this->db->exec("UPDATE {$this->t('partner')} SET status='approved' WHERE id=?", [$partnerId]);
        $link = $this->referralQr(self::ALEX_SLUG, (string) $partner['public_id']);
        $this->audit('staff:' . $staff['staff_id'], 'partner_approve', (string) $partner['public_id'], ['status' => $partner['status']], ['status' => 'approved']);
        return $this->ok(['public_id' => (string) $partner['public_id'], 'referral' => $link, 'status' => 'approved']);
    }

    public function recordInvoice(array $req, array $in): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !in_array($staff['role'], ['thungan', 'quanly'], true) || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if (($in['source'] ?? '') === 'webhook') {
            return $this->err('source_invalid');
        }
        return $this->insertInvoice('staff:' . $staff['staff_id'], $in);
    }

    public function markApprovedPaid(array $req, string $externalId, string $idem): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !in_array($staff['role'], ['thungan', 'quanly'], true) || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if (!preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $idem)) {
            return $this->err('idempotency_required');
        }
        $this->db->begin();
        try {
            $prior = $this->db->fetch("SELECT * FROM {$this->t('invoice_event')} WHERE idempotency_key=?", [$idem]);
            if ($prior) {
                $this->db->commit();
                return $this->ok(['replay' => true, 'invoice_id' => (int) $prior['invoice_id']]);
            }
            $invoice = $this->db->fetch($this->db->lockSql('invoice', 'external_id=?'), [$externalId]);
            if (!$invoice) {
                $this->db->rollback();
                return $this->err('not_found', 404);
            }
            $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE id=?", [(int) $invoice['partner_id']]);
            if (!$partner || $partner['status'] !== 'approved') {
                $this->db->rollback();
                return $this->err('partner_not_approved');
            }
            $this->db->insert(
                "INSERT INTO {$this->t('invoice_event')} (invoice_id, event_type, actor, idempotency_key, created_at) VALUES (?,?,?,?,?)",
                [(int) $invoice['id'], 'approved_paid', 'staff:' . $staff['staff_id'], $idem, $this->now()]
            );
            $bps = $this->rateBps((string) $partner['role']);
            $accrual = null;
            if ($bps !== null && (int) $invoice['link_verified'] === 1 && in_array($invoice['source'], ['manual', 'import'], true)) {
                $amount = intdiv(((int) $invoice['food_base']) * $bps, 10000);
                $ledgerKey = 'accrual:' . (int) $invoice['id'];
                $already = $this->db->fetch("SELECT id, amount_minor FROM {$this->t('ledger')} WHERE idempotency_key=?", [$ledgerKey]);
                if (!$already) {
                    $accrual = $this->db->insert(
                        "INSERT INTO {$this->t('ledger')} (invoice_id, partner_id, entry_type, rate_bps, base_minor, amount_minor, idempotency_key, created_at) VALUES (?,?, 'accrual', ?,?,?,?,?)",
                        [(int) $invoice['id'], (int) $partner['id'], $bps, (int) $invoice['food_base'], $amount, $ledgerKey, $this->now()]
                    );
                } else {
                    $accrual = (int) $already['id'];
                }
            }
            $this->db->commit();
            return $this->ok(['invoice_id' => (int) $invoice['id'], 'accrual_id' => $accrual, 'alex_commission' => null]);
        } catch (PDOException $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE')) {
                return $this->ok(['replay' => true]);
            }
            throw $e;
        }
    }

    public function reverseInvoice(array $req, string $externalId, string $kind, string $idem): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if ($kind !== 'cancelled' && $kind !== 'refunded') {
            return $this->err('kind_invalid');
        }
        if (!preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $idem)) {
            return $this->err('idempotency_required');
        }
        $this->db->begin();
        try {
            $prior = $this->db->fetch("SELECT * FROM {$this->t('invoice_event')} WHERE idempotency_key=?", [$idem]);
            if ($prior) {
                $this->db->commit();
                return $this->ok(['replay' => true, 'invoice_id' => (int) $prior['invoice_id']]);
            }
            $invoice = $this->db->fetch($this->db->lockSql('invoice', 'external_id=?'), [$externalId]);
            if (!$invoice) {
                $this->db->rollback();
                return $this->err('not_found', 404);
            }
            $this->db->insert(
                "INSERT INTO {$this->t('invoice_event')} (invoice_id, event_type, actor, idempotency_key, created_at) VALUES (?,?,?,?,?)",
                [(int) $invoice['id'], $kind, 'staff:' . $staff['staff_id'], $idem, $this->now()]
            );
            $accrual = $this->db->fetch(
                "SELECT * FROM {$this->t('ledger')} WHERE invoice_id=? AND entry_type='accrual'",
                [(int) $invoice['id']]
            );
            $reversalId = null;
            if ($accrual) {
                $revKey = 'reversal:' . (int) $invoice['id'];
                $existingRev = $this->db->fetch("SELECT * FROM {$this->t('ledger')} WHERE idempotency_key=?", [$revKey]);
                if ($existingRev) {
                    $this->db->rollback();
                    return $this->err('already_reversed', 409);
                }
                $reversalId = $this->db->insert(
                    "INSERT INTO {$this->t('ledger')} (invoice_id, partner_id, entry_type, rate_bps, base_minor, amount_minor, idempotency_key, created_at) VALUES (?,?, 'reversal', ?,?,?,?,?)",
                    [(int) $invoice['id'], (int) $accrual['partner_id'], (int) $accrual['rate_bps'], (int) $accrual['base_minor'], (int) $accrual['amount_minor'], $revKey, $this->now()]
                );
            }
            $this->db->commit();
            return $this->ok(['invoice_id' => (int) $invoice['id'], 'reversal_id' => $reversalId]);
        } catch (Throwable $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            throw $e;
        }
    }

    public function requestBank(array $req, int $partnerId, string $ciphertext): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if (!preg_match('/^enc:v1:[A-Za-z0-9+\/=_-]{16,180}$/', $ciphertext)) {
            return $this->err('plaintext_bank_refused');
        }
        $hash = 'sha256:' . hash('sha256', $ciphertext);
        $id = $this->db->insert(
            "INSERT INTO {$this->t('bank')} (partner_id, requester_id, approver_id, ciphertext, status, created_at, decided_at) VALUES (?,?,NULL,?,'pending',?,NULL)",
            [$partnerId, (int) $staff['staff_id'], $hash, $this->now()]
        );
        $this->audit('staff:' . $staff['staff_id'], 'bank_request', 'bank:' . $id, [], ['field' => 'ciphertext_placeholder', 'status' => 'pending']);
        return $this->ok(['bank_id' => $id, 'status' => 'pending']);
    }

    public function approveBank(array $req, int $bankId, string $ciphertext): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $row = $this->db->fetch("SELECT * FROM {$this->t('bank')} WHERE id=?", [$bankId]);
        if (!$row || $row['status'] !== 'pending') {
            return $this->err('not_found', 404);
        }
        if ((int) $row['requester_id'] === (int) $staff['staff_id']) {
            return $this->err('self_approval_denied', 403);
        }
        $expect = 'sha256:' . hash('sha256', $ciphertext);
        if (!hash_equals((string) $row['ciphertext'], $expect)) {
            return $this->err('plaintext_bank_refused');
        }
        $this->db->exec(
            "UPDATE {$this->t('bank')} SET ciphertext=?, approver_id=?, status='approved', decided_at=? WHERE id=?",
            [$ciphertext, (int) $staff['staff_id'], $this->now(), $bankId]
        );
        $this->audit('staff:' . $staff['staff_id'], 'bank_approve', 'bank:' . $bankId, [], ['field' => 'ciphertext_placeholder', 'status' => 'approved']);
        return $this->ok(['status' => 'approved']);
    }

    public function exportCsv(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $rows = $this->db->fetchAll(
            "SELECT p.public_id, p.role, p.status, i.phone_mask, p.display_name FROM {$this->t('partner')} p JOIN {$this->t('identity')} i ON i.id=p.identity_id ORDER BY p.id"
        );
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, ['public_id', 'role', 'status', 'phone_mask', 'display_name']);
        foreach ($rows as $row) {
            fputcsv($fh, [
                Gn_Alex_Text::csvCell((string) $row['public_id']),
                Gn_Alex_Text::csvCell((string) $row['role']),
                Gn_Alex_Text::csvCell((string) $row['status']),
                Gn_Alex_Text::csvCell((string) $row['phone_mask']),
                Gn_Alex_Text::csvCell((string) $row['display_name']),
            ]);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $this->ok(['csv' => $csv === false ? '' : $csv]);
    }

    public function posRead(): array {
        if ($this->posEndpoint === '' || $this->httpClient === null) {
            return [
                'ok' => false,
                'error' => 'no_supported_endpoint',
                'wrote' => false,
                'simulated' => false,
                'http' => 409,
            ];
        }
        try {
            $raw = $this->httpClient->get($this->posEndpoint);
        } catch (Throwable $e) {
            $last = $this->db->fetch("SELECT fetched_at FROM {$this->t('pos_snapshot')} WHERE ok=1 ORDER BY id DESC LIMIT 1");
            return [
                'ok' => false,
                'error' => 'pos_outage',
                'wrote' => false,
                'simulated' => false,
                'http' => 503,
                'fallback_snapshot_at' => $last['fetched_at'] ?? null,
            ];
        }
        $fetched = $this->now();
        $this->db->insert(
            "INSERT INTO {$this->t('pos_snapshot')} (fetched_at, endpoint, ok, raw_json, error) VALUES (?,?,1,?, '')",
            [$fetched, substr($this->posEndpoint, 0, 255), $raw]
        );
        return [
            'ok' => true,
            'wrote' => false,
            'simulated' => false,
            'http' => 200,
            'fetched_at' => $fetched,
            'mapped' => $this->mapPos($raw),
            'invoices_created' => 0,
        ];
    }

    public function posWrite(): array {
        return ['ok' => false, 'error' => 'pos_write_unsupported', 'wrote' => false, 'simulated' => false, 'http' => 405];
    }

    public function posReconcileCsv(): array {
        $last = $this->db->fetch("SELECT * FROM {$this->t('pos_snapshot')} ORDER BY id DESC LIMIT 1");
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, ['fetched_at', 'kind', 'amount', 'note']);
        if ($last) {
            $mapped = $this->mapPos((string) $last['raw_json']);
            if (!$mapped['lines']) {
                fputcsv($fh, [(string) $last['fetched_at'], 'raw', '', Gn_Alex_Text::csvCell('unmapped snapshot')]);
            }
            foreach ($mapped['lines'] as $line) {
                fputcsv($fh, [
                    (string) $last['fetched_at'],
                    Gn_Alex_Text::csvCell((string) $line['kind']),
                    (string) $line['amount'],
                    Gn_Alex_Text::csvCell((string) $line['name']),
                ]);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $this->ok(['csv' => $csv === false ? '' : $csv, 'fallback' => true, 'simulated' => false]);
    }

    public function ingestWebhook(string $key, array $payload): array {
        if (!preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $key)) {
            return $this->err('idempotency_required');
        }
        $existing = $this->db->fetch("SELECT result_json FROM {$this->t('webhook')} WHERE idempotency_key=?", [$key]);
        if ($existing) {
            $stored = json_decode((string) $existing['result_json'], true) ?: ['ok' => true];
            $stored['replay'] = true;
            return $stored;
        }
        $result = [
            'ok' => true,
            'accepted' => true,
            'link_verified' => false,
            'commission' => null,
            'revenue_counted' => false,
            'replay' => false,
            'http' => 200,
        ];
        if (!empty($payload['external_id'])) {
            $inv = $this->insertInvoice('webhook', [
                'external_id' => (string) $payload['external_id'],
                'source' => 'webhook',
                'food_base' => $payload['food_base'] ?? 0,
                'partner_public_id' => (string) ($payload['partner_public_id'] ?? ''),
                'campaign' => (string) ($payload['campaign'] ?? ''),
                'tax' => $payload['tax'] ?? 0,
                'drinks' => $payload['drinks'] ?? 0,
                'service_fee' => $payload['service_fee'] ?? 0,
                'discount' => $payload['discount'] ?? 0,
                'bill_total' => $payload['bill_total'] ?? null,
                'status' => $payload['status'] ?? null,
            ]);
            $result['invoice'] = ['ok' => $inv['ok'], 'error' => $inv['error'] ?? null, 'invoice_id' => $inv['invoice_id'] ?? null, 'replay' => $inv['replay'] ?? false];
        }
        $this->db->insert(
            "INSERT INTO {$this->t('webhook')} (idempotency_key, body_hash, result_json, created_at) VALUES (?,?,?,?)",
            [$key, hash('sha256', json_encode($this->redact($payload))), json_encode($result), $this->now()]
        );
        return $result;
    }

    public function publicMetrics(string $slug): array {
        $campaign = $this->campaignBySlug($slug);
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        $family = (string) $campaign['campaign_family'];
        $cid = (int) $campaign['id'];
        $scans = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('scan')} WHERE campaign_id=?", [$cid])['c'];
        $eligible = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('registration')} WHERE campaign_id=? AND status='eligible'", [$cid])['c'];
        $checkins = (int) $this->db->fetch(
            "SELECT COUNT(*) AS c FROM {$this->t('checkin')} k JOIN {$this->t('voucher')} v ON v.id=k.voucher_id WHERE v.campaign_family=?",
            [$family]
        )['c'];
        $beer = (int) $this->db->fetch(
            "SELECT COUNT(*) AS c FROM {$this->t('redemption')} d JOIN {$this->t('voucher')} v ON v.id=d.voucher_id WHERE v.campaign_family=? AND d.item_type='beer' AND d.status='committed'",
            [$family]
        )['c'];
        $salad = (int) $this->db->fetch(
            "SELECT COUNT(*) AS c FROM {$this->t('redemption')} d JOIN {$this->t('voucher')} v ON v.id=d.voucher_id WHERE v.campaign_family=? AND d.item_type='salad' AND d.status='committed'",
            [$family]
        )['c'];
        $flags = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('abuse')} WHERE campaign_id=?", [$cid])['c'];
        $pos = $this->linkedPos($cid);
        return $this->ok([
            'scans' => $scans,
            'eligible_registrations' => $eligible,
            'checkins' => $checkins,
            'beer_issued' => $beer,
            'salad' => $salad,
            'suspicious_flags' => $flags,
            'pos_invoice_count' => $pos['count'],
            'pos_revenue' => $pos['revenue'],
            'alex_commission' => null,
            'alex_commission_calculated' => false,
        ]);
    }

    public function listCustomers(array $req, string $slug): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('customer_list_not_public', 403);
        }
        $campaign = $this->campaignBySlug($slug);
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        $rows = $this->db->fetchAll(
            "SELECT i.display_name, i.phone_mask, v.beer_quota, v.beer_issued FROM {$this->t('registration')} r
             JOIN {$this->t('identity')} i ON i.id=r.identity_id
             JOIN {$this->t('voucher')} v ON v.identity_id=i.id AND v.campaign_family=?
             WHERE r.campaign_id=? AND r.status='eligible'",
            [(string) $campaign['campaign_family'], (int) $campaign['id']]
        );
        return $this->ok(['customers' => $rows]);
    }

    public function crmBoundary(): array {
        return $this->ok([
            'beautyos_present' => false,
            'jetpack_crm_in_repo' => true,
            'mode' => 'read_only_contract',
            'writes' => false,
            'second_calculator' => false,
            'legacy_calculators_invoked' => false,
            'contact_map' => [
                'external_ref' => 'phone_hmac_not_exported_publicly',
                'display_name' => 'string',
                'phone' => 'not_exported',
            ],
        ]);
    }

    public function alexCommission(): array {
        return $this->ok(['calculated' => false, 'amount' => null, 'reason' => 'alex_commission_never_calculated']);
    }

    private function insertInvoice(string $actor, array $in): array {
        $source = (string) ($in['source'] ?? '');
        if (!in_array($source, ['manual', 'import', 'webhook'], true)) {
            return $this->err('source_invalid');
        }
        $external = (string) ($in['external_id'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.:-]{4,80}$/', $external)) {
            return $this->err('external_id');
        }
        if (!isset($in['food_base']) || !is_numeric($in['food_base']) || (int) $in['food_base'] < 0) {
            return $this->err('food_base');
        }
        $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE public_id=?", [(string) ($in['partner_public_id'] ?? '')]);
        if (!$partner) {
            return $this->err('partner_required');
        }
        $campaignId = null;
        if (!empty($in['campaign'])) {
            $campaign = $this->campaignBySlug((string) $in['campaign']);
            if (!$campaign) {
                return $this->err('campaign_required');
            }
            $campaignId = (int) $campaign['id'];
        }
        $ignored = [];
        foreach (['bill_total', 'grand_total', 'revenue', 'total', 'status', 'amount'] as $key) {
            if (array_key_exists($key, $in) && $in[$key] !== null) {
                $ignored[] = $key;
            }
        }
        $link = ($source !== 'webhook' && !empty($in['link_verified'])) ? 1 : 0;
        $raw = json_encode(['ignored_keys' => $ignored, 'source' => $source, 'actor' => $actor]);
        $this->db->begin();
        try {
            $existing = $this->db->fetch($this->db->lockSql('invoice', 'external_id=?'), [$external]);
            if ($existing) {
                $this->db->commit();
                return $this->ok(['invoice_id' => (int) $existing['id'], 'replay' => true, 'created' => false]);
            }
            $id = $this->db->insert(
                "INSERT INTO {$this->t('invoice')} (external_id, partner_id, campaign_id, source, food_base, tax, drinks, service_fee, discount, link_verified, raw_json, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $external, (int) $partner['id'], $campaignId, $source, (int) $in['food_base'],
                    (int) ($in['tax'] ?? 0), (int) ($in['drinks'] ?? 0), (int) ($in['service_fee'] ?? 0), (int) ($in['discount'] ?? 0),
                    $link, $raw, $this->now(),
                ]
            );
            $this->db->insert(
                "INSERT INTO {$this->t('invoice_event')} (invoice_id, event_type, actor, idempotency_key, created_at) VALUES (?,?,?,?,?)",
                [$id, 'received', $actor, 'recv:' . $external, $this->now()]
            );
            $this->db->commit();
            return $this->ok(['invoice_id' => $id, 'replay' => false, 'created' => true, 'link_verified' => $link]);
        } catch (PDOException $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE')) {
                $existing = $this->db->fetch("SELECT id FROM {$this->t('invoice')} WHERE external_id=?", [$external]);
                return $this->ok(['invoice_id' => (int) ($existing['id'] ?? 0), 'replay' => true, 'created' => false]);
            }
            throw $e;
        }
    }

    private function linkedPos(int $campaignId): array {
        $rows = $this->db->fetchAll(
            "SELECT * FROM {$this->t('invoice')} WHERE campaign_id=? AND link_verified=1 AND source IN ('manual','import')",
            [$campaignId]
        );
        $count = 0;
        $revenue = 0;
        foreach ($rows as $row) {
            $events = $this->db->fetchAll(
                "SELECT event_type FROM {$this->t('invoice_event')} WHERE invoice_id=? ORDER BY id ASC",
                [(int) $row['id']]
            );
            $paid = false;
            $dead = false;
            foreach ($events as $event) {
                if ($event['event_type'] === 'approved_paid') {
                    $paid = true;
                    $dead = false;
                }
                if ($event['event_type'] === 'cancelled' || $event['event_type'] === 'refunded') {
                    $dead = true;
                }
            }
            if ($paid && !$dead) {
                $count++;
                $revenue += (int) $row['food_base'];
            }
        }
        return ['count' => $count, 'revenue' => $revenue];
    }

    /** @return array{recognized:bool,lines:list<array{kind:string,amount:int,name:string}>} */
    private function mapPos(string $raw): array {
        $json = json_decode($raw, true);
        $lines = [];
        if (is_array($json)) {
            $items = $json['items'] ?? $json;
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (($item['kind'] ?? '') === 'food' && isset($item['amount']) && is_numeric($item['amount'])) {
                        $lines[] = [
                            'kind' => 'food',
                            'amount' => (int) $item['amount'],
                            'name' => Gn_Alex_Text::sanitize((string) ($item['name'] ?? ''), 80),
                        ];
                    }
                }
            }
        }
        return ['recognized' => $lines !== [], 'lines' => $lines];
    }

    private function rateBps(string $role): ?int {
        if ($role === 'ctv') {
            return 500;
        }
        if ($role === 'driver') {
            return 1500;
        }
        return null;
    }

    private function partnerPublicId(string $role, string $hmac): string {
        return 'GNP' . strtoupper(substr(hash('sha256', $role . '|' . $hmac . '|' . self::REFERRAL_FAMILY), 0, 10));
    }

    private function insertStaff(string $role, string $pin, string $label, string $actor): array {
        if (!in_array($role, ['quay', 'thungan', 'quanly'], true)) {
            return $this->err('role_invalid');
        }
        if ($reason = $this->pinError($pin)) {
            return $this->err($reason);
        }
        $label = Gn_Alex_Text::sanitize($label, 40);
        if ($label === '') {
            $label = $role;
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('staff')} (role, pin_hash, label, status, created_at) VALUES (?,?,?,'active',?)",
            [$role, password_hash($pin, PASSWORD_DEFAULT), $label, $this->now()]
        );
        $this->audit($actor, 'staff_enroll', 'staff:' . $id, [], ['role' => $role, 'label' => $label]);
        return $this->ok(['staff_id' => $id, 'role' => $role]);
    }

    private function pinError(string $pin): ?string {
        if (!preg_match('/^[0-9]{8,12}$/', $pin)) {
            return 'pin_policy';
        }
        if (preg_match('/^(\d)\1+$/', $pin)) {
            return 'pin_policy';
        }
        $bad = ['12345678', '123456789', '01234567', '87654321', '00000000', '11111111', '0123456789', '1234567890'];
        if (in_array($pin, $bad, true)) {
            return 'pin_policy';
        }
        return null;
    }

    /** @return array{token:string,csrf:string,expires_in:int,session_id:int,role?:string} */
    private function newStaffToken(int $staffId): array {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $sid = $this->db->insert(
            "INSERT INTO {$this->t('staff_session')} (staff_id, token_hash, expires_at, revoked_at, created_at) VALUES (?,?,?,NULL,?)",
            [$staffId, hash('sha256', $token), $this->now() + self::SESSION_TTL, $this->now()]
        );
        $staff = $this->db->fetch("SELECT role FROM {$this->t('staff')} WHERE id=?", [$staffId]);
        return [
            'token' => $token,
            'csrf' => hash_hmac('sha256', $sid . '|' . $staffId, $this->tokenKey),
            'expires_in' => self::SESSION_TTL,
            'session_id' => $sid,
            'role' => (string) ($staff['role'] ?? ''),
        ];
    }

    /** @return array<string,mixed>|null */
    private function sessionByToken(string $token): ?array {
        if ($token === '') {
            return null;
        }
        $row = $this->db->fetch(
            "SELECT s.id AS session_id, s.staff_id, s.expires_at, s.revoked_at, f.role, f.status
             FROM {$this->t('staff_session')} s JOIN {$this->t('staff')} f ON f.id=s.staff_id
             WHERE s.token_hash=?",
            [hash('sha256', $token)]
        );
        if (!$row || $row['status'] !== 'active') {
            return null;
        }
        $row['active'] = empty($row['revoked_at']) && (int) $row['expires_at'] >= $this->now();
        return $row;
    }

    /** @param array<string,mixed> $req */
    private function activeStaff(array $req): ?array {
        $row = $this->sessionByToken((string) ($req['session_token'] ?? ''));
        if (!$row || !$row['active']) {
            return null;
        }
        return $row;
    }

    /** @param array<string,mixed> $staff @param array<string,mixed> $req */
    private function csrfOk(array $staff, array $req): bool {
        $expect = hash_hmac('sha256', $staff['session_id'] . '|' . $staff['staff_id'], $this->tokenKey);
        $given = (string) ($req['csrf'] ?? '');
        return $given !== '' && hash_equals($expect, $given);
    }

    private function checkPublicCsrf(string $sid, string $token): bool {
        if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $sid) || $token === '') {
            return false;
        }
        return hash_equals(hash_hmac('sha256', 'pub|' . $sid, $this->tokenKey), $token);
    }

    /** @param array<string,mixed> $req */
    private function hasConstantTicket(array $req): bool {
        foreach (['ticket', 'ticket_code', 'voucher_code', 'constant_code', 'ticket_secret', 'ticket_url'] as $key) {
            if (!empty($req[$key])) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string,mixed>|null $identity @param array<string,mixed> $req */
    private function proofMatches(array $req, ?array $identity): bool {
        if (!$identity) {
            return false;
        }
        $phone = Gn_Alex_Phone::canonicalize((string) ($req['proof_phone'] ?? ''));
        if ($phone === null) {
            return false;
        }
        $name = mb_strtolower(Gn_Alex_Text::sanitize((string) ($req['proof_name'] ?? '')));
        return Gn_Alex_Phone::hmac($phone, $this->pepper) === (string) $identity['phone_hmac']
            && $name !== ''
            && $name === mb_strtolower((string) $identity['display_name']);
    }

    private function ensureIdentity(string $hmac, string $mask, string $name): array {
        $found = $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE phone_hmac=?", [$hmac]);
        if ($found) {
            return $found;
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('identity')} (phone_hmac, phone_mask, display_name, created_at) VALUES (?,?,?,?)",
            [$hmac, $mask, $name, $this->now()]
        );
        return $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE id=?", [$id]);
    }

    private function ensureRegistration(int $campaignId, int $identityId, string $locale): void {
        $found = $this->db->fetch(
            "SELECT id FROM {$this->t('registration')} WHERE campaign_id=? AND identity_id=?",
            [$campaignId, $identityId]
        );
        if ($found) {
            return;
        }
        $this->db->insert(
            "INSERT INTO {$this->t('registration')} (campaign_id, identity_id, locale, consent_at, status, created_at) VALUES (?,?,?,?,'eligible',?)",
            [$campaignId, $identityId, $locale, $this->now(), $this->now()]
        );
    }

    /** @param array<string,mixed> $campaign @return array{voucher:array<string,mixed>,created:bool,ticket_secret:?string} */
    private function issueVoucher(int $identityId, array $campaign): array {
        $existing = $this->db->fetch(
            "SELECT * FROM {$this->t('voucher')} WHERE identity_id=? AND campaign_family=?",
            [$identityId, (string) $campaign['campaign_family']]
        );
        if ($existing) {
            return ['voucher' => $existing, 'created' => false, 'ticket_secret' => null];
        }
        $id = $this->db->insert(
            "INSERT INTO {$this->t('voucher')} (identity_id, campaign_id, campaign_family, beer_quota, beer_issued, salad_quota, salad_issued, version, status, created_at)
             VALUES (?,?,?,?,0,?,0,1,'active',?)",
            [$identityId, (int) $campaign['id'], (string) $campaign['campaign_family'], (int) $campaign['beer_quota'], (int) $campaign['salad_quota'], $this->now()]
        );
        $secret = bin2hex(random_bytes(16));
        $this->db->insert(
            "INSERT INTO {$this->t('ticket')} (voucher_id, secret_hash, created_at) VALUES (?,?,?)",
            [$id, hash('sha256', $secret . '|' . $this->pepper), $this->now()]
        );
        $voucher = $this->db->fetch("SELECT * FROM {$this->t('voucher')} WHERE id=?", [$id]);
        return ['voucher' => $voucher, 'created' => true, 'ticket_secret' => $secret];
    }

    /** @return array{token:string} */
    private function newGuestSession(int $voucherId): array {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->db->insert(
            "INSERT INTO {$this->t('guest_session')} (voucher_id, token_hash, expires_at, revoked_at, created_at) VALUES (?,?,?,NULL,?)",
            [$voucherId, hash('sha256', $token), $this->now() + 1800, $this->now()]
        );
        return ['token' => $token];
    }

    /** @return array<string,mixed>|null */
    private function guestByToken(string $token, bool $activeOnly): ?array {
        if ($token === '') {
            return null;
        }
        $row = $this->db->fetch("SELECT * FROM {$this->t('guest_session')} WHERE token_hash=?", [hash('sha256', $token)]);
        if (!$row) {
            return null;
        }
        if ($activeOnly && (!empty($row['revoked_at']) || (int) $row['expires_at'] < $this->now())) {
            return null;
        }
        return $row;
    }

    /** @return array<string,mixed>|null */
    private function parsePresence(string $token): ?array {
        $pad = strlen($token) % 4;
        if ($pad !== 0) {
            $token .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false || !str_contains($raw, '.')) {
            return null;
        }
        $parts = explode('.', $raw);
        if (count($parts) !== 4) {
            return null;
        }
        [$vid, $exp, $jti, $sig] = $parts;
        $payload = $vid . '.' . $exp . '.' . $jti;
        $expect = hash_hmac('sha256', $payload, $this->tokenKey);
        if (!hash_equals($expect, $sig)) {
            return null;
        }
        if ((int) $exp < $this->now()) {
            return null;
        }
        $row = $this->db->fetch("SELECT * FROM {$this->t('nonce')} WHERE jti_hash=?", [hash('sha256', $jti)]);
        if (!$row || (int) $row['voucher_id'] !== (int) $vid) {
            return null;
        }
        return $row;
    }

    private function expirePending(): void {
        $this->db->exec(
            "UPDATE {$this->t('redemption')} SET status='expired', hold_key=NULL WHERE status='pending' AND expires_at IS NOT NULL AND expires_at <= ?",
            [$this->now()]
        );
    }

    private function flag(?int $campaignId, ?string $hmac, string $code): void {
        $this->db->insert(
            "INSERT INTO {$this->t('abuse')} (campaign_id, phone_hmac, code, created_at) VALUES (?,?,?,?)",
            [$campaignId, $hmac, $code, $this->now()]
        );
    }

    private function rateHit(string $bucket, int $limit, int $window): bool {
        $now = $this->now();
        $row = $this->db->fetch("SELECT n, reset_at FROM {$this->t('rate')} WHERE bucket=?", [$bucket]);
        if (!$row || (int) $row['reset_at'] <= $now) {
            if ($row) {
                $this->db->exec("UPDATE {$this->t('rate')} SET n=1, reset_at=? WHERE bucket=?", [$now + $window, $bucket]);
            } else {
                $this->db->insert("INSERT INTO {$this->t('rate')} (bucket, n, reset_at) VALUES (?,?,?)", [$bucket, 1, $now + $window]);
            }
            return true;
        }
        if ((int) $row['n'] >= $limit) {
            return false;
        }
        $this->db->exec("UPDATE {$this->t('rate')} SET n=n+1 WHERE bucket=?", [$bucket]);
        return true;
    }

    /** @param array<string,mixed> $before @param array<string,mixed> $after */
    private function audit(string $actor, string $action, string $subject, array $before, array $after): void {
        $this->db->insert(
            "INSERT INTO {$this->t('audit')} (actor, action, subject, before_json, after_json, created_at) VALUES (?,?,?,?,?,?)",
            [$actor, $action, $subject, json_encode($this->redact($before)), json_encode($this->redact($after)), $this->now()]
        );
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function redact(array $data): array {
        $blocked = ['otp', 'pin', 'password', 'ciphertext', 'ticket_secret', 'pepper', 'secret', 'account', 'pan', 'cvv', 'bank'];
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                $out[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $out[$key] = $this->redact($value);
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    private function note(string $event): void {
        $this->sink[] = $event;
    }

    private function t(string $name): string {
        return $this->db->table($name);
    }

    /** @return array<string,mixed>|null */
    private function campaignBySlug(string $slug): ?array {
        return $this->db->fetch("SELECT * FROM {$this->t('campaign')} WHERE slug=?", [$slug]);
    }

    /** @return array<string,mixed> */
    private function campaignById(int $id): array {
        $row = $this->db->fetch("SELECT * FROM {$this->t('campaign')} WHERE id=?", [$id]);
        if (!$row) {
            throw new RuntimeException('campaign missing');
        }
        return $row;
    }

    /** @param array<string,mixed> $data */
    private function ok(array $data, int $http = 200): array {
        return array_merge(['ok' => true, 'http' => $http], $data);
    }

    /** @param array<string,mixed> $extra */
    private function err(string $code, int $http = 422, array $extra = []): array {
        return array_merge(['ok' => false, 'error' => $code, 'http' => $http], $extra);
    }
}
