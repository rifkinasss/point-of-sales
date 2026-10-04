<x-filament-panels::page>
    @if (session()->has('cashier.shift_closed_at'))
        <x-filament::section icon="heroicon-o-check-circle" icon-color="success">
            <x-slot name="heading">Shift telah ditutup</x-slot>

            <p class="text-sm text-gray-600 dark:text-gray-300">Kas akhir telah tercatat. Demi keamanan pergantian kasir, keluar dari akun ini sebelum shift baru dibuka.</p>

            <form class="mt-6" method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <x-filament::button type="submit" color="gray" icon="heroicon-o-arrow-right-on-rectangle">
                    Keluar dari akun
                </x-filament::button>
            </form>
        </x-filament::section>
    @else
        <x-filament::section icon="heroicon-o-banknotes" icon-color="primary">
            <x-slot name="heading">Mulai shift kasir</x-slot>

            <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">Hitung uang fisik yang tersedia di laci kasir, lalu catat sebagai setoran awal. POS akan aktif setelah shift dibuka.</p>

            <form wire:submit="openShift">
                {{ $this->form }}

                <x-filament::button class="mt-6" type="submit" icon="heroicon-o-lock-open">
                    Buka shift dan mulai kasir
                </x-filament::button>
            </form>
        </x-filament::section>
    @endif
</x-filament-panels::page>
