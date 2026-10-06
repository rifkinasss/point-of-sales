<?php

namespace App\Actions\Owner;

use App\Models\Category;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\ValidationException;

class SetCategoryStatusAction
{
    public function execute(User $owner, Category $category, bool $active): Category
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $category->store_id), 404);
        if (! $active && $category->products()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['category' => 'Kategori yang masih dipakai produk aktif tidak dapat dinonaktifkan.']);
        }
        $category->update(['is_active' => $active]);
        return $category->refresh();
    }
}
