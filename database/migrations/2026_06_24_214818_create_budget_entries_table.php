<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->string('category');
            $table->decimal('jan', 12, 2)->default(0);
            $table->decimal('feb', 12, 2)->default(0);
            $table->decimal('mar', 12, 2)->default(0);
            $table->decimal('apr', 12, 2)->default(0);
            $table->decimal('may', 12, 2)->default(0);
            $table->decimal('jun', 12, 2)->default(0);
            $table->decimal('jul', 12, 2)->default(0);
            $table->decimal('aug', 12, 2)->default(0);
            $table->decimal('sep', 12, 2)->default(0);
            $table->decimal('oct', 12, 2)->default(0);
            $table->decimal('nov', 12, 2)->default(0);
            $table->decimal('dec', 12, 2)->default(0);
            $table->unsignedInteger('year')->default(date('Y'));
            $table->timestamps();

            $table->unique(['type', 'category', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_entries');
    }
};
