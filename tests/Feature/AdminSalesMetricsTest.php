<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;

function createMetricOrder(User $customer, array $items): Order
{
    $order = Order::create([
        'user_id' => $customer->id,
        'order_number' => 'ORD-'.strtoupper(uniqid()),
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'customer_name' => 'Metric Customer',
        'phone' => '01700000000',
        'shipping_address' => 'House 12, Dhaka',
        'total' => array_sum(array_column($items, 'subtotal')),
    ]);

    foreach ($items as $item) {
        OrderIdem::create([
            'order_id' => $order->id,
            'product_id' => $item['product_id'] ?? null,
            'product_name' => $item['product_name'],
            'unit_price' => $item['unit_price'],
            'quantity' => $item['quantity'],
            'subtotal' => $item['subtotal'],
            'status' => $item['status'],
        ]);
    }

    return $order;
}

function metricCustomer(): User
{
    return User::factory()->customer()->create();
}

test('admin dashboard sales aggregate item subtotals, not the dead order status', function () {
    // orders.status stays 'pending' forever; only item statuses move.
    createMetricOrder(metricCustomer(), [
        ['product_name' => 'Keyboard', 'unit_price' => 1000, 'quantity' => 1, 'subtotal' => 1000, 'status' => 'processing'],
        ['product_name' => 'Mouse', 'unit_price' => 500, 'quantity' => 1, 'subtotal' => 500, 'status' => 'shipped'],
        ['product_name' => 'Monitor', 'unit_price' => 2000, 'quantity' => 1, 'subtotal' => 2000, 'status' => 'delivered'],
        ['product_name' => 'Cable', 'unit_price' => 700, 'quantity' => 1, 'subtotal' => 700, 'status' => 'pending'],
        ['product_name' => 'Case', 'unit_price' => 300, 'quantity' => 1, 'subtotal' => 300, 'status' => 'cancelled'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('totalSales', function ($totalSales) {
            return (float) $totalSales === 3500.0;
        });
});

test('admin report sales figures come from order items in the date range', function () {
    $owner = User::factory()->businessOwner()->create();
    $category = Category::factory()->create();
    $productA = Product::factory()->for($owner, 'owner')->for($category)->create(['name' => 'Coffee Beans']);
    $productB = Product::factory()->for($owner, 'owner')->for($category)->create(['name' => 'Coffee Grinder']);

    createMetricOrder(metricCustomer(), [
        ['product_id' => $productA->id, 'product_name' => $productA->name, 'unit_price' => 800, 'quantity' => 2, 'subtotal' => 1600, 'status' => 'delivered'],
        ['product_id' => $productB->id, 'product_name' => $productB->name, 'unit_price' => 2500, 'quantity' => 1, 'subtotal' => 2500, 'status' => 'processing'],
        ['product_id' => $productB->id, 'product_name' => $productB->name, 'unit_price' => 2500, 'quantity' => 1, 'subtotal' => 2500, 'status' => 'cancelled'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk()
        ->assertViewHas('payload', function (array $payload): bool {
            // Delivered (1600) + processing (2500); the cancelled item is
            // excluded from net sales, orders and units alike.
            return (float) $payload['totals']['netSales'] === 4100.0
                && (int) $payload['totals']['orders'] === 1
                && (int) $payload['totals']['units'] === 3
                && count(array_filter($payload['series']['netSales'], fn ($v): bool => (float) $v > 0)) === 1;
        })
        ->assertViewHas('monthlySales', fn ($monthlySales) => count($monthlySales) === 1)
        ->assertViewHas('bestSellingProducts', function ($bestSelling) use ($productA) {
            $top = $bestSelling->first();

            return $top->product_name === $productA->name
                && (int) $top->quantity_sold === 2
                && (float) $top->revenue === 1600.0;
        });
});

test('admin report excludes items from orders outside the date range', function () {
    createMetricOrder(metricCustomer(), [
        ['product_name' => 'Now Item', 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'delivered'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    // A yesterday-only window must not include today's order.
    $this->actingAs($admin)
        ->get(route('admin.reports', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->subDay()->toDateString(),
        ]))
        ->assertSessionHasNoErrors()
        ->assertOk()
        ->assertViewHas('payload', fn (array $payload): bool => (float) $payload['totals']['netSales'] === 0.0);
});
