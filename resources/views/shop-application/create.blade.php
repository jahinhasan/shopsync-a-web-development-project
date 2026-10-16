
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
                ShopSync Seller Program
            </p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">
                Open your shop
            </h2>
            <p class="mt-2 text-sm text-gray-500">
                Tell us about your business and submit it for review.
            </p>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
                    <p class="font-semibold">Please review the following:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ route('owner.shop-application.store') }}"
                  enctype="multipart/form-data"
                  class="space-y-8">
                @csrf

                {{-- Shop details --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">01 / Shop profile</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Tell us about your shop</h3>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Shop name *</label>
                            <input name="shop_name" value="{{ old('shop_name') }}" required
                                   maxlength="150"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Shop type *</label>
                            <select name="shop_type" required
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select shop type</option>
                                @foreach (['Fashion & Clothing', 'Electronics', 'Home & Living', 'Beauty & Personal Care', 'Food & Grocery', 'Books & Stationery', 'Sports & Outdoors', 'Handmade & Crafts', 'Other'] as $type)
                                    <option value="{{ $type }}" @selected(old('shop_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Current business stage *</label>
                            <select name="business_stage" required
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select current stage</option>
                                @foreach (['Planning to start', 'Already selling offline', 'Selling through social media', 'Selling on another marketplace', 'Existing online store'] as $stage)
                                    <option value="{{ $stage }}" @selected(old('business_stage') === $stage)>{{ $stage }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Estimated product count *</label>
                            <input type="number" name="estimated_product_count"
                                   min="1" max="100000" required
                                   value="{{ old('estimated_product_count', 1) }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Shop description *</label>
                            <textarea name="description" rows="4" required maxlength="5000"
                                      placeholder="What does your shop sell? What makes it different?"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Product categories *</label>
                            <input name="product_categories" required
                                   value="{{ old('product_categories') }}"
                                   placeholder="e.g. T-shirts, jeans, shoes"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </section>

                {{-- Location --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">02 / Location & contact</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Where is your business?</h3>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Business phone *</label>
                            <input name="business_phone" type="tel" required
                                   value="{{ old('business_phone') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Business email</label>
                            <input name="business_email" type="email"
                                   value="{{ old('business_email') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Division *</label>
                            <input name="division" required value="{{ old('division') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">District *</label>
                            <input name="district" required value="{{ old('district') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Upazila</label>
                            <input name="upazila" value="{{ old('upazila') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Postal code</label>
                            <input name="postal_code" value="{{ old('postal_code') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Full business address *</label>
                            <textarea name="full_address" rows="3" required
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('full_address') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Business history --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">03 / Business history</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Your business experience</h3>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Previous business history</label>
                            <textarea name="business_history" rows="4"
                                      placeholder="How long have you been operating? What have you sold before?"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('business_history') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Previous platforms or marketplaces</label>
                            <input name="previous_platforms" value="{{ old('previous_platforms') }}"
                                   placeholder="e.g. Facebook, Instagram, another marketplace"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Supplier information</label>
                            <textarea name="supplier_information" rows="3"
                                      placeholder="Where do your products come from?"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('supplier_information') }}</textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Additional information</label>
                            <textarea name="additional_information" rows="3"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('additional_information') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Product evidence --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">04 / Product evidence</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Show us your products</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Upload sample photos or videos, and evidence of products you currently sell.
                        </p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Demo product photos</label>
                            <input type="file" name="demo_photos[]" multiple accept="image/*"
                   class="block w-full text-sm text-gray-600">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Demo product videos</label>
                            <input type="file" name="demo_videos[]" multiple accept="video/*"
                   class="block w-full text-sm text-gray-600">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Real product proof</label>
                            <p class="mb-2 text-xs text-gray-500">Photos or documents showing products currently available.</p>
                            <input type="file" name="real_product_proofs[]" multiple accept="image/*,.pdf"
                   class="block w-full text-sm text-gray-600">
                        </div>
                    </div>
                </section>

                {{-- Identity --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">05 / Applicant verification</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Verify your identity</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            The person applying must be the person represented by the identity evidence.
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Legal name *</label>
                            <input name="legal_name" required value="{{ old('legal_name') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Date of birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Identity document *</label>
                            <select name="identity_document_type" required
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select document</option>
                                <option value="national_id">National ID</option>
                                <option value="passport">Passport</option>
                                <option value="driving_license">Driving license</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Document number</label>
                            <input name="identity_document_number"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-gray-700">Identity document image *</label>
                            <input type="file" name="identity_document" required accept="image/*,.pdf"
                                   class="block w-full text-sm text-gray-600">
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl bg-gray-50 p-5">
                        <h4 class="font-semibold text-gray-900">Live camera photo</h4>
                        <p class="mt-1 text-sm text-gray-500">
                            Allow camera access, capture a current photo, and attach it to your application.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" id="start-camera"
                                    class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white">
                                Start camera
                            </button>
                            <button type="button" id="capture-photo" disabled
                                    class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40">
                                Capture photo
                            </button>
                        </div>

                        <video id="camera-preview" autoplay playsinline
                               class="mt-4 hidden max-h-96 w-full rounded-xl bg-black object-contain"></video>
                        <canvas id="camera-canvas" class="hidden"></canvas>
                        <img id="photo-preview" alt="Captured identity photo"
                             class="mt-4 hidden max-h-96 rounded-xl border border-gray-200">

                        <input type="file" name="identity_photo" id="identity-photo-file"
                               accept="image/*" class="mt-4 block w-full text-sm text-gray-600">

                        <p class="mt-2 text-xs text-gray-500">
                            If camera access is unavailable, use the photo upload field above.
                        </p>
                    </div>
                </section>

                {{-- Terms --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">06 / Review & consent</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Before submitting</h3>
                    </div>

                    <div class="max-h-64 overflow-y-auto rounded-xl border border-gray-200 p-5 text-sm leading-6 text-gray-600">
                        <h4 class="font-semibold text-gray-900">ShopSync Seller Application Terms</h4>
                        <p class="mt-2">I confirm that the information and evidence provided in this application are accurate and belong to me or my business.</p>
                        <p class="mt-2">I understand that ShopSync may review my identity and business evidence to determine eligibility to sell on the platform.</p>
                        <p class="mt-2">I understand that submitting this application does not guarantee approval. I must wait for approval before listing or selling products.</p>
                        <p class="mt-2">I agree to follow ShopSync's seller policies, product rules, customer protection requirements, and applicable laws.</p>
                        <p class="mt-2">I understand that false, misleading, or fraudulent information may result in rejection or suspension of my application.</p>
                    </div>

                    <label class="mt-5 flex items-start gap-3">
                        <input type="checkbox" name="terms_accepted" value="1" required
                               class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm leading-6 text-gray-700">
                            I have read and accept the ShopSync Seller Application Terms. I confirm that the information submitted is accurate. *
                        </span>
                    </label>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('owner.dashboard') }}"
                       class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                        Submit shop application →
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const startButton = document.getElementById('start-camera');
        const captureButton = document.getElementById('capture-photo');
        const video = document.getElementById('camera-preview');
        const canvas = document.getElementById('camera-canvas');
        const preview = document.getElementById('photo-preview');
        const photoInput = document.getElementById('identity-photo-file');

        let cameraStream = null;
        let capturedPhoto = null;

        startButton.addEventListener('click', async () => {
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false
                });

                video.srcObject = cameraStream;
                video.classList.remove('hidden');
                captureButton.disabled = false;
            } catch (error) {
                alert('Camera access failed. Please allow camera permission or upload a photo.');
            }
        });

        captureButton.addEventListener('click', () => {
            if (!video.videoWidth || !video.videoHeight) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            canvas.toBlob(blob => {
                if (!blob) return;

                capturedPhoto = new File([blob], 'identity-photo.jpg', {
                    type: 'image/jpeg'
                });

                const transfer = new DataTransfer();
                transfer.items.add(capturedPhoto);
                photoInput.files = transfer.files;

                preview.src = URL.createObjectURL(blob);
                preview.classList.remove('hidden');
            }, 'image/jpeg', 0.9);
        });

        document.querySelector('form').addEventListener('submit', () => {
            cameraStream?.getTracks().forEach(track => track.stop());
        });
    </script>
</x-app-layout>
