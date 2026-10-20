<?php
namespace AdPilot\Delivery;

use AdPilot\Campaigns\Eligibility;
use AdPilot\Core\{Clock, Ids, Input, Problem, Store};

final class Delivery
{
    public function __construct(private readonly Store $store, private readonly Clock $clock, private readonly Token $tokens, private readonly \Closure $stop)
    {
    }

    public function decide(array $opportunities, bool $measurement): array
    {
        if (count($opportunities) > 20 || count($opportunities) < 1) {
            throw new Problem('batch_limit', 'A decision batch must contain between 1 and 20 opportunities.');
        }
        $ids = [];
        foreach ($opportunities as $opportunity) {
            if (!is_array($opportunity)) {
                throw new Problem('invalid_opportunity', 'Invalid render opportunity.');
            }
            $ids[] = Input::text($opportunity, 'slot_id', 26);
            $render = Input::text($opportunity, 'render_instance_id', 80);
            if (!preg_match('/^[a-zA-Z0-9_-]{8,80}$/D', $render)) {
                throw new Problem('invalid_opportunity', 'Invalid render occurrence identity.');
            }
        }
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $stopped = ($this->stop)();
        $candidates = $stopped ? [] : $this->store->candidates(array_values(array_unique($ids)), $now);
        $eligible = [];
        foreach ($candidates as $row) {
            $creative = ['status' => $row['creative_status'], 'public_id' => $row['creative_public_id']];
            $contract = ['status' => $row['contract_status'], 'start_at' => $row['contract_start_at'], 'end_at' => $row['contract_end_at']];
            if (Eligibility::evaluate($row, $creative, $contract, $now, $stopped) === null && hash_equals($row['content_digest'], hash('sha256', $row['content_json'], true))) {
                $eligible[$row['slot_public_id']] ??= $row;
            }
        }
        $decisions = [];
        foreach ($opportunities as $opportunity) {
            $row = $eligible[$opportunity['slot_id']] ?? null;
            $decision = ['slot_id' => $opportunity['slot_id'], 'render_instance_id' => $opportunity['render_instance_id'], 'status' => $row ? 'selected' : 'empty'];
            if ($row) {
                $nonce = Ids::make();
                $reference = $this->tokens->issue(['campaign' => $row['public_id'], 'slot' => $row['slot_public_id'],
                    'version' => (int) $row['row_version'], 'digest' => bin2hex($row['content_digest']), 'render' => $opportunity['render_instance_id'], 'nonce' => $nonce, 'measurement' => $measurement]);
                $decision += ['decision_ref' => $reference, 'creative' => json_decode($row['content_json'], true, 16, JSON_THROW_ON_ERROR)];
                if ($measurement) {
                    $this->event($row, $opportunity['render_instance_id'], 'served', hash('sha256', $nonce . ':served'));
                }
            }
            $decisions[] = $decision;
        }
        return ['decisions' => $decisions, 'measurement_enabled' => $measurement, 'billable' => false];
    }

    public function signal(string $reference, string $type, bool $measurement): array
    {
        if (!in_array($type, ['rendered', 'click'], true)) {
            throw new Problem('unsupported_event', 'This event type is unavailable in R1.');
        }
        $claims = $this->tokens->read($reference);
        $rows = $this->store->candidates([$claims['slot']], $this->clock->now()->format('Y-m-d H:i:s'));
        $row = null;
        foreach ($rows as $candidate) {
            if ($candidate['public_id'] === $claims['campaign'] && (int) $candidate['row_version'] === $claims['version'] && bin2hex($candidate['content_digest']) === $claims['digest']) {
                $row = $candidate;
            }
        }
        if (!$row || ($this->stop)() || Eligibility::evaluate($row, ['status' => $row['creative_status'], 'public_id' => $row['creative_public_id']],
            ['status' => $row['contract_status'], 'start_at' => $row['contract_start_at'], 'end_at' => $row['contract_end_at']], $this->clock->now()->format('Y-m-d H:i:s'), false) !== null) {
            throw new Problem('decision_revoked', 'Current campaign eligibility no longer permits this decision.', 409);
        }
        if ($measurement && ($claims['measurement'] ?? false)) {
            $this->event($row, $claims['render'], $type === 'rendered' ? 'rendered_claim' : 'click', hash('sha256', $claims['nonce'] . ':' . $type));
        }
        $content = json_decode($row['content_json'], true, 16, JSON_THROW_ON_ERROR);
        return ['accepted' => true, 'billable' => false] + ($type === 'click' ? ['destination_url' => $content['destination_url']] : []);
    }

    private function event(array $row, string $render, string $type, string $key): void
    {
        // A unique DB index provides deduplication across retrying browsers.
        if ($this->store->one('delivery_events', ['event_key' => $key])) {
            return;
        }
        try {
            $this->store->insert('delivery_events', ['organization_id' => $row['organization_id'], 'campaign_id' => $row['id'], 'slot_id' => $row['slot_id'],
                'event_type' => $type, 'event_key' => $key, 'render_instance_id' => $render,
                'occurred_at' => $this->clock->now()->format('Y-m-d H:i:s'), 'source_class' => 'public']);
        } catch (Problem $error) {
            if (!$this->store->one('delivery_events', ['event_key' => $key])) {
                throw $error;
            }
        }
    }
}
