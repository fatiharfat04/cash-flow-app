<?php

namespace App\Services;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BudgetService
{
    /**
     * Upsert limit budget untuk satu kategori pada bulan tertentu.
     */
    public function setBudget(User $user, int $categoryId, Carbon $month, int $amountLimit): Budget
    {
        $normalizedMonth = Carbon::parse($month)->startOfMonth();

        $budget = Budget::firstOrNew([
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'month' => $normalizedMonth->toDateString(),
        ]);

        $budget->amount_limit = $amountLimit;
        $budget->save();

        return $budget;
    }

    /**
     * Status budget seluruh kategori expense pada bulan berjalan.
     *
     * @return Collection<int, array{category: Category, limit: int, spent: int, percentage: float, status: BudgetStatus}>
     */
    public function getBudgetStatus(User $user, Carbon $month): Collection
    {
        $normalizedMonth = Carbon::parse($month)->startOfMonth();
        $start = $normalizedMonth->copy()->startOfMonth();
        $end = $normalizedMonth->copy()->endOfMonth();

        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->whereBetween('month', [$start->toDateString(), $end->toDateString()])
            ->with('category')
            ->get()
            ->keyBy('category_id');

        $spentPerCategory = $user->transactions()
            ->ofType('expense')
            ->betweenDates($start->toDateString(), $end->toDateString())
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets->map(function (Budget $budget) use ($spentPerCategory) {
            $limit = (int) $budget->amount_limit;
            $spent = (int) ($spentPerCategory[$budget->category_id] ?? 0);
            $percentage = $limit > 0 ? round(($spent / $limit) * 100, 2) : 0.0;

            $status = match (true) {
                $percentage >= 100 => BudgetStatus::Exceeded,
                $percentage >= 80 => BudgetStatus::Warning,
                default => BudgetStatus::Safe,
            };

            return [
                'category' => $budget->category,
                'limit' => $limit,
                'spent' => $spent,
                'percentage' => $percentage,
                'status' => $status,
            ];
        })->values();
    }
}
