<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['goat', 'farm']);
            $table->foreignId('goat_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('insights')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('alerts')->nullable();
            $table->json('recommendations')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->index(['scope', 'generated_at']);
            $table->index('goat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
