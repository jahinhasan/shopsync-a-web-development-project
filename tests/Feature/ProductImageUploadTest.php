<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// 1x1 transparent PNG — GD is not available in this environment, so
// UploadedFile::fake()->image() cannot be used.
const TEST_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

function uploadTestOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

function validProductPayload(): array
{
    $category = Category::factory()->create();

    return [
        'category_id' => $category->id,
        'name' => 'Test Product',
        'slug' => 'test-product-'.strtolower(Str::random(6)),
        'description' => 'A test product.',
        'price' => 1500,
        'stock' => 10,
    ];
}

test('product store rejects arbitrary non-image files', function () {
    Storage::fake('public');
    $owner = uploadTestOwner();

    $file = UploadedFile::fake()->createWithContent('payload.txt', 'definitely not an image');

    $this->actingAs($owner)
        ->post(route('owner.products.store'), validProductPayload() + ['image' => $file])
        ->assertSessionHasErrors('image');

    Storage::disk('public')->assertDirectoryEmpty('products');
});

test('product store rejects svg uploads', function () {
    Storage::fake('public');
    $owner = uploadTestOwner();

    $file = UploadedFile::fake()->createWithContent(
        'evil.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
    );

    $this->actingAs($owner)
        ->post(route('owner.products.store'), validProductPayload() + ['image' => $file])
        ->assertSessionHasErrors('image');

    Storage::disk('public')->assertDirectoryEmpty('products');
});

test('product store accepts a real png image', function () {
    Storage::fake('public');
    $owner = uploadTestOwner();

    $file = UploadedFile::fake()->createWithContent(
        'photo.png',
        base64_decode(TEST_PNG)
    );

    $this->actingAs($owner)
        ->post(route('owner.products.store'), validProductPayload() + ['image' => $file])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.products.index'));

    $product = Product::query()->where('name', 'Test Product')->sole();

    expect($product->image_path)->toStartWith('products/');

    Storage::disk('public')->assertExists($product->image_path);
});

test('product update replaces the image and deletes the replaced file', function () {
    Storage::fake('public');
    $owner = uploadTestOwner();

    Storage::disk('public')->put('products/old-image.png', 'old bytes');

    $category = Category::factory()->create();
    $product = Product::factory()->for($owner, 'owner')->for($category)->create([
        'image_path' => 'products/old-image.png',
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'new-image.png',
        base64_decode(TEST_PNG)
    );

    $this->actingAs($owner)
        ->patch(route('owner.products.update', $product), validProductPayload() + ['image' => $file])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.products.index'));

    $product->refresh();

    expect($product->image_path)->not->toBe('products/old-image.png');

    Storage::disk('public')->assertExists($product->image_path);
    Storage::disk('public')->assertMissing('products/old-image.png');
});
