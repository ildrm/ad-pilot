<?php
namespace AdPilot\Core;

/** Site-bound persistence port. Module services own their table writes. */
interface Store
{
    public function one(string $table, array $where, bool $lock = false): ?array;
    public function many(string $table, array $where = [], int $limit = 50, int $offset = 0): array;
    public function count(string $table, array $where = []): int;
    public function insert(string $table, array $data): array;
    public function update(string $table, array $row, array $changes, ?int $expected = null): array;
    public function transaction(callable $operation): mixed;
    public function overlaps(int $slot, string $start, string $end, string $now): bool;
    public function transactional(): bool;
    public function candidates(array $slotIds, string $now): array;
}
