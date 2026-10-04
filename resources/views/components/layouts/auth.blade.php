<!DOCTYPE html>
<html lang="id">
    <head><meta charset="utf-8"><meta content="width=device-width, initial-scale=1" name="viewport"><title>{{ $title ?? 'NasLabs ERP' }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
    <body class="min-h-screen bg-slate-950 text-slate-900">
        <style>
            .auth-surface input:not([type="hidden"]) { width: 100%; border: 1px solid #cbd5e1 !important; background: #fff !important; border-radius: .75rem; padding: .75rem 1rem; box-shadow: 0 1px 2px rgb(15 23 42 / .06); outline: none; transition: border-color .15s, box-shadow .15s; }
            .auth-surface input:not([type="hidden"]):focus { border-color: #4f46e5 !important; box-shadow: 0 0 0 4px rgb(79 70 229 / .12); }
        </style>
        <main class="grid min-h-screen lg:grid-cols-2">
            <section class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-violet-700 to-fuchsia-700 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 20% 20%, white 0, transparent 22%), radial-gradient(circle at 80% 65%, white 0, transparent 18%);"></div>
                <div class="relative"><div class="inline-flex items-center gap-3 rounded-2xl border border-white/25 bg-white/10 px-4 py-3 backdrop-blur"><span class="grid size-9 place-items-center rounded-xl bg-white font-black text-indigo-700">N</span><span class="font-bold tracking-wide">NASLABS ERP</span></div><h1 class="mt-16 max-w-lg text-5xl font-black leading-tight">Kelola bisnis lebih cepat, dari mana saja.</h1><p class="mt-6 max-w-md text-lg leading-8 text-indigo-100">Kasir, stok, pelanggan, pembelian, dan keuangan dalam satu ruang kerja terpadu.</p></div>
                <div class="relative grid grid-cols-3 gap-3 text-sm text-indigo-100"><div class="rounded-xl bg-white/10 p-4"><strong class="block text-2xl text-white">10</strong>produk Free</div><div class="rounded-xl bg-white/10 p-4"><strong class="block text-2xl text-white">2</strong>staf</div><div class="rounded-xl bg-white/10 p-4"><strong class="block text-2xl text-white">∞</strong>transaksi</div></div>
            </section>
            <section class="auth-surface flex items-center justify-center bg-slate-50 px-6 py-10 sm:px-10"><div class="w-full max-w-md"><a class="mb-10 inline-flex items-center gap-2 font-bold text-indigo-700 lg:hidden" href="{{ route('auth.login') }}"><span class="grid size-8 place-items-center rounded-lg bg-indigo-700 text-white">N</span> NASLABS ERP</a>{{ $slot }}</div></section>
        </main>
    </body>
</html>
