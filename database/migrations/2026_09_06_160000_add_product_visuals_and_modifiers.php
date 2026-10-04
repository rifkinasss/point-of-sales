<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('barcode');
        });

        Schema::create('product_option_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('minimum_selections')->default(0);
            $table->unsignedTinyInteger('maximum_selections')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_option_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('price_adjustment')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('modifier_total')->default(0)->after('unit_price');
            $table->json('modifiers')->nullable()->after('modifier_total');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn(['modifier_total', 'modifiers']);
        });

        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_option_groups');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};
