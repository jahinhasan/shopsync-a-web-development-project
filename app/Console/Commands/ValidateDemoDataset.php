<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Read-only validation for the ShopSync demo dataset.
 *
 * Verifies referential integrity, order totals, inventory chain
 * consistency, owner order visibility, report-query compatibility and
 * image references. This command never writes to the database.
 */
class ValidateDemoDataset extends Command
{
    private const DATASET_SEED = 'shopsync-demo';

    private const VALID_ROLES = ['admin', 'business_owner', 'customer'];

    private const VALID_ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    private const SALES_STATUSES = ['processing', 'shipped', 'delivered'];

    private const VALID_INVENTORY_TYPES = ['restock', 'adjustment', 'sale'];

    private const VALID_PAYMENT_STATUSES = ['paid', 'unpaid'];

    private const TOTAL_EPSILON = 0.011;

    /**
     * @var list<string>
     */
    private array $failures = [];

    /**
     * @var list<string>
     */
    private array $warnings = [];

    protected $signature = 'shopsync:validate-dataset
        {--check-urls : Probe up to 25 remote image URLs with GET requests}';

    protected $description = 'Validate the ShopSync demo dataset (integrity, totals, images, inventory chains, owner visibility, report compatibility) — read-only';

    public function handle(): int
    {
        $datasetProductIds = $this->datasetProductIds();

        $this->section('Environment & manifest');
        $this->checkManifest();

        $this->section('Entity counts');
        $this->reportCounts($datasetProductIds);

        $this->section('Referential integrity');
        $this->checkReferentialIntegrity();

        $this->section('Order money math');
        $this->checkMoney();

        $this->section('Uniqueness');
        $this->checkUniqueness();

        $this->section('Value domains');
        $this->checkDomains();

        $this->section('Inventory chains');
        $this->checkInventoryChains();

        $this->section('Business owner order visibility');
        $this->checkOwnerVisibility();

        $this->section('Report query compatibility');
        $this->checkReportQueries();

        $this->section('Product images');
        $this->checkImages($datasetProductIds);

        $this->section('Edge case coverage');
        $this->reportEdgeCoverage();

        $this->section('Non-dataset records preserved');
        $this->checkPreservedAccounts();

        return $this->renderVerdict();
    }

