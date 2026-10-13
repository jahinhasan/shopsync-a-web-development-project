<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Category Management
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded bg-red-100 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Categories</h3>

                <a href="{{ route('admin.categories.create') }}"
                   class="rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                    Add Category
                </a>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Parent Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase">
                                Products
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @forelse ($categories as $category)
                            <tr>
                                <td class="px-6 py-4">
                                    {{ $category->name }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $category->parent?->name ?? 'None' }}
                                </td>

                                <td class="px-6 py-4">
                                    {{ $category->products_count }}
                                </td>

                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       class="mr-3 text-indigo-600 hover:text-indigo-900">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.categories.destroy', $category) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No categories found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $categories->links() }}
            </div>

        </div>
    </div>
</x-app-layout>