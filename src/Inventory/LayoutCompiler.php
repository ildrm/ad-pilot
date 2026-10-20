<?php
namespace AdPilot\Inventory;

use AdPilot\Core\Input;
use AdPilot\Core\Problem;

final class LayoutCompiler
{
    public static function container(array $input, int $quota = 5000): array
    {
        $count = Input::integer($input, 'slot_count', 1, $quota);
        $columns = Input::integer($input, 'columns', 1, 24, 4);
        $gap = Input::integer($input, 'gap', 0, 64, 12);
        $ratio = $input['aspect_ratio'] ?? '4/1';
        if (!is_string($ratio) || !preg_match('/^(?:[1-9]|[1-9]\d)\/(?:[1-9]|[1-9]\d)$/D', $ratio)) {
            throw new Problem('invalid_ratio', 'Aspect ratio must be two positive integers separated by /.');
        }
        return ['slot_count' => $count, 'columns' => $columns, 'gap' => $gap, 'aspect_ratio' => $ratio,
            'tablet_columns' => Input::integer($input, 'tablet_columns', 1, 12, min(2, $columns)),
            'mobile_columns' => Input::integer($input, 'mobile_columns', 1, 6, 1)];
    }

    public static function stableSlot(string $container, int $ordinal): string
    {
        return strtoupper(substr(hash('sha256', $container . ':' . $ordinal), 0, 26));
    }

    public static function validate(array $definition): array
    {
        if (!isset($definition['zones']) || !is_array($definition['zones']) || count($definition['zones']) > 100) {
            throw new Problem('invalid_layout', 'A layout requires at most 100 zone references.');
        }
        $seen = [];
        foreach ($definition['zones'] as $zone) {
            if (!is_array($zone)) { throw new Problem('invalid_layout', 'Every zone reference must be an object.'); }
            $id = Input::text($zone, 'zone_id', 26);
            if (isset($seen[$id]) || !isset($zone['container_ids']) || !is_array($zone['container_ids']) || count($zone['container_ids']) > 100) {
                throw new Problem('invalid_layout', 'Zone references must be unique and containers bounded.');
            }
            $seen[$id] = true;
            foreach ($zone['container_ids'] as $container) {
                if (!is_string($container) || !preg_match('/^[A-Z0-9]{26}$/D', $container)) {
                    throw new Problem('invalid_layout', 'Invalid container reference.');
                }
            }
            if (count(array_unique($zone['container_ids'])) !== count($zone['container_ids'])) {
                throw new Problem('invalid_layout', 'Duplicate container reference.');
            }
        }
        Input::json($definition);
        return $definition;
    }
}
