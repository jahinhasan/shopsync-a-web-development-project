<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use App\Notifications\AdminModerationNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModerationController extends Controller
{
    public function index(Request $request)
    {
        $productQuery = Product::withTrashed()
            ->with(['owner:id,name,email', 'category:id,name'])
            ->latest();

        if ($request->filled('product_search')) {
            $search = trim($request->string('product_search')->toString());

            $productQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('product_status')) {
            $status = $request->string('product_status')->toString();

            if ($status === 'removed') {
                $productQuery->onlyTrashed();
            } elseif (in_array($status, ['active', 'suspended'], true)) {
                $productQuery->where('admin_status', $status)
                    ->whereNull('deleted_at');
            }
        }

        $products = $productQuery
            ->paginate(10, ['*'], 'products_page')
            ->withQueryString();

        // Do not show private identity information in the moderation list.
        $shops = ShopApplication::query()
            ->select([
                'id',
                'user_id',
                'shop_name',
                'status',
                'shop_status',
                'shop_status_reason',
                'shop_status_updated_at',
            ])
            ->latest()
            ->paginate(10, ['*'], 'shops_page')
            ->withQueryString();

        return view('admin.moderation.index', compact('products', 'shops'));
    }

    public function suspendProduct(Request $request, int $productId)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $product = Product::query()->findOrFail($productId);

        $product->forceFill([
            'admin_status' => 'suspended',
            'admin_reason' => $data['reason'],
            'moderated_by' => Auth::id(),
        ])->save();

        $product->owner?->notify(new AdminModerationNotice([
            'type' => 'product_suspended',
            'title' => 'Product suspended by ShopSync Admin',
            'message' => "Your product '{$product->name}' has been suspended.",
            'reason' => $data['reason'],
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]));

        return back()->with('success', 'Product suspended and its owner notified.');
    }

    public function warnProductOwner(Request $request, int $productId)
    {
        $data = $request->validate([
            'warning' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $product = Product::query()->findOrFail($productId);

        $product->owner?->notify(new AdminModerationNotice([
            'type' => 'product_warning',
            'title' => 'Warning about your product',
            'message' => "The admin issued a warning about '{$product->name}'.",
            'reason' => $data['warning'],
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]));

        return back()->with('success', 'Warning sent to the product owner.');
    }

    
public function removeProduct(Request $request, int $productId)
{
    $data = $request->validate([
        'reason' => ['required', 'string', 'min:5', 'max:2000'],
    ]);

    $product = Product::query()->findOrFail($productId);

    // Save the owner before removing the product.
    $owner = $product->owner;
    $productName = $product->name;

    $product->forceFill([
        'admin_status' => 'removed',
        'admin_reason' => $data['reason'],
        'moderated_by' => Auth::id(),
    ])->save();

    // Soft-delete: hide from customers but preserve order history.
    $product->delete();

    // Notify only the owner whose product was removed.
    if ($owner) {
        $owner->notify(new AdminModerationNotice([
            'type' => 'product_removed',
            'title' => 'Your product was removed',
            'message' => "Your product '{$productName}' was removed from the ShopSync store.",
            'reason' => $data['reason'],
            'product_id' => $product->id,
            'product_name' => $productName,
        ]));
    }

    return back()->with(
        'success',
        'Product removed from the customer store. Its owner has been notified.'
    );
}

    public function restoreProduct(int $productId)
    {
        $product = Product::withTrashed()->findOrFail($productId);

        if ($product->trashed()) {
            $product->restore();
        }

        $product->forceFill([
            'admin_status' => 'active',
            'admin_reason' => null,
            'moderated_by' => Auth::id(),
        ])->save();

        $product->owner?->notify(new AdminModerationNotice([
            'type' => 'product_restored',
            'title' => 'Product moderation status updated',
            'message' => "The admin restored '{$product->name}'.",
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]));

        return back()->with('success', 'Product restored. Its owner may still need to activate it.');
    }

    public function updateShopStatus(Request $request, int $applicationId)
    {
        $data = $request->validate([
            'action' => ['required', 'in:suspend,close,reactivate'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $application = ShopApplication::query()->findOrFail($applicationId);

        if (
            $data['action'] !== 'reactivate'
            && $application->status !== 'approved'
        ) {
            return back()->withErrors([
                'shop' => 'Only an approved shop can be suspended or closed.',
            ]);
        }

        if (
            $data['action'] === 'reactivate'
            && $application->shop_status === 'closed'
        ) {
            // Reopening a closed shop is an explicit admin action.
            $newStatus = 'active';
        } else {
            $newStatus = match ($data['action']) {
                'suspend' => 'suspended',
                'close' => 'closed',
                'reactivate' => 'active',
            };
        }

        $application->forceFill([
            'shop_status' => $newStatus,
            'shop_status_reason' => $data['reason'],
            'shop_status_updated_by' => Auth::id(),
            'shop_status_updated_at' => now(),
        ])->save();

        $application->applicant?->notify(new AdminModerationNotice([
            'type' => 'shop_' . $newStatus,
            'title' => match ($newStatus) {
                'suspended' => 'Your shop has been suspended',
                'closed' => 'Your shop has been closed',
                default => 'Your shop has been reactivated',
            },
            'message' => "The status of '{$application->shop_name}' was changed to {$newStatus}.",
            'reason' => $data['reason'],
            'shop_name' => $application->shop_name,
            'shop_application_id' => $application->id,
        ]));

        return back()->with('success', "Shop status updated to {$newStatus}.");
    }
}
