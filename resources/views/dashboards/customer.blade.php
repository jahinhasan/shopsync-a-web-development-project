
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Customer Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-gray-900">
                    Welcome, {{ auth()->user()->name }}
                </h1>
                <p class="mt-2 text-sm text-gray-600">
                    Browse products, manage your cart and track your orders.
                </p>

                <a href="{{ route('shop.index') }}"
                   class="mt-5 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-700">
                    Start Shopping →
                </a>
            </div>

            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg bg-red-100 p-4 text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <section>
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    Your Shopping Space
                </h3>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                    <a href="{{ route('shop.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">
                            Browse Products
                        </h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Explore the available product catalogue.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Shop Now →
                        </span>
                    </a>

                    <a href="{{ route('cart.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">
                            Shopping Cart
                        </h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Review quantities and remove unwanted items.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            View Cart →
                        </span>
                    </a>

                    <a href="{{ route('orders.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">
                            My Orders
                        </h4>
                        <p class="mt-2 text-sm text-gray-500">
                            View your order history and order status.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Order History →
                        </span>
                    </a>

                    <a href="{{ route('checkout.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">
                            Checkout
                        </h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Continue to shipping details and place your order.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Proceed to Checkout →
                        </span>
                    </a>

                    <a href="{{ route('profile.edit') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">
                            My Profile
                        </h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Update your account information and password.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Edit Profile →
                        </span>
                    </a>

                </div>
            </section>

        </div>
    </div>
</x-app-layout>
