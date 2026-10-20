<?php
namespace AdPilot\Identity;

use AdPilot\Core\{Audit, Input, Problem, Store};

final class Organizations
{
    public function __construct(private readonly Store $store, private readonly Audit $audit)
    {
    }

    public function create(int $actor, array $input): array
    {
        $name = Input::text($input, 'display_name');
        $legal = Input::text($input + ['legal_name' => $name], 'legal_name');
        return $this->store->transaction(function () use ($actor, $name, $legal): array {
            $org = $this->store->insert('organizations', ['display_name' => $name, 'legal_name' => $legal,
                'status' => 'pending', 'owner_user_id' => $actor, 'profile_json' => '{}']);
            $this->store->insert('organization_memberships', ['organization_id' => $org['id'], 'user_id' => $actor,
                'role_key' => 'owner', 'status' => 'active', 'granted_by' => $actor]);
            $this->audit->record($actor, 'organization.created', 'organization', $org['public_id']);
            return self::view($org) + ['role' => 'owner'];
        });
    }

    public function authorized(int $actor, string $id, string $operation = 'read', bool $lock = false): array
    {
        $org = $this->store->one('organizations', ['public_id' => $id], $lock);
        if (!$org) {
            throw new Problem('not_found', 'Organization unavailable.', 404);
        }
        $membership = $this->store->one('organization_memberships', ['organization_id' => $org['id'], 'user_id' => $actor], $lock);
        Policy::requireMembership($membership, $org, $operation);
        return $org;
    }

    public function mine(int $actor): array
    {
        $items = [];
        foreach ($this->store->many('organization_memberships', ['user_id' => $actor, 'status' => 'active'], 100) as $member) {
            $org = $this->store->one('organizations', ['id' => $member['organization_id']]);
            if ($org && $org['status'] !== 'closed') {
                $items[] = self::view($org) + ['role' => $member['role_key']];
            }
        }
        return $items;
    }

    public function setStatus(int $actor, string $id, string $status, string $reason): array
    {
        if (!in_array($status, ['verified', 'suspended'], true) || trim($reason) === '') {
            throw new Problem('invalid_transition', 'A supported organization status and reason are required.');
        }
        return $this->store->transaction(function () use ($actor, $id, $status, $reason): array {
            $org = $this->store->one('organizations', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Organization unavailable.', 404);
            $row = $this->store->update('organizations', $org, ['status' => $status]);
            $this->audit->record($actor, 'organization.' . $status, 'organization', $id, $reason);
            return self::view($row);
        });
    }

    public function changeMember(int $actor, string $id, int $user, string $role, bool $revoke): array
    {
        if (!in_array($role, ['owner', 'manager', 'analyst', 'billing'], true)) {
            throw new Problem('invalid_role', 'Unsupported organization role.');
        }
        return $this->store->transaction(function () use ($actor, $id, $user, $role, $revoke): array {
            $org = $this->authorized($actor, $id, 'members', true);
            $member = $this->store->one('organization_memberships', ['organization_id' => $org['id'], 'user_id' => $user], true)
                ?? throw new Problem('not_found', 'Membership unavailable.', 404);
            if ($member['role_key'] === 'owner' && ($revoke || $role !== 'owner') && $this->store->count('organization_memberships', ['organization_id' => $org['id'], 'role_key' => 'owner', 'status' => 'active']) < 2) {
                throw new Problem('last_owner', 'Transfer ownership before removing the last active owner.', 409);
            }
            $updated = $this->store->update('organization_memberships', $member, ['role_key' => $role,
                'status' => $revoke ? 'revoked' : 'active', 'permission_version' => (int) $member['permission_version'] + 1]);
            $this->audit->record($actor, $revoke ? 'membership.revoked' : 'membership.role_changed', 'organization', $id);
            return ['id' => $updated['public_id'], 'user_id' => $user, 'role_key' => $updated['role_key'], 'status' => $updated['status']];
        });
    }

    public static function view(array $org): array
    {
        return ['id' => $org['public_id'], 'display_name' => $org['display_name'], 'legal_name' => $org['legal_name'],
            'status' => $org['status'], 'row_version' => (int) $org['row_version']];
    }
}
