<?php

use App\Livewire\Transactions\TransactionForm;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->categories = seedDefaultCategories();
});

function filledForm(User $user, array $overrides = []): array
{
    return array_merge([
        'type' => 'expense',
        'category_id' => Category::whereNull('user_id')->where('type', 'expense')->firstOrFail()->id,
        'amount' => 150000,
        'description' => 'Makan siang',
        'transaction_date' => today()->toDateString(),
    ], $overrides);
}

it('defaults the form to today for a new transaction', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->assertSet('transaction', null)
        ->assertSet('type', 'expense')
        ->assertSet('open', false)
        ->assertSet('transaction_date', today()->toDateString());
});

it('creates a transaction owned by the current user', function () {
    $payload = filledForm($this->user);

    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->call('openCreate')
        ->assertSet('open', true)
        ->set('type', $payload['type'])
        ->set('category_id', $payload['category_id'])
        ->set('amount', $payload['amount'])
        ->set('description', $payload['description'])
        ->set('transaction_date', $payload['transaction_date'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('transaction-saved')
        ->assertSet('open', false);

    $transaction = Transaction::sole();

    expect($transaction->user_id)->toBe($this->user->id)
        ->and($transaction->amount)->toBe(150000)
        ->and($transaction->type->value)->toBe('expense')
        ->and($transaction->description)->toBe('Makan siang');
});

it('requires an amount', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $this->categories['expense']->id)
        ->set('amount', '')
        ->call('save')
        ->assertHasErrors(['amount' => 'required']);
});

it('rejects an amount of zero', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $this->categories['expense']->id)
        ->set('amount', 0)
        ->call('save')
        ->assertHasErrors(['amount' => 'min']);

    expect(Transaction::count())->toBe(0);
});

it('rejects a description longer than 500 characters', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $this->categories['expense']->id)
        ->set('amount', 1000)
        ->set('description', str_repeat('a', 501))
        ->call('save')
        ->assertHasErrors(['description' => 'max']);
});

it('rejects a transaction dated in the future', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $this->categories['expense']->id)
        ->set('amount', 1000)
        ->set('transaction_date', now()->addDay()->toDateString())
        ->call('save')
        ->assertHasErrors(['transaction_date' => 'before_or_equal']);

    expect(Transaction::count())->toBe(0);
});

it('stores an empty description as null', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $this->categories['expense']->id)
        ->set('amount', 25000)
        ->set('description', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::sole()->description)->toBeNull();
});

it('requires a category', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('amount', 1000)
        ->call('save')
        ->assertHasErrors(['category_id' => 'required']);
});

it('rejects a category that belongs to another user', function () {
    $other = User::factory()->create();
    $otherCategory = Category::factory()->for($other)->create(['type' => 'expense']);

    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $otherCategory->id)
        ->set('amount', 1000)
        ->call('save')
        ->assertHasErrors(['category_id']);

    expect(Transaction::count())->toBe(0);
});

it('rejects a category whose type does not match the transaction type', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('type', 'income')
        ->set('category_id', $this->categories['expense']->id)
        ->set('amount', 1000)
        ->call('save')
        ->assertHasErrors(['category_id']);

    expect(Transaction::count())->toBe(0);
});

it('resets the selected category when the type changes', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('category_id', $this->categories['expense']->id)
        ->set('type', 'income')
        ->assertSet('category_id', null)
        ->assertHasNoErrors(['category_id']);
});

it('only offers the categories matching the selected type', function () {
    $custom = Category::factory()->for($this->user)->create(['type' => 'income', 'name' => 'Bonus']);

    $names = Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('type', 'income')
        ->instance()
        ->categories
        ->pluck('name')
        ->all();

    expect($names)->toContain('Gaji', 'Bonus')
        ->and($names)->not->toContain('Makanan')
        ->and($names)->toContain($custom->name);
});

it('fills the form when editing an owned transaction', function () {
    $category = $this->categories['expense'];
    $transaction = Transaction::create([
        'user_id' => $this->user->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 75000,
        'description' => 'Kopi',
        'transaction_date' => today()->toDateString(),
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->call('openEdit', $transaction->id)
        ->assertSet('open', true)
        ->assertSet('type', 'expense')
        ->assertSet('category_id', $category->id)
        ->assertSet('amount', 75000)
        ->assertSet('description', 'Kopi');
});

it('updates an owned transaction', function () {
    $transaction = Transaction::create([
        'user_id' => $this->user->id,
        'category_id' => $this->categories['expense']->id,
        'type' => 'expense',
        'amount' => 75000,
        'description' => 'Kopi',
        'transaction_date' => today()->toDateString(),
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->call('openEdit', $transaction->id)
        ->set('amount', 90000)
        ->set('description', 'Kopi susu')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('transaction-saved');

    expect($transaction->fresh()->amount)->toBe(90000)
        ->and($transaction->fresh()->description)->toBe('Kopi susu')
        ->and(Transaction::count())->toBe(1);
});

it('refuses to edit another users transaction', function () {
    $other = User::factory()->create();
    $otherTransaction = Transaction::create([
        'user_id' => $other->id,
        'category_id' => $this->categories['expense']->id,
        'type' => 'expense',
        'amount' => 10000,
        'transaction_date' => today()->toDateString(),
    ]);

    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->call('openEdit', $otherTransaction->id)
        ->assertSet('open', false)
        ->assertSet('noticeType', 'error');
});

it('does not let the client swap the transaction being edited', function () {
    $other = User::factory()->create();
    $otherTransaction = Transaction::create([
        'user_id' => $other->id,
        'category_id' => $this->categories['expense']->id,
        'type' => 'expense',
        'amount' => 10000,
        'transaction_date' => today()->toDateString(),
    ]);

    expect(fn () => Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->set('transaction', $otherTransaction->id)
    )->toThrow(CannotUpdateLockedPropertyException::class);
});

it('closes the form without saving', function () {
    Livewire::actingAs($this->user)
        ->test(TransactionForm::class)
        ->call('openCreate')
        ->set('amount', 50000)
        ->call('close')
        ->assertSet('open', false)
        ->assertSet('amount', null);

    expect(Transaction::count())->toBe(0);
});
