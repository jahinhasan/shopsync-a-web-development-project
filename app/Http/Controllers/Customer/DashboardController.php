<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
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

$sellerSearch = mb_substr(
    trim((string) $request->query('seller_q', '')),
    0,
    100
);

$sellerType = trim((string) $request->query('seller_type', ''));

$shopTypes = DB::table('shop_applications as sa')
    ->join('users as u', 'u.id', '=', 'sa.user_id')
    ->where('u.role', 'business_owner')
    ->where('sa.status', 'approved')
    ->where('sa.shop_status', 'active')
    ->whereExists(function ($query) {
        $query->selectRaw('1')
            ->from('products as p')
            ->whereColumn('p.user_id', 'u.id')
            ->where('p.is_active', true)
            ->where('p.admin_status', 'active')
            ->where('p.stock', '>', 0)
            ->whereNull('p.deleted_at');
    })
    ->whereNotNull('sa.shop_type')
    ->where('sa.shop_type', '!=', '')
    ->distinct()
    ->orderBy('sa.shop_type')
    ->pluck('sa.shop_type');

$shopsQuery = DB::table('shop_applications as sa')
    ->join('users as u', 'u.id', '=', 'sa.user_id')
    ->join('products as p', 'p.user_id', '=', 'u.id')
    ->where('u.role', 'business_owner')
    ->where('sa.status', 'approved')
    ->where('sa.shop_status', 'active')
    ->where('p.is_active', true)
    ->where('p.admin_status', 'active')
    ->where('p.stock', '>', 0)
    ->whereNull('p.deleted_at')
    ->select(
        'u.id',
        'sa.shop_name',
        'sa.slug',
        'sa.shop_type',
        DB::raw('COUNT(DISTINCT p.id) as product_count')
    )
    ->groupBy(
        'u.id',
        'sa.shop_name',
        'sa.slug',
        'sa.shop_type'
    );

if ($sellerSearch !== '') {
    $shopsQuery->where(
        'sa.shop_name',
        'like',
        '%' . $sellerSearch . '%'
    );
}

if ($sellerType !== '') {
    $shopsQuery->where('sa.shop_type', $sellerType);
}

$topShops = $shopsQuery
    ->orderBy('sa.shop_name')
    ->paginate(12)
    ->withQueryString();

        return view('dashboards.customer', compact(
            'featuredProducts',
            'newArrivals',
            'categories',
            'topShops',
            'sellerSearch',
'sellerType',
'shopTypes'
        ));
    }
}
