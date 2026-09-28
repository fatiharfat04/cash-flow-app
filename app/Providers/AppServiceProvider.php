<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Policies\BudgetPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policy registration (§8 PROJECT.md)
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Budget::class, BudgetPolicy::class);
    }
}
