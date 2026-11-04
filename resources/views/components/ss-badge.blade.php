@props(['value'])

@php
    // Status colors are reserved semantics: good/warning/critical plus
    // neutral in-progress states. Never reused as chart series colors.
    $palette = [
        'delivered' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'paid' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'approved' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'processing' => ['bg-indigo-50 text-indigo-700 ring-indigo-600/20', 'bg-indigo-500'],
        'shipped' => ['bg-sky-50 text-sky-700 ring-sky-600/20', 'bg-sky-500'],
        'pending' => ['bg-amber-50 text-amber-800 ring-amber-600/20', 'bg-amber-500'],
        'cancelled' => ['bg-rose-50 text-rose-700 ring-rose-600/20', 'bg-rose-500'],
        'unpaid' => ['bg-gray-100 text-gray-600 ring-gray-500/20', 'bg-gray-400'],
    ];

    [$classes, $dotClass] = $palette[strtolower(trim((string) $value))]
        ?? ['bg-gray-100 text-gray-600 ring-gray-500/20', 'bg-gray-400'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    {{ ucfirst(strtolower(trim((string) $value))) }}
</span>
