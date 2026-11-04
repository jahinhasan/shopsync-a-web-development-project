{{--
    Weekday x time-of-day order density on a single-hue sequential ramp
    (light = quiet, dark = busy). Needs at least two weeks of data upstream
    or it is not rendered at all.
--}}
@props([
    'cells' => [],
    'max' => 1,
    'slotLabels' => ['0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22'],
    'dayLabels' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    'ariaLabel' => 'Orders by weekday and time of day',
])

<div x-data="ssChart" @pointermove="mark($event)" @pointerleave="leave()"
     @focusin.capture="focusMark($event)" class="relative" {{ $attributes }}>
    <div class="flex items-center gap-2" role="img" aria-label="{{ $ariaLabel }}">
        <span class="w-9 shrink-0" aria-hidden="true"></span>

        <div class="grid flex-1 grid-cols-12 gap-1" aria-hidden="true">
            @foreach ($slotLabels as $label)
                <span class="text-center text-[10px] text-gray-400">{{ $label }}</span>
            @endforeach
        </div>
    </div>

    @foreach ($cells as $dayIndex => $row)
        <div class="mt-1 flex items-center gap-2">
            <span class="w-9 shrink-0 text-[11px] font-medium text-gray-500" aria-hidden="true">
                {{ $dayLabels[$dayIndex] ?? '' }}
            </span>

            <div class="grid flex-1 grid-cols-12 gap-1">
                @foreach ($row as $slotIndex => $count)
                    @php
                        $step = $max > 0 && $count > 0
                            ? min(7, max(1, (int) ceil(($count / $max) * 7)))
                            : 0;
                        $background = $step === 0 ? 'var(--ss-chart-grid)' : "var(--ss-seq-{$step})";
                        $tip = json_encode([
                            'title' => ($dayLabels[$dayIndex] ?? '').' '.($slotLabels[$slotIndex] ?? '').':00–'.($slotLabels[$slotIndex] ?? '').':59',
                            'rows' => [['label' => 'orders', 'value' => \App\Services\Analytics\Formats::number($count), 'color' => $step > 0 ? 'var(--ss-seq-7)' : 'var(--ss-chart-dim)']],
                        ]);
                    @endphp
                    <span class="h-6 rounded-[3px]" style="background: {{ $background }}"
                          tabindex="0" data-tip="{{ $tip }}"
                          aria-label="{{ $dayLabels[$dayIndex] ?? '' }} {{ $slotLabels[$slotIndex] ?? '' }}: {{ \App\Services\Analytics\Formats::number($count) }} orders"></span>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Scale legend: one hue, light to dark. --}}
    <div class="mt-3 flex items-center gap-1.5 text-[10px] text-gray-500">
        <span class="mr-1">Less</span>
        <span class="h-3 w-5 rounded-[3px]" style="background: var(--ss-chart-grid)" aria-hidden="true"></span>
        @foreach (range(1, 7) as $step)
            <span class="h-3 w-5 rounded-[3px]" style="background: var(--ss-seq-{{ $step }})" aria-hidden="true"></span>
        @endforeach
        <span class="ml-1">More</span>
    </div>

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
