<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\AuditLogger;
use App\Policies\UserSessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Smony\FilamentUserSessions\Models\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('view-filament-loginguard', fn (User $user): bool => $user->role === UserRole::Admin);
        Gate::define('manage-announcements', fn (User $user): bool => $user->role === UserRole::Admin);
        Gate::policy(Session::class, UserSessionPolicy::class);

        foreach ([Product::class, Purchase::class, Expense::class, Sale::class] as $auditable) {
            $auditable::created(fn ($model) => app(AuditLogger::class)->log('created', $model, [], $this->safeAuditValues($model->getAttributes())));
            $auditable::updated(function ($model): void {
                $changes = collect($model->getChanges())->except(['updated_at'])->all();
                if ($changes !== []) app(AuditLogger::class)->log('updated', $model, [], $this->safeAuditValues($changes));
            });
            $auditable::deleted(fn ($model) => app(AuditLogger::class)->log('deleted', $model));
        }
    }

    private function safeAuditValues(array $values): array
    {
        unset($values['password'], $values['remember_token']);
        return $values;
    }
}
