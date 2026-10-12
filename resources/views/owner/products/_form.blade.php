
@csrf

<div class="space-y-6">

    @if ($errors->any())
        <div class="rounded border border-red-300 bg-red-50 p-4 text-red-700">
            <p class="font-semibold">Please fix the following errors:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
            Product Name
        </label>
        <input
            id="name"
            name="name"
            required
            maxlength="100"
            value="{{ old('name', $product->name ?? '') }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        >
        @error('name')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="slug" class="block font-medium">Slug</label>
        <input
            id="slug"
            name="slug"
            required
            value="{{ old('slug', $product->slug ?? '') }}"
            class="mt-1 w-full rounded-md border-gray-300"
        >
        @error('slug')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="category_id" class="block text-sm font-medium">
            Category
        </label>

        <select
            id="category_id"
            name="category_id"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        >
            <option value="">Select Category</option>

            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)
                >
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        @error('category_id')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium">
            Description
        </label>
        <textarea
            id="description"
            name="description"
            rows="4"
            class="mt-1 w-full rounded-md border-gray-300"
        >{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="price" class="block text-sm font-medium">Price</label>
        <input
            id="price"
            name="price"
            type="number"
            min="0.01"
            step="0.01"
            required
            value="{{ old('price', $product->price ?? '') }}"
            class="mt-1 w-full rounded-md border-gray-300"
        >
        @error('price')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="stock" class="block text-sm font-medium">Stock</label>
        <input
            id="stock"
            name="stock"
            type="number"
            min="0"
            required
            value="{{ old('stock', $product->stock ?? 0) }}"
            class="mt-1 w-full rounded-md border-gray-300"
        >
        @error('stock')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        @if (!empty($product?->image_path))
        <div class="mb-4">
            <p class="mb-2 font-medium">Current Image:</p>
            <img
                src="{{ asset('storage/' . $product->image_path) }}"
                alt="{{ $product->name }}"
                class="h-32 w-32 rounded-lg object-cover"
        >

        </div>
            
        @endif
        <label for="image" class="block text-sm font-medium">
            Product Image
        </label>
        <input
            id="image"
            name="image"
            type="file"
            accept="image/*"
            class="mt-1 w-full rounded-md border-gray-300"
        >
        @error('image')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        class="rounded-md bg-indigo-600 px-4 py-2 text-white"
    >
        {{ $buttonText }}
    </button>

</div>
