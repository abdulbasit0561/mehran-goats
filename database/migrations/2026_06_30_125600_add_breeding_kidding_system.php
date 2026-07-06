<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add pregnancy/breeding lifecycle fields to breeding_records
        Schema::table('breeding_records', function (Blueprint $table) {
            $table->date('expected_kidding_date')->nullable()->after('kidding_date')
                  ->comment('Auto-calculated: breeding_date + 150 days');
            $table->enum('pregnancy_status', ['bred', 'pregnant', 'kidded', 'aborted', 'open'])
                  ->default('bred')->after('expected_kidding_date');
            $table->enum('kidding_difficulty', ['easy', 'assisted', 'difficult', 'cesarean'])
                  ->nullable()->after('litter_size');
            $table->index('pregnancy_status');
        });

        // Add parent lineage + birth origin to goats
        Schema::table('goats', function (Blueprint $table) {
            $table->foreignId('sire_id')->nullable()->after('caretaker_id')
                  ->references('id')->on('goats')->nullOnDelete();
            $table->foreignId('dam_id')->nullable()->after('sire_id')
                  ->references('id')->on('goats')->nullOnDelete();
            $table->foreignId('breeding_record_id')->nullable()->after('dam_id')
                  ->references('id')->on('breeding_records')->nullOnDelete();
            $table->enum('birth_type', ['purchased', 'farm_born'])->default('purchased')
                  ->after('breeding_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('goats', function (Blueprint $table) {
            $table->dropForeign(['sire_id']);
            $table->dropForeign(['dam_id']);
            $table->dropForeign(['breeding_record_id']);
            $table->dropColumn(['sire_id', 'dam_id', 'breeding_record_id', 'birth_type']);
        });

        Schema::table('breeding_records', function (Blueprint $table) {
            $table->dropIndex(['pregnancy_status']);
            $table->dropColumn(['expected_kidding_date', 'pregnancy_status', 'kidding_difficulty']);
        });
    }
};
