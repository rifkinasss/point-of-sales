@php
    $storeContext = app(\App\Services\ActiveStoreContext::class);
    $owner = auth()->user();
    $availableStores = $owner ? $storeContext->availableStores($owner) : collect();
    $storeScope = $owner ? $storeContext->scope($owner) : null;
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Owner Workspace' }} · NasLabs ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="nl-page flex h-screen flex-col overflow-hidden antialiased" x-data="{
    sidebarOpen: false,
    sidebarCollapsed: false,
    isDesktop() { return window.matchMedia('(min-width: 1024px)').matches },
    labelsVisible() { return !this.isDesktop() || !this.sidebarCollapsed },
    toggleSidebar() {
        if (this.isDesktop()) {
            this.sidebarCollapsed = !this.sidebarCollapsed
            return
        }
        this.sidebarOpen = true
    }
}" x-init="sidebarCollapsed = isDesktop() && localStorage.getItem('ownerSidebarCollapsed') === 'true';
$watch('sidebarCollapsed', value => localStorage.setItem('ownerSidebarCollapsed', value))"
    x-on:keydown.escape.window="sidebarOpen = false">
    <div x-cloak x-show="sidebarOpen" x-on:click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-950/30 lg:hidden" aria-hidden="true"></div>
    <x-account-switcher-banner />
    <div class="flex min-h-0 flex-1">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-border bg-surface px-5 py-5 transition-[width,transform] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:w-[var(--owner-sidebar-width)]"
            style="--owner-sidebar-width: 16rem"
            x-bind:style="'--owner-sidebar-width: ' + (isDesktop() && sidebarCollapsed ? '4.5rem' : '16rem')"
            x-bind:class="(sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0') + (isDesktop() && sidebarCollapsed ? ' px-2' : '')"
            aria-label="Sidebar owner">
            <div class="flex items-center justify-between"
                x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center' : ''">
                <a class="inline-flex min-w-0 items-center gap-3 text-foreground" href="{{ route('owner.dashboard') }}">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary text-sm font-black text-white">N</span>
                    <span class="min-w-0" x-show="labelsVisible()" x-cloak>
                        <span class="block truncate text-sm font-black tracking-tight">NasLabs ERP</span>
                        <span class="block truncate text-xs text-muted">Owner Workspace</span>
                    </span>
                </a>
                <button class="nl-focus grid size-9 shrink-0 place-items-center rounded-lg text-muted hover:bg-background hover:text-foreground lg:hidden"
                    type="button" x-on:click="sidebarOpen = false" aria-label="Tutup navigasi" title="Tutup navigasi">
                    <x-filament::icon icon="heroicon-o-x-mark" class="size-5" aria-hidden="true" />
                </button>
            </div>

            <div class="relative mt-7" x-data="{ storeMenuOpen: false }"
                x-on:keydown.escape.window="storeMenuOpen = false">
                <button class="nl-focus w-full rounded-lg border border-border bg-background p-3 text-left hover:border-primary/50 hover:bg-primary-soft/40"
                    x-bind:class="isDesktop() && sidebarCollapsed ? 'border-transparent bg-transparent p-1' : ''"
                    type="button" x-on:click="storeMenuOpen = !storeMenuOpen"
                    x-bind:aria-expanded="storeMenuOpen" @disabled($availableStores->count() <= 1)
                    aria-controls="owner-store-menu" aria-label="Pilih konteks bisnis"
                    x-bind:title="sidebarCollapsed ? '{{ $storeScope?->all ? 'Semua cabang' : $storeScope?->selected()?->name ?? 'Store belum dipilih' }}' : ''">
                    <span class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-primary-soft text-primary">
                            <x-filament::icon icon="heroicon-o-building-storefront" class="size-5" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1" x-show="labelsVisible()" x-cloak>
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Konteks bisnis</span>
                                @if ($availableStores->count() > 1)
                                    <x-filament::icon icon="heroicon-o-chevron-down" class="size-4 shrink-0 text-muted"
                                        x-bind:class="storeMenuOpen ? 'rotate-180' : ''" aria-hidden="true" />
                                @endif
                            </span>
                            <span class="mt-1 block truncate text-sm font-semibold text-foreground">
                                {{ $storeScope?->all ? 'Semua Cabang' : $storeScope?->selected()?->name ?? 'Store belum dipilih' }}
                            </span>
                            <span class="mt-1 block truncate text-xs text-muted">
                                {{ $storeScope?->all ? $availableStores->count().' cabang aktif' : ($storeScope?->selected()?->branch_code ?: 'Store utama') }}
                            </span>
                        </span>
                        <span class="sr-only" x-show="!labelsVisible()">Pilih konteks bisnis</span>
                    </span>
                </button>
                @if ($availableStores->count() > 1)
                    <div id="owner-store-menu"
                        class="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-lg border border-border bg-surface p-1 shadow-lg"
                        x-bind:class="isDesktop() && sidebarCollapsed ? 'left-full top-0 ml-2 mt-0 w-72' : ''"
                        x-cloak x-show="storeMenuOpen" x-transition.origin.top
                        x-on:click.outside="storeMenuOpen = false">
                        <p class="px-3 py-2 text-[11px] font-bold uppercase tracking-wider text-muted">Pilih konteks</p>
                        <form method="POST" action="{{ route('owner.store-context.switch') }}">
                            @csrf
                            <button class="nl-focus flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm hover:bg-primary-soft"
                                type="submit" name="store" value="all">
                                <x-filament::icon icon="heroicon-o-squares-2x2" class="size-5 shrink-0 text-muted" aria-hidden="true" />
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-foreground">Semua Cabang</span>
                                    <span class="block text-xs text-muted">{{ $availableStores->count() }} cabang aktif</span>
                                </span>
                                @if ($storeScope?->all)<x-filament::icon icon="heroicon-o-check" class="size-4 text-primary" aria-hidden="true" />@endif
                            </button>
                        </form>
                        <div class="my-1 border-t border-border"></div>
                        @foreach ($availableStores as $store)
                            <form method="POST" action="{{ route('owner.store-context.switch') }}">
                                @csrf
                                <button class="nl-focus flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm hover:bg-primary-soft"
                                    type="submit" name="store" value="{{ $store->public_id }}">
                                    <x-filament::icon icon="heroicon-o-building-storefront" class="size-5 shrink-0 text-muted" aria-hidden="true" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold text-foreground">{{ $store->name }}</span>
                                        <span class="block truncate text-xs text-muted">{{ $store->branch_code ?: 'Store utama' }}</span>
                                    </span>
                                    @if (!$storeScope?->all && $storeScope?->selected()?->is($store))
                                        <x-filament::icon icon="heroicon-o-check" class="size-4 shrink-0 text-primary" aria-hidden="true" />
                                    @endif
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>

            <nav class="mt-6 min-h-0 flex-1 space-y-5 overflow-y-auto pr-1" aria-label="Navigasi owner">
                <div>
                    <p class="px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-muted" x-show="labelsVisible()" x-cloak>Overview</p>
                    <a class="nl-focus relative mt-2 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-bold {{ ($activeNav ?? 'dashboard') === 'dashboard' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}"
                        x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="Dashboard" href="{{ route('owner.dashboard') }}">
                        <x-filament::icon icon="heroicon-o-squares-2x2" class="size-5 shrink-0" aria-hidden="true" />
                        <span x-show="labelsVisible()" x-cloak>Dashboard</span>
                    </a>
                </div>
                @foreach ([
                    'Operasional' => [['Transaksi', 'heroicon-o-receipt-percent'], ['Kas & Rekonsiliasi', 'heroicon-o-banknotes']],
                    'Master Data' => [['Toko & Cabang', 'heroicon-o-building-storefront'], ['Produk & Stok', 'heroicon-o-cube'], ['Pelanggan', 'heroicon-o-users'], ['Promo', 'heroicon-o-tag']],
                    'Organisasi' => [['Manager & Staff', 'heroicon-o-user-group']],
                    'Analisis' => [['Laporan', 'heroicon-o-chart-bar-square']],
                    'Akun' => [['Pengaturan', 'heroicon-o-cog-6-tooth']],
                ] as $group => $items)
                    <div>
                        <p class="px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-muted" x-show="labelsVisible()" x-cloak>{{ $group }}</p>
                        <div class="mt-2 space-y-1">
                            @foreach ($items as [$label, $icon])
                                @if ($label === 'Transaksi')
                                    <a href="{{ route('owner.transactions.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'transactions' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}"><x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span></a>
                                @elseif ($label === 'Kas & Rekonsiliasi')
                                    <a href="{{ route('owner.cash-reconciliation.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'cash' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}"><x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span></a>
                                @elseif ($label === 'Toko & Cabang')
                                    <a href="{{ route('owner.stores.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'stores' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}"
                                        x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" />
                                        <span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @elseif ($label === 'Produk & Stok')
                                    <a href="{{ route('owner.products') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'products' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}"
                                        x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" />
                                        <span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @elseif ($label === 'Pelanggan')
                                    <a href="{{ route('owner.customers.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'customers' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @elseif ($label === 'Promo')
                                    <a href="{{ route('owner.promotions.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'promotions' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @elseif ($label === 'Manager & Staff')
                                    <a href="{{ route('owner.staff.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'staff' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}"
                                        x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" />
                                        <span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @elseif ($label === 'Laporan')
                                    <a href="{{ route('owner.reports.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'reports' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @elseif ($label === 'Pengaturan')
                                    <a href="{{ route('owner.settings.index') }}" class="nl-focus relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ ($activeNav ?? '') === 'settings' ? 'bg-primary-soft text-primary before:absolute before:left-0 before:h-6 before:w-1 before:rounded-r-full before:bg-primary' : 'text-muted hover:bg-background hover:text-foreground' }}" x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''" title="{{ $label }}">
                                        <x-filament::icon icon="{{ $icon }}" class="size-5 shrink-0" aria-hidden="true" /><span x-show="labelsVisible()" x-cloak>{{ $label }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="mt-6 border-t border-border pt-4">
                <p class="text-xs leading-5 text-muted" x-show="labelsVisible()" x-cloak>
                    Ringkasan bisnis lintas cabang dalam satu ruang kerja.
                </p>
                <form class="mt-4" method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button class="nl-focus flex w-full items-center gap-3 rounded-lg border border-border px-3 py-2.5 text-sm font-bold text-secondary hover:border-primary hover:text-primary"
                        x-bind:class="isDesktop() && sidebarCollapsed ? 'justify-center px-2' : ''"
                        type="submit" aria-label="Keluar" title="Keluar">
                        <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="size-4 shrink-0" aria-hidden="true" />
                        <span x-show="labelsVisible()" x-cloak>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 shrink-0 border-b border-border bg-surface px-5 py-4 lg:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button class="nl-focus grid size-10 place-items-center rounded-lg border border-border text-secondary hover:border-primary hover:text-primary"
                            type="button" x-on:click="toggleSidebar()" x-bind:aria-label="sidebarCollapsed ? 'Buka sidebar' : 'Buka navigasi'" title="Navigasi">
                            <x-filament::icon icon="heroicon-o-bars-3" class="size-5" aria-hidden="true" />
                        </button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $title ?? 'Dashboard Owner' }}</p>
                            <p class="mt-1 text-sm font-semibold text-foreground">{{ $storeScope?->all ? 'Ringkasan seluruh cabang' : $storeScope?->selected()?->name ?? 'Konteks bisnis' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden text-sm font-semibold text-secondary sm:inline">{{ $owner?->name }} · Owner</span>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button class="nl-focus inline-flex min-h-10 items-center gap-2 rounded-lg border border-border bg-surface px-3 text-sm font-bold text-secondary hover:border-primary hover:text-primary"
                                type="submit"><x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="size-4" aria-hidden="true" />Keluar</button>
                        </form>
                    </div>
                </div>
            </header>
            <main class="min-h-0 flex-1 overflow-y-auto p-5 lg:p-8">{{ $slot }}</main>
        </div>
    </div>
    @livewireScripts
</body>

</html>
