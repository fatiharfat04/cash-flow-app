<?php

use App\Livewire\Categories\CategoryManager;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

it('renders the category page with the seeded categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    seedDefaultCategories();

    $this->get(route('categories.index'))
        ->assertOk()
        ->assertSee('Kategori')
        ->assertSee('Makanan')
        ->assertSee('Gaji');
});

it('creates a custom category owned by the current user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->set('name', 'Kopi')
        ->set('type', 'expense')
        ->set('icon', 'tag')
        ->set('color', '#B85C4A')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('noticeType', 'success')
        ->assertSet('editingId', null);

    expect(Category::where('name', 'Kopi')->first()->user_id)->toBe($user->id);
});

it('validates the required fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->set('name', '')
        ->set('color', 'biru')
        ->call('save')
        ->assertHasErrors([
            'name' => 'required',
            'color' => 'regex',
        ]);
});

it('rejects a duplicate category name for the same type and user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Category::create([
        'user_id' => $user->id,
        'name' => 'Kopi',
        'type' => 'expense',
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->set('name', 'Kopi')
        ->set('type', 'expense')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('fills the form when editing an owned category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Kopi',
        'type' => 'expense',
        'icon' => 'wallet',
        'color' => '#4A7C59',
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('edit', $category->id)
        ->assertSet('editingId', $category->id)
        ->assertSet('name', 'Kopi')
        ->assertSet('icon', 'wallet')
        ->assertSet('color', '#4A7C59');
});

it('refuses to edit a default category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    seedDefaultCategories();

    $default = Category::whereNull('user_id')->firstOrFail();

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('edit', $default->id)
        ->assertSet('editingId', null)
        ->assertSet('noticeType', 'error');
});

it('refuses to edit another users category', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user);

    $otherCategory = Category::create([
        'user_id' => $other->id,
        'name' => 'Milik Orang',
        'type' => 'expense',
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('edit', $otherCategory->id)
        ->assertSet('editingId', null)
        ->assertSet('noticeType', 'error');
});

it('ignores a forged editingId and never over someone else category', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user);

    $otherCategory = Category::create([
        'user_id' => $other->id,
        'name' => 'Milik Orang',
        'type' => 'expense',
        'color' => '#D9BC7C',
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->set('editingId', $otherCategory->id)
        ->set('name', 'Dicuri')
        ->set('type', 'expense')
        ->call('save')
        ->assertSet('noticeType', 'error');

    expect($otherCategory->fresh()->name)->toBe('Milik Orang');
});

it('ignores a forged editingId pointing at a default category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    seedDefaultCategories();

    $default = Category::whereNull('user_id')->where('name', 'Makanan')->firstOrFail();

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->set('editingId', $default->id)
        ->set('name', 'Dicuri')
        ->set('type', 'expense')
        ->call('save')
        ->assertSet('noticeType', 'error');

    expect($default->fresh()->name)->toBe('Makanan');
});

it('cannot delete a default category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    seedDefaultCategories();

    $default = Category::whereNull('user_id')->firstOrFail();

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('delete', $default->id)
        ->assertSet('noticeType', 'error');

    expect(Category::find($default->id))->not->toBeNull();
});

it('cannot delete a category that is still used by a transaction', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Kopi',
        'type' => 'expense',
    ]);

    $user->transactions()->create([
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 25000,
        'transaction_date' => today()->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('delete', $category->id)
        ->assertSet('noticeType', 'error');

    expect(Category::find($category->id))->not->toBeNull();
});

it('deletes an unused custom category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Kopi',
        'type' => 'expense',
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('delete', $category->id)
        ->assertSet('noticeType', 'success');

    expect(Category::find($category->id))->toBeNull();
});

it('updates an owned category through the form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Kopi',
        'type' => 'expense',
    ]);

    Livewire::actingAs($user)
        ->test(CategoryManager::class)
        ->call('edit', $category->id)
        ->set('name', 'Kopi Susu')
        ->set('color', '#4A7C59')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('noticeType', 'success')
        ->assertSet('editingId', null);

    expect($category->fresh()->name)->toBe('Kopi Susu')
        ->and($category->fresh()->color)->toBe('#4A7C59');
});
