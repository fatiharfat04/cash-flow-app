<?php

namespace App\Enums;

enum BudgetStatus: string
{
    case Safe = 'safe';
    case Warning = 'warning';
    case Exceeded = 'exceeded';

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Aman',
            self::Warning => 'Waspada',
            self::Exceeded => 'Melebihi',
        };
    }
}
