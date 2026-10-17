<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Models\OrderIdem;
class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));

$productsQuery = Product::query()
    ->availableToCustomers()
    ->with(['owner', 'category']);

        if ($search !== '') {
            $productsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                        $ownerQuery->where('name', 'like', '%' . $search . '%');
                    })->orWhereHas('owner.shopApplication', function ($applicationQuery) use ($search) {
    $applicationQuery->where('shop_name', 'like', '%' . $search . '%');
});
            });
        }

        if ($categorySlug !== '') {
            $productsQuery->whereHas('category', function ($query) use ($categorySlug) {
                $query->where('slug', $categorySlug)
                    ->where('is_active', true);
            });
        }

        $products = $productsQuery->latest()
            ->paginate(12)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('shop.index', compact('products', 'categories', 'search', 'categorySlug'));
    }

public function show(Request $request, Product $product): View
{
    abort_unless($product->isAvailableToCustomers(), 404);

    $product->load(['category', 'owner']);

    $reviews = $product->reviews()
        ->with('user')
        ->latest()
        ->paginate(8);

    $ratingQuery = $product->reviews()->whereNotNull('rating');

    $averageRating = (float) ($ratingQuery->avg('rating') ?? 0);
    $ratingCount = (int) $ratingQuery->count();

    $customer = $request->user();
    $userReview = null;
    $hasPurchased = false;

    if ($customer?->isCustomer()) {
        $userReview = $product->reviews()
            ->where('user_id', $customer->id)
            ->first();

        $excluded = ['cancelled', 'canceled', 'refunded'];

        $hasPurchased = OrderIdem::query()
            ->where('product_id', $product->id)
            ->whereNotIn('status', $excluded)
            ->whereHas('order', function ($query) use ($customer, $excluded) {
                $query->where('user_id', $customer->id)
                    ->whereNotIn('status', $excluded);
            })
            ->exists();
    }

    return view('shop.show', compact(
        'product',
        'reviews',
        'averageRating',
        'ratingCount',
        'hasPurchased',
        'userReview'
    ));
}
}
