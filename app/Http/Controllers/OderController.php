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
   public function manage(): View
{
    $userId = Auth::id();

    $orders = Order::whereHas('items.product', function ($query) use ($userId) {
        $query->where('user_id', $userId);
    })
        ->with(['items.product', 'user'])
        ->latest()
        ->paginate(15);

    return view('order.manage', compact('orders'));
}

public function updateStatus(
    Request $request,
    Order $order
): RedirectResponse {
    $validated = $request->validate([
        'status' => [
            'required',
            Rule::in([
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
            ]),
        ],
    ]);

    $ownsOrderProduct = $order->items()
        ->whereHas('product', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->exists();

    abort_unless($ownsOrderProduct, 403);

    $order->update([
        'status' => $validated['status'],
    ]);

    return redirect()
        ->route('owner.order.manage')
        ->with('success', 'Order status updated successfully.');
}
}
