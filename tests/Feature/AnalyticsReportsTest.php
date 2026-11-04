<?php

use App\Models\Category;
use App\Models\CustomerVoucher;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Carbon;

function analyticsCustomer(): User
{
    return User::factory()->customer()->create();
}

function analyticsOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

function makeMetricOrder(User $customer, array $items, ?Carbon $placedAt = null, array $orderAttributes = []): Order
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
        ...$orderAttributes,
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

    makeMetricOrderPlacedAt($order, $placedAt ?? now());

    return $order;
}

function makeMetricOrderPlacedAt(Order $order, Carbon $placedAt): void
{
    // created_at is not mass-assignable, so backdate it directly.
    Order::query()->whereKey($order->id)->update(['created_at' => $placedAt]);
}

function metricProduct(User $owner, string $name): Product
{
    return Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory()->create())
        ->create(['name' => $name]);
}

test('each owner report shows only that owner\'s sales', function () {
    $ownerA = analyticsOwner();
    $ownerB = analyticsOwner();

    $productA = metricProduct($ownerA, 'Owner A Product');
    $productB = metricProduct($ownerB, 'Owner B Product');

    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $productA->id, 'product_name' => $productA->name, 'unit_price' => 500, 'quantity' => 1, 'subtotal' => 500, 'status' => 'delivered'],
    ]);
    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $productB->id, 'product_name' => $productB->name, 'unit_price' => 900, 'quantity' => 1, 'subtotal' => 900, 'status' => 'delivered'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($ownerA)
        ->get(route('owner.reports'))
        ->assertOk()
        ->assertViewHas('payload', fn (array $p): bool => (float) $p['totals']['netSales'] === 500.0);

    // A second owner requesting the identical window must not be served
    // the first owner's cached payload.
    $this->actingAs($ownerB)
        ->get(route('owner.reports'))
        ->assertOk()
        ->assertViewHas('payload', fn (array $p): bool => (float) $p['totals']['netSales'] === 900.0);

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk()
        ->assertViewHas('payload', function (array $p): bool {
            return (float) $p['totals']['netSales'] === 1400.0
                && count($p['shopPerformance']) === 2;
        });
});

test('net sales exclude pending and cancelled items', function () {
    $owner = analyticsOwner();
    $product = metricProduct($owner, 'Mixed Status Product');

    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 1000, 'quantity' => 1, 'subtotal' => 1000, 'status' => 'delivered'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 400, 'quantity' => 2, 'subtotal' => 800, 'status' => 'processing'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 250, 'quantity' => 1, 'subtotal' => 250, 'status' => 'pending'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'cancelled'],
    ]);

    // A second order with only non-qualifying items contributes nothing.
    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 300, 'quantity' => 1, 'subtotal' => 300, 'status' => 'cancelled'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk()
        ->assertViewHas('payload', function (array $p): bool {
            return (float) $p['totals']['netSales'] === 1800.0
                && (int) $p['totals']['units'] === 3
                && (int) $p['totals']['orders'] === 1;
        });
});

test('granularity regroups the series into daily, weekly and monthly buckets', function () {
    $owner = analyticsOwner();
    $product = metricProduct($owner, 'Bucket Product');

    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'delivered'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);
    $range = [
        'from' => now()->subDays(9)->toDateString(),
        'to' => now()->toDateString(),
    ];

    $this->actingAs($admin)
        ->get(route('admin.reports', $range + ['granularity' => 'daily']))
        ->assertOk()
        ->assertViewHas('payload', fn (array $p): bool => count($p['series']['labels']) === 10);

    $this->actingAs($admin)
        ->get(route('admin.reports', $range + ['granularity' => 'weekly']))
        ->assertOk()
        ->assertViewHas('payload', function (array $p): bool {
            return count($p['series']['labels']) === 2
                && str_starts_with($p['series']['labels'][0], 'Wk ');
        });

    $this->actingAs($admin)
        ->get(route('admin.reports', $range + ['granularity' => 'monthly']))
        ->assertOk()
        ->assertViewHas('payload', function (array $p): bool {
            // Calendar-month buckets: the 10-day window may span two months,
            // and regrouping must preserve the window total either way.
            return count($p['series']['labels']) <= 2
                && count($p['series']['labels']) >= 1
                && preg_match('/^[A-Z][a-z]{2} \d{4}$/', (string) $p['series']['labels'][0]) === 1
                && (float) array_sum($p['series']['netSales']) === 100.0;
        });
});

