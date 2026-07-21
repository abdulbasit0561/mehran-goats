<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goat_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->constrained()->cascadeOnDelete();
            $table->string('image_path')->comment('Relative path on the public disk');
            $table->decimal('age_months', 6, 1)->default(0)->comment('Age of goat in months at time of photo');
            $table->decimal('weight_kg', 8, 2)->default(0)->comment('Weight of goat in kg at time of photo');
            $table->date('taken_at')->comment('Date the photo was taken / recorded');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goat_images');
    }
};
