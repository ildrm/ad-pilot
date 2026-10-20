<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$actor = get_current_user_id();
$org = $s['organizations']->create($actor, ['display_name' => 'Isolation fixture A']);
$other = $s['organizations']->create($actor, ['display_name' => 'Isolation fixture B']);
$user = wp_insert_user(['user_login' => 'adpilot-analyst-' . strtolower(substr(AdPilot\Core\Ids::make(), -8)), 'user_pass' => wp_generate_password(32), 'role' => 'subscriber']);
$orgRow = $s['organizations']->authorized($actor, $org['id']);
$member = $s['store']->insert('organization_memberships', ['organization_id' => $orgRow['id'], 'user_id' => $user, 'role_key' => 'analyst', 'status' => 'active']);
wp_set_current_user($user);
$get = static function (string $path, string $method = 'GET', array $body = []) {
    $r = new WP_REST_Request($method, '/adpilot/v1/' . $path);
    if ($body) { $r->set_header('Content-Type', 'application/json'); $r->set_body(json_encode($body)); }
    return rest_get_server()->dispatch($r);
};
expect($get('organizations/' . $org['id'] . '/campaigns')->get_status() === 200, 'analyst reads own scoped campaign projection');
expect($get('organizations/' . $other['id'] . '/campaigns')->get_status() === 403, 'direct other-organization list denied');
expect($get('organizations/' . $org['id'] . '/orders')->get_status() === 403, 'analyst has no implicit billing grant');
expect($get('admin/settings', 'PATCH', ['row_version' => 1, 'settings' => ['emergency_stop' => false]])->get_status() === 403, 'subscriber cannot mutate privileged settings');
$s['store']->update('organization_memberships', $member, ['status' => 'revoked']);
expect($get('organizations/' . $org['id'] . '/campaigns')->get_status() === 403, 'revoked membership rejected without cached grant');
wp_set_current_user($actor);
$response = $get('editor/placement-config/validate', 'POST', ['block' => 'adpilot/ad-slot', 'attributes' => ['slotId' => str_repeat('0', 26)]]);
expect($response->get_status() === 404, 'unknown editor slot reference is rejected');
$response = $get('admin/slots?search=%27%20OR%201=1');
// REST internal requests need query parameters separately; SQL escaping tested below.
expect($s['store']->many('slots', ['_search' => "' OR 1=1 --"], 10) === [], 'search literals cannot inject SQL');
$token = $s['tokens']->issue(['nonce' => 'fixture']);
expectProblem(fn() => $s['tokens']->read($token . 'x'), 'invalid_decision', 'tampered delivery signature rejected');
expectProblem(fn() => $s['store']->insert('zones', ['site_id' => 2, 'name' => 'X']), 'untrusted_scope', 'caller cannot choose site scope');
expectProblem(fn() => $s['store']->one('slots', ['id' => 1], true), 'lock_without_transaction', 'row lock requires transaction owner');
foreach (['admin/heatmaps/pages', 'admin/replay/sessions', 'network/sites', 'admin/metered-reconciliation'] as $path) {
    expect($get($path)->get_status() === 404, 'later release backend route unavailable: ' . $path);
}
echo "Security integration checks passed.\n";
