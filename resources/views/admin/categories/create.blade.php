
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Create Category
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">

                @if ($errors->any())
                    <div class="mb-5 rounded bg-red-100 p-4 text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.categories.store') }}"
                      method="POST"
                      class="space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="mb-1 block font-medium">
                            Category Name
                        </label>
                        <input id="name"
                               name="name"
                               type="text"
                               value="{{ old('name') }}"
                               required
                               maxlength="255"
                               class="w-full rounded-md border-gray-300 shadow-sm"
                               placeholder="e.g. Electronics">
                    </div>

                    <div>
                        <label for="slug" class="mb-1 block font-medium">
                            Slug
                        </label>
                        <input id="slug"
                               name="slug"
                               type="text"
                               value="{{ old('slug') }}"
                               maxlength="255"
                               class="w-full rounded-md border-gray-300 shadow-sm"
                               placeholder="e.g. electronics">
                        <p class="mt-1 text-sm text-gray-500">
                            Leave empty if your controller generates the slug automatically.
                        </p>
                    </div>

                    <div>
                        <label for="parent_id" class="mb-1 block font-medium">
                            Parent Category
                        </label>
                        <select id="parent_id"
                                name="parent_id"
                                class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">None (Main Category)</option>

                            @foreach ($categories as $parent)
                                <option value="{{ $parent->id }}"
                                    @selected(old('parent_id') == $parent->id)>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="rounded-md bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700">
                            Create Category
                        </button>

                        <a href="{{ route('admin.categories.index') }}"
                           class="rounded-md border px-5 py-2 text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
