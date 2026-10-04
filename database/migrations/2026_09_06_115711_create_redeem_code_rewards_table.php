<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-redeem-codes.table_names.rewards'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained(config('filament-redeem-codes.table_names.campaigns'))
                ->cascadeOnDelete();
            $table->string('type', 64);
            $table->unsignedInteger('amount')->default(1);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-redeem-codes.table_names.rewards'));
    }
};
