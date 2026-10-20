<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$actor = get_current_user_id();
$org = $s['organizations']->create($actor, ['display_name' => 'Synthetic immutable versions']);
$input = ['name' => 'Synthetic version', 'title' => 'First version', 'body' => 'Synthetic test only.', 'destination_url' => 'https://example.com/first'];
$original = $s['creatives']->create($actor, $org['id'], $input);
$s['creatives']->submit($actor, $org['id'], $original['id']);
$s['creatives']->review($actor, $original['id'], 'approved', 'Synthetic exact-version review');
$row = $s['store']->one('creative_versions', ['public_id' => $original['id']]);
expectProblem(fn() => $s['store']->update('creative_versions', $row, ['content_json' => '{}']), 'immutable_record', 'approved creative bytes cannot be overwritten through storage');
$request = new WP_REST_Request('POST', '/adpilot/v1/organizations/' . $org['id'] . '/creatives/' . $original['id'] . '/versions');
$request->set_header('Content-Type', 'application/json');
$request->set_body(json_encode(array_replace($input, ['destination_url' => 'https://example.com/second'])));
$response = rest_get_server()->dispatch($request);
$second = $response->get_data();
expect($response->get_status() === 200 && $second['version'] === 2 && $second['status'] === 'draft' && $second['digest'] !== $original['digest'], 'changing destination creates a distinct unapproved version through REST');
expect($s['store']->one('creative_versions', ['public_id' => $original['id']])['content_digest'] === $row['content_digest'], 'successor preserves prior approved bytes and digest');
expectProblem(fn() => $s['creatives']->review($actor, $second['id'], 'approved', 'Stale review'), 'review_conflict', 'a new draft cannot inherit or bypass submission approval');
$s['creatives']->submit($actor, $org['id'], $second['id']);
$approved = $s['creatives']->review($actor, $second['id'], 'approved', 'Synthetic new-digest review');
expect($approved['status'] === 'approved' && $s['store']->count('creative_reviews', ['creative_version_id' => $row['id']]) === 1, 'each version has independent exact-digest review history');
$third = $s['creatives']->revise($actor, $org['id'], $original['id'], $input);
expect($third['version'] === 3 && $third['status'] === 'draft', 'editing an older version still advances the immutable family sequence');
$other = $s['organizations']->create($actor, ['display_name' => 'Synthetic version isolation']);
expectProblem(fn() => $s['creatives']->revise($actor, $other['id'], $original['id'], $input), 'not_found', 'creative version reference cannot cross organization scope');
$zone = $s['inventory']->createZone($actor, ['name' => 'Synthetic unpublished catalog']);
$container = $s['inventory']->createContainer($actor, ['zone_id' => $zone['id'], 'name' => 'Unpublished', 'slot_count' => 1]);
$slot = $s['store']->one('slots', ['container_id' => $s['store']->one('containers', ['public_id' => $container['id']])['id']]);
$rate = $s['pricing']->publish($actor, ['slot_id' => $slot['public_id'], 'currency' => 'USD', 'unit_price_minor' => '1500',
    'parameters' => ['tax_policy' => 'explicitly_not_applicable', 'policy_reference' => 'SYNTHETIC_POLICY_ONLY', 'quote_ttl_seconds' => 300, 'hold_ttl_seconds' => 120, 'currency_precision' => 2]]);
$request = new WP_REST_Request('GET', '/adpilot/v1/catalog/packages');
$request->set_param('slot_id', $slot['public_id']);
expect(rest_get_server()->dispatch($request)->get_status() === 404, 'unpublished placement has no public package endpoint');
expect($s['store']->count('rate_card_versions', ['public_id' => $rate['id'], '_catalog' => true]) === 0, 'unfiltered catalog also excludes rates on unpublished inventory');
$quote = $s['store']->one('quotes', ['status' => 'issued']);
if ($quote) { expectProblem(fn() => $s['store']->update('quotes', $quote, ['total_minor' => 1]), 'immutable_record', 'issued quote money cannot be overwritten'); }
echo "Immutable revision and public catalog checks passed.\n";
