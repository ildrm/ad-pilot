<?php
namespace AdPilot\Core;

final class Input
{
    public static function text(array $data, string $key, int $max = 191, bool $required = true): string
    {
        $value = $data[$key] ?? '';
        if (!is_string($value) || ($required && trim($value) === '') || mb_strlen($value) > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            throw new Problem('invalid_field', "Invalid {$key}.");
        }
        return trim(strip_tags($value));
    }

    public static function integer(array $data, string $key, int $min, int $max, ?int $default = null): int
    {
        $value = $data[$key] ?? $default;
        if (is_string($value) && preg_match('/^\d{1,9}$/D', $value)) {
            $value = (int) $value;
        }
        if (!is_int($value) || $value < $min || $value > $max) {
            throw new Problem('invalid_field', "Invalid {$key}.");
        }
        return $value;
    }

    public static function date(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})$/D', $value)) {
            throw new Problem('invalid_date', "{$key} must be an RFC3339 timestamp with timezone.");
        }
        try {
            $date = new \DateTimeImmutable($value);
            $errors = \DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] || $errors['error_count'])) {
                throw new \Exception();
            }
        } catch (\Exception) {
            throw new Problem('invalid_date', "Invalid {$key}.");
        }
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    public static function url(array $data, string $key): string
    {
        $url = self::text($data, $key, 2048);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true) || parse_url($url, PHP_URL_USER) || parse_url($url, PHP_URL_PASS)) {
            throw new Problem('invalid_url', 'An HTTP(S) destination without credentials is required.');
        }
        return $url;
    }

    public static function json(array $value, int $max = 65536): string
    {
        $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > $max) {
            throw new Problem('payload_too_large', 'The document exceeds the configured size limit.', 413);
        }
        return $json;
    }
}
