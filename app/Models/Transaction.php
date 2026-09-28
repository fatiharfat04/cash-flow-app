<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['user_id', 'category_id', 'type', 'amount', 'description', 'transaction_date'];

    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'integer',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Query Scopes — WAJIB dipakai, jangan tulis ulang logic tanggal di component
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeToday(Builder $q): Builder
    {
        return $q->whereDate('transaction_date', today());
    }

    public function scopeThisWeek(Builder $q): Builder
    {
        return $q->whereBetween('transaction_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
    }

    public function scopeThisMonth(Builder $q): Builder
    {
        return $q->whereBetween('transaction_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
    }

    public function scopeBetweenDates(Builder $q, string $from, string $to): Builder
    {
        return $q->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeOfType(Builder $q, TransactionType|string $type): Builder
    {
        return $q->where('type', $type instanceof TransactionType ? $type->value : $type);
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp'.number_format($this->amount, 0, ',', '.');
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderByDesc('transaction_date')->orderByDesc('id');
    }
}
