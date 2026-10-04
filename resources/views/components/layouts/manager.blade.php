@php
    $browserTimezone = app(\App\Services\BrowserTimezone::class);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Manager Workspace' }} · NasLabs ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="nl-page min-h-screen antialiased" x-data="{ sidebarOpen: false }">
    <div x-cloak x-show="sidebarOpen" x-on:click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-950/30 lg:hidden"
        aria-hidden="true"></div>
    <div class="min-h-screen lg:flex">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-border bg-surface px-5 py-5 transition-transform lg:static lg:min-h-screen lg:w-64 lg:translate-x-0"
            x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            aria-label="Sidebar manager">
            <div class="flex items-center justify-between">
                <a class="inline-flex items-center gap-3 text-foreground" href="{{ route('manager.dashboard') }}"><span
                        class="grid size-9 place-items-center rounded-lg bg-primary text-sm font-black text-white">N</span><span><span
                            class="block text-sm font-black tracking-tight">NasLabs ERP</span><span
                            class="block text-xs text-muted">Manager Workspace</span></span></a>
                <button
                    class="nl-focus grid size-9 place-items-center rounded-lg text-muted hover:bg-background hover:text-foreground lg:hidden"
                    type="button" x-on:click="sidebarOpen = false" aria-label="Tutup navigasi"><x-filament::icon
                        icon="heroicon-o-x-mark" class="size-5" aria-hidden="true" /></button>
            </div>
            <div class="mt-7 rounded-lg border border-border bg-background p-3">
                <div class="flex items-start gap-3"><span
                        class="grid size-9 shrink-0 place-items-center rounded-md bg-primary-soft text-primary"><x-filament::icon
                            icon="heroicon-o-building-storefront" class="size-5" aria-hidden="true" /></span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Store aktif</p>
                        <p class="mt-1 truncate text-sm font-semibold text-foreground">
                            {{ auth()->user()?->store?->name ?? 'Store belum dipilih' }}</p>
                        <p class="mt-1 text-xs text-muted">{{ auth()->user()?->role?->label() ?? 'Manager' }}</p>
                    </div>
                </div>
            </div>
            <nav class="mt-6 flex-1 space-y-1" aria-label="Navigasi manager">
                <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? 'dashboard') === 'dashboard' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}"
                    href="{{ route('manager.dashboard') }}"><x-filament::icon icon="heroicon-o-squares-2x2"
                        class="size-5" aria-hidden="true" />Dashboard</a>
                <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? '') === 'transactions' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}"
                    href="{{ route('manager.transactions.index') }}"><x-filament::icon icon="heroicon-o-receipt-percent"
                        class="size-5" aria-hidden="true" />Transaksi</a>
                @foreach ([['Shift Kasir', 'heroicon-o-clock', 'manager.shifts.index', 'shifts'], ['Kas & Rekonsiliasi', 'heroicon-o-banknotes', 'manager.cash.index', 'cash'], ['Produk & Stok', 'heroicon-o-cube', null, null], ['Pelanggan', 'heroicon-o-users', null, null], ['Promo', 'heroicon-o-tag', null, null], ['Laporan', 'heroicon-o-chart-bar-square', null, null]] as [$label, $icon, $route, $nav])
                    @if ($route)
                        <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? '') === $nav ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" href="{{ route($route) }}"><x-filament::icon icon="{{ $icon }}" class="size-5" aria-hidden="true" /><span>{{ $label }}</span></a>
                        @continue
                    @endif
                    <span
                        class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-muted"
                        title="Modul manager ini belum tersedia"><x-filament::icon icon="{{ $icon }}"
                            class="size-5" aria-hidden="true" /><span>{{ $label }}</span><span
                            class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-muted/70">Segera
                            hadir</span></span>
                @endforeach
            </nav>
            <div class="mt-6 border-t border-border pt-4">
                <p class="text-xs leading-5 text-muted">Pantau penjualan, kasir, kas, dan stok dari satu ruang kerja
                    operasional.</p>
            </div>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="border-b border-border bg-surface px-5 py-4 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3"><button
                            class="nl-focus grid size-10 place-items-center rounded-lg border border-border text-secondary hover:border-primary hover:text-primary lg:hidden"
                            type="button" x-on:click="sidebarOpen = true" aria-label="Buka navigasi"><x-filament::icon
                                icon="heroicon-o-bars-3" class="size-5" aria-hidden="true" /></button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted">
                                {{ $title ?? 'Manager Workspace' }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm"><span
                                    class="inline-flex items-center gap-1.5 font-semibold text-foreground"><x-filament::icon
                                        icon="heroicon-o-building-storefront" class="size-4 text-primary"
                                        aria-hidden="true" />{{ auth()->user()?->store?->name ?? 'Store' }}</span><span
                                    class="hidden items-center gap-1.5 text-muted sm:inline-flex"><x-filament::icon
                                        icon="heroicon-o-calendar-days" class="size-4"
                                        aria-hidden="true" /><x-local-date-time :value="now()"
                                        :formatted="$browserTimezone->format(now(), 'd M Y')" /></span><span
                                    class="hidden items-center gap-1.5 text-muted md:inline-flex"><x-filament::icon
                                        icon="heroicon-o-clock" class="size-4" aria-hidden="true" /><x-local-date-time
                                        :value="now()" :formatted="$browserTimezone->format(now(), 'H:i T')" /></span></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-foreground">{{ auth()->user()?->name }}</p>
                            <p class="mt-0.5 text-xs text-muted">Manager</p>
                        </div>
                        <form method="POST" action="{{ route('auth.logout') }}">@csrf<button
                                class="nl-focus inline-flex min-h-10 items-center gap-2 rounded-lg border border-border bg-surface px-3 text-sm font-bold text-secondary hover:border-primary hover:text-primary"
                                type="submit"><x-filament::icon icon="heroicon-o-arrow-right-on-rectangle"
                                    class="size-4" aria-hidden="true" />Keluar</button></form>
                    </div>
                </div>
            </header>
            <main class="p-5 lg:p-8">{{ $slot }}</main>
        </div>
    </div>
    @livewireScripts
</body>

</html>
