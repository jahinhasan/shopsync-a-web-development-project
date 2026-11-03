<?php

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;

function cancellationSetup(int $stock = 5, int $quantity = 2): array
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    $customer = User::factory()->customer()->create();

    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create(['stock' => $stock]);

    $order = Order::create([
        'user_id' => $customer->id,
        'order_number' => 'ORD-'.strtoupper(uniqid()),
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'customer_name' => 'Cancel Customer',
        'phone' => '01700000000',
        'shipping_address' => 'House 12, Dhaka',
        'total' => (float) $product->price * $quantity,
    ]);

    $item = OrderIdem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $product->price,
        'quantity' => $quantity,
        'subtotal' => (float) $product->price * $quantity,
        'status' => 'pending',
    ]);

    return [$owner, $order, $item, $product];
}

test('cancelling a pending item restores stock and records the ledger reversal', function () {
    [$owner, $order, $item, $product] = cancellationSetup(stock: 5, quantity: 2);

    $this->actingAs($owner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertRedirect(route('owner.order.manage'))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(7);

    $transaction = InventoryTransaction::query()->sole();

    expect($transaction->type)->toBe('cancellation')
        ->and((int) $transaction->quantity_before)->toBe(5)
        ->and((int) $transaction->quantity_change)->toBe(2)
        ->and((int) $transaction->quantity_after)->toBe(7)
        ->and($transaction->order_id)->toBe($order->id)
        ->and($item->fresh()->status)->toBe('cancelled');
});

test('duplicate cancellation requests never restore stock twice', function () {
    [$owner, $order, $item, $product] = cancellationSetup(stock: 5, quantity: 2);

    $this->actingAs($owner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    // The duplicate request hits the already-cancelled item.
    $this->actingAs($owner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Item status is unchanged.');

    expect($product->fresh()->stock)->toBe(7)
        ->and(InventoryTransaction::query()->where('type', 'cancellation')->count())->toBe(1);
});

test('a shipped item cannot be cancelled and stock is untouched', function () {
    [$owner, $order, $item, $product] = cancellationSetup(stock: 5, quantity: 2);
    $item->update(['status' => 'shipped']);

    $this->actingAs($owner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('status');

    expect($product->fresh()->stock)->toBe(5)
        ->and(InventoryTransaction::query()->count())->toBe(0)
        ->and($item->fresh()->status)->toBe('shipped');
});

test('an owner cannot cancel another owner\'s order item', function () {
    [$order, $item] = cancellationSetup(stock: 5, quantity: 1);

    $otherOwner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($otherOwner, 'applicant')->approved()->create();

    $this->actingAs($otherOwner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertNotFound();
});

test('cancelling a processing item also restores stock', function () {
    [$owner, $order, $item, $product] = cancellationSetup(stock: 4, quantity: 3);
    $item->update(['status' => 'processing']);

    $this->actingAs($owner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($product->fresh()->stock)->toBe(7);
});
