<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['vaccination', 'treatment', 'checkup', 'other'])->default('checkup');
            $table->date('date');
            $table->decimal('cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['goat_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
