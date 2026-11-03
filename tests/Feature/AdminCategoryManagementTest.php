<?php

use App\Models\Category;
use App\Models\User;

function adminUser(): User
{
    return User::factory()->create(['role' => 'admin']);
}

test('admin can update a category without renaming it', function () {
    $admin = adminUser();
    $category = Category::factory()->create(['name' => 'Books', 'slug' => 'books']);

    $this->actingAs($admin)
        ->patch(route('admin.categories.update', $category), [
            'name' => 'Books',
            'slug' => 'books',
            'description' => 'Printed and digital books.',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    expect($category->fresh()->description)->toBe('Printed and digital books.');
});

test('admin category update still rejects a name used by another category', function () {
    $admin = adminUser();
    Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
    $category = Category::factory()->create(['name' => 'Books', 'slug' => 'books']);

    $this->actingAs($admin)
        ->patch(route('admin.categories.update', $category), [
            'name' => 'Electronics',
            'slug' => 'books',
        ])
        ->assertSessionHasErrors('name');

    expect($category->fresh()->name)->toBe('Books');
});

test('admin can create a category without a slug and one is generated', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Home Appliances',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::query()->where('name', 'Home Appliances')->sole()->slug)
        ->toBe('home-appliances');
});
