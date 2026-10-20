<?php
namespace AdPilot\Core;

final class Audit
{
    private const EVENTS = [
        'organization.created' => 'OrganizationRegistered', 'membership.revoked' => 'MembershipRevoked',
        'organization.suspended' => 'OrganizationSuspended', 'zone.created' => 'ZoneCreated',
        'zone.updated' => 'InventoryDefinitionChanged', 'container.created' => 'InventoryDefinitionChanged',
        'container.updated' => 'InventoryDefinitionChanged', 'layout.published' => 'LayoutRevisionPublished',
        'creative.approved' => 'CreativeVersionApproved', 'creative.rejected' => 'CreativeVersionRejected',
        'campaign.pause' => 'CampaignPaused', 'campaign.emergency-stop' => 'DeliveryEmergencyStopped',
        'quote.issued' => 'QuoteIssued', 'hold.created' => 'HoldAcquired', 'hold.expired' => 'HoldExpired',
        'reservation.complimentary_granted' => 'ReservationCommitted', 'order.created' => 'AdvertisingOrderCreated',
        'order.paid' => 'PaymentSettled',
    ];
    public function __construct(private readonly Store $store, private readonly Clock $clock)
    {
    }

    public function record(int $actor, string $action, string $type, string $id, string $reason = ''): void
    {
        $audit = $this->store->insert('audit_log', ['actor_user_id' => $actor, 'action' => $action, 'object_type' => $type,
            'object_public_id' => $id, 'reason' => mb_substr($reason, 0, 1000), 'context_json' => '{}']);
        if (isset(self::EVENTS[$action])) {
            $this->store->insert('outbox_messages', ['event_type' => self::EVENTS[$action], 'aggregate_public_id' => $id,
                'payload_json' => Input::json(['schema_version' => 1, 'site_id' => (int) $audit['site_id'], 'event_id' => Ids::make(),
                    'event_type' => self::EVENTS[$action], 'object_type' => $type, 'object_id' => $id, 'audit_ref' => $audit['public_id']]), 'status' => 'pending',
                'available_at' => $this->clock->now()->format('Y-m-d H:i:s')]);
        }
    }
}
