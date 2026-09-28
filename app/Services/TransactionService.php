<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TransactionService
{
    public function create(User $user, array $data): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'type' => TransactionType::from($data['type']),
            'amount' => (int) $data['amount'],
            'description' => $data['description'] ?? null,
            'transaction_date' => $data['transaction_date'],
        ]);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update([
            'category_id' => $data['category_id'],
            'type' => TransactionType::from($data['type']),
            'amount' => (int) $data['amount'],
            'description' => $data['description'] ?? null,
            'transaction_date' => $data['transaction_date'],
        ]);

        return $transaction->refresh();
    }

    public function delete(Transaction $transaction): bool
    {
        return (bool) $transaction->delete();
    }

    /**
     * Bangun query transaksi milik user sesuai filter.
     *
     * Supported filters:
     * - period: 'today' | 'week' | 'month' | 'all' | 'custom' (default 'month')
     * - from / to: tanggal custom (dipakai saat period = custom)
     * - category_id
     * - type: 'income' | 'expense'
     * - search: pencarian deskripsi
     */
    public function getFiltered(User $user, array $filters): Builder
    {
        $query = $this
            ->applyFilters(Transaction::query()->forUser($user->id)->with('category'), $filters)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        return $query;
    }

    /**
     * @return array{total_income:int,total_expense:int,balance:int}
     */
    public function getSummary(User $user, array $filters): array
    {
        $rows = $this
            ->applyFilters(Transaction::query()->forUser($user->id), $filters)
            ->selectRaw('type, COALESCE(SUM(amount), 0) as total')
            ->groupBy('type')
            ->get();

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($rows as $row) {
            if ($row->type === TransactionType::Income) {
                $totalIncome = (int) $row->total;
            } elseif ($row->type === TransactionType::Expense) {
                $totalExpense = (int) $row->total;
            }
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Total saldo seluruh waktu (tanpa filter periode).
     */
    public function getBalance(User $user): int
    {
        return $this->getSummary($user, ['period' => 'all'])['balance'];
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $period = $filters['period'] ?? 'month';

        match ($period) {
            'today' => $query->today(),
            'week' => $query->thisWeek(),
            'month' => $query->thisMonth(),
            'all' => $query,
            default => $query->betweenDates(
                $filters['from'] ?? now()->startOfMonth()->toDateString(),
                $filters['to'] ?? now()->endOfMonth()->toDateString()
            ),
        };

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (! empty($filters['search'])) {
            $query->where('description', 'like', '%'.$filters['search'].'%');
        }

        return $query;
    }
}
