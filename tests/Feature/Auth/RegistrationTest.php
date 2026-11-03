<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => 'customer',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration requires a selectable role', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test2@example.com',
        'role' => 'admin',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test2@example.com']);
});
