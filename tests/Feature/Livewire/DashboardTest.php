<?php

use App\Livewire\Dashboard;
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

function dashboardTx(User $user, array $overrides = []): Transaction
{
    return Transaction::create(array_merge([
        'user_id' => $user->id,
        'category_id' => Category::whereNull('user_id')->where('type', 'expense')->firstOrFail()->id,
        'type' => 'expense',
        'amount' => 10000,
        'description' => 'Transaksi umum',
        'transaction_date' => today()->toDateString(),
    ], $overrides));
}

it('renders the dashboard page', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Saldo')
        ->assertSee('Transaksi Terbaru')
        ->assertSee('Status Budget');
});

it('renders without errors when there is no data at all', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('Saldo')
        ->assertSee('Rp0')
        ->assertSee('Belum ada transaksi')
        ->assertSee('Belum ada limit budget bulan ini')
        ->assertSee('Belum ada pengeluaran bulan ini');
});

it('summarises balance and the current month totals', function () {
    dashboardTx($this->user, [
        'type' => 'income',
        'category_id' => $this->categories['income']->id,
        'amount' => 500000,
        'description' => 'Gaji bulanan',
    ]);
    dashboardTx($this->user, ['amount' => 200000, 'description' => 'Belanja bulanan']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('Rp300.000')  // saldo seluruh waktu
        ->assertSee('Rp500.000')  // pemasukan bulan ini
        ->assertSee('Rp200.000')  // pengeluaran bulan ini
        ->assertSee('Gaji bulanan');
});

it('exposes trend and breakdown data to the chart', function () {
    dashboardTx($this->user, ['amount' => 200000, 'description' => 'Transportasi mingguan']);

    $component = Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertDispatched('chart-data-updated');

    // Jembatan Alpine -> Chart.js (§7.5 & §9.5) harus ada di markup
    $component
        ->assertSee('dashboardCharts(', false)
        ->assertSee("JSON.parse('", false)
        ->assertSee('x-on:chart-data-updated.window', false)
        ->assertSee('x-ref="trendCanvas"', false)
        ->assertSee('x-ref="breakdownCanvas"', false);

    $trend = $component->instance()->trendData;
    $breakdown = $component->instance()->breakdownData;

    expect($trend['labels'])->toHaveCount(30)
        ->and($trend['income'])->toHaveCount(30)
        ->and($trend['expense'])->toHaveCount(30)
        ->and($trend['expense'])->toContain(200000)
        ->and($breakdown['labels'])->toContain('Makanan')
        ->and($breakdown['values'])->toContain(200000)
        ->and($breakdown['colors'])->toHaveCount(count($breakdown['labels']));
});

it('lists only the five most recent transactions of the current user', function () {
    foreach (range(1, 6) as $index) {
        dashboardTx($this->user, ['description' => 'Terbaru '.$index]);
    }

    $other = User::factory()->create();
    dashboardTx($other, ['description' => 'Transaksi Orang Lain']);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('Terbaru 6')
        ->assertSee('Terbaru 2')
        ->assertDontSee('Terbaru 1')
        ->assertDontSee('Transaksi Orang Lain');
});

it('does not leak other users data anywhere on the dashboard', function () {
    $other = User::factory()->create();
    dashboardTx($other, ['description' => 'Rahasia Orang Lain']);
    Budget::factory()->for($other)->create([
        'category_id' => Category::factory()->for($other)->create(['type' => 'expense', 'name' => 'Kategori Orang Lain'])->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertDontSee('Rahasia Orang Lain')
        ->assertDontSee('Kategori Orang Lain');
});

it('shows the budget status for the current month', function () {
    Budget::factory()->for($this->user)->create([
        'category_id' => $this->categories['expense']->id,
        'amount_limit' => 500000,
    ]);
    dashboardTx($this->user, ['amount' => 150000]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee($this->categories['expense']->name)
        ->assertSee('Rp150.000 dari Rp500.000')
        ->assertSee('Aman');
});
