<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_investor_id')->nullable()->constrained('investors')->nullOnDelete();
            $table->date('sale_date');
            $table->decimal('sale_price', 12, 2);
            $table->decimal('sale_weight', 10, 2)->nullable();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'other'])->default('cash');
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('paid');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('goat_id');
        });

        // Link income transactions back to the sale that created them.
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('investor_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
        });
        Schema::dropIfExists('sales');
    }
};
