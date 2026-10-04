<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('name');
            $table->string('email')->nullable()->after('phone');
            $table->decimal('tax_percentage', 5, 2)->default(0)->after('timezone');
            $table->boolean('receipt_show_cashier')->default(true)->after('receipt_footer');
            $table->boolean('receipt_show_tax')->default(false)->after('receipt_show_cashier');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn([
                'logo_path',
                'email',
                'tax_percentage',
                'receipt_show_cashier',
                'receipt_show_tax',
            ]);
        });
    }
};
