<?php

namespace App\Support;

/**
 * Locale-aware number formatting so every screen shows 6 017 / 13,7 in Macedonian and 6,017 / 13.7 in English.
 */
class Fmt
{
    public static function num(int|float|string|null $value, int $decimals = 0, bool $trim = false): string
    {
        $mk = app()->getLocale() === 'mk';
        $formatted = number_format((float) $value, $decimals, $mk ? ',' : '.', $mk ? "\u{00A0}" : ',');

        if ($trim && $decimals > 0) {
            $sep = $mk ? ',' : '.';
            $formatted = rtrim(rtrim($formatted, '0'), $sep);
        }

        return $formatted;
    }

    public static function signed(int|float $value, int $decimals = 1): string
    {
        return ($value >= 0 ? '+' : '−').self::num(abs($value), $decimals);
    }
}
