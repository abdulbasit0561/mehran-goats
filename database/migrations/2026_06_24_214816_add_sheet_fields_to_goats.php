<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goats', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('breed_id');
            $table->decimal('purchase_weight', 8, 2)->default(0)->after('weight_kg');
            $table->string('vendor')->nullable()->after('purchase_weight');
            $table->date('sale_date')->nullable()->after('sale_price');
            $table->decimal('sale_weight', 8, 2)->default(0)->after('sale_date');
            $table->decimal('estimated_value', 12, 2)->default(0)->after('sale_weight');
        });
    }

    public function down(): void
    {
        Schema::table('goats', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_date', 'purchase_weight', 'vendor',
                'sale_date', 'sale_weight', 'estimated_value',
            ]);
        });
    }
};
