<?php
namespace AdPilot\Platform;

use AdPilot\Core\Problem;

final class Schema
{
    public const VERSION = 1;

    /** Explicit migration 001; only implemented R1 persistence units are created. */
    public static function definitions(): array
    {
        return [
            'organizations' => "legal_name varchar(191) NOT NULL, display_name varchar(191) NOT NULL, status varchar(32) NOT NULL, owner_user_id bigint unsigned NOT NULL, profile_json longtext NULL, profile_schema_version smallint NOT NULL DEFAULT 1, verified_at datetime NULL, KEY owner (site_id,owner_user_id)",
            'organization_memberships' => "organization_id bigint unsigned NOT NULL, user_id bigint unsigned NOT NULL, role_key varchar(40) NOT NULL, status varchar(24) NOT NULL, granted_by bigint unsigned NULL, revoked_at datetime NULL, permission_version bigint NOT NULL DEFAULT 1, UNIQUE KEY membership (site_id,organization_id,user_id), KEY member_lookup (site_id,user_id,status)",
            'zones' => "zone_key varchar(120) NOT NULL, name varchar(191) NOT NULL, status varchar(24) NOT NULL, commercial_class varchar(40) NOT NULL DEFAULT 'standard', description text NULL, current_layout_id bigint unsigned NULL, UNIQUE KEY zone_key (site_id,zone_key), KEY state (site_id,status)",
            'containers' => "zone_id bigint unsigned NOT NULL, container_key varchar(120) NOT NULL, layout_type varchar(32) NOT NULL, generator_json longtext NOT NULL, generator_schema_version smallint NOT NULL DEFAULT 1, responsive_json longtext NOT NULL, responsive_schema_version smallint NOT NULL DEFAULT 1, status varchar(24) NOT NULL, UNIQUE KEY container_key (site_id,zone_id,container_key)",
            'slots' => "zone_id bigint unsigned NOT NULL, container_id bigint unsigned NOT NULL, sequence_id bigint unsigned NULL, ordinal int unsigned NOT NULL, slot_key varchar(140) NOT NULL, status varchar(24) NOT NULL, commercial_class varchar(40) NOT NULL DEFAULT 'standard', capacity_units int NOT NULL DEFAULT 1, allocation_mode varchar(32) NOT NULL DEFAULT 'exclusive', retired_at datetime NULL, UNIQUE KEY slot_key (site_id,slot_key), UNIQUE KEY position (site_id,container_id,ordinal), KEY container (site_id,container_id,status), KEY zone (site_id,zone_id,status)",
            'layout_drafts' => "scope_key varchar(140) NOT NULL, author_user_id bigint NOT NULL, base_revision_id bigint NULL, definition_json longtext NOT NULL, definition_schema_version smallint NOT NULL DEFAULT 1, validation_status varchar(24) NOT NULL DEFAULT 'unvalidated', impact_digest binary(32) NULL, status varchar(24) NOT NULL, KEY scope (site_id,scope_key,status)",
            'layout_revisions' => "scope_key varchar(140) NOT NULL, revision_number bigint NOT NULL, layout_digest binary(32) NOT NULL, definition_json longtext NOT NULL, definition_schema_version smallint NOT NULL DEFAULT 1, impact_decision_id bigint NULL, published_by bigint NOT NULL, published_at datetime(6) NOT NULL, UNIQUE KEY revision (site_id,scope_key,revision_number)",
            'layout_active_pointers' => "scope_key varchar(140) NOT NULL, layout_revision_id bigint NOT NULL, UNIQUE KEY scope (site_id,scope_key)",
            'creative_versions' => "organization_id bigint NOT NULL, creative_key varchar(120) NOT NULL, version_number int NOT NULL, format varchar(32) NOT NULL, content_json longtext NOT NULL, content_digest binary(32) NOT NULL, status varchar(24) NOT NULL, submitted_at datetime NULL, UNIQUE KEY version (site_id,creative_key,version_number), KEY org_state (site_id,organization_id,status)",
            'creative_reviews' => "organization_id bigint NOT NULL, creative_version_id bigint NOT NULL, reviewer_user_id bigint NOT NULL, decision varchar(32) NOT NULL, reason text NOT NULL, content_digest binary(32) NOT NULL, reviewed_at datetime(6) NOT NULL, KEY creative (site_id,creative_version_id,id)",
            'campaigns' => "organization_id bigint NOT NULL, name varchar(191) NOT NULL, slot_id bigint NOT NULL, approved_creative_id char(26) NULL, creative_version_id bigint NULL, reservation_id bigint NULL, approval_status varchar(24) NOT NULL, financial_status varchar(32) NOT NULL, delivery_status varchar(24) NOT NULL, start_at datetime NOT NULL, end_at datetime NOT NULL, targeting_json longtext NULL, KEY slot_state (site_id,slot_id,delivery_status), KEY organization (site_id,organization_id,id)",
            'rate_card_versions' => "zone_id bigint NULL, slot_id bigint NULL, rate_key varchar(120) NOT NULL, version_number int NOT NULL, model varchar(32) NOT NULL, currency char(3) NOT NULL, unit_price_minor bigint NOT NULL, parameters_json longtext NOT NULL, effective_start_at datetime NOT NULL, effective_end_at datetime NULL, published_at datetime NULL, UNIQUE KEY rate (site_id,rate_key,version_number)",
            'quotes' => "organization_id bigint NOT NULL, campaign_id bigint NULL, rate_card_version_id bigint NOT NULL, currency char(3) NOT NULL, subtotal_minor bigint NOT NULL, tax_minor bigint NOT NULL DEFAULT 0, discount_minor bigint NOT NULL DEFAULT 0, total_minor bigint NOT NULL, offer_snapshot_json longtext NOT NULL, terms_digest binary(32) NOT NULL, expires_at datetime NOT NULL, status varchar(24) NOT NULL, KEY organization (site_id,organization_id,status,expires_at)",
            'inventory_holds' => "organization_id bigint NOT NULL, quote_id bigint NOT NULL, campaign_id bigint NULL, starts_at datetime NOT NULL, ends_at datetime NOT NULL, expires_at datetime NOT NULL, status varchar(24) NOT NULL, idempotency_hash binary(32) NOT NULL, consumed_at datetime NULL, UNIQUE KEY idempotency (site_id,organization_id,idempotency_hash), KEY expiry (site_id,status,expires_at)",
            'hold_slot_lines' => "hold_id bigint NOT NULL, slot_id bigint NOT NULL, units int NOT NULL, starts_at datetime NOT NULL, ends_at datetime NOT NULL, UNIQUE KEY hold_slot (site_id,hold_id,slot_id), KEY overlap (site_id,slot_id,starts_at,ends_at)",
            'contract_reservations' => "organization_id bigint NOT NULL, campaign_id bigint NULL, order_id bigint NULL, status varchar(24) NOT NULL, contract_type varchar(32) NOT NULL, starts_at datetime NOT NULL, ends_at datetime NOT NULL, contract_snapshot_json longtext NOT NULL, KEY organization (site_id,organization_id,status)",
            'reservation_slot_lines' => "reservation_id bigint NOT NULL, slot_id bigint NOT NULL, units int NOT NULL, starts_at datetime NOT NULL, ends_at datetime NOT NULL, UNIQUE KEY reservation_slot (site_id,reservation_id,slot_id), KEY overlap (site_id,slot_id,starts_at,ends_at)",
            'advertising_orders' => "organization_id bigint NOT NULL, hold_id bigint NOT NULL, quote_id bigint NOT NULL, woo_order_id bigint NULL, currency char(3) NOT NULL, total_minor bigint NOT NULL, status varchar(32) NOT NULL, paid_minor bigint NOT NULL DEFAULT 0, UNIQUE KEY hold_order (site_id,hold_id), UNIQUE KEY woo_order (site_id,woo_order_id)",
            'financial_ledger_entries' => "organization_id bigint NOT NULL, order_id bigint NOT NULL, currency char(3) NOT NULL, amount_minor bigint NOT NULL, entry_type varchar(40) NOT NULL, source_provider_event_id bigint NULL, source_idempotency_hash binary(32) NOT NULL, reversal_of_entry_id bigint NULL, posted_at datetime NOT NULL, UNIQUE KEY source (site_id,source_idempotency_hash), KEY organization (site_id,organization_id,posted_at)",
            'delivery_events' => "organization_id bigint NOT NULL, campaign_id bigint NOT NULL, slot_id bigint NOT NULL, event_type varchar(32) NOT NULL, event_key char(64) NOT NULL, render_instance_id varchar(80) NOT NULL, occurred_at datetime NOT NULL, source_class varchar(24) NOT NULL, UNIQUE KEY event_key (site_id,event_key), KEY campaign_event (site_id,campaign_id,event_type,occurred_at)",
            'audit_log' => "actor_user_id bigint NOT NULL, action varchar(100) NOT NULL, object_type varchar(64) NOT NULL, object_public_id varchar(80) NOT NULL, reason text NOT NULL, context_json longtext NOT NULL, KEY audit_time (site_id,created_at)",
            'outbox_messages' => "event_type varchar(100) NOT NULL, aggregate_public_id varchar(80) NOT NULL, payload_json longtext NOT NULL, status varchar(24) NOT NULL, attempts int NOT NULL DEFAULT 0, available_at datetime NOT NULL, KEY pending (site_id,status,available_at)",
            'schema_migrations' => "version_number int NOT NULL, checksum char(64) NOT NULL, status varchar(24) NOT NULL, UNIQUE KEY migration (site_id,version_number)",
        ];
    }

