<?php

use App\Models\User;

test('login page renders', function () {
    $this->get('/login')->assertStatus(200);
});

test('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('user can log in with username and password', function () {
    $user = User::create([
        'name' => 'Test Admin',
        'username' => 'testadmin',
        'email' => 'testadmin@pos.local',
        'password' => 'password',
        'status' => 'active',
    ]);

    $response = $this->post('/login', [
        'username' => 'testadmin',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('login fails with wrong password', function () {
    User::create([
        'name' => 'Test Admin',
        'username' => 'testadmin',
        'email' => 'testadmin@pos.local',
        'password' => 'password',
        'status' => 'active',
    ]);

    $this->post('/login', [
        'username' => 'testadmin',
        'password' => 'wrong',
    ])->assertSessionHasErrors();

    $this->assertGuest();
});
