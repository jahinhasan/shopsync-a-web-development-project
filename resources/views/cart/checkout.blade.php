 <x-app-layout>
     <x-slot name="header">
         <h2 class="text-xl font-semibold">Checkout</h2>
     </x-slot>

     <div class="space-y-4">
        <form method="POST" action="{{ route('checkout.place') }}"
      class="space-y-4 rounded-lg border bg-white p-5">
    @csrf

    @if ($errors->any())
        <div role="alert" class="rounded-xl bg-red-50 p-3 text-sm text-red-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-4">
         <h2 class="text-lg font-bold text-gray-900">Payment method</h2>

         <div class="grid gap-3 sm:grid-cols-3">
             <label
                 class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-indigo-300">
                 <input type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod')
                     class="text-indigo-600 focus:ring-indigo-500">
                 <span>
                     <span class="block font-semibold text-gray-900">Cash on Delivery</span>
                     <span class="mt-1 block text-xs text-gray-500">Pay when delivered</span>
                 </span>
             </label>

             <label
                 class="flex cursor-pointer items-center gap-3 rounded-xl border border-pink-200 bg-pink-50/50 p-4 transition hover:border-pink-400">
                 <input type="radio" name="payment_method" value="bkash" @checked(old('payment_method') === 'bkash')
                     class="text-pink-600 focus:ring-pink-500">
                 <span>
                     <span class="block font-semibold text-gray-900">bKash</span>
                     <span class="mt-1 block text-xs text-gray-500">
                         {{ config('shopsync.payment_numbers.bkash') ?: 'Payment number not configured' }}
                     </span>
                 </span>
             </label>

             <label
                 class="flex cursor-pointer items-center gap-3 rounded-xl border border-orange-200 bg-orange-50/50 p-4 transition hover:border-orange-400">
                 <input type="radio" name="payment_method" value="nagad" @checked(old('payment_method') === 'nagad')
                     class="text-orange-600 focus:ring-orange-500">
                 <span>
                     <span class="block font-semibold text-gray-900">Nagad</span>
                     <span class="mt-1 block text-xs text-gray-500">
                         {{ config('shopsync.payment_numbers.nagad') ?: 'Payment number not configured' }}
                     </span>
                 </span>
             </label>
         </div>

         <div id="mobile-payment-reference" class="hidden rounded-2xl border border-gray-200 bg-gray-50 p-4">
             <label for="payment-reference" class="mb-2 block text-sm font-semibold text-gray-800">
                 Transaction ID / reference
             </label>

             <input id="payment-reference" name="payment_reference" type="text" maxlength="120"
                 value="{{ old('payment_reference') }}" placeholder="Enter the transaction ID from your payment"
                 class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

             <p class="mt-2 text-xs leading-5 text-gray-500">
                 Complete the payment using the displayed number, then enter its transaction ID.
                 The payment remains pending until the seller verifies it.
             </p>
         </div>

         <div>
             <label for="voucher-code" class="mb-2 block text-sm font-semibold text-gray-800">
                 Voucher code (optional)
             </label>

             <input id="voucher-code" name="voucher_code" value="{{ old('voucher_code') }}" maxlength="40"
                 placeholder="Enter your ShopSync voucher"
                 class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">

             <p class="mt-1 text-xs text-gray-500">
                 Vouchers apply only to their eligible product/category and minimum order amount.
             </p>
         </div>
     </div>

     <script>
         (() => {
             const radios = document.querySelectorAll('input[name="payment_method"]');
             const referencePanel = document.getElementById('mobile-payment-reference');
             const referenceInput = document.getElementById('payment-reference');

             function updatePaymentFields() {
                 const method = document.querySelector('input[name="payment_method"]:checked')?.value;
                 const needsReference = ['bkash', 'nagad'].includes(method);

                 referencePanel?.classList.toggle('hidden', !needsReference);

                 if (referenceInput) {
                     referenceInput.required = needsReference;
                 }
             }

             radios.forEach(radio => radio.addEventListener('change', updatePaymentFields));
             updatePaymentFields();
         })();
     </script>


         <div>
             <label for="customer_name" class="mb-1 block font-medium">
                 Full name
             </label>
             <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required maxlength="255"
                 class="w-full rounded-md border-gray-300">
         </div>

         <div>
             <label for="phone" class="mb-1 block font-medium">
                 Phone number
             </label>
             <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required maxlength="30"
                 class="w-full rounded-md border-gray-300">
         </div>

         <div>
             <label for="shipping_address" class="mb-1 block font-medium">
                 Shipping address
             </label>
             <textarea id="shipping_address" name="shipping_address" required maxlength="2000" rows="4"
                 class="w-full rounded-md border-gray-300">{{ old('shipping_address') }}</textarea>
         </div>

         <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700">
             Place Order
         </button>
     </form>
     </div>
 </x-app-layout>
