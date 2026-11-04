{{--
    Line/area chart rendered as server-computed SVG.

    $series: list of ['name' => string, 'values' => list<float|null>, 'color' => css color,
                      'area' => bool (default false), 'dashed' => bool (default false)]
             null values break the line (used by the previous-period overlay).
    $projection: ['values' => list<float>]|null — drawn dashed past the last bucket.
    $regression: ['slope' => float, 'intercept' => float]|null — dashed reference line.
    $anomalies: list<['z' => float, 'flagged' => bool]>|null — critical rings on flagged points.

    Tooltips are driven by the shared `ssChart` Alpine component; the tooltip
    body is written with x-text so database-derived labels are never HTML.
--}}
@props([
    'labels' => [],
    'series' => [],
    'projection' => null,
    'regression' => null,
    'anomalies' => null,
    'format' => 'money',
    'ariaLabel' => 'Trend chart',
])

@php
    $W = 640;
    $H = 300;
    $padL = 54;
    $padR = 16;
    $padT = 16;
    $padB = 32;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;

    $n = count($labels);
    $projectionCount = is_array($projection) ? count($projection['values']) : 0;
    $totalSlots = max($n + $projectionCount, 2);
    $step = $plotW / ($totalSlots - 1);

    $xAt = fn (int $index): float => $padL + $index * $step;

    $allValues = [];

    foreach ($series as $entry) {
        foreach ($entry['values'] as $value) {
            if ($value !== null) {
                $allValues[] = (float) $value;
            }
        }
    }

    if (is_array($projection)) {
        foreach ($projection['values'] as $value) {
            $allValues[] = (float) $value;
        }
    }

    $yMax = \App\Services\Analytics\AnalyticsMath::niceCeil(
        (float) (count($allValues) > 0 ? max($allValues) : 0)
    );

    $scaleY = fn (float $value): float => $padT + $plotH * (1 - min(max($value, 0), $yMax) / $yMax);

    $valueFmt = $format === 'money'
        ? fn (float $value): string => \App\Services\Analytics\Formats::money($value)
        : fn (float $value): string => \App\Services\Analytics\Formats::number($value);

    $axisFmt = $format === 'money'
        ? fn (float $value): string => \App\Services\Analytics\Formats::moneyCompact($value)
        : fn (float $value): string => \App\Services\Analytics\Formats::numberCompact($value);

    $drawnSeries = [];

    foreach ($series as $entry) {
        $segments = [];
        $current = [];

        foreach ($entry['values'] as $index => $value) {
            if ($value === null) {
                if (count($current) > 1) {
                    $segments[] = $current;
                }

                $current = [];

                continue;
            }

            $current[] = [round($xAt($index), 1), round($scaleY((float) $value), 1)];
        }

        if (count($current) > 1) {
            $segments[] = $current;
        }

        $pathD = '';

        foreach ($segments as $segment) {
            $pathD .= ' '.collect($segment)
                ->map(fn (array $point, int $key): string => ($key === 0 ? 'M' : 'L')."{$point[0]} {$point[1]}")
                ->implode(' ');
        }

        $areaD = '';

        if (! empty($entry['area']) && $segments !== []) {
            $first = $segments[0];
            $baseline = $padT + $plotH;
            $areaD = collect($first)
                ->map(fn (array $point, int $key): string => ($key === 0 ? 'M' : 'L')."{$point[0]} {$point[1]}")
                ->implode(' ')." L{$first[count($first) - 1][0]} {$baseline} L{$first[0][0]} {$baseline} Z";
        }

        $drawnSeries[] = $entry + [
            'pathD' => trim($pathD),
            'areaD' => $areaD,
            'display' => array_map(
                fn ($value) => $value === null ? null : $valueFmt((float) $value),
                $entry['values'],
            ),
            'hasData' => in_array(false, array_map('is_null', $entry['values']), true),
        ];
    }

    // Tooltip payload: one slot per historical bucket plus the projection.
    $slots = [];

    for ($index = 0; $index < $n; $index++) {
        $rows = [];

        foreach ($drawnSeries as $entry) {
            if ($entry['display'][$index] !== null) {
                $rows[] = ['label' => $entry['name'], 'value' => $entry['display'][$index], 'color' => $entry['color']];
            }
        }

        if (is_array($anomalies) && ($anomalies[$index]['flagged'] ?? false)) {
            $rows[] = ['label' => 'unusual (z-score)', 'value' => number_format((float) $anomalies[$index]['z'], 1), 'color' => '#dc2626'];
        }

        $slots[] = ['x' => round($xAt($index), 1), 'title' => $labels[$index], 'rows' => $rows];
    }

    $projectionSlots = [];

    if (is_array($projection) && $n > 0) {
        foreach ($projection['values'] as $offset => $value) {
            $projectionSlots[] = [
                'x' => round($xAt($n + $offset), 1),
                'title' => 'Projection',
                'rows' => [[
                    'label' => $drawnSeries[0]['name'] ?? 'Projection',
                    'value' => $valueFmt((float) $value),
                    'color' => $drawnSeries[0]['color'] ?? 'var(--ss-chart-dim)',
                ]],
            ];
        }
    }

    $chartData = json_encode(['width' => $W, 'slots' => array_merge($slots, $projectionSlots)]);

    $labelEvery = max(1, (int) ceil($n / 8));
    // Small axes get fewer, coarser ticks so a ৳1 ceiling doesn't
    // advertise fake precision like "৳0.8".
    $tickFractions = $yMax > 2 ? [0.0, 0.25, 0.5, 0.75, 1.0] : [0.0, 0.5, 1.0];
    $ticks = $tickFractions;
    $baseline = $padT + $plotH;
