<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
   public function index(): View {
    $userId = Auth::id();

    $totalProducts = Product::where('user_id', $userId)->count();
    $totalStock = Product::where('user_id', $userId)->sum('stock');
    $orderQuery = Order::whereHas('items.product', function ($query) use ($userId) {
        $query->where('user_id', $userId);
    });
$totalOrders = (clone $orderQuery)->distinct()->count('orders.id');
$totalSales = (clone $orderQuery)->whereIn(
    'status',['processing','shipped','delivered']
)->sum('total');

return view('dashboards.owner',compact(
'totalProducts','totalStock','totalOrders','totalSales',
));




   }
}
