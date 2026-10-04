<!DOCTYPE html>
<html lang="id">
    <head><meta charset="utf-8"><meta content="width=device-width, initial-scale=1" name="viewport"><title>{{ $title ?? 'NasLabs ERP' }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
    <body class="min-h-screen bg-background text-foreground">
        <style>
            .auth-surface input:not([type="hidden"]) { width: 100%; border: 1px solid var(--nl-border) !important; background: var(--nl-surface) !important; border-radius: .5rem; padding: .75rem 1rem; box-shadow: 0 1px 2px rgb(15 23 42 / .04); outline: none; transition: border-color .15s, box-shadow .15s; }
            .auth-surface input:not([type="hidden"]):focus { border-color: var(--nl-primary) !important; box-shadow: 0 0 0 4px rgb(59 82 226 / .12); }
        </style>
        <main class="grid min-h-screen lg:grid-cols-2">
            <section class="relative hidden overflow-hidden bg-foreground p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-12">
                <div class="absolute right-0 top-0 size-64 translate-x-1/3 -translate-y-1/3 rounded-full border border-white/10"></div>
                <div class="relative"><div class="inline-flex items-center gap-3 rounded-lg border border-white/10 bg-white/5 px-4 py-3"><span class="grid size-9 place-items-center rounded-md bg-primary font-black text-white">N</span><span class="font-bold tracking-wide">NASLABS ERP</span></div><h1 class="mt-14 max-w-lg text-[clamp(2.75rem,4vw,3.25rem)] font-black leading-[1.08] tracking-tight">Kelola operasional bisnis dalam satu ruang kerja.</h1><p class="mt-5 max-w-md text-base leading-7 text-white/70">Kasir, stok, pelanggan, pembelian, dan laporan terhubung dalam satu sistem.</p><div class="mt-8 grid max-w-md gap-3 sm:grid-cols-2"><div class="flex items-center gap-3 text-sm text-white/80"><x-filament::icon icon="heroicon-o-shopping-cart" class="size-4 text-primary" aria-hidden="true" /> Kasir</div><div class="flex items-center gap-3 text-sm text-white/80"><x-filament::icon icon="heroicon-o-cube" class="size-4 text-primary" aria-hidden="true" /> Stok</div><div class="flex items-center gap-3 text-sm text-white/80"><x-filament::icon icon="heroicon-o-user-group" class="size-4 text-primary" aria-hidden="true" /> Pelanggan</div><div class="flex items-center gap-3 text-sm text-white/80"><x-filament::icon icon="heroicon-o-truck" class="size-4 text-primary" aria-hidden="true" /> Pembelian</div><div class="flex items-center gap-3 text-sm text-white/80"><x-filament::icon icon="heroicon-o-chart-bar" class="size-4 text-primary" aria-hidden="true" /> Laporan</div></div></div>
                <div class="relative max-w-md text-xs text-white/50">Satu ruang kerja untuk operasional yang lebih teratur.</div>
            </section>
            <section class="auth-surface flex min-h-screen items-center justify-center bg-background px-5 py-8 sm:px-10"><div class="w-full max-w-md"><a class="mb-7 inline-flex items-center gap-2 font-bold text-secondary lg:hidden" href="{{ route('auth.login') }}"><span class="grid size-8 place-items-center rounded-md bg-primary text-white">N</span> NASLABS ERP</a>{{ $slot }}</div></section>
        </main>
    </body>
</html>
