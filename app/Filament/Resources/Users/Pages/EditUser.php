<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $currentUser = Filament::auth()->user();

        if (! $currentUser instanceof User || ! array_key_exists($data['role'], UserRole::assignableOptionsFor($currentUser->role))) {
            throw ValidationException::withMessages([
                'data.role' => 'Anda tidak memiliki izin untuk menetapkan role tersebut.',
            ]);
        }

        if ($currentUser->role === UserRole::Owner) {
            $data['store_id'] = $currentUser->store_id;
        }

        return $data;
    }
}
