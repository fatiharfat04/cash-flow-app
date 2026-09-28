<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CategoryService
{
    public function createCustom(User $user, array $data): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'type' => TransactionType::from($data['type']),
            'icon' => $data['icon'] ?? 'tag',
            'color' => $data['color'] ?? '#D9BC7C',
        ]);
    }

    /**
     * Update kategori kustom milik user yang login.
     * Kategori default (user_id null) tidak boleh diubah oleh siapa pun.
     */
    public function update(Category $category, array $data): Category
    {
        if ($category->isDefault() || $category->user_id !== auth()->id()) {
            throw new AuthorizationException('Anda tidak punya akses untuk mengubah kategori ini.');
        }

        $category->update([
            'name' => $data['name'],
            'type' => TransactionType::from($data['type']),
            'icon' => $data['icon'] ?? $category->icon,
            'color' => $data['color'] ?? $category->color,
        ]);

        return $category->refresh();
    }

    /**
     * Hapus kategori kustom. Kategori yang masih dipakai transaksi tidak boleh
     * dihapus (FK restrictOnDelete) — dicek dulu supaya pesannya jelas.
     */
    public function delete(Category $category): bool
    {
        if ($category->isDefault() || $category->user_id !== auth()->id()) {
            throw new AuthorizationException('Anda tidak punya akses untuk menghapus kategori ini.');
        }

        $inUse = $category->transactions()->withTrashed()->exists();

        if ($inUse) {
            throw new \Exception(
                'Kategori "'.$category->name.'" masih digunakan oleh transaksi dan tidak dapat dihapus. '
                .'Pindahkan atau hapus transaksi tersebut terlebih dahulu.'
            );
        }

        return (bool) $category->delete();
    }

    /**
     * @return Collection<int, Category>
     */
    public function getVisibleForUser(User $user, ?TransactionType $type = null): Collection
    {
        return Category::query()
            ->visibleTo($user->id)
            ->when($type, fn (Builder $query) => $query->ofType($type))
            ->orderBy('name')
            ->get();
    }
}
