<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('death_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->constrained()->cascadeOnDelete();
            $table->date('death_date');
            $table->string('cause')->nullable();
            $table->enum('disposal_method', ['sold_to_butcher', 'buried', 'composted', 'other'])->default('buried');
            $table->decimal('disposal_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // One death record per goat (a goat can only die once).
            $table->unique('goat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('death_records');
    }
};
