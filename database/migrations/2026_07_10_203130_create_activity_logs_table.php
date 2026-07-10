<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();           // snapshot — survives user deletion
            $table->string('action', 20);                       // create | update | delete
            $table->string('entity_type');                      // e.g. Goat, Transaction, Investor
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('description')->nullable();          // human-readable summary
            $table->json('old_values')->nullable();             // full snapshot or changed-before
            $table->json('new_values')->nullable();             // full snapshot or changed-after
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
