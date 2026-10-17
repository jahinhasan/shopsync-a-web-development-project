<?php

namespace App\Http\Controllers;

use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $customer = $request->user();

        abort_unless($customer?->isCustomer(), 403);
        abort_unless($product->isAvailableToCustomers(), 404);

        $data = $request->validate([
            'comment' => ['required', 'string', 'min:2', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $values = ['comment' => $data['comment']];

        if (isset($data['rating'])) {
            if (!$this->hasPurchased($customer->id, $product->id)) {
                return back()
                    ->withErrors(['rating' => 'Order this product before rating it.'])
                    ->withInput();
            }

            $values['rating'] = (int) $data['rating'];
        }

        ProductReview::updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $customer->id,
            ],
            $values
        );

        return redirect()
            ->to(route('shop.show', $product) . '#reviews')
            ->with('success', 'Your review has been saved.');
    }

    private function hasPurchased(int $userId, int $productId): bool
    {
        $excluded = ['cancelled', 'canceled', 'refunded'];

        return OrderIdem::query()
            ->where('product_id', $productId)
            ->whereNotIn('status', $excluded)
            ->whereHas('order', function ($query) use ($userId, $excluded) {
                $query->where('user_id', $userId)
                    ->whereNotIn('status', $excluded);
            })
            ->exists();
    }
}