<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$actor = get_current_user_id();
$orgView = $s['organizations']->create($actor, ['display_name' => 'Synthetic settlement fixture']);
$s['organizations']->setStatus($actor, $orgView['id'], 'verified', 'Synthetic test');
$org = $s['organizations']->authorized($actor, $orgView['id'], 'purchase');
$zone = $s['inventory']->createZone($actor, ['name' => 'Synthetic settlement slots']);
$container = $s['inventory']->createContainer($actor, ['zone_id' => $zone['id'], 'name' => 'Settlement', 'slot_count' => 2]);
$draft = $s['layouts']->draft($actor, ['name' => 'Settlement', 'zones' => [['zone_id' => $zone['id'], 'container_ids' => [$container['id']]]]]);
$s['layouts']->publish($actor, $draft['id'], $draft['row_version']);
$slots = $s['store']->many('slots', ['zone_id' => $s['inventory']->zone($zone['id'])['id']]);
foreach ($slots as $index => $slot) {
    $rate = $s['pricing']->publish($actor, ['slot_id' => $slot['public_id'], 'currency' => 'USD', 'unit_price_minor' => '1500',
        'parameters' => ['tax_policy' => 'explicitly_not_applicable', 'policy_reference' => 'SYNTHETIC_NOT_LEGAL_POLICY', 'quote_ttl_seconds' => 300, 'hold_ttl_seconds' => 120, 'currency_precision' => 2]]);
    $quoteView = $s['pricing']->quote($actor, $orgView['id'], ['rate_id' => $rate['id'], 'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400), 'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 172800)]);
    $quote = $s['store']->one('quotes', ['public_id' => $quoteView['id']]);
    expectProblem(fn() => $s['reservations']->hold($actor, 9999999, $quote, 'cross-tenant-key0000'), 'forbidden', 'hold application boundary rechecks quote organization');
    $key = 'synthetic-fixture-' . $quote['public_id'];
    $hold = $s['reservations']->hold($actor, (int) $org['id'], $quote, $key);
    $same = $s['reservations']->hold($actor, (int) $org['id'], $quote, $key);
    expect($hold['id'] === $same['id'], 'hold retries return one existing allocation');
    expectProblem(fn() => $s['reservations']->hold($actor, (int) $org['id'], $quote, $key . 'other'), 'capacity_unavailable', 'second key cannot oversell same interval');
    $order = $s['commerce']->createOrder($actor, (int) $org['id'], $hold);
    expect($s['commerce']->createOrder($actor, (int) $org['id'], $hold)['id'] === $order['id'], 'one hold maps to one order under retries');
    expectProblem(fn() => $s['commerce']->settleVerified($order, new AdPilot\Pricing\Money(1499, 'USD'), 'synthetic-' . $index), 'payment_mismatch', 'capture mismatch cannot allocate or post ledger');
    if ($index === 1) {
        $s['store']->update('inventory_holds', $hold, ['expires_at' => gmdate('Y-m-d H:i:s', time() - 60)]);
    }
    $captured = $s['commerce']->settleVerified($order, new AdPilot\Pricing\Money(1500, 'USD'), 'SYNTHETIC-' . $order['public_id']);
    $again = $s['commerce']->settleVerified($order, new AdPilot\Pricing\Money(1500, 'USD'), 'SYNTHETIC-' . $order['public_id']);
    expect($s['store']->count('financial_ledger_entries', ['order_id' => $order['id']]) === 1 && $again['id'] === $captured['id'], 'duplicate settlement produces one exact ledger fact');
    $contracts = $s['store']->count('contract_reservations', ['order_id' => $order['id']]);
    expect($index === 0 ? $contracts === 1 && $captured['status'] === 'paid' : $contracts === 0 && $captured['status'] === 'remediation_required', 'late capture records money without reviving inventory');
    if ($index === 0) {
        $creative = $s['creatives']->create($actor, $orgView['id'], ['name' => 'Synthetic paid workflow', 'title' => 'Test sponsor', 'body' => 'Synthetic test only.', 'destination_url' => 'https://example.com']);
        $s['creatives']->submit($actor, $orgView['id'], $creative['id']);
        $offer = json_decode($quote['offer_snapshot_json'], true);
        $campaignInput = ['name' => 'Synthetic paid campaign', 'slot_id' => $slot['public_id'], 'creative_id' => $creative['id'],
            'start_at' => str_replace(' ', 'T', $offer['start_at']) . 'Z', 'end_at' => str_replace(' ', 'T', $offer['end_at']) . 'Z'];
        $campaign = $s['campaigns']->create($actor, $orgView['id'], $campaignInput);
        expectProblem(fn() => $s['campaigns']->allocatePaid($actor, $campaign['id'], $order['public_id'], 'Synthetic allocation'), 'approval_required', 'settled payment cannot approve an unreviewed creative');
        $s['creatives']->review($actor, $creative['id'], 'approved', 'Synthetic exact-version review');
        $active = $s['campaigns']->allocatePaid($actor, $campaign['id'], $order['public_id'], 'Synthetic paid entitlement mapping');
        expect($active['financial_status'] === 'paid' && $active['approval_status'] === 'approved', 'paid allocation activates only after separate exact-version approval');
        $duplicate = $s['campaigns']->create($actor, $orgView['id'], $campaignInput);
        expectProblem(fn() => $s['campaigns']->allocatePaid($actor, $duplicate['id'], $order['public_id'], 'Synthetic duplicate'), 'allocation_mismatch', 'exclusive paid entitlement cannot activate a second campaign');
    }
    $ledger = $s['store']->one('financial_ledger_entries', ['order_id' => $order['id']]);
    expectProblem(fn() => $s['store']->update('financial_ledger_entries', $ledger, ['amount_minor' => 0]), 'immutable_record', 'financial ledger rejects mutable correction');
    expectProblem(fn() => $s['woo']->start($order, $actor), 'checkout_unavailable', 'uncertified Woo checkout remains unavailable');
}
echo "Synthetic settlement application checks passed; no real gateway certification claimed.\n";
