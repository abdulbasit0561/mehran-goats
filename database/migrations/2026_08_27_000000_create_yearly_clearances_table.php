<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yearly_clearances', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->date('clearance_date');

            $table->decimal('total_sales', 12, 2)->default(0)->comment('All income transactions for the year');
            $table->decimal('total_purchases', 12, 2)->default(0)->comment('Goat Purchases expense category for the year');
            $table->decimal('total_expenses', 12, 2)->default(0)->comment('All expense transactions for the year (incl. purchases)');
            $table->decimal('total_expenses_business', 12, 2)->default(0)->comment('Operating expenses excluding goat purchases');
            $table->decimal('gross_profit', 12, 2)->default(0)->comment('Sales - purchases');
            $table->decimal('net_profit', 12, 2)->default(0)->comment('Sales - all expenses (signed; negative = loss)');
            $table->decimal('herd_value_opening', 12, 2)->default(0);
            $table->decimal('herd_value_closing', 12, 2)->default(0);
            $table->decimal('outstanding_business_balance', 12, 2)->default(0)->comment('Sum of partner final balances (net position of the farm vs partners)');

            $table->json('report_payload')->comment('Full computed Yearly Clearance Report snapshot at completion');

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yearly_clearances');
    }
};
