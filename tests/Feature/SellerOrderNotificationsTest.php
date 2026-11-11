<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use App\Notifications\NewOrderReceived;

function notificationTestOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

function placeOrderPayload(): array
{
    return [
        'customer_name' => 'Test Customer',
        'phone' => '01700000000',
        'shipping_address' => 'House 12, Dhaka',
        'payment_method' => 'cod',
    ];
}

function orderNotification(User $owner, Order $order)
{
    return $owner->notifications()
        ->where('type', NewOrderReceived::class)
        ->where('data->order_id', $order->id)
        ->first();
}

test('a seller receives one notification when a customer successfully orders their product', function () {
    $owner = notificationTestOwner();
    $customer = User::factory()->customer()->create();
    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create(['stock' => 5, 'name' => 'Wireless Mouse']);

    $response = $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 2]])
        ->post(route('checkout.place'), placeOrderPayload());

    $response->assertRedirect();

    $order = Order::first();
    expect($order)->not->toBeNull();

    $notifications = $owner->notifications()->where('type', NewOrderReceived::class)->get();

    expect($notifications)->toHaveCount(1);

    $data = $notifications->first()->data;

    expect($data['title'])->toBe('New Order Received!')
        ->and($data['customer_name'])->toBe('Test Customer')
        ->and($data['order_id'])->toBe($order->id)
        ->and($data['order_number'])->toBe($order->order_number)
        ->and($data['order_status'])->toBe('pending')
        ->and($data['seller_subtotal'])->toEqual(round((float) $product->price * 2, 2))
        ->and($data['placed_at'])->toBe($order->created_at->toDateTimeString())
        ->and($data['items'][0]['product_name'])->toBe('Wireless Mouse')
        ->and($data['items'][0]['quantity'])->toBe(2)
        ->and($data['message'])->toContain('Test Customer')
        ->and($data['message'])->toContain($order->order_number)
        ->and($data['message'])->toContain('Wireless Mouse');

    // The customer placed the order; they are not the one being notified.
    expect($customer->notifications()->count())->toBe(0);
});

test('a multi-seller order notifies each seller once, scoped to their own products', function () {
    $ownerA = notificationTestOwner();
    $ownerB = notificationTestOwner();
    $customer = User::factory()->customer()->create();

    $productA = Product::factory()
        ->for($ownerA, 'owner')
        ->for(Category::factory())
        ->create(['stock' => 10, 'name' => 'Owner A Widget']);
    $productB = Product::factory()
        ->for($ownerB, 'owner')
        ->for(Category::factory())
        ->create(['stock' => 10, 'name' => 'Owner B Gadget']);

    $this->actingAs($customer)
        ->withSession(['cart' => [$productA->id => 3, $productB->id => 1]])
        ->post(route('checkout.place'), placeOrderPayload())
        ->assertRedirect();

    $order = Order::first();

    expect($ownerA->notifications()->count())->toBe(1)
        ->and($ownerB->notifications()->count())->toBe(1);

    $dataA = orderNotification($ownerA, $order)->data;
    $dataB = orderNotification($ownerB, $order)->data;

    // Seller A sees only their product, quantity and subtotal.
    expect(collect($dataA['items'])->pluck('product_name')->all())->toBe(['Owner A Widget'])
        ->and($dataA['items'][0]['quantity'])->toBe(3)
        ->and($dataA['seller_subtotal'])->toEqual(round((float) $productA->price * 3, 2))
        ->and($dataA['message'])->not->toContain('Owner B Gadget');

    // Seller B sees only their product, quantity and subtotal.
    expect(collect($dataB['items'])->pluck('product_name')->all())->toBe(['Owner B Gadget'])
        ->and($dataB['items'][0]['quantity'])->toBe(1)
        ->and($dataB['seller_subtotal'])->toEqual(round((float) $productB->price, 2))
        ->and($dataB['message'])->not->toContain('Owner A Widget');
});

test('a failed checkout creates no order and no notifications', function () {
    $owner = notificationTestOwner();
    $customer = User::factory()->customer()->create();
    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create(['stock' => 2]);

    $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 5]])
        ->post(route('checkout.place'), placeOrderPayload())
        ->assertRedirect()
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0)
        ->and($owner->notifications()->count())->toBe(0)
        ->and($product->fresh()->stock)->toBe(2);
});

test('cancelling an order item afterwards sends no additional notification', function () {
    $owner = notificationTestOwner();
    $customer = User::factory()->customer()->create();
    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create(['stock' => 5]);

    $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 2]])
        ->post(route('checkout.place'), placeOrderPayload())
        ->assertRedirect();

    $order = Order::first();
    $item = $order->items->first();

    expect($owner->notifications()->count())->toBe(1);

    $this->actingAs($owner)
        ->patch(route('owner.orders.update-status', $order), [
            'item_id' => $item->id,
            'status' => 'cancelled',
        ])
        ->assertRedirect();

    // Only the original creation notification remains; cancellation adds none.
    expect($owner->notifications()->count())->toBe(1);
});

test('an order notification links to the order on the seller manage-orders page', function () {
    $owner = notificationTestOwner();
    $customer = User::factory()->customer()->create();
    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create(['stock' => 5]);

    $this->actingAs($customer)
        ->withSession(['cart' => [$product->id => 1]])
        ->post(route('checkout.place'), placeOrderPayload())
        ->assertRedirect();

    $order = Order::first();
    $data = orderNotification($owner, $order)->data;

    expect($data['url'])->toBe(route('owner.order.manage').'#order-'.$order->id);

    // The seller can follow the link and sees the order there.
    $this->actingAs($owner)
        ->get(route('owner.order.manage'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('id="order-'.$order->id.'"', false);
});

test('a seller cannot read another seller\'s notification', function () {
    $ownerA = notificationTestOwner();
    $ownerB = notificationTestOwner();
    $customer = User::factory()->customer()->create();
    $productA = Product::factory()->for($ownerA, 'owner')->for(Category::factory())->create(['stock' => 5]);

    $this->actingAs($customer)
        ->withSession(['cart' => [$productA->id => 1]])
        ->post(route('checkout.place'), placeOrderPayload())
        ->assertRedirect();

    $order = Order::first();
    $notificationA = orderNotification($ownerA, $order);

    // Seller B cannot mark seller A's notification as read.
    $this->actingAs($ownerB)
        ->patch(route('notifications.read', $notificationA->id))
        ->assertNotFound();

    expect($notificationA->fresh()->read_at)->toBeNull();

    // Seller B's own notifications page does not expose seller A's notification.
    $this->actingAs($ownerB)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertDontSee($order->order_number);
});

test('marking a notification read and marking all read update the unread state', function () {
    $owner = notificationTestOwner();
    $customer = User::factory()->customer()->create();
    $product = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 10]);

    foreach ([1, 2] as $quantity) {
        $this->actingAs($customer)
            ->withSession(['cart' => [$product->id => $quantity]])
            ->post(route('checkout.place'), placeOrderPayload())
            ->assertRedirect();
    }

    expect($owner->unreadNotifications()->count())->toBe(2);

    $first = orderNotification($owner, Order::first());

    $this->actingAs($owner)
        ->patch(route('notifications.read', $first->id))
        ->assertRedirect();

    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($owner->unreadNotifications()->count())->toBe(1);

    $this->actingAs($owner)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($owner->unreadNotifications()->count())->toBe(0);
});

test('guests cannot access the notification endpoints', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));

    $this->patch(route('notifications.read-all'))->assertRedirect(route('login'));

    $this->patch(route('notifications.read', '00000000-0000-0000-0000-000000000000'))
        ->assertRedirect(route('login'));
});
