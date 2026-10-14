<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Import the ShopSync demo dataset.
 *
 * Only touches records that belong to the reserved dataset namespace
 * (emails under shopsync.test, DUMMYJSON-* orders, and products whose
 * slug exists in the DummyJSON manifest). Non-dataset records are never
 * modified or deleted.
 */
class SeedDemoDataset extends Command
{
    private const IMAGE_DIR = 'products/dataset';

    private const DATASET_SEED = 'shopsync-demo';

    private const DEMO_PASSWORD = 'password';

    private const MAX_ORDERS = 300;

    /**
     * Business owner personas mapped to product category groups.
     *
     * @var array<string, array{name: string, group: string}>
     */
    private const OWNER_PERSONAS = [
        'dataset.owner@shopsync.test' => ['name' => 'TechNova Electronics', 'group' => 'electronics'],
        'owner.fashion@shopsync.test' => ['name' => 'Dhaka Style House', 'group' => 'fashion'],
        'owner.home@shopsync.test' => ['name' => 'GreenHome Living', 'group' => 'home'],
        'owner.beauty@shopsync.test' => ['name' => 'GlowCare Beauty', 'group' => 'beauty'],
    ];

    /**
     * Top level (parent) categories, one per product group.
     *
     * @var array<string, array{slug: string, name: string, description: string}>
     */
    private const GROUP_PARENTS = [
        'electronics' => [
            'slug' => 'tech-electronics',
            'name' => 'Tech & Electronics',
            'description' => 'Phones, computers, tablets, accessories and rides.',
        ],
        'fashion' => [
            'slug' => 'fashion-apparel',
            'name' => 'Fashion & Apparel',
            'description' => 'Clothing, footwear, watches, bags and jewellery for everyone.',
        ],
        'home' => [
            'slug' => 'home-living',
            'name' => 'Home & Living',
            'description' => 'Furniture, decor, kitchen essentials and daily groceries.',
        ],
        'beauty' => [
            'slug' => 'beauty-wellness',
            'name' => 'Beauty & Wellness',
            'description' => 'Skincare, fragrance, makeup and sports accessories.',
        ],
    ];

    /**
     * DummyJSON child categories grouped under the parent categories above.
     *
     * @var array<string, list<string>>
     */
    private const GROUP_CATEGORY_SLUGS = [
        'electronics' => ['smartphones', 'laptops', 'tablets', 'mobile-accessories', 'motorcycle', 'vehicle'],
        'fashion' => ['mens-shirts', 'mens-shoes', 'mens-watches', 'tops', 'sunglasses', 'womens-bags', 'womens-dresses', 'womens-jewellery', 'womens-shoes', 'womens-watches'],
        'home' => ['furniture', 'home-decoration', 'kitchen-accessories', 'groceries'],
        'beauty' => ['beauty', 'fragrances', 'skin-care', 'sports-accessories'],
    ];

    /**
     * Documented demo customers. The last two intentionally never order.
     *
     * @var list<string>
     */
    private const CUSTOMER_NAMES = [
        'Rahim Uddin', 'Nusrat Jahan', 'Tanvir Ahmed', 'Sadia Islam',
        'Mehedi Hasan', 'Farhana Akter', 'Arif Chowdhury', 'Sumaiya Rahman',
        'Imran Hossain', 'Priya Das', 'Shakib Al Mahmud', 'Rumana Haque',
        'Nadia Sultana', 'Fahim Rahman', 'Tania Rahman', 'Jubayer Alam',
        'Misty Khatun', 'Sabbir Hossain', 'Lamia Karim', 'Rafiul Islam',
        'Anika Tabassum', 'Nazmul Hassan', 'Rezaul Karim', 'Shirin Akter',
    ];

    /**
     * Shipping address areas used by the deterministic address generator.
     *
     * @var array<string, list<string>>
     */
    private const ADDRESS_AREAS = [
        'Dhaka' => ['Dhanmondi', 'Gulshan 2', 'Banani', 'Mirpur DOHS', 'Uttara Sector 7', 'Bashundhara R/A', 'Mohammadpur', 'Motijheel'],
        'Chattogram' => ['Khulshi', 'Agrabad', 'Nasirabad'],
        'Sylhet' => ['Zindabazar', 'Shibganj'],
        'Khulna' => ['Sonadanga', 'Khalishpur'],
    ];

    /**
     * @var list<string>
     */
    private const NEGATIVE_ADJUSTMENT_REASONS = [
        'Damaged items removed during warehouse audit',
        'Stock count correction after physical inventory',
        'Items expired and removed from sellable stock',
        'Display unit items written off',
        'Supplier shortage adjustment on received shipment',
    ];

    /**
     * @var list<string>
     */
    private const POSITIVE_ADJUSTMENT_REASONS = [
        'Found extra units during stock recount',
        'Customer return returned to sellable stock',
        'Supplier shipped bonus units on the last delivery',
    ];

