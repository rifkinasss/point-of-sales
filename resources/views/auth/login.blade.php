<x-layouts.auth title="Masuk — NasLabs ERP">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/60 sm:p-9">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600">Selamat datang</p><h1 class="mt-3 text-3xl font-black tracking-tight text-slate-950">Masuk ke toko Anda</h1><p class="mt-2 text-sm leading-6 text-slate-500">Gunakan akun Owner, Manager, atau Kasir untuk melanjutkan.</p>
        @if (session('status'))<div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
        <form action="{{ route('auth.login.store') }}" class="mt-7 space-y-5" method="POST">@csrf
            <div><label class="mb-2 block text-sm font-semibold" for="email">Email</label><input autofocus class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100" id="email" name="email" required type="email" value="{{ old('email') }}">@error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><div class="mb-2 flex justify-between"><label class="text-sm font-semibold" for="password">Password</label><a class="text-sm font-semibold text-indigo-600" href="{{ route('password.request') }}">Lupa password?</a></div><input class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100" id="password" name="password" required type="password"></div>
            <button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-indigo-700" type="submit">Masuk ke POS</button>
        </form>
        <p class="mt-7 text-center text-sm text-slate-500">Belum punya toko? <a class="font-bold text-indigo-600" href="{{ route('auth.register') }}">Mulai gratis</a></p>
    </div>
</x-layouts.auth>
