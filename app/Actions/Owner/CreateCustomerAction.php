<?php

namespace App\Actions\Owner;

use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;

class CreateCustomerAction
{
    public function execute(User $owner, Store $store, array $attributes): Customer
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $store->id) && $store->isActive(), 404);
        return DB::transaction(fn (): Customer => Customer::query()->withoutGlobalScopes()->create($attributes + ['store_id' => $store->id]));
    }
}
