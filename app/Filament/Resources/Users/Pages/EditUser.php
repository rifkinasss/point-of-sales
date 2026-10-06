<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Admin\ManageUserAccessAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if ($record instanceof User) {
            $data['store_public_ids'] = $record->role?->value === 'manager'
                ? $record->managedStores()->pluck('stores.public_id')->all()
                : [];
            $data['store_public_id'] = $record->store?->public_id;
        }
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $admin = Filament::auth()->user();
        abort_unless($admin instanceof User && $record instanceof User, 403);
        $storePublicIds = $data['role'] === 'manager'
            ? ($data['store_public_ids'] ?? [])
            : array_filter([(string) ($data['store_public_id'] ?? '')]);

        return app(ManageUserAccessAction::class)->update($admin, $record, $data, $storePublicIds);
    }
}
