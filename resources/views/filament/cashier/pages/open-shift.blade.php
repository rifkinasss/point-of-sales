<x-filament-panels::page>
    @if (session()->has('cashier.shift_closed_at'))
        <div class="open-shift-screen">
            <header class="open-shift-header">
                <div class="open-shift-identity">
                    <div class="open-shift-mark" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-building-storefront" />
                    </div>
                    <div>
                        <p class="open-shift-app-name">NasLabs ERP</p>
                        <p class="open-shift-context-line">
                            {{ auth()->user()?->store?->name ?? 'Store aktif' }}
                            <span aria-hidden="true">·</span>
                            {{ auth()->user()?->name ?? 'Cashier' }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-right-on-rectangle">
                        Keluar
                    </x-filament::button>
                </form>
            </header>

            <section class="open-shift-closed-state" aria-labelledby="closed-shift-heading">
                <div class="open-shift-state-icon" aria-hidden="true">
                    <x-filament::icon icon="heroicon-o-check-circle" />
                </div>
                <div>
                    <p class="open-shift-kicker">Shift selesai</p>
                    <h1 id="closed-shift-heading">Shift telah ditutup</h1>
                    <p>Kas akhir telah tercatat. Keluar dari akun ini sebelum pergantian kasir dan pembukaan shift berikutnya.</p>
                </div>
            </section>
        </div>
    @else
        <div
            class="open-shift-screen"
            x-data
            x-on:keydown.ctrl.enter.window.prevent="$refs.openShiftForm.requestSubmit()"
        >
            <header class="open-shift-header">
                <div class="open-shift-identity">
                    <div class="open-shift-mark" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-building-storefront" />
                    </div>
                    <div>
                        <p class="open-shift-app-name">NasLabs ERP</p>
                        <p class="open-shift-context-line">
                            {{ auth()->user()?->store?->name ?? 'Store aktif' }}
                            <span aria-hidden="true">·</span>
                            {{ auth()->user()?->name ?? 'Cashier' }}
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-right-on-rectangle">
                        Keluar
                    </x-filament::button>
                </form>
            </header>

            <section class="open-shift-hero" aria-labelledby="open-shift-heading">
                <p class="open-shift-kicker">Cashier workspace</p>
                <h1 id="open-shift-heading">Mulai Shift Kasir</h1>
                <p>Pastikan kas awal sudah dihitung. Shift harus dibuka sebelum menerima transaksi.</p>
            </section>

            <div class="open-shift-layout">
                <section class="open-shift-summary" aria-label="Konteks shift">
                    <article class="open-shift-summary-card open-shift-summary-card--status">
                        <div class="open-shift-summary-icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-lock-closed" />
                        </div>
                        <div>
                            <p>Status shift</p>
                            <strong>Belum dibuka</strong>
                        </div>
                    </article>

                    <article class="open-shift-summary-card">
                        <div class="open-shift-summary-icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-building-storefront" />
                        </div>
                        <div>
                            <p>Store aktif</p>
                            <strong>{{ auth()->user()?->store?->name ?? 'Store aktif' }}</strong>
                        </div>
                    </article>

                    <article class="open-shift-summary-card">
                        <div class="open-shift-summary-icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-user" />
                        </div>
                        <div>
                            <p>Cashier</p>
                            <strong>{{ auth()->user()?->name ?? 'Cashier' }}</strong>
                        </div>
                    </article>

                    <article class="open-shift-summary-card">
                        <div class="open-shift-summary-icon" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-calendar-days" />
                        </div>
                        <div>
                            <p>Hari ini</p>
                            <strong>{{ now()->format('d M Y') }}</strong>
                            <small>{{ now()->format('H:i') }} WITA</small>
                        </div>
                    </article>
                </section>

                <x-filament::section class="open-shift-form-card" icon="heroicon-o-banknotes" icon-color="primary">
                    <x-slot name="heading">Catat kas awal</x-slot>
                    <x-slot name="description">Masukkan jumlah uang fisik di laci sebelum mulai melayani transaksi.</x-slot>

                    <form x-ref="openShiftForm" wire:submit="openShift" class="open-shift-form">
                        {{ $this->form }}

                        <x-filament::button
                            class="open-shift-submit"
                            type="submit"
                            icon="heroicon-o-lock-open"
                            wire:loading.attr="disabled"
                            wire:target="openShift"
                        >
                            <span wire:loading.remove wire:target="openShift">Buka shift dan mulai kasir</span>
                            <span wire:loading wire:target="openShift">Menyiapkan POS...</span>
                        </x-filament::button>

                        <p class="open-shift-submit-hint">
                            Tekan <kbd>Ctrl</kbd> + <kbd>Enter</kbd> untuk membuka shift
                        </p>
                    </form>
                </x-filament::section>
            </div>
        </div>
    @endif
</x-filament-panels::page>
