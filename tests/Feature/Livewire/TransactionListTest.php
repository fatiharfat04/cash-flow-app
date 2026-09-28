<?php

use App\Livewire\Transactions\TransactionList;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->categories = seedDefaultCategories();
});

function listTransaction(User $user, array $overrides = []): Transaction
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

it('renders the transactions page', function () {
    $this->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('Transaksi')
        ->assertSee('Tambah Transaksi');
});

it('lists only the current users transactions', function () {
    $other = User::factory()->create();

    listTransaction($this->user, ['description' => 'Transaksi Saya']);
    listTransaction($other, ['description' => 'Transaksi Orang Lain']);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->assertSee('Transaksi Saya')
        ->assertDontSee('Transaksi Orang Lain');
});

it('shows an empty state when nothing matches', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->assertSee('Belum ada transaksi untuk filter ini');
});

it('summarises income, expense and balance for the active filter', function () {
    listTransaction($this->user, [
        'type' => 'income',
        'category_id' => $this->categories['income']->id,
        'amount' => 500000,
        'description' => 'Gaji bulanan',
    ]);
    listTransaction($this->user, ['amount' => 200000, 'description' => 'Belanja']);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->assertSee('Rp500.000')
        ->assertSee('Rp200.000')
        ->assertSee('Rp300.000');
});

it('filters by period today', function () {
    listTransaction($this->user, ['description' => 'Transaksi Hari Ini']);
    listTransaction($this->user, [
        'description' => 'Transaksi Tiga Hari Lalu',
        'transaction_date' => now()->subDays(3)->toDateString(),
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('period', 'today')
        ->call('applyFilter')
        ->assertSee('Transaksi Hari Ini')
        ->assertDontSee('Transaksi Tiga Hari Lalu');
});

it('filters by period month', function () {
    listTransaction($this->user, ['description' => 'Transaksi Bulan Ini']);
    listTransaction($this->user, [
        'description' => 'Transaksi Bulan Lalu',
        'transaction_date' => now()->subMonthNoOverflow()->toDateString(),
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->call('applyFilter')
        ->assertSee('Transaksi Bulan Ini')
        ->assertDontSee('Transaksi Bulan Lalu');
});

it('filters by a custom date range', function () {
    listTransaction($this->user, [
        'description' => 'Transaksi Terpilih',
        'transaction_date' => now()->subDays(2)->toDateString(),
    ]);
    listTransaction($this->user, [
        'description' => 'Transaksi Di Luar Rentang',
        'transaction_date' => now()->subDays(40)->toDateString(),
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('period', 'custom')
        ->set('dateFrom', now()->subDays(5)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->call('applyFilter')
        ->assertHasNoErrors()
        ->assertSee('Transaksi Terpilih')
        ->assertDontSee('Transaksi Di Luar Rentang');
});

it('requires a valid custom date range', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('period', 'custom')
        ->set('dateFrom', now()->toDateString())
        ->set('dateTo', now()->subDay()->toDateString())
        ->call('applyFilter')
        ->assertHasErrors(['dateTo' => 'after_or_equal']);
});

it('requires both custom dates before the filter is applied', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('period', 'custom')
        ->call('applyFilter')
        ->assertHasErrors(['dateFrom' => 'required', 'dateTo' => 'required']);
});

it('clears the category filter when the empty option is chosen again', function () {
    $transport = Category::whereNull('user_id')->where('name', 'Transportasi')->firstOrFail();
    listTransaction($this->user, ['category_id' => $transport->id, 'description' => 'Taksi ke kantor']);
    listTransaction($this->user);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('categoryFilter', $transport->id)
        ->call('applyFilter')
        ->assertSet('categoryFilter', $transport->id)
        ->set('categoryFilter', '')
        ->assertSet('categoryFilter', null)
        ->call('applyFilter')
        ->assertHasNoErrors()
        ->assertSee('Taksi ke kantor')
        ->assertSee('Transaksi umum');
});

it('rejects a period outside the supported values', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('period', 'all-time')
        ->call('applyFilter')
        ->assertHasErrors(['period' => 'in']);
});

it('filters by category and by type', function () {
    $transport = Category::whereNull('user_id')->where('name', 'Transportasi')->firstOrFail();

    listTransaction($this->user, ['category_id' => $transport->id, 'description' => 'Taksi ke kantor']);
    listTransaction($this->user);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('categoryFilter', $transport->id)
        ->call('applyFilter')
        ->assertSee('Taksi ke kantor')
        ->assertDontSee('Transaksi umum')
        ->call('resetFilter')
        ->assertSee('Transaksi umum');
});

it('filters transactions by type', function () {
    listTransaction($this->user);
    listTransaction($this->user, [
        'type' => 'income',
        'category_id' => $this->categories['income']->id,
        'description' => 'Pemasukan Terfilter',
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->set('typeFilter', 'income')
        ->call('applyFilter')
        ->assertSee('Pemasukan Terfilter')
        ->assertDontSee('Transaksi umum');
});

it('only offers categories that are visible to the current user', function () {
    $other = User::factory()->create();
    Category::factory()->for($other)->create(['type' => 'expense', 'name' => 'Kategori Orang Lain']);

    $names = Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->instance()
        ->categories
        ->pluck('name')
        ->all();

    expect($names)->toContain('Makanan')
        ->and($names)->not->toContain('Kategori Orang Lain');
});

it('paginates the list', function () {
    foreach (range(1, 12) as $index) {
        listTransaction($this->user, ['description' => 'Transaksi '.str_pad($index, 2, '0', STR_PAD_LEFT)]);
    }

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->assertSee('12 data')
        ->assertSee('Transaksi 12')
        ->assertDontSee('Transaksi 01')
        ->call('nextPage')
        ->assertSee('Transaksi 01')
        ->assertDontSee('Transaksi 12');
});

it('deletes an owned transaction', function () {
    $transaction = listTransaction($this->user);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->call('delete', $transaction->id)
        ->assertSet('noticeType', 'success');

    expect(Transaction::find($transaction->id))->toBeNull();
});

it('refuses to delete another users transaction', function () {
    $other = User::factory()->create();
    $transaction = listTransaction($other);

    Livewire::actingAs($this->user)
        ->test(TransactionList::class)
        ->call('delete', $transaction->id)
        ->assertSet('noticeType', 'error');

    expect(Transaction::find($transaction->id))->not->toBeNull();
});
