<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$actor = get_current_user_id();
$orgView = $s['organizations']->create($actor, ['display_name' => 'Reservation race fixture']);
$s['organizations']->setStatus($actor, $orgView['id'], 'verified', 'Synthetic race fixture');
$org = $s['organizations']->authorized($actor, $orgView['id'], 'purchase');
$zone = $s['inventory']->createZone($actor, ['name' => 'Reservation race slot']);
$container = $s['inventory']->createContainer($actor, ['zone_id' => $zone['id'], 'name' => 'Race slot', 'slot_count' => 1]);
$draft = $s['layouts']->draft($actor, ['name' => 'Race', 'zones' => [['zone_id' => $zone['id'], 'container_ids' => [$container['id']]]]]);
$s['layouts']->publish($actor, $draft['id'], $draft['row_version']);
$slot = $s['store']->one('slots', ['zone_id' => $s['inventory']->zone($zone['id'])['id']]);
$rate = $s['pricing']->publish($actor, ['slot_id' => $slot['public_id'], 'currency' => 'USD', 'unit_price_minor' => '1500',
    'parameters' => ['tax_policy' => 'explicitly_not_applicable', 'policy_reference' => 'SYNTHETIC_TEST_ONLY', 'quote_ttl_seconds' => 300, 'hold_ttl_seconds' => 120, 'currency_precision' => 2]]);
$intent = ['rate_id' => $rate['id'], 'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400), 'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 172800)];
$quoteView = $s['pricing']->quote($actor, $orgView['id'], $intent);
$quote = $s['store']->one('quotes', ['public_id' => $quoteView['id']]);
$fixture = ['actor' => $actor, 'org' => (int) $org['id'], 'quote' => $quoteView['id']];
file_put_contents(ABSPATH . '../race-fixture.json', json_encode($fixture, JSON_THROW_ON_ERROR));
expect($quoteView['total_minor'] === '1500', 'authoritative fixed-window quote uses exact server rate');
expect($s['store']->transactional(), 'real reservation tables use InnoDB');
echo "Race fixture ready.\n";
