<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // nullable = kategori default/global (dibuat oleh seeder, milik semua user)
            // terisi  = kategori kustom milik user tertentu
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->string('icon', 50)->default('tag');       // nama icon (Heroicons slug)
            $table->string('color', 7)->default('#D9BC7C');   // hex, default cream-500
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'name', 'type'], 'uniq_category_per_user_type');
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
