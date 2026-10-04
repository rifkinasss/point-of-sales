<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-redeem-codes.table_names.reward_types'), function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label', 191);
            $table->string('icon', 64)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-redeem-codes.table_names.reward_types'));
    }
};
