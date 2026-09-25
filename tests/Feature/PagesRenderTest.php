<?php

use App\Models\User;
use Database\Seeders\PosDemoSeeder;

beforeEach(function () {
    $this->seed(PosDemoSeeder::class);
});

test('core pages render for authenticated user', function (string $url) {
    $user = User::where('username', 'admin')->firstOrFail();

    $this->actingAs($user)->get($url)->assertStatus(200);
})->with(['/dashboard', '/products', '/stocks', '/pos']);
