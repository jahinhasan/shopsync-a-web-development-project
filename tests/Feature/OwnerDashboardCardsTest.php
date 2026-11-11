<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;

function cardTestOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

function cardTestOrder(User $customer, string $orderNumber, string $itemStatus, Product $product): Order
{
    $order = Order::create([
        'user_id' => $customer->id,
        'order_number' => $orderNumber,
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'customer_name' => 'Card Test Customer',
        'phone' => '01700000000',
        'shipping_address' => 'House 12, Dhaka',
        'total' => (float) $product->price,
    ]);

    OrderIdem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => $product->price,
        'quantity' => 1,
        'subtotal' => (float) $product->price,
        'status' => $itemStatus,
    ]);

    return $order;
}

test('owner dashboard statistic cards link to their filtered destinations', function () {
    $owner = cardTestOwner();

    $this->actingAs($owner)
        ->get(route('owner.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('owner.products.index').'"', false)
        ->assertSee('href="'.route('owner.order.manage').'"', false)
        ->assertSee('href="'.route('owner.order.manage', ['status' => 'sales']).'"', false)
        ->assertSee('href="'.route('owner.order.manage', ['status' => 'pending']).'"', false)
        ->assertSee('href="'.route('owner.products.index', ['stock_status' => 'low_stock']).'"', false);
});

test('the pending filter lists only orders with items waiting on processing', function () {
    $owner = cardTestOwner();
    $customer = User::factory()->customer()->create();

    $pendingProduct = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);
    $deliveredProduct = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);

    cardTestOrder($customer, 'ORD-PENDING-01', 'pending', $pendingProduct);
    cardTestOrder($customer, 'ORD-DELIVERED-01', 'delivered', $deliveredProduct);

    $this->actingAs($owner)
        ->get(route('owner.order.manage', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('ORD-PENDING-01')
        ->assertDontSee('ORD-DELIVERED-01');
});

test('the processed-sales filter lists only orders with processed items', function () {
    $owner = cardTestOwner();
    $customer = User::factory()->customer()->create();

    $pendingProduct = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);
    $shippedProduct = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);

    cardTestOrder($customer, 'ORD-PENDING-02', 'pending', $pendingProduct);
    cardTestOrder($customer, 'ORD-SHIPPED-01', 'shipped', $shippedProduct);

    $this->actingAs($owner)
        ->get(route('owner.order.manage', ['status' => 'sales']))
        ->assertOk()
        ->assertSee('ORD-SHIPPED-01')
        ->assertDontSee('ORD-PENDING-02');
});

test('the unfiltered manage page still lists every order containing the seller products', function () {
    $owner = cardTestOwner();
    $customer = User::factory()->customer()->create();

    $pendingProduct = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);
    $deliveredProduct = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);

    cardTestOrder($customer, 'ORD-PENDING-03', 'pending', $pendingProduct);
    cardTestOrder($customer, 'ORD-DELIVERED-03', 'delivered', $deliveredProduct);

    $this->actingAs($owner)
        ->get(route('owner.order.manage'))
        ->assertOk()
        ->assertSee('ORD-PENDING-03')
        ->assertSee('ORD-DELIVERED-03');
});

test('an unknown status filter falls back to the unfiltered list', function () {
    $owner = cardTestOwner();
    $customer = User::factory()->customer()->create();

    $product = Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 5]);
    cardTestOrder($customer, 'ORD-ANY-01', 'pending', $product);

    $this->actingAs($owner)
        ->get(route('owner.order.manage', ['status' => 'not-a-status']))
        ->assertOk()
        ->assertSee('ORD-ANY-01');
});

test('the low-stock filter lists only products with five or fewer units', function () {
    $owner = cardTestOwner();

    Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 3, 'name' => 'Low Stock Item']);
    Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 12, 'name' => 'Healthy Stock Item']);

    $this->actingAs($owner)
        ->get(route('owner.products.index', ['stock_status' => 'low_stock']))
        ->assertOk()
        ->assertSee('Low Stock Item')
        ->assertDontSee('Healthy Stock Item');
});

test('the existing stock filters keep working alongside the new low-stock option', function () {
    $owner = cardTestOwner();

    Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 0, 'name' => 'Out Of Stock Item']);
    Product::factory()->for($owner, 'owner')->for(Category::factory())->create(['stock' => 7, 'name' => 'In Stock Item']);

    $this->actingAs($owner)
        ->get(route('owner.products.index', ['stock_status' => 'out_of_stock']))
        ->assertOk()
        ->assertSee('Out Of Stock Item')
        ->assertDontSee('In Stock Item');

    $this->actingAs($owner)
        ->get(route('owner.products.index', ['stock_status' => 'in_stock']))
        ->assertOk()
        ->assertSee('In Stock Item')
        ->assertDontSee('Out Of Stock Item');
});

test('dashboards without link destinations still render the kpi cards unchanged', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
