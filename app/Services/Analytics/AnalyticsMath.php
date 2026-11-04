<?php

namespace App\Services\Analytics;

/**
 * Pure statistical helpers used by the analytics reports. Every function is
 * side-effect free so the numeric behavior can be pinned by unit tests.
 *
 * The guards here enforce the analytics data-integrity rules: statistical
 * techniques (regression, z-scores) report null instead of a weak value when
 * the series is too short to support the claim.
 */
class AnalyticsMath
{
    /**
     * Least-squares linear regression over an evenly spaced series
     * (x = 0, 1, 2, ...).
     *
     * @param  list<float|int>  $values
     * @return array{slope: float, intercept: float, r2: float}|null null when fewer than 3 points or zero variance.
     */
    public static function linearRegression(array $values): ?array
    {
        $n = count($values);

        if ($n < 3) {
            return null;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumXX = 0.0;
        $sumYY = 0.0;

        foreach ($values as $x => $y) {
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumXX += $x * $x;
            $sumYY += $y * $y;
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);

        if ($denominator == 0.0) {
            return null;
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $varianceY = ($n * $sumYY) - ($sumY * $sumY);

        if ($varianceY == 0.0) {
            return null;
        }

        // R² = SSR/SST = slope²·Sxx / Syy for simple linear regression.
        $r2 = (($slope ** 2) * $denominator) / $varianceY;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r2' => max(0.0, min(1.0, $r2)),
        ];
    }

    /**
     * Simple moving average. Values before the window is full are null so a
     * chart never presents a partial window as if it were real.
     *
     * @param  list<float|int>  $values
     * @return list<float|null>
     */
    public static function movingAverage(array $values, int $window = 7): array
    {
        if ($window < 2 || $values === []) {
            return array_fill(0, count($values), null);
        }

        $output = [];

        foreach ($values as $index => $value) {
            if ($index + 1 < $window) {
                $output[] = null;

                continue;
            }

            $output[] = array_sum(array_slice($values, $index + 1 - $window, $window)) / $window;
        }

        return $output;
    }

    /**
     * Running sum (discrete integral) of a series.
     *
     * @param  list<float|int>  $values
     * @return list<float>
     */
    public static function cumulative(array $values): array
    {
        $total = 0.0;

        $output = [];

        foreach ($values as $value) {
            $total += $value;

            $output[] = $total;
        }

        return $output;
    }

    /**
     * Linear-interpolated percentile (0-100). Returns null for an empty set.
     *
     * @param  list<float|int>  $values
     */
    public static function percentile(array $values, float $p): ?float
    {
        if ($values === []) {
            return null;
        }

        $sorted = array_values($values);

        sort($sorted);

        if (count($sorted) === 1) {
            return (float) $sorted[0];
        }

        $position = (count($sorted) - 1) * ($p / 100);

        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        if ($lower === $upper) {
            return (float) $sorted[$lower];
        }

        $fraction = $position - $lower;

        return $sorted[$lower] + (($sorted[$upper] - $sorted[$lower]) * $fraction);
    }

    public static function median(array $values): ?float
    {
        return self::percentile($values, 50);
    }

    public static function mean(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return array_sum($values) / count($values);
    }

    /**
     * Population standard deviation. Null when the series is empty or has no
     * spread (a constant series cannot support deviation-based claims).
     *
     * @param  list<float|int>  $values
     */
    public static function standardDeviation(array $values): ?float
    {
        $mean = self::mean($values);

        if ($mean === null || count($values) < 2) {
            return null;
        }

        $variance = array_reduce(
            $values,
            fn (float $carry, $value): float => $carry + (($value - $mean) ** 2),
            0.0,
        ) / count($values);

        if ($variance == 0.0) {
            return null;
        }

        return sqrt($variance);
    }

    /**
     * Z-scores for a series, flagging |z| > 2 only when there are at least 10
     * observations and a non-zero deviation — below that a flag is noise, not
     * an insight.
     *
     * @param  list<float|int>  $values
     * @return array<int, array{z: float, flagged: bool}>
     */
    public static function zScores(array $values): array
    {
        $sd = self::standardDeviation($values);
        $mean = self::mean($values);

        $output = [];

        foreach ($values as $value) {
            if ($sd === null || $mean === null || count($values) < 10) {
                $output[] = ['z' => 0.0, 'flagged' => false];

                continue;
            }

            $z = ($value - $mean) / $sd;

            $output[] = ['z' => $z, 'flagged' => abs($z) > 2];
        }

        return $output;
    }

    /**
     * Percentage change from previous to current. Null when the previous
     * period is zero (no honest percentage exists); an infinity-like number
     * would mislead.
     */
    public static function percentageChange(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null || $previous == 0.0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Round up to a clean axis maximum: 1/2/5 × 10^k (60 → 100, 320 → 500).
     */
    public static function niceCeil(float $value): float
    {
        if ($value <= 0) {
            return 1.0;
        }

        $exponent = floor(log10($value));
        $base = 10 ** $exponent;
        $mantissa = $value / $base;

        $step = match (true) {
            $mantissa <= 1 => 1.0,
            $mantissa <= 2 => 2.0,
            $mantissa <= 5 => 5.0,
            default => 10.0,
        };

        return $step * $base;
    }

    /**
     * Weekday index (0 = Sunday) compatible across drivers: MySQL
     * DAYOFWEEK() is 1-7 starting Sunday, SQLite strftime('%w') is 0-6
     * starting Sunday.
     */
    public static function weekdayFromDriverValue(int $value): int
    {
        return $value % 7;
    }
}
