<div
    class="nl-page min-h-screen px-4 py-5 sm:px-6 lg:px-8"
    x-data
    x-init="$nextTick(() => $refs.openingCash?.focus())"
    x-on:keydown.ctrl.enter.window.prevent="$refs.openShiftForm.requestSubmit()"
    x-on:keydown.meta.enter.window.prevent="$refs.openShiftForm.requestSubmit()"
>
    @php($user = auth()->user())

    <header class="mx-auto flex max-w-5xl items-center justify-between gap-4 border-b border-slate-200/80 pb-4 dark:border-white/10">
        <div class="flex min-w-0 items-center gap-3">
            <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-indigo-600 text-sm font-black text-white shadow-sm">N</div>
            <div class="min-w-0">
                <p class="font-bold tracking-tight text-slate-950 dark:text-white">NasLabs POS</p>
                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user?->store?->name ?? 'Store aktif' }} <span aria-hidden="true">·</span> {{ $user?->name ?? 'Cashier' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus-visible:outline-3 focus-visible:outline-indigo-300 focus-visible:outline-offset-2 dark:border-white/15 dark:bg-white/5 dark:text-slate-200 dark:hover:bg-white/10" type="submit">
                <span class="hidden sm:inline">Keluar</span>
                <span aria-hidden="true">↗</span>
            </button>
        </form>
    </header>

    <main class="mx-auto max-w-5xl py-8 sm:py-10">
        @if ($shiftClosed)
            <section class="max-w-2xl rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-400/20 dark:bg-emerald-950/30" aria-labelledby="closed-shift-heading">
                <p class="text-xs font-extrabold uppercase tracking-[0.08em] text-emerald-700 dark:text-emerald-300">Shift selesai</p>
                <h1 id="closed-shift-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Shift telah ditutup</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Kas akhir telah tercatat. Keluar dari akun ini sebelum pergantian kasir dan pembukaan shift berikutnya.</p>
            </section>
        @else
            <section aria-labelledby="open-shift-heading">
                <p class="text-xs font-extrabold uppercase tracking-[0.08em] text-indigo-600 dark:text-indigo-300">Cashier workspace</p>
                <h1 id="open-shift-heading" class="mt-2 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl dark:text-white">Mulai Shift Kasir</h1>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600 dark:text-slate-300">Siapkan kas awal sebelum menerima transaksi. Setelah shift dibuka, Anda akan langsung masuk ke POS.</p>
            </section>

            <section class="mt-7 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Konteks shift">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/20 dark:bg-amber-950/30">
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">SHIFT</p>
                    <p class="mt-2 font-bold text-slate-950 dark:text-white">Belum Dibuka</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">KASIR</p>
                    <p class="mt-2 truncate font-bold text-slate-950 dark:text-white">{{ $user?->name ?? 'Cashier' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">STORE</p>
                    <p class="mt-2 truncate font-bold text-slate-950 dark:text-white">{{ $user?->store?->name ?? 'Store aktif' }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">TANGGAL</p>
                    <p class="mt-2 font-bold text-slate-950 dark:text-white">{{ now()->format('d M Y') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ now()->format('H:i') }} WITA</p>
                </div>
            </section>

            <section class="mt-7 max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7 dark:border-white/10 dark:bg-slate-900" aria-labelledby="opening-form-heading">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">Persiapan kas</p>
                    <h2 id="opening-form-heading" class="mt-1 text-xl font-black tracking-tight text-slate-950 dark:text-white">Catat kas awal</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Masukkan jumlah uang fisik di laci kasir.</p>
                </div>

                <form x-ref="openShiftForm" class="mt-6 space-y-5" wire:submit="openShift">
                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-800 dark:text-slate-200" for="opening-cash">Kas awal</label>
                        <div class="flex overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 dark:border-white/15 dark:bg-white/5">
                            <span class="grid min-w-14 place-items-center border-r border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">Rp</span>
                            <input x-ref="openingCash" id="opening-cash" class="min-h-14 min-w-0 flex-1 border-0 bg-transparent px-4 text-lg font-bold text-slate-950 outline-none placeholder:text-slate-400 dark:text-white" inputmode="numeric" autocomplete="off" placeholder="0" wire:model="openingCash" autofocus type="text">
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Contoh: 100000 atau 100.000</p>
                        @error('openingCash')<p class="mt-2 text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-800 dark:text-slate-200" for="opening-note">Catatan pembukaan <span class="font-normal text-slate-500">(opsional)</span></label>
                        <textarea id="opening-note" class="min-h-28 w-full resize-y rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-white/15 dark:bg-white/5 dark:text-white" maxlength="1000" placeholder="Contoh: kas awal dari pergantian shift pagi" wire:model="openingNote"></textarea>
                        @error('openingNote')<p class="mt-2 text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror
                    </div>

                    @error('shift')<p class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" role="alert">{{ $message }}</p>@enderror

                    <button class="flex min-h-14 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-extrabold text-white shadow-sm transition hover:bg-indigo-700 focus-visible:outline-3 focus-visible:outline-indigo-300 focus-visible:outline-offset-2 disabled:cursor-wait disabled:opacity-60" type="submit" wire:loading.attr="disabled" wire:target="openShift">
                        <span wire:loading.remove wire:target="openShift">Buka Shift &amp; Mulai Kasir</span>
                        <span wire:loading wire:target="openShift">Menyiapkan POS...</span>
                    </button>
                    <p class="text-center text-xs text-slate-500 dark:text-slate-400">Tekan <kbd class="rounded border border-slate-300 px-1.5 py-0.5 font-bold dark:border-white/20">Ctrl</kbd> + <kbd class="rounded border border-slate-300 px-1.5 py-0.5 font-bold dark:border-white/20">Enter</kbd> untuk submit</p>
                </form>
            </section>
        @endif
    </main>
</div>
