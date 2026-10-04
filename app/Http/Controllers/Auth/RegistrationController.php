<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return Auth::check() ? redirect('/owner') : view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'store_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $baseSlug = Str::slug($data['store_name']) ?: 'toko';
            $slug = $baseSlug;
            $suffix = 2;

            while (Store::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $store = Store::query()->create([
                'name' => $data['store_name'],
                'slug' => $slug,
                'plan' => 'free',
                'product_limit' => 10,
                'staff_limit' => 2,
                'currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'invoice_prefix' => 'POS',
                'receipt_show_cashier' => true,
                'receipt_show_tax' => false,
            ]);

            return User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => UserRole::Owner,
                'store_id' => $store->id,
                'is_active' => true,
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/owner')->with('status', 'Toko dan akun Free Anda siap digunakan.');
    }
}
