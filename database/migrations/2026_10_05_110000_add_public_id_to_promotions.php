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
        Schema::table('promotions', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        DB::table('promotions')->whereNull('public_id')->orderBy('id')->eachById(
            fn (object $promotion): int => DB::table('promotions')->where('id', $promotion->id)->update(['public_id' => (string) Str::ulid()]),
        );

        Schema::table('promotions', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropUnique(['promotions_public_id_unique']);
            $table->dropColumn('public_id');
        });
    }
};
