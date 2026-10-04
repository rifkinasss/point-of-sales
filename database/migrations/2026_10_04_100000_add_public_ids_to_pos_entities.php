<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $tables = [
        'categories' => 'id',
        'products' => 'id',
        'customers' => 'id',
        'product_option_groups' => 'id',
        'product_option_values' => 'id',
    ];

    public function up(): void
    {
        foreach (array_keys($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->ulid('public_id')->nullable()->unique()->after('id');
            });

            DB::table($tableName)
                ->whereNull('public_id')
                ->orderBy('id')
                ->eachById(function (object $row) use ($tableName): void {
                    DB::table($tableName)->where('id', $row->id)->update(['public_id' => (string) Str::ulid()]);
                });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->ulid('public_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique([$tableName.'_public_id_unique']);
                $table->dropColumn('public_id');
            });
        }
    }
};
