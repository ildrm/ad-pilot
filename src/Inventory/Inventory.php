<?php
namespace AdPilot\Inventory;

use AdPilot\Core\{Audit, Ids, Input, Problem, Store};

final class Inventory
{
    public function __construct(private readonly Store $store, private readonly Audit $audit)
    {
    }

    public function zone(string $id): array
    {
        return $this->store->one('zones', ['public_id' => $id]) ?? throw new Problem('not_found', 'Zone unavailable.', 404);
    }

    public function slot(string $id, bool $lock = false): array
    {
        return $this->store->one('slots', ['public_id' => $id], $lock) ?? throw new Problem('not_found', 'Slot unavailable.', 404);
    }

    public function createZone(int $actor, array $input): array
    {
        $name = Input::text($input, 'name');
        $description = Input::text($input, 'description', 2000, false);
        return $this->store->transaction(function () use ($actor, $name, $description): array {
            $row = $this->store->insert('zones', ['zone_key' => Ids::make(), 'name' => $name, 'description' => $description, 'status' => 'draft']);
            $this->audit->record($actor, 'zone.created', 'zone', $row['public_id']);
            return self::zoneView($row);
        });
    }

    public function updateZone(int $actor, string $id, array $input): array
    {
        return $this->store->transaction(function () use ($actor, $id, $input): array {
            $zone = $this->store->one('zones', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Zone unavailable.', 404);
            $changes = [];
            foreach (['name' => 191, 'description' => 2000] as $field => $length) {
                if (array_key_exists($field, $input)) {
                    $changes[$field] = Input::text($input, $field, $length, $field === 'name');
                }
            }
            $row = $this->store->update('zones', $zone, $changes, Input::integer($input, 'row_version', 1, PHP_INT_MAX));
            $this->audit->record($actor, 'zone.updated', 'zone', $id);
            return self::zoneView($row);
        });
    }

    public function createContainer(int $actor, array $input): array
    {
        $zone = $this->zone(Input::text($input, 'zone_id', 26));
        $definition = LayoutCompiler::container($input);
        $definition['name'] = Input::text($input + ['name' => 'Container'], 'name');
        return $this->store->transaction(function () use ($actor, $zone, $definition): array {
            $container = $this->store->insert('containers', ['zone_id' => $zone['id'], 'container_key' => Ids::make(), 'layout_type' => 'grid',
                'generator_json' => Input::json($definition), 'responsive_json' => Input::json($definition), 'status' => 'draft']);
            $this->materialize($container, $definition['slot_count']);
            $this->audit->record($actor, 'container.created', 'container', $container['public_id']);
            return $this->containerView($container, $zone);
        });
    }

    private function materialize(array $container, int $count): void
    {
        for ($ordinal = 1; $ordinal <= $count; ++$ordinal) {
            $id = LayoutCompiler::stableSlot($container['public_id'], $ordinal);
            $this->store->insert('slots', ['public_id' => $id, 'slot_key' => $id, 'zone_id' => $container['zone_id'],
                'container_id' => $container['id'], 'ordinal' => $ordinal, 'status' => 'draft']);
        }
    }

    public function updateContainer(int $actor, string $id, array $input): array
    {
        return $this->store->transaction(function () use ($actor, $id, $input): array {
            $row = $this->store->one('containers', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Container unavailable.', 404);
            if ($row['status'] === 'published') {
                throw new Problem('published_container_immutable', 'Published geometry requires a new layout draft and contract-impact review.', 409);
            }
            $old = json_decode($row['generator_json'], true, 32, JSON_THROW_ON_ERROR);
            $definition = LayoutCompiler::container($input + $old);
            if ($definition['slot_count'] !== $old['slot_count']) {
                throw new Problem('slot_identity_change', 'Create a new container to change its materialized sequence length.', 409);
            }
            $definition['name'] = Input::text($input + $old, 'name');
            $row = $this->store->update('containers', $row, ['generator_json' => Input::json($definition), 'responsive_json' => Input::json($definition)], Input::integer($input, 'row_version', 1, PHP_INT_MAX));
            $this->audit->record($actor, 'container.updated', 'container', $id);
            return $this->containerView($row);
        });
    }

    public function containerView(array $row, ?array $zone = null): array
    {
        $zone ??= $this->store->one('zones', ['id' => $row['zone_id']]);
        return json_decode($row['generator_json'], true, 32, JSON_THROW_ON_ERROR) + ['id' => $row['public_id'],
            'zone_id' => $zone['public_id'] ?? null, 'status' => $row['status'], 'row_version' => (int) $row['row_version']];
    }

    public static function zoneView(array $row): array
    {
        return ['id' => $row['public_id'], 'name' => $row['name'], 'description' => $row['description'], 'status' => $row['status'], 'row_version' => (int) $row['row_version']];
    }

    public function slotView(array $row): array
    {
        $zone = $this->store->one('zones', ['id' => $row['zone_id']]);
        $container = $this->store->one('containers', ['id' => $row['container_id']]);
        return ['id' => $row['public_id'], 'name' => ($zone['name'] ?? '') . ' / ' . $row['ordinal'], 'zone_id' => $zone['public_id'] ?? null,
            'zone_name' => $zone['name'] ?? '', 'container_id' => $container['public_id'] ?? null, 'ordinal' => (int) $row['ordinal'], 'status' => $row['status']];
    }
}
