@csrf

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
        <input id="name" name="name" required maxlength="180" value="{{ old('name', $product->name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        @error('name') <p class="text-sm text-red-600">({{ $message }})</p> @enderror
    </div>

    <div>
        <label for="slug" class="block font-medium">Slug</label>
        <input id="slug" name="slug" required  value="{{  old('slug',$product->slug ?? ' ') }}" class="mt-1 w-full rounded-md border-gray-300">
        @error('slug') <p class="text-sm text-red-600">{($massage)} </p> @enderror
 

    </div>


    
    <div>
        <label for="category_id" class="block text-sm font-medium">Category</label>
        <select id="name" name="category_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">Select Category</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id ?? '') === (string)$category->id}}>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <p class="text-sm text-red-600">({{ $message }})</p> @enderror

    </div>
    <div>
        <label for="description" class="block text-smfont-medium">Description</label>
                <textarea id="description" name="description" rows="4"
            class="mt-1 w-full rounded-md border-gray-300">{{ old('description', $product->description ?? '') }}</textarea>
        @error('description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

 <div>
        <label for="price" class="block text-sm font-medium">Price</label>
        <input id="price" name="price" type="number" min="0.01" step="0.01" required
            value="{{ old('price', $product->price ?? '') }}"
            class="mt-1 w-full rounded-md border-gray-300">
        @error('price') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>



    <div>
        <label for="stock" class="block text-sm font-medium">Stock</label>
        <input id="stock" name="stock" type="number" min="0" required
            value="{{ old('stock', $product->stock ?? 0) }}"
            class="mt-1 w-full rounded-md border-gray-300">
        @error('stock') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium">Product Image</label>
        <input id="image" name="image" type="file" accept="image/*"
            class="mt-1 w-full rounded-md border-gray-300">
        @error('image') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white">
        {{ $buttonText }}
    </button>



</div>