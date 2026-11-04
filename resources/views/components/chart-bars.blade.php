{{--
    Vertical bar chart (columns) as server-computed SVG.
    Bars stay <= 24px thick, grow from a single baseline, carry a 4px
    rounded data-end and sit square on the axis. Each bar has a full-height
    transparent hit target (hover + keyboard focus) feeding the shared
    ssChart tooltip.
--}}
@props([
    'labels' => [],
    'values' => [],
    'color' => 'var(--ss-chart-1)',
    'format' => 'money',
    'ariaLabel' => 'Bar chart',
    'height' => 240,
])

@php
    $W = 640;
    $H = (int) $height;
    $padL = 54;
    $padR = 16;
    $padT = 12;
    $padB = 32;
    $plotW = $W - $padL - $padR;
    $plotH = $H - $padT - $padB;
    $baseline = $padT + $plotH;

    $n = count($values);
    $yMax = \App\Services\Analytics\AnalyticsMath::niceCeil((float) (count($values) > 0 ? max($values) : 0));

    $slotWidth = $n > 0 ? $plotW / $n : $plotW;
    $barWidth = min(24.0, $slotWidth * 0.7);

    $valueFmt = $format === 'money'
        ? fn (float $value): string => \App\Services\Analytics\Formats::money($value)
        : fn (float $value): string => \App\Services\Analytics\Formats::number($value);

    $axisFmt = $format === 'money'
        ? fn (float $value): string => \App\Services\Analytics\Formats::moneyCompact($value)
        : fn (float $value): string => \App\Services\Analytics\Formats::numberCompact($value);

    $bars = [];

    foreach ($values as $index => $value) {
        $value = (float) $value;
        $x = $padL + ($index * $slotWidth) + (($slotWidth - $barWidth) / 2);

        $top = $value > 0 ? $padT + ($plotH * (1 - min($value, $yMax) / $yMax)) : $baseline;
        $radius = min(4.0, $barWidth / 2, $baseline - $top);

        // Rounded top corners only; the baseline end stays square.
        $path = $value > 0
            ? sprintf(
                'M%s %s L%s %s A%s %s 0 0 1 %s %s L%s %s A%s %s 0 0 1 %s %s L%s %s Z',
                round($x, 1), round($baseline, 1),
                round($x, 1), round($top + $radius, 1),
                $radius, $radius, round($x + $radius, 1), round($top, 1),
                round($x + $barWidth - $radius, 1), round($top, 1),
                $radius, $radius, round($x + $barWidth, 1), round($top + $radius, 1),
                round($x + $barWidth, 1), round($baseline, 1),
            )
            : null;

        $bars[] = [
            'path' => $path,
            'hitX' => round($padL + ($index * $slotWidth), 1),
            'hitW' => round(max($slotWidth, 24), 1),
            'tip' => json_encode([
                'title' => $labels[$index] ?? '',
                'rows' => [['label' => 'value', 'value' => $valueFmt($value), 'color' => $color]],
            ]),
            'aria' => ($labels[$index] ?? '').': '.$valueFmt($value),
        ];
    }

    $labelEvery = max(1, (int) ceil($n / 8));
@endphp

<div x-data="ssChart" @pointermove="mark($event)" @pointerleave="leave()"
     @focusin.capture="focusMark($event)"
     class="relative" {{ $attributes }}>
    <svg viewBox="0 0 {{ $W }} {{ $H }}" class="h-auto w-full select-none" role="img"
         aria-label="{{ $ariaLabel }}">
        @foreach ($yMax > 2 ? [1.0, 0.75, 0.5, 0.25] : [1.0, 0.5] as $tick)
            @php
                $tickY = round($padT + $plotH * (1 - $tick), 1);
            @endphp
            <line x1="{{ $padL }}" y1="{{ $tickY }}" x2="{{ $W - $padR }}" y2="{{ $tickY }}"
                  stroke="var(--ss-chart-grid)" stroke-width="1" shape-rendering="crispEdges"/>
            <text x="{{ $padL - 8 }}" y="{{ $tickY + 3 }}" text-anchor="end"
                  font-size="10" fill="var(--ss-chart-muted)" font-variant-numeric="tabular-nums">
                {{ $axisFmt($yMax * $tick) }}
            </text>
        @endforeach
        <text x="{{ $padL - 8 }}" y="{{ $baseline + 3 }}" text-anchor="end" font-size="10"
              fill="var(--ss-chart-muted)" font-variant-numeric="tabular-nums">0</text>

        @foreach ($labels as $index => $label)
            @if ($index % $labelEvery === 0)
                <text x="{{ round($padL + ($index * $slotWidth) + ($slotWidth / 2), 1) }}"
                      y="{{ $H - 10 }}" text-anchor="middle" font-size="10"
                      fill="var(--ss-chart-muted)">{{ $label }}</text>
            @endif
        @endforeach

        @foreach ($bars as $bar)
            @if ($bar['path'])
                <path d="{{ $bar['path'] }}" fill="{{ $color }}"/>
            @endif
        @endforeach

        <line x1="{{ $padL }}" y1="{{ $baseline }}" x2="{{ $W - $padR }}" y2="{{ $baseline }}"
              stroke="var(--ss-chart-axis)" stroke-width="1" shape-rendering="crispEdges"/>

        {{-- Full-column hit targets: hover and keyboard both reach the tooltip. --}}
        @foreach ($bars as $bar)
            <rect x="{{ $bar['hitX'] }}" y="{{ $padT }}" width="{{ $bar['hitW'] }}" height="{{ $plotH }}"
                  fill="transparent" tabindex="0" role="img" aria-label="{{ $bar['aria'] }}"
                  data-tip="{{ $bar['tip'] }}"/>
        @endforeach
    </svg>

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
</div>
