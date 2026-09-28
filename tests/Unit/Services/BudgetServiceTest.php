<?php

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function budgetService(): BudgetService
{
    return app(BudgetService::class);
}

it('creates a budget and updates it instead of duplicating it', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $month = today()->startOfMonth();

    $first = budgetService()->setBudget($user, $category->id, $month, 500000);
    $second = budgetService()->setBudget($user, $category->id, $month, 750000);

    expect($first->id)->toBe($second->id)
        ->and(Budget::count())->toBe(1)
        ->and($second->fresh()->amount_limit)->toBe(750000);
});

it('normalises the month to the first day of the month', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    $budget = budgetService()->setBudget($user, $category->id, today()->setDay(17), 100000);

    expect($budget->fresh()->month->toDateString())->toBe(today()->startOfMonth()->toDateString());
});

it('keeps budgets separate per user and per month', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $userA->id, 'type' => 'expense']);

    budgetService()->setBudget($userA, $category->id, today()->startOfMonth(), 100000);
    budgetService()->setBudget($userA, $category->id, today()->startOfMonth()->copy()->addMonth(), 200000);
    budgetService()->setBudget($userB, $category->id, today()->startOfMonth(), 300000);

    expect(Budget::count())->toBe(3)
        ->and(budgetService()->getBudgetStatus($userA, today()))->toHaveCount(1)
        ->and(budgetService()->getBudgetStatus($userB, today()))->toHaveCount(1)
        ->and(budgetService()->getBudgetStatus($userB, today())->first()['limit'])->toBe(300000);
});

it('reports a safe status below eighty percent', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    budgetService()->setBudget($user, $category->id, today()->startOfMonth(), 100000);
    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 79000,
        'transaction_date' => today()->toDateString(),
    ]);

    $status = budgetService()->getBudgetStatus($user, today())->first();

    expect($status['spent'])->toBe(79000)
        ->and($status['percentage'])->toBe(79.0)
        ->and($status['status'])->toBe(BudgetStatus::Safe);
});

it('reports a warning at eighty percent and above', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    budgetService()->setBudget($user, $category->id, today()->startOfMonth(), 100000);
    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 80000,
        'transaction_date' => today()->toDateString(),
    ]);

    $status = budgetService()->getBudgetStatus($user, today())->first();

    expect($status['percentage'])->toBe(80.0)
        ->and($status['status'])->toBe(BudgetStatus::Warning);
});

it('reports exceeded at one hundred percent and above', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    budgetService()->setBudget($user, $category->id, today()->startOfMonth(), 100000);
    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 130000,
        'transaction_date' => today()->toDateString(),
    ]);

    $status = budgetService()->getBudgetStatus($user, today())->first();

    expect($status['percentage'])->toBe(130.0)
        ->and($status['status'])->toBe(BudgetStatus::Exceeded);
});

it('treats a month without transactions as safe', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);

    budgetService()->setBudget($user, $category->id, today()->startOfMonth(), 100000);

    $status = budgetService()->getBudgetStatus($user, today())->first();

    expect($status['spent'])->toBe(0)
        ->and($status['percentage'])->toBe(0.0)
        ->and($status['status'])->toBe(BudgetStatus::Safe);
});

it('only counts expenses of the requested month', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['type' => 'expense']);
    $month = today()->startOfMonth();

    budgetService()->setBudget($user, $category->id, $month, 1000000);

    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 10000,
        'transaction_date' => $month->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 90000,
        'transaction_date' => $month->copy()->subMonth()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $category->id,
        'type' => 'income',
        'amount' => 500000,
        'transaction_date' => $month->toDateString(),
    ]);

    $status = budgetService()->getBudgetStatus($user, $month)->first();

    expect($status['spent'])->toBe(10000)
        ->and($status['limit'])->toBe(1000000)
        ->and($status['category'])->toBeInstanceOf(Category::class);
});

it('returns an empty collection when the user has no budgets', function () {
    $user = User::factory()->create();

    expect(budgetService()->getBudgetStatus($user, today()))->toHaveCount(0);
});
