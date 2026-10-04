<x-layouts.auth title="Masuk — NasLabs ERP">
    <div class="rounded-lg border border-border bg-surface p-6 shadow-sm sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Selamat datang</p><h1 class="mt-3 text-3xl font-black tracking-tight text-foreground">Masuk ke akun Anda</h1><p class="mt-2 text-sm leading-6 text-muted">Gunakan akun yang terdaftar untuk mengakses workspace sesuai peran Anda.</p>
        @if (session('status'))<div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>@endif
        <form action="{{ route('auth.login.store') }}" class="mt-7 space-y-5" method="POST">@csrf
            <div><label class="mb-2 block text-sm font-semibold text-secondary" for="email"><span class="inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-envelope" class="size-4 text-muted" aria-hidden="true" /> Email</span></label><input autofocus class="pl-4" id="email" name="email" required type="email" value="{{ old('email') }}">@error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><div class="mb-2 flex justify-between"><label class="text-sm font-semibold text-secondary" for="password"><span class="inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-lock-closed" class="size-4 text-muted" aria-hidden="true" /> Password</span></label><a class="text-sm font-semibold text-primary hover:text-primary-hover" href="{{ route('password.request') }}">Lupa password?</a></div><input id="password" name="password" required type="password"></div>
            <button class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 font-bold text-white transition hover:bg-primary-hover" type="submit"><x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="size-4" aria-hidden="true" /> Masuk ke NasLabs ERP</button>
        </form>
        <p class="mt-6 text-center text-sm text-muted">Belum punya toko? <a class="font-bold text-primary hover:text-primary-hover" href="{{ route('auth.register') }}">Mulai gratis</a></p>
    </div>
</x-layouts.auth>
