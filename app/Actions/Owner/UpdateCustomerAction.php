<?php

namespace App\Actions\Owner;

use App\Models\Customer;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;

class UpdateCustomerAction
{
    public function execute(User $owner, Customer $customer, array $attributes): Customer
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $customer->store_id), 404);
        return DB::transaction(function () use ($customer, $attributes): Customer { $customer->update($attributes); return $customer->refresh(); });
    }
}