@endphp

<div x-data="ssChart" @pointermove="track($event)" @pointerleave="leave()"
     class="relative" {{ $attributes }}>
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full select-none" role="img"
         aria-label="{{ $ariaLabel }}" data-chart="{{ $chartData }}">
        {{-- Gridlines and y-axis: solid hairlines, recessive. --}}
        @foreach ($ticks as $tick)
            @php
                $tickValue = $yMax * $tick;
                $tickY = round($scaleY($tickValue), 1);
            @endphp
            <line x1="{{ $padL }}" y1="{{ $tickY }}" x2="{{ $W - $padR }}" y2="{{ $tickY }}"
                  stroke="var(--ss-chart-grid)" stroke-width="1" shape-rendering="crispEdges"/>
            <text x="{{ $padL - 8 }}" y="{{ $tickY + 3 }}" text-anchor="end"
                  font-size="10" fill="var(--ss-chart-muted)" font-variant-numeric="tabular-nums">
                {{ $axisFmt($tickValue) }}
            </text>
        @endforeach

        {{-- Baseline sits slightly darker than the grid. --}}
        <line x1="{{ $padL }}" y1="{{ $baseline }}" x2="{{ $W - $padR }}" y2="{{ $baseline }}"
              stroke="var(--ss-chart-axis)" stroke-width="1" shape-rendering="crispEdges"/>

        {{-- X labels, thinned to fit. --}}
        @foreach ($labels as $index => $label)
            @if ($index % $labelEvery === 0)
                <text x="{{ round($xAt($index), 1) }}" y="{{ $H - 10 }}" text-anchor="middle"
                      font-size="10" fill="var(--ss-chart-muted)">{{ $label }}</text>
            @endif
        @endforeach

        {{-- Crosshair (Alpine-controlled). --}}
        <line x1="0" y1="{{ $padT }}" x2="0" y2="{{ $baseline }}"
              stroke="var(--ss-chart-axis)" stroke-width="1"
              :x1="crossX ?? 0" :x2="crossX ?? 0"
              :style="crossX === null ? 'visibility:hidden' : 'visibility:visible'"/>

        {{-- Area wash (~10% opacity), then the 2px lines. --}}
        @foreach ($drawnSeries as $entry)
            @if ($entry['areaD'])
                <path d="{{ $entry['areaD'] }}" fill="{{ $entry['color'] }}" opacity="0.1"/>
            @endif
        @endforeach

        @foreach ($drawnSeries as $entry)
            @if ($entry['pathD'])
                <path d="{{ $entry['pathD'] }}" fill="none" stroke="{{ $entry['color'] }}"
                      stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      @if (! empty($entry['dashed'])) stroke-dasharray="4 5" @endif/>
            @endif
        @endforeach

        {{-- Fitted trend over the historical domain (reference, dashed). --}}
        @if (is_array($regression) && $n > 1)
            @php
                $fitStart = $scaleY((float) $regression['intercept']);
                $fitEnd = $scaleY((float) $regression['intercept'] + ((float) $regression['slope'] * ($n - 1)));
                $fitEndX = round($xAt($n - 1), 1);
            @endphp
            <line x1="{{ round($xAt(0), 1) }}" y1="{{ round($fitStart, 1) }}"
                  x2="{{ $fitEndX }}" y2="{{ round($fitEnd, 1) }}"
                  stroke="var(--ss-chart-dim)" stroke-width="1.5" stroke-dasharray="3 6"/>
            <text x="{{ $fitEndX }}" y="{{ round(min($fitEnd, $baseline - 6), 1) - 6 }}"
                  text-anchor="end" font-size="10" fill="var(--ss-chart-muted)">trend</text>
        @endif

        {{-- Projection: dashed continuation + open marker + label. --}}
        @if (is_array($projection) && $n > 0)
            @php
                $lastHistValue = $drawnSeries[0]['values'][$n - 1] ?? null;
            @endphp
            @if ($lastHistValue !== null)
                @php
                    $projectionPath = 'M'.round($xAt($n - 1), 1).' '.round($scaleY((float) $lastHistValue), 1)
                        .collect($projection['values'])->map(function ($value, $offset) use ($xAt, $scaleY, $n): string {
                            return ' L'.round($xAt($n + $offset), 1).' '.round($scaleY((float) $value), 1);
                        })->implode('');
                    $lastProjectionX = round($xAt($n - 1 + count($projection['values'])), 1);
                    $lastProjectionY = round($scaleY((float) end($projection['values'])), 1);
                @endphp
                <path d="{{ $projectionPath }}" fill="none" stroke="{{ $drawnSeries[0]['color'] ?? 'var(--ss-chart-dim)' }}"
                      stroke-width="2" stroke-linecap="round" stroke-dasharray="2 7"/>
                <circle cx="{{ $lastProjectionX }}" cy="{{ $lastProjectionY }}" r="4"
                        fill="#ffffff" stroke="{{ $drawnSeries[0]['color'] ?? 'var(--ss-chart-dim)' }}" stroke-width="2"/>
                <text x="{{ min($lastProjectionX, $W - $padR) }}" y="{{ max($padT + 10, $lastProjectionY - 10) }}"
                      text-anchor="end" font-size="10" fill="var(--ss-chart-muted)">Projection</text>
            @endif
        @endif

        {{-- Anomaly rings on statistically unusual days. --}}
        @if (is_array($anomalies))
            @foreach ($anomalies as $index => $anomaly)
                @if ($anomaly['flagged'] && ($drawnSeries[0]['values'][$index] ?? null) !== null)
                    <circle cx="{{ round($xAt($index), 1) }}" cy="{{ round($scaleY((float) $drawnSeries[0]['values'][$index]), 1) }}"
                            r="6.5" fill="none" stroke="var(--ss-status-critical)" stroke-width="1.5"
                            opacity="0.9"/>
                @endif
            @endforeach
        @endif

        {{-- End marker of the primary series. --}}
        @if ($n > 0 && ($drawnSeries[0]['values'][$n - 1] ?? null) !== null)
            <circle cx="{{ round($xAt($n - 1), 1) }}" cy="{{ round($scaleY((float) $drawnSeries[0]['values'][$n - 1]), 1) }}"
                    r="4" fill="{{ $drawnSeries[0]['color'] }}" stroke="#ffffff" stroke-width="2"/>
        @endif
    </svg>

    {{-- Tooltip: content written via x-text; values lead, labels follow. --}}
    <div class="ss-chart-tooltip" x-cloak x-show="open"
         :style="`left:${flip ? x - 232 : x + 14}px; top:${Math.max(y - 40, 0)}px`">
        <p class="mb-1 text-xs font-semibold text-gray-900" x-text="title"></p>
        <template x-for="(row, rowIndex) in rows" :key="row.label + rowIndex">
            <div class="flex items-center justify-between gap-4">
                <span class="flex items-center text-gray-600">
                    <span class="ss-chart-tooltip-key" :style="`background:${row.color || 'var(--ss-chart-dim)'}`"></span>
                    <span x-text="row.label"></span>
                </span>
                <span class="ss-chart-tooltip-value" x-text="row.value"></span>
            </div>
        </template>
    </div>

    {{-- Legend: only when more than one series is drawn. --}}
    @if (collect($drawnSeries)->filter(fn ($entry): bool => $entry['hasData'])->count() > 1)
        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
            @foreach ($drawnSeries as $entry)
                @if ($entry['hasData'])
                    <span class="flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="inline-block h-[3px] w-3 rounded-full" style="background: {{ $entry['color'] }}"
                              aria-hidden="true"></span>
                        {{ $entry['name'] }}
                    </span>
                @endif
            @endforeach
        </div>
    @endif
</div>
