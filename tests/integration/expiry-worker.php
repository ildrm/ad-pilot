<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
$fixturePath = dirname(ABSPATH) . '/expiry-fixture.json';
$readyPath = dirname(ABSPATH) . '/expiry-lock-ready';
$mode = $args[0] ?? '';
if ($mode === 'prepare') {
    wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
    $actor = get_current_user_id();
    $org = $s['organizations']->create($actor, ['display_name' => 'Synthetic quote lock expiry']);
    $s['organizations']->setStatus($actor, $org['id'], 'verified', 'Synthetic only');
    $orgRow = $s['organizations']->authorized($actor, $org['id']);
    $zone = $s['inventory']->createZone($actor, ['name' => 'Synthetic quote lock expiry']);
    $container = $s['inventory']->createContainer($actor, ['zone_id' => $zone['id'], 'name' => 'Expiry', 'slot_count' => 1]);
    $draft = $s['layouts']->draft($actor, ['name' => 'Expiry', 'zones' => [['zone_id' => $zone['id'], 'container_ids' => [$container['id']]]]]);
    $s['layouts']->publish($actor, $draft['id'], $draft['row_version']);
    $slot = $s['store']->one('slots', ['zone_id' => $s['inventory']->zone($zone['id'])['id']]);
    $rate = $s['pricing']->publish($actor, ['slot_id' => $slot['public_id'], 'currency' => 'USD', 'unit_price_minor' => '1000',
        'parameters' => ['tax_policy' => 'explicitly_not_applicable', 'policy_reference' => 'SYNTHETIC_ONLY', 'quote_ttl_seconds' => 60, 'hold_ttl_seconds' => 60, 'currency_precision' => 2]]);
    $quote = $s['pricing']->quote($actor, $org['id'], ['rate_id' => $rate['id'], 'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400), 'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 172800)]);
    // Shorten only this synthetic fixture using SQL; application quotes stay immutable.
    global $wpdb;
    $wpdb->update($wpdb->prefix . 'adpilot_quotes', ['expires_at' => gmdate('Y-m-d H:i:s', time() + 4)], ['public_id' => $quote['id'], 'site_id' => get_current_blog_id()]);
    file_put_contents($fixturePath, json_encode(['actor' => $actor, 'organization' => $orgRow['id'], 'slot' => $slot['id'], 'quote' => $quote['id']]));
    if (is_file($readyPath)) { unlink($readyPath); }
} elseif ($mode === 'lock') {
    $fixture = json_decode(file_get_contents($fixturePath), true);
    $s['store']->transaction(function () use ($s, $fixture, $readyPath): void {
        $s['store']->one('slots', ['id' => $fixture['slot']], true);
        file_put_contents($readyPath, 'locked');
        usleep(6000000);
    });
} elseif ($mode === 'contend') {
    $fixture = json_decode(file_get_contents($fixturePath), true);
    $quote = $s['store']->one('quotes', ['public_id' => $fixture['quote']]);
    expectProblem(fn() => $s['reservations']->hold($fixture['actor'], $fixture['organization'], $quote, 'expiry-lock-' . $fixture['quote']), 'quote_expired', 'quote expiring during a real row-lock wait cannot acquire capacity');
    expect($s['store']->count('inventory_holds', ['quote_id' => $quote['id']]) === 0, 'expired lock contender leaves no hold behind');
} else {
    throw new RuntimeException('Expected prepare, lock or contend.');
}
