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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20);
            $table->timestamp('opened_at');
            $table->unsignedBigInteger('opening_cash')->default(0);
            $table->text('opening_note')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closing_cash_actual')->nullable();
            $table->unsignedBigInteger('expected_cash')->nullable();
            $table->bigInteger('cash_difference')->nullable();
            $table->text('closing_note')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'opened_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
