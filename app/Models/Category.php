<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'type', 'icon', 'color'];

    protected $casts = ['type' => TransactionType::class];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function isDefault(): bool
    {
        return is_null($this->user_id);
    }

    public function scopeVisibleTo(Builder $q, int $userId): Builder
    {
        return $q->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $userId));
    }

    public function scopeOfType(Builder $q, TransactionType|string $type): Builder
    {
        return $q->where('type', $type instanceof TransactionType ? $type->value : $type);
    }
}
