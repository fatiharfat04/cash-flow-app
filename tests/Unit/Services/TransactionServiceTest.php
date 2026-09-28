<?php

use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function transactionService(): TransactionService
{
    return app(TransactionService::class);
}

it('creates a transaction with integer rupiah amount', function () {
    $user = User::factory()->create();
    $category = seedDefaultCategories()['expense'];

    $transaction = transactionService()->create($user, [
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => '150000',
        'description' => 'Makan siang',
        'transaction_date' => today()->toDateString(),
    ]);

    expect($transaction->amount)->toBe(150000)
        ->and($transaction->getAttributes()['amount'])->toBe(150000)
        ->and($transaction->type->value)->toBe('expense')
        ->and($transaction->user_id)->toBe($user->id)
        ->and($transaction->category_id)->toBe($category->id);
});

it('updates an existing transaction', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();
    $transaction = transactionService()->create($user, [
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 10000,
        'transaction_date' => today()->toDateString(),
    ]);

    $updated = transactionService()->update($transaction, [
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 750000,
        'description' => 'Bonus proyek',
        'transaction_date' => today()->subDay()->toDateString(),
    ]);

    expect($updated->amount)->toBe(750000)
        ->and($updated->type->value)->toBe('income')
        ->and($updated->category_id)->toBe($categories['income']->id)
        ->and($updated->transaction_date->toDateString())->toBe(today()->subDay()->toDateString());
});

it('soft deletes a transaction', function () {
    $user = User::factory()->create();
    $category = seedDefaultCategories()['expense'];
    $transaction = transactionService()->create($user, [
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
    ]);

    expect(transactionService()->delete($transaction))->toBeTrue()
        ->and(Transaction::find($transaction->id))->toBeNull()
        ->and(Transaction::withTrashed()->find($transaction->id))->not->toBeNull();
});

it('summarises income, expense and balance for the current month', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->count(2)->create([
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 5000000,
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 1250000,
        'transaction_date' => today()->toDateString(),
    ]);

    $summary = transactionService()->getSummary($user, ['period' => 'month']);

    expect($summary['total_income'])->toBe(10000000)
        ->and($summary['total_expense'])->toBe(1250000)
        ->and($summary['balance'])->toBe(8750000);
});

it('excludes transactions outside the requested period', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 999999,
        'transaction_date' => today()->subMonth()->toDateString(),
    ]);

    expect(transactionService()->getSummary($user, ['period' => 'today'])['total_expense'])->toBe(1000)
        ->and(transactionService()->getSummary($user, ['period' => 'month'])['total_expense'])->toBe(1000)
        ->and(transactionService()->getSummary($user, ['period' => 'all'])['total_expense'])->toBe(1000999)
        ->and(transactionService()->getBalance($user))->toBe(-1000999);
});

it('supports a custom date range filter', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    foreach ([0, 3, 10, 40] as $daysAgo) {
        Transaction::factory()->for($user)->create([
            'category_id' => $categories['expense']->id,
            'type' => 'expense',
            'amount' => 100,
            'transaction_date' => today()->subDays($daysAgo)->toDateString(),
        ]);
    }

    $summary = transactionService()->getSummary($user, [
        'period' => 'custom',
        'from' => today()->subDays(5)->toDateString(),
        'to' => today()->toDateString(),
    ]);

    expect($summary['total_expense'])->toBe(200);

    $rows = transactionService()->getFiltered($user, [
        'period' => 'custom',
        'from' => today()->subDays(5)->toDateString(),
        'to' => today()->toDateString(),
    ])->get();

    expect($rows)->toHaveCount(2);
});

it('filters by category, type and description', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 20000,
        'description' => 'Kopi pagi',
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 30000,
        'description' => 'Gaji bulanan',
        'transaction_date' => today()->toDateString(),
    ]);

    $byCategory = transactionService()->getFiltered($user, ['period' => 'month', 'category_id' => $categories['expense']->id])->get();
    $byType = transactionService()->getFiltered($user, ['period' => 'month', 'type' => 'income'])->get();
    $bySearch = transactionService()->getFiltered($user, ['period' => 'month', 'search' => 'kopi'])->get();

    expect($byCategory)->toHaveCount(1)
        ->and($byCategory->first()->description)->toBe('Kopi pagi')
        ->and($byType)->toHaveCount(1)
        ->and($byType->first()->amount)->toBe(30000)
        ->and($bySearch)->toHaveCount(1)
        ->and($bySearch->first()->amount)->toBe(20000);
});

it('never leaks transactions that belong to another user', function () {
    $categories = seedDefaultCategories();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Transaction::factory()->for($userA)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 5000,
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($userB)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 7000000,
        'transaction_date' => today()->toDateString(),
    ]);

    expect(transactionService()->getFiltered($userA, ['period' => 'all'])->get())->toHaveCount(1)
        ->and(transactionService()->getSummary($userA, ['period' => 'all'])['total_expense'])->toBe(5000)
        ->and(transactionService()->getSummary($userB, ['period' => 'all'])['total_expense'])->toBe(7000000);
});

it('returns zeros when there are no transactions', function () {
    $user = User::factory()->create();

    expect(transactionService()->getSummary($user, ['period' => 'month']))->toBe([
        'total_income' => 0,
        'total_expense' => 0,
        'balance' => 0,
    ]);
});

it('ignores soft deleted transactions in the summary', function () {
    $user = User::factory()->create();
    $category = seedDefaultCategories()['expense'];

    $transaction = transactionService()->create($user, [
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 4000,
        'transaction_date' => today()->toDateString(),
    ]);

    transactionService()->delete($transaction);

    expect(transactionService()->getSummary($user, ['period' => 'month'])['total_expense'])->toBe(0);
});