    protected $signature = 'shopsync:seed-demo
        {--images-only : Only download product images into storage, without touching the database}
        {--skip-images : Import data only and keep whatever image_path values already exist}
        {--dry-run : Run the whole import inside a transaction and roll it back afterwards}
        {--orders=36 : Number of fresh demo orders to generate (existing legacy orders are kept)}
        {--force : Run without the confirmation prompt}';

    protected $description = 'Import the ShopSync demo dataset (accounts, categories, products, images, orders, inventory history) without touching non-dataset records';

    public function handle(): int
    {
        if (! in_array(app()->environment(), ['local', 'development', 'testing'], true)) {
            $this->error('This command may only run in local, development or testing environments.');

            return self::FAILURE;
        }

        $datasetDir = storage_path('app/datasets');

        foreach (['products.json', 'categories.json'] as $file) {
            if (! is_file("{$datasetDir}/{$file}")) {
                $this->error("Dataset manifest missing: {$datasetDir}/{$file}");

                return self::FAILURE;
            }
        }

        if ($this->option('images-only')) {
            return $this->handleImagesOnly();
        }

        $orderCount = min(self::MAX_ORDERS, max(0, (int) $this->option('orders')));

        if (! $this->option('force') && ! $this->confirm(
            "Import the demo dataset (products, categories, accounts and {$orderCount} new orders)? Existing non-dataset records are never touched.",
            true
        )) {
            $this->info('Aborted, nothing was changed.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            DB::beginTransaction();
            $this->warn('Dry run: every database write will be rolled back at the end.');
        }

        try {
            $stats = DB::transaction(function () use ($orderCount): array {
                return $this->import($orderCount);
            });
        } catch (Throwable $exception) {
            if ($dryRun) {
                DB::rollBack();
            }

            throw $exception;
        }

        if ($dryRun) {
            DB::rollBack();
        }

        $this->renderSummary($stats);

        if ($dryRun) {
            $this->warn('Dry run complete — the database was rolled back and nothing was persisted.');
        } else {
            $this->newLine();
            $this->info('Demo dataset imported successfully.');
            $this->line('Demo credentials: see storage/app/datasets/README.md');
            $this->line('Run: php artisan shopsync:validate-dataset');
        }

        return self::SUCCESS;
    }

    /**
     * Download every manifest thumbnail into the public disk without any DB access.
     */
    private function handleImagesOnly(): int
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::IMAGE_DIR)) {
            $disk->makeDirectory(self::IMAGE_DIR);
        }

        $stats = ['images_downloaded' => 0, 'images_reused' => 0, 'images_failed' => 0];
        $pending = [];
        $urls = [];

        foreach ($this->manifestProducts() as $entry) {
            $slug = $entry['slug'];
            $path = self::IMAGE_DIR."/{$slug}.webp";

            if ($this->isValidLocalImage($disk, $path)) {
                $stats['images_reused']++;

                continue;
            }

            if ($entry['thumbnail'] === '') {
                $stats['images_failed']++;
                $this->line("  No thumbnail URL in manifest for <fg=yellow>{$slug}</>.");

                continue;
            }

            $pending[$slug] = $path;
            $urls[$slug] = $entry['thumbnail'];
        }

        $this->poolDownload($pending, $urls, $stats);

        $this->newLine();
        $this->info("Images ready: {$stats['images_reused']} reused, {$stats['images_downloaded']} downloaded, {$stats['images_failed']} failed.");

        return $stats['images_failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Run the full dataset import inside the caller's transaction.
     *
     * @return array<string, int>
     */
    private function import(int $orderCount): array
    {
        $stats = [
            'accounts_created' => 0,
            'accounts_updated' => 0,
            'parent_categories_created' => 0,
            'categories_linked' => 0,
            'fallback_categories_created' => 0,
            'products_created' => 0,
            'products_updated' => 0,
            'products_zero_stock' => 0,
            'products_low_stock' => 0,
            'products_inactive' => 0,
            'images_downloaded' => 0,
            'images_reused' => 0,
            'images_failed' => 0,
            'images_linked_to_local_file' => 0,
            'images_falling_back_to_url' => 0,
            'images_without_any_source' => 0,
            'legacy_orders_backdated' => 0,
            'orders_created' => 0,
            'orders_updated' => 0,
            'order_items_created' => 0,
            'inventory_rows_created' => 0,
            'inventory_ledgers_skipped' => 0,
            'inventory_ledgers_reconciled' => 0,
        ];

        $accounts = $this->importAccounts($stats);
        $categoryResolver = $this->importCategories($stats);

        $productsBySlug = $this->importProducts($accounts['owners'], $categoryResolver, $stats);

        if ($this->option('skip-images')) {
            $this->warn('Skipping image downloads (--skip-images).');
        } else {
            $this->applyImages($productsBySlug, $stats, ! $this->option('dry-run'));
        }

        $buyers = $this->collectBuyers($accounts['customers']);

        $this->backdateLegacyOrders($stats);
        $this->generateOrders($buyers, $productsBySlug, $orderCount, $stats);
        $this->buildInventoryLedgers($productsBySlug, $stats);

        return $stats;
    }

    /**
     * Create or refresh the admin, business owner and documented customer accounts.
     *
     * @param  array<string, int>  $stats
     * @return array{admin: User, owners: array<string, User>, customers: list<User>}
     */
    private function importAccounts(array &$stats): array
    {
        $password = Hash::make(self::DEMO_PASSWORD);
        $adminCreatedAt = now()->subDays(280);

        $admin = $this->upsertUser('admin@shopsync.test', 'Dataset Admin', 'admin', $password, $adminCreatedAt, $stats);

        $owners = [];

        foreach (self::OWNER_PERSONAS as $email => $persona) {
            $owners[$persona['group']] = $this->upsertUser(
                $email,
                $persona['name'],
                'business_owner',
                $password,
                $adminCreatedAt->copy()->addDays(5),
                $stats
            );
        }

        $customers = [];

        foreach (self::CUSTOMER_NAMES as $index => $name) {
            $email = sprintf('customer%02d@shopsync.test', $index + 1);
            $customers[] = $this->upsertUser(
                $email,
                $name,
                'customer',
                $password,
                now()->subDays(30 + $this->pickInt("customer-created-{$index}", 210)),
                $stats
            );
        }

        return ['admin' => $admin, 'owners' => $owners, 'customers' => $customers];
    }

    /**
     * Create or refresh a single dataset account.
     *
     * @param  array<string, int>  $stats
     */
    private function upsertUser(string $email, string $name, string $role, string $password, Carbon $createdAt, array &$stats): User
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->name = $name;
        $user->role = $role;

        // Re-hash only when the documented password drifted, so reruns stay write-clean.
        if (! $user->exists || ! Hash::check(self::DEMO_PASSWORD, (string) $user->password)) {
            $user->password = $password;
        }

        $user->email_verified_at = $user->email_verified_at ?? $createdAt;

        if (! $user->exists) {
            $user->created_at = $createdAt;
        }

        $wasRecentlyCreated = ! $user->exists;
        $changed = $user->isDirty();

        if ($changed) {
            $user->save();
        }

        if ($wasRecentlyCreated) {
            $stats['accounts_created']++;
        } elseif ($changed) {
            $stats['accounts_updated']++;
        }

        return $user;
    }

    /**
     * Create the parent categories and attach the DummyJSON child categories to them.
     *
     * @param  array<string, int>  $stats
     * @return \Closure(string): Category
     */
    private function importCategories(array &$stats): \Closure
    {
        $parents = [];

        foreach (self::GROUP_PARENTS as $group => $parent) {
            $category = Category::firstOrNew(['slug' => $parent['slug']]);

            $category->name = $parent['name'];
            $category->description = $parent['description'];
            $category->parent_id = null;
            $category->is_active = 1;

            if (! $category->exists) {
                $category->created_at = now()->startOfDay()->subDays(250);
                $stats['parent_categories_created']++;
            }

            if ($category->isDirty()) {
                $category->save();
            }

            $parents[$group] = $category;
        }

        $children = [];
        $resolved = [];

        foreach (self::GROUP_CATEGORY_SLUGS as $group => $slugs) {
            foreach ($slugs as $slug) {
                $category = Category::firstOrNew(['slug' => $slug]);

                if (! $category->exists) {
                    // Should not normally happen: the manifest import already created these.
                    $category->name = Str::headline($slug);
                    $category->created_at = now()->startOfDay()->subDays(240);
                }

                $category->parent_id = $parents[$group]->id;
                $category->is_active = 1;

                if ($category->isDirty()) {
                    $category->save();
                    $stats['categories_linked']++;
                }

                $children[$slug] = $category;
                $resolved[$slug] = $category;
            }
        }

        return function (string $slug) use (&$resolved, &$stats): Category {
            if (isset($resolved[$slug])) {
                return $resolved[$slug];
            }

            $category = Category::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => Str::headline($slug),
                    'description' => 'Imported from the demo dataset manifest.',
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );

            $resolved[$slug] = $category;
            $stats['fallback_categories_created']++;

            return $category;
        };
    }

    /**
     * Create or refresh every manifest product and hand out owners, edge cases and dates.
     *
     * @param  array<string, User>  $owners
     * @param  array<string, int>  $stats
     * @return Collection<string, Product>
     */
    private function importProducts(array $owners, \Closure $resolveCategory, array &$stats): Collection
    {
        $entries = $this->manifestProducts();
        $products = collect();
        $seenSlugs = [];

        foreach ($entries as $entry) {
            $slug = $entry['slug'];

            // Handle the two duplicate manifest titles deterministically.
            $seenSlugs[$slug] = ($seenSlugs[$slug] ?? 0) + 1;

            if ($seenSlugs[$slug] > 1) {
                $slug .= '-'.$seenSlugs[$slug];
            }

            $group = $this->groupForCategory($entry['category']);
            $owner = $owners[$group] ?? reset($owners);

            $product = Product::firstOrNew(['slug' => $slug]);
            $wasRecentlyCreated = ! $product->exists;

            [$stock, $isActive] = $this->stockPlan($slug, $entry['stock']);

            $product->user_id = $owner->id;
            $product->category_id = $resolveCategory($entry['category'])->id;
            $product->name = $entry['title'];
            $product->description = $this->buildDescription($entry['raw']);
            $product->price = number_format((float) $entry['price'], 2, '.', '');
            $product->stock = $stock;
            $product->is_active = (int) $isActive;

            // Seed the fallback image (remote URL) only where no image exists
            // yet; applyImages() replaces it with the local copy whenever the
            // download succeeded. Existing image_path values are never reset,
            // so reruns and --skip-images stay write-clean.
            if ($wasRecentlyCreated || $product->getRawOriginal('image_path') === null) {
                $product->image_path = $entry['thumbnail'] !== '' ? $entry['thumbnail'] : null;
            }

            $product->created_at = $this->productCreatedAt($slug);

            $changed = $product->isDirty();

            if ($changed) {
                $product->save();
            }

            if ($wasRecentlyCreated) {
                $stats['products_created']++;
            } elseif ($changed) {
                $stats['products_updated']++;
            }

            if ($stock === 0) {
                $stats['products_zero_stock']++;
            } elseif ($stock <= 5) {
                $stats['products_low_stock']++;
            }

            if (! $isActive) {
                $stats['products_inactive']++;
            }

            $products->put($slug, $product);
        }

        return $products;
    }

    /**
     * Decode the product manifest and resolve deterministic unique slugs.
     *
     * @return list<array{slug: string, title: string, category: string, price: float, stock: int, thumbnail: string, raw: array<string, mixed>}>
     */
    private function manifestProducts(): array
    {
        $contents = file_get_contents(storage_path('app/datasets/products.json'));

        if ($contents === false) {
            throw new \RuntimeException('Unable to read storage/app/datasets/products.json');
        }

        $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $entries = [];

        foreach ($decoded['products'] ?? [] as $raw) {
            $title = trim((string) ($raw['title'] ?? ''));
            $slug = Str::slug($title);

            if ($title === '' || $slug === '') {
                continue;
            }

            $entries[] = [
                'slug' => $slug,
                'title' => $title,
                'category' => (string) ($raw['category'] ?? ''),
                'price' => (float) ($raw['price'] ?? 0),
                'stock' => max(0, (int) ($raw['stock'] ?? 0)),
                'thumbnail' => (string) ($raw['thumbnail'] ?? ''),
                'raw' => $raw,
            ];
        }

        if ($entries === []) {
            throw new \RuntimeException('products.json contains no products.');
        }

        return $entries;
    }

    /**
     * Deterministic stock and activation plan, including the edge cases.
     *
     * @return array{int, bool}
     */
    private function stockPlan(string $slug, int $manifestStock): array
    {
        $roll = $this->unit("stock-plan-{$slug}");

        if ($roll < 0.04) {
            return [0, true];
        }

        if ($roll < 0.12) {
            return [1 + $this->pickInt("stock-low-{$slug}", 5), true];
        }

        $stock = $manifestStock;

        if ($stock === 0) {
            $stock = 20 + $this->pickInt("stock-bump-{$slug}", 100);
        }

        return [min(150, $stock), $this->unit("active-plan-{$slug}") >= 0.03];
    }

    /**
     * Merge the manifest description with brand, warranty and shipping metadata.
     *
     * @param  array<string, mixed>  $raw
     */
    private function buildDescription(array $raw): ?string
    {
        $parts = [trim((string) ($raw['description'] ?? ''))];
        $meta = [];

        foreach ([
            'brand' => 'Brand',
            'sku' => 'SKU',
            'warrantyInformation' => 'Warranty',
            'shippingInformation' => 'Shipping',
            'returnPolicy' => 'Returns',
        ] as $key => $label) {
            $value = trim((string) ($raw[$key] ?? ''));

            if ($value !== '' && $value !== '-') {
                $meta[] = "{$label}: {$value}";
            }
        }

        if ($meta !== []) {
            $parts[] = implode(' • ', $meta);
        }

        $description = trim(implode("\n\n", array_filter($parts)));

        return $description === '' ? null : $description;
    }

    /**
     * Backdate every product 180-260 days so it predates all order history.
     */
    private function productCreatedAt(string $slug): Carbon
    {
        return now()->startOfDay()->subDays(180 + $this->pickInt("product-created-{$slug}", 80))
            ->setTimeFromTimeString(sprintf('%02d:%02d:00', $this->pickInt("product-hour-{$slug}", 10) + 8, $this->pickInt("product-minute-{$slug}", 60)));
    }

    /**
     * Download manifest thumbnails (pooled) and point every dataset product at
     * the local copy, falling back to the remote URL or null.
     *
     * @param  Collection<string, Product>  $productsBySlug
     * @param  array<string, int>  $stats
     */
    private function applyImages(Collection $productsBySlug, array &$stats, bool $download): void
    {
        $disk = Storage::disk('public');

        if ($download) {
            if (! $disk->exists(self::IMAGE_DIR)) {
                $disk->makeDirectory(self::IMAGE_DIR);
            }

            $pending = [];
            $urls = [];

            foreach ($productsBySlug as $slug => $product) {
                $path = self::IMAGE_DIR."/{$slug}.webp";
                $url = self::thumbnailFor($product);

                if ($this->isValidLocalImage($disk, $path)) {
                    $stats['images_reused']++;

                    continue;
                }

                if ($url === '') {
                    continue;
                }

                $pending[$slug] = $path;
                $urls[$slug] = $url;
            }

            $this->poolDownload($pending, $urls, $stats);
        }

        foreach ($productsBySlug as $slug => $product) {
            $path = self::IMAGE_DIR."/{$slug}.webp";

            $imagePath = $this->isValidLocalImage($disk, $path)
                ? $path
                : (self::thumbnailFor($product) ?: null);

            if ($imagePath !== null && ! Str::startsWith($imagePath, ['http://', 'https://'])) {
                $stats['images_linked_to_local_file']++;
            } elseif ($imagePath !== null) {
                $stats['images_falling_back_to_url']++;
            } else {
                $stats['images_without_any_source']++;
            }

            if ($product->image_path !== $imagePath) {
                $product->image_path = $imagePath;
                $product->save();
            }
        }
    }

    /**
     * Download the given slug => path map from slug => url map in pooled
     * batches, validating that each body really is an image.
     *
     * @param  array<string, string>  $pending
     * @param  array<string, string>  $urls
     * @param  array<string, int>  $stats
     */
    private function poolDownload(array $pending, array $urls, array &$stats): void
    {
        $disk = Storage::disk('public');

        foreach (array_chunk($pending, 16, true) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk, $urls): void {
                foreach ($chunk as $slug => $path) {
                    $pool->as($slug)->timeout(20)->get($urls[$slug]);
                }
            });

            foreach ($chunk as $slug => $path) {
                $response = $responses[$slug] ?? null;

                if ($this->isValidImageResponse($response)) {
                    $body = $response->body();

                    if ($body !== '' && getimagesizefromstring($body) !== false) {
                        $disk->put($path, $body);
                        $stats['images_downloaded']++;

                        continue;
                    }
                }

                $stats['images_failed']++;
                $this->line("  Image download failed for <fg=yellow>{$slug}</>; keeping fallback.");
            }
        }
    }

    /**
     * Resolve the remote thumbnail for a product (manifest URL or existing
     * external URL already stored in image_path).
     */
    private static function thumbnailFor(Product $product): string
    {
        $imagePath = (string) ($product->getRawOriginal('image_path') ?? '');

        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        return '';
    }

    /**
     * @param  mixed  $response
     */
    private function isValidImageResponse($response): bool
    {
        return $response instanceof Response
            && $response->successful()
            && str_starts_with((string) $response->header('Content-Type'), 'image/');
    }

    private function isValidLocalImage(FilesystemAdapter $disk, string $path): bool
    {
        if (! $disk->exists($path) || $disk->size($path) === 0) {
            return false;
        }

        return getimagesize($disk->path($path)) !== false;
    }

    /**
     * Documented customers (minus the two that never order) plus a
     * deterministic sample of the legacy dataset customers.
     *
     * @param  list<User>  $documentedCustomers
     * @return array{documented: list<User>, legacy: list<User>}
     */
    private function collectBuyers(array $documentedCustomers): array
    {
        // The last two documented customers intentionally never order.
        $documented = array_slice($documentedCustomers, 0, count($documentedCustomers) - 2);

        $legacy = User::query()
            ->where('email', 'like', 'dataset.user.%@shopsync.test')
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user, int $index): bool => $index % 13 === 0)
            ->values()
            ->all();

        return ['documented' => $documented, 'legacy' => $legacy];
    }

    /**
     * Backdate the legacy DUMMYJSON-* orders across the last ~150 days and
     * give them realistic statuses, payment states, phones and addresses.
     *
     * @param  array<string, int>  $stats
     */
    private function backdateLegacyOrders(array &$stats): void
    {
        $orders = Order::query()
            ->with(['user:id,name', 'items:id,order_id,subtotal'])
            ->where('order_number', 'like', 'DUMMYJSON-%')
            ->get();

        foreach ($orders as $order) {
            $user = $order->user;

            if ($user === null) {
                continue;
            }

            $key = $order->order_number;
            $ageDays = 2 + $this->pickInt("legacy-age-{$key}", 150);
            $createdAt = now()->startOfDay()->subDays($ageDays)->subMinutes($this->pickInt("legacy-minute-{$key}", 1440));
            [$status, $paymentStatus] = $this->statusForAge("legacy-{$key}", $ageDays);

            $order->status = $status;
            $order->payment_status = $paymentStatus;
            $order->payment_method = 'cod';
            $order->customer_name = $user->name;
            $order->phone = $this->phoneNumber("legacy-phone-{$key}");
            $order->shipping_address = $this->shippingAddress("legacy-addr-{$key}");

            // The legacy importer double-counted duplicate cart lines in the
            // order total; normalize it to the sum of the stored item subtotals.
            $order->total = number_format((float) $order->items->sum('subtotal'), 2, '.', '');

            $order->created_at = $createdAt;
            $order->updated_at = $createdAt;

            if ($order->isDirty()) {
                $order->saveQuietly();
                $stats['legacy_orders_backdated']++;
            }
        }
    }

    /**
     * Generate fresh demo orders with deterministic order numbers, items and dates.
     *
     * @param  array{documented: list<User>, legacy: list<User>}  $buyers
     * @param  Collection<string, Product>  $productsBySlug
     * @param  array<string, int>  $stats
     */
    private function generateOrders(array $buyers, Collection $productsBySlug, int $orderCount, array &$stats): void
    {
        if ($orderCount === 0) {
            return;
        }

        $sellable = Product::query()
            ->whereIn('id', $productsBySlug->pluck('id'))
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('id')
            ->get()
            ->values();

        if ($sellable->isEmpty()) {
            $this->warn('No sellable dataset products found, skipping order generation.');

            return;
        }

        $pool = $sellable->all();
        $poolSize = count($pool);
        $documented = $buyers['documented'];
        $legacy = $buyers['legacy'];

        if ($documented === []) {
            $this->warn('No documented demo customers found, skipping order generation.');

            return;
        }

        mt_srand(20261014);

        for ($index = 1; $index <= $orderCount; $index++) {
            $customer = $index <= count($documented)
                ? $documented[$index - 1]
                : ($legacy !== [] ? $legacy[mt_rand(0, count($legacy) - 1)] : $documented[$index % count($documented)]);

            $ageDays = mt_rand(0, 149);
            $createdAt = now()->startOfDay()->subDays($ageDays)->subMinutes(mt_rand(0, 1439));

            $roll = mt_rand(1, 100);
            $itemCount = match (true) {
                $roll <= 30 => 1,
                $roll <= 60 => 2,
                $roll <= 80 => 3,
                $roll <= 92 => 4,
                default => 5,
            };

            $cursor = mt_rand(0, $poolSize - 1);
            $picked = [];

            while (count($picked) < $itemCount) {
                $candidate = $pool[$cursor % $poolSize];
                $cursor++;
                $picked[$candidate->id] = ['product' => $candidate, 'quantity' => mt_rand(1, 3)];
            }

            $total = 0.0;

            foreach ($picked as $line) {
                $total += round((float) $line['product']->price * $line['quantity'], 2);
            }

            $orderNumber = 'ORD-'.strtoupper(substr(md5(self::DATASET_SEED.'-order-'.$index), 0, 12));
            [$status, $paymentStatus] = $this->statusForAge("order-{$index}", $ageDays);

            $order = Order::firstOrNew(['order_number' => $orderNumber]);
            $wasRecentlyCreated = ! $order->exists;

            $order->user_id = $customer->id;
            $order->status = $status;
            $order->payment_method = 'cod';
            $order->payment_status = $paymentStatus;
            $order->customer_name = $customer->name;
            $order->phone = $this->phoneNumber("order-phone-{$index}");
            $order->shipping_address = $this->shippingAddress("order-addr-{$index}");
            $order->total = number_format($total, 2, '.', '');
            $order->created_at = $createdAt;
            $order->updated_at = $createdAt;

            if ($order->isDirty()) {
                $order->saveQuietly();

                if ($wasRecentlyCreated) {
                    $stats['orders_created']++;
                } else {
                    $stats['orders_updated']++;
                }
            }

            foreach ($picked as $line) {
                $product = $line['product'];
                $quantity = $line['quantity'];

                $itemIdem = OrderIdem::firstOrCreate(
                    ['order_id' => $order->id, 'product_id' => $product->id],
                    [
                        'product_name' => $product->name,
                        'unit_price' => $product->price,
                        'quantity' => $quantity,
                        'subtotal' => round((float) $product->price * $quantity, 2),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]
                );

                if ($itemIdem->wasRecentlyCreated) {
                    $stats['order_items_created']++;
                }
            }
        }
    }

    /**
     * Build a fully chain-consistent inventory history for every dataset
     * product that has no transactions yet.
     *
     * The ledger always ends exactly at the product's current stock:
     * initial restock, then chronological sales and adjustments.
     *
     * @param  Collection<string, Product>  $productsBySlug
     * @param  array<string, int>  $stats
     */
    private function buildInventoryLedgers(Collection $productsBySlug, array &$stats): void
    {
        $products = $productsBySlug->values();
        $productIds = $products->pluck('id')->all();

        $withHistory = InventoryTransaction::query()
            ->whereIn('product_id', $productIds)
            ->distinct()
            ->pluck('product_id')
            ->all();

        $itemsByProduct = OrderIdem::query()
            ->join('orders', 'orders.id', '=', 'order_idems.order_id')
            ->whereIn('order_idems.product_id', $productIds)
            ->orderBy('orders.created_at')
            ->orderBy('order_idems.order_id')
            ->orderBy('order_idems.id')
            ->get([
                'order_idems.id',
                'order_idems.order_id',
                'order_idems.product_id',
                'order_idems.quantity',
                'orders.order_number',
                'orders.created_at as order_created_at',
            ])
            ->groupBy('product_id');

        $rows = [];

        foreach ($products as $product) {
            if (in_array($product->id, $withHistory, true)) {
                $stats['inventory_ledgers_skipped']++;
                $this->reconcileExistingLedger($product, $stats);

                continue;
            }

            $events = [];

            foreach ($itemsByProduct->get($product->id, collect()) as $item) {
                $events[] = [
                    'timestamp' => Carbon::parse($item->order_created_at)->getTimestamp(),
                    'type' => 'sale',
                    'change' => -1 * (int) $item->quantity,
                    'reason' => 'Stock reduced for order '.$item->order_number,
                    'order_id' => (int) $item->order_id,
                ];
            }

            if ($this->unit("adjustment-{$product->slug}") < 0.1) {
                $adjustmentDate = Carbon::instance($product->created_at)
                    ->copy()
                    ->addDays(7 + $this->pickInt("adjustment-date-{$product->slug}", 100));

                if ($adjustmentDate->greaterThan(now()->subDay())) {
                    $adjustmentDate = now()->subDays(2);
                }

                $negative = $this->unit("adjustment-kind-{$product->slug}") < 0.8;

                $events[] = [
                    'timestamp' => $adjustmentDate->getTimestamp(),
                    'type' => 'adjustment',
                    'change' => $negative
                        ? -(1 + $this->pickInt("adjustment-qty-{$product->slug}", 4))
                        : 1 + $this->pickInt("adjustment-found-{$product->slug}", 3),
                    'reason' => $negative
                        ? self::NEGATIVE_ADJUSTMENT_REASONS[$this->pickInt("adjustment-reason-{$product->slug}", count(self::NEGATIVE_ADJUSTMENT_REASONS))]
                        : self::POSITIVE_ADJUSTMENT_REASONS[$this->pickInt("adjustment-reason-{$product->slug}", count(self::POSITIVE_ADJUSTMENT_REASONS))],
                    'order_id' => null,
                ];
            }

            $netChange = array_sum(array_column($events, 'change'));
            $initialStock = (int) $product->stock - $netChange;

            if ($events === [] && $initialStock === 0) {
                continue;
            }

            usort($events, fn (array $a, array $b): int => [$a['timestamp'], $a['order_id']] <=> [$b['timestamp'], $b['order_id']]);

            $createdAt = Carbon::instance($product->created_at);

            $rows[] = [
                'product_id' => $product->id,
                'user_id' => $product->user_id,
                'order_id' => null,
                'type' => 'restock',
                'quantity_before' => 0,
                'quantity_change' => $initialStock,
                'quantity_after' => $initialStock,
                'reason' => 'Initial stock received from supplier',
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $createdAt->format('Y-m-d H:i:s'),
            ];

            $running = $initialStock;

            foreach ($events as $event) {
                $before = $running;
                $running += $event['change'];
                $date = Carbon::createFromTimestamp($event['timestamp']);

                $rows[] = [
                    'product_id' => $product->id,
                    'user_id' => $product->user_id,
                    'order_id' => $event['order_id'],
                    'type' => $event['type'],
                    'quantity_before' => $before,
                    'quantity_change' => $event['change'],
                    'quantity_after' => $running,
                    'reason' => $event['reason'],
                    'created_at' => $date->format('Y-m-d H:i:s'),
                    'updated_at' => $date->format('Y-m-d H:i:s'),
                ];
            }

            if (count($rows) >= 500) {
                InventoryTransaction::insert($rows);
                $stats['inventory_rows_created'] += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            InventoryTransaction::insert($rows);
            $stats['inventory_rows_created'] += count($rows);
        }
    }

    /**
     * Append a single adjustment row when a dataset product's pre-existing
     * inventory history does not reach the product's current stock.
     *
     * Existing rows are never modified; the appended row makes the chain end
     * exactly at the current stock, and reruns skip it once balances agree.
     *
     * @param  array<string, int>  $stats
     */
    private function reconcileExistingLedger(Product $product, array &$stats): void
    {
        $last = InventoryTransaction::query()
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($last === null) {
            return;
        }

        $balance = (int) $last->quantity_after;
        $drift = ((int) $product->stock) - $balance;

        if ($drift === 0) {
            return;
        }

        $lastDate = Carbon::parse($last->created_at);
        $date = $lastDate->copy()->addHour();

        if ($date->greaterThan(now())) {
            $date = $lastDate;
        }

        $timestamp = $date->format('Y-m-d H:i:s');

        InventoryTransaction::insert([
            [
                'product_id' => $product->id,
                'user_id' => $product->user_id,
                'order_id' => null,
                'type' => 'adjustment',
                'quantity_before' => $balance,
                'quantity_change' => $drift,
                'quantity_after' => $balance + $drift,
                'reason' => 'Dataset reconciliation: stock matched to '.$product->stock.' units',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);

        $stats['inventory_rows_created']++;
        $stats['inventory_ledgers_reconciled']++;
    }

    /**
     * Deterministic status and payment state for an order of a given age.
     *
     * @return array{string, string}
     */
    private function statusForAge(string $key, int $ageDays): array
    {
        if ($this->unit("cancelled-{$key}") < 0.09) {
            return ['cancelled', 'unpaid'];
        }

        if ($ageDays > 60) {
            return ['delivered', 'paid'];
        }

        if ($ageDays > 25) {
            return $this->unit("shipped-{$key}") < 0.7 ? ['shipped', 'unpaid'] : ['processing', 'unpaid'];
        }

        if ($ageDays > 8) {
            return $this->unit("processing-{$key}") < 0.75 ? ['processing', 'unpaid'] : ['shipped', 'unpaid'];
        }

        return $this->unit("pending-{$key}") < 0.75 ? ['pending', 'unpaid'] : ['processing', 'unpaid'];
    }

    /**
     * Deterministic phone number in Bangladeshi mobile format.
     */
    private function phoneNumber(string $key): string
    {
        return '+8801'.str_pad((string) $this->pickInt("phone-{$key}", 1000000000), 9, '0', STR_PAD_LEFT);
    }

    /**
     * Deterministic shipping address.
     */
    private function shippingAddress(string $key): string
    {
        $cities = array_keys(self::ADDRESS_AREAS);
        $city = $cities[$this->pickInt("city-{$key}", count($cities))];
        $areas = self::ADDRESS_AREAS[$city];
        $area = $areas[$this->pickInt("area-{$key}", count($areas))];

        return sprintf(
            'House %d, Road %d, %s, %s',
            1 + $this->pickInt("house-{$key}", 120),
            1 + $this->pickInt("road-{$key}", 25),
            $area,
            $city
        );
    }

    private function groupForCategory(string $categorySlug): string
    {
        foreach (self::GROUP_CATEGORY_SLUGS as $group => $slugs) {
            if (in_array($categorySlug, $slugs, true)) {
                return $group;
            }
        }

        return array_key_first(self::GROUP_CATEGORY_SLUGS);
    }

    /**
     * Deterministic float in [0, 1) derived from a stable key.
     */
    private function unit(string $key): float
    {
        return (hexdec(substr(md5(self::DATASET_SEED.'|'.$key), 0, 8)) % 100000) / 100000;
    }

    /**
     * Deterministic integer in [0, $max).
     */
    private function pickInt(string $key, int $max): int
    {
        return $max > 0 ? (int) floor($this->unit($key) * $max) : 0;
    }

    /**
     * Print the import summary table.
     *
     * @param  array<string, int>  $stats
     */
    private function renderSummary(array $stats): void
    {
        $this->newLine();
        $this->info('Dataset summary:');

        $rows = [];

        foreach ($stats as $label => $value) {
            $rows[] = [Str::headline(str_replace('_', ' ', $label)), $value];
        }

        $this->table(['Metric', 'Count'], $rows);

        $this->newLine();
        $this->line('Demo accounts (development only):');
        $this->line('  Admin:  admin@shopsync.test');
        $this->line('  Owners: dataset.owner@shopsync.test, owner.fashion@shopsync.test, owner.home@shopsync.test, owner.beauty@shopsync.test');
        $this->line('  Customers: customer01@shopsync.test ... customer24@shopsync.test');
        $this->line('  Password for every demo account: '.self::DEMO_PASSWORD);
    }
}
