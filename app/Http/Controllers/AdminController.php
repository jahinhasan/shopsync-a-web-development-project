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
        abort_unless(Auth::user()->role === 'admin', 403);
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalCategories = Category::count();
        $totalSales = (float) Order::whereIn('status', [
            'processing',
            'shipped',
            'delivered    ',
        ])->sum('total');
        $recentOrders = Order::with('user')->latest()->take(8)->get();
        return view('admin.dashboard', compact('totalUsers', 'totalProducts', 'totalOrders', 'totalCategories', 'totalSales', 'recentOrders'));
    }
}
