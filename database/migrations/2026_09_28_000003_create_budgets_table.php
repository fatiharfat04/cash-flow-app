<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->date('month');                    // selalu tanggal 1, misal 2026-09-01
            $table->unsignedBigInteger('amount_limit'); // INTEGER
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'month'], 'uniq_budget_per_category_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
