<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;

function shopCustomer(): User
{
    return User::factory()->customer()->create();
}

function shopOwnerUser(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create([
        'shop_name' => 'Gadget Haven',
        'shop_status' => 'active',
    ]);

    return $owner;
}

function shopProduct(User $owner, Category $category, array $attributes = []): Product
{
    return Product::factory()
        ->for($owner, 'owner')
        ->for($category)
        ->create($attributes);
}

test('shop search matches product name, seller and shop name', function () {
    $owner = shopOwnerUser();
    $electronics = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
    $grocery = Category::factory()->create(['name' => 'Grocery', 'is_active' => true]);

    $target = shopProduct($owner, $electronics, ['name' => 'Wireless Mouse Pro']);
    shopProduct($owner, $electronics, ['name' => 'Mechanical Keyboard']);
    shopProduct($owner, $grocery, ['name' => 'Basmati Rice 5kg']);

    $customer = shopCustomer();

    // By product name.
    $this->actingAs($customer)
        ->get(route('shop.index', ['q' => 'wireless mouse']))
        ->assertOk()
        ->assertSee('Wireless Mouse Pro')
        ->assertDontSee('Mechanical Keyboard');

    // By seller's account name.
    $this->actingAs($customer)
        ->get(route('shop.index', ['q' => $owner->name]))
        ->assertOk()
        ->assertSee('Wireless Mouse Pro');

    // By approved shop name.
    $this->actingAs($customer)
        ->get(route('shop.index', ['q' => 'gadget haven']))
        ->assertOk()
        ->assertSee('Wireless Mouse Pro')
        ->assertSee('Basmati Rice 5kg');

    // The view renders the query back into the search box.
    $this->actingAs($customer)
        ->get(route('shop.index', ['q' => 'wireless']))
        ->assertOk()
        ->assertViewHas('search', 'wireless');
});

test('shop category filter narrows results and combines with search', function () {
    $owner = shopOwnerUser();
    $electronics = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
    $grocery = Category::factory()->create(['name' => 'Grocery', 'is_active' => true]);

    shopProduct($owner, $electronics, ['name' => 'Smart Watch']);
    shopProduct($owner, $grocery, ['name' => 'Green Tea']);

    $customer = shopCustomer();

    $this->actingAs($customer)
        ->get(route('shop.index', ['category' => $electronics->slug]))
        ->assertOk()
        ->assertSee('Smart Watch')
        ->assertDontSee('Green Tea');

    // Combined: category narrows the same keyword search.
    shopProduct($owner, $grocery, ['name' => 'Smart Rice Cooker']);

    $this->actingAs($customer)
        ->get(route('shop.index', ['q' => 'smart', 'category' => $electronics->slug]))
        ->assertOk()
        ->assertSee('Smart Watch')
        ->assertDontSee('Smart Rice Cooker');

    // Inactive categories are not selectable through the filter.
    $hidden = Category::factory()->create(['name' => 'Archived', 'is_active' => false]);
    shopProduct($owner, $hidden, ['name' => 'Old Stock Item']);

    $this->actingAs($customer)
        ->get(route('shop.index', ['category' => $hidden->slug]))
        ->assertOk()
        ->assertDontSee('Old Stock Item');
});

test('shop shows a helpful empty state when nothing matches', function () {
    $owner = shopOwnerUser();
    $category = Category::factory()->create(['is_active' => true]);
    shopProduct($owner, $category, ['name' => 'Bamboo Cutting Board']);

    $customer = shopCustomer();

    $this->actingAs($customer)
        ->get(route('shop.index', ['q' => 'nonexistent-thing']))
        ->assertOk()
        ->assertSee('No products match')
        ->assertSee('Browse all products');
});

test('customer dashboard renders category links instead of one grid per category', function () {
    $owner = shopOwnerUser();
    $category = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
    shopProduct($owner, $category, ['name' => 'Smart Watch']);
    $customer = shopCustomer();

    $response = $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk();
    $content = $response->getContent();

    // The category is linked as a quick-link into the shop's category filter,
    // not rendered as its own product-grid section heading.
    expect(substr_count($content, 'category='.$category->slug))->toBe(1)
        ->and($content)->not->toContain('See more');
});
