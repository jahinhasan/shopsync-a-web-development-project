<?php

namespace App\Services;

use App\Models\CustomerPointTransaction;
use App\Models\CustomerVoucher;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CustomerRewardService
{
    public function balance(User $user): int
    {
        return (int) CustomerPointTransaction::query()
            ->where('user_id', $user->id)
            ->selectRaw(
                "COALESCE(SUM(CASE
                    WHEN type = 'earned' THEN points
                    WHEN type IN ('spent', 'reversed') THEN -points
                    ELSE 0
                END), 0) AS balance"
            )
            ->value('balance');
    }

    public function processDeliveredItems(Order $order): void
    {
        $order->refresh();

        if ($order->payment_status !== 'paid') {
            return;
        }

        $order->items()
            ->where('status', 'delivered')
            ->whereNull('rewards_processed_at')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (int $id) => $this->processItem($id));
    }

    private function processItem(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $item = OrderIdem::query()
                ->whereKey($itemId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($item->rewards_processed_at || $item->status !== 'delivered') {
                return;
            }

            $order = Order::query()
                ->whereKey($item->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->payment_status !== 'paid') {
                return;
            }

            $points = (int) $item->reward_points_per_unit * (int) $item->quantity;

            if ($points > 0) {
                CustomerPointTransaction::firstOrCreate(
                    [
                        'order_item_id' => $item->id,
                        'type' => 'earned',
                    ],
                    [
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'points' => $points,
                    ]
                );
            }

            $voucherIssued = false;

            if (
                $item->surprise_voucher_eligible
                && $item->reward_category_id
                && ! CustomerVoucher::query()
                    ->where('source_order_item_id', $item->id)
                    ->exists()
            ) {
                $voucher = Voucher::create([
                    'name' => 'ShopSync Surprise Reward',
                    'discount_type' => 'percent',
                    'discount_value' => 5,
                    'min_order_total' => 500,
                    'max_discount' => 100,
                    'points_cost' => null,
                    'category_id' => $item->reward_category_id,
                    'is_active' => true,
                    'funding_source' => 'platform',
                    'expires_in_days' => 30,
                ]);

                CustomerVoucher::create([
                    'voucher_id' => $voucher->id,
                    'user_id' => $order->user_id,
                    'code' => $this->makeCode(),
                    'source' => 'surprise',
                    'source_order_item_id' => $item->id,
                    'status' => 'available',
                    'expires_at' => now()->addDays(30),
                ]);

                $voucherIssued = true;
            }

            $item->forceFill([
                'rewards_processed_at' => now(),
                'surprise_voucher_issued_at' => $voucherIssued ? now() : null,
            ])->save();
        });
    }

    public function applyVoucherToOrder(
        User $user,
        Order $order,
        string $code
    ): float {
        return DB::transaction(function () use ($user, $order, $code) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->voucher_code || (float) $lockedOrder->voucher_discount_total > 0) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'A voucher has already been applied to this order.',
                ]);
            }

            $customerVoucher = CustomerVoucher::query()
                ->where('user_id', $user->id)
                ->where('code', strtoupper(trim($code)))
                ->where('status', 'available')
                ->lockForUpdate()
                ->with('voucher')
                ->first();

            if (
                ! $customerVoucher
                || ! $customerVoucher->voucher
                || ! $customerVoucher->voucher->is_active
                || ! $customerVoucher->expires_at
                || $customerVoucher->expires_at->isPast()
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'This voucher is invalid, expired, or already used.',
                ]);
            }

            $voucher = $customerVoucher->voucher;

            $items = OrderIdem::query()
                ->where('order_id', $lockedOrder->id)
                ->with(['product' => fn ($query) => $query->withTrashed()])
                ->lockForUpdate()
                ->get();

            $eligibleItems = $items->filter(function (OrderIdem $item) use ($voucher) {
                if ($voucher->product_id) {
                    return (int) $item->product_id === (int) $voucher->product_id;
                }

                if ($voucher->category_id) {
                    return (int) $item->reward_category_id === (int) $voucher->category_id;
                }

                return false;
            })->values();

            $eligibleSubtotal = round(
                (float) $eligibleItems->sum(fn ($item) => (float) $item->subtotal),
                2
            );

            if (
                $eligibleItems->isEmpty()
                || $eligibleSubtotal < (float) $voucher->min_order_total
            ) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'This voucher does not match the products or minimum order amount.',
                ]);
            }

            $rawDiscount = match ($voucher->discount_type) {
                'percent' => $eligibleSubtotal * ((float) $voucher->discount_value / 100),
                'fixed' => (float) $voucher->discount_value,
                default => 0,
            };

            $discount = round(min(
                $rawDiscount,
                (float) $voucher->max_discount,
                $eligibleSubtotal
            ), 2);

            if ($discount <= 0) {
                throw ValidationException::withMessages([
                    'voucher_code' => 'This voucher cannot discount the selected items.',
                ]);
            }

            $remaining = $discount;
            $lastIndex = $eligibleItems->count() - 1;

            foreach ($eligibleItems as $index => $item) {
                $lineSubtotal = (float) $item->subtotal;

                $allocated = $index === $lastIndex
                    ? $remaining
                    : round($discount * $lineSubtotal / $eligibleSubtotal, 2);

                $allocated = max(0, min($allocated, $lineSubtotal, $remaining));

                $item->forceFill([
                    'voucher_discount_amount' => $allocated,
                ])->save();

                $remaining = round($remaining - $allocated, 2);
            }

            $lockedOrder->forceFill([
                'voucher_code' => $customerVoucher->code,
                'voucher_discount_total' => $discount,
                'total' => max(0, round((float) $lockedOrder->total - $discount, 2)),
            ])->save();

            $customerVoucher->update([
                'status' => 'redeemed',
                'redeemed_order_id' => $lockedOrder->id,
                'redeemed_at' => now(),
            ]);

            return $discount;
        });
    }

    public function makeCode(): string
    {
        do {
            $code = 'SS' . strtoupper(Str::random(10));
        } while (CustomerVoucher::query()->where('code', $code)->exists());

        return $code;
    }
}