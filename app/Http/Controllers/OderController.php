<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class OderController extends Controller
{
    public function index(): View
    {
        $orders = Order::where('user_id', Auth::id())->latest()->paginate(12);

        return view('order.index', compact('orders'));
    }

    public function show(Order $order,): View
    {
        abort_unless($order->user_id === Auth::id(), 403);
        $order->load('items');
        return view('order.show', compact('order'));
    }

    public function manage(): \Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $query = Order::query()->with('user')->latest();

        if ($user->role === 'business_owner') {
            $query->whereHas('items.product', function ($productQuery) use ($user) {
                $productQuery->where('user_id', $user->id);
            });
        } else if ($user->role === 'admin') {
            abort(403);
        }

        $orders = $query->paginate(12);
        return view('order.manage', compact('orders'));
        // Admin can see all orders, no additional filtering needed
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {


        $user = Auth::user();
        if ($user->role === 'business_owner') {
            $ownsProduct = $order->items()->whereHas('product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->exists();

            abort_unless($ownsProduct, 403);
        } elseif ($user->role === 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),],
        ]);

$order->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Order status updated successfully.');
    }
}
