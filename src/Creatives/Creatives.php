<?php
namespace AdPilot\Creatives;

use AdPilot\Core\{Audit, Clock, Ids, Input, Problem, Store};
use AdPilot\Identity\Organizations;

final class Creatives
{
    public function __construct(private readonly Store $store, private readonly Organizations $organizations, private readonly Audit $audit, private readonly Clock $clock)
    {
    }

    public function create(int $actor, string $orgId, array $input): array
    {
        $payload = self::payload($input);
        return $this->store->transaction(function () use ($actor, $orgId, $payload): array {
            $org = $this->organizations->authorized($actor, $orgId, 'creative_write', true);
            return $this->insertVersion($actor, $org, Ids::make(), 1, $payload);
        });
    }

    private static function payload(array $input): array
    {
        if (!empty($input['attachment_id']) || !empty($input['html']) || !empty($input['script'])) {
            throw new Problem('format_unavailable', 'Native text creatives are available. Media uploads require the private asset pipeline.', 422);
        }
        return ['name' => Input::text($input, 'name'), 'title' => Input::text($input, 'title', 120),
            'body' => Input::text($input, 'body', 600), 'destination_url' => Input::url($input, 'destination_url')];
    }

    private function insertVersion(int $actor, array $org, string $key, int $version, array $payload): array
    {
        $json = Input::json($payload, 4096);
        $row = $this->store->insert('creative_versions', ['organization_id' => $org['id'], 'creative_key' => $key,
            'version_number' => $version, 'format' => 'native', 'content_json' => $json, 'content_digest' => hash('sha256', $json, true), 'status' => 'draft']);
        $this->audit->record($actor, 'creative.created', 'creative', $row['public_id']);
        return self::view($row);
    }

    public function revise(int $actor, string $orgId, string $id, array $input): array
    {
        $payload = self::payload($input);
        return $this->store->transaction(function () use ($actor, $orgId, $id, $payload): array {
            $org = $this->organizations->authorized($actor, $orgId, 'creative_write', true);
            $source = $this->store->one('creative_versions', ['public_id' => $id, 'organization_id' => $org['id']])
                ?? throw new Problem('not_found', 'Creative unavailable.', 404);
            // Every successor locks the same family root, even when editing an
            // older version. Prior bytes, reviews and campaign selection stay exact.
            $this->store->one('creative_versions', ['creative_key' => $source['creative_key'], 'version_number' => 1, 'organization_id' => $org['id']], true)
                ?? throw new Problem('creative_family_invalid', 'Creative history requires review.', 409);
            $version = $this->store->count('creative_versions', ['creative_key' => $source['creative_key'], 'organization_id' => $org['id']]) + 1;
            return $this->insertVersion($actor, $org, $source['creative_key'], $version, $payload);
        });
    }

    public function submit(int $actor, string $orgId, string $id): array
    {
        return $this->store->transaction(function () use ($actor, $orgId, $id): array {
            $org = $this->organizations->authorized($actor, $orgId, 'creative_write', true);
            $row = $this->store->one('creative_versions', ['public_id' => $id, 'organization_id' => $org['id']], true)
                ?? throw new Problem('not_found', 'Creative unavailable.', 404);
            if ($row['status'] !== 'draft') {
                throw new Problem('invalid_transition', 'Only a draft can be submitted; create a new version to revise it.', 409);
            }
            $row = $this->store->update('creative_versions', $row, ['status' => 'submitted', 'submitted_at' => $this->clock->now()->format('Y-m-d H:i:s')]);
            $this->audit->record($actor, 'creative.submitted', 'creative', $id);
            return self::view($row);
        });
    }

    public function review(int $actor, string $id, string $decision, string $reason): array
    {
        if (!in_array($decision, ['approved', 'rejected', 'changes_requested'], true) || trim($reason) === '') {
            throw new Problem('invalid_review', 'A supported decision and review reason are required.');
        }
        return $this->store->transaction(function () use ($actor, $id, $decision, $reason): array {
            $row = $this->store->one('creative_versions', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Creative unavailable.', 404);
            if ($row['status'] !== 'submitted' || !hash_equals($row['content_digest'], hash('sha256', $row['content_json'], true))) {
                throw new Problem('review_conflict', 'This exact creative version is no longer pending review.', 409);
            }
            $this->store->insert('creative_reviews', ['organization_id' => $row['organization_id'], 'creative_version_id' => $row['id'],
                'reviewer_user_id' => $actor, 'decision' => $decision, 'reason' => mb_substr($reason, 0, 1000),
                'content_digest' => $row['content_digest'], 'reviewed_at' => $this->clock->now()->format('Y-m-d H:i:s')]);
            $row = $this->store->update('creative_versions', $row, ['status' => $decision]);
            $this->audit->record($actor, 'creative.' . $decision, 'creative', $id, $reason);
            return self::view($row);
        });
    }

    public static function view(array $row): array
    {
        return json_decode($row['content_json'], true, 16, JSON_THROW_ON_ERROR) + ['id' => $row['public_id'], 'creative_id' => $row['public_id'],
            'version' => (int) $row['version_number'], 'format' => $row['format'], 'digest' => bin2hex($row['content_digest']), 'status' => $row['status'], 'row_version' => (int) $row['row_version']];
    }
}
