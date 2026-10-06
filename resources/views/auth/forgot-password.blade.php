<x-layouts.auth title="Reset Password — NasLabs ERP">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/60 sm:p-9">
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600">Pemulihan akun</p>
        <h1 class="mt-3 text-3xl font-black text-slate-950">Lupa password?</h1>
        <p class="mt-2 text-sm text-slate-500">Kami kirim tautan reset password ke email Anda.</p>
        @if (session('status'))
            <div class="mt-5 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        <form action="{{ route('password.email') }}" class="mt-7 space-y-5" method="POST">
            @csrf<div><label class="mb-1 block text-sm font-semibold">Email</label><input
                    class="w-full rounded-xl border-slate-300 px-4 py-3" name="email" required type="email"
                    value="{{ old('email') }}">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div><button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-bold text-white" type="submit">Kirim
                tautan reset</button></form>
        <p class="mt-6 text-center text-sm"><a class="font-bold text-indigo-600"
                href="{{ route('auth.login') }}">Kembali ke login</a></p>
    </div>
</x-layouts.auth>
