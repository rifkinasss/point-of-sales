<div class="min-h-screen bg-background px-4 py-5 sm:px-6 lg:px-8" x-data>
    @php
        $user = auth()->user();
        $summary = $this->cashSummary;
        $actualCash = $closingCashActual === '' ? null : (int) $closingCashActual;
        $variance = $actualCash === null ? null : $actualCash - $summary['expected'];
        $movements = $shift?->cashMovements?->sortByDesc('created_at') ?? collect();
    @endphp

    <header class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 border-b border-border pb-4">
        <div class="flex min-w-0 items-center gap-3">
            <a class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary text-sm font-black text-white" href="{{ url('/cashier/pos') }}" aria-label="Kembali ke POS">N</a>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-primary">NasLabs POS</p>
                <p class="truncate text-sm font-bold text-foreground">{{ $user?->store?->name ?? 'Toko aktif' }}</p>
                <p class="truncate text-xs text-muted">{{ $user?->name ?? 'Kasir' }} · Rekonsiliasi shift</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a class="nl-focus inline-flex min-h-11 items-center gap-2 rounded-lg border border-border bg-surface px-3 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary" href="{{ url('/cashier/pos') }}">
                <x-filament::icon icon="heroicon-o-arrow-left" class="size-4" aria-hidden="true" />
                Kembali ke POS
            </a>
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button class="nl-focus inline-flex min-h-11 items-center gap-2 rounded-lg border border-border bg-surface px-3 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary" type="submit" aria-label="Keluar dari akun">
                    <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="size-4" aria-hidden="true" />
                    Keluar
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-6xl py-7 sm:py-8">
        <section class="flex flex-col justify-between gap-5 border-b border-border pb-7 lg:flex-row lg:items-end">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Shift kasir</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-foreground sm:text-4xl">Tutup Shift</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-muted">Rekonsiliasi kas fisik sebelum mengakhiri shift. Periksa angka, catat selisih bila ada, lalu konfirmasi penutupan.</p>
            </div>
            @if ($shift)
                <div class="flex shrink-0 flex-wrap items-center gap-3 text-sm text-muted">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 font-bold text-emerald-700">
                        <x-filament::icon icon="heroicon-o-check-circle" class="size-4" aria-hidden="true" />
                        Shift aktif
                    </span>
                    <span>Dibuka <x-local-date-time :value="$shift->opened_at" /></span>
                </div>
            @endif
        </section>

        @if ($message)
            <p class="mt-5 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800" role="status">
                <x-filament::icon icon="heroicon-o-information-circle" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                {{ $message }}
            </p>
        @endif

        @if ($shift)
            <section class="mt-7" aria-labelledby="shift-summary-title">
                <div class="mb-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-muted">Ringkasan finansial</p>
                        <h2 id="shift-summary-title" class="mt-1 text-lg font-bold text-foreground">Posisi kas selama shift</h2>
                    </div>
                    <span class="hidden text-xs text-muted sm:block">{{ $movements->count() }} pergerakan tercatat</span>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                    @foreach ([['Kas awal', $summary['opening'], 'heroicon-o-banknotes'], ['Penjualan tunai', $summary['sales'], 'heroicon-o-shopping-bag'], ['Refund tunai', $summary['refunds'], 'heroicon-o-arrow-uturn-left'], ['Kas masuk', $summary['cashIn'], 'heroicon-o-arrow-down-tray'], ['Kas keluar', $summary['cashOut'], 'heroicon-o-arrow-up-tray']] as [$label, $amount, $icon])
                        <div class="rounded-lg border border-border bg-surface p-4">
                            <x-filament::icon icon="{{ $icon }}" class="size-4 text-muted" aria-hidden="true" />
                            <p class="mt-4 text-xs font-medium text-muted">{{ $label }}</p>
                            <p class="mt-1 text-base font-bold text-foreground">Rp {{ number_format($amount, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                    <div class="rounded-lg border border-primary bg-primary p-4 text-white shadow-sm">
                        <x-filament::icon icon="heroicon-o-calculator" class="size-4 text-blue-100" aria-hidden="true" />
                        <p class="mt-4 text-xs font-medium text-blue-100">Kas diharapkan</p>
                        <p class="mt-1 text-lg font-black">Rp {{ number_format($summary['expected'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </section>

            <section class="mt-7 grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(19rem,0.8fr)]" aria-labelledby="reconciliation-title">
                <div class="rounded-xl border border-primary/30 bg-surface p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">Final review</p>
                            <h2 id="reconciliation-title" class="mt-1 text-2xl font-black text-foreground">Rekonsiliasi kas</h2>
                            <p class="mt-2 max-w-lg text-sm leading-6 text-muted">Bandingkan kas di laci dengan saldo yang dihitung sistem sebelum shift ditutup.</p>
                        </div>
                        <x-filament::icon icon="heroicon-o-clipboard-document-check" class="size-6 shrink-0 text-primary" aria-hidden="true" />
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-background p-4">
                            <p class="text-xs font-semibold text-muted">Kas diharapkan</p>
                            <p class="mt-2 text-xl font-black text-foreground">Rp {{ number_format($summary['expected'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-lg bg-primary-soft p-4">
                            <p class="text-xs font-semibold text-primary">Kas fisik</p>
                            <p class="mt-2 text-xl font-black text-foreground">{{ $actualCash === null ? 'Belum diisi' : 'Rp '.number_format($actualCash, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <form class="mt-6 space-y-4" wire:submit="closeShift">
                        <div>
                            <label class="block text-sm font-bold text-foreground" for="closing-cash-actual">Masukkan kas fisik</label>
                            <div x-data="{ display: '', format(value) { const digits = String(value ?? '').replace(/\D/g, ''); return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : ''; }, update(event) { const digits = String(event.target.value ?? '').replace(/\D/g, ''); const value = digits === '' ? 0 : Number(digits); this.display = this.format(digits); event.target.value = this.display; $wire.set('closingCashActual', value, false); } }" x-init="display = format($wire.closingCashActual)" x-effect="display = format($wire.closingCashActual)" class="mt-2 flex min-h-12 items-center rounded-lg border border-border bg-surface focus-within:border-primary focus-within:ring-4 focus-within:ring-primary/10">
                                <span class="shrink-0 ps-4 text-sm font-bold text-muted">Rp</span>
                                <input id="closing-cash-actual" class="min-w-0 flex-1 border-0 bg-transparent ps-2 pe-4 text-base font-bold text-foreground outline-none focus:ring-0" type="text" inputmode="numeric" autocomplete="off" placeholder="0" x-bind:value="display" x-on:input="update($event)" autofocus />
                            </div>
                            <p class="mt-2 text-xs text-muted">Masukkan jumlah uang tunai yang benar-benar ada di laci.</p>
                            @error('closingCashActual')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="rounded-lg border border-border bg-background p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold text-muted">Selisih otomatis</p>
                                    <p class="mt-1 text-lg font-black {{ $variance === null ? 'text-muted' : ($variance === 0 ? 'text-emerald-700' : ($variance < 0 ? 'text-red-700' : 'text-amber-700')) }}">
                                        {{ $variance === null ? 'Belum dihitung' : ($variance < 0 ? '- ' : ($variance > 0 ? '+ ' : '')).'Rp '.number_format(abs($variance), 0, ',', '.') }}
                                    </p>
                                </div>
                                @if ($variance === null)
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-muted">Menunggu kas fisik</span>
                                @elseif ($variance === 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700"><x-filament::icon icon="heroicon-o-check-circle" class="size-4" aria-hidden="true" /> Sesuai</span>
                                @elseif ($variance < 0)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700"><x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-4" aria-hidden="true" /> Kurang</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700"><x-filament::icon icon="heroicon-o-arrow-trending-up" class="size-4" aria-hidden="true" /> Lebih</span>
                                @endif
                            </div>
                            @if ($variance !== null && $variance !== 0)
                                <p class="mt-3 text-xs leading-5 text-muted">Catatan penutupan wajib diisi karena kas fisik berbeda dari kas yang diharapkan.</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-foreground" for="closing-note">Catatan penutupan <span class="font-normal text-muted">(opsional jika sesuai)</span></label>
                            <textarea id="closing-note" class="mt-2 min-h-24 w-full rounded-lg border border-border bg-surface px-4 py-3 text-sm text-foreground outline-none placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/10" maxlength="1000" wire:model="closingNote" placeholder="Contoh: kas diserahkan ke supervisor"></textarea>
                            @error('closingNote')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                            @error('closing_note')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="border-t border-border pt-4">
                            <div class="flex items-start gap-2 text-xs leading-5 text-muted"><x-filament::icon icon="heroicon-o-lock-closed" class="mt-0.5 size-4 shrink-0" aria-hidden="true" /> Setelah ditutup, shift ini tidak dapat menerima transaksi baru.</div>
                            <button class="nl-focus mt-4 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 font-bold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-60" type="submit" wire:loading.attr="disabled" wire:target="closeShift">
                                <x-filament::icon icon="heroicon-o-lock-closed" class="size-5" aria-hidden="true" />
                                <span wire:loading.remove wire:target="closeShift">Tutup shift sekarang</span>
                                <span wire:loading wire:target="closeShift">Menyimpan rekonsiliasi...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="rounded-xl border border-border bg-surface p-5 sm:p-6" aria-labelledby="movement-title">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-muted">Audit kas</p>
                            <h2 id="movement-title" class="mt-1 text-lg font-bold text-foreground">Pergerakan kas selama shift</h2>
                        </div>
                        <x-filament::icon icon="heroicon-o-arrows-right-left" class="size-5 text-muted" aria-hidden="true" />
                    </div>
                    <p class="mt-2 text-sm leading-6 text-muted">Setiap kas masuk dan keluar tercatat sebelum rekonsiliasi akhir.</p>

                    <div class="mt-5 space-y-2">
                        @forelse ($movements as $movement)
                            @php($isIn = in_array($movement->type->value, ['opening', 'cash_in', 'sale'], true))
                            @php($movementLabel = ['opening' => 'Kas awal', 'sale' => 'Penjualan tunai', 'refund' => 'Refund tunai', 'cash_in' => 'Kas masuk', 'cash_out' => 'Kas keluar'][$movement->type->value] ?? 'Pergerakan kas')
                            <div class="flex items-start gap-3 rounded-lg border border-border px-3 py-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-md {{ $isIn ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}"><x-filament::icon icon="{{ $movement->type->value === 'cash_out' ? 'heroicon-o-arrow-up-tray' : 'heroicon-o-arrow-down-tray' }}" class="size-4" aria-hidden="true" /></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3 text-sm"><p class="font-bold text-foreground">{{ $movementLabel }}</p><span class="shrink-0 font-bold {{ $isIn ? 'text-emerald-700' : 'text-red-700' }}">{{ $isIn ? '+' : '-' }} Rp {{ number_format($movement->amount, 0, ',', '.') }}</span></div>
                                    <p class="mt-1 truncate text-xs text-muted">{{ $movement->note ?: 'Tanpa catatan' }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-border bg-background p-5 text-center"><x-filament::icon icon="heroicon-o-banknotes" class="mx-auto size-6 text-muted" aria-hidden="true" /><p class="mt-2 text-sm font-semibold text-foreground">Belum ada pergerakan tambahan</p><p class="mt-1 text-xs text-muted">Kas awal dan penjualan akan muncul di sini.</p></div>
                        @endforelse
                    </div>

                    <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <button class="nl-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-sm font-bold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100" type="button" wire:click="openMovementModal('cash_in')"><x-filament::icon icon="heroicon-o-arrow-down-tray" class="size-4" aria-hidden="true" /> Kas masuk</button>
                        <button class="nl-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 text-sm font-bold text-red-700 transition hover:border-red-300 hover:bg-red-100" type="button" wire:click="openMovementModal('cash_out')"><x-filament::icon icon="heroicon-o-arrow-up-tray" class="size-4" aria-hidden="true" /> Kas keluar</button>
                    </div>
                </aside>
            </section>
        @else
            <div class="mt-7 rounded-xl border border-border bg-surface p-6 text-center"><x-filament::icon icon="heroicon-o-lock-closed" class="mx-auto size-8 text-muted" aria-hidden="true" /><p class="mt-3 font-bold text-foreground">Tidak ada shift aktif</p><p class="mt-1 text-sm text-muted">Buka shift baru untuk melanjutkan operasional kasir.</p><a class="nl-focus mt-5 inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover" href="{{ route('cashier.open-shift') }}"><x-filament::icon icon="heroicon-o-arrow-right" class="size-4" aria-hidden="true" /> Buka shift</a></div>
        @endif
    </main>

    <div x-cloak x-show="$wire.movementModalOpen" x-on:keydown.escape.window="$wire.closeMovementModal()" class="pos-modal-overlay fixed inset-0 z-50 grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="movement-modal-title" x-transition.opacity>
        <div class="pos-modal-surface w-full max-w-md overflow-hidden bg-surface" x-on:click.stop>
            <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
                <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-primary">AUDIT KAS</p><h2 id="movement-modal-title" class="mt-1 text-xl font-black text-foreground">Catat pergerakan kas</h2><p class="mt-1 text-sm text-muted">Simpan perubahan kas dengan catatan yang jelas.</p></div>
                <button class="nl-focus grid size-9 shrink-0 place-items-center rounded-lg text-muted hover:bg-background hover:text-foreground" type="button" aria-label="Tutup dialog" wire:click="closeMovementModal"><x-filament::icon icon="heroicon-o-x-mark" class="size-5" aria-hidden="true" /></button>
            </div>
            <form class="space-y-4 p-5" wire:submit="recordCashMovement">
                <div><label class="block text-sm font-bold text-foreground" for="movement-type">Jenis pergerakan</label><select id="movement-type" class="mt-2 min-h-11 w-full rounded-lg border border-border bg-surface px-4 text-sm text-foreground outline-none focus:border-primary focus:ring-4 focus:ring-primary/10" wire:model="movementType"><option value="cash_in">Kas masuk</option><option value="cash_out">Kas keluar</option></select></div>
                <div><label class="block text-sm font-bold text-foreground" for="movement-amount">Nominal</label><div x-data="{ display: '', format(value) { const digits = String(value ?? '').replace(/\D/g, ''); return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : ''; }, update(event) { const digits = String(event.target.value ?? '').replace(/\D/g, ''); const value = digits === '' ? 0 : Number(digits); this.display = this.format(digits); event.target.value = this.display; $wire.set('movementAmount', value, false); } }" x-init="display = format($wire.movementAmount)" x-effect="display = format($wire.movementAmount)" class="mt-2 flex min-h-11 items-center rounded-lg border border-border bg-surface focus-within:border-primary focus-within:ring-4 focus-within:ring-primary/10"><span class="shrink-0 ps-4 text-sm font-bold text-muted">Rp</span><input id="movement-amount" class="min-w-0 flex-1 border-0 bg-transparent ps-2 pe-4 text-sm font-bold text-foreground outline-none focus:ring-0" type="text" inputmode="numeric" placeholder="0" x-bind:value="display" x-on:input="update($event)" /></div>@error('movementAmount')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-bold text-foreground" for="movement-note">Catatan <span class="font-normal text-muted">(wajib)</span></label><textarea id="movement-note" class="mt-2 min-h-24 w-full rounded-lg border border-border bg-surface px-4 py-3 text-sm text-foreground outline-none placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/10" maxlength="255" wire:model="movementNote" placeholder="Contoh: pembelian perlengkapan"></textarea>@error('movementNote')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror</div>
                <div class="flex justify-end gap-2 border-t border-border pt-4"><button class="nl-focus inline-flex min-h-11 items-center gap-2 rounded-lg border border-border bg-surface px-4 text-sm font-semibold text-secondary hover:border-primary hover:text-primary" type="button" wire:click="closeMovementModal">Batal</button><button class="nl-focus inline-flex min-h-11 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-60" type="submit" wire:loading.attr="disabled" wire:target="recordCashMovement"><x-filament::icon icon="heroicon-o-check" class="size-4" aria-hidden="true" /><span wire:loading.remove wire:target="recordCashMovement">Simpan pergerakan</span><span wire:loading wire:target="recordCashMovement">Menyimpan...</span></button></div>
            </form>
        </div>
    </div>
</div>