    /**
     * Product IDs whose slug exists in the downloaded DummyJSON manifest.
     *
     * @return list<int>
     */
    private function datasetProductIds(): array
    {
        $manifest = storage_path('app/datasets/products.json');

        if (! is_file($manifest)) {
            return [];
        }

        $contents = file_get_contents($manifest);

        if ($contents === false) {
            return [];
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        $slugs = [];

        foreach ($decoded['products'] ?? [] as $raw) {
            $slug = Str::slug(trim((string) ($raw['title'] ?? '')));

            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return Product::query()->whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function checkManifest(): void
    {
        foreach (['products.json', 'categories.json'] as $file) {
            $exists = is_file(storage_path("app/datasets/{$file}"));

            $this->line(
                $exists
                    ? '  <fg=green>[OK]</> storage/app/datasets/'.$file
                    : '  <fg=yellow>[MISSING]</> storage/app/datasets/'.$file
            );

            if (! $exists) {
                $this->warnings[] = "Manifest file missing: {$file}";
            }
        }
    }

    /**
     * @param  list<int>  $datasetProductIds
     */
    private function reportCounts(array $datasetProductIds): void
    {
        $roleCounts = User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $users = User::count();

        $this->stat('Users total', $users);

        foreach (self::VALID_ROLES as $role) {
            $this->stat("Users role={$role}", (int) ($roleCounts[$role] ?? 0));
        }

        $this->stat('Users in dataset namespace (@shopsync.test)', User::where('email', 'like', '%@shopsync.test')->count());
        $this->stat('Documented demo customers (customerNN@)', User::where('email', 'regexp', '^customer[0-9]{2}@shopsync\\.test$')->count());

        $categories = Category::count();
        $parents = Category::whereNotNull('parent_id')->distinct()->count('parent_id');
        $this->stat('Categories total', $categories);
        $this->stat('Categories with a parent (children)', Category::whereNotNull('parent_id')->count());
        $this->stat('Parent categories', $parents);

        $productQuery = Product::query();
        $this->stat('Products total', $productQuery->count());
        $this->stat('Products in dataset manifest', count($datasetProductIds));
        $this->stat('Products active', Product::where('is_active', true)->count());
        $this->stat('Products inactive', Product::where('is_active', false)->count());

        $orders = Order::count();
        $generatedNumbers = $this->generatedOrderNumbers();
        $this->stat('Orders total', $orders);
        $this->stat('Orders legacy dataset (DUMMYJSON-*)', Order::where('order_number', 'like', 'DUMMYJSON-%')->count());
        $this->stat('Orders dataset generated (deterministic ORD-*)', Order::whereIn('order_number', $generatedNumbers)->count());
        $this->stat('Orders other/manual', Order::where('order_number', 'not like', 'DUMMYJSON-%')->whereNotIn('order_number', $generatedNumbers)->count());
        $this->stat('Order items', OrderIdem::count());
        $this->stat('Order items with null product', OrderIdem::whereNull('product_id')->count());

        foreach (InventoryTransaction::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type') as $type => $total) {
            $this->stat("Inventory transactions type={$type}", (int) $total);
        }

        $dateRange = Order::selectRaw('MIN(created_at) as first_order, MAX(created_at) as last_order')->first();
        $this->stat('Order date range', trim(($dateRange?->first_order ?? '?').'  ->  '.($dateRange?->last_order ?? '?')));
    }

    private function checkReferentialIntegrity(): void
    {
        $orphanProducts = Product::query()
            ->where(function ($query): void {
                $query->whereNotExists(function ($sub): void {
                    $sub->selectRaw(1)->from('users')->whereColumn('users.id', 'products.user_id');
                })->orWhereNotExists(function ($sub): void {
                    $sub->selectRaw(1)->from('categories')->whereColumn('categories.id', 'products.category_id');
                });
            })
            ->count();

        $this->check('Every product has an owner and a category', $orphanProducts === 0, "{$orphanProducts} orphaned products");

        $orphanOrders = Order::query()
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)->from('users')->whereColumn('users.id', 'orders.user_id');
            })
            ->count();

        $this->check('Every order belongs to an existing user', $orphanOrders === 0, "{$orphanOrders} orphaned orders");

        $orphanItems = OrderIdem::query()
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)->from('orders')->whereColumn('orders.id', 'order_idems.order_id');
            })
            ->count();

        $this->check('Every order item belongs to an existing order', $orphanItems === 0, "{$orphanItems} orphaned items");

        $orphanTransactions = InventoryTransaction::query()
            ->where(function ($query): void {
                $query->whereNotExists(function ($sub): void {
                    $sub->selectRaw(1)->from('products')->whereColumn('products.id', 'inventory_transactions.product_id');
                })->orWhereNotExists(function ($sub): void {
                    $sub->selectRaw(1)->from('users')->whereColumn('users.id', 'inventory_transactions.user_id');
                });
            })
            ->count();

        $this->check('Every inventory transaction has a product and user', $orphanTransactions === 0, "{$orphanTransactions} orphaned transactions");

        $badSaleReferences = InventoryTransaction::where('type', 'sale')->whereNull('order_id')->count();

        $this->check('Sale transactions reference an order', $badSaleReferences === 0, "{$badSaleReferences} sale rows without order_id");

        $badCategories = Category::query()
            ->whereNotNull('parent_id')
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)
                    ->from('categories as parents')
                    ->whereColumn('parents.id', 'categories.parent_id');
            })
            ->count();

        $this->check('Every category parent exists', $badCategories === 0, "{$badCategories} categories with missing parent");

        $nullProductItems = OrderIdem::whereNull('product_id')->count();
        $this->note("Order items with null product_id (deleted products, allowed by schema): {$nullProductItems}");
    }

    private function checkMoney(): void
    {
        $mismatchedTotals = Order::query()
            ->join('order_idems', 'order_idems.order_id', '=', 'orders.id')
            ->groupBy('orders.id', 'orders.order_number', 'orders.total')
            ->selectRaw('orders.id, orders.order_number, orders.total, SUM(order_idems.subtotal) as items_total')
            ->get()
            ->filter(fn ($row): bool => abs((float) $row->total - (float) $row->items_total) > self::TOTAL_EPSILON);

        $this->check(
            'Order total equals the sum of its item subtotals',
            $mismatchedTotals->isEmpty(),
            $mismatchedTotals->take(5)->map(fn ($row): string => "{$row->order_number}: total {$row->total} vs items {$row->items_total}")->implode('; ')
        );

        $mismatchedItems = OrderIdem::query()
            ->select(['id', 'order_id', 'unit_price', 'quantity', 'subtotal'])
            ->get()
            ->filter(fn (OrderIdem $item): bool => abs(
                (float) $item->subtotal - round((float) $item->unit_price * (int) $item->quantity, 2)
            ) > self::TOTAL_EPSILON);

        $this->check(
            'Every item subtotal equals unit_price * quantity',
            $mismatchedItems->isEmpty(),
            $mismatchedItems->take(5)->map(fn (OrderIdem $item): string => "item {$item->id}: subtotal {$item->subtotal}")->implode('; ')
        );

        $ordersWithoutItems = Order::whereDoesntHave('items')->count();
        $this->note("Orders without any items: {$ordersWithoutItems}");

        if ($ordersWithoutItems > 0) {
            $this->warnings[] = "{$ordersWithoutItems} orders have no items.";
        }
    }

    private function checkUniqueness(): void
    {
        $duplicateSlugs = Product::query()
            ->selectRaw('slug, count(*) as total')
            ->groupBy('slug')
            ->havingRaw('count(*) > 1')
            ->pluck('total', 'slug');

        $this->check('Product slugs are unique', $duplicateSlugs->isEmpty(), $duplicateSlugs->keys()->take(5)->implode(', '));

        $duplicateOrderNumbers = Order::query()
            ->selectRaw('order_number, count(*) as total')
            ->groupBy('order_number')
            ->havingRaw('count(*) > 1')
            ->pluck('total', 'order_number');

        $this->check('Order numbers are unique', $duplicateOrderNumbers->isEmpty(), $duplicateOrderNumbers->keys()->take(5)->implode(', '));
    }

    private function checkDomains(): void
    {
        $roles = User::query()->selectRaw('distinct role')->pluck('role');
        $invalidRoles = $roles->reject(fn ($role): bool => in_array($role, self::VALID_ROLES, true));

        $this->check('User roles are within the application domain', $invalidRoles->isEmpty(), 'invalid: '.$invalidRoles->implode(', '));

        $statuses = Order::query()->selectRaw('distinct status')->pluck('status');
        $invalidStatuses = $statuses->reject(fn ($status): bool => in_array($status, self::VALID_ORDER_STATUSES, true));

        $this->check('Order statuses are within the application domain', $invalidStatuses->isEmpty(), 'invalid: '.$invalidStatuses->implode(', '));

        $paymentStatuses = Order::query()->selectRaw('distinct payment_status')->pluck('payment_status');
        $invalidPaymentStatuses = $paymentStatuses->reject(fn ($status): bool => in_array($status, self::VALID_PAYMENT_STATUSES, true));

        $this->check(
            'Payment statuses are paid/unpaid',
            $invalidPaymentStatuses->isEmpty(),
            'invalid: '.$invalidPaymentStatuses->implode(', ')
        );

        $paymentMethods = Order::query()->selectRaw('distinct payment_method')->pluck('payment_method')->implode(', ');
        $this->note("Payment methods in use: {$paymentMethods}");

        $types = InventoryTransaction::query()->selectRaw('distinct type')->pluck('type');
        $invalidTypes = $types->reject(fn ($type): bool => in_array($type, self::VALID_INVENTORY_TYPES, true));

        $this->check('Inventory transaction types are within the application domain', $invalidTypes->isEmpty(), 'invalid: '.$invalidTypes->implode(', '));
    }

    private function checkInventoryChains(): void
    {
        $productsWithHistory = InventoryTransaction::query()
            ->select('product_id')
            ->distinct()
            ->orderBy('product_id')
            ->pluck('product_id');

        if ($productsWithHistory->isEmpty()) {
            $this->note('No inventory transactions found to validate.');

            return;
        }

        $brokenRows = 0;
        $brokenChains = 0;
        $stockMismatches = 0;
        $samples = [];

        foreach ($productsWithHistory->chunk(500) as $chunk) {
            $stocks = Product::query()->whereIn('id', $chunk)->pluck('stock', 'id');

            $transactions = InventoryTransaction::query()
                ->whereIn('product_id', $chunk)
                ->orderBy('product_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'product_id', 'type', 'quantity_before', 'quantity_change', 'quantity_after', 'order_id']);

            $previousAfter = null;
            $previousProduct = null;

            foreach ($transactions as $transaction) {
                $productId = (int) $transaction->product_id;

                if ($previousProduct !== $productId) {
                    $previousProduct = $productId;
                    $previousAfter = null;
                }

                if ((int) $transaction->quantity_before + (int) $transaction->quantity_change !== (int) $transaction->quantity_after) {
                    $brokenRows++;

                    if (count($samples) < 5) {
                        $samples[] = "product {$productId} txn {$transaction->id}: before/change/after mismatch";
                    }

                    continue;
                }

                if ($previousAfter !== null && (int) $transaction->quantity_before !== $previousAfter) {
                    $brokenChains++;

                    if (count($samples) < 5) {
                        $samples[] = "product {$productId} txn {$transaction->id}: chain break at {$previousAfter} -> {$transaction->quantity_before}";
                    }
                }

                if ($transaction->type === 'sale' && $transaction->order_id === null) {
                    $brokenRows++;

                    if (count($samples) < 5) {
                        $samples[] = "product {$productId} txn {$transaction->id}: sale without order";
                    }
                }

                if (in_array($transaction->type, ['restock', 'adjustment'], true) && $transaction->order_id !== null) {
                    $brokenRows++;

                    if (count($samples) < 5) {
                        $samples[] = "product {$productId} txn {$transaction->id}: {$transaction->type} with order reference";
                    }
                }

                $previousAfter = (int) $transaction->quantity_after;
            }

            foreach ($transactions->groupBy('product_id') as $productId => $productTransactions) {
                $last = $productTransactions->last();
                $stock = (int) ($stocks[(int) $productId] ?? 0);

                if ((int) $last->quantity_after !== $stock) {
                    $stockMismatches++;

                    if (count($samples) < 5) {
                        $samples[] = "product {$productId}: final balance {$last->quantity_after} vs stock {$stock}";
                    }
                }
            }
        }

        $this->check('Inventory rows satisfy before + change = after', $brokenRows === 0, implode('; ', $samples));
        $this->check('Inventory rows chain correctly over time', $brokenChains === 0);
        $this->check(
            'Final inventory balance matches current product stock',
            $stockMismatches === 0,
            "{$stockMismatches} products drift from their history"
        );
    }

    private function checkOwnerVisibility(): void
    {
        $owners = User::query()->where('role', 'business_owner')->orderBy('id')->get();

        if ($owners->isEmpty()) {
            $this->warnings[] = 'No business owners found.';

            return;
        }

        $rows = [];

        foreach ($owners as $owner) {
            $productCount = Product::where('user_id', $owner->id)->count();

            $visibleOrders = Order::query()
                ->whereHas('items.product', fn ($query) => $query->where('user_id', $owner->id))
                ->count();

            $ordersWithoutTheirItems = Order::query()
                ->whereHas('items.product', fn ($query) => $query->where('user_id', $owner->id))
                ->whereDoesntHave('items.product', fn ($query) => $query->where('user_id', $owner->id))
                ->count();

            $this->check(
                "Owner {$owner->email} sees only orders containing their products",
                $ordersWithoutTheirItems === 0,
                "{$ordersWithoutTheirItems} inconsistent orders"
            );

            $sales = OrderIdem::query()
                ->whereHas('product', fn ($query) => $query->where('user_id', $owner->id))
                ->whereHas('order', fn ($query) => $query->whereIn('status', self::SALES_STATUSES))
                ->selectRaw('SUM(subtotal) as total, SUM(quantity) as quantity')
                ->first();

            $rows[] = [
                $owner->email,
                $productCount,
                $visibleOrders,
                number_format((float) ($sales?->total ?? 0), 2),
                (int) ($sales?->quantity ?? 0),
            ];

            if ($productCount > 0 && $visibleOrders === 0) {
                $this->warnings[] = "Owner {$owner->email} has {$productCount} products but no visible orders.";
            }
        }

        $this->table(['Owner', 'Products', 'Visible orders', 'Sales (their items)', 'Items sold'], $rows);

        $multiOwner = OrderIdem::query()
            ->selectRaw('order_idems.order_id')
            ->join('products', 'products.id', '=', 'order_idems.product_id')
            ->groupBy('order_idems.order_id')
            ->havingRaw('count(distinct products.user_id) > 1')
            ->get()
            ->count();

        $this->note("Orders containing items from multiple owners (shared carts): {$multiOwner}");
    }

    private function checkReportQueries(): void
    {
        $from = now()->subDays(200)->startOfDay();
        $to = now()->endOfDay();

        try {
            Order::query()
                ->whereIn('status', self::SALES_STATUSES)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as sale_date, SUM(total) as sales, COUNT(*) as orders_count')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('sale_date')
                ->get();

            Order::query()
                ->whereIn('status', self::SALES_STATUSES)
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as sale_month, SUM(total) as sales, COUNT(*) as orders_count")
                ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                ->orderBy('sale_month')
                ->get();

            OrderIdem::query()
                ->selectRaw('product_id, MAX(product_name) as product_name, SUM(quantity) as quantity_sold, SUM(subtotal) as revenue')
                ->whereHas('order', function ($query) use ($from, $to): void {
                    $query->whereIn('status', self::SALES_STATUSES)->whereBetween('created_at', [$from, $to]);
                })
                ->groupBy('product_id')
                ->orderByDesc('quantity_sold')
                ->take(10)
                ->get();

            Product::with('owner')->where('stock', '<=', 5)->orderBy('stock')->take(20)->get();

            InventoryTransaction::query()->latest()->take(10)->get();

            $this->check('Admin report queries execute', true);
        } catch (Throwable $exception) {
            $this->check('Admin report queries execute', false, $exception->getMessage());
        }

        try {
            $ownerId = optional(User::where('role', 'business_owner')->first())->id;

            if ($ownerId !== null) {
                OrderIdem::query()
                    ->whereHas('product', fn ($query) => $query->where('user_id', $ownerId))
                    ->whereHas('order', fn ($query) => $query->whereIn('status', self::SALES_STATUSES))
                    ->join('orders', 'order_idems.order_id', '=', 'orders.id')
                    ->selectRaw('DATE(orders.created_at) as sale_date, SUM(order_idems.subtotal) as sales, SUM(order_idems.quantity) as quantity_sold')
                    ->groupByRaw('DATE(orders.created_at)')
                    ->orderBy('sale_date')
                    ->get();

                $this->check('Owner report queries execute', true);
            } else {
                $this->note('No business owner available for the owner report smoke test.');
            }
        } catch (Throwable $exception) {
            $this->check('Owner report queries execute', false, $exception->getMessage());
        }
    }

    /**
     * @param  list<int>  $datasetProductIds
     */
    private function checkImages(array $datasetProductIds): void
    {
        $products = Product::query()->whereIn('id', $datasetProductIds)->get(['id', 'slug', 'image_path']);

        if ($products->isEmpty()) {
            $this->note('No dataset products found — import the dataset first.');

            return;
        }

        $disk = Storage::disk('public');
        $local = $products->filter(fn (Product $product): bool => $product->image_path !== null
            && ! Str::startsWith($product->image_path, ['http://', 'https://']));
        $remote = $products->filter(fn (Product $product): bool => $product->image_path !== null
            && Str::startsWith($product->image_path, ['http://', 'https://']));
        $missing = $products->filter(fn (Product $product): bool => $product->image_path === null);

        $this->stat('Dataset products with a local image', $local->count());
        $this->stat('Dataset products with a remote image URL', $remote->count());
        $this->stat('Dataset products without any image', $missing->count());

        $broken = 0;
        $samples = [];

        foreach ($local as $product) {
            $path = (string) $product->image_path;

            if (! $disk->exists($path) || $disk->size($path) === 0 || getimagesize($disk->path($path)) === false) {
                $broken++;

                if (count($samples) < 5) {
                    $samples[] = $path;
                }
            }
        }

        $this->check('Local dataset image files exist and are valid images', $broken === 0, implode(', ', $samples));

        if ($missing->isNotEmpty()) {
            $this->warnings[] = "{$missing->count()} dataset products have no image at all.";
        }

        if ($this->option('check-urls') && $remote->isNotEmpty()) {
            $unreachable = 0;

            foreach ($remote->take(25) as $product) {
                try {
                    $response = Http::timeout(8)->get((string) $product->image_path);

                    if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                        $unreachable++;
                    }
                } catch (Throwable) {
                    $unreachable++;
                }
            }

            $this->check(
                'Sampled remote image URLs are reachable (up to 25)',
                $unreachable === 0,
                "{$unreachable} unreachable URLs"
            );
        }
    }

    private function reportEdgeCoverage(): void
    {
        $zeroStock = Product::where('stock', 0)->count();
        $lowStock = Product::whereBetween('stock', [1, 5])->count();
        $ampleStock = Product::where('stock', '>', 5)->count();
        $inactive = Product::where('is_active', false)->count();
        $neverSold = Product::query()
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)->from('order_idems')->whereColumn('order_idems.product_id', 'products.id');
            })
            ->count();
        $customersWithoutOrders = User::where('role', 'customer')
            ->whereNotExists(function ($sub): void {
                $sub->selectRaw(1)->from('orders')->whereColumn('orders.user_id', 'users.id');
            })
            ->count();
        $storefrontReady = Product::where('is_active', true)->where('stock', '>', 0)->count();

        $this->stat('Products with zero stock', $zeroStock);
        $this->stat('Products with low stock (1-5)', $lowStock);
        $this->stat('Products with ample stock (6+)', $ampleStock);
        $this->stat('Inactive products', $inactive);
        $this->stat('Products never sold', $neverSold);
        $this->stat('Customers without orders', $customersWithoutOrders);
        $this->stat('Products visible in the storefront (active + in stock)', $storefrontReady);

        $statusRows = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => ['status' => $row->status, 'orders' => $row->total]);

        $this->table(
            ['Order status', 'Orders'],
            $statusRows->map(fn (array $row): array => [$row['status'], $row['orders']])->all()
        );

        foreach (self::VALID_ORDER_STATUSES as $status) {
            if (! $statusRows->contains('status', $status)) {
                $this->warnings[] = "No orders with status '{$status}' exist.";
            }
        }

        if ($zeroStock === 0) {
            $this->warnings[] = 'No zero-stock products in the dataset.';
        }

        if ($neverSold === 0) {
            $this->warnings[] = 'Every product has at least one sale.';
        }

        if ($customersWithoutOrders === 0) {
            $this->warnings[] = 'Every customer has at least one order.';
        }

        $recent = Order::where('created_at', '>=', now()->subDays(7))->count();
        $this->stat('Orders created in the last 7 days', $recent);
    }

    private function checkPreservedAccounts(): void
    {
        foreach ([
            'business@gmail.com' => 'business_owner',
            'admin@gmail.com' => 'admin',
            'c@gmail.com' => 'customer',
        ] as $email => $role) {
            $user = User::where('email', $email)->first();

            $this->check(
                "Pre-existing account preserved: {$email}",
                $user !== null && $user->role === $role,
                $user === null ? 'missing' : "role is now {$user->role}"
            );
        }
    }

    /**
     * Order numbers the seeder generates deterministically for indexes 1..300.
     *
     * @return list<string>
     */
    private function generatedOrderNumbers(): array
    {
        $numbers = [];

        for ($index = 1; $index <= 300; $index++) {
            $numbers[] = 'ORD-'.strtoupper(substr(md5(self::DATASET_SEED.'-order-'.$index), 0, 12));
        }

        return $numbers;
    }

    private function check(string $label, bool $passed, string $detail = ''): void
    {
        $suffix = $detail !== '' ? " — {$detail}" : '';

        if ($passed) {
            $this->line("  <fg=green>[PASS]</> {$label}{$suffix}");

            return;
        }

        $this->line("  <fg=red>[FAIL]</> {$label}{$suffix}");
        $this->failures[] = $label.$suffix;
    }

    private function note(string $label): void
    {
        $this->line("  <fg=blue>[INFO]</> {$label}");
    }

    private function stat(string $label, int|string $value): void
    {
        $this->line(sprintf('  <fg=cyan>%s</>: %s', $label, $value));
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->info("== {$title} ==");
    }

    private function renderVerdict(): int
    {
        $this->newLine();

        if ($this->warnings !== []) {
            $this->warn('Warnings ('.count($this->warnings).'):');

            foreach ($this->warnings as $warning) {
                $this->line("  - {$warning}");
            }
        }

        if ($this->failures !== []) {
            $this->newLine();
            $this->error('Validation failed with '.count($this->failures).' problem(s).');

            return self::FAILURE;
        }

        $this->info('All hard checks passed. Idempotency tip: run `shopsync:seed-demo --dry-run` twice — the second run should report everything as unchanged.');

        return self::SUCCESS;
    }
}
