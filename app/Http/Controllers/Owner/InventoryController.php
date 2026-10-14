<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class InventoryController extends Controller
{
    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        abort_unless((int) $product->user_id === (int) Auth::id(), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:restock,adjustment'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($product, $validated) {
            $lockedProduct = Product::whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $lockedProduct->user_id === (int) Auth::id(),
                403
            );

            $before = (int) $lockedProduct->stock;
            $quantity = (int) $validated['quantity'];

            $change = $validated['type'] === 'restock'
                ? $quantity
                : -$quantity;

            $after = $before + $change;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock cannot be reduced below zero.',
                ]);
            }

            $lockedProduct->update([
                'stock' => $after,
            ]);

            InventoryTransaction::create([
                'product_id' => $lockedProduct->id,
                'user_id' => Auth::id(),
                'order_id' => null,
                'type' => $validated['type'],
                'quantity_before' => $before,
                'quantity_change' => $change,
                'quantity_after' => $after,
                'reason' => $validated['reason'],
            ]);
        });

        return back()->with('success', 'Stock updated and history recorded successfully.');
    }

    public function history(): View
    {
        $userId = Auth::id();

        $transactions = InventoryTransaction::query()
            ->where('user_id', $userId)
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with('product')
            ->latest()
            ->paginate(20);

        return view('owner.inventory.history', compact('transactions'));
    }
}
