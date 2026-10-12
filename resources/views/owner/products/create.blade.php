<x-app-layout>
<x-slot name="header">
<h2 class>Add Product</h2>
</x-slot>

<div class="mx-auto max-w-3xl  px-4 py-8" >
    <form action="{{ route('owner.products.store') }}" method="post" enctype="multipart/form-data" class="rounded-lg border bg-white p-6">

        @include('owner.products._form', ['buttonText' => 'Create Product'])
    </form>

</div>


</x-app-layout>