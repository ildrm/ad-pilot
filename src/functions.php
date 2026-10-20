<?php
/** Shared, policy-aware public theme facade; these helpers return escaped markup. */
function adpilot_render_slot(string $slot_id, array $options = []): string
{
    return AdPilot\Platform\Plugin::safeRender('slot', $slot_id, !empty($options['preview']));
}
function adpilot_render_zone(string $zone_id, array $options = []): string
{
    return AdPilot\Platform\Plugin::safeRender('zone', $zone_id, !empty($options['preview']));
}
function adpilot_render_container(string $container_id, array $options = []): string
{
    return AdPilot\Platform\Plugin::safeRender('container', $container_id, !empty($options['preview']));
}