test('period comparison produces percentage deltas against the previous window', function () {
    $owner = analyticsOwner();
    $product = metricProduct($owner, 'Compare Product');

    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 1000, 'quantity' => 1, 'subtotal' => 1000, 'status' => 'delivered'],
    ], now()->subDays(8)->startOfDay());

    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 2000, 'quantity' => 1, 'subtotal' => 2000, 'status' => 'delivered'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports', [
            'from' => now()->subDays(6)->toDateString(),
            'to' => now()->toDateString(),
            'compare' => '1',
        ]))
        ->assertOk()
        ->assertViewHas('payload', fn (array $p): bool => $p['deltas'] !== null
            && (float) $p['deltas']['netSales'] === 100.0);
});

test('projections and heatmaps stay hidden until there is enough data', function () {
    $owner = analyticsOwner();
    $product = metricProduct($owner, 'Guard Product');
    $customer = analyticsCustomer();

    // Sixteen days of orders with a rising trend: enough points for both
    // guards and enough variance for the regression to fit.
    foreach (range(0, 15) as $daysAgo) {
        makeMetricOrder($customer, [
            ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 50 * (16 - $daysAgo), 'quantity' => 1, 'subtotal' => 50 * (16 - $daysAgo), 'status' => 'delivered'],
        ], now()->subDays($daysAgo)->setTime(12, 0));
    }

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk()
        ->assertViewHas('payload', function (array $p): bool {
            return $p['series']['projection'] === null
                && $p['heatmap'] === null;
        });

    $this->actingAs($admin)
        ->get(route('admin.reports', [
            'from' => now()->subDays(15)->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('payload', function (array $p): bool {
            return is_array($p['series']['projection'])
                && $p['heatmap'] !== null
                && count($p['heatmap']['cells']) === 7;
        });
});

test('csv export streams the daily series and product pareto', function () {
    $owner = analyticsOwner();
    $product = metricProduct($owner, 'Export Product');

    makeMetricOrder(analyticsCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 4100, 'quantity' => 1, 'subtotal' => 4100, 'status' => 'delivered'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('admin.reports', ['export' => 'csv']));

    $response->assertOk();
    expect($response->baseResponse->headers->get('Content-Type'))->toContain('text/csv');
    expect($response->baseResponse->headers->get('Content-Disposition'))->toContain('.csv');

    // Run the stream callback through an output buffer to inspect the file.
    ob_start();
    ($response->baseResponse->getCallback())();
    $csv = ob_get_clean();

    expect($csv)->toContain('Net sales series');
    expect($csv)->toContain('Top products (Pareto)');
    expect($csv)->toContain('Export Product');
    expect($csv)->toContain('4100.00');
});

test('invalid report windows are rejected', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports', ['from' => now()->addDay()->toDateString()]))
        ->assertSessionHasErrors('from');

    $this->actingAs($admin)
        ->get(route('admin.reports', [
            'from' => now()->subDays(3)->toDateString(),
            'to' => now()->subDays(9)->toDateString(),
        ]))
        ->assertSessionHasErrors();
});

test('voucher usage reports each order-level discount exactly once', function () {
    $owner = analyticsOwner();
    $product = metricProduct($owner, 'Voucher Product');
    $customer = analyticsCustomer();

    $voucher = Voucher::factory()->create();
    $customerVoucher = CustomerVoucher::factory()
        ->for($voucher)
        ->for($customer)
        ->create(['status' => 'redeemed']);

    // One voucher order with TWO items and an order-level discount: the
    // usage query groups per order before summing per code, so the discount
    // must count once — never multiplied by the item count.
    makeMetricOrder($customer, [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 600, 'quantity' => 1, 'subtotal' => 600, 'status' => 'delivered'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 300, 'quantity' => 1, 'subtotal' => 300, 'status' => 'delivered'],
    ], null, [
        'voucher_code' => $customerVoucher->code,
        'voucher_discount_total' => 150,
    ]);

    // A second, non-voucher order keeps the revenue split honest.
    makeMetricOrder($customer, [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 400, 'quantity' => 1, 'subtotal' => 400, 'status' => 'delivered'],
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk()
        ->assertViewHas('payload', function (array $p) use ($customerVoucher): bool {
            $usage = $p['vouchers']['usage'];

            return $p['vouchers']['issued'] === 1
                && $p['vouchers']['redeemed'] === 1
                && $p['vouchers']['redemptionRate'] === 100.0
                && count($usage) === 1
                && $usage[0]['code'] === $customerVoucher->code
                && $usage[0]['orders'] === 1
                && $usage[0]['discount'] === 150.0
                && (float) $p['vouchers']['salesWithVoucher'] === 900.0
                && (float) $p['vouchers']['salesWithoutVoucher'] === 400.0
                && $p['vouchers']['salesWithVoucher'] + $p['vouchers']['salesWithoutVoucher'] === $p['totals']['netSales'];
            // The shape assertions above also pin the usage rows as plain
            // arrays with code/orders/discount keys, which the views, CSV
            // export and dashboards all consume.
        });
});
