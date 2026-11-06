<?php
class Gn_Alex_Phone {
    public static function canonicalize(string $raw): ?string {
        $s = preg_replace('/[\s.\-()]/', '', trim($raw));
        if ($s === null || $s === '') {
            return null;
        }
        if (str_starts_with($s, '00')) {
            $s = '+' . substr($s, 2);
        }
        if (preg_match('/^0([35789]\d{8})$/', $s, $m)) {
            $s = '+84' . $m[1];
        } elseif (preg_match('/^84([35789]\d{8})$/', $s, $m)) {
            $s = '+84' . $m[1];
        }
        if (!preg_match('/^\+84[35789]\d{8}$/', $s)) {
            return null;
        }
        return $s;
    }

    public static function mask(string $canonical): string {
        return substr($canonical, 0, 5) . '****' . substr($canonical, -3);
    }

    public static function hmac(string $canonical, string $pepper): string {
        return hash_hmac('sha256', $canonical, $pepper);
    }
}

class Gn_Alex_Text {
    public static function sanitize(string $value, int $max = 80): string {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        return mb_substr(trim($value), 0, $max);
    }

    public static function esc(string $value): string {
        if (function_exists('esc_html')) {
            return esc_html($value);
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csvCell(string $value): string {
        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }
}
