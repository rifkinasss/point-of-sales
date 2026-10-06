<?php

namespace App\Actions\Owner;

use App\Models\Product;
use App\Models\User;
use App\Services\ActiveStoreContext;

class SetProductStatusAction
{
    public function execute(User $owner, Product $product, bool $active): Product
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $product->store_id), 404);
        $product->update(['is_active' => $active]);
        return $product->refresh();
    }
}
