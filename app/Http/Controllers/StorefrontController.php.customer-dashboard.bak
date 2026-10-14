<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->where('stock', '>', 0)
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

    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->stock > 0, 404);

        $product->load(['category', 'owner']);

        return view('shop.show', compact('product'));
    }
}
