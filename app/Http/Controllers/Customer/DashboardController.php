<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $availableProducts = fn($query) => $query
            ->availableToCustomers();

        $featuredProducts = Product::query()
            ->availableToCustomers()
            ->with(['owner', 'category'])
            ->orderByDesc('rating_average')
            ->orderByDesc('rating_count')
            ->latest()
            ->take(8)
            ->get();

        $newArrivals = Product::query()
            ->availableToCustomers()
            ->with(['owner', 'category'])
            ->latest()
            ->take(8)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', $availableProducts)
            ->with([
                'products' => fn($query) => $query
                    ->availableToCustomers()
                    ->with('owner')
                    ->latest()
                    ->take(8),
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn($category) => $category->products->isNotEmpty())
            ->values();

        $topShops = DB::table('products')
            ->join('users', 'users.id', '=', 'products.user_id')
            ->join('shop_applications', 'shop_applications.user_id', '=', 'users.id')
            ->where('users.role', 'business_owner')
            ->where('shop_applications.status', 'approved')
            ->where('shop_applications.shop_status', 'active')
            ->where('products.is_active', true)
            ->where('products.admin_status', 'active')
            ->whereNull('products.deleted_at')
            ->where('products.stock', '>', 0)
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(products.id) as product_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('product_count')
            ->orderBy('users.name')
            ->limit(6)
            ->get();

        return view('dashboards.customer', compact(
            'featuredProducts',
            'newArrivals',
            'categories',
            'topShops'
        ));
    }
}
