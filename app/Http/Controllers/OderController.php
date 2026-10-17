<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Services\CustomerRewardService;
use Illuminate\Support\Facades\DB;
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
        if ($newStatus === 'delivered') {
    app(CustomerRewardService::class)->processDeliveredItems($order);
}

        return redirect()
            ->route('owner.order.manage')
            ->with('success', 'Product order status updated successfully.');
    }
    public function updatePaymentStatus(
    Request $request,
    Order $order,
    OrderIdem $item
): RedirectResponse {
    $data = $request->validate([
        'payment_status' => ['required', Rule::in(['paid', 'failed'])],
    ]);

    $userId = (int) Auth::id();

    abort_unless((int) $item->order_id === (int) $order->id, 404);

    $ownsProduct = Product::withTrashed()
        ->whereKey($item->product_id)
        ->where('user_id', $userId)
        ->exists();

    abort_unless($ownsProduct, 403);

    if (
        $data['payment_status'] === 'paid'
        && in_array($order->payment_method, ['bkash', 'nagad'], true)
        && blank($order->payment_reference)
    ) {
        return back()->withErrors([
            'payment_status' => 'No transaction reference was submitted by the customer.',
        ]);
    }

    if (
        $data['payment_status'] === 'paid'
        && $order->payment_method === 'cod'
        && $item->status !== 'delivered'
    ) {
        return back()->withErrors([
            'payment_status' => 'Confirm COD payment after marking this item delivered.',
        ]);
    }

    DB::transaction(function () use ($order, $item, $data, $userId) {
        $lockedOrder = Order::query()
            ->whereKey($order->id)
            ->lockForUpdate()
            ->firstOrFail();

        $lockedItem = OrderIdem::query()
            ->whereKey($item->id)
            ->where('order_id', $lockedOrder->id)
            ->lockForUpdate()
            ->firstOrFail();

        $paid = $data['payment_status'] === 'paid';

        $lockedItem->forceFill([
            'payment_status' => $data['payment_status'],
            'payment_verified_by' => $paid ? $userId : null,
            'payment_verified_at' => $paid ? now() : null,
        ])->save();

        $activeItems = $lockedOrder->items()
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $allPaid = $activeItems->isNotEmpty()
            && $activeItems->every(
                fn ($orderItem) => $orderItem->payment_status === 'paid'
            );

        $lockedOrder->forceFill([
            'payment_status' => $allPaid ? 'paid' : 'pending',
        ])->save();
    });

    $order->refresh();

    if ($order->payment_status === 'paid') {
        app(CustomerRewardService::class)->processDeliveredItems($order);
    }

    return back()->with(
        'success',
        $data['payment_status'] === 'paid'
            ? 'Payment verified for your product in this order.'
            : 'Payment reference marked as failed for your product.'
    );
}
}
