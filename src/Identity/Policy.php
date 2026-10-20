<?php
namespace AdPilot\Identity;

use AdPilot\Core\Problem;

final class Policy
{
    private const GRANTS = [
        'owner' => ['read', 'campaign_write', 'creative_write', 'purchase', 'finance', 'members'],
        'manager' => ['read', 'campaign_write', 'creative_write'],
        'analyst' => ['read'],
        'billing' => ['read', 'purchase', 'finance'],
    ];

    public static function requireMembership(?array $membership, array $organization, string $operation): void
    {
        if (!$membership || $membership['status'] !== 'active' || !in_array($organization['status'], ['pending', 'verified'], true) || !in_array($operation, self::GRANTS[$membership['role_key']] ?? [], true)) {
            throw new Problem('forbidden', 'Current organization membership does not permit this operation.', 403);
        }
    }
}
