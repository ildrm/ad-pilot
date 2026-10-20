<?php
namespace AdPilot\Pricing;

use AdPilot\Core\Problem;

final class Money
{
    public function __construct(public readonly int $minor, public readonly string $currency)
    {
        if ($minor < 0 || !preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new Problem('invalid_money', 'Money requires a nonnegative exact integer and ISO currency code.');
        }
    }

    public static function parse(mixed $minor, string $currency): self
    {
        if (!is_string($minor) || !preg_match('/^(0|[1-9]\d{0,17})$/D', $minor) || (string) (int) $minor !== $minor) {
            throw new Problem('invalid_money', 'Transport minor units as a canonical decimal integer string.');
        }
        return new self((int) $minor, $currency);
    }

    public function times(int $units): self
    {
        if ($units < 1 || ($this->minor !== 0 && $units > intdiv(PHP_INT_MAX, $this->minor))) {
            throw new Problem('money_overflow', 'The exact total exceeds the supported range.');
        }
        return new self($this->minor * $units, $this->currency);
    }

    /** Exact gateway decimal conversion, including zero-decimal currencies. */
    public static function fromDecimal(string $decimal, string $currency, int $precision): self
    {
        if ($precision < 0 || $precision > 4 || !preg_match('/^(0|[1-9]\d*)(?:\.(\d+))?$/D', $decimal, $parts) || strlen($parts[2] ?? '') > $precision) {
            throw new Problem('invalid_money', 'Gateway total does not match the configured currency precision.');
        }
        $minor = ltrim($parts[1] . str_pad($parts[2] ?? '', $precision, '0'), '0');
        return self::parse($minor === '' ? '0' : $minor, $currency);
    }
}
