<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goats', function (Blueprint $table) {
            $table->id();
            $table->string('tag_id')->unique()->comment('Unique ear-tag ID');
            $table->string('name')->nullable();
            $table->foreignId('breed_id')->constrained()->cascadeOnDelete();
            $table->enum('gender', ['male', 'female']);
            $table->date('dob')->nullable()->comment('Date of birth');
            $table->decimal('weight_kg', 8, 2)->default(0);
            $table->enum('status', ['kid', 'grower', 'breeder', 'sold', 'deceased'])->default('kid');
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goats');
    }
};
