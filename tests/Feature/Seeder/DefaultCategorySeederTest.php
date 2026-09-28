<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the six default categories owned by nobody', function () {
    $this->seed(DefaultCategorySeeder::class);

    $defaults = Category::whereNull('user_id')->get()->keyBy('name');

    expect($defaults)->toHaveCount(6)
        ->and($defaults->get('Gaji')->type->value)->toBe('income')
        ->and($defaults->get('Bonus/Lainnya')->type->value)->toBe('income')
        ->and($defaults->get('Makanan')->type->value)->toBe('expense')
        ->and($defaults->get('Transportasi')->type->value)->toBe('expense')
        ->and($defaults->get('Hiburan')->type->value)->toBe('expense')
        ->and($defaults->get('Tagihan')->type->value)->toBe('expense')
        ->and($defaults->get('Makanan')->color)->toBe('#B85C4A')
        ->and($defaults->get('Gaji')->color)->toBe('#4A7C59');
});

it('is idempotent when seeded twice', function () {
    $this->seed(DefaultCategorySeeder::class);
    $this->seed(DefaultCategorySeeder::class);

    expect(Category::whereNull('user_id')->count())->toBe(6);
});

it('is visible to every user through the visibleTo scope', function () {
    $user = User::factory()->create();
    $this->seed(DefaultCategorySeeder::class);

    $custom = Category::create([
        'user_id' => $user->id,
        'name' => 'Kopi',
        'type' => 'expense',
    ]);

    $visibleForUser = Category::visibleTo($user->id)->pluck('id');
    $visibleForOther = Category::visibleTo(User::factory()->create()->id)->pluck('id');

    expect($visibleForUser)->toHaveCount(7)
        ->and($visibleForUser)->toContain($custom->id)
        ->and($visibleForOther)->toHaveCount(6)
        ->and($visibleForOther)->not->toContain($custom->id);
});

