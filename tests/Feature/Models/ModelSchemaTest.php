<?php

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the categories, transactions and budgets tables', function () {
    expect(Schema::hasTable('categories'))->toBeTrue()
        ->and(Schema::hasTable('transactions'))->toBeTrue()
        ->and(Schema::hasTable('budgets'))->toBeTrue();
});

it('creates every column defined in the schema', function () {
    $expected = [
        'categories' => ['id', 'user_id', 'name', 'type', 'icon', 'color', 'created_at', 'updated_at', 'deleted_at'],
        'transactions' => ['id', 'user_id', 'category_id', 'type', 'amount', 'description', 'transaction_date', 'created_at', 'updated_at', 'deleted_at'],
        'budgets' => ['id', 'user_id', 'category_id', 'month', 'amount_limit', 'created_at', 'updated_at'],
    ];

    foreach ($expected as $table => $columns) {
        foreach ($columns as $column) {
            expect(Schema::hasColumn($table, $column))->toBeTrue("{$table}.{$column} hilang");
        }
    }
});

it('stores money as integers without decimals', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $transaction = Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 150000,
        'description' => 'Makan siang',
        'transaction_date' => today()->toDateString(),
    ]);

    $fresh = $transaction->fresh();

    expect($fresh->amount)->toBe(150000)
        ->and($fresh->getAttributes()['amount'])->toBe(150000);
});

it('wires up the model relationships', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $transaction = Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
    ]);
    $budget = Budget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'month' => today()->startOfMonth()->toDateString(),
        'amount_limit' => 500000,
    ]);

    expect($user->transactions)->toHaveCount(1)
        ->and($user->budgets)->toHaveCount(1)
        ->and($user->customCategories)->toHaveCount(1)
        ->and($transaction->user->id)->toBe($user->id)
        ->and($transaction->category->id)->toBe($category->id)
        ->and($category->transactions)->toHaveCount(1)
        ->and($category->budgets)->toHaveCount(1)
        ->and($budget->category->id)->toBe($category->id)
        ->and($budget->user->id)->toBe($user->id);
});

it('marks seeded global categories as default', function () {
    $category = Category::create([
        'user_id' => null,
        'name' => 'Makanan',
        'type' => 'expense',
    ]);

    expect($category->isDefault())->toBeTrue()
        ->and(Category::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Makanan',
            'type' => 'expense',
        ])->isDefault())->toBeFalse();
});

it('restricts deleting a category that is still used by a transaction', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
    ]);

    $category->forceDelete();
})->throws(QueryException::class);

it('allows soft deleting an unused category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $category->delete();

    expect(Category::withTrashed()->find($category->id))->not->toBeNull()
        ->and(Category::find($category->id))->toBeNull();
});

it('cascades transactions and budgets when a user is deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
    ]);

    Budget::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'month' => today()->startOfMonth()->toDateString(),
        'amount_limit' => 100000,
    ]);

    $user->delete();

    expect(Transaction::withTrashed()->count())->toBe(0)
        ->and(Budget::count())->toBe(0)
        ->and(Category::count())->toBe(0);
});

it('enforces one budget per category per month', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $month = today()->startOfMonth()->toDateString();

    Budget::create(['user_id' => $user->id, 'category_id' => $category->id, 'month' => $month, 'amount_limit' => 1]);
    Budget::create(['user_id' => $user->id, 'category_id' => $category->id, 'month' => $month, 'amount_limit' => 2]);
})->throws(QueryException::class);
