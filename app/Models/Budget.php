<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category_id', 'month', 'amount_limit'];

    protected $casts = ['month' => 'date', 'amount_limit' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeForMonth(Builder $q, Carbon $month): Builder
    {
        return $q->whereYear('month', $month->year)->whereMonth('month', $month->month);
    }
}
