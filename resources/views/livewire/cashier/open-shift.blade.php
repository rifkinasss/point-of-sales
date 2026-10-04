<div
    class="nl-page min-h-screen px-4 py-3 sm:px-6 lg:px-8"
    x-data
    x-init="$nextTick(() => $refs.openingCash?.focus())"
    x-on:keydown.ctrl.enter.window.prevent="$refs.openShiftForm.requestSubmit()"
    x-on:keydown.meta.enter.window.prevent="$refs.openShiftForm.requestSubmit()"
>
    @php($user = auth()->user())

    <header class="mx-auto flex min-h-[3.5rem] max-w-5xl items-center justify-between gap-4 border-b border-border dark:border-white/10">
        <div class="flex min-w-0 items-center gap-2.5">
            <div class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary text-xs font-black text-white">N</div>
            <div class="min-w-0 leading-tight">
                <p class="font-bold tracking-tight text-foreground dark:text-white">NasLabs POS</p>
                <p class="truncate text-[11px] text-muted">{{ $user?->store?->name ?? 'Store aktif' }} <span aria-hidden="true">·</span> Cashier</p>
            </div>
        </div>

        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button class="nl-focus inline-flex min-h-9 items-center rounded-lg border border-border bg-surface px-2.5 text-xs font-semibold text-secondary transition hover:border-primary hover:text-primary dark:border-white/15 dark:bg-white/5 dark:text-slate-200" type="submit">Keluar</button>
        </form>
    </header>

    <main class="mx-auto max-w-5xl py-5 sm:py-6">
        @if ($shiftClosed)
            <section class="max-w-2xl rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-400/20 dark:bg-emerald-950/30" aria-labelledby="closed-shift-heading">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.08em] text-emerald-700 dark:text-emerald-300">Shift selesai</p>
                <h1 id="closed-shift-heading" class="mt-1 text-xl font-black tracking-tight text-foreground dark:text-white">Shift telah ditutup</h1>
                <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">Kas akhir telah tercatat. Keluar dari akun ini sebelum pergantian kasir dan pembukaan shift berikutnya.</p>
            </section>
        @else
            <section aria-labelledby="open-shift-heading">
                <p class="text-[11px] font-extrabold uppercase tracking-[0.08em] text-primary">Cashier workspace</p>
                <h1 id="open-shift-heading" class="mt-1 text-2xl font-black tracking-tight text-foreground sm:text-3xl dark:text-white">Mulai Shift Kasir</h1>
                <p class="mt-1 max-w-xl text-sm leading-5 text-muted">Siapkan kas awal sebelum menerima transaksi. Setelah shift dibuka, Anda akan langsung masuk ke POS.</p>
            </section>

            <section class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4" aria-label="Konteks shift">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-400/20 dark:bg-amber-950/30"><p class="text-[10px] font-semibold text-amber-700 dark:text-amber-300">SHIFT</p><p class="mt-1 truncate text-sm font-bold text-foreground dark:text-white">Belum Dibuka</p></div>
                <div class="rounded-lg border border-border bg-surface p-3 dark:border-white/10 dark:bg-white/5"><p class="text-[10px] font-semibold text-muted">KASIR</p><p class="mt-1 truncate text-sm font-bold text-foreground dark:text-white">{{ $user?->name ?? 'Cashier' }}</p></div>
                <div class="rounded-lg border border-border bg-surface p-3 dark:border-white/10 dark:bg-white/5"><p class="text-[10px] font-semibold text-muted">STORE</p><p class="mt-1 truncate text-sm font-bold text-foreground dark:text-white">{{ $user?->store?->name ?? 'Store aktif' }}</p></div>
                <div class="rounded-lg border border-border bg-surface p-3 dark:border-white/10 dark:bg-white/5"><p class="text-[10px] font-semibold text-muted">TANGGAL</p><p class="mt-1 truncate text-sm font-bold text-foreground dark:text-white">{{ now()->format('d M Y') }} <span class="font-normal text-muted">· {{ now()->format('H:i') }} WITA</span></p></div>
            </section>

            <section class="mt-4 max-w-3xl rounded-lg border border-border bg-surface p-4 dark:border-white/10 dark:bg-slate-900 sm:p-5" aria-labelledby="opening-form-heading">
                <div class="flex items-baseline justify-between gap-3"><div><p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-muted">Persiapan kas</p><h2 id="opening-form-heading" class="mt-0.5 text-lg font-black tracking-tight text-foreground dark:text-white">Catat kas awal</h2></div><span class="hidden text-xs text-muted sm:inline">Sebelum transaksi pertama</span></div>
                <form x-ref="openShiftForm" class="mt-4 grid gap-3 sm:grid-cols-2" wire:submit="openShift">
                    <div><label class="mb-1.5 block text-sm font-bold text-secondary dark:text-slate-200" for="opening-cash">Kas awal</label><div class="flex overflow-hidden rounded-lg border border-border bg-surface focus-within:border-primary focus-within:ring-4 focus-within:ring-primary/10 dark:border-white/15 dark:bg-white/5"><span class="grid min-w-12 place-items-center border-r border-border bg-background px-2 text-sm font-bold text-muted dark:border-white/10 dark:bg-white/5 dark:text-slate-300">Rp</span><input x-ref="openingCash" id="opening-cash" class="min-h-12 min-w-0 flex-1 border-0 bg-transparent px-3 text-base font-bold text-foreground outline-none placeholder:text-muted dark:text-white" inputmode="numeric" autocomplete="off" placeholder="0" wire:model="openingCash" autofocus type="text"></div><p class="mt-1 text-[11px] text-muted">Contoh: 100000 atau 100.000</p>@error('openingCash')<p class="mt-1 text-xs font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                    <div><label class="mb-1.5 block text-sm font-bold text-secondary dark:text-slate-200" for="opening-note">Catatan <span class="font-normal text-muted">(opsional)</span></label><textarea id="opening-note" class="min-h-12 w-full resize-none rounded-lg border border-border bg-surface px-3 py-2.5 text-sm text-foreground outline-none transition placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/10 dark:border-white/15 dark:bg-white/5 dark:text-white" rows="2" maxlength="1000" placeholder="Catatan pergantian kas" wire:model="openingNote"></textarea>@error('openingNote')<p class="mt-1 text-xs font-semibold text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="sm:col-span-2">@error('shift')<p class="mb-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700" role="alert">{{ $message }}</p>@enderror<button class="nl-focus flex min-h-12 w-full items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-extrabold text-white transition hover:bg-primary-hover disabled:cursor-wait disabled:opacity-60" type="submit" wire:loading.attr="disabled" wire:target="openShift"><span wire:loading.remove wire:target="openShift">Buka Shift &amp; Mulai Kasir</span><span wire:loading wire:target="openShift">Menyiapkan POS...</span></button><p class="mt-1.5 text-right text-[11px] text-muted">Tekan <kbd class="rounded border border-border px-1 py-0.5 font-bold dark:border-white/20">Ctrl</kbd> + <kbd class="rounded border border-border px-1 py-0.5 font-bold dark:border-white/20">Enter</kbd></p></div>
                </form>
            </section>
        @endif
    </main>
</div>
