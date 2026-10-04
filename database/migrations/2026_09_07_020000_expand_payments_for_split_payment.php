<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_sale_id_unique');
            $table->string('status', 20)->default('completed')->after('method');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->index(['sale_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['sale_id', 'status']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['status', 'refunded_at']);
            $table->unique('sale_id');
        });
    }
};
