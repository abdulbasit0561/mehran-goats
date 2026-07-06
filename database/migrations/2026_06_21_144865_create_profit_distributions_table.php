<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profit_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_profit', 12, 2)->comment('Total farm profit for the period');
            $table->decimal('share_pct', 5, 2)->comment('Snapshot of investor share % at distribution time');
            $table->decimal('amount', 12, 2)->comment('Distributed amount = total_profit * share_pct / 100');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['investor_id', 'period_start']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_distributions');
    }
};
