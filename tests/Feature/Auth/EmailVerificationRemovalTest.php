<?php

use App\Models\User;

test('email verification endpoints are removed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/verify-email')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/verify-email/1/hash')
        ->assertNotFound();

    $this->actingAs($user)
        ->post('/email/verification-notification')
        ->assertNotFound();
});

test('password reset endpoints remain available', function () {
    $this->get('/forgot-password')->assertOk();

    // Unknown emails are handled without enumerating accounts; the request
    // itself must be accepted.
    $this->post('/forgot-password', ['email' => 'missing@example.com'])
        ->assertRedirect();
});
