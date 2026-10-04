<x-filament-panels::page>
    @if ($shift)
        <x-filament::section class="mb-6">
            <x-slot name="heading">Ringkasan shift</x-slot>

            <div class="grid gap-4 text-sm md:grid-cols-2">
                <div>Kas awal: Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</div>
                <div>Dibuka: {{ $shift->opened_at->format('d M Y H:i') }}</div>
            </div>

            <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">Sebelum keluar, hitung seluruh uang fisik di laci. Jika ada selisih, jelaskan penyebabnya pada catatan penutupan.</p>
        </x-filament::section>

        <form wire:submit="closeShift">
            {{ $this->form }}

            <x-filament::button class="mt-6" type="submit" color="danger">
                Tutup shift dan catat kas akhir
            </x-filament::button>
        </form>
    @else
        <x-filament::section>
            Tidak ada shift aktif untuk akun ini.
        </x-filament::section>
    @endif
</x-filament-panels::page>
