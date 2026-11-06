<?php
interface Gn_Alex_Otp_Provider {
    public function id(): string;
    public function isProductionVerified(): bool;
    /** @return array{acknowledged:bool,ack_id:string,error?:string,test_only?:bool,provider?:string} */
    public function send(string $phoneHmac, string $otp, array $context): array;
}

interface Gn_Alex_Get_Client {
    public function get(string $url): string;
}

class Gn_Alex_Otp_Provider_Unconfigured implements Gn_Alex_Otp_Provider {
    public function id(): string {
        return 'unconfigured';
    }
    public function isProductionVerified(): bool {
        return false;
    }
    public function send(string $phoneHmac, string $otp, array $context): array {
        return ['acknowledged' => false, 'ack_id' => '', 'error' => 'otp_provider_required'];
    }
}

/**
 * Isolated test double. Refuses to acknowledge unless GN_ALEX_TEST_HARNESS=1
 * and the process is not marked production. It is not a production provider.
 */
class Gn_Alex_Otp_Provider_IsolatedTestMock implements Gn_Alex_Otp_Provider {
    public const FLAG = 'GN_ALEX_TEST_HARNESS';
    /** @var array<string,string> */
    private array $outbox = [];

    public function __construct(private bool $harness, private bool $production) {
    }

    public function id(): string {
        return 'isolated_test_mock';
    }

    public function isProductionVerified(): bool {
        return false;
    }

    public function send(string $phoneHmac, string $otp, array $context): array {
        $flag = getenv(self::FLAG);
        if (!$this->harness || $this->production || $flag !== '1') {
            return ['acknowledged' => false, 'ack_id' => '', 'error' => 'isolated_mock_refused'];
        }
        $this->outbox[$phoneHmac] = $otp;
        return [
            'acknowledged' => true,
            'ack_id' => 'isolated-test-mock-' . bin2hex(random_bytes(4)),
            'test_only' => true,
            'provider' => 'isolated_test_mock',
        ];
    }

    public function lastOtp(string $phoneHmac): ?string {
        return $this->outbox[$phoneHmac] ?? null;
    }
}

interface Gn_Alex_Otp_Transport {
    /** @return array{ack_id?:string} */
    public function deliver(string $phoneHmac, string $otp, array $credentials): array;
}

/**
 * Production gateway shell. isProductionVerified is true only when an operator
 * supplies an endpoint and a secret of real length plus a transport.
 * This repository does not ship commercial SMS credentials, and send()
 * fail-closes when the transport does not acknowledge. The factory never
 * constructs this class.
 */
class Gn_Alex_Otp_Provider_VerifiedGateway implements Gn_Alex_Otp_Provider {
    public function __construct(private array $credentials, private ?Gn_Alex_Otp_Transport $transport) {
    }

    public function id(): string {
        return 'verified_gateway';
    }

    public function isProductionVerified(): bool {
        $endpoint = (string) ($this->credentials['endpoint'] ?? '');
        $secret = (string) ($this->credentials['secret'] ?? '');
        return $this->transport !== null && strlen($endpoint) >= 12 && strlen($secret) >= 16;
    }

    public function send(string $phoneHmac, string $otp, array $context): array {
        if (!$this->isProductionVerified()) {
            return ['acknowledged' => false, 'ack_id' => '', 'error' => 'otp_provider_required'];
        }
        try {
            $json = $this->transport->deliver($phoneHmac, $otp, [
                'endpoint' => (string) $this->credentials['endpoint'],
            ]);
        } catch (Throwable $e) {
            return ['acknowledged' => false, 'ack_id' => '', 'error' => 'otp_provider_required'];
        }
        if (empty($json['ack_id'])) {
            return ['acknowledged' => false, 'ack_id' => '', 'error' => 'otp_provider_required'];
        }
        return ['acknowledged' => true, 'ack_id' => (string) $json['ack_id'], 'provider' => 'verified_gateway'];
    }
}

class Gn_Alex_Provider_Factory {
    public static function make(): Gn_Alex_Otp_Provider {
        $production = (defined('GN_ALEX_PRODUCTION') && GN_ALEX_PRODUCTION) || getenv('GN_ALEX_PRODUCTION') === '1';
        $harness = getenv(Gn_Alex_Otp_Provider_IsolatedTestMock::FLAG) === '1';
        $allowed = defined('GN_ALEX_ALLOW_ISOLATED_TEST_MOCK') && GN_ALEX_ALLOW_ISOLATED_TEST_MOCK;
        if ($allowed && $harness && !$production) {
            return new Gn_Alex_Otp_Provider_IsolatedTestMock(true, false);
        }
        return new Gn_Alex_Otp_Provider_Unconfigured();
    }
}
