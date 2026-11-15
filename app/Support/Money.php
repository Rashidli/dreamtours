<?php

namespace App\Support;

/**
 * Exact money arithmetic on 2-decimal strings (bcmath), so totals never drift the
 * way floats do (0.1 + 0.2). Inputs may be strings, ints or floats from the
 * database; results are strings like "123.45".
 */
class Money
{
    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }
        // Floats go through a fixed 2-decimal string first: 0.1 + 0.2 → "0.30".
        return bcadd(is_float($value) ? number_format($value, 2, '.', '') : (string) $value, '0', 2);
    }

    public static function add(mixed ...$values): string
    {
        return array_reduce($values, fn ($sum, $v) => bcadd($sum, self::of($v), 2), '0.00');
    }

    public static function sub(mixed $a, mixed $b): string
    {
        return bcsub(self::of($a), self::of($b), 2);
    }

    /** a × b, rounded half up to cents (e.g. a percentage of a price). */
    public static function mul(mixed $a, mixed $b): string
    {
        $raw = bcmul(self::of($a), (string) $b, 4);

        return bcadd($raw, bccomp($raw, '0', 4) >= 0 ? '0.005' : '-0.005', 2);
    }

    public static function max0(mixed $value): string
    {
        return bccomp(self::of($value), '0', 2) > 0 ? self::of($value) : '0.00';
    }

    public static function min(mixed $a, mixed $b): string
    {
        return bccomp(self::of($a), self::of($b), 2) <= 0 ? self::of($a) : self::of($b);
    }

    /** -1, 0 or 1. */
    public static function cmp(mixed $a, mixed $b): int
    {
        return bccomp(self::of($a), self::of($b), 2);
    }

    public static function isZero(mixed $value): bool
    {
        return self::cmp($value, 0) === 0;
    }
}
