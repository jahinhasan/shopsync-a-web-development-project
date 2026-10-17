<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CustomerPointTransaction;
use App\Models\CustomerVoucher;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CustomerRewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerRewardController extends Controller
{
    public function index(Request $request, CustomerRewardService $rewards): View
    {
        $user = $request->user();

        abort_unless($user->isCustomer(), 403);

        $pointsBalance = $rewards->balance($user);

        $vouchers = CustomerVoucher::query()
            ->where('user_id', $user->id)
            ->with('voucher.category')
            ->latest()
            ->paginate(12);

        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->availableToCustomers())
            ->orderBy('name')
            ->get();

        return view('customer.rewards.index', compact(
            'pointsBalance',
            'vouchers',
            'categories'
        ));
    }

    public function buyVoucher(Request $request, CustomerRewardService $rewards): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isCustomer(), 403);

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $voucherCode = DB::transaction(function () use ($user, $data, $rewards) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $category = Category::query()
                ->whereKey($data['category_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $hasEligibleProduct = Product::query()
                ->availableToCustomers()
                ->where('category_id', $category->id)
                ->exists();

            if (! $hasEligibleProduct) {
                throw ValidationException::withMessages([
                    'category_id' => 'No eligible products are available in this category.',
                ]);
            }

            $pointsCost = 100;

            if ($rewards->balance($user) < $pointsCost) {
                throw ValidationException::withMessages([
                    'points' => 'You need at least 100 points to buy this voucher.',
                ]);
            }

            $voucher = Voucher::create([
                'name' => 'Points Reward — ' . $category->name,
                'discount_type' => 'percent',
                'discount_value' => 5,
                'min_order_total' => 500,
                'max_discount' => 100,
                'points_cost' => $pointsCost,
                'category_id' => $category->id,
                'is_active' => true,
                'funding_source' => 'platform',
                'expires_in_days' => 30,
            ]);

            $customerVoucher = CustomerVoucher::create([
                'voucher_id' => $voucher->id,
                'user_id' => $user->id,
                'code' => $rewards->makeCode(),
                'source' => 'points',
                'status' => 'available',
                'expires_at' => now()->addDays(30),
            ]);

            CustomerPointTransaction::create([
                'user_id' => $user->id,
                'customer_voucher_id' => $customerVoucher->id,
                'type' => 'spent',
                'points' => $pointsCost,
            ]);

            return $customerVoucher->code;
        });

        return redirect()
            ->route('customer.rewards')
            ->with('success', "Voucher {$voucherCode} created. It costs 100 points and is valid for 30 days.");
    }
}