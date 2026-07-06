<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->enum('role', ['investor', 'manager'])->default('investor')->after('name');
            $table->decimal('expense_split_pct', 5, 2)->default(33.33)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn(['role', 'expense_split_pct']);
        });
    }
};
