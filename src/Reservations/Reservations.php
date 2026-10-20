<?php
namespace AdPilot\Reservations;

use AdPilot\Core\{Audit, Clock, Input, Problem, Store};

final class Reservations
{
    public function __construct(private readonly Store $store, private readonly Audit $audit, private readonly Clock $clock)
    {
    }

    /** One slot row is the serialization point for all conflicting writers. */
    public function hold(int $actor, int $org, array $quote, string $key): array
    {
        if ((int) $quote['organization_id'] !== $org) {
            throw new Problem('forbidden', 'Quote belongs to a different organization.', 403);
        }
        if (strlen($key) < 16 || strlen($key) > 128) {
            throw new Problem('idempotency_required', 'An idempotency key of 16–128 characters is required.');
        }
        $snapshot = json_decode($quote['offer_snapshot_json'], true, 32, JSON_THROW_ON_ERROR);
        return $this->store->transaction(function () use ($actor, $org, $quote, $key, $snapshot): array {
            $slot = $this->store->one('slots', ['id' => $snapshot['slot_internal_id']], true)
                ?? throw new Problem('not_found', 'Slot unavailable.', 404);
            // Lock waits can outlive a quote. Read authoritative state and time only
            // after acquiring the slot serialization point.
            $quote = $this->store->one('quotes', ['id' => $quote['id'], 'organization_id' => $org], true)
                ?? throw new Problem('not_found', 'Quote unavailable.', 404);
            $now = $this->clock->now();
            $existing = $this->store->one('inventory_holds', ['organization_id' => $org, 'idempotency_hash' => hash('sha256', $key, true)]);
            if ($existing) {
                if ((int) $existing['quote_id'] !== (int) $quote['id']) {
                    throw new Problem('idempotency_conflict', 'This key belongs to a different request.', 409);
                }
                return $existing;
            }
            if ($quote['status'] !== 'issued' || $quote['expires_at'] <= $now->format('Y-m-d H:i:s') || $slot['status'] !== 'published') {
                throw new Problem('quote_expired', 'Refresh the quote before reserving inventory.', 409);
            }
            if ($this->store->overlaps((int) $slot['id'], $snapshot['start_at'], $snapshot['end_at'], $now->format('Y-m-d H:i:s'))) {
                throw new Problem('capacity_unavailable', 'This exclusive interval is no longer available.', 409);
            }
            $hold = $this->store->insert('inventory_holds', ['organization_id' => $org, 'quote_id' => $quote['id'],
                'starts_at' => $snapshot['start_at'], 'ends_at' => $snapshot['end_at'],
                'expires_at' => $now->modify('+' . $snapshot['hold_ttl_seconds'] . ' seconds')->format('Y-m-d H:i:s'),
                'status' => 'active', 'idempotency_hash' => hash('sha256', $key, true)]);
            $this->store->insert('hold_slot_lines', ['hold_id' => $hold['id'], 'slot_id' => $slot['id'], 'units' => 1,
                'starts_at' => $hold['starts_at'], 'ends_at' => $hold['ends_at']]);
            $this->audit->record($actor, 'hold.created', 'hold', $hold['public_id']);
            return $hold;
        });
    }

    public function complimentary(int $actor, array $campaign, string $reason): array
    {
        return $this->store->transaction(function () use ($actor, $campaign, $reason): array {
            $slot = $this->store->one('slots', ['id' => $campaign['slot_id']], true) ?? throw new Problem('not_found', 'Slot unavailable.', 404);
            if ($slot['status'] !== 'published' || $this->store->overlaps((int) $slot['id'], $campaign['start_at'], $campaign['end_at'], $this->clock->now()->format('Y-m-d H:i:s'))) {
                throw new Problem('capacity_unavailable', 'This interval has a hold or contract; manual allocation cannot preempt it.', 409);
            }
            $reservation = $this->store->insert('contract_reservations', ['organization_id' => $campaign['organization_id'], 'campaign_id' => $campaign['id'],
                'status' => 'active', 'contract_type' => 'complimentary', 'starts_at' => $campaign['start_at'], 'ends_at' => $campaign['end_at'],
                'contract_snapshot_json' => Input::json(['reason' => $reason, 'authorized_by' => $actor, 'consideration_minor' => '0'])]);
            $this->store->insert('reservation_slot_lines', ['reservation_id' => $reservation['id'], 'slot_id' => $slot['id'], 'units' => 1,
                'starts_at' => $campaign['start_at'], 'ends_at' => $campaign['end_at']]);
            $this->audit->record($actor, 'reservation.complimentary_granted', 'reservation', $reservation['public_id'], $reason);
            return $reservation;
        });
    }
}
