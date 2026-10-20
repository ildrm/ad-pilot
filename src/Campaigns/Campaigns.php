<?php
namespace AdPilot\Campaigns;

use AdPilot\Core\{Audit, Input, Problem, Store};
use AdPilot\Identity\Organizations;
use AdPilot\Inventory\Inventory;
use AdPilot\Reservations\Reservations;

final class Campaigns
{
    public function __construct(private readonly Store $store, private readonly Organizations $organizations,
        private readonly Inventory $inventory, private readonly Reservations $reservations, private readonly Audit $audit)
    {
    }

    public function create(int $actor, string $orgId, array $input): array
    {
        $org = $this->organizations->authorized($actor, $orgId, 'campaign_write');
        $slot = $this->inventory->slot(Input::text($input, 'slot_id', 26));
        $name = Input::text($input, 'name');
        $start = Input::date($input, 'start_at');
        $end = Input::date($input, 'end_at');
        if ($end <= $start || $slot['status'] !== 'published') {
            throw new Problem('invalid_campaign', 'Choose a published slot and a valid half-open schedule.');
        }
        $creative = $this->store->one('creative_versions', ['public_id' => Input::text($input, 'creative_id', 26), 'organization_id' => $org['id']])
            ?? throw new Problem('not_found', 'Creative unavailable.', 404);
        return $this->store->transaction(function () use ($actor, $orgId, $slot, $name, $start, $end, $creative): array {
            $org = $this->organizations->authorized($actor, $orgId, 'campaign_write', true);
            $row = $this->store->insert('campaigns', ['organization_id' => $org['id'], 'name' => $name, 'slot_id' => $slot['id'],
                'creative_version_id' => $creative['id'], 'approved_creative_id' => $creative['status'] === 'approved' ? $creative['public_id'] : null,
                'approval_status' => $creative['status'] === 'approved' ? 'approved' : 'pending', 'financial_status' => 'unfunded', 'delivery_status' => 'draft', 'start_at' => $start, 'end_at' => $end]);
            $this->audit->record($actor, 'campaign.created', 'campaign', $row['public_id']);
            return $this->view($row);
        });
    }

