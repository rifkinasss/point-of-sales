<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipe_ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity_required');
            $table->timestamps();
            $table->unique(['product_id', 'ingredient_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipe_ingredients');
    }
};
