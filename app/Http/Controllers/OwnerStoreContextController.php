<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OwnerStoreContextController
{
    public function __invoke(Request $request, ActiveStoreContext $context): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);

        $data = $request->validate(['store' => ['required', 'string', 'max:64']]);
        $context->switch($user, $data['store']);

        return back();
    }
}
