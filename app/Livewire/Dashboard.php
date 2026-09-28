<?php

namespace App\Livewire;

use App\Services\BudgetService;
use App\Services\DashboardService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Dashboard utama (§7.5 PROJECT.md).
 *
 * Semua angka & data chart diambil dari DashboardService/BudgetService —
 * component ini hanya memanggil, tidak pernah menghitung (rule #7 & #15).
 */
#[Layout('layouts.app')]
#[Title('Dashboard — Cash Flow')]
class Dashboard extends Component
{
    public function getSummaryProperty(): array
    {
        return app(DashboardService::class)->getSummaryCards(auth()->user());
    }

    public function getTrendDataProperty(): array
    {
        return app(DashboardService::class)->getTrendChartData(auth()->user());
    }

    public function getBreakdownDataProperty(): array
    {
        return app(DashboardService::class)->getCategoryBreakdown(auth()->user(), now());
    }

    public function getRecentTransactionsProperty(): Collection
    {
        return app(DashboardService::class)->getRecentTransactions(auth()->user());
    }

    public function getBudgetStatusProperty(): Collection
    {
        return app(BudgetService::class)->getBudgetStatus(auth()->user(), now());
    }

    public function render()
    {
        // Dijembatani ke Chart.js lewat Alpine (§7.5 & §9.5)
        $this->dispatch(
            'chart-data-updated',
            trend: $this->trendData,
            breakdown: $this->breakdownData
        );

        return view('livewire.dashboard');
    }
}
