<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'other'])->default('cash')->after('investor_id');
            $table->boolean('is_recurring')->default(false)->after('payment_method');
            $table->decimal('budget_amount', 12, 2)->nullable()->after('is_recurring');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'is_recurring', 'budget_amount']);
        });
    }
};
