<?php
namespace AdPilot\Campaigns;

final class Eligibility
{
    public static function evaluate(array $campaign, array $creative, array $contract, string $now, bool $stopped): ?string
    {
        if ($stopped || $campaign['delivery_status'] !== 'active') {
            return 'stopped';
        }
        if ($creative['status'] !== 'approved' || $creative['public_id'] !== $campaign['approved_creative_id']) {
            return 'creative_unapproved';
        }
        if (!in_array($campaign['financial_status'], ['complimentary', 'paid'], true) || $contract['status'] !== 'active') {
            return 'entitlement_missing';
        }
        if ($now < $campaign['start_at'] || $now >= $campaign['end_at'] || $now < $contract['start_at'] || $now >= $contract['end_at']) {
            return 'outside_schedule';
        }
        return null;
    }
}
