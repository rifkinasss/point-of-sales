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
        Schema::table('stores', function (Blueprint $table): void {
            $table->char('store_code', 3)->nullable()->after('slug');
            $table->string('branch_code', 7)->nullable()->after('store_code');
        });

        DB::table('stores')->orderBy('id')->get()->each(function (object $store): void {
            $source = strtoupper((string) ($store->invoice_prefix ?: $store->name ?: 'STO'));
            $code = preg_replace('/[^A-Z0-9]/', '', $source) ?: 'STO';
            $code = str_pad(substr($code, 0, 3), 3, 'X');

            if (DB::table('stores')->where('store_code', $code)->exists()) {
                $code = strtoupper(substr(base_convert((string) $store->id, 10, 36), -3));
                $code = str_pad($code, 3, '0', STR_PAD_LEFT);
            }

            DB::table('stores')->where('id', $store->id)->update(['store_code' => $code]);
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->unique('store_code');
        });

        Schema::create('invoice_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('branch_code', 7)->nullable();
            $table->date('business_date');
            $table->string('scope_key', 180)->unique();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'branch_code', 'business_date']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->unique()->after('id');
        });

        DB::table('sales')->whereNull('public_id')->orderBy('id')->get()->each(function (object $sale): void {
            DB::table('sales')->where('id', $sale->id)->update(['public_id' => (string) Str::ulid()]);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['sales_public_id_unique']);
            $table->dropColumn('public_id');
        });

        Schema::dropIfExists('invoice_sequences');

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropUnique(['store_code']);
            $table->dropColumn(['store_code', 'branch_code']);
        });
    }
};
