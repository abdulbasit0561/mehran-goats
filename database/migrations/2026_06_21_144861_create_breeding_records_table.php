<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('breeding_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sire_id')->constrained('goats')->cascadeOnDelete();
            $table->foreignId('dam_id')->constrained('goats')->cascadeOnDelete();
            $table->date('breeding_date');
            $table->date('kidding_date')->nullable();
            $table->unsignedInteger('litter_size')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['sire_id', 'breeding_date']);
            $table->index(['dam_id', 'breeding_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_records');
    }
};
