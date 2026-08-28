<?php

use App\Models\User;

test('a user can log in with correct credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');
});

test('login fails with an incorrect password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors();
});

test('logging in redirects each role to its own dashboard', function () {
    $admin = User::factory()->admin()->create(['password' => bcrypt('password')]);

    $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

    $response = $this->get('/dashboard');
    $response->assertRedirect('/admin/dashboard');
});

test('a suspended user is blocked from role-gated pages even while authenticated', function () {
    $user = User::factory()->create(['status' => 'suspended']);

    $response = $this->actingAs($user)->get('/customer/dashboard');

    $response->assertForbidden();
});
