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
    public const REFERRAL_SLUG = 'referral-food';

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
        private string $posEndpoint = '',
        private string $webhookSecret = '',
        private string $tenant = 'ganhngon'
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
        if ($slug !== self::REFERRAL_SLUG || !preg_match('/^[A-Z0-9]{6,20}$/', $publicId)) {
            return '';
        }
        return '/gn-alex/referral/' . rawurlencode($publicId);
    }

    public function handoffPath(string $publicId): string {
        if (!preg_match('/^[A-Z0-9]{6,20}$/', $publicId)) {
            return '';
        }
        return '/dat-ban/?gn_ref=' . rawurlencode($publicId);
    }

    public function guestPath(): string {
        return '/gn-alex/khach';
    }

    public function counterPath(): string {
        return '/gn-alex/quay';
    }

    public function tokenUrl(string $token): array {
        return $this->err('token_not_placeable_in_url');
    }

    public function issueRegistrationToken(string $purpose): array {
        if (!in_array($purpose, ['otp_request', 'otp_verify', 'partner_apply'], true)) {
            return $this->err('csrf_purpose');
        }
        $token = bin2hex(random_bytes(16));
        $this->db->insert(
            "INSERT INTO {$this->t('reg_token')} (token_hash, purpose, expires_at, used_at, created_at) VALUES (?,?,?,NULL,?)",
            [hash('sha256', $token), $purpose, $this->now() + 600, $this->now()]
        );
        return $this->ok(['csrf' => $token, 'expires_in' => 600, 'single_use' => true]);
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

    public function ensureReferralCampaign(): array {
        $found = $this->campaignBySlug(self::REFERRAL_SLUG);
        if ($found) {
            return $found;
        }
        $start = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('Asia/Bangkok'));
        $end = new DateTimeImmutable('2027-01-01 00:00:00', new DateTimeZone('Asia/Bangkok'));
        $id = $this->db->insert(
            "INSERT INTO {$this->t('campaign')} (slug, campaign_family, partner_code, starts_at, ends_at, tz, beer_quota, salad_quota, policy_version, title_en, title_ru, conditions_en, conditions_ru, status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                self::REFERRAL_SLUG, self::REFERRAL_FAMILY, 'referral', $start->getTimestamp(), $end->getTimestamp(),
                'Asia/Bangkok', 0, 0, 1, 'Referral food', 'Referral food',
                'Table request handoff. Not a discount and not an event pass.',
                'Table request handoff. Not a discount and not an event pass.',
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
        if (!$this->consumeRegistrationToken((string) ($in['csrf'] ?? ''), 'otp_request')) {
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
        if (!$this->rateHit('otp-ip:' . $ip, 8, 600)) {
            return $this->err('rate_limited', 429);
        }
        $reserved = $this->reserveIdentityOtp($hmac);
        if (!$reserved['ok']) {
            return $reserved;
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
        $verify = $this->issueRegistrationToken('otp_verify');
        return $this->ok([
            'challenge_id' => $id,
            'expires_in' => self::OTP_TTL,
            'phone_mask' => Gn_Alex_Phone::mask($phone),
            'provider' => $this->otp->id(),
            'provider_ack' => $ack,
            'verify_csrf' => $verify['csrf'] ?? '',
        ]);
    }

    public function verifyOtp(array $in): array {
        if (!$this->consumeRegistrationToken((string) ($in['csrf'] ?? ''), 'otp_verify')) {
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
            $extra = [];
            if ($status !== 'locked') {
                $fresh = $this->issueRegistrationToken('otp_verify');
                $extra['verify_csrf'] = $fresh['csrf'] ?? '';
            }
            return $this->err($status === 'locked' ? 'otp_locked' : 'otp_invalid', 422, $extra);
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
            $partner = $this->db->fetch("SELECT id, attribution FROM {$this->t('partner')} WHERE public_id=?", [$partnerPublicId]);
            if (!$partner || ($partner['attribution'] ?? '') !== 'granted') {
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

    public function lookupGuest(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        if ($this->hasConstantTicket($req)) {
            $this->flag(null, null, 'screenshot_rejected');
            return $this->err('constant_ticket_not_accepted');
        }
        $campaign = $this->campaignBySlug((string) ($req['campaign'] ?? ''));
        if (!$campaign) {
            return $this->err('campaign_required');
        }
        $phone = Gn_Alex_Phone::canonicalize((string) ($req['proof_phone'] ?? ''));
        $name = mb_strtolower(Gn_Alex_Text::sanitize((string) ($req['proof_name'] ?? '')));
        if ($phone === null || $name === '') {
            return $this->err('identity_required');
        }
        $hmac = Gn_Alex_Phone::hmac($phone, $this->pepper);
        $identity = $this->db->fetch("SELECT * FROM {$this->t('identity')} WHERE phone_hmac=?", [$hmac]);
        if (!$identity || $name !== mb_strtolower((string) $identity['display_name'])) {
            $this->flag((int) $campaign['id'], $hmac, 'identity_mismatch');
            return $this->err('identity_mismatch');
        }
        $voucher = $this->db->fetch(
            "SELECT * FROM {$this->t('voucher')} WHERE identity_id=? AND campaign_family=?",
            [(int) $identity['id'], (string) $campaign['campaign_family']]
        );
        if (!$voucher) {
            return $this->err('not_eligible');
        }
        $before = (int) $voucher['beer_issued'];
        return $this->ok([
            'display_name' => (string) $identity['display_name'],
            'phone_mask' => (string) $identity['phone_mask'],
            'remaining' => (int) $voucher['beer_quota'] - $before,
            'salad_remaining' => (int) $voucher['salad_quota'] - (int) $voucher['salad_issued'],
            'voucher_id' => (int) $voucher['id'],
            'beer_issued' => $before,
            'redeems' => false,
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

    public function enrollFromWpAdmin(array $wpUser, string $role, string $pin, string $label): array {
        if (empty($wpUser['manage_options'])) {
            return $this->err('unauthorized', 403);
        }
        $count = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('staff')}")['c'];
        if ($count === 0) {
            return $this->err('bootstrap_required');
        }
        return $this->insertStaff($role, $pin, $label, 'wp:' . (int) ($wpUser['id'] ?? 0));
    }

    public function partnerQueue(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !in_array($staff['role'], ['quanly', 'onboarding'], true) || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $rows = $this->db->fetchAll(
            "SELECT p.id, p.public_id, p.role, p.status, p.attribution, i.phone_mask, p.display_name
             FROM {$this->t('partner')} p JOIN {$this->t('identity')} i ON i.id=p.identity_id ORDER BY p.id"
        );
        return $this->ok(['applications' => $rows, 'payout_enabled' => false]);
    }

    public function staffCount(): int {
        return (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('staff')}")['c'];
    }

    public function webhookConfigured(): bool {
        return strlen($this->webhookSecret) >= 16;
    }

    public function providerStatus(): array {
        return $this->ok([
            'provider' => $this->otp->id(),
            'production_verified' => $this->otp->isProductionVerified(),
            'webhook_configured' => $this->webhookConfigured(),
            'payout_enabled' => false,
            'automatic_commission' => false,
        ]);
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
        if (!$this->consumeRegistrationToken((string) ($in['csrf'] ?? ''), 'partner_apply')) {
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
            "INSERT INTO {$this->t('partner')} (public_id, identity_id, role, status, attribution, reviewer_id, campaign_family, display_name, created_at) VALUES (?,?,?,'pending','none',NULL,?,?,?)",
            [$public, (int) $identity['id'], $role, self::REFERRAL_FAMILY, $name, $this->now()]
        );
        return $this->ok([
            'partner_id' => $id,
            'public_id' => $public,
            'status' => 'pending',
            'attribution' => 'none',
            'created' => true,
            'referral' => null,
            'reason' => 'requires_approval',
        ]);
    }

    public function approvePartner(array $req, int $partnerId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE id=?", [$partnerId]);
        if (!$partner || $partner['status'] !== 'pending') {
            return $this->err('not_found', 404);
        }
        $this->db->exec(
            "UPDATE {$this->t('partner')} SET status='reviewed', reviewer_id=?, attribution='none' WHERE id=?",
            [(int) $staff['staff_id'], $partnerId]
        );
        $this->audit('staff:' . $staff['staff_id'], 'partner_review', (string) $partner['public_id'], ['status' => 'pending'], ['status' => 'reviewed', 'referral' => null]);
        return $this->ok([
            'public_id' => (string) $partner['public_id'],
            'referral' => null,
            'status' => 'reviewed',
            'attribution' => 'none',
            'reason' => 'requires_approval',
        ]);
    }

    public function grantAttribution(array $req, int $partnerId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'onboarding' || !$this->csrfOk($staff, $req)) {
            return $this->err('distinct_role_required', 403);
        }
        $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE id=?", [$partnerId]);
        if (!$partner || $partner['status'] !== 'reviewed' || empty($partner['reviewer_id'])) {
            return $this->err('review_required');
        }
        if ((int) $partner['reviewer_id'] === (int) $staff['staff_id']) {
            return $this->err('self_approval_denied', 403);
        }
        $campaign = $this->ensureReferralCampaign();
        if (($campaign['slug'] ?? '') !== self::REFERRAL_SLUG || (int) ($campaign['beer_quota'] ?? 1) !== 0) {
            return $this->err('campaign_not_referral');
        }
        $link = $this->referralQr(self::REFERRAL_SLUG, (string) $partner['public_id']);
        if ($link === '' || str_contains($link, self::ALEX_SLUG)) {
            return $this->err('campaign_not_referral');
        }
        $this->db->exec("UPDATE {$this->t('partner')} SET status='attributable', attribution='granted' WHERE id=?", [$partnerId]);
        $this->audit('staff:' . $staff['staff_id'], 'attribution_grant', (string) $partner['public_id'], ['attribution' => 'none'], ['attribution' => 'granted', 'campaign' => self::REFERRAL_SLUG]);
        return $this->ok([
            'public_id' => (string) $partner['public_id'],
            'role' => (string) $partner['role'],
            'referral' => $link,
            'handoff' => $this->handoffPath((string) $partner['public_id']),
            'status' => 'attributable',
            'attribution' => 'granted',
            'campaign' => self::REFERRAL_SLUG,
        ]);
    }

    public function lookupPartnerPublic(string $code): array {
        $code = strtoupper($code);
        if (!preg_match('/^[A-Z0-9]{6,20}$/', $code)) {
            return $this->err('not_found', 404);
        }
        $partner = $this->db->fetch("SELECT public_id, role, status, attribution FROM {$this->t('partner')} WHERE public_id=?", [$code]);
        if (!$partner) {
            return $this->err('not_found', 404);
        }
        $granted = $partner['status'] === 'attributable' && $partner['attribution'] === 'granted';
        return $this->ok([
            'public_id' => (string) $partner['public_id'],
            'role' => (string) $partner['role'],
            'status' => (string) $partner['status'],
            'attribution' => $granted ? 'granted' : 'none',
            'referral' => $granted ? $this->referralQr(self::REFERRAL_SLUG, $code) : null,
            'handoff' => $granted ? $this->handoffPath($code) : null,
            'needs_approval' => !$granted,
        ]);
    }

    public function noteReferralVisit(string $code): array {
        $look = $this->lookupPartnerPublic($code);
        if (!$look['ok']) {
            return $look;
        }
        if (!empty($look['needs_approval'])) {
            return $this->ok(['public_id' => $look['public_id'], 'created' => false, 'click' => 'needs_approval', 'needs_approval' => true]);
        }
        $partner = $this->db->fetch("SELECT id FROM {$this->t('partner')} WHERE public_id=?", [(string) $look['public_id']]);
        $existing = $this->db->fetch("SELECT id, created_at FROM {$this->t('referral_click')} WHERE partner_id=?", [(int) $partner['id']]);
        if ($existing) {
            return $this->ok(['public_id' => $look['public_id'], 'created' => false, 'click' => 'ignored', 'needs_approval' => false]);
        }
        $this->db->insert(
            "INSERT INTO {$this->t('referral_click')} (partner_id, public_id, campaign_family, created_at) VALUES (?,?,?,?)",
            [(int) $partner['id'], (string) $look['public_id'], self::REFERRAL_FAMILY, $this->now()]
        );
        $this->audit('public', 'referral_click', (string) $look['public_id'], [], ['source' => 'provisional', 'campaign' => self::REFERRAL_SLUG]);
        return $this->ok(['public_id' => $look['public_id'], 'created' => true, 'click' => 'provisional', 'needs_approval' => false]);
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
        $actor = 'staff:' . $staff['staff_id'];
        $fingerprint = $this->eventFingerprint('approved_paid', $externalId, $actor, $idem);
        $this->db->begin();
        try {
            $prior = $this->db->fetch("SELECT * FROM {$this->t('invoice_event')} WHERE idempotency_key=?", [$idem]);
            if ($prior) {
                $decision = $this->eventDecision($prior, $fingerprint, $externalId, 'approved_paid');
                $this->db->rollback();
                return $decision;
            }
            $invoice = $this->db->fetch($this->db->lockSql('invoice', 'external_id=?'), [$externalId]);
            if (!$invoice) {
                $this->db->rollback();
                return $this->err('not_found', 404);
            }
            $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE id=?", [(int) $invoice['partner_id']]);
            if (!$partner || ($partner['attribution'] ?? '') !== 'granted') {
                $this->db->rollback();
                return $this->err('partner_not_attributable');
            }
            $this->db->insert(
                "INSERT INTO {$this->t('invoice_event')} (invoice_id, event_type, actor, idempotency_key, body_hash, created_at) VALUES (?,?,?,?,?,?)",
                [(int) $invoice['id'], 'approved_paid', $actor, $idem, $fingerprint, $this->now()]
            );
            $ledgerKey = 'pending:' . (int) $invoice['id'];
            $already = $this->db->fetch("SELECT id FROM {$this->t('ledger')} WHERE idempotency_key=?", [$ledgerKey]);
            $pendingId = $already ? (int) $already['id'] : $this->db->insert(
                "INSERT INTO {$this->t('ledger')} (invoice_id, partner_id, entry_type, rate_bps, base_minor, amount_minor, reason, idempotency_key, created_at) VALUES (?,?, 'pending_unpriced', 0, ?, NULL, 'requires_approval', ?, ?)",
                [(int) $invoice['id'], (int) $partner['id'], (int) $invoice['food_base'], $ledgerKey, $this->now()]
            );
            $this->db->commit();
            $this->audit('staff:' . $staff['staff_id'], 'invoice_paid_unpriced', $externalId, [], ['reason' => 'requires_approval', 'food_base' => (int) $invoice['food_base']]);
            return $this->ok([
                'invoice_id' => (int) $invoice['id'],
                'ledger_id' => $pendingId,
                'priced' => false,
                'reason' => 'requires_approval',
                'automatic_commission' => false,
                'alex_commission' => null,
            ]);
        } catch (PDOException $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE')) {
                $prior = $this->db->fetch("SELECT * FROM {$this->t('invoice_event')} WHERE idempotency_key=?", [$idem]);
                if ($prior) {
                    return $this->eventDecision($prior, $fingerprint, $externalId, 'approved_paid');
                }
                return $this->err('invoice_conflict', 409);
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
        $actor = 'staff:' . $staff['staff_id'];
        $fingerprint = $this->eventFingerprint($kind, $externalId, $actor, $idem);
        $this->db->begin();
        try {
            $prior = $this->db->fetch("SELECT * FROM {$this->t('invoice_event')} WHERE idempotency_key=?", [$idem]);
            if ($prior) {
                $decision = $this->eventDecision($prior, $fingerprint, $externalId, $kind);
                $this->db->rollback();
                return $decision;
            }
            $invoice = $this->db->fetch($this->db->lockSql('invoice', 'external_id=?'), [$externalId]);
            if (!$invoice) {
                $this->db->rollback();
                return $this->err('not_found', 404);
            }
            $alreadyDead = $this->db->fetch(
                "SELECT id FROM {$this->t('invoice_event')} WHERE invoice_id=? AND event_type IN ('cancelled','refunded')",
                [(int) $invoice['id']]
            );
            if ($alreadyDead) {
                $this->db->rollback();
                return $this->err('already_reversed', 409);
            }
            $this->db->insert(
                "INSERT INTO {$this->t('invoice_event')} (invoice_id, event_type, actor, idempotency_key, body_hash, created_at) VALUES (?,?,?,?,?,?)",
                [(int) $invoice['id'], $kind, $actor, $idem, $fingerprint, $this->now()]
            );
            $priced = $this->db->fetch(
                "SELECT * FROM {$this->t('ledger')} WHERE invoice_id=? AND entry_type='priced'",
                [(int) $invoice['id']]
            );
            $reversalId = null;
            if ($priced) {
                $revKey = 'reversal:' . (int) $invoice['id'];
                $existingRev = $this->db->fetch("SELECT * FROM {$this->t('ledger')} WHERE idempotency_key=?", [$revKey]);
                if ($existingRev) {
                    $this->db->rollback();
                    return $this->err('already_reversed', 409);
                }
                $reversalId = $this->db->insert(
                    "INSERT INTO {$this->t('ledger')} (invoice_id, partner_id, entry_type, rate_bps, base_minor, amount_minor, reason, idempotency_key, created_at) VALUES (?,?, 'reversal', ?,?,?,?,?,?)",
                    [(int) $invoice['id'], (int) $priced['partner_id'], (int) $priced['rate_bps'], (int) $priced['base_minor'], $priced['amount_minor'], 'reversal', $revKey, $this->now()]
                );
            }
            $this->db->commit();
            $this->audit('staff:' . $staff['staff_id'], 'invoice_reverse', $externalId, [], ['kind' => $kind, 'reversal_id' => $reversalId]);
            return $this->ok(['invoice_id' => (int) $invoice['id'], 'reversal_id' => $reversalId]);
        } catch (Throwable $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            if ($e instanceof PDOException && ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE'))) {
                $prior = $this->db->fetch("SELECT * FROM {$this->t('invoice_event')} WHERE idempotency_key=?", [$idem]);
                if ($prior) {
                    return $this->eventDecision($prior, $fingerprint, $externalId, $kind);
                }
                return $this->err('invoice_conflict', 409);
            }
            throw $e;
        }
    }

    public function requestBank(array $req, int $partnerId, string $ciphertext): array {
        return $this->err('payout_provider_pending', 403);
    }

    public function approveBank(array $req, int $bankId, string $ciphertext): array {
        return $this->err('payout_provider_pending', 403);
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

    public function exportReconcile(array $req): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        return $this->posReconcileCsv();
    }

    public function posReconcileCsv(): array {
        $last = $this->db->fetch("SELECT * FROM {$this->t('pos_snapshot')} ORDER BY id DESC LIMIT 1");
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, ['fetched_at', 'kind', 'amount', 'note']);
        $vatKnown = false;
        $foodTotal = null;
        if ($last) {
            $decoded = json_decode((string) $last['raw_json'], true);
            $vatKnown = is_array($decoded) && !empty($decoded['vat_known']);
            $mapped = $this->mapPos((string) $last['raw_json']);
            if (!$mapped['lines']) {
                fputcsv($fh, [(string) $last['fetched_at'], 'raw', '', Gn_Alex_Text::csvCell('unmapped snapshot')]);
            }
            $sum = 0;
            foreach ($mapped['lines'] as $line) {
                $sum += (int) $line['amount'];
                fputcsv($fh, [
                    (string) $last['fetched_at'],
                    Gn_Alex_Text::csvCell((string) $line['kind']),
                    (string) $line['amount'],
                    Gn_Alex_Text::csvCell((string) $line['name']),
                ]);
            }
            if ($vatKnown && $mapped['lines']) {
                $foodTotal = $sum;
                fputcsv($fh, [(string) $last['fetched_at'], 'eligible_food_lines', (string) $sum, 'vat_known']);
            } else {
                fputcsv($fh, [(string) $last['fetched_at'], 'vat_unresolved', '', 'food total blocked']);
            }
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $this->ok([
            'csv' => $csv === false ? '' : $csv,
            'fallback' => true,
            'simulated' => false,
            'vat_unresolved' => !$vatKnown,
            'food_total' => $foodTotal,
        ]);
    }

    /** @param array<string,string> $headers */
    public function ingestWebhook(string $rawBody, array $headers): array {
        $countBefore = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('webhook')}")['c'];
        if (strlen($this->webhookSecret) < 16) {
            return $this->err('webhook_secret_missing', 503);
        }
        if ($this->production && empty($headers['https'])) {
            return $this->err('https_required', 403);
        }
        $timestamp = (string) ($headers['x-gn-timestamp'] ?? '');
        $signature = (string) ($headers['x-gn-signature'] ?? '');
        $tenant = (string) ($headers['x-gn-tenant'] ?? '');
        if ($timestamp === '' || $signature === '' || $tenant === '' || !preg_match('/^[0-9]{10}$/', $timestamp)) {
            return $this->err('webhook_unauthorized', 401);
        }
        if (abs($this->now() - (int) $timestamp) > 300) {
            return $this->err('webhook_timestamp', 401);
        }
        $expect = hash_hmac('sha256', $timestamp . '.' . $rawBody, $this->webhookSecret);
        if (!preg_match('/^[a-f0-9]{64}$/', $signature) || !hash_equals($expect, $signature)) {
            return $this->err('webhook_signature', 401);
        }
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return $this->err('webhook_body', 400);
        }
        $bodyTenant = (string) ($payload['tenant'] ?? '');
        if (!hash_equals($this->tenant, $tenant) || !hash_equals($this->tenant, $bodyTenant)) {
            return $this->err('webhook_tenant', 401);
        }
        $key = (string) ($payload['event_key'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.:-]{8,80}$/', $key)) {
            return $this->err('idempotency_required');
        }
        $bodyHash = hash('sha256', $rawBody);
        $existing = $this->db->fetch("SELECT * FROM {$this->t('webhook')} WHERE idempotency_key=?", [$key]);
        if ($existing) {
            if (!hash_equals((string) $existing['body_hash'], $bodyHash)) {
                return $this->err('webhook_conflict', 409);
            }
            $stored = json_decode((string) $existing['result_json'], true) ?: ['ok' => true];
            $stored['replay'] = true;
            return $stored;
        }
        $spoof = in_array((string) ($payload['source'] ?? ''), ['manual', 'import'], true) || !empty($payload['link_verified']);
        $result = [
            'ok' => true,
            'accepted' => true,
            'stored' => true,
            'attributed' => false,
            'link_verified' => false,
            'commission' => null,
            'priced' => false,
            'reason' => $spoof ? 'manual_spoof_rejected' : 'untrusted_webhook',
            'replay' => false,
            'http' => 200,
        ];
        $this->db->insert(
            "INSERT INTO {$this->t('webhook')} (idempotency_key, body_hash, signed_timestamp, tenant, result_json, created_at) VALUES (?,?,?,?,?,?)",
            [$key, $bodyHash, (int) $timestamp, $tenant, json_encode($result), $this->now()]
        );
        $countAfter = (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('webhook')}")['c'];
        if ($countAfter !== $countBefore + 1) {
            return $this->err('webhook_conflict', 409);
        }
        return $result;
    }

    public function publicMetrics(string $slug, array $req = []): array {
        $staff = $this->activeStaff($req);
        if (!$staff || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
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
            'linked_invoice_count' => $pos['count'],
            'eligible_food_base' => $pos['food_base'],
            'automatic_commission' => false,
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
            'mode' => 'pending_wp_facts_only',
            'writes' => false,
            'crm_posts' => false,
            'automatic_commission' => false,
            'staged_attribution_only' => true,
            'p1_production_proven' => false,
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
        if (!in_array($source, ['manual', 'import'], true)) {
            return $this->err('source_invalid');
        }
        $external = (string) ($in['external_id'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.:-]{4,80}$/', $external)) {
            return $this->err('external_id');
        }
        $food = $this->minorUnits($in['food_base'] ?? null);
        if ($food === null) {
            return $this->err('food_base');
        }
        $vat = (string) ($in['vat_treatment'] ?? 'unknown');
        if (!in_array($vat, ['included', 'excluded', 'unknown'], true)) {
            return $this->err('vat_treatment');
        }
        $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE public_id=?", [(string) ($in['partner_public_id'] ?? '')]);
        if (!$partner || ($partner['attribution'] ?? '') !== 'granted') {
            return $this->err('partner_not_attributable');
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
        $link = !empty($in['link_verified']) ? 1 : 0;
        $canonical = [
            'campaign_id' => $campaignId,
            'external_id' => $external,
            'food_base' => $food,
            'link_verified' => $link,
            'partner_id' => (int) $partner['id'],
            'source' => $source,
            'vat_treatment' => $vat,
        ];
        $fingerprint = hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));
        $raw = json_encode(['fingerprint' => $fingerprint, 'vat_treatment' => $vat, 'ignored_keys' => $ignored], JSON_UNESCAPED_SLASHES);
        $receivedHash = $this->eventFingerprint('received', $external, $actor, 'recv:' . $external);
        $this->db->begin();
        try {
            $existing = $this->db->fetch($this->db->lockSql('invoice', 'external_id=?'), [$external]);
            if ($existing) {
                $this->db->rollback();
                return $this->invoiceReplayOrConflict($existing, $fingerprint, $canonical, $actor, $external);
            }
            $id = $this->db->insert(
                "INSERT INTO {$this->t('invoice')} (external_id, partner_id, campaign_id, source, food_base, tax, drinks, service_fee, discount, link_verified, raw_json, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$external, (int) $partner['id'], $campaignId, $source, $food, 0, 0, 0, 0, $link, $raw, $this->now()]
            );
            $this->db->insert(
                "INSERT INTO {$this->t('invoice_event')} (invoice_id, event_type, actor, idempotency_key, body_hash, created_at) VALUES (?,?,?,?,?,?)",
                [$id, 'received', $actor, 'recv:' . $external, $receivedHash, $this->now()]
            );
            $this->db->commit();
            $this->audit($actor, 'invoice_record', $external, [], ['food_base' => $food, 'source' => $source, 'link_verified' => $link, 'vat_treatment' => $vat]);
            return $this->ok(['invoice_id' => $id, 'replay' => false, 'created' => true, 'link_verified' => $link]);
        } catch (PDOException $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE')) {
                $existing = $this->db->fetch("SELECT * FROM {$this->t('invoice')} WHERE external_id=?", [$external]);
                if ($existing) {
                    return $this->invoiceReplayOrConflict($existing, $fingerprint, $canonical, $actor, $external);
                }
                return $this->err('invoice_conflict', 409);
            }
            throw $e;
        }
    }

    /** @param mixed $value */
    private function minorUnits($value): ?int {
        if (is_int($value)) {
            return ($value >= 0 && $value <= 9000000000000) ? $value : null;
        }
        if (is_string($value) && preg_match('/^(0|[1-9][0-9]{0,14})$/', $value)) {
            $number = (int) $value;
            return ($number <= 9000000000000) ? $number : null;
        }
        return null;
    }

    /** @param array<string,mixed> $canonical */
    private function invoiceReplayOrConflict(array $existing, string $fingerprint, array $canonical, string $actor, string $external): array {
        $stored = json_decode((string) $existing['raw_json'], true);
        $storedHash = is_array($stored) ? (string) ($stored['fingerprint'] ?? '') : '';
        if ($storedHash !== '' && hash_equals($storedHash, $fingerprint)) {
            return $this->ok(['invoice_id' => (int) $existing['id'], 'replay' => true, 'created' => false, 'conflict' => false]);
        }
        $storedCanonical = [
            'campaign_id' => $existing['campaign_id'] === null ? null : (int) $existing['campaign_id'],
            'external_id' => (string) $existing['external_id'],
            'food_base' => (int) $existing['food_base'],
            'link_verified' => (int) $existing['link_verified'],
            'partner_id' => (int) $existing['partner_id'],
            'source' => (string) $existing['source'],
            'vat_treatment' => is_array($stored) ? (string) ($stored['vat_treatment'] ?? 'unknown') : 'unknown',
        ];
        $fields = [];
        foreach ($canonical as $key => $value) {
            if (($storedCanonical[$key] ?? null) !== $value) {
                $fields[] = $key;
            }
        }
        $this->audit($actor, 'invoice_conflict', $external, [], ['fields' => $fields]);
        return $this->err('invoice_conflict', 409);
    }

    private function eventFingerprint(string $action, string $externalId, string $actor, string $idem): string {
        $payload = ['action' => $action, 'actor' => $actor, 'external_id' => $externalId, 'idempotency_key' => $idem];
        ksort($payload);
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    /** @param array<string,mixed> $prior */
    private function eventDecision(array $prior, string $fingerprint, string $externalId, string $action): array {
        $invoice = $this->db->fetch("SELECT id, external_id FROM {$this->t('invoice')} WHERE id=?", [(int) $prior['invoice_id']]);
        $same = $invoice
            && hash_equals((string) $invoice['external_id'], $externalId)
            && (string) $prior['event_type'] === $action
            && hash_equals((string) ($prior['body_hash'] ?? ''), $fingerprint);
        if ($same) {
            return $this->ok(['replay' => true, 'invoice_id' => (int) $invoice['id'], 'conflict' => false]);
        }
        return $this->err('invoice_conflict', 409);
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
        return ['count' => $count, 'food_base' => $revenue];
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
        if (!in_array($role, ['quay', 'thungan', 'quanly', 'onboarding'], true)) {
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

    private function consumeRegistrationToken(string $token, string $purpose): bool {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return false;
        }
        $this->db->begin();
        try {
            $row = $this->db->fetch($this->db->lockSql('reg_token', 'token_hash=?'), [hash('sha256', $token)]);
            if (!$row || (string) $row['purpose'] !== $purpose || !empty($row['used_at']) || (int) $row['expires_at'] < $this->now()) {
                $this->db->rollback();
                return false;
            }
            $changed = $this->db->exec(
                "UPDATE {$this->t('reg_token')} SET used_at=? WHERE id=? AND used_at IS NULL",
                [$this->now(), (int) $row['id']]
            );
            if ($changed !== 1) {
                $this->db->rollback();
                return false;
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            return false;
        }
    }

    /** @return array<string,mixed> */
    private function reserveIdentityOtp(string $hmac): array {
        $bucket = 'otp:' . $hmac;
        $now = $this->now();
        $this->db->begin();
        try {
            $pending = $this->db->fetch(
                "SELECT id FROM {$this->t('otp')} WHERE phone_hmac=? AND status='pending' AND expires_at>?",
                [$hmac, $now]
            );
            if ($pending) {
                $this->db->rollback();
                return $this->err('otp_already_pending', 429);
            }
            $row = $this->db->fetch($this->db->lockSql('rate', 'bucket=?'), [$bucket]);
            if (!$row || (int) $row['reset_at'] <= $now) {
                if ($row) {
                    $this->db->exec("UPDATE {$this->t('rate')} SET n=1, reset_at=? WHERE bucket=?", [$now + 600, $bucket]);
                } else {
                    $this->db->insert("INSERT INTO {$this->t('rate')} (bucket, n, reset_at) VALUES (?,?,?)", [$bucket, 1, $now + 600]);
                }
                $this->db->commit();
                return $this->ok(['reserved' => true]);
            }
            if ((int) $row['n'] >= 3) {
                $this->db->rollback();
                return $this->err('rate_limited', 429);
            }
            $this->db->exec("UPDATE {$this->t('rate')} SET n=n+1 WHERE bucket=?", [$bucket]);
            $this->db->commit();
            return $this->ok(['reserved' => true]);
        } catch (PDOException $e) {
            if ($this->db->inTx) {
                $this->db->rollback();
            }
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'locked')) {
                return $this->err('rate_limited', 429);
            }
            throw $e;
        }
    }

    public function proposeRule(array $req, string $role, int $effectiveFrom, int $effectiveTo): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'quanly' || !$this->csrfOk($staff, $req)) {
            return $this->err('unauthorized', 403);
        }
        $bps = $this->rateBps($role);
        if ($bps === null || $effectiveTo <= $effectiveFrom) {
            return $this->err('policy_invalid');
        }
        $version = 1 + (int) $this->db->fetch("SELECT COUNT(*) AS c FROM {$this->t('policy_rule')} WHERE role=?", [$role])['c'];
        $id = $this->db->insert(
            "INSERT INTO {$this->t('policy_rule')} (role, rate_bps, food_only, rule_version, effective_from, effective_to, status, requester_id, approver_id, created_at, decided_at) VALUES (?,?,1,?,?,?,'pending',?,NULL,?,NULL)",
            [$role, $bps, $version, $effectiveFrom, $effectiveTo, (int) $staff['staff_id'], $this->now()]
        );
        return $this->ok(['rule_id' => $id, 'rule_version' => $version, 'status' => 'pending', 'reason' => 'requires_approval']);
    }

    public function approveRule(array $req, int $ruleId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'onboarding' || !$this->csrfOk($staff, $req)) {
            return $this->err('distinct_role_required', 403);
        }
        $rule = $this->db->fetch("SELECT * FROM {$this->t('policy_rule')} WHERE id=?", [$ruleId]);
        if (!$rule || $rule['status'] !== 'pending') {
            return $this->err('not_found', 404);
        }
        if ((int) $rule['requester_id'] === (int) $staff['staff_id']) {
            return $this->err('self_approval_denied', 403);
        }
        $this->db->exec(
            "UPDATE {$this->t('policy_rule')} SET status='approved', approver_id=?, decided_at=? WHERE id=?",
            [(int) $staff['staff_id'], $this->now(), $ruleId]
        );
        $this->audit('staff:' . $staff['staff_id'], 'rule_approve', 'rule:' . $ruleId, ['status' => 'pending'], ['status' => 'approved', 'rule_version' => (int) $rule['rule_version']]);
        return $this->ok(['status' => 'approved', 'rule_version' => (int) $rule['rule_version'], 'automatic_commission' => false]);
    }

    public function priceAttribution(array $req, string $externalId): array {
        $staff = $this->activeStaff($req);
        if (!$staff || $staff['role'] !== 'onboarding' || !$this->csrfOk($staff, $req)) {
            return $this->err('distinct_role_required', 403);
        }
        $invoice = $this->db->fetch("SELECT * FROM {$this->t('invoice')} WHERE external_id=?", [$externalId]);
        if (!$invoice || (int) $invoice['link_verified'] !== 1 || !in_array($invoice['source'], ['manual', 'import'], true)) {
            return $this->err('requires_approval');
        }
        $partner = $this->db->fetch("SELECT * FROM {$this->t('partner')} WHERE id=?", [(int) $invoice['partner_id']]);
        if (!$partner || ($partner['attribution'] ?? '') !== 'granted') {
            return $this->err('partner_not_attributable');
        }
        $campaign = $invoice['campaign_id'] !== null ? $this->campaignById((int) $invoice['campaign_id']) : null;
        $slug = (string) ($campaign['slug'] ?? '');
        $family = (string) ($campaign['campaign_family'] ?? '');
        if ($slug === self::ALEX_SLUG || $family === self::ALEX_FAMILY) {
            return $this->err('alex_not_commissionable');
        }
        if ($slug !== self::REFERRAL_SLUG || $family !== self::REFERRAL_FAMILY) {
            return $this->err('campaign_not_referral');
        }
        if ((string) $partner['role'] !== 'ctv' && (string) $partner['role'] !== 'driver') {
            return $this->err('role_invalid');
        }
        return $this->err('vat_review_required');
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
