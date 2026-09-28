<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: kategori yang sudah dipakai transaksi TIDAK BOLEH dihapus,
            // riwayat transaksi finansial harus tetap ada meski kategorinya dihapus/diarsipkan.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->unsignedBigInteger('amount');     // INTEGER, rupiah penuh, tidak boleh negatif
            $table->text('description')->nullable();
            $table->date('transaction_date');         // TERPISAH dari created_at
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type', 'transaction_date']);
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
