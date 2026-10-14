<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ImportDummyJsonDataset extends Command
{
    protected $signature = 'shopsync:import-dataset';

    protected $description =
        'Import DummyJSON products, categories, users and carts into ShopSync';

    public function handle(): int
    {
        $directory = storage_path('app/datasets');

        foreach (['products.json', 'categories.json', 'users.json', 'carts.json'] as $file) {
            if (! is_file("{$directory}/{$file}")) {
                $this->error("Dataset file missing: {$directory}/{$file}");

                return self::FAILURE;
            }
        }

        $productsData = $this->readJson("{$directory}/products.json");
        $categoriesData = $this->readJson("{$directory}/categories.json");
        $usersData = $this->readJson("{$directory}/users.json");
        $cartsData = $this->readJson("{$directory}/carts.json");

        $onlineProducts = $productsData['products'] ?? [];
        $onlineCategories = $categoriesData;
        $onlineUsers = $usersData['users'] ?? [];
        $onlineCarts = $cartsData['carts'] ?? [];

        if (! $onlineProducts || ! $onlineCategories || ! $onlineUsers || ! $onlineCarts) {
            $this->error('One or more dataset files contain no records.');

            return self::FAILURE;
        }

        $this->info('Importing DummyJSON dataset...');
        $this->warn('This importer does not delete existing database records.');

        DB::transaction(function () use (
            $onlineProducts,
            $onlineCategories,
            $onlineUsers,
            $onlineCarts
        ): void {
            /*
             * Use a dedicated local demo owner.
             */
            $owner = User::firstOrCreate(
                ['email' => 'dataset.owner@shopsync.test'],
                [
                    'name' => 'Dataset Demo Store',
                    'role' => 'business_owner',
                    'password' => Hash::make('1234'),
                    'email_verified_at' => now(),
                ]
            );

            /*
             * Import online categories.
             */
            $categoryMap = [];

            foreach ($onlineCategories as $data) {
                $slug = $data['slug'] ?? Str::slug($data['name'] ?? '');

                if ($slug === '') {
                    continue;
                }

                $category = Category::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'] ?? Str::headline($slug),
                        'description' => 'Imported from DummyJSON.',
                        'parent_id' => null,
                        'is_active' => true,
                    ]
                );

                $categoryMap[$slug] = $category;
            }

            /*
             * Import products.
             * Store image URLs, not downloaded image files.
             */
            $productMap = [];

            foreach ($onlineProducts as $data) {
                $title = trim($data['title'] ?? '');
                $slug = Str::slug($title);

                if ($title === '' || $slug === '') {
                    continue;
                }

                $categorySlug = $data['category'] ?? '';
                $category = $categoryMap[$categorySlug] ?? null;

                if (! $category) {
                    $category = Category::firstOrCreate(
                        ['slug' => $categorySlug ?: 'uncategorized'],
                        [
                            'name' => Str::headline($categorySlug ?: 'uncategorized'),
                            'description' => 'Imported product category.',
                            'parent_id' => null,
                            'is_active' => true,
                        ]
                    );

                    $categoryMap[$categorySlug] = $category;
                }

                $product = Product::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'user_id' => $owner->id,
                        'category_id' => $category->id,
                        'name' => $title,
                        'description' => $data['description'] ?? null,
                        'price' => $data['price'] ?? 0,
                        'stock' => max(0, (int) ($data['stock'] ?? 0)),
                        'image_path' => $data['thumbnail'] ?? null,
                        'is_active' => true,
                    ]
                );

                /*
                 * Map DummyJSON product ID to local Product model.
                 */
                $productMap[(int) $data['id']] = $product;
            }

            /*
             * Import customer identities safely.
             * Source passwords and sensitive fields are deliberately ignored.
             */
            $userMap = [];

            foreach ($onlineUsers as $data) {
                $externalId = (int) ($data['id'] ?? 0);

                if ($externalId < 1) {
                    continue;
                }

                $firstName = trim($data['firstName'] ?? '');
                $lastName = trim($data['lastName'] ?? '');
                $name = trim("{$firstName} {$lastName}");

                if ($name === '') {
                    $name = "Dataset Customer {$externalId}";
                }

                $email = "dataset.user.{$externalId}@shopsync.test";

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'role' => 'customer',
                        'password' => Hash::make(Str::random(48)),
                        'email_verified_at' => now(),
                    ]
                );

                $userMap[$externalId] = $user;
            }

            /*
             * Turn each online cart into one local demo order.
             * These are synthetic test orders, not actual purchases.
             */
            $statuses = [
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
            ];

            $importedOrders = 0;
            $skippedCarts = 0;

            foreach ($onlineCarts as $cart) {
                $cartId = (int) ($cart['id'] ?? 0);
                $externalUserId = (int) ($cart['userId'] ?? 0);

                if (
                    $cartId < 1 ||
                    ! isset($userMap[$externalUserId]) ||
                    empty($cart['products'])
                ) {
                    $skippedCarts++;

                    continue;
                }

                $preparedItems = [];
                $orderTotal = 0.0;

                foreach ($cart['products'] as $cartItem) {
                    $externalProductId = (int) ($cartItem['id'] ?? 0);
                    $quantity = max(1, (int) ($cartItem['quantity'] ?? 1));

                    $product = $productMap[$externalProductId] ?? null;

                    if (! $product) {
                        continue;
                    }

                    $unitPrice = (float) $product->price;
                    $subtotal = round($unitPrice * $quantity, 2);
                    $orderTotal += $subtotal;

                    $preparedItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];
                }

                if (! $preparedItems) {
                    $skippedCarts++;

                    continue;
                }

                $customer = $userMap[$externalUserId];
                $status = $statuses[($cartId - 1) % count($statuses)];

                $order = Order::firstOrCreate(
                    ['order_number' => sprintf('DUMMYJSON-%04d', $cartId)],
                    [
                        'user_id' => $customer->id,
                        'status' => $status,
                        'payment_method' => 'cod',
                        'payment_status' => $status === 'delivered'
                            ? 'paid'
                            : 'unpaid',
                        'customer_name' => $customer->name,
                        'phone' => '00000000000',
                        'shipping_address' => 'DummyJSON demo address; not a real delivery address.',
                        'total' => round($orderTotal, 2),
                    ]
                );

                foreach ($preparedItems as $item) {
                    OrderIdem::firstOrCreate(
                        [
                            'order_id' => $order->id,
                            'product_id' => $item['product']->id,
                        ],
                        [
                            'product_name' => $item['product']->name,
                            'unit_price' => $item['unit_price'],
                            'quantity' => $item['quantity'],
                            'subtotal' => $item['subtotal'],
                        ]
                    );
                }

                $importedOrders++;
            }

            $this->newLine();
            $this->info('Dataset import completed.');
            $this->line('Categories processed: '.count($categoryMap));
            $this->line('Products mapped: '.count($productMap));
            $this->line('Users mapped: '.count($userMap));
            $this->line("Carts processed: {$importedOrders}");
            $this->line("Carts skipped: {$skippedCarts}");
            $this->line("Demo owner: {$owner->email}");
        });

        $this->newLine();
        $this->warn('Imported order statuses are synthetic and imported orders do not deduct stock.');
        $this->warn('Local customer accounts have random passwords; they are not the source dataset passwords.');

        return self::SUCCESS;
    }

    private function readJson(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read dataset file: {$path}");
        }

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new RuntimeException("Invalid JSON structure: {$path}");
        }

        return $data;
    }
}
