@props([
    'label',
    'value',
    'delta' => null,
    'deltaLabel' => 'vs previous period',
    'invert' => false,
    'sparkline' => null,
    'hero' => false,
])

@php
    $showDelta = is_numeric($delta);
    $isUp = is_numeric($delta) && $delta > 0;
    $isGood = is_numeric($delta) && $delta !== 0.0
        ? ($invert ? ! $isUp : $isUp)
        : null;

    $sparkPoints = null;

    if (is_array($sparkline) && count($sparkline) > 1) {
        $min = min($sparkline);
        $max = max($sparkline);
        $range = $max - $min;

        if ($range <= 0) {
            $range = 1;
        }

        $sparkPoints = [];

        foreach ($sparkline as $index => $point) {
            $x = $index * (96 / (count($sparkline) - 1));
            $sparkY = 28 - ((($point - $min) / $range) * 24);

            $sparkPoints[] = [round($x, 1), round($sparkY, 1)];
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>

    <p class="mt-1.5 font-semibold tracking-tight text-gray-900 {{ $hero ? 'text-4xl sm:text-5xl' : 'text-2xl' }}">
        {{ $value }}
    </p>

    @if ($showDelta || $sparkPoints !== null)
        <div class="mt-2.5 flex items-center justify-between gap-3">
            @if ($showDelta)
                <p class="text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 font-medium tabular-nums {{ $isGood === null
                        ? 'bg-gray-100 text-gray-600'
                        : ($isGood ? 'bg-emerald-50 text-[color:var(--ss-delta-up)]' : 'bg-rose-50 text-[color:var(--ss-delta-down)]') }}">
                        @if ($isUp)
                            <svg class="h-2.5 w-2.5" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true"><path d="M5 1l4 6H1z"/></svg>
                        @elseif (is_numeric($delta) && $delta < 0)
                            <svg class="h-2.5 w-2.5" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true"><path d="M5 9L1 3h8z"/></svg>
                        @endif
                        {{ \App\Services\Analytics\Formats::signedPercent(is_numeric($delta) ? (float) $delta : null) }}
                    </span>
                    <span class="ml-1">{{ $deltaLabel }}</span>
                </p>
            @endif

            @if ($sparkPoints !== null)
                <svg viewBox="0 0 96 32" class="h-8 w-24 shrink-0 overflow-visible" aria-hidden="true">
                    <polyline
                        points="{{ collect($sparkPoints)->map(fn (array $point): string => implode(',', $point))->implode(' ') }}"
                        fill="none"
                        stroke="var(--ss-chart-dim)"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    {{-- Current period (last point) in the accent hue. --}}
                    @if (count($sparkPoints) >= 2)
                        @php
                            [$last, $prev] = [$sparkPoints[count($sparkPoints) - 1], $sparkPoints[count($sparkPoints) - 2]];
                        @endphp
                        <line x1="{{ $prev[0] }}" y1="{{ $prev[1] }}" x2="{{ $last[0] }}" y2="{{ $last[1] }}"
                              stroke="var(--ss-chart-1)" stroke-width="2" stroke-linecap="round"/>
                    @endif
                    <circle cx="{{ $last[0] }}" cy="{{ $last[1] }}" r="2.5" fill="var(--ss-chart-1)"
                            stroke="#ffffff" stroke-width="1.5"/>
                </svg>
            @endif
        </div>
    @endif
</div>
