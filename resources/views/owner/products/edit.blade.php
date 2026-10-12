
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Edit Product</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-8">
        <form
            method="POST"
            action="{{ route('owner.products.update', ['product' => $product->id]) }}"
            enctype="multipart/form-data"
            class="rounded-lg border bg-white p-6"
        >
            @csrf
            @method('PUT')

            @include('owner.products._form', [
                'buttonText' => 'Update Product'
            ])
        </form>
    </div>
</x-app-layout>
