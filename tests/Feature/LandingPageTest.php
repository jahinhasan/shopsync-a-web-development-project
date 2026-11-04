<?php

use App\Models\User;

test('guests see the animated landing page at the root route', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Every shop in sync.')
        ->assertSee('landing-hero-canvas')
        ->assertSee('landing-rose-canvas')
        ->assertSee(route('register'), false);
});

test('authenticated customers are redirected from the landing page to the dashboard', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

test('admins are redirected from the landing page to their dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
