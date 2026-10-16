<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">ShopSync Seller Program</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">
                {{ $application ? 'Update your shop application' : 'Open your shop' }}
            </h2>
            <p class="mt-2 text-sm text-gray-500">
                {{ $application ? 'Update the requested information and resubmit your application for review.' : 'Tell us about your business and submit it for review.' }}
            </p>
        </div>
    </x-slot>

    @php
        $currentMedia = $application?->media ?? collect();
        $currentShopType = old('shop_type', $application?->shop_type ?? '');
    @endphp

    <div class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6">
            @if ($application && $application->status === 'needs_information')
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                    <p class="font-semibold">The admin requested more information.</p>
                    <p class="mt-1">Your previous answers are filled in below. Please update the requested fields and resubmit. Your existing private documents will be kept unless you upload replacements.</p>
                </div>
            @endif

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
                            <label for="shop_name" class="mb-2 block text-sm font-medium text-gray-700">Shop name *</label>
                            <input id="shop_name" name="shop_name" value="{{ old('shop_name', $application?->shop_name ?? '') }}" required maxlength="150"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="shop_type" class="mb-2 block text-sm font-medium text-gray-700">Shop type *</label>
                            <select id="shop_type" name="shop_type" required
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select shop type</option>
                                @foreach (['Fashion & Clothing', 'Electronics', 'Home & Living', 'Beauty & Personal Care', 'Food & Grocery', 'Books & Stationery', 'Sports & Outdoors', 'Handmade & Crafts', 'Other'] as $type)
                                    <option value="{{ $type }}" @selected($currentShopType === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="other_shop_type" class="mb-2 block text-sm font-medium text-gray-700">Other shop type (if applicable)</label>
                            <input id="other_shop_type" name="other_shop_type" value="{{ old('other_shop_type', $application?->other_shop_type ?? '') }}" maxlength="100"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="business_stage" class="mb-2 block text-sm font-medium text-gray-700">Current business stage *</label>
                            <select id="business_stage" name="business_stage" required
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select current stage</option>
                                @foreach (['Planning to start', 'Already selling offline', 'Selling through social media', 'Selling on another marketplace', 'Existing online store'] as $stage)
                                    <option value="{{ $stage }}" @selected(old('business_stage', $application?->business_stage ?? '') === $stage)>{{ $stage }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="estimated_product_count" class="mb-2 block text-sm font-medium text-gray-700">Estimated product count *</label>
                            <input id="estimated_product_count" type="number" name="estimated_product_count" min="1" max="100000" required
                                   value="{{ old('estimated_product_count', $application?->estimated_product_count ?? 1) }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="description" class="mb-2 block text-sm font-medium text-gray-700">Shop description *</label>
                            <textarea id="description" name="description" rows="4" required maxlength="5000"
                                      placeholder="What does your shop sell? What makes it different?"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $application?->description ?? '') }}</textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="product_categories" class="mb-2 block text-sm font-medium text-gray-700">Product categories *</label>
                            <input id="product_categories" name="product_categories" required maxlength="2000"
                                   value="{{ old('product_categories', $application?->product_categories ?? '') }}"
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
                            <label for="business_phone" class="mb-2 block text-sm font-medium text-gray-700">Business phone *</label>
                            <input id="business_phone" name="business_phone" type="tel" required maxlength="30"
                                   value="{{ old('business_phone', $application?->business_phone ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="business_email" class="mb-2 block text-sm font-medium text-gray-700">Business email</label>
                            <input id="business_email" name="business_email" type="email" maxlength="255"
                                   value="{{ old('business_email', $application?->business_email ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="division" class="mb-2 block text-sm font-medium text-gray-700">Division *</label>
                            <input id="division" name="division" required maxlength="100" value="{{ old('division', $application?->division ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="district" class="mb-2 block text-sm font-medium text-gray-700">District *</label>
                            <input id="district" name="district" required maxlength="100" value="{{ old('district', $application?->district ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="upazila" class="mb-2 block text-sm font-medium text-gray-700">Upazila</label>
                            <input id="upazila" name="upazila" maxlength="100" value="{{ old('upazila', $application?->upazila ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="postal_code" class="mb-2 block text-sm font-medium text-gray-700">Postal code</label>
                            <input id="postal_code" name="postal_code" maxlength="20" value="{{ old('postal_code', $application?->postal_code ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="full_address" class="mb-2 block text-sm font-medium text-gray-700">Full business address *</label>
                            <textarea id="full_address" name="full_address" rows="3" required maxlength="1000"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('full_address', $application?->full_address ?? '') }}</textarea>
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
                            <label for="business_history" class="mb-2 block text-sm font-medium text-gray-700">Previous business history</label>
                            <textarea id="business_history" name="business_history" rows="4" maxlength="5000"
                                      placeholder="How long have you been operating? What have you sold before?"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('business_history', $application?->business_history ?? '') }}</textarea>
                        </div>

                        <div>
                            <label for="previous_platforms" class="mb-2 block text-sm font-medium text-gray-700">Previous platforms or marketplaces</label>
                            <input id="previous_platforms" name="previous_platforms" maxlength="1000"
                                   value="{{ old('previous_platforms', $application?->previous_platforms ?? '') }}"
                                   placeholder="e.g. Facebook, Instagram, another marketplace"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="supplier_information" class="mb-2 block text-sm font-medium text-gray-700">Supplier information</label>
                            <textarea id="supplier_information" name="supplier_information" rows="3" maxlength="3000"
                                      placeholder="Where do your products come from?"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('supplier_information', $application?->supplier_information ?? '') }}</textarea>
                        </div>

                        <div>
                            <label for="additional_information" class="mb-2 block text-sm font-medium text-gray-700">Additional information</label>
                            <textarea id="additional_information" name="additional_information" rows="3" maxlength="3000"
                                      class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('additional_information', $application?->additional_information ?? '') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Product evidence --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">04 / Product evidence</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Show us your products</h3>
                        <p class="mt-2 text-sm text-gray-500">New files replace the existing files in that evidence category. Leave a file field empty to keep its current uploads.</p>
                    </div>

                    @foreach ([
                        'demo_photo' => 'Demo product photos',
                        'demo_video' => 'Demo product videos',
                        'product_proof' => 'Real product proof',
                    ] as $category => $label)
                        @php
                            $categoryMedia = $currentMedia->where('category', $category);
                            $inputName = match ($category) {
                                'demo_photo' => 'demo_photos',
                                'demo_video' => 'demo_videos',
                                default => 'real_product_proofs',
                            };
                        @endphp
                        <div class="mb-5 last:mb-0">
                            <label for="{{ $inputName }}" class="mb-2 block text-sm font-medium text-gray-700">{{ $label }}</label>
                            @if ($categoryMedia->isNotEmpty())
                                <div class="mb-3 rounded-xl bg-gray-50 p-3 text-sm text-gray-600">
                                    <p class="font-semibold text-gray-700">Already uploaded:</p>
                                    <ul class="mt-1 list-inside list-disc">
                                        @foreach ($categoryMedia as $media)
                                            <li>{{ $media->original_name ?: 'Evidence file' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <input id="{{ $inputName }}" type="file" name="{{ $inputName }}[]" multiple
                                   @if ($category === 'demo_photo') accept="image/jpeg,image/png,image/webp" @elseif ($category === 'demo_video') accept="video/mp4,video/webm,video/quicktime" @else accept="image/jpeg,image/png,image/webp,.pdf" @endif
                                   class="block w-full text-sm text-gray-600">
                        </div>
                    @endforeach
                </section>

                {{-- Identity --}}
                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-indigo-600">05 / Applicant verification</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">Verify your identity</h3>
                        <p class="mt-2 text-sm text-gray-500">Identity documents are kept in private storage. Upload a replacement only if you need to change the existing file.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="legal_name" class="mb-2 block text-sm font-medium text-gray-700">Legal name *</label>
                            <input id="legal_name" name="legal_name" required maxlength="255"
                                   value="{{ old('legal_name', $application?->legal_name ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="date_of_birth" class="mb-2 block text-sm font-medium text-gray-700">Date of birth</label>
                            <input id="date_of_birth" type="date" name="date_of_birth"
                                   value="{{ old('date_of_birth', $application?->date_of_birth?->format('Y-m-d') ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="identity_document_type" class="mb-2 block text-sm font-medium text-gray-700">Identity document *</label>
                            <select id="identity_document_type" name="identity_document_type" required
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select document</option>
                                @foreach (['national_id' => 'National ID', 'passport' => 'Passport', 'driving_license' => 'Driving license'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('identity_document_type', $application?->identity_document_type ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="identity_document_number" class="mb-2 block text-sm font-medium text-gray-700">Document number</label>
                            <input id="identity_document_number" name="identity_document_number" maxlength="100"
                                   value="{{ old('identity_document_number', $application?->identity_document_number ?? '') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="identity_document" class="mb-2 block text-sm font-medium text-gray-700">Identity document file *</label>
                            @if ($application?->identity_document_path)
                                <p class="mb-2 rounded-xl bg-gray-50 p-3 text-sm text-gray-600">An identity document is already uploaded. Leave this field empty to keep it, or select a replacement.</p>
                            @endif
                            <input id="identity_document" type="file" name="identity_document" accept="image/jpeg,image/png,image/webp,.pdf"
                                   @if (!$application?->identity_document_path) required @endif
                                   class="block w-full text-sm text-gray-600">
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl bg-gray-50 p-5">
                        <h4 class="font-semibold text-gray-900">Live camera photo</h4>
                        <p class="mt-1 text-sm text-gray-500">Allow camera access and capture a current photo, or upload an image below.</p>

                        @if ($application?->identity_photo_path)
                            <p class="mt-3 rounded-xl bg-white p-3 text-sm text-gray-600">An identity photo is already uploaded. Leave the photo field empty to keep it, or capture/upload a replacement.</p>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button type="button" id="start-camera" class="rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white">Start camera</button>
                            <button type="button" id="capture-photo" disabled class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-40">Capture photo</button>
                        </div>

                        <video id="camera-preview" autoplay playsinline class="mt-4 hidden max-h-96 w-full rounded-xl bg-black object-contain"></video>
                        <canvas id="camera-canvas" class="hidden"></canvas>
                        <img id="photo-preview" alt="Captured identity photo" class="mt-4 hidden max-h-96 rounded-xl border border-gray-200">

                        <label for="identity-photo-file" class="mb-2 mt-4 block text-sm font-medium text-gray-700">Identity photo upload</label>
                        <input type="file" name="identity_photo" id="identity-photo-file" accept="image/jpeg,image/png,image/webp"
                               @if (!$application?->identity_photo_path) required @endif
                               class="block w-full text-sm text-gray-600">
                        <p class="mt-2 text-xs text-gray-500">If camera access is unavailable, upload a photo directly.</p>
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
                               @checked((string) old('terms_accepted', $application?->terms_accepted ? '1' : '') === '1')
                               class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm leading-6 text-gray-700">I have read and accept the ShopSync Seller Application Terms. I confirm that the information submitted is accurate. *</span>
                    </label>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ $application ? route('owner.shop-application.status') : route('shop.index') }}"
                       class="rounded-xl border border-gray-300 bg-white px-5 py-3 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700">
                        {{ $application ? 'Resubmit updated application →' : 'Submit shop application →' }}
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

        startButton.addEventListener('click', async () => {
            try {
                if (!navigator.mediaDevices?.getUserMedia) {
                    throw new Error('Camera API unavailable');
                }

                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
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

            canvas.toBlob((blob) => {
                if (!blob) return;

                const capturedPhoto = new File([blob], 'identity-photo.jpg', {
                    type: 'image/jpeg',
                });

                const transfer = new DataTransfer();
                transfer.items.add(capturedPhoto);
                photoInput.files = transfer.files;

                preview.src = URL.createObjectURL(blob);
                preview.classList.remove('hidden');
            }, 'image/jpeg', 0.9);
        });

        document.querySelector('form')?.addEventListener('submit', () => {
            cameraStream?.getTracks().forEach((track) => track.stop());
        });

        window.addEventListener('beforeunload', () => {
            cameraStream?.getTracks().forEach((track) => track.stop());
        });
    </script>
</x-app-layout>
