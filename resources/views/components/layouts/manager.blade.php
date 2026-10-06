@php
    $browserTimezone = app(\App\Services\BrowserTimezone::class);
    $storeContext = app(\App\Services\ActiveStoreContext::class);
    $manager = auth()->user();
    $availableStores = $manager ? $storeContext->availableStores($manager) : collect();
    $storeScope = $manager ? $storeContext->scope($manager) : null;
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

<body class="nl-page h-screen overflow-hidden antialiased" x-data="{
    sidebarOpen: false,
    sidebarCollapsed: false,
    isDesktop() {
        return window.matchMedia('(min-width: 1024px)').matches
    },
    labelsVisible() {
        return !this.isDesktop() || !this.sidebarCollapsed
    },
    toggleSidebar() {
        if (this.isDesktop()) {
            this.sidebarCollapsed = !this.sidebarCollapsed
            return
        }
        this.sidebarOpen = true
    },
    closeSidebar() { this.sidebarOpen = false }
}" x-init="sidebarCollapsed = isDesktop() && localStorage.getItem('managerSidebarCollapsed') === 'true';
$watch('sidebarCollapsed', value => localStorage.setItem('managerSidebarCollapsed', value))"
    x-on:keydown.escape.window="sidebarOpen = false">
    <div x-cloak x-show="sidebarOpen" x-on:click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-950/30 lg:hidden" aria-hidden="true"></div>
    <div class="flex h-full">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-border bg-surface px-5 py-5 transition-[width,transform] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:w-[var(--manager-sidebar-width)]"
            style="--manager-sidebar-width: 16rem"
            x-bind:style="'--manager-sidebar-width: ' + (isDesktop() && sidebarCollapsed ? '4.5rem' : '16rem')"
            x-bind:class="(sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0') + (isDesktop() && sidebarCollapsed ? ' px-2' : '')"
            aria-label="Sidebar manager">
            <div class="flex items-center justify-between" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center' : ''">
                <a class="inline-flex min-w-0 items-center gap-3 text-foreground"
                    href="{{ route('manager.dashboard') }}">
                    <span
                        class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary text-sm font-black text-white">N</span>
                    <span class="min-w-0" x-show="labelsVisible()" x-cloak>
                        <span class="block truncate text-sm font-black tracking-tight">NasLabs ERP</span>
                        <span class="block truncate text-xs text-muted">Manager Workspace</span>
                    </span>
                </a>
                <button
                    class="nl-focus grid size-9 shrink-0 place-items-center rounded-lg text-muted hover:bg-background hover:text-foreground lg:hidden"
                    type="button" x-on:click="closeSidebar()" aria-label="Tutup navigasi"
                    title="Tutup navigasi"><x-filament::icon icon="heroicon-o-x-mark" class="size-5"
                        aria-hidden="true" /></button>
            </div>
            <div class="relative mt-7" x-data="{ storeMenuOpen: false }" x-on:keydown.escape.window="storeMenuOpen = false">
                <button
                    class="nl-focus w-full rounded-lg border border-border bg-background p-3 text-left hover:border-primary/50 hover:bg-primary-soft/40"
                    x-bind:class="isDesktop() && sidebarCollapsed ? 'border-transparent bg-transparent p-1' : ''" type="button"
                    x-on:click="storeMenuOpen = !storeMenuOpen" x-bind:aria-expanded="storeMenuOpen"
                    @disabled($availableStores->count() <= 1) aria-controls="manager-store-menu" aria-label="Pilih store aktif"
                    x-bind:title="sidebarCollapsed ?
                        '{{ $storeScope?->all ? 'Semua cabang' : $storeScope?->selected()?->name ?? 'Store belum dipilih' }}' :
                        ''">
                    <span class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-primary-soft text-primary">
                            <x-filament::icon icon="heroicon-o-building-storefront" class="size-5" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1" x-show="labelsVisible()" x-cloak>
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Store
                                    aktif</span>
                                @if ($availableStores->count() > 1)
                                    <x-filament::icon icon="heroicon-o-chevron-down"
                                        class="size-4 shrink-0 text-muted transition-transform"
                                        x-bind:class="storeMenuOpen ? 'rotate-180' : ''" aria-hidden="true" />
                                @endif
                            </span>
                            <span class="mt-1 block truncate text-sm font-semibold text-foreground">
                                {{ $storeScope?->all ? 'Semua cabang' : $storeScope?->selected()?->name ?? 'Store belum dipilih' }}
                            </span>
                            @if (!$storeScope?->all && $storeScope?->selected()?->branch_code)
                                <span
                                    class="mt-1 block truncate text-xs text-muted">{{ $storeScope->selected()->branch_code }}</span>
                            @else
                                <span
                                    class="mt-1 block text-xs text-muted">{{ $storeScope?->all ? 'Ringkasan seluruh cabang' : 'Store utama' }}</span>
                            @endif
                        </span>
                        <span class="sr-only" x-show="!labelsVisible()">Pilih store aktif</span>
                    </span>
                </button>

                @if ($availableStores->count() > 1)
                    <div id="manager-store-menu"
                        class="absolute z-50 overflow-hidden rounded-lg border border-border bg-surface p-1 shadow-lg"
                        x-bind:class="isDesktop() && sidebarCollapsed ? 'left-full top-0 ml-2 w-72' : 'inset-x-0 top-full mt-2'"
                        x-cloak x-show="storeMenuOpen" x-transition.origin.top
                        x-on:click.outside="storeMenuOpen = false">
                        <form method="POST" action="{{ route('manager.store-context.switch') }}">
                            @csrf
                            <button
                                class="nl-focus flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm hover:bg-background"
                                type="submit" name="store" value="all">
                                <span
                                    class="grid size-7 shrink-0 place-items-center rounded-md bg-background text-muted">
                                    <x-filament::icon icon="heroicon-o-squares-2x2" class="size-4" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-foreground">Semua cabang</span>
                                    <span class="block truncate text-xs text-muted">Ringkasan read-only</span>
                                </span>
                                @if ($storeScope?->all)
                                    <x-filament::icon icon="heroicon-o-check" class="size-4 shrink-0 text-primary"
                                        aria-hidden="true" />
                                @endif
                            </button>
                        </form>
                        <div class="my-1 border-t border-border"></div>
                        @foreach ($availableStores as $store)
                            <form method="POST" action="{{ route('manager.store-context.switch') }}">
                                @csrf
                                <button
                                    class="nl-focus flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm hover:bg-primary-soft"
                                    type="submit" name="store" value="{{ $store->public_id }}">
                                    <span
                                        class="grid size-7 shrink-0 place-items-center rounded-md bg-background text-muted">
                                        <x-filament::icon icon="heroicon-o-building-storefront" class="size-4"
                                            aria-hidden="true" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block truncate font-semibold text-foreground">{{ $store->name }}</span>
                                        <span
                                            class="block truncate text-xs text-muted">{{ $store->branch_code ?: 'Store utama' }}</span>
                                    </span>
                                    @if (!$storeScope?->all && $storeScope?->selected()?->is($store))
                                        <x-filament::icon icon="heroicon-o-check" class="size-4 shrink-0 text-primary"
                                            aria-hidden="true" />
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
            <nav class="mt-6 min-h-0 flex-1 space-y-1 overflow-y-auto pr-1" aria-label="Navigasi manager">
                <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? 'dashboard') === 'dashboard' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="Dashboard"
                    href="{{ route('manager.dashboard') }}"><x-filament::icon icon="heroicon-o-squares-2x2"
                        class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>Dashboard</span></a>
                <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? '') === 'transactions' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="Transaksi"
                    href="{{ route('manager.transactions.index') }}"><x-filament::icon
                        icon="heroicon-o-receipt-percent" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>Transaksi</span></a>
                @foreach ([['Shift Kasir', 'heroicon-o-clock', 'manager.shifts.index', 'shifts'], ['Kas & Rekonsiliasi', 'heroicon-o-banknotes', 'manager.cash.index', 'cash'], ['Produk & Stok', 'heroicon-o-cube', 'manager.products.index', 'products'], ['Pelanggan', 'heroicon-o-users', 'manager.customers.index', 'customers'], ['Promo', 'heroicon-o-tag', 'manager.promotions.index', 'promotions'], ['Laporan', 'heroicon-o-chart-bar-square', 'manager.reports.index', 'reports']] as [$label, $icon, $route, $nav])
                    @if ($route)
                        <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? '') === $nav ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}"
                            href="{{ route($route) }}"><x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0"
                                aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span></a>
                        @continue
                    @endif
                    <span
                        class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-muted" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''"
                        title="Modul manager ini belum tersedia"><x-filament::icon icon="{{ $icon }}"
                            class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span><span x-show="labelsVisible()" x-cloak
                            class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-muted/70">Segera
                            hadir</span></span>
                @endforeach
                <div class="my-4 border-t border-border"></div>
                <a class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? '') === 'settings' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''"
                    href="{{ route('manager.settings') }}"><x-filament::icon icon="heroicon-o-cog-6-tooth"
                        class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>Pengaturan</span></a>
            </nav>
            <div class="mt-6 border-t border-border pt-4">
                <p class="text-xs leading-5 text-muted" x-show="labelsVisible()" x-cloak>Pantau penjualan, kasir, kas, dan stok dari satu ruang kerja
                    operasional.</p>
                <div class="mt-4 flex items-center justify-between gap-3" x-bind:class="!labelsVisible() ? 'justify-center' : ''">
                    <div class="min-w-0" x-show="labelsVisible()" x-cloak>
                        <p class="truncate text-sm font-semibold text-foreground">{{ $manager?->name }}</p>
                        <p class="text-xs text-muted">Manager</p>
                    </div>
                    <form method="POST" action="{{ route('auth.logout') }}">@csrf
                        <button
                            class="nl-focus grid size-9 shrink-0 place-items-center rounded-lg border border-border text-muted hover:border-primary hover:text-primary"
                            type="submit" aria-label="Keluar" title="Keluar">
                            <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="size-4"
                                aria-hidden="true" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 shrink-0 border-b border-border bg-surface px-5 py-4 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3"><button
                            class="nl-focus grid size-10 place-items-center rounded-lg border border-border text-secondary hover:border-primary hover:text-primary"
                            type="button" x-on:click="toggleSidebar()"
                            x-bind:aria-expanded="sidebarOpen || !sidebarCollapsed"
                            x-bind:aria-label="sidebarCollapsed ? 'Buka sidebar' : 'Buka navigasi'"
                            title="Navigasi"><x-filament::icon icon="heroicon-o-bars-3" class="size-5"
                                aria-hidden="true" /></button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted">
                                {{ $title ?? 'Manager Workspace' }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm"><span
                                    class="inline-flex items-center gap-1.5 font-semibold text-foreground"><x-filament::icon
                                        icon="heroicon-o-building-storefront" class="size-4 text-primary"
                                        aria-hidden="true" />{{ $storeScope?->all ? 'Semua cabang' : $storeScope?->selected()?->name ?? 'Store' }}</span><span
                                    class="hidden items-center gap-1.5 text-muted sm:inline-flex"><x-filament::icon
                                        icon="heroicon-o-calendar-days" class="size-4"
                                        aria-hidden="true" /><x-local-date-time :value="now()"
                                        :formatted="$browserTimezone->format(now(), 'd M Y')" /></span><span
                                    class="hidden items-center gap-1.5 text-muted md:inline-flex"><x-filament::icon
                                        icon="heroicon-o-clock" class="size-4"
                                        aria-hidden="true" /><x-local-date-time :value="now()"
                                        :formatted="$browserTimezone->format(now(), 'H:i T')" /></span></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($availableStores->count() > 0)
                            <form method="POST" action="{{ route('manager.store-context.switch') }}"
                                class="hidden sm:block">
                                @csrf
                                <label class="sr-only" for="manager-store-context">Store aktif</label>
                                <select id="manager-store-context" name="store" onchange="this.form.submit()"
                                    class="nl-focus min-h-10 max-w-56 rounded-lg border border-border bg-surface px-3 text-sm font-semibold text-secondary">
                                    @if ($availableStores->count() > 1)
                                        <option value="all" @selected($storeScope?->all)>Semua cabang</option>
                                    @endif
                                    @foreach ($availableStores as $store)
                                        <option value="{{ $store->public_id }}" @selected(!$storeScope?->all && $storeScope?->selected()?->is($store))>
                                            {{ $store->name }}</option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('auth.logout') }}" class="hidden sm:block">
                            @csrf<button
                                class="nl-focus inline-flex min-h-10 items-center gap-2 rounded-lg border border-border bg-surface px-3 text-sm font-bold text-secondary hover:border-primary hover:text-primary"
                                type="submit"><x-filament::icon icon="heroicon-o-arrow-right-on-rectangle"
                                    class="size-4" aria-hidden="true" />Keluar</button></form>
                    </div>
                </div>
            </header>
            <main class="min-h-0 flex-1 overflow-y-auto p-5 lg:p-8">{{ $slot }}</main>
        </div>
    </div>
    @livewireScripts
</body>

</html>
