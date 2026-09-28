<?php

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardService(): DashboardService
{
    return app(DashboardService::class);
}

it('builds summary cards with balance, monthly income and monthly expense', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 5000000,
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 1500000,
        'transaction_date' => today()->toDateString(),
    ]);

    // Bulan lalu tidak ikut hitungan bulan berjalan, tapi tetap masuk saldo.
    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 500000,
        'transaction_date' => today()->subMonth()->toDateString(),
    ]);

    $cards = dashboardService()->getSummaryCards($user);

    expect($cards['income_this_month'])->toBe(5000000)
        ->and($cards['expense_this_month'])->toBe(1500000)
        ->and($cards['balance'])->toBe(3000000);
});

it('shapes the trend chart data for the requested number of days', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 200000,
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 50000,
        'transaction_date' => today()->subDays(2)->toDateString(),
    ]);

    $trend = dashboardService()->getTrendChartData($user, 7);

    expect($trend['labels'])->toHaveCount(7)
        ->and($trend['income'])->toHaveCount(7)
        ->and($trend['expense'])->toHaveCount(7)
        ->and($trend['labels'])->toBe(collect(range(0, 6))->map(fn ($i) => today()->subDays(6 - $i)->toDateString())->all())
        ->and($trend['labels'][6])->toBe(today()->toDateString())
        ->and($trend['income'][6])->toBe(200000)
        ->and($trend['income'][0])->toBe(0)
        ->and($trend['expense'][4])->toBe(50000);
});

it('does not leak other users into the trend chart', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($userB)->create([
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 999999,
        'transaction_date' => today()->toDateString(),
    ]);

    $trend = dashboardService()->getTrendChartData($userA, 7);

    expect(array_sum($trend['income']))->toBe(0);
});

it('breaks down monthly expenses per category with their colours', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 120000,
        'transaction_date' => today()->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['income']->id,
        'type' => 'income',
        'amount' => 5000000,
        'transaction_date' => today()->toDateString(),
    ]);

    $breakdown = dashboardService()->getCategoryBreakdown($user, today());

    expect($breakdown['labels'])->toBe(['Makanan'])
        ->and($breakdown['values'])->toBe([120000])
        ->and($breakdown['colors'])->toBe(['#B85C4A']);
});

it('returns the most recent transactions up to the limit', function () {
    $user = User::factory()->create();
    $categories = seedDefaultCategories();

    Transaction::factory()->for($user)->count(7)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 1000,
        'transaction_date' => today()->subDays(3)->toDateString(),
    ]);

    Transaction::factory()->for($user)->create([
        'category_id' => $categories['expense']->id,
        'type' => 'expense',
        'amount' => 9999,
        'transaction_date' => today()->toDateString(),
    ]);

    $recent = dashboardService()->getRecentTransactions($user, 5);

    expect($recent)->toHaveCount(5)
        ->and($recent->first()->amount)->toBe(9999)
        ->and($recent->first()->category)->toBeInstanceOf(Category::class);
});

it('renders with zero data', function () {
    $user = User::factory()->create();

    $cards = dashboardService()->getSummaryCards($user);
    $trend = dashboardService()->getTrendChartData($user, 30);
    $breakdown = dashboardService()->getCategoryBreakdown($user, today());
    $recent = dashboardService()->getRecentTransactions($user);

    expect($cards['balance'])->toBe(0)
        ->and(array_sum($trend['income']))->toBe(0)
        ->and($breakdown['labels'])->toBe([])
        ->and($recent)->toHaveCount(0);
});
