<?php
require dirname(__DIR__, 2) . '/src/autoload.php';

use AdPilot\Core\{Ids, Input, Problem};
use AdPilot\Identity\Policy;
use AdPilot\Inventory\LayoutCompiler;
use AdPilot\Pricing\Money;
use AdPilot\Campaigns\Eligibility;

$passed = 0;
function check(bool $ok, string $name): void {
    global $passed;
    if (!$ok) { throw new RuntimeException($name); }
    ++$passed;
    echo "PASS {$name}\n";
}
function denied(callable $call, string $key, string $name): void {
    try { $call(); } catch (Problem $e) { check($e->key === $key, $name); return; }
    throw new RuntimeException("Expected denial: {$name}");
}

$ids = array_map(static fn(): string => Ids::make(), range(1, 1000));
check(count(array_unique($ids)) === 1000 && strlen($ids[0]) === 26, 'opaque IDs have 128 random bits and 26 characters');
check(Input::date(['start' => '2026-10-20T12:30:00+03:30'], 'start') === '2026-10-20 09:00:00', 'contract timestamps normalize to UTC');
denied(fn() => Input::date(['start' => '2026-02-31T12:00:00Z'], 'start'), 'invalid_date', 'invalid calendar dates are rejected');
denied(fn() => Input::date(['start' => '2026-10-20 12:00:00'], 'start'), 'invalid_date', 'missing timezone is rejected');
denied(fn() => Input::url(['url' => 'javascript:alert(1)'], 'url'), 'invalid_url', 'script destinations rejected');
denied(fn() => Input::url(['url' => 'https://user:pass@example.com'], 'url'), 'invalid_url', 'credential-bearing URLs rejected');
check(Money::parse('1500', 'USD')->times(3)->minor === 4500, 'exact integer minor-unit multiplication');
denied(fn() => Money::parse(15.2, 'USD'), 'invalid_money', 'floating money is rejected');
denied(fn() => Money::parse('01', 'USD'), 'invalid_money', 'ambiguous monetary integers rejected');
denied(fn() => (new Money(PHP_INT_MAX, 'USD'))->times(2), 'money_overflow', 'money overflow fails safely');
check(Money::fromDecimal('15.20', 'USD', 2)->minor === 1520, 'gateway decimals convert without floating point');
check(Money::fromDecimal('1500', 'JPY', 0)->minor === 1500, 'zero-decimal currencies retain exact units');
denied(fn() => Money::fromDecimal('1.5', 'JPY', 0), 'invalid_money', 'fractional zero-decimal capture rejected');
denied(fn() => Money::fromDecimal('1.005', 'USD', 2), 'invalid_money', 'gateway precision cannot round silently');
denied(fn() => Money::fromDecimal('1e3', 'USD', 2), 'invalid_money', 'exponential gateway amounts rejected');
denied(fn() => Policy::requireMembership(null, ['status' => 'verified'], 'read'), 'forbidden', 'no implicit organization grant');
denied(fn() => Policy::requireMembership(['status' => 'revoked', 'role_key' => 'owner'], ['status' => 'verified'], 'read'), 'forbidden', 'revocation is current');
denied(fn() => Policy::requireMembership(['status' => 'active', 'role_key' => 'analyst'], ['status' => 'verified'], 'purchase'), 'forbidden', 'analysts cannot purchase');
Policy::requireMembership(['status' => 'active', 'role_key' => 'billing'], ['status' => 'verified'], 'finance');
check(true, 'billing membership grants scoped finance operation');
$container = LayoutCompiler::container(['slot_count' => 1200]);
check($container['slot_count'] === 1200, 'no 500-slot product cap');
check(LayoutCompiler::stableSlot($ids[0], 1) === LayoutCompiler::stableSlot($ids[0], 1), 'logical slot identity stable');
check(LayoutCompiler::stableSlot($ids[0], 1) !== LayoutCompiler::stableSlot($ids[0], 2), 'sequence positions have distinct identities');
denied(fn() => LayoutCompiler::container(['slot_count' => 2, 'aspect_ratio' => 'url(x)']), 'invalid_ratio', 'layout style injection rejected');
$campaign = ['delivery_status' => 'active', 'financial_status' => 'paid', 'approved_creative_id' => 'A', 'start_at' => '2026-01-01', 'end_at' => '2027-01-01'];
$creative = ['status' => 'approved', 'public_id' => 'A'];
$contract = ['status' => 'active', 'start_at' => '2026-01-01', 'end_at' => '2027-01-01'];
check(Eligibility::evaluate($campaign, $creative, $contract, '2026-10-20', false) === null, 'all eligibility gates required');
check(Eligibility::evaluate($campaign, $creative, $contract, '2026-10-20', true) === 'stopped', 'emergency stop defeats eligible cached state');
check(Eligibility::evaluate($campaign, ['status' => 'submitted', 'public_id' => 'A'], $contract, '2026-10-20', false) === 'creative_unapproved', 'payment cannot bypass approval');
check(Eligibility::evaluate($campaign, $creative, $contract, '2027-01-01', false) === 'outside_schedule', 'delivery intervals are half open');
echo "{$passed} domain checks passed.\n";
