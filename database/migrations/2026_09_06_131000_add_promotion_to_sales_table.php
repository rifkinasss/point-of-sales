<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('redeem_code_id')->nullable()->after('cashier_id')->constrained('redeem_codes')->nullOnDelete();
            $table->string('promotion_code', 40)->nullable()->after('redeem_code_id');
            $table->unsignedBigInteger('promotion_discount_total')->default(0)->after('item_discount_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('redeem_code_id');
            $table->dropColumn(['promotion_code', 'promotion_discount_total']);
        });
    }
};
