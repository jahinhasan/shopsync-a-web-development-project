{{--
    Honest empty state: shown when a section's data genuinely does not
    exist yet, with an optional hint on what will make it appear.
--}}
@props([
    'message',
    'hint' => null,
])

<div {{ $attributes }} class="rounded-xl border border-dashed border-gray-200 bg-gray-50/60 px-4 py-8 text-center">
    <p class="text-sm font-medium text-gray-600">{{ $message }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</div>
