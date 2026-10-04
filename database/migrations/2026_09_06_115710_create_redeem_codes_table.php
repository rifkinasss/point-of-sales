<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-redeem-codes.table_names.codes'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained(config('filament-redeem-codes.table_names.campaigns'))
                ->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->boolean('reusable')->default(false);
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index('redeemed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-redeem-codes.table_names.codes'));
    }
};
