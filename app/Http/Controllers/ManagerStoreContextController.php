<?php

namespace App\Http\Controllers;

use App\Services\ActiveStoreContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ManagerStoreContextController
{
    public function __invoke(Request $request, ActiveStoreContext $context): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $data = $request->validate(['store' => ['required', 'string', 'max:64']]);
        $context->switch($user, $data['store']);

        $route = (string) $request->session()->get('_previous.url', '');
        if ($data['store'] === 'all' && (
            str_contains($route, '/manager/products/')
            || str_contains($route, '/manager/promotions')
            || str_contains($route, '/manager/customers/')
        )) {
            return redirect()->route('manager.dashboard');
        }

        return back();
    }
}
