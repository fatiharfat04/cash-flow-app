<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Pemasukan',
            self::Expense => 'Pengeluaran',
        };
    }

    public function colorToken(): string
    {
        return match ($this) {
            self::Income => 'income',
            self::Expense => 'expense',
        };
    }
}
