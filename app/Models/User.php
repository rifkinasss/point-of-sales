<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Marcelodelgado\Announcements\Traits\HasAnnouncements;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'store_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasAnnouncements, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->role === UserRole::Admin,
            'owner' => $this->role === UserRole::Owner,
            'manager' => $this->role === UserRole::Manager,
            'cashier' => $this->role === UserRole::Cashier,
            default => false,
        };
    }

    public function canImpersonate(self $target): bool
    {
        if (! $target->is_active) {
            return false;
        }

        return match ($this->role) {
            UserRole::Admin => in_array($target->role, [UserRole::Owner, UserRole::Manager, UserRole::Cashier], true),
            UserRole::Owner => in_array($target->role, [UserRole::Manager, UserRole::Cashier], true),
            UserRole::Manager, UserRole::Cashier => false,
        };
    }

    public function canBeImpersonated(self $by): bool
    {
        return $this->is_active && $this->role !== UserRole::Admin;
    }

    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }
}
