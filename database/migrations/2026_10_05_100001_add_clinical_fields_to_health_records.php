<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            $table->text('symptoms')->nullable()->after('notes');
            $table->string('diagnosis')->nullable()->after('symptoms');
            $table->string('medication')->nullable()->after('diagnosis');
            $table->string('dosage')->nullable()->after('medication');
            $table->string('vet_name')->nullable()->after('dosage');
            $table->string('vaccine_name')->nullable()->after('vet_name');
        });
    }

    public function down(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            $table->dropColumn(['symptoms', 'diagnosis', 'medication', 'dosage', 'vet_name', 'vaccine_name']);
        });
    }
};
