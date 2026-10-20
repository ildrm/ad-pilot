<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$before = $s['store']->count('slots');
$migrations = $s['store']->count('schema_migrations');
AdPilot\Platform\Schema::migrate($GLOBALS['wpdb']);
AdPilot\Platform\Schema::migrate($GLOBALS['wpdb']);
expect($s['store']->count('slots') === $before && $s['store']->count('schema_migrations') === $migrations, 'migration reruns preserve inventory and a single checksum record');
$observed = [];
$listener = static function (array $event) use (&$observed): void {
    if (($event['schema_version'] ?? null) !== 1 || $event['site_id'] !== get_current_blog_id()) { throw new RuntimeException('Untrusted event'); }
    $observed[] = $event['event_id'];
};
add_action('adpilot_domain_event', $listener);
$s['inventory']->createZone(get_current_user_id(), ['name' => 'Synthetic outbox operation']);
$result = $s['outbox']->drain(100);
expect($result['dispatched'] > 0 && count($observed) > 0, 'committed schema-versioned outbox events dispatch through local bus');
expect(count(array_unique($observed)) === count($observed), 'one claim dispatches each observed event once per run');
remove_action('adpilot_domain_event', $listener);
AdPilot\Platform\Plugin::deactivate();
expect($s['store']->count('slots') === $before && !wp_next_scheduled('adpilot_maintenance'), 'deactivation retains business records and clears only owned job');
AdPilot\Platform\Plugin::activate();
expect((bool) wp_next_scheduled('adpilot_maintenance'), 'reactivation restores scheduler without destructive migration');
echo "Operations checks passed.\n";
