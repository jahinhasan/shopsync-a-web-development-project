{{--
    Segmented bar: one whole split into parts (max 6 — the tail is folded
    into "Other" upstream). Tall mode prints each part's share inside its
    block where the label fits; thin mode shows no text and leans on the
    figures beside it. Legend lists names in block order.
--}}
@props([
    'items' => [],
    'variant' => 'tall',
    'showLegend' => true,
    'ariaLabel' => 'Segmented bar',
])

@php
    $total = (float) collect($items)->sum('value');

    $parts = collect($items)->map(function (array $item) use ($total): array {
        $share = $total > 0 ? ((float) $item['value'] / $total) * 100 : 0;

        return $item + [
            'share' => $share,
            'tip' => json_encode([
                'title' => $item['label'],
                'rows' => [
                    ['label' => 'value', 'value' => $item['display'], 'color' => $item['color']],
                    ['label' => 'share', 'value' => \App\Services\Analytics\Formats::percent($share), 'color' => null],
                ],
            ]),
        ];
    });
@endphp

<div x-data="ssChart" @pointermove="mark($event)" @pointerleave="leave()"
     @focusin.capture="focusMark($event)" class="relative" {{ $attributes }}>
    <div class="flex {{ $variant === 'tall' ? 'h-7' : 'h-2' }} w-full gap-[2px]"
         role="img" aria-label="{{ $ariaLabel }}">
        @foreach ($parts as $part)
            @if ($part['value'] > 0)
                <span class="flex min-w-[3px] items-center justify-center rounded-[4px] {{ $variant === 'tall' ? 'leading-7' : '' }}"
                      style="width: {{ round($part['share'], 2) }}%; background: {{ $part['color'] }}"
                      tabindex="0" data-tip="{{ $part['tip'] }}"
                      aria-label="{{ $part['label'] }}: {{ $part['display'] }} ({{ \App\Services\Analytics\Formats::percent($part['share']) }})">
                    @if ($variant === 'tall' && $part['share'] >= 10)
                        <span class="px-1 text-[10px] font-semibold text-white">{{ $part['short'] ?? $part['display'] }}</span>
                    @endif
                </span>
            @endif
        @endforeach
    </div>

    @if ($showLegend)
        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1">
            @foreach ($parts as $part)
                <span class="flex items-center gap-1.5 text-xs text-gray-600">
                    <span class="inline-block h-2.5 w-2.5 rounded-[3px]" style="background: {{ $part['color'] }}"
                          aria-hidden="true"></span>
                    {{ $part['label'] }}
                </span>
            @endforeach
        </div>
    @endif
</div>
