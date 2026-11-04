<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use Illuminate\Support\Carbon;

function dashCustomer(): User
{
    return User::factory()->customer()->create();
}

function dashOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

function dashProduct(User $owner, string $name, int $stock = 50): Product
{
    return Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory()->create())
        ->create(['name' => $name, 'stock' => $stock]);
}

function makeDashOrder(User $customer, array $items, ?Carbon $placedAt = null): Order
{
    $order = Order::create([
        'user_id' => $customer->id,
        'order_number' => 'ORD-'.strtoupper(uniqid()),
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'customer_name' => 'Dash Customer',
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

    // created_at is not mass-assignable, so backdate it directly.
    Order::query()->whereKey($order->id)->update(['created_at' => $placedAt ?? now()]);

    return $order;
}

test('dashboard windows switch buckets and reject invalid presets', function () {
    $owner = dashOwner();
    $product = dashProduct($owner, 'Window Product');
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => $trend['window']['days'] === 30
            && $trend['window']['granularity'] === 'daily'
            && $trend['deltas'] !== null);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['window' => '7d']))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => $trend['window']['days'] === 7
            && $trend['window']['granularity'] === 'daily');

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['window' => '90d']))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => $trend['window']['days'] === 90
            && $trend['window']['granularity'] === 'weekly');

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['window' => '12m']))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => $trend['window']['days'] === 365
            && $trend['window']['granularity'] === 'monthly');

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['window' => '5z']))
        ->assertSessionHasErrors('window');
});

test('order funnel rates use every placed item as the denominator', function () {
    $owner = dashOwner();
    $product = dashProduct($owner, 'Funnel Product');
    $admin = User::factory()->create(['role' => 'admin']);

    // Placed 20 days back so the 7-day window below stays genuinely empty.
    makeDashOrder(dashCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'delivered'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'delivered'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'cancelled'],
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'pending'],
    ], now()->subDays(20));

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('trend', function (array $trend): bool {
            $funnel = $trend['orderFunnel'];

            return $funnel['total'] === 4
                && $funnel['delivered'] === 2
                && $funnel['cancelled'] === 1
                && $funnel['open'] === 1
                && $funnel['completionRate'] === 50.0
                && $funnel['cancellationRate'] === 25.0;
        });

    // No items at all: rates must be null, never zero or invented.
    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['window' => '7d']))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => $trend['orderFunnel']['total'] === 0
            && $trend['orderFunnel']['completionRate'] === null
            && $trend['orderFunnel']['cancellationRate'] === null);
});

test('customer analytics compute frequency distribution and per-customer spend', function () {
    $owner = dashOwner();
    $product = dashProduct($owner, 'Distribution Product');
    $admin = User::factory()->create(['role' => 'admin']);

    $loyal = dashCustomer();
    $single = dashCustomer();

    makeDashOrder($loyal, [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 200, 'quantity' => 1, 'subtotal' => 200, 'status' => 'delivered'],
    ]);
    makeDashOrder($loyal, [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 300, 'quantity' => 1, 'subtotal' => 300, 'status' => 'delivered'],
    ]);
    makeDashOrder($single, [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'delivered'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('trend', function (array $trend): bool {
            $acquisition = $trend['acquisition'];

            return $acquisition['orderDistribution'] === ['oneOrder' => 1, 'twoOrders' => 1, 'threePlus' => 0]
                && $acquisition['repeatRate'] === 50.0
                && $acquisition['avgOrderValuePerCustomer'] === 300.0;
        });
});

test('stock health counts low and out-of-stock products per scope', function () {
    $owner = dashOwner();
    dashProduct($owner, 'Out Of Stock', 0);
    dashProduct($owner, 'Nearly Gone', 3);
    dashProduct($owner, 'Plenty', 40);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => $trend['stockHealth'] === [
            'total' => 3,
            'lowStock' => 2,
            'outOfStock' => 1,
        ]);
});

test('owner dashboard payload never contains another shop\'s data', function () {
    $ownerA = dashOwner();
    $ownerB = dashOwner();

    $productA = dashProduct($ownerA, 'Owner A Widget');
    $productB = dashProduct($ownerB, 'Owner B Widget');

    makeDashOrder(dashCustomer(), [
        ['product_id' => $productA->id, 'product_name' => $productA->name, 'unit_price' => 500, 'quantity' => 1, 'subtotal' => 500, 'status' => 'delivered'],
    ]);
    makeDashOrder(dashCustomer(), [
        ['product_id' => $productB->id, 'product_name' => $productB->name, 'unit_price' => 900, 'quantity' => 1, 'subtotal' => 900, 'status' => 'delivered'],
    ]);

    $this->actingAs($ownerA)
        ->get(route('owner.dashboard'))
        ->assertOk()
        ->assertViewHas('trend', function (array $trend): bool {
            $names = array_column($trend['topProducts'], 'name');

            return (float) $trend['totals']['netSales'] === 500.0
                && $names === ['Owner A Widget']
                && $trend['shopPerformance'] === null;
        });

    // A manipulated window parameter cannot widen the scope: the owner id
    // in the cache key comes from Auth::id(), never from the request.
    $this->actingAs($ownerB)
        ->get(route('owner.dashboard', ['window' => '12m']))
        ->assertOk()
        ->assertViewHas('trend', fn (array $trend): bool => (float) $trend['totals']['netSales'] === 900.0
            && $trend['stockHealth']['total'] === 1);
});

test('hero deltas compare the window against the preceding one', function () {
    $owner = dashOwner();
    $product = dashProduct($owner, 'Delta Product');
    $admin = User::factory()->create(['role' => 'admin']);

    makeDashOrder(dashCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 100, 'quantity' => 1, 'subtotal' => 100, 'status' => 'delivered'],
    ], now()->subDays(8)->startOfDay());

    makeDashOrder(dashCustomer(), [
        ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 500, 'quantity' => 1, 'subtotal' => 500, 'status' => 'delivered'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['window' => '7d']))
        ->assertOk()
        ->assertViewHas('trend', function (array $trend): bool {
            $deltas = $trend['deltas'];

            return $deltas !== null
                && $deltas['netSales'] === 400.0
                && $trend['previous']['totals']['netSales'] === 100.0
                && $trend['series']['summary']['direction'] !== null;
        });
});

test('owner csv export respects the owner scope', function () {
    $ownerA = dashOwner();
    $ownerB = dashOwner();

    $productA = dashProduct($ownerA, 'Owner A Export Widget');
    $productB = dashProduct($ownerB, 'Owner B Secret Widget');

    makeDashOrder(dashCustomer(), [
        ['product_id' => $productA->id, 'product_name' => $productA->name, 'unit_price' => 700, 'quantity' => 1, 'subtotal' => 700, 'status' => 'delivered'],
    ]);
    makeDashOrder(dashCustomer(), [
        ['product_id' => $productB->id, 'product_name' => $productB->name, 'unit_price' => 900, 'quantity' => 1, 'subtotal' => 900, 'status' => 'delivered'],
    ]);

    $response = $this->actingAs($ownerA)->get(route('owner.reports', ['export' => 'csv']));
    $response->assertOk();

    ob_start();
    ($response->baseResponse->getCallback())();
    $csv = ob_get_clean();

    expect($csv)->toContain('Owner A Export Widget');
    expect($csv)->not->toContain('Owner B Secret Widget');
});
