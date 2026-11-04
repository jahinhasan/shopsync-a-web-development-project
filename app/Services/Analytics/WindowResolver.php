<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Resolves the shared reporting window: preset chips expand to concrete
 * dates, custom from/to dates are validated with the same rules the reports
 * always used, and the granularity/compare switches are normalized.
 *
 * @return array{from: Carbon, to: Carbon, granularity: string, compare: bool, preset: ?string}
 */
class WindowResolver
{
    private const PRESETS = ['7d', '30d', '90d', '12m', 'mtd', 'ytd', 'all'];

    /** @var list<string> presets offered on the dashboards */
    public const DASHBOARD_WINDOWS = ['7d', '30d', '90d', '12m'];

    public static function resolve(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'preset' => ['nullable', 'string', 'in:'.implode(',', self::PRESETS)],
            'granularity' => ['nullable', 'string', 'in:'.implode(',', AnalyticsService::GRANULARITIES)],
            'compare' => ['nullable', 'boolean'],
        ]);

        $preset = $validated['preset'] ?? null;

        $to = Carbon::parse($validated['to'] ?? now()->toDateString());
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])
            : self::defaultFrom($preset, $to);

        if ($from->greaterThan(now()->endOfDay())) {
            throw ValidationException::withMessages([
                'from' => 'Start date cannot be in the future.',
            ]);
        }

        return [
            'from' => $from->startOfDay(),
            'to' => $to->endOfDay(),
            'granularity' => $validated['granularity'] ?? 'daily',
            'compare' => (bool) ($validated['compare'] ?? false),
            'preset' => $preset,
        ];
    }

    private static function defaultFrom(?string $preset, Carbon $to): Carbon
    {
        return match ($preset) {
            '7d' => $to->copy()->subDays(6),
            '30d' => $to->copy()->subDays(29),
            '90d' => $to->copy()->subDays(89),
            '12m' => $to->copy()->subDays(364),
            'mtd' => now()->startOfMonth(),
            'ytd' => now()->startOfYear(),
            'all' => Carbon::parse(
                DB::table('orders')->min('created_at') ?? now()->toDateString(),
            )->startOfDay(),
            default => now()->startOfMonth(),
        };
    }

    /**
     * Window preset for the dashboards: chip-driven, comparison always on,
     * bucket size chosen so the chart stays readable (7d/30d daily,
     * 90d weekly, 12m monthly).
     *
     * @return array{window: string, from: Carbon, to: Carbon, granularity: string, compare: bool, days: int, label: string, prevLabel: string}
     */
    public static function dashboard(Request $request): array
    {
        $validated = $request->validate([
            'window' => ['nullable', 'string', 'in:'.implode(',', self::DASHBOARD_WINDOWS)],
        ]);

        return self::expandDashboardWindow($validated['window'] ?? '30d');
    }

    /**
     * @return array{window: string, from: Carbon, to: Carbon, granularity: string, compare: bool, days: int, label: string, prevLabel: string}
     */
    public static function expandDashboardWindow(string $window): array
    {
        $window = in_array($window, self::DASHBOARD_WINDOWS, true) ? $window : '30d';

        [$days, $granularity, $label] = match ($window) {
            '7d' => [7, 'daily', 'Last 7 days'],
            '90d' => [90, 'weekly', 'Last 90 days'],
            '12m' => [365, 'monthly', 'Last 12 months'],
            default => [30, 'daily', 'Last 30 days'],
        };

        return [
            'window' => $window,
            'from' => now()->subDays($days - 1)->startOfDay(),
            'to' => now()->endOfDay(),
            'granularity' => $granularity,
            'compare' => true,
            'days' => $days,
            'label' => $label,
            'prevLabel' => 'previous '.$days.' days',
        ];
    }
}
