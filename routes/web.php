<?php

use App\Livewire\Categories\CategoryManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Akan berubah jadi Livewire component di Fase 7 (lihat §8 PROJECT.md)
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('/categories', CategoryManager::class)->name('categories.index');
});

Route::view('/', 'welcome');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
