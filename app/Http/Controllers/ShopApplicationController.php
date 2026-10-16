<?php

namespace App\Http\Controllers;

use App\Models\ShopApplication;
use App\Models\ShopApplicationMedia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShopApplicationController extends Controller
{

public function create()
{
    $user = Auth::user();

    abort_unless(
        $user && $user->role === 'business_owner',
        403
    );

    $application = $user->shopApplication()
        ->with('media')
        ->first();

    // Only applicants asked to provide more information can edit.
    if (
        $application
        && $application->status !== 'needs_information'
    ) {
        return redirect()->route('owner.shop-application.status');
    }

    return view(
        'shop-application.create',
        compact('application')
    );
}


public function store(Request $request)
{
    $user = Auth::user();

    abort_unless(
        $user && $user->role === 'business_owner',
        403
    );

    $existing = $user->shopApplication()->first();

    $isResubmission = $existing
        && $existing->status === 'needs_information';

    // An existing application can only be edited when the admin requested it.
    if ($existing && !$isResubmission) {
        return redirect()->route('owner.shop-application.status');
    }

    $shopNameRule = Rule::unique('shop_applications', 'shop_name');

    if ($existing) {
        $shopNameRule->ignore($existing->id);
    }

    $validated = $request->validate([
        'shop_name' => ['required', 'string', 'max:150', $shopNameRule],
        'shop_type' => ['required', 'string', 'max:100'],
        'other_shop_type' => ['nullable', 'string', 'max:100'],
        'business_stage' => ['required', 'string', 'max:100'],
        'estimated_product_count' => ['required', 'integer', 'min:1', 'max:100000'],
        'description' => ['required', 'string', 'max:5000'],
        'product_categories' => ['required', 'string', 'max:2000'],

        'business_phone' => ['required', 'string', 'max:30'],
        'business_email' => ['nullable', 'email', 'max:255'],
        'division' => ['required', 'string', 'max:100'],
        'district' => ['required', 'string', 'max:100'],
        'upazila' => ['nullable', 'string', 'max:100'],
        'postal_code' => ['nullable', 'string', 'max:20'],
        'full_address' => ['required', 'string', 'max:1000'],

        'business_history' => ['nullable', 'string', 'max:5000'],
        'previous_platforms' => ['nullable', 'string', 'max:1000'],
        'supplier_information' => ['nullable', 'string', 'max:3000'],
        'additional_information' => ['nullable', 'string', 'max:3000'],

        'legal_name' => ['required', 'string', 'max:255'],
        'date_of_birth' => ['nullable', 'date', 'before:today'],
        'identity_document_type' => [
            'required',
            Rule::in(['national_id', 'passport', 'driving_license']),
        ],
        'identity_document_number' => ['nullable', 'string', 'max:100'],

        'identity_photo' => [
            $existing?->identity_photo_path ? 'nullable' : 'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
        ],
        'identity_document' => [
            $existing?->identity_document_path ? 'nullable' : 'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240',
        ],

        'demo_photos' => ['nullable', 'array', 'max:5'],
        'demo_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

        'demo_videos' => ['nullable', 'array', 'max:2'],
        'demo_videos.*' => ['file', 'mimes:mp4,webm,mov', 'max:20480'],

        'real_product_proofs' => ['nullable', 'array', 'max:5'],
        'real_product_proofs.*' => [
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240',
        ],

        'terms_accepted' => ['required', 'accepted'],
    ]);

    // Preserve the previous reviewer for resubmissions.
    $admin = $existing?->assigned_admin_id
        ? User::find($existing->assigned_admin_id)
        : null;

    $admin ??= User::where('role', 'admin')
        ->orderBy('id')
        ->first();

    if (!$admin) {
        return back()
            ->withErrors([
                'application' => 'No admin is available to review applications.',
            ])
            ->withInput();
    }

    $storedPaths = [];
    $oldPathsToDelete = [];
    $application = $existing;

    try {
        DB::transaction(function () use (
            $request,
            $validated,
            $user,
            $admin,
            $existing,
            &$application,
            &$storedPaths,
            &$oldPathsToDelete
        ) {
            $identityPhotoPath = $existing?->identity_photo_path;
            $identityDocumentPath = $existing?->identity_document_path;

            if ($request->hasFile('identity_photo')) {
                $path = $request->file('identity_photo')
                    ->store('shop-applications/identity', 'local');

                if (!$path) {
                    throw new \RuntimeException('Identity photo upload failed.');
                }

                $storedPaths[] = $path;

                if ($existing?->identity_photo_path) {
                    $oldPathsToDelete[] = $existing->identity_photo_path;
                }

                $identityPhotoPath = $path;
            }

            if ($request->hasFile('identity_document')) {
                $path = $request->file('identity_document')
                    ->store('shop-applications/documents', 'local');

                if (!$path) {
                    throw new \RuntimeException('Identity document upload failed.');
                }

                $storedPaths[] = $path;

                if ($existing?->identity_document_path) {
                    $oldPathsToDelete[] = $existing->identity_document_path;
                }

                $identityDocumentPath = $path;
            }

            $slug = $existing?->slug;

            if (!$existing || $validated['shop_name'] !== $existing->shop_name) {
                $baseSlug = Str::slug($validated['shop_name']) ?: 'shop';
                $slug = $baseSlug;
                $counter = 1;

                while (
                    ShopApplication::query()
                        ->where('slug', $slug)
                        ->when(
                            $existing,
                            fn ($query) => $query->where('id', '!=', $existing->id)
                        )
                        ->exists()
                ) {
                    $counter++;
                    $slug = $baseSlug . '-' . $counter;
                }
            }

            $attributes = [
                'user_id' => $user->id,
                'assigned_admin_id' => $existing?->assigned_admin_id ?? $admin->id,

                'shop_name' => $validated['shop_name'],
                'slug' => $slug,
                'shop_type' => $validated['shop_type'],
                'other_shop_type' => $validated['other_shop_type'] ?? null,
                'business_stage' => $validated['business_stage'],
                'description' => $validated['description'],

                'business_email' => $validated['business_email'] ?? null,
                'business_phone' => $validated['business_phone'],
                'country' => 'Bangladesh',
                'division' => $validated['division'],
                'district' => $validated['district'],
                'upazila' => $validated['upazila'] ?? null,
                'full_address' => $validated['full_address'],
                'postal_code' => $validated['postal_code'] ?? null,

                'estimated_product_count' => $validated['estimated_product_count'],
                'product_categories' => $validated['product_categories'],
                'business_history' => $validated['business_history'] ?? null,
                'previous_platforms' => $validated['previous_platforms'] ?? null,
                'supplier_information' => $validated['supplier_information'] ?? null,
                'additional_information' => $validated['additional_information'] ?? null,

                'legal_name' => $validated['legal_name'],
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'identity_document_type' => $validated['identity_document_type'],
                'identity_document_number' => $validated['identity_document_number'] ?? null,
                'identity_photo_path' => $identityPhotoPath,
                'identity_document_path' => $identityDocumentPath,

                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ];

            if ($existing) {
                $existing->update($attributes);
                $application = $existing->fresh();
            } else {
                $application = ShopApplication::create($attributes);
            }

            foreach ([
                'demo_photos' => 'demo_photo',
                'demo_videos' => 'demo_video',
                'real_product_proofs' => 'product_proof',
            ] as $input => $category) {
                // No new uploads for this category: keep existing evidence.
                if (!$request->hasFile($input)) {
                    continue;
                }

                // New uploads replace evidence for that category.
                $oldMedia = $application->media()
                    ->where('category', $category)
                    ->get();

                foreach ($oldMedia as $media) {
                    $oldPathsToDelete[] = $media->file_path;
                    $media->delete();
                }

                foreach ($request->file($input, []) as $file) {
                    $path = $file->store(
                        'shop-applications/' . $application->id . '/evidence',
                        'local'
                    );

                    if (!$path) {
                        throw new \RuntimeException('Evidence upload failed.');
                    }

                    $storedPaths[] = $path;

                    $application->media()->create([
                        'category' => $category,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            $application->reviews()->create([
                'actor_id' => $user->id,
                'actor_role' => 'business_owner',
                'action' => $existing ? 'resubmitted' : 'submitted',
                'comment' => null,
            ]);
        });
    } catch (\Throwable $e) {
        foreach (array_unique($storedPaths) as $path) {
            Storage::disk('local')->delete($path);
        }

        throw $e;
    }

    // Delete replaced files only after the database transaction succeeds.
    foreach (array_unique($oldPathsToDelete) as $path) {
        Storage::disk('local')->delete($path);
    }

    return redirect()
        ->route('owner.shop-application.status')
        ->with(
            'success',
            $isResubmission
                ? 'Your updated application has been resubmitted for review.'
                : 'Your shop application has been submitted for review.'
        );
}


public function status()
{
    $user = Auth::user();

    abort_unless(
        $user && $user->role === 'business_owner',
        403
    );

    $application = $user->shopApplication()
        ->with(['media', 'reviews.actor'])
        ->first();

    abort_unless($application, 404);

    if ($application->status === 'approved') {
        return redirect()->route('owner.dashboard');
    }

    return view(
        'shop-application.status',
        compact('application')
    );
}

}
