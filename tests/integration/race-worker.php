<?php
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local') { throw new RuntimeException('Local fixture only.'); }
$s = AdPilot\Platform\Plugin::services();
$fixture = json_decode(file_get_contents(ABSPATH . '../race-fixture.json'), true, 16, JSON_THROW_ON_ERROR);
$quote = $s['store']->one('quotes', ['public_id' => $fixture['quote']]);
try {
    $hold = $s['reservations']->hold($fixture['actor'], $fixture['org'], $quote, 'race-' . str_repeat((string) ($args[0] ?? 'a'), 24));
    echo 'WINNER ' . $hold['public_id'] . "\n";
} catch (AdPilot\Core\Problem $error) {
    if ($error->key !== 'capacity_unavailable') { throw $error; }
    echo "LOSER capacity_unavailable\n";
}
