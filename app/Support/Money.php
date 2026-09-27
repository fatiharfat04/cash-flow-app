<?php

namespace App\Support;

/**
 * Pembentukan tampilan rupiah saja (bukan kalkulasi finansial).
 * Semua perhitungan uang tetap berada di Service Layer (lihat §6 PROJECT.md).
 */
final class Money
{
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    public static function formatRupiah(int $amount): string
    {
        return static::format($amount);
    }
}
