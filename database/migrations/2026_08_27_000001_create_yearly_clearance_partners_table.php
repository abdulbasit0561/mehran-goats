<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yearly_clearance_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yearly_clearance_id')->constrained('yearly_clearances')->cascadeOnDelete();
            $table->foreignId('investor_id')->constrained('investors')->cascadeOnDelete();

            $table->string('investor_name')->comment('Snapshot of investor name at clearance time');
            $table->decimal('share_pct', 5, 2)->default(0)->comment('Snapshot of capital-derived share % at clearance time');
            $table->decimal('opening_balance', 12, 2)->default(0)->comment('Carried from previous year clearance final_balance, or computed from pre-year records');
            $table->decimal('investment', 12, 2)->default(0)->comment('Capital invested during the year');
            $table->decimal('profit_share', 12, 2)->default(0)->comment('Net profit x share% / 100 (signed; negative = loss share)');
            $table->decimal('expenses_paid_by_partner', 12, 2)->default(0)->comment('Expense transactions paid by this partner during the year');
            $table->decimal('withdrawals_received', 12, 2)->default(0)->comment('Income transactions received by this partner during the year');
            $table->decimal('final_balance', 12, 2)->default(0)->comment('opening + investment + profit_share + expenses_paid - withdrawals (positive = payable to partner)');
            $table->enum('status', ['payable_to_partner', 'receivable_from_partner', 'settled'])->default('settled');

            $table->timestamps();

            $table->unique(['yearly_clearance_id', 'investor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yearly_clearance_partners');
    }
};
