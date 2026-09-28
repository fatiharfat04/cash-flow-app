<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Catatan: saat dipakai, user_id/category_id sebaiknya diisi eksplisit
     * (mis. ->for($user) dan ['category_id' => $category->id]) supaya konsisten.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => 'expense',
            'amount' => fake()->numberBetween(1000, 500000),
            'description' => fake()->sentence(4),
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }

    public function on(string $date): static
    {
        return $this->state(fn () => ['transaction_date' => $date]);
    }
}
