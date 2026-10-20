<?php
namespace AdPilot\Pricing;

use AdPilot\Core\{Audit, Clock, Ids, Input, Problem, Store};
use AdPilot\Identity\Organizations;
use AdPilot\Inventory\Inventory;

final class Pricing
{
    public function __construct(private readonly Store $store, private readonly Organizations $organizations,
        private readonly Inventory $inventory, private readonly Audit $audit, private readonly Clock $clock)
    {
    }

    public function publish(int $actor, array $input): array
    {
        $slot = $this->inventory->slot(Input::text($input, 'slot_id', 26));
        $money = Money::parse($input['unit_price_minor'] ?? null, Input::text($input, 'currency', 3));
        $policy = $input['parameters'] ?? null;
        if (!is_array($policy) || ($policy['tax_policy'] ?? '') !== 'explicitly_not_applicable' || empty($policy['policy_reference'])) {
            throw new Problem('tax_policy_required', 'This fixed-price adapter requires an explicit not-applicable tax policy reference. Other jurisdictions need an approved tax adapter.', 409);
        }
        $parameters = ['tax_policy' => $policy['tax_policy'], 'policy_reference' => Input::text($policy, 'policy_reference'),
            'quote_ttl_seconds' => Input::integer($policy, 'quote_ttl_seconds', 60, 3600),
            'hold_ttl_seconds' => Input::integer($policy, 'hold_ttl_seconds', 60, 3600),
            'currency_precision' => Input::integer($policy, 'currency_precision', 0, 4), 'unit' => 'fixed_window'];
        return $this->store->transaction(function () use ($actor, $slot, $money, $parameters): array {
            $now = $this->clock->now()->format('Y-m-d H:i:s');
            $rate = $this->store->insert('rate_card_versions', ['slot_id' => $slot['id'], 'rate_key' => Ids::make(), 'version_number' => 1,
                'model' => 'fixed', 'currency' => $money->currency, 'unit_price_minor' => $money->minor, 'parameters_json' => Input::json($parameters),
                'effective_start_at' => $now, 'published_at' => $now]);
            $this->audit->record($actor, 'rate.published', 'rate', $rate['public_id']);
            return self::rateView($rate) + ['slot_id' => $slot['public_id']];
        });
    }

    public function quote(int $actor, string $orgId, array $input): array
    {
        $org = $this->organizations->authorized($actor, $orgId, 'purchase');
        if ($org['status'] !== 'verified') { throw new Problem('verification_required', 'Organization verification is required before purchasing.', 409); }
        $rate = $this->store->one('rate_card_versions', ['public_id' => Input::text($input, 'rate_id', 26)]) ?? throw new Problem('not_found', 'Published rate unavailable.', 404);
        $slot = $this->store->one('slots', ['id' => $rate['slot_id'], 'status' => 'published']) ?? throw new Problem('not_found', 'Published slot unavailable.', 404);
        $now = $this->clock->now();
        if (!$rate['published_at'] || $rate['effective_start_at'] > $now->format('Y-m-d H:i:s') || ($rate['effective_end_at'] && $rate['effective_end_at'] <= $now->format('Y-m-d H:i:s'))) {
            throw new Problem('rate_unavailable', 'This rate is not currently effective.', 409);
        }
        $start = Input::date($input, 'start_at'); $end = Input::date($input, 'end_at');
        if ($start >= $end || $start < $now->format('Y-m-d H:i:s')) { throw new Problem('invalid_schedule', 'Choose a future half-open booking interval.'); }
        $parameters = json_decode($rate['parameters_json'], true, 16, JSON_THROW_ON_ERROR);
        $snapshot = ['rate_id' => $rate['public_id'], 'slot_id' => $slot['public_id'], 'slot_internal_id' => (int) $slot['id'],
            'start_at' => $start, 'end_at' => $end, 'hold_ttl_seconds' => $parameters['hold_ttl_seconds'], 'terms' => $parameters];
        return $this->store->transaction(function () use ($actor, $orgId, $rate, $snapshot, $parameters): array {
            $org = $this->organizations->authorized($actor, $orgId, 'purchase', true);
            if ($org['status'] !== 'verified') { throw new Problem('verification_required', 'Organization verification is required before purchasing.', 409); }
            $now = $this->clock->now();
            $quote = $this->store->insert('quotes', ['organization_id' => $org['id'], 'rate_card_version_id' => $rate['id'],
                'currency' => $rate['currency'], 'subtotal_minor' => $rate['unit_price_minor'], 'total_minor' => $rate['unit_price_minor'],
                'offer_snapshot_json' => Input::json($snapshot), 'terms_digest' => hash('sha256', Input::json($snapshot), true),
                'expires_at' => $now->modify('+' . $parameters['quote_ttl_seconds'] . ' seconds')->format('Y-m-d H:i:s'), 'status' => 'issued']);
            $this->audit->record($actor, 'quote.issued', 'quote', $quote['public_id']);
            return self::quoteView($quote);
        });
    }

    public static function rateView(array $rate): array
    {
        return ['id' => $rate['public_id'], 'currency' => $rate['currency'], 'unit_price_minor' => (string) $rate['unit_price_minor'],
            'model' => $rate['model'], 'parameters' => json_decode($rate['parameters_json'], true), 'published_at' => $rate['published_at']];
    }

    public static function quoteView(array $quote): array
    {
        $offer = json_decode($quote['offer_snapshot_json'], true, 32, JSON_THROW_ON_ERROR);
        unset($offer['slot_internal_id']);
        return ['id' => $quote['public_id'], 'currency' => $quote['currency'], 'total_minor' => (string) $quote['total_minor'],
            'subtotal_minor' => (string) $quote['subtotal_minor'], 'tax_minor' => (string) $quote['tax_minor'], 'discount_minor' => (string) $quote['discount_minor'],
            'expires_at' => $quote['expires_at'], 'status' => $quote['status'], 'offer' => $offer];
    }
}
