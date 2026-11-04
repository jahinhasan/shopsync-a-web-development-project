<?php

namespace App\Services\Analytics;

/**
 * Presentation formatting shared by the analytics views and chart
 * components. Values arrive as raw numbers; every string the reader sees is
 * produced here so the same number formats identically in a chart, a table
 * and a CSV.
 */
class Formats
{
    public static function money(float|int|null $value): string
    {
        return '৳'.number_format((float) ($value ?? 0), 2);
    }

    public static function moneyCompact(float|int|null $value): string
    {
        return '৳'.self::numberCompact($value);
    }

    public static function numberCompact(float|int|null $value): string
    {
        $value = (float) ($value ?? 0);
        $abs = abs($value);

        if ($abs >= 1000000) {
            return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.').'M';
        }

        if ($abs >= 1000) {
            return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.').'K';
        }

        return number_format($value, $abs >= 100 || fmod($value, 1) == 0.0 ? 0 : 1);
    }

    public static function number(float|int|null $value): string
    {
        return number_format((float) ($value ?? 0), 0);
    }

    public static function percent(float|int|null $value): string
    {
        return number_format((float) ($value ?? 0), 1).'%';
    }

    /**
     * Signed percentage for delta chips: null renders as an em dash because
     * no honest percentage exists.
     */
    public static function signedPercent(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        return ($value >= 0 ? '+' : '').number_format($value, 1).'%';
    }

    public static function duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        if ($seconds < 60) {
            return $seconds.'s';
        }

        if ($seconds < 3600) {
            return (int) round($seconds / 60).'m';
        }

        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);

        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
    }
}
