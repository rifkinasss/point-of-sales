<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        DB::table('cashier_shifts')->whereNull('public_id')->orderBy('id')->eachById(
            fn (object $shift): int => DB::table('cashier_shifts')
                ->where('id', $shift->id)
                ->update(['public_id' => (string) Str::ulid()]),
        );

        Schema::table('cashier_shifts', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table): void {
            $table->dropUnique(['cashier_shifts_public_id_unique']);
            $table->dropColumn('public_id');
        });
    }
};
