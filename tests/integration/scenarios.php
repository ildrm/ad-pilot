<?php
/** Run only in the isolated local fixture via wp eval-file. No production data. */
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('Integration scenarios require the isolated local WordPress fixture.');
}

use AdPilot\Core\Problem;
use AdPilot\Platform\Plugin;

require_once dirname(__DIR__) . '/php/assertions.php';
$services = Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$actor = get_current_user_id();
$inventory = $services['inventory'];
$org = $services['organizations']->create($actor, ['display_name' => 'Integration advertiser']);
$services['organizations']->setStatus($actor, $org['id'], 'verified', 'Synthetic local test');
$zone = $inventory->createZone($actor, ['name' => 'Integration header']);
$container = $inventory->createContainer($actor, ['zone_id' => $zone['id'], 'name' => 'Header', 'slot_count' => 4, 'columns' => 4]);
$draft = $services['layouts']->draft($actor, ['name' => 'Integration layout', 'zones' => [['zone_id' => $zone['id'], 'container_ids' => [$container['id']]]]]);
$published = $services['layouts']->publish($actor, $draft['id'], $draft['row_version']);
expect($published['status'] === 'published', 'layout publication writes immutable snapshot');
$slots = $services['store']->many('slots', ['zone_id' => $inventory->zone($zone['id'])['id']]);
expect(count($slots) === 4, 'compact container materializes stable sellable slots');
$creative = $services['creatives']->create($actor, $org['id'], ['name' => 'Test', 'title' => 'Local sponsor', 'body' => 'A safe native advertisement.', 'destination_url' => 'https://example.com']);
$services['creatives']->submit($actor, $org['id'], $creative['id']);
$services['creatives']->review($actor, $creative['id'], 'approved', 'Synthetic local test approval');
$campaign = $services['campaigns']->create($actor, $org['id'], ['name' => 'Local campaign', 'slot_id' => $slots[0]['public_id'], 'creative_id' => $creative['id'], 'start_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 60), 'end_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 3600)]);
expect($campaign['financial_status'] === 'unfunded', 'creating a campaign does not invent payment');
expectProblem(fn() => $services['campaigns']->complimentary($actor, $campaign['id'], 'test', false), 'policy_required', 'manual entitlement requires explicit operator policy');
$services['campaigns']->complimentary($actor, $campaign['id'], 'Synthetic local test entitlement', true);
$decision = $services['delivery']->decide([['slot_id' => $slots[0]['public_id'], 'render_instance_id' => 'test-render-1']], false);
expect(count($decision['decisions']) === 1 && $decision['decisions'][0]['status'] === 'selected', 'current approved entitled campaign selected');
$signal = $services['delivery']->signal($decision['decisions'][0]['decision_ref'], 'click', false);
expect($signal['destination_url'] === 'https://example.com' && $signal['billable'] === false, 'signed click revalidates current eligibility and never charges');
$services['campaigns']->action($actor, $campaign['id'], 'emergency-stop', 'Synthetic stop test');
$stopped = $services['delivery']->decide([['slot_id' => $slots[0]['public_id'], 'render_instance_id' => 'test-render-2']], false);
expect($stopped['decisions'][0]['status'] === 'empty', 'authoritative stop defeats previous decision cache');
expectProblem(fn() => $services['delivery']->signal($decision['decisions'][0]['decision_ref'], 'click', false), 'decision_revoked', 'a previously issued decision is revoked by emergency stop');
expectProblem(fn() => $services['organizations']->authorized(99999999, $org['id']), 'forbidden', 'cross-organization membership denied');
$request = new WP_REST_Request('GET', '/adpilot/v1/admin/zones');
wp_set_current_user(0);
expect(rest_get_server()->dispatch($request)->get_status() === 403, 'direct privileged REST call denied anonymously');
echo "Integration scenarios passed.\n";
