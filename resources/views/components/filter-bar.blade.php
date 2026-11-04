@props([
    'routeName',
    'from',
    'to',
    'granularity' => 'daily',
    'compare' => false,
    'preset' => null,
    'showExport' => true,
])

@php
    // Preset chips carry the current granularity/compare so one click keeps
    // the rest of the view stable.
    $carried = array_filter([
        'granularity' => $granularity !== 'daily' ? $granularity : null,
        'compare' => $compare ? '1' : null,
    ]);

    $presets = [
        ['key' => '7d', 'label' => '7 days'],
        ['key' => '30d', 'label' => '30 days'],
        ['key' => '90d', 'label' => '90 days'],
        ['key' => 'mtd', 'label' => 'This month'],
        ['key' => 'ytd', 'label' => 'This year'],
        ['key' => 'all', 'label' => 'All time'],
    ];

    $exportUrl = route($routeName, array_filter(array_merge($carried, [
        'from' => $from,
        'to' => $to,
        'export' => $showExport ? 'csv' : null,
    ])));
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-4 shadow-sm']) }}>
    {{-- Date range first, presets before the custom range. --}}
    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Date range presets">
        @foreach ($presets as $item)
            <a href="{{ route($routeName, array_merge($carried, ['preset' => $item['key']])) }}"
               class="rounded-full px-3 py-1 text-xs font-medium transition-colors duration-200 {{ $preset === $item['key']
                   ? 'bg-gray-900 text-white'
                   : 'border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
               @if ($preset === $item['key']) aria-current="true" @endif>
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route($routeName) }}" class="mt-3 flex flex-wrap items-end gap-x-4 gap-y-3 border-t border-gray-100 pt-3">
        <div>
            <label for="filter-from" class="mb-1 block text-xs font-medium text-gray-500">From</label>
            <input id="filter-from" name="from" type="date" value="{{ $from }}"
                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="filter-to" class="mb-1 block text-xs font-medium text-gray-500">To</label>
            <input id="filter-to" name="to" type="date" value="{{ $to }}"
                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div>
            <label for="filter-granularity" class="mb-1 block text-xs font-medium text-gray-500">View by</label>
            <select id="filter-granularity" name="granularity"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (['daily' => 'Day', 'weekly' => 'Week', 'monthly' => 'Month'] as $key => $label)
                    <option value="{{ $key }}" @selected($granularity === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex items-center gap-2 pb-1.5 text-sm text-gray-600">
            <input type="checkbox" name="compare" value="1" @checked($compare)
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            Compare previous period
        </label>

        <div class="ml-auto flex items-center gap-2">
            @if ($showExport)
                <a href="{{ $exportUrl }}"
                   class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Export CSV
                </a>
            @endif

            <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                Apply
            </button>
        </div>
    </form>
</div>
