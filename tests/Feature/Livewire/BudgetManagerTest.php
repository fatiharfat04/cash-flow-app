<?php

use App\Livewire\Budgets\BudgetManager;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->categories = seedDefaultCategories();
});

function budgetSpent(User $user, Category $category, int $amount, ?string $date = null): Transaction
{
    return Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => $amount,
        'transaction_date' => $date ?? today()->toDateString(),
    ]);
}

it('renders the budgets page', function () {
    $this->get(route('budgets.index'))
        ->assertOk()
        ->assertSee('Budget')
        ->assertSee('Limit per Kategori');
});

it('only lists expense categories visible to the current user', function () {
    $other = User::factory()->create();
    Category::factory()->for($other)->create(['type' => 'expense', 'name' => 'Kategori Orang Lain']);

    $component = Livewire::actingAs($this->user)->test(BudgetManager::class);

    $names = $component->instance()->categories->pluck('name')->all();

    expect($names)->toContain('Makanan')
        ->and($names)->not->toContain('Gaji')
        ->and($names)->not->toContain('Kategori Orang Lain');
});

it('shows a category that has no limit yet', function () {
    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->assertSee('Makanan')
        ->assertSee('Belum ada limit untuk bulan ini')
        ->assertDontSee('% terpakai');
});

it('saves a limit for the current month', function () {
    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $this->categories['expense']->id, 500000)
        ->assertSet('noticeType', 'success')
        ->assertSee('Aman')
        ->assertSee('Rp500.000');

    $budget = Budget::sole();

    expect($budget->amount_limit)->toBe(500000)
        ->and($budget->user_id)->toBe($this->user->id)
        ->and($budget->month->toDateString())->toBe(now()->startOfMonth()->toDateString());
});

it('updates an existing limit instead of duplicating it', function () {
    $categoryId = $this->categories['expense']->id;

    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $categoryId, 500000)
        ->call('saveBudget', $categoryId, 750000);

    expect(Budget::count())->toBe(1)
        ->and(Budget::sole()->amount_limit)->toBe(750000);
});

it('keeps a separate limit for every month', function () {
    $categoryId = $this->categories['expense']->id;

    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $categoryId, 500000)
        ->call('nextMonth')
        ->call('saveBudget', $categoryId, 300000);

    expect(Budget::count())->toBe(2)
        ->and(Budget::pluck('amount_limit')->sort()->values()->all())->toBe([300000, 500000]);
});

it('reports a safe status while spending stays under the limit', function () {
    $category = $this->categories['expense'];
    budgetSpent($this->user, $category, 100000);

    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $category->id, 1000000)
        ->assertSee('Aman')
        ->assertSee('10,00% terpakai')
        ->assertSee('Rp100.000');
});

it('reports a warning status at 80 percent used', function () {
    $category = $this->categories['expense'];
    budgetSpent($this->user, $category, 450000);

    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $category->id, 500000)
        ->assertSee('Waspada')
        ->assertSee('90,00% terpakai')
        ->assertSee('Rp450.000')
        ->assertDontSee('Aman');
});

it('reports an exceeded status when spending passes the limit', function () {
    $category = $this->categories['expense'];
    budgetSpent($this->user, $category, 150000);

    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $category->id, 100000)
        ->assertSee('Melebihi')
        ->assertSee('150,00% terpakai')
        ->assertSee('Rp150.000');
});

it('rejects a limit below one rupiah', function () {
    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $this->categories['expense']->id, 0)
        ->assertSet('noticeType', 'error');

    expect(Budget::count())->toBe(0);
});

it('rejects a budget on another users category', function () {
    $other = User::factory()->create();
    $otherCategory = Category::factory()->for($other)->create(['type' => 'expense']);

    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $otherCategory->id, 100000)
        ->assertSet('noticeType', 'error');

    expect(Budget::count())->toBe(0);
});

it('rejects a budget on an income category', function () {
    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $this->categories['income']->id, 100000)
        ->assertSet('noticeType', 'error');

    expect(Budget::count())->toBe(0);
});

it('navigates forward and reloads the limits for that month', function () {
    $categoryId = $this->categories['expense']->id;

    $component = Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('saveBudget', $categoryId, 500000);

    expect((int) ($component->instance()->limits[$categoryId] ?? 0))->toBe(500000);

    $component->call('nextMonth')->assertSet(
        'month',
        fn ($month) => $month->format('Y-m-d') === now()->startOfMonth()->addMonthNoOverflow()->format('Y-m-d')
    );

    expect($component->instance()->limits)->toBeEmpty()
        ->and($component->instance()->budgetStatus)->toBeEmpty();
});

it('navigates back to the previous month', function () {
    Livewire::actingAs($this->user)
        ->test(BudgetManager::class)
        ->call('previousMonth')
        ->assertSet(
            'month',
            fn ($month) => $month->format('Y-m-d') === now()->startOfMonth()->subMonthNoOverflow()->format('Y-m-d')
        )
        ->assertSee(now()->startOfMonth()->subMonthNoOverflow()->translatedFormat('F Y'));
});
