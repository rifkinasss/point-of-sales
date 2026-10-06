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
        Schema::table('units', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });
        DB::table('units')->whereNull('public_id')->orderBy('id')->eachById(function (object $unit): void {
            DB::table('units')->where('id', $unit->id)->update(['public_id' => (string) Str::ulid()]);
        });
        Schema::table('units', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropUnique(['units_public_id_unique']);
            $table->dropColumn('public_id');
        });
    }
};
