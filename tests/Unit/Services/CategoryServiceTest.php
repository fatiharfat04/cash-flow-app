<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function categoryService(): CategoryService
{
    return app(CategoryService::class);
}

it('creates a custom category owned by the current user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = categoryService()->createCustom($user, [
        'name' => 'Kopi',
        'type' => 'expense',
        'icon' => 'shopping-bag',
        'color' => '#B85C4A',
    ]);

    expect($category->user_id)->toBe($user->id)
        ->and($category->isDefault())->toBeFalse()
        ->and($category->name)->toBe('Kopi');
});

it('lets the owner update their custom category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = categoryService()->createCustom($user, ['name' => 'Kopi', 'type' => 'expense']);
    $updated = categoryService()->update($category, [
        'name' => 'Kopi Susu',
        'type' => 'expense',
        'icon' => 'tag',
        'color' => '#4A7C59',
    ]);

    expect($updated->name)->toBe('Kopi Susu')
        ->and($updated->color)->toBe('#4A7C59');
});

it('refuses to update a default category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    seedDefaultCategories();

    categoryService()->update(Category::whereNull('user_id')->firstOrFail(), [
        'name' => 'Dicuri',
        'type' => 'expense',
    ]);
})->throws(AuthorizationException::class);

it('refuses to update a category that belongs to someone else', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $category = Category::factory()->for($owner)->create(['type' => 'expense']);

    $this->actingAs($intruder);

    categoryService()->update($category, ['name' => 'Hapus Milik Orang', 'type' => 'expense']);
})->throws(AuthorizationException::class);

it('refuses to delete a category used by a transaction and explains why', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
    ]);

    categoryService()->delete($category);
})->throws(Exception::class, 'masih digunakan oleh transaksi');

it('deletes an unused custom category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    expect(categoryService()->delete($category))->toBeTrue()
        ->and(Category::find($category->id))->toBeNull();
});

it('refuses to delete a default category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    seedDefaultCategories();

    categoryService()->delete(Category::whereNull('user_id')->firstOrFail());
})->throws(AuthorizationException::class);

it('lists default plus own categories and never someone else custom ones', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = Category::factory()->for($user)->create(['name' => 'Kopi', 'type' => 'expense']);
    Category::factory()->for($other)->create(['name' => 'Milik Orang', 'type' => 'expense']);
    seedDefaultCategories();

    $expenseOnly = categoryService()->getVisibleForUser($user, \App\Enums\TransactionType::Expense);
    $all = categoryService()->getVisibleForUser($user);

    expect($expenseOnly->pluck('id')->all())->toContain($mine->id)
        ->and($expenseOnly->pluck('name')->all())->not->toContain('Milik Orang')
        ->and($expenseOnly->pluck('name')->all())->not->toContain('Gaji')
        ->and($all)->toHaveCount(7)
        ->and($all->where('type', \App\Enums\TransactionType::Income)->pluck('name')->all())->toContain('Gaji');
});

it('never returns a trashed category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $category->delete();

    $visible = categoryService()->getVisibleForUser($user);

    expect($visible->pluck('id')->all())->not->toContain($category->id);
});
