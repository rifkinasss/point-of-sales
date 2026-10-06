<?php

namespace App\Actions\Owner;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;

class SetStoreStatusAction
{
    public function execute(User $owner, Store $store, StoreStatus $status): Store
    {
        return app(\App\Actions\Store\SetStoreStatusAction::class)->execute($owner, $store, $status);
    }
}
