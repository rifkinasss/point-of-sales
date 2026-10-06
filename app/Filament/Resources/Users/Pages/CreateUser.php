<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Admin\ManageUserAccessAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $admin = Filament::auth()->user();
        abort_unless($admin instanceof User, 403);
        $storePublicIds = $data['role'] === 'manager'
            ? ($data['store_public_ids'] ?? [])
            : array_filter([(string) ($data['store_public_id'] ?? '')]);

        return app(ManageUserAccessAction::class)->create($admin, $data, $storePublicIds);
    }
}
