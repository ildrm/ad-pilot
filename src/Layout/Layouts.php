<?php
namespace AdPilot\Layout;

use AdPilot\Core\{Audit, Clock, Ids, Input, Problem, Store};
use AdPilot\Inventory\LayoutCompiler;

final class Layouts
{
    public function __construct(private readonly Store $store, private readonly Audit $audit, private readonly Clock $clock)
    {
    }

    public function draft(int $actor, array $input): array
    {
        $definition = LayoutCompiler::validate($input['definition'] ?? ['name' => Input::text($input, 'name'), 'zones' => $input['zones'] ?? []]);
        $this->references($definition);
        return $this->store->transaction(function () use ($actor, $definition): array {
            $row = $this->store->insert('layout_drafts', ['scope_key' => Ids::make(), 'author_user_id' => $actor,
                'definition_json' => Input::json($definition), 'status' => 'draft']);
            $this->audit->record($actor, 'layout.draft_created', 'layout', $row['public_id']);
            return self::view($row);
        });
    }

    public function save(int $actor, string $id, array $input): array
    {
        $definition = LayoutCompiler::validate($input['definition'] ?? []);
        $this->references($definition);
        return $this->store->transaction(function () use ($actor, $id, $input, $definition): array {
            $row = $this->store->one('layout_drafts', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Draft unavailable.', 404);
            if ($row['status'] !== 'draft') {
                throw new Problem('immutable_record', 'Create another draft after publication.', 409);
            }
            $row = $this->store->update('layout_drafts', $row, ['definition_json' => Input::json($definition), 'validation_status' => 'unvalidated'], Input::integer($input, 'row_version', 1, PHP_INT_MAX));
            $this->audit->record($actor, 'layout.draft_updated', 'layout', $id);
            return self::view($row);
        });
    }

    private function references(array $definition): array
    {
        $containers = [];
        foreach ($definition['zones'] as $zone) {
            $z = $this->store->one('zones', ['public_id' => $zone['zone_id']]) ?? throw new Problem('invalid_layout', 'Referenced zone unavailable.');
            foreach ($zone['container_ids'] as $id) {
                $row = $this->store->one('containers', ['public_id' => $id, 'zone_id' => $z['id']]) ?? throw new Problem('invalid_layout', 'Container does not belong to the referenced zone.');
                $containers[] = $row;
            }
        }
        return $containers;
    }

    public function validate(string $id): array
    {
        $draft = $this->store->one('layout_drafts', ['public_id' => $id]) ?? throw new Problem('not_found', 'Draft unavailable.', 404);
        $definition = LayoutCompiler::validate(json_decode($draft['definition_json'], true, 64, JSON_THROW_ON_ERROR));
        $containers = $this->references($definition);
        $count = array_sum(array_map(static fn(array $c): int => (int) json_decode($c['generator_json'], true)['slot_count'], $containers));
        return ['valid' => true, 'slot_count' => $count, 'blockers' => [], 'warnings' => $count > 100 ? ['High density requires publisher policy and browser performance review.'] : [], 'preview_billable' => false];
    }

    public function publish(int $actor, string $id, int $expected): array
    {
        return $this->store->transaction(function () use ($actor, $id, $expected): array {
            $draft = $this->store->one('layout_drafts', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Draft unavailable.', 404);
            if ($draft['status'] !== 'draft' || (int) $draft['row_version'] !== $expected) {
                throw new Problem('version_conflict', 'This draft changed or was already published.', 409);
            }
            $this->validate($id);
            $definition = json_decode($draft['definition_json'], true, 64, JSON_THROW_ON_ERROR);
            // Published geometry is frozen; contract-breaking edits are unavailable.
            $definition['containers'] = array_map(static fn(array $row): array => ['id' => $row['public_id'], 'zone_id' => $row['zone_id'], 'definition' => json_decode($row['generator_json'], true)], $this->references($definition));
            $pointer = $this->store->one('layout_active_pointers', ['scope_key' => $draft['scope_key']], true);
            $revision = $this->store->insert('layout_revisions', ['scope_key' => $draft['scope_key'],
                'revision_number' => $this->store->count('layout_revisions', ['scope_key' => $draft['scope_key']]) + 1,
                'layout_digest' => hash('sha256', Input::json($definition), true), 'definition_json' => Input::json($definition),
                'published_by' => $actor, 'published_at' => $this->clock->now()->format('Y-m-d H:i:s')]);
            if ($pointer) {
                $this->store->update('layout_active_pointers', $pointer, ['layout_revision_id' => $revision['id']]);
            } else {
                $this->store->insert('layout_active_pointers', ['scope_key' => $draft['scope_key'], 'layout_revision_id' => $revision['id']]);
            }
            foreach ($this->references($definition) as $container) {
                foreach ($this->store->many('slots', ['container_id' => $container['id']], 5000) as $slot) {
                    if ($slot['status'] !== 'published') {
                        $this->store->update('slots', $slot, ['status' => 'published']);
                    }
                }
                if ($container['status'] !== 'published') {
                    $this->store->update('containers', $container, ['status' => 'published']);
                }
                $zone = $this->store->one('zones', ['id' => $container['zone_id']], true);
                $this->store->update('zones', $zone, ['status' => 'published', 'current_layout_id' => $revision['id']]);
            }
            $this->store->update('layout_drafts', $draft, ['status' => 'published', 'validation_status' => 'valid']);
            $this->audit->record($actor, 'layout.published', 'layout', $id);
            return ['id' => $revision['public_id'], 'layout_id' => $draft['scope_key'], 'revision' => (int) $revision['revision_number'], 'status' => 'published'];
        });
    }

    public static function view(array $row): array
    {
        $definition = json_decode($row['definition_json'], true, 64, JSON_THROW_ON_ERROR);
        return ['id' => $row['public_id'], 'layout_id' => $row['scope_key'], 'name' => $definition['name'] ?? 'Layout', 'definition' => $definition,
            'row_version' => (int) $row['row_version'], 'status' => $row['status']];
    }
}
