<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * FK `transactions.category_id` memakai RESTRICT (transaksi tidak boleh
     * ikut terhapus saat kategori dihapus). Saat user dihapus, cascade MySQL
     * ke tabel `categories` bisa dieksekusi sebelum cascade ke `transactions`
     * sehingga melanggar RESTRICT dan menggagalkan penghapusan akun.
     *
     * Karena itu transaksi milik user dihapus lebih dulu di sini.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->transactions()->withTrashed()->forceDelete();
            $user->budgets()->delete();
            $user->customCategories()->forceDelete();
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function customCategories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
