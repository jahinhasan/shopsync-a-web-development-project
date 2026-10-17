<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\ShopConversation;
use App\Models\User;

function chatThrottleOwner(): User
{
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    return $owner;
}

test('chat messages are throttled after 30 per minute', function () {
    $owner = chatThrottleOwner();
    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create();
    $customer = User::factory()->customer()->create();
    $conversation = ShopConversation::create([
        'customer_id' => $customer->id,
        'shop_owner_id' => $owner->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
    ]);

    $this->actingAs($customer);

    for ($i = 0; $i < 30; $i++) {
        $this->post(route('chat.messages.store', $conversation), ['body' => "hello {$i}"])
            ->assertCreated();
    }

    $this->post(route('chat.messages.store', $conversation), ['body' => 'one more'])
        ->assertStatus(429);
});

test('product reviews are throttled after 10 per minute', function () {
    $owner = chatThrottleOwner();
    $category = Category::factory()->create();
    $product = Product::factory()->for($owner, 'owner')->for($category)->create();
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer);

    for ($i = 0; $i < 10; $i++) {
        $this->post(route('shop.reviews.store', $product), ['comment' => "review {$i}"])
            ->assertRedirect();
    }

    $this->post(route('shop.reviews.store', $product), ['comment' => 'one more'])
        ->assertStatus(429);
});
