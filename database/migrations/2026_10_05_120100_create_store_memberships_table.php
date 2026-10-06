<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'store_id']);
        });

        DB::table('users')
            ->where('role', UserRole::Manager->value)
            ->whereNotNull('store_id')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('store_memberships')->insert([
                    'user_id' => $user->id,
                    'store_id' => $user->store_id,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_memberships');
    }
};
