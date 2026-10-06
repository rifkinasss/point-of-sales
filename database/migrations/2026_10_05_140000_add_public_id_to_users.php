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
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        DB::table('users')->select('id')->orderBy('id')->each(function (object $user): void {
            DB::table('users')->where('id', $user->id)->update(['public_id' => (string) Str::ulid()]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
