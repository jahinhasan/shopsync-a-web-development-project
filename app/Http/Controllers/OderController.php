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
        $orders = Order::where('user_id', Auth::id())
            ->with('items')
            ->latest()
            ->paginate(12);

        return view('order.index', compact('orders'));
    }

    public function show(Order $order,): View
    {
        abort_unless((int) $order->user_id ===(int) Auth::id(), 403);
        $order->load('items');
        return view('order.show', compact('order'));
    }

    public function manage(): View
    {
        $userId = Auth::id();

        $orders = Order::query()
            ->whereHas('items.product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'items' => function ($query) use ($userId) {
                    $query->whereHas('product', function ($productQuery) use ($userId) {
                        $productQuery->where('user_id', $userId);
                    })->with('product');
                },
                'user',
            ])
            ->latest()
            ->paginate(15);

        return view('order.manage', compact('orders'));
    }

    public function updateStatus(
        Request $request,
        Order $order
    ): RedirectResponse {
        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
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

        $userId = Auth::id();

        $item = $order->items()
            ->whereKey($validated['item_id'])
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        $allowedTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        $currentStatus = $item->status;
        $newStatus = $validated['status'];

        if ($currentStatus === $newStatus) {
            return back()->with('success', 'Item status is unchanged.');
        }

        if (! in_array(
            $newStatus,
            $allowedTransitions[$currentStatus] ?? [],
            true
        )) {
            return back()
                ->withErrors([
                    'status' => "Cannot change item status from {$currentStatus} to {$newStatus}.",
                ])
                ->withInput();
        }

        $item->update(['status' => $newStatus]);

        return redirect()
            ->route('owner.order.manage')
            ->with('success', 'Product order status updated successfully.');
    }
}
