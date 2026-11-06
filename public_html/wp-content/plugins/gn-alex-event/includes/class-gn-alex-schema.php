<?php
/**
 * New gn_alex_* tables only. Statements never ALTER sao_*, wp_*, or voucher-online tables.
 */
class Gn_Alex_Schema {
    public static function statements(string $driver, string $prefix): array {
        if (!preg_match('/^[a-z0-9_]+$/', $prefix)) {
            throw new InvalidArgumentException('prefix');
        }
        $auto = $driver === 'mysql' ? 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = $driver === 'mysql' ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $tables = [];

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}identity (
            id {$auto},
            phone_hmac CHAR(64) NOT NULL,
            phone_mask VARCHAR(32) NOT NULL,
            display_name VARCHAR(80) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (phone_hmac)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}otp (
            id {$auto},
            phone_hmac CHAR(64) NOT NULL,
            campaign_id BIGINT NOT NULL,
            otp_hash CHAR(64) NOT NULL,
            expires_at BIGINT NOT NULL,
            attempts INT NOT NULL DEFAULT 0,
            max_attempts INT NOT NULL DEFAULT 5,
            provider_id VARCHAR(64) NOT NULL,
            provider_ack VARCHAR(80) NOT NULL,
            status VARCHAR(20) NOT NULL,
            locale VARCHAR(2) NOT NULL,
            display_name VARCHAR(80) NOT NULL,
            created_at BIGINT NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}campaign (
            id {$auto},
            slug VARCHAR(80) NOT NULL,
            campaign_family VARCHAR(80) NOT NULL,
            partner_code VARCHAR(40) NOT NULL,
            starts_at BIGINT NOT NULL,
            ends_at BIGINT NOT NULL,
            tz VARCHAR(40) NOT NULL,
            beer_quota INT NOT NULL,
            salad_quota INT NOT NULL,
            policy_version INT NOT NULL DEFAULT 1,
            title_en VARCHAR(160) NOT NULL,
            title_ru VARCHAR(160) NOT NULL,
            conditions_en TEXT NOT NULL,
            conditions_ru TEXT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (slug)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}registration (
            id {$auto},
            campaign_id BIGINT NOT NULL,
            identity_id BIGINT NOT NULL,
            locale VARCHAR(2) NOT NULL,
            consent_at BIGINT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (campaign_id, identity_id)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}voucher (
            id {$auto},
            identity_id BIGINT NOT NULL,
            campaign_id BIGINT NOT NULL,
            campaign_family VARCHAR(80) NOT NULL,
            beer_quota INT NOT NULL,
            beer_issued INT NOT NULL DEFAULT 0,
            salad_quota INT NOT NULL,
            salad_issued INT NOT NULL DEFAULT 0,
            version INT NOT NULL DEFAULT 1,
            status VARCHAR(20) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (identity_id, campaign_family)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}ticket (
            id {$auto},
            voucher_id BIGINT NOT NULL,
            secret_hash CHAR(64) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (voucher_id)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}guest_session (
            id {$auto},
            voucher_id BIGINT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at BIGINT NOT NULL,
            revoked_at BIGINT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (token_hash)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}staff (
            id {$auto},
            role VARCHAR(20) NOT NULL,
            pin_hash VARCHAR(255) NOT NULL,
            label VARCHAR(40) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at BIGINT NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}staff_session (
            id {$auto},
            staff_id BIGINT NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expires_at BIGINT NOT NULL,
            revoked_at BIGINT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (token_hash)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}nonce (
            id {$auto},
            voucher_id BIGINT NOT NULL,
            jti_hash CHAR(64) NOT NULL,
            expires_at BIGINT NOT NULL,
            consumed_at BIGINT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (jti_hash)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}redemption (
            id {$auto},
            voucher_id BIGINT NOT NULL,
            item_type VARCHAR(10) NOT NULL,
            slot INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            commit_key VARCHAR(80) NULL,
            hold_key VARCHAR(80) NULL,
            idempotency_key VARCHAR(80) NOT NULL,
            staff_id BIGINT NOT NULL,
            session_id BIGINT NOT NULL,
            confirmed INT NOT NULL,
            version_at INT NOT NULL,
            result_json TEXT NOT NULL,
            created_at BIGINT NOT NULL,
            expires_at BIGINT NULL,
            UNIQUE (commit_key),
            UNIQUE (hold_key),
            UNIQUE (idempotency_key)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}scan (
            id {$auto},
            campaign_id BIGINT NOT NULL,
            kind VARCHAR(20) NOT NULL,
            partner_public_id VARCHAR(40) NULL,
            created_at BIGINT NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}checkin (
            id {$auto},
            voucher_id BIGINT NOT NULL,
            staff_id BIGINT NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (voucher_id)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}abuse (
            id {$auto},
            campaign_id BIGINT NULL,
            phone_hmac CHAR(64) NULL,
            code VARCHAR(60) NOT NULL,
            created_at BIGINT NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}rate (
            bucket VARCHAR(120) NOT NULL PRIMARY KEY,
            n INT NOT NULL,
            reset_at BIGINT NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}audit (
            id {$auto},
            actor VARCHAR(40) NOT NULL,
            action VARCHAR(60) NOT NULL,
            subject VARCHAR(80) NOT NULL,
            before_json TEXT NOT NULL,
            after_json TEXT NOT NULL,
            created_at BIGINT NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}override (
            id {$auto},
            voucher_id BIGINT NOT NULL,
            requester_id BIGINT NOT NULL,
            approver_id BIGINT NULL,
            delta INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            reason VARCHAR(160) NOT NULL,
            created_at BIGINT NOT NULL,
            decided_at BIGINT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}partner (
            id {$auto},
            public_id VARCHAR(20) NOT NULL,
            identity_id BIGINT NOT NULL,
            role VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            attribution VARCHAR(20) NOT NULL DEFAULT 'none',
            reviewer_id BIGINT NULL,
            campaign_family VARCHAR(80) NOT NULL,
            display_name VARCHAR(80) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (public_id),
            UNIQUE (identity_id, role, campaign_family)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}invoice (
            id {$auto},
            external_id VARCHAR(80) NOT NULL,
            partner_id BIGINT NOT NULL,
            campaign_id BIGINT NULL,
            source VARCHAR(20) NOT NULL,
            food_base BIGINT NOT NULL,
            tax BIGINT NOT NULL DEFAULT 0,
            drinks BIGINT NOT NULL DEFAULT 0,
            service_fee BIGINT NOT NULL DEFAULT 0,
            discount BIGINT NOT NULL DEFAULT 0,
            link_verified INT NOT NULL DEFAULT 0,
            raw_json TEXT NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (external_id)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}invoice_event (
            id {$auto},
            invoice_id BIGINT NOT NULL,
            event_type VARCHAR(30) NOT NULL,
            actor VARCHAR(40) NOT NULL,
            idempotency_key VARCHAR(80) NOT NULL,
            body_hash CHAR(64) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (idempotency_key)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}ledger (
            id {$auto},
            invoice_id BIGINT NOT NULL,
            partner_id BIGINT NOT NULL,
            entry_type VARCHAR(32) NOT NULL,
            rate_bps INT NOT NULL,
            base_minor BIGINT NOT NULL,
            amount_minor BIGINT NULL,
            reason VARCHAR(80) NOT NULL,
            idempotency_key VARCHAR(80) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (idempotency_key)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}policy_rule (
            id {$auto},
            role VARCHAR(20) NOT NULL,
            rate_bps INT NOT NULL,
            food_only INT NOT NULL,
            rule_version INT NOT NULL,
            effective_from BIGINT NOT NULL,
            effective_to BIGINT NOT NULL,
            status VARCHAR(20) NOT NULL,
            requester_id BIGINT NOT NULL,
            approver_id BIGINT NULL,
            created_at BIGINT NOT NULL,
            decided_at BIGINT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}referral_click (
            id {$auto},
            partner_id BIGINT NOT NULL,
            public_id VARCHAR(20) NOT NULL,
            campaign_family VARCHAR(40) NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (partner_id)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}reg_token (
            id {$auto},
            token_hash CHAR(64) NOT NULL,
            purpose VARCHAR(20) NOT NULL,
            expires_at BIGINT NOT NULL,
            used_at BIGINT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (token_hash)
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}bank (
            id {$auto},
            partner_id BIGINT NOT NULL,
            requester_id BIGINT NOT NULL,
            approver_id BIGINT NULL,
            ciphertext VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL,
            created_at BIGINT NOT NULL,
            decided_at BIGINT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}pos_snapshot (
            id {$auto},
            fetched_at BIGINT NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            ok INT NOT NULL,
            raw_json TEXT NOT NULL,
            error VARCHAR(80) NOT NULL
        ){$engine}";

        $tables[] = "CREATE TABLE IF NOT EXISTS {$prefix}webhook (
            id {$auto},
            idempotency_key VARCHAR(80) NOT NULL,
            body_hash CHAR(64) NOT NULL,
            signed_timestamp BIGINT NOT NULL,
            tenant VARCHAR(80) NOT NULL,
            result_json TEXT NOT NULL,
            created_at BIGINT NOT NULL,
            UNIQUE (idempotency_key)
        ){$engine}";

        return $tables;
    }
}
