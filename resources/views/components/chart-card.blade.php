@props([
    'title',
    'subtitle' => null,
    'footnote' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>

            @if ($subtitle)
                <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($table)
            <div x-data="{ showTable: false }"
                 class="flex rounded-lg border border-gray-200 p-0.5 text-xs font-medium"
                 role="group"
                 aria-label="{{ $title }} view switch">
                <button type="button" @click="showTable = false"
                        class="rounded-md px-2.5 py-1 transition-colors duration-200"
                        :class="! showTable ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900'">
                    Chart
                </button>
                <button type="button" @click="showTable = true"
                        class="rounded-md px-2.5 py-1 transition-colors duration-200"
                        :class="showTable ? 'bg-gray-900 text-white' : 'text-gray-600 hover:text-gray-900'">
                    Table
                </button>
            </div>
        @endisset
    </div>

    <div class="mt-4" @isset($table) x-show="! showTable" @endisset>
        {{ $slot }}
    </div>

    @isset($table)
        <div class="mt-4" x-show="showTable" x-cloak>
            {{ $table }}
        </div>
    @endisset

    @if ($footnote)
        <p class="mt-3 border-t border-gray-100 pt-2.5 text-xs leading-relaxed text-gray-500">
            {{ $footnote }}
        </p>
    @endif
</div>
