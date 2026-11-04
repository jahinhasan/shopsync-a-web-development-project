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
    private const PRESETS = ['7d', '30d', '90d', 'mtd', 'ytd', 'all'];

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
            'mtd' => now()->startOfMonth(),
            'ytd' => now()->startOfYear(),
            'all' => Carbon::parse(
                DB::table('orders')->min('created_at') ?? now()->toDateString(),
            )->startOfDay(),
            default => now()->startOfMonth(),
        };
    }
}
