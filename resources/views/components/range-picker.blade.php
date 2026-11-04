{{--
    Dashboard analytics window picker: plain GET links (works without
    JavaScript), server-validated by WindowResolver::dashboard(). The active
    preset is highlighted and marked aria-current.
--}}
@props([
    'routeName',
    'active' => '30d',
])

@php
    $options = [
        '7d' => '7 days',
        '30d' => '30 days',
        '90d' => '90 days',
        '12m' => '12 months',
    ];
@endphp

<nav {{ $attributes }} class="flex flex-wrap items-center gap-2" aria-label="Analytics date range">
    @foreach ($options as $key => $label)
        <a href="{{ route($routeName, ['window' => $key]) }}"
           @if ($key === $active) aria-current="page" @endif
           class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $key === $active
               ? 'bg-indigo-600 text-white shadow-sm'
               : 'bg-white text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
