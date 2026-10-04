<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('tier', 30)->default('regular')->after('code');
            $table->string('card_number', 80)->nullable()->after('tier');
            $table->date('birth_date')->nullable()->after('email');
            $table->unique(['store_id', 'card_number']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['store_id', 'card_number']);
            $table->dropColumn(['tier', 'card_number', 'birth_date']);
        });
    }
};
