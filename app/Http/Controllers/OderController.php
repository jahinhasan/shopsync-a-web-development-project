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
    public function manage()
    {
        $userId = Auth::id();

        $orders = \App\Models\Order::whereHas('items.product', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with(['items.product', 'user'])
            ->latest()
            ->paginate(15);

        return view('order.manage', compact('orders'));
    }

    public function updateStatus(
        \Illuminate\Http\Request $request,
        \App\Models\Order $order
    ) {
        $request->validate([
            'status' => [
                'required',
                \Illuminate\Validation\Rule::in([
                    'processing',
                    'shipped',
                    'delivered',
                    'cancelled',
                ]),
            ],
        ]);

        $userId = Auth::id();

        $ownsOrderProduct = $order->items()
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->exists();

        abort_unless($ownsOrderProduct, 403);

        $order->update([
            'status' => $request->status,
        ]);

        return redirect()
            ->route('orders.manage')
            ->with('success', 'Order status updated successfully.');
    }
}
