<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Manager Workspace' }} · NasLabs ERP</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="nl-page min-h-screen antialiased">
        <div class="min-h-screen lg:flex">
            <aside class="border-b border-border bg-surface px-5 py-5 lg:min-h-screen lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r">
                <a class="inline-flex items-center gap-3 text-foreground" href="{{ route('manager.dashboard') }}"><span class="grid size-9 place-items-center rounded-lg bg-primary text-sm font-black text-white">N</span><span><span class="block text-sm font-black tracking-tight">NasLabs ERP</span><span class="block text-xs text-muted">Manager workspace</span></span></a>
                <div class="mt-7 border-y border-border py-4"><p class="text-[11px] font-bold uppercase tracking-wider text-muted">Store aktif</p><p class="mt-1 truncate text-sm font-semibold text-foreground">{{ auth()->user()?->store?->name ?? 'Store belum dipilih' }}</p></div>
                <nav class="mt-5 flex gap-2 overflow-x-auto lg:block lg:space-y-1" aria-label="Navigasi manager"><a class="nl-focus inline-flex items-center gap-2 whitespace-nowrap rounded-lg bg-primary-soft px-3 py-2.5 text-sm font-bold text-primary" href="{{ route('manager.dashboard') }}"><x-filament::icon icon="heroicon-o-squares-2x2" class="size-4" aria-hidden="true" />Dashboard</a>@foreach ([['Transaksi', 'heroicon-o-receipt-percent'], ['Shift Kasir', 'heroicon-o-clock'], ['Kas', 'heroicon-o-banknotes'], ['Produk & Stok', 'heroicon-o-cube'], ['Pelanggan', 'heroicon-o-users'], ['Promo', 'heroicon-o-tag'], ['Laporan', 'heroicon-o-chart-bar-square']] as [$label, $icon])<span class="inline-flex cursor-not-allowed items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2.5 text-sm font-medium text-muted" title="Modul manager ini belum tersedia"><x-filament::icon icon="{{ $icon }}" class="size-4" aria-hidden="true" />{{ $label }}<span class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-muted/70">Segera hadir</span></span>@endforeach</nav>
            </aside>
            <div class="min-w-0 flex-1"><header class="flex flex-wrap items-center justify-between gap-4 border-b border-border bg-surface px-5 py-4 lg:px-8"><div><p class="text-xs font-semibold uppercase tracking-wider text-muted">Ruang kerja manager</p><p class="mt-1 text-sm font-semibold text-foreground">{{ auth()->user()?->name }}</p></div><div class="flex items-center gap-4"><p class="hidden text-sm text-muted sm:block">{{ auth()->user()?->store?->name ?? 'Store' }}</p><form method="POST" action="{{ route('auth.logout') }}">@csrf<button class="nl-focus min-h-10 rounded-lg border border-border bg-surface px-3 text-sm font-bold text-secondary hover:border-primary hover:text-primary" type="submit">Keluar</button></form></div></header><main class="p-5 lg:p-8">{{ $slot }}</main></div>
        </div>
        @livewireScripts
    </body>
</html>
