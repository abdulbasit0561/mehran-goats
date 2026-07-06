<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('caretakers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->unsignedSmallInteger('every_nth_kid')->default(5)->comment('Every Nth kid born belongs to this caretaker');
            $table->date('start_date')->nullable()->comment('When this agreement started');
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('goats', function (Blueprint $table) {
            $table->foreignId('caretaker_id')->nullable()->after('breed_id')
                  ->constrained('caretakers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goats', function (Blueprint $table) {
            $table->dropForeign(['caretaker_id']);
            $table->dropColumn('caretaker_id');
        });

        Schema::dropIfExists('caretakers');
    }
};
