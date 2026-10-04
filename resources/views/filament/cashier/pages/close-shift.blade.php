<x-filament-panels::page>
    @if ($shift)
        <div class="mx-auto grid max-w-6xl gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(20rem,0.8fr)]">
            <div class="space-y-6">
                <x-filament::section>
                    <x-slot name="heading">Ringkasan shift</x-slot>
                    <x-slot name="description">Dibuka {{ $shift->opened_at->format('d M Y H:i') }}</x-slot>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([['Kas awal', $this->cashSummary['opening'], 'bg-slate-50 text-slate-700'], ['Penjualan tunai', $this->cashSummary['sales'], 'bg-emerald-50 text-emerald-700'], ['Kas masuk', $this->cashSummary['cashIn'], 'bg-sky-50 text-sky-700'], ['Refund', $this->cashSummary['refunds'], 'bg-rose-50 text-rose-700'], ['Kas keluar', $this->cashSummary['cashOut'], 'bg-amber-50 text-amber-700'], ['Kas diharapkan', $this->cashSummary['expected'], 'bg-indigo-50 text-indigo-700']] as [$label, $amount, $tone])
                            <div class="rounded-xl p-4 {{ $tone }} dark:bg-white/5 dark:text-gray-200">
                                <p class="text-xs font-semibold uppercase tracking-wide opacity-70">{{ $label }}</p>
                                <p class="mt-2 text-lg font-black">Rp {{ number_format($amount, 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>

                    <p class="mt-5 text-sm text-amber-700 dark:text-amber-300">Sebelum keluar, hitung seluruh uang fisik di laci. Jika ada selisih, jelaskan penyebabnya pada catatan penutupan.</p>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Catat pergerakan kas</x-slot>
                    <x-slot name="description">Gunakan untuk kas masuk atau kas keluar di luar pembayaran penjualan.</x-slot>
                    <form class="grid gap-4 md:grid-cols-[12rem_minmax(0,1fr)_minmax(0,1.4fr)_auto] md:items-end" wire:submit="recordCashMovement">
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="movement-type">Jenis</label>
                            <select id="movement-type" class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm dark:border-white/10 dark:bg-white/5" wire:model="movementType">
                                <option value="cash_in">Kas masuk</option>
                                <option value="cash_out">Kas keluar</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="movement-amount">Nominal</label>
                            <x-filament::input.wrapper prefix="Rp"><x-filament::input id="movement-amount" min="1" type="number" wire:model="movementAmount" /></x-filament::input.wrapper>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium" for="movement-note">Catatan</label>
                            <x-filament::input.wrapper><x-filament::input id="movement-note" wire:model="movementNote" placeholder="Contoh: uang kembalian tambahan" /></x-filament::input.wrapper>
                        </div>
                        <x-filament::button type="submit" color="gray">Catat</x-filament::button>
                    </form>
                </x-filament::section>
            </div>

            <x-filament::section>
                <x-slot name="heading">Tutup shift</x-slot>
                <x-slot name="description">Bandingkan kas fisik dengan kas yang diharapkan sebelum mengunci shift.</x-slot>
                <form wire:submit="closeShift">
                    {{ $this->form }}

                    <x-filament::button class="mt-6 w-full" type="submit" color="danger" icon="heroicon-o-lock-closed">
                        Tutup shift dan catat kas akhir
                    </x-filament::button>
                </form>
            </x-filament::section>
        </div>
    @else
        <x-filament::section>
            Tidak ada shift aktif untuk akun ini.
        </x-filament::section>
    @endif
</x-filament-panels::page>
