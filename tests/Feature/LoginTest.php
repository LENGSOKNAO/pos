<?php

use App\Models\User;

function createLoginUser(): User
{
    return User::create([
        'username' => 'testcashier',
        'email' => 'cashier@test.local',
        'password_hash' => 'password123',
    ]);
}

test('login page renders', function () {
    $this->get('/login')->assertOk();
});

test('login succeeds with valid credentials', function () {
    createLoginUser();

    $response = $this->post('/login', [
        'username' => 'testcashier',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

test('login succeeds with cookie session driver like production', function () {
    config(['session.driver' => 'cookie']);
    createLoginUser();

    $response = $this->post('/login', [
        'username' => 'testcashier',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
});

test('login rejects invalid credentials without server error', function () {
    createLoginUser();

    $response = $this->post('/login', [
        'username' => 'testcashier',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('username');
    $this->assertGuest();
});
