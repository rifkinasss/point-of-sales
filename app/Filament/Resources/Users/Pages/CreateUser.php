<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ensureRoleCanBeAssigned($data['role']);

        $currentUser = Filament::auth()->user();

        if ($currentUser instanceof User && $currentUser->role === UserRole::Owner) {
            if (! $currentUser->store || ! $currentUser->store->canAddStaff()) {
                throw ValidationException::withMessages([
                    'data.name' => "Paket Free dibatasi {$currentUser->store?->staff_limit} staf. Upgrade paket untuk menambah staf.",
                ]);
            }

            $data['store_id'] = $currentUser->store_id;
        }

        return $data;
    }

    private function ensureRoleCanBeAssigned(string $role): void
    {
        $currentUser = Filament::auth()->user();

        if (! $currentUser instanceof User || ! array_key_exists($role, UserRole::assignableOptionsFor($currentUser->role))) {
            throw ValidationException::withMessages([
                'data.role' => 'Anda tidak memiliki izin untuk menetapkan role tersebut.',
            ]);
        }
    }
}