    public function complimentary(int $actor, string $id, string $reason, bool $policyReady): array
    {
        if (!$policyReady || trim($reason) === '') {
            throw new Problem('policy_required', 'Record the manual entitlement policy and an allocation reason first.', 409);
        }
        return $this->store->transaction(function () use ($actor, $id, $reason): array {
            $campaign = $this->store->one('campaigns', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Campaign unavailable.', 404);
            if ($campaign['financial_status'] !== 'unfunded' || $campaign['reservation_id']) {
                throw new Problem('already_allocated', 'This campaign already has a financial or allocation disposition.', 409);
            }
            $creative = $this->store->one('creative_versions', ['id' => $campaign['creative_version_id']]);
            $org = $this->store->one('organizations', ['id' => $campaign['organization_id']]);
            if (!$creative || $creative['status'] !== 'approved' || !$org || $org['status'] !== 'verified') {
                throw new Problem('approval_required', 'Verify the organization and approve the exact creative version before activation.', 409);
            }
            $reservation = $this->reservations->complimentary($actor, $campaign, $reason);
            $campaign = $this->store->update('campaigns', $campaign, ['reservation_id' => $reservation['id'], 'financial_status' => 'complimentary',
                'approval_status' => 'approved', 'approved_creative_id' => $creative['public_id'], 'delivery_status' => 'active']);
            $this->audit->record($actor, 'campaign.activated', 'campaign', $id, $reason);
            return $this->view($campaign);
        });
    }

    public function action(int $actor, string $id, string $action, string $reason): array
    {
        if (!in_array($action, ['pause', 'resume', 'emergency-stop'], true) || trim($reason) === '') {
            throw new Problem('invalid_transition', 'A supported campaign action and reason are required.');
        }
        return $this->store->transaction(function () use ($actor, $id, $action, $reason): array {
            $row = $this->store->one('campaigns', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Campaign unavailable.', 404);
            if ($action === 'resume') {
                $creative = $this->store->one('creative_versions', ['id' => $row['creative_version_id']]);
                $contract = $this->store->one('contract_reservations', ['id' => $row['reservation_id']]);
                $org = $this->store->one('organizations', ['id' => $row['organization_id']]);
                if (!$creative || $creative['status'] !== 'approved' || !$contract || $contract['status'] !== 'active' || !$org || $org['status'] !== 'verified') {
                    throw new Problem('eligibility_required', 'Approval and current entitlement must hold before resuming.', 409);
                }
            }
            $row = $this->store->update('campaigns', $row, ['delivery_status' => $action === 'resume' ? 'active' : ($action === 'emergency-stop' ? 'suspended' : 'paused')]);
            $this->audit->record($actor, 'campaign.' . $action, 'campaign', $id, $reason);
            return $this->view($row);
        });
    }

    public function allocatePaid(int $actor, string $id, string $orderId, string $reason): array
    {
        if (trim($reason) === '') { throw new Problem('reason_required', 'An allocation reason is required.'); }
        return $this->store->transaction(function () use ($actor, $id, $orderId, $reason): array {
            $campaign = $this->store->one('campaigns', ['public_id' => $id], true) ?? throw new Problem('not_found', 'Campaign unavailable.', 404);
            $order = $this->store->one('advertising_orders', ['public_id' => $orderId, 'organization_id' => $campaign['organization_id']]);
            if (!$order || $order['status'] !== 'paid') { throw new Problem('payment_required', 'A reconciled paid order in the same organization is required.', 409); }
            $contract = $this->store->one('contract_reservations', ['order_id' => $order['id'], 'status' => 'active'], true)
                ?? throw new Problem('entitlement_missing', 'The paid order has no active reservation.', 409);
            if ($campaign['reservation_id']) { throw new Problem('already_allocated', 'Campaign already has an allocation.', 409); }
            $line = $this->store->one('reservation_slot_lines', ['reservation_id' => $contract['id'], 'slot_id' => $campaign['slot_id']]);
            if (!$line || $campaign['start_at'] < $line['starts_at'] || $campaign['end_at'] > $line['ends_at'] || $this->store->count('campaigns', ['reservation_id' => $contract['id']]) !== 0) {
                throw new Problem('allocation_mismatch', 'Paid inventory and schedule cannot be reused or exceeded.', 409);
            }
            $creative = $this->store->one('creative_versions', ['id' => $campaign['creative_version_id'], 'organization_id' => $campaign['organization_id']]);
            $org = $this->store->one('organizations', ['id' => $campaign['organization_id']]);
            if (!$creative || $creative['status'] !== 'approved' || !$org || $org['status'] !== 'verified') {
                throw new Problem('approval_required', 'Payment does not bypass organization or creative approval.', 409);
            }
            $row = $this->store->update('campaigns', $campaign, ['financial_status' => 'paid', 'reservation_id' => $contract['id'],
                'approved_creative_id' => $creative['public_id'], 'approval_status' => 'approved', 'delivery_status' => 'active']);
            $this->audit->record($actor, 'campaign.activated', 'campaign', $id, $reason);
            return $this->view($row);
        });
    }

    public function view(array $row): array
    {
        $slot = $this->store->one('slots', ['id' => $row['slot_id']]);
        $org = $this->store->one('organizations', ['id' => $row['organization_id']]);
        return ['id' => $row['public_id'], 'name' => $row['name'], 'organization_id' => $org['public_id'] ?? null,
            'slot_id' => $slot['public_id'] ?? null, 'approval_status' => $row['approval_status'], 'financial_status' => $row['financial_status'],
            'delivery_status' => $row['delivery_status'], 'status' => $row['delivery_status'], 'start_at' => $row['start_at'], 'end_at' => $row['end_at'], 'row_version' => (int) $row['row_version']];
    }
}
