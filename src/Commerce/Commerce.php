<?php
namespace AdPilot\Commerce;

use AdPilot\Core\{Audit, Clock, Input, Problem, Store};
use AdPilot\Pricing\Money;

final class Commerce
{
    public function __construct(private readonly Store $store, private readonly Audit $audit, private readonly Clock $clock)
    {
    }

    public function createOrder(int $actor, int $organization, array $hold): array
    {
        if ((int) $hold['organization_id'] !== $organization) { throw new Problem('forbidden', 'Hold belongs to a different organization.', 403); }
        return $this->store->transaction(function () use ($actor, $organization, $hold): array {
            $line = $this->store->one('hold_slot_lines', ['hold_id' => $hold['id']]) ?? throw new Problem('hold_invalid', 'Hold has no inventory line.', 409);
            $this->store->one('slots', ['id' => $line['slot_id']], true);
            $hold = $this->store->one('inventory_holds', ['id' => $hold['id']], true);
            $existing = $this->store->one('advertising_orders', ['hold_id' => $hold['id']]);
            if ($existing) { return $existing; }
            if ($hold['status'] !== 'active' || $hold['expires_at'] <= $this->clock->now()->format('Y-m-d H:i:s')) {
                throw new Problem('hold_expired', 'Acquire a current hold before creating an order.', 409);
            }
            $quote = $this->store->one('quotes', ['id' => $hold['quote_id'], 'organization_id' => $organization]) ?? throw new Problem('quote_unavailable', 'Quote unavailable.', 409);
            $order = $this->store->insert('advertising_orders', ['organization_id' => $organization, 'hold_id' => $hold['id'], 'quote_id' => $quote['id'],
                'currency' => $quote['currency'], 'total_minor' => $quote['total_minor'], 'status' => 'pending_payment']);
            $this->audit->record($actor, 'order.created', 'order', $order['public_id']);
            return $order;
        });
    }

    /** Called only by the trusted Woo adapter, never a browser/payment-return route. */
    public function settleVerified(array $order, Money $captured, string $providerReference): array
    {
        if ($captured->currency !== $order['currency'] || $captured->minor !== (int) $order['total_minor'] || $providerReference === '') {
            throw new Problem('payment_mismatch', 'Verified payment amount/currency does not match the immutable order.', 409);
        }
        return $this->store->transaction(function () use ($order, $captured, $providerReference): array {
            $line = $this->store->one('hold_slot_lines', ['hold_id' => $order['hold_id']]) ?? throw new Problem('hold_invalid', 'Order hold has no inventory line.', 409);
            $this->store->one('slots', ['id' => $line['slot_id']], true);
            $hold = $this->store->one('inventory_holds', ['id' => $order['hold_id']], true) ?? throw new Problem('hold_invalid', 'Order hold unavailable.', 409);
            $current = $this->store->one('advertising_orders', ['id' => $order['id']], true) ?? throw new Problem('not_found', 'Order unavailable.', 404);
            if (in_array($current['status'], ['paid', 'remediation_required'], true)) { return $current; }
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $this->store->insert('financial_ledger_entries', ['organization_id' => $current['organization_id'], 'order_id' => $current['id'], 'currency' => $captured->currency,
                'amount_minor' => $captured->minor, 'entry_type' => 'capture', 'source_idempotency_hash' => hash('sha256', 'woo:' . $providerReference, true), 'posted_at' => $now]);
            // Capture is a money fact even when fulfillment needs a remedy.
            if ($hold['status'] !== 'active' || $hold['expires_at'] <= $now) {
                $current = $this->store->update('advertising_orders', $current, ['status' => 'remediation_required', 'paid_minor' => $captured->minor]);
                $this->audit->record(0, 'order.remediation_required', 'order', $current['public_id'], 'Verified capture arrived without a current hold; no inventory was allocated.');
                return $current;
            }
            $reservation = $this->store->insert('contract_reservations', ['organization_id' => $current['organization_id'], 'order_id' => $current['id'],
                'status' => 'active', 'contract_type' => 'paid', 'starts_at' => $hold['starts_at'], 'ends_at' => $hold['ends_at'],
                'contract_snapshot_json' => Input::json(['quote_id' => $current['quote_id'], 'order_public_id' => $current['public_id'], 'total_minor' => (string) $captured->minor, 'currency' => $captured->currency])]);
            $this->store->insert('reservation_slot_lines', ['reservation_id' => $reservation['id'], 'slot_id' => $line['slot_id'], 'units' => 1,
                'starts_at' => $hold['starts_at'], 'ends_at' => $hold['ends_at']]);
            $this->store->update('inventory_holds', $hold, ['status' => 'consumed', 'consumed_at' => $now]);
            $current = $this->store->update('advertising_orders', $current, ['status' => 'paid', 'paid_minor' => $captured->minor]);
            // No creative approval or campaign activation occurs on payment.
            $this->audit->record(0, 'order.paid', 'order', $current['public_id']);
            return $current;
        });
    }

    public static function view(array $row): array
    {
        return ['id' => $row['public_id'], 'status' => $row['status'], 'currency' => $row['currency'],
            'total_minor' => (string) $row['total_minor'], 'paid_minor' => (string) $row['paid_minor'], 'row_version' => (int) $row['row_version']];
    }
}
