<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->foreignId('investor_id')
                ->nullable()
                ->after('payment_method')
                ->constrained()
                ->nullOnDelete()
                ->comment('Investor who paid or received the cash difference');
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropColumn('investor_id');
        });
    }
};
