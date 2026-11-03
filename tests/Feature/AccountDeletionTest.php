<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopApplication;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function deletionOrderFor(User $customer): Order
{
    return Order::create([
        'user_id' => $customer->id,
        'order_number' => 'ORD-'.strtoupper(uniqid()),
        'status' => 'pending',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'customer_name' => $customer->name,
        'phone' => '01700000000',
        'shipping_address' => 'House 12, Dhaka',
        'total' => 500,
    ]);
}

test('deleting an account with order history anonymizes it and preserves orders', function () {
    $user = User::factory()->customer()->create();
    $order = deletionOrderFor($user);

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest();

    $fresh = $user->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->name)->toBe('Former user #'.$user->id)
        ->and($fresh->email)->toBe('former-user-'.$user->id.'@shopsync.invalid')
        ->and(Hash::check('password', $fresh->password))->toBeFalse()
        ->and($fresh->remember_token)->toBeNull();

    // The order survives and still belongs to the anonymized account.
    expect($order->fresh()->user_id)->toBe($user->id)
        ->and(Order::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('anonymization frees the original email for reuse', function () {
    $user = User::factory()->customer()->create();
    deletionOrderFor($user);

    $originalEmail = $user->email;

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    $this->assertDatabaseHas('users', ['email' => 'former-user-'.$user->id.'@shopsync.invalid']);

    expect(User::query()->where('email', $originalEmail)->exists())->toBeFalse();
});

test('deleting an owner account with products anonymizes it and preserves products', function () {
    $owner = User::factory()->businessOwner()->create();

    $product = Product::factory()
        ->for($owner, 'owner')
        ->for(Category::factory())
        ->create();

    $this->actingAs($owner)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    $fresh = $owner->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->email)->toBe('former-user-'.$owner->id.'@shopsync.invalid')
        ->and($product->fresh()->user_id)->toBe($owner->id);
});

test('an account with a shop application is anonymized instead of cascade-deleted', function () {
    $owner = User::factory()->businessOwner()->create();
    ShopApplication::factory()->for($owner, 'applicant')->approved()->create();

    $this->actingAs($owner)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    expect($owner->fresh())->not->toBeNull()
        ->and($owner->fresh()->shopApplication)->not->toBeNull();
});

test('anonymized users cannot log in with their old password', function () {
    $user = User::factory()->customer()->create();
    deletionOrderFor($user);

    $email = $user->email;

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    $this->post('/login', [
        'email' => $email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});
