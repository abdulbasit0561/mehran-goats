<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ownership_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('investor_id')->constrained()->cascadeOnDelete();
            $table->decimal('share_pct', 5, 2)->default(0)->comment('0-100 ownership percentage');
            $table->timestamps();
            $table->index(['goat_id', 'investor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_units');
    }
};
