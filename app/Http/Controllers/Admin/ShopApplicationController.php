<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopApplication;
use App\Models\ShopApplicationMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ShopApplicationController extends Controller
{
    private const REVIEWABLE_STATUSES = [
        'pending',
        'needs_information',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'needs_information',
                    'approved',
                    'rejected',
                ]),
            ],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        // Only applications assigned to the authenticated admin.
        $baseQuery = ShopApplication::query()
            ->where('assigned_admin_id', $request->user()->id);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $query = (clone $baseQuery)
            ->with('applicant:id,name,email')
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['q'])) {
            $search = trim($filters['q']);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('shop_name', 'like', "%{$search}%")
                    ->orWhere('business_email', 'like', "%{$search}%")
                    ->orWhere('business_phone', 'like', "%{$search}%")
                    ->orWhereHas('applicant', function ($users) use ($search) {
                        $users->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $applications = $query
            ->paginate(15)
            ->withQueryString();

        return view('admin.shop-applications.index', [
            'applications' => $applications,
            'statusCounts' => $statusCounts,
            'filters' => $filters,
        ]);
    }

    public function show(int $id)
    {
        $application = $this->findAssignedApplication($id);

        return view('admin.shop-applications.show', compact('application'));
    }

    public function approve(Request $request, int $id)
    {
        $application = $this->findAssignedApplication($id);
        $this->ensureReviewable($application);

        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($application, $data) {
            $application->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'admin_notes' => $data['comment'] ?? $application->admin_notes,
            ]);

            $application->reviews()->create([
                'actor_id' => Auth::id(),
                'actor_role' => 'admin',
                'action' => 'approved',
                'comment' => $data['comment'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.shop-applications.show', $id)
            ->with('success', 'Shop application approved successfully.');
    }

    public function requestInformation(Request $request, int $id)
    {
        $application = $this->findAssignedApplication($id);
        $this->ensureReviewable($application);

        $data = $request->validate([
            'comment' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        DB::transaction(function () use ($application, $data) {
            $application->update([
                'status' => 'needs_information',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'admin_notes' => $data['comment'],
                'rejection_reason' => null,
            ]);

            $application->reviews()->create([
                'actor_id' => Auth::id(),
                'actor_role' => 'admin',
                'action' => 'requested_information',
                'comment' => $data['comment'],
            ]);
        });

        return redirect()
            ->route('admin.shop-applications.show', $id)
            ->with('success', 'Additional information requested from the applicant.');
    }

    public function reject(Request $request, int $id)
    {
        $application = $this->findAssignedApplication($id);
        $this->ensureReviewable($application);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        DB::transaction(function () use ($application, $data) {
            $application->update([
                'status' => 'rejected',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
                'admin_notes' => $data['rejection_reason'],
            ]);

            $application->reviews()->create([
                'actor_id' => Auth::id(),
                'actor_role' => 'admin',
                'action' => 'rejected',
                'comment' => $data['rejection_reason'],
            ]);
        });

        return redirect()
            ->route('admin.shop-applications.show', $id)
            ->with('success', 'Shop application rejected.');
    }

    public function document(int $id, string $document)
    {
        $application = $this->findAssignedApplication($id);

        $path = match ($document) {
            'identity-photo' => $application->identity_photo_path,
            'identity-document' => $application->identity_document_path,
            default => abort(404),
        };

        abort_unless(
            $path && Storage::disk('local')->exists($path),
            404
        );

        // No public URL: access passes through this authorization check.
        return response()->file(
            Storage::disk('local')->path($path),
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'Pragma' => 'no-cache',
            ]
        );
    }

    public function media(int $id, int $mediaId)
    {
        $application = $this->findAssignedApplication($id);

        $media = ShopApplicationMedia::query()
            ->where('shop_application_id', $application->id)
            ->whereKey($mediaId)
            ->firstOrFail();

        abort_unless(
            Storage::disk('local')->exists($media->file_path),
            404
        );

        return Storage::disk('local')->download(
            $media->file_path,
            basename($media->original_name ?: 'application-evidence')
        );
    }

    private function findAssignedApplication(int $id): ShopApplication
    {
        // Return 404 for unassigned applications, including other admins' items.
        return ShopApplication::query()
            ->whereKey($id)
            ->where('assigned_admin_id', Auth::id())
            ->with([
                'applicant:id,name,email',
                'media',
                'reviews.actor',
            ])
            ->firstOrFail();
    }

    private function ensureReviewable(ShopApplication $application): void
    {
        abort_unless(
            in_array($application->status, self::REVIEWABLE_STATUSES, true),
            409,
            'This application is no longer open for review.'
        );
    }
}
