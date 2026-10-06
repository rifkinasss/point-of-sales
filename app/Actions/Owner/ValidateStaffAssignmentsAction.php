<?php

namespace App\Actions\Owner;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerStaffQuery;
use Illuminate\Validation\ValidationException;

class ValidateStaffAssignmentsAction
{
    /** @return array<int, Store> */
    public function execute(User $owner, UserRole $role, array $storePublicIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $storePublicIds))));
        $stores = app(OwnerStaffQuery::class)->authorizedStores($owner, true)->whereIn('public_id', $ids)->values();
        if (count($ids) !== $stores->count()) throw ValidationException::withMessages(['selectedStorePublicIds' => 'Semua toko yang dipilih harus berada dalam akses Owner dan berstatus aktif.']);
        if ($role === UserRole::Manager && $stores->isEmpty()) throw ValidationException::withMessages(['selectedStorePublicIds' => 'Manager harus memiliki minimal satu toko yang ditugaskan.']);
        if ($role === UserRole::Cashier && $stores->count() !== 1) throw ValidationException::withMessages(['selectedStorePublicIds' => 'Cashier harus memiliki tepat satu toko operasional.']);
        return $stores->all();
    }
}
