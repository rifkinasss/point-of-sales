<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Owner = 'owner';
    case Manager = 'manager';
    case Cashier = 'cashier';

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::Admin, self::Owner, self::Manager], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Cashier => 'Kasir',
        };
    }

    /** @return array<string, string> */
    public static function assignableOptionsFor(self $role): array
    {
        return collect(match ($role) {
            self::Admin => [self::Owner, self::Manager, self::Cashier],
            self::Owner => [self::Manager, self::Cashier],
            self::Manager, self::Cashier => [],
        })->mapWithKeys(fn (self $assignableRole): array => [
            $assignableRole->value => $assignableRole->label(),
        ])->all();
    }
}