    public static function migrate(\wpdb $db): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        if ((int) get_option('adpilot_schema_version', 0) > self::VERSION) {
            throw new Problem('schema_downgrade_blocked', 'This plugin cannot downgrade a newer database schema.', 503);
        }
        $checksum = hash('sha256', json_encode(self::definitions(), JSON_THROW_ON_ERROR));
        $store = new WpStore($db, get_current_blog_id());
        $migrationTable = $db->prefix . 'adpilot_schema_migrations';
        if ($db->get_var($db->prepare('SHOW TABLES LIKE %s', $db->esc_like($migrationTable))) === $migrationTable) {
            $recorded = $store->one('schema_migrations', ['version_number' => self::VERSION]);
            if ($recorded && $recorded['checksum'] !== $checksum) {
                throw new Problem('migration_checksum_mismatch', 'A recorded migration has changed; no DDL was performed.', 503);
            }
        }
        $collate = $db->get_charset_collate();
        foreach (self::definitions() as $table => $columns) {
            $name = $db->prefix . 'adpilot_' . $table;
            $columnLines = preg_split('/, (?=[a-zA-Z_])/', $columns);
            $sql = "CREATE TABLE {$name} (\n" .
                "id bigint unsigned NOT NULL AUTO_INCREMENT,\nsite_id bigint unsigned NOT NULL,\npublic_id char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,\nrow_version int unsigned NOT NULL DEFAULT 1,\ncreated_at datetime(6) NOT NULL,\nupdated_at datetime(6) NOT NULL,\n" .
                implode(",\n", $columnLines) . ",\nPRIMARY KEY  (id),\nUNIQUE KEY uq_site_public (site_id,public_id)\n) ENGINE=InnoDB {$collate};";
            dbDelta($sql);
            if ($db->get_var($db->prepare('SHOW TABLES LIKE %s', $db->esc_like($name))) !== $name) {
                throw new Problem('migration_failed', 'AdPilot could not create its required storage.', 503);
            }
        }
        $migration = $store->one('schema_migrations', ['version_number' => self::VERSION]);
        if ($migration && $migration['checksum'] !== $checksum) {
            throw new Problem('migration_checksum_mismatch', 'A recorded migration has changed; explicit migration review is required.', 503);
        }
        if (!$migration) {
            $store->insert('schema_migrations', ['version_number' => self::VERSION, 'checksum' => $checksum, 'status' => 'complete']);
        }
        update_option('adpilot_schema_version', self::VERSION, false);
    }
}
