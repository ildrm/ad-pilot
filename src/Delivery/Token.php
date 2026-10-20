<?php
namespace AdPilot\Delivery;

use AdPilot\Core\{Clock, Problem};

final class Token
{
    public function __construct(private readonly string $secret, private readonly Clock $clock, private readonly int $site)
    {
    }

    public function issue(array $claims): string
    {
        $claims = array_replace($claims, ['site' => $this->site, 'exp' => $this->clock->now()->getTimestamp() + 300]);
        $payload = rtrim(strtr(base64_encode(json_encode($claims, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        return $payload . '.' . hash_hmac('sha256', $payload, $this->secret);
    }

    public function read(string $token): array
    {
        if (strlen($token) > 4096 || substr_count($token, '.') !== 1) {
            throw new Problem('invalid_decision', 'Invalid decision reference.', 403);
        }
        [$payload, $signature] = explode('.', $token, 2);
        if (!hash_equals(hash_hmac('sha256', $payload, $this->secret), $signature)) {
            throw new Problem('invalid_decision', 'Invalid decision signature.', 403);
        }
        try {
            $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
            $claims = json_decode($decoded ?: '', true, 16, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new Problem('invalid_decision', 'Invalid decision payload.', 403);
        }
        if (!is_array($claims) || ($claims['site'] ?? null) !== $this->site || ($claims['exp'] ?? 0) <= $this->clock->now()->getTimestamp()) {
            throw new Problem('decision_expired', 'Decision expired or belongs to another site.', 403);
        }
        foreach (['campaign', 'slot', 'render', 'nonce', 'digest'] as $field) {
            if (!is_string($claims[$field] ?? null) || $claims[$field] === '') {
                throw new Problem('invalid_decision', 'Incomplete decision provenance.', 403);
            }
        }
        if (!is_int($claims['version'] ?? null) || $claims['version'] < 1 || !is_bool($claims['measurement'] ?? null)) {
            throw new Problem('invalid_decision', 'Invalid decision schema.', 403);
        }
        return $claims;
    }
}
