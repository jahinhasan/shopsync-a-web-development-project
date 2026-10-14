<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
   public function dashboard(): View
    {
        $totalUsers = User::count();

        $totalProducts = Product::count();

        $totalCategories = Category::count();

        $totalOrders = Order::count();

        $totalSales = Order::whereIn('status', [
            'processing',
            'shipped',
            'delivered',
        ])->sum('total');

        $recentOrders = Order::latest()
            ->take(5)
            ->get();

        return view('dashboards.admin', compact(
            'totalUsers',
            'totalProducts',
            'totalCategories',
            'totalOrders',
            'totalSales',
            'recentOrders'
        ));
    }
}
