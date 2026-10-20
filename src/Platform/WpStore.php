<?php
namespace AdPilot\Platform;

use AdPilot\Core\{Ids, Problem, Store};

final class WpStore implements Store
{
    private int $depth = 0;

    public function __construct(private readonly \wpdb $db, public readonly int $siteId)
    {
    }

    private function table(string $name): string
    {
        if (!isset(Schema::definitions()[$name])) {
            throw new Problem('invalid_storage', 'Unknown persistence unit.', 500);
        }
        if (get_current_blog_id() !== $this->siteId) {
            throw new Problem('site_context_changed', 'A fresh site-bound repository is required.', 403);
        }
        return $this->db->prefix . 'adpilot_' . $name;
    }

    private function predicates(array $where, string $table): array
    {
        $sql = ['site_id = %d'];
        $args = [$this->siteId];
        foreach ($where as $field => $value) {
            if ($field === '_catalog') {
                if ($table !== 'rate_card_versions' || $value !== true) { throw new Problem('invalid_predicate', 'Invalid catalog scope.', 500); }
                $slots = $this->table('slots');
                $zones = $this->table('zones');
                $sql[] = "published_at IS NOT NULL AND effective_start_at <= %s AND (effective_end_at IS NULL OR effective_end_at > %s) AND slot_id IN (SELECT s.id FROM {$slots} s JOIN {$zones} z ON z.id=s.zone_id AND z.site_id=s.site_id WHERE s.site_id=%d AND s.status='published' AND z.status='published')";
                array_push($args, gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s'), $this->siteId);
                continue;
            }
            if ($field === '_available_before') {
                if ($table !== 'outbox_messages' || !is_string($value)) { throw new Problem('invalid_predicate', 'Invalid job availability predicate.', 500); }
                $sql[] = 'available_at <= %s'; $args[] = $value;
                continue;
            }
            if ($field === '_search') {
                $columns = ['zones' => 'name', 'containers' => 'generator_json', 'slots' => 'slot_key', 'campaigns' => 'name', 'organizations' => 'display_name', 'creative_versions' => 'content_json', 'layout_drafts' => 'definition_json'];
                if (!isset($columns[$table]) || !is_string($value) || strlen($value) > 160) {
                    throw new Problem('invalid_search', 'Search is not supported for this resource.');
                }
                $like = '%' . $this->db->esc_like($value) . '%';
                if ($table === 'slots') {
                    $zones = $this->table('zones');
                    $sql[] = "(slot_key LIKE %s OR zone_id IN (SELECT id FROM {$zones} WHERE site_id=%d AND name LIKE %s))";
                    array_push($args, $like, $this->siteId, $like);
                } else {
                    $sql[] = '`' . $columns[$table] . '` LIKE %s';
                    $args[] = $like;
                }
                continue;
            }
            if (!preg_match('/^[a-z_]+$/D', $field) || $field === 'site_id') {
                throw new Problem('invalid_predicate', 'Invalid storage predicate.', 500);
            }
            if ($value === null) {
                $sql[] = "`{$field}` IS NULL";
            } else {
                $sql[] = "`{$field}` = %s";
                $args[] = $value;
            }
        }
        return [implode(' AND ', $sql), $args];
    }

    public function one(string $table, array $where, bool $lock = false): ?array
    {
        if ($lock && $this->depth === 0) {
            throw new Problem('lock_without_transaction', 'Row locks require an owning transaction.', 500);
        }
        [$sql, $args] = $this->predicates($where, $table);
        $row = $this->db->get_row($this->db->prepare('SELECT * FROM ' . $this->table($table) . " WHERE {$sql} LIMIT 1" . ($lock ? ' FOR UPDATE' : ''), ...$args), ARRAY_A);
        $this->check();
        return $row ?: null;
    }

    public function many(string $table, array $where = [], int $limit = 50, int $offset = 0): array
    {
        [$sql, $args] = $this->predicates($where, $table);
        $args[] = max(1, min(5000, $limit));
        $args[] = max(0, $offset);
        $rows = $this->db->get_results($this->db->prepare('SELECT * FROM ' . $this->table($table) . " WHERE {$sql} ORDER BY id ASC LIMIT %d OFFSET %d", ...$args), ARRAY_A);
        $this->check();
        return $rows ?: [];
    }

    public function count(string $table, array $where = []): int
    {
        [$sql, $args] = $this->predicates($where, $table);
        $count = $this->db->get_var($this->db->prepare('SELECT COUNT(*) FROM ' . $this->table($table) . " WHERE {$sql}", ...$args));
        $this->check();
        return (int) $count;
    }

    public function insert(string $table, array $data): array
    {
        if (isset($data['site_id']) || isset($data['id'])) {
            throw new Problem('untrusted_scope', 'Repository scope and primary keys are server controlled.', 500);
        }
        $data += ['public_id' => Ids::make(), 'row_version' => 1, 'created_at' => gmdate('Y-m-d H:i:s'), 'updated_at' => gmdate('Y-m-d H:i:s')];
        $data['site_id'] = $this->siteId;
        if ($this->db->insert($this->table($table), $data) === false) {
            $this->check();
            throw new Problem('storage_conflict', 'The operation conflicts with an existing record.', 409);
        }
        return $this->one($table, ['id' => $this->db->insert_id]) ?? throw new Problem('storage_failed', 'Inserted record unavailable.', 500);
    }

    public function update(string $table, array $row, array $changes, ?int $expected = null): array
    {
        if (in_array($table, ['layout_revisions', 'creative_reviews', 'financial_ledger_entries', 'audit_log', 'delivery_events', 'rate_card_versions', 'quotes'], true) || ($table === 'creative_versions' && array_diff(array_keys($changes), ['status', 'submitted_at']))) {
            throw new Problem('immutable_record', 'Append-only records cannot be updated.', 409);
        }
        if ((int) $row['site_id'] !== $this->siteId || array_intersect(array_keys($changes), ['id', 'site_id', 'public_id', 'created_at', 'row_version'])) {
            throw new Problem('untrusted_scope', 'Identity fields cannot be changed.', 403);
        }
        $expected ??= (int) $row['row_version'];
        $changes += ['row_version' => $expected + 1, 'updated_at' => gmdate('Y-m-d H:i:s')];
        $result = $this->db->update($this->table($table), $changes, ['id' => $row['id'], 'site_id' => $this->siteId, 'row_version' => $expected]);
        $this->check();
        if ($result !== 1) {
            throw new Problem('version_conflict', 'This record changed. Reload before retrying.', 409);
        }
        return $this->one($table, ['id' => $row['id']]) ?? throw new Problem('not_found', 'Record unavailable.', 404);
    }

    public function transaction(callable $operation): mixed
    {
        if ($this->depth > 0) {
            return $operation();
        }
        if (!$this->transactional()) {
            throw new Problem('transactions_required', 'InnoDB transactional storage is required for this mutation.', 503);
        }
        $this->db->query('START TRANSACTION');
        $this->check();
        ++$this->depth;
        try {
            $result = $operation();
            $this->db->query('COMMIT');
            $this->check();
            return $result;
        } catch (\Throwable $error) {
            $this->db->query('ROLLBACK');
            throw $error;
        } finally {
            --$this->depth;
        }
    }

    public function transactional(): bool
    {
        foreach (array_keys(Schema::definitions()) as $table) {
            $status = $this->db->get_row($this->db->prepare('SHOW TABLE STATUS LIKE %s', $this->db->esc_like($this->table($table))), ARRAY_A);
            if (!$status || strtolower($status['Engine']) !== 'innodb') {
                return false;
            }
        }
        return true;
    }

    public function overlaps(int $slot, string $start, string $end, string $now): bool
    {
        $holds = $this->table('inventory_holds');
        $lines = $this->table('hold_slot_lines');
        $contracts = $this->table('contract_reservations');
        $reservations = $this->table('reservation_slot_lines');
        $sql = "SELECT l.id FROM {$lines} l JOIN {$holds} h ON h.id=l.hold_id AND h.site_id=l.site_id WHERE l.site_id=%d AND l.slot_id=%d AND h.status='active' AND h.expires_at>%s AND l.starts_at<%s AND l.ends_at>%s LIMIT 1";
        $held = $this->db->get_var($this->db->prepare($sql, $this->siteId, $slot, $now, $end, $start));
        $this->check();
        if ($held) {
            return true;
        }
        $sql = "SELECT l.id FROM {$reservations} l JOIN {$contracts} c ON c.id=l.reservation_id AND c.site_id=l.site_id WHERE l.site_id=%d AND l.slot_id=%d AND c.status='active' AND l.starts_at<%s AND l.ends_at>%s LIMIT 1";
        $reserved = $this->db->get_var($this->db->prepare($sql, $this->siteId, $slot, $end, $start));
        $this->check();
        return (bool) $reserved;
    }

    private function check(): void
    {
        if ($this->db->last_error !== '') {
            // SQL and database diagnostics never leave this infrastructure boundary.
            throw new Problem('storage_failed', 'Storage could not complete this operation.', 503);
        }
    }

    /** Current published delivery projection in one bounded query, never per slot. */
    public function candidates(array $slotIds, string $now): array
    {
        if (!$slotIds || count($slotIds) > 20) {
            return [];
        }
        $s = $this->table('slots');
        $c = $this->table('campaigns');
        $v = $this->table('creative_versions');
        $r = $this->table('contract_reservations');
        $l = $this->table('reservation_slot_lines');
        $o = $this->table('organizations');
        $z = $this->table('zones');
        $placeholders = implode(',', array_fill(0, count($slotIds), '%s'));
        $sql = "SELECT c.*,s.public_id AS slot_public_id,v.public_id AS creative_public_id,v.status AS creative_status,v.content_json,v.content_digest,r.status AS contract_status,r.starts_at AS contract_start_at,r.ends_at AS contract_end_at
            FROM {$s} s JOIN {$z} z ON z.id=s.zone_id AND z.site_id=s.site_id
            JOIN {$c} c ON c.slot_id=s.id AND c.site_id=s.site_id
            JOIN {$v} v ON v.id=c.creative_version_id AND v.site_id=c.site_id AND v.organization_id=c.organization_id
            JOIN {$r} r ON r.id=c.reservation_id AND r.site_id=c.site_id AND r.organization_id=c.organization_id
            JOIN {$l} l ON l.reservation_id=r.id AND l.site_id=r.site_id AND l.slot_id=s.id
            JOIN {$o} o ON o.id=c.organization_id AND o.site_id=c.site_id
            WHERE s.site_id=%d AND s.public_id IN ({$placeholders}) AND s.status='published' AND z.status='published'
            AND c.delivery_status='active' AND o.status='verified' AND v.status='approved' AND r.status='active'
            AND c.start_at<=%s AND c.end_at>%s AND l.starts_at<=%s AND l.ends_at>%s ORDER BY c.id ASC LIMIT 100";
        $rows = $this->db->get_results($this->db->prepare($sql, $this->siteId, ...[...$slotIds, $now, $now, $now, $now]), ARRAY_A);
        $this->check();
        return $rows ?: [];
    }
}
