<?php

use App\Models\Category;
use App\Models\CustomerVoucher;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use App\Models\Voucher;

function voucherTestOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

test('checkout with an invalid voucher code creates no order', function () {
    $owner = voucherTestOwner();
    $category = Category::factory()->create();
    $product = Product::factory()->for($owner, 'owner')->for($category)->create([
        'price' => 1000,
        'stock' => 5,
    ]);
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 2]])
        ->post(route('checkout.place'), [
            'customer_name' => 'Test Customer',
            'phone' => '01700000000',
            'shipping_address' => 'House 12, Dhaka',
            'payment_method' => 'cod',
            'voucher_code' => 'SSNOPE123456',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('voucher_code');

    expect(Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(5)
        ->and(session('cart'))->toBe([$product->id => 2]);
});

test('checkout with a valid category voucher applies the discount and redeems the voucher', function () {
    $owner = voucherTestOwner();
    $category = Category::factory()->create();
    $product = Product::factory()->for($owner, 'owner')->for($category)->create([
        'price' => 1000,
        'stock' => 5,
    ]);
    $customer = User::factory()->customer()->create();
    $voucher = Voucher::factory()->forCategory($category)->create();
    $customerVoucher = CustomerVoucher::factory()->for($voucher)->for($customer)->create();

    $response = $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 2]])
        ->post(route('checkout.place'), [
            'customer_name' => 'Test Customer',
            'phone' => '01700000000',
            'shipping_address' => 'House 12, Dhaka',
            'payment_method' => 'cod',
            'voucher_code' => $customerVoucher->code,
        ]);

    $order = Order::query()->sole();

    $response->assertRedirect(route('orders.show', $order));

    expect((float) $order->total)->toBe(1900.0)
        ->and((float) $order->voucher_discount_total)->toBe(100.0)
        ->and($order->voucher_code)->toBe($customerVoucher->code)
        ->and((float) $order->items()->first()->voucher_discount_amount)->toBe(100.0)
        ->and($product->fresh()->stock)->toBe(3)
        ->and($customerVoucher->fresh()->status)->toBe('redeemed')
        ->and($customerVoucher->fresh()->redeemed_order_id)->toBe($order->id);
});

test('checkout with a voucher matching no cart product creates no order', function () {
    $owner = voucherTestOwner();
    $productCategory = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $product = Product::factory()->for($owner, 'owner')->for($productCategory)->create([
        'price' => 1000,
        'stock' => 5,
    ]);
    $customer = User::factory()->customer()->create();
    $voucher = Voucher::factory()->forCategory($otherCategory)->create();
    $customerVoucher = CustomerVoucher::factory()->for($voucher)->for($customer)->create();

    $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 2]])
        ->post(route('checkout.place'), [
            'customer_name' => 'Test Customer',
            'phone' => '01700000000',
            'shipping_address' => 'House 12, Dhaka',
            'payment_method' => 'cod',
            'voucher_code' => $customerVoucher->code,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('voucher_code');

    expect(Order::count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(5);
});
