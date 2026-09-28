<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(private readonly TransactionService $transactionService)
    {
    }

    /**
     * @return array{balance:int,income_this_month:int,expense_this_month:int}
     */
    public function getSummaryCards(User $user): array
    {
        $monthSummary = $this->transactionService->getSummary($user, ['period' => 'month']);

        return [
            'balance' => $this->transactionService->getBalance($user),
            'income_this_month' => $monthSummary['total_income'],
            'expense_this_month' => $monthSummary['total_expense'],
        ];
    }

    /**
     * @return array{labels:list<string>,income:list<int>,expense:list<int>}
     */
    public function getTrendChartData(User $user, int $days = 30): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = Transaction::query()
            ->forUser($user->id)
            ->betweenDates($start->toDateString(), Carbon::today()->toDateString())
            ->get(['transaction_date', 'type', 'amount']);

        $grouped = $rows->groupBy(function (Transaction $transaction) {
            return Carbon::parse($transaction->transaction_date)->toDateString();
        });

        $labels = $income = $expense = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $dayRows = $grouped->get($date, collect());

            $labels[] = $date;
            $income[] = (int) $dayRows->where('type', TransactionType::Income)->sum('amount');
            $expense[] = (int) $dayRows->where('type', TransactionType::Expense)->sum('amount');
        }

        return [
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense,
        ];
    }

    /**
     * Breakdown pengeluaran per kategori dalam satu bulan.
     *
     * @return array{labels:list<string>,values:list<int>,colors:list<string>}
     */
    public function getCategoryBreakdown(User $user, Carbon $month): array
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        $rows = Transaction::query()
            ->forUser($user->id)
            ->ofType('expense')
            ->betweenDates($start->toDateString(), $end->toDateString())
            ->with('category')
            ->get();

        $grouped = $rows->groupBy(function (Transaction $transaction) {
            return $transaction->category?->name ?? 'Tanpa Kategori';
        });

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($grouped->sortByDesc(fn ($collection) => $collection->sum('amount')) as $label => $collection) {
            $labels[] = (string) $label;
            $values[] = (int) $collection->sum('amount');
            $colors[] = $collection->first()->category?->color ?? '#B85C4A';
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
        ];
    }

    public function getRecentTransactions(User $user, int $limit = 5): Collection
    {
        return Transaction::query()
            ->forUser($user->id)
            ->with('category')
            ->latest('transaction_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
