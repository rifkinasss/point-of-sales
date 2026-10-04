<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 30);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('stock_opnames', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('number', 50);
            $table->string('status', 20)->default('draft');
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'number']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->integer('system_quantity');
            $table->integer('actual_quantity');
            $table->integer('difference');
            $table->timestamps();
        });

        Schema::create('production_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('number', 50);
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_produced');
            $table->foreignId('produced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('produced_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'number']);
        });

        Schema::create('store_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number', 50);
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('amount');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_invoices');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('audit_logs');
    }
};
