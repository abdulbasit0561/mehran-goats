<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->date('exchange_date');
            $table->foreignId('outgoing_goat_id')->constrained('goats')->cascadeOnDelete();
            $table->decimal('outgoing_value', 12, 2)->default(0);
            $table->foreignId('incoming_goat_id')->nullable()->constrained('goats')->nullOnDelete();
            $table->string('incoming_tag_id')->comment('Tag ID of the new goat received');
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('incoming_gender', ['male', 'female']);
            $table->decimal('incoming_weight_kg', 8, 2)->default(0);
            $table->decimal('incoming_value', 12, 2)->default(0);
            $table->string('counterparty')->nullable()->comment('Person you exchanged with');
            $table->enum('cash_direction', ['paid', 'received', 'none'])->default('none');
            $table->decimal('cash_amount', 12, 2)->default(0)->comment('Cash difference paid or received');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'other'])->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exchange_date']);
            $table->index(['cash_direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchanges');
    }
};
