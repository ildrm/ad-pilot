<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
require_once dirname(__DIR__) . '/php/assertions.php';
$s = AdPilot\Platform\Plugin::services();
wp_set_current_user(get_user_by('login', 'adpilot-dev')->ID);
$actor = get_current_user_id();
$zones = []; $containers = []; $shortcodes = [];
foreach (['Header' => 4, 'Sidebar' => 100, 'Content' => 196, 'Footer' => 200] as $name => $count) {
    $zone = $s['inventory']->createZone($actor, ['name' => 'Synthetic 500 / ' . $name]);
    $container = $s['inventory']->createContainer($actor, ['zone_id' => $zone['id'], 'name' => $name, 'slot_count' => $count, 'columns' => $name === 'Sidebar' ? 1 : 4]);
    $zones[] = ['zone_id' => $zone['id'], 'container_ids' => [$container['id']]];
    $containers[] = $container;
    $shortcodes[] = '<h2>' . esc_html($name) . '</h2>[adpilot_container id="' . $container['id'] . '"]';
}
$draft = $s['layouts']->draft($actor, ['name' => 'Synthetic 4 / 100 / 196 / 200', 'zones' => $zones]);
expect($s['layouts']->validate($draft['id'])['slot_count'] === 500, '4+100+196+200 logical-slot layout validates');
$s['layouts']->publish($actor, $draft['id'], $draft['row_version']);
$before = $GLOBALS['wpdb']->num_queries;
$html = '';
foreach ($containers as $container) { $html .= adpilot_render_container($container['id']); }
$queries = $GLOBALS['wpdb']->num_queries - $before;
expect(substr_count($html, 'data-adpilot-slot=') === 500, 'shared renderer emits 500 lightweight placeholders');
expect($queries < 30, 'render query count is bounded by containers, not 500 slots [' . $queries . ']');
expect(!str_contains($html, '<img') && !str_contains($html, '<iframe'), 'configured slots do not eagerly download creatives');
$a = adpilot_render_container($containers[0]['id']); $b = adpilot_render_container($containers[0]['id']);
preg_match_all('/id="(adp-[^"]+)"/', $a . $b, $ids);
expect(count(array_unique($ids[1])) === 8, 'repeated container occurrences have distinct DOM identities');
expectProblem(fn() => $s['inventory']->updateContainer($actor, $containers[0]['id'], ['row_version' => 2, 'columns' => 2]), 'published_container_immutable', 'published geometry cannot bypass contract-impact review');
$page = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'AdPilot synthetic 500-slot fixture', 'post_content' => implode("\n", $shortcodes)]);
$portal = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'AdPilot local advertiser portal', 'post_content' => '[adpilot_portal]']);
file_put_contents(ABSPATH . '../preview-pages.json', json_encode(['density' => get_permalink($page), 'portal' => get_permalink($portal), 'draft_id' => $draft['id']], JSON_THROW_ON_ERROR));
echo 'Density fixture URL: ' . get_permalink($page) . "\n";
echo 'Portal URL: ' . get_permalink($portal) . "\n";
