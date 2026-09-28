<?php

use App\Models\User;

it('renders the branded landing page without the default laravel styling', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Cash Flow')
        ->assertSee('Arus kas jelas, keputusan lebih tenang.')
        ->assertSee('Daftar Gratis')
        ->assertSee('Masuk')
        ->assertDontSee('#FF2D20')
        ->assertDontSee('Laracasts')
        ->assertDontSee('bg-gray-50');
});

it('offers the dashboard to an authenticated visitor', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Buka dashboard')
        ->assertDontSee('Daftar Gratis');
});
