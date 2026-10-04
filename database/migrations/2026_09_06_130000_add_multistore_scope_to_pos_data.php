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
            $table->string('slug', 150)->nullable()->unique()->after('name');
        });

        foreach (['users', 'categories', 'units', 'products', 'stock_movements', 'cashier_shifts', 'sales'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete()->index();
            });
        }

        $storeId = DB::table('stores')->orderBy('id')->value('id');

        if (! $storeId) {
            $storeId = DB::table('stores')->insertGetId([
                'name' => 'Toko Saya',
                'slug' => 'toko-saya',
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'invoice_prefix' => 'POS',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('stores')->whereNull('slug')->orderBy('id')->get()->each(function (object $store): void {
            DB::table('stores')->where('id', $store->id)->update([
                'slug' => Str::slug($store->name).'-'.$store->id,
            ]);
        });

        foreach (['categories', 'units', 'products', 'stock_movements', 'cashier_shifts', 'sales'] as $tableName) {
            DB::table($tableName)->whereNull('store_id')->update(['store_id' => $storeId]);
        }

        DB::table('users')->where('role', '!=', 'admin')->whereNull('store_id')->update(['store_id' => $storeId]);
    }

    public function down(): void
    {
        foreach (['users', 'categories', 'units', 'products', 'stock_movements', 'cashier_shifts', 'sales'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('store_id');
            });
        }

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
