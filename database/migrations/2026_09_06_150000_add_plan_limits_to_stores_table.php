<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('plan', 30)->default('free')->after('slug');
            $table->unsignedInteger('product_limit')->default(10)->after('plan');
            $table->unsignedInteger('staff_limit')->default(2)->after('product_limit');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn(['plan', 'product_limit', 'staff_limit']);
        });
    }
};
