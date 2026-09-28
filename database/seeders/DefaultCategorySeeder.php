<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    /**
     * Kategori default (user_id = null) — dipakai oleh semua user.
     *
     * @var list<array{name:string,type:string,icon:string,color:string}>
     */
    private array $defaults = [
        ['name' => 'Gaji',          'type' => 'income',  'icon' => 'banknotes',      'color' => '#4A7C59'],
        ['name' => 'Bonus/Lainnya', 'type' => 'income',  'icon' => 'gift',           'color' => '#4A7C59'],
        ['name' => 'Makanan',       'type' => 'expense', 'icon' => 'shopping-cart',  'color' => '#B85C4A'],
        ['name' => 'Transportasi',  'type' => 'expense', 'icon' => 'truck',          'color' => '#B85C4A'],
        ['name' => 'Hiburan',       'type' => 'expense', 'icon' => 'film',           'color' => '#B85C4A'],
        ['name' => 'Tagihan',       'type' => 'expense', 'icon' => 'document-text',  'color' => '#B85C4A'],
    ];

    public function run(): void
    {
        foreach ($this->defaults as $category) {
            Category::firstOrCreate([
                'user_id' => null,
                'name' => $category['name'],
                'type' => $category['type'],
            ], [
                'icon' => $category['icon'],
                'color' => $category['color'],
            ]);
        }
    }
}
