<?php
namespace AdPilot\Platform;

use AdPilot\Core\{Clock, Problem, Store};

/** At-least-once local dispatch; consumers must deduplicate by event_id. */
final class Outbox
{
    public function __construct(private readonly Store $store, private readonly Clock $clock)
    {
    }

    public function drain(int $limit = 50): array
    {
        $dispatched = 0; $failed = 0;
        foreach ($this->store->many('outbox_messages', ['status' => 'processing', '_available_before' => $this->clock->now()->format('Y-m-d H:i:s')], 100) as $stale) {
            if ($stale['available_at'] <= $this->clock->now()->format('Y-m-d H:i:s')) {
                $this->store->transaction(function () use ($stale): void {
                    $current = $this->store->one('outbox_messages', ['id' => $stale['id']], true);
                    if ($current && $current['status'] === 'processing' && $current['available_at'] <= $this->clock->now()->format('Y-m-d H:i:s')) {
                        $this->store->update('outbox_messages', $current, ['status' => (int) $current['attempts'] >= 5 ? 'failed' : 'pending']);
                    }
                });
            }
        }
        foreach ($this->store->many('outbox_messages', ['status' => 'pending', '_available_before' => $this->clock->now()->format('Y-m-d H:i:s')], max(1, min(100, $limit))) as $candidate) {
            if ($candidate['available_at'] > $this->clock->now()->format('Y-m-d H:i:s')) { continue; }
            $claimed = $this->store->transaction(function () use ($candidate): ?array {
                $row = $this->store->one('outbox_messages', ['id' => $candidate['id']], true);
                if (!$row || $row['status'] !== 'pending') { return null; }
                return $this->store->update('outbox_messages', $row, ['status' => 'processing', 'attempts' => (int) $row['attempts'] + 1,
                    'available_at' => $this->clock->now()->modify('+5 minutes')->format('Y-m-d H:i:s')]);
            });
            if (!$claimed) { continue; }
            try {
                $payload = json_decode($claimed['payload_json'], true, 16, JSON_THROW_ON_ERROR);
                if (($payload['schema_version'] ?? null) !== 1 || ($payload['site_id'] ?? null) !== get_current_blog_id() || ($payload['event_type'] ?? '') !== $claimed['event_type']) {
                    throw new Problem('invalid_job_provenance', 'Invalid committed event envelope.');
                }
                do_action('adpilot_domain_event', $payload);
                $this->store->update('outbox_messages', $claimed, ['status' => 'dispatched']);
                ++$dispatched;
            } catch (\Throwable) {
                $this->store->update('outbox_messages', $claimed, ['status' => (int) $claimed['attempts'] >= 5 ? 'failed' : 'pending',
                    'available_at' => $this->clock->now()->modify('+' . min(3600, 30 * (2 ** (int) $claimed['attempts'])) . ' seconds')->format('Y-m-d H:i:s')]);
                ++$failed;
            }
        }
        return compact('dispatched', 'failed');
    }

    public function retry(string $id): array
    {
        return $this->store->transaction(function () use ($id): array {
            $row = $this->store->one('outbox_messages', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Job unavailable.', 404);
            if ($row['status'] !== 'failed') { throw new Problem('retry_unavailable', 'Only a failed committed job can be retried.', 409); }
            $row = $this->store->update('outbox_messages', $row, ['status' => 'pending', 'attempts' => 0, 'available_at' => $this->clock->now()->format('Y-m-d H:i:s')]);
            return ['id' => $row['public_id'], 'status' => $row['status']];
        });
    }
}
