{{--
    Ranked horizontal bars for nominal categories (products, shops,
    categories): one hue for every bar — bar length already carries the
    value, so color is not re-encoding it. 4px rounded data-end, square at
    the baseline, value at the tip.
--}}
@props([
    'items' => [],
    'color' => 'var(--ss-chart-1)',
    'ariaLabel' => 'Ranked bars',
])

@php
    $max = collect($items)->max('value');

    $rows = collect($items)->map(function (array $item) use ($max, $color): array {
        $width = $max > 0 && $item['value'] > 0
            ? max(1.5, round(((float) $item['value'] / (float) $max) * 100, 1))
            : 0;

        return $item + [
            'width' => $width,
            'tip' => json_encode([
                'title' => $item['name'],
                'rows' => [['label' => $item['label'] ?? 'value', 'value' => $item['display'], 'color' => $color]],
            ]),
        ];
    });
@endphp

<div x-data="ssChart" @pointermove="mark($event)" @pointerleave="leave()"
     @focusin.capture="focusMark($event)"
     class="relative space-y-2.5" role="img" aria-label="{{ $ariaLabel }}" {{ $attributes }}>
    @foreach ($rows as $row)
        <div class="flex items-center gap-3" tabindex="0" data-tip="{{ $row['tip'] }}"
             aria-label="{{ $row['name'] }}: {{ $row['display'] }}">
            <span class="w-32 shrink-0 truncate text-sm text-gray-600 sm:w-40" title="{{ $row['name'] }}">
                {{ $row['name'] }}
            </span>

            <span class="h-4 flex-1">
                <span class="block h-4 rounded-r transition-[width] duration-300" style="width: {{ $row['width'] }}%; background: {{ $color }}"></span>
            </span>

            <span class="w-24 shrink-0 text-right text-sm font-medium tabular-nums text-gray-900">
                {{ $row['display'] }}
            </span>
        </div>
    @endforeach
</div>
