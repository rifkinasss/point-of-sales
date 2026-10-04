<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-redeem-codes.table_names.histories'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('redeem_code_id')
                ->constrained(config('filament-redeem-codes.table_names.codes'))
                ->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->text('agent')->nullable();
            $table->timestamps();

            $table->index('redeem_code_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-redeem-codes.table_names.histories'));
    }
};
