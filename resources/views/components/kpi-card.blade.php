{{--
    Reference-style KPI card: metric title, large value, small subtitle and
    an icon tile. The optional delta chip compares the selected analytics
    window against the previous one and renders only when a numeric delta
    exists (null deltas mean "not computable", e.g. an empty previous
    window). Pass `invert` for metrics where going up is bad.
--}}
@props([
    'title',
    'value',
    'subtitle',
    'icon',
    'delta' => null,
    'deltaLabel' => 'vs previous period',
    'invert' => false,
    'href' => null,
])

@php
    $showDelta = is_numeric($delta);
    $isUp = is_numeric($delta) && (float) $delta > 0;
    $isGood = is_numeric($delta) && (float) $delta !== 0.0
        ? ($invert ? ! $isUp : $isUp)
        : null;
    // When a destination is given the card renders as a real link: same
    // visual markup, but the whole card is clickable and keyboard-focusable.
    $asLink = filled($href);
@endphp

<{{ $asLink ? 'a' : 'article' }} @if ($asLink) href="{{ $href }}" @endif
    class="ss-motion-card block rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm" {{ $attributes }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="mt-3 break-words text-2xl font-bold tracking-tight text-gray-900">
                {{ $value }}
            </p>
        </div>
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl"
              aria-hidden="true">
            {{ $icon }}
        </span>
    </div>

    <p class="mt-3 text-xs leading-5 text-gray-500">{{ $subtitle }}</p>

    @if ($showDelta)
        <p class="mt-2 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 font-medium tabular-nums {{ $isGood === null
                ? 'bg-gray-100 text-gray-600'
                : ($isGood ? 'bg-emerald-50 text-[color:var(--ss-delta-up)]' : 'bg-rose-50 text-[color:var(--ss-delta-down)]') }}">
                @if ($isUp)
                    <svg class="h-2.5 w-2.5" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true"><path d="M5 1l4 6H1z"/></svg>
                @elseif ((float) $delta < 0)
                    <svg class="h-2.5 w-2.5" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true"><path d="M5 9L1 3h8z"/></svg>
                @endif
                {{ \App\Services\Analytics\Formats::signedPercent((float) $delta) }}
            </span>
            <span class="ml-1">{{ $deltaLabel }}</span>
        </p>
    @endif
</{{ $asLink ? 'a' : 'article' }}>
