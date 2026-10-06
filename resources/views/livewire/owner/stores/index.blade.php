<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-primary">Master data</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-foreground">Toko & Cabang</h1>
            <p class="mt-1 text-sm text-muted">Kelola identitas dan status operasional store yang berada dalam akun Owner.</p>
        </div>
        <a href="{{ route('owner.stores.create') }}" class="nl-focus inline-flex min-h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover">
            <x-filament::icon icon="heroicon-o-plus" class="size-4" aria-hidden="true" /> Tambah store
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
    @endif

    <section class="rounded-lg border border-border bg-surface p-4">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem]">
            <label>
                <span class="sr-only">Cari store</span>
                <input wire:model.live.debounce.300ms="search" class="nl-input w-full" placeholder="Cari nama, kode toko, atau cabang">
            </label>
            <select wire:model.live="status" class="nl-input w-full" aria-label="Filter status">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Tidak aktif</option>
            </select>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-border bg-surface">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-left text-sm">
                <thead class="border-b border-border bg-background text-xs uppercase tracking-wide text-muted">
                    <tr><th class="px-5 py-3">Store</th><th class="px-5 py-3">Kode</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Manager</th><th class="px-5 py-3 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($stores as $store)
                        <tr class="hover:bg-background">
                            <td class="px-5 py-4"><a class="font-bold text-primary hover:underline" href="{{ route('owner.stores.show', ['store' => $store->public_id]) }}">{{ $store->name }}</a><p class="mt-1 text-xs text-muted">{{ $store->branch_name ?: 'Tanpa nama cabang' }}</p></td>
                            <td class="px-5 py-4"><span class="font-mono text-xs font-bold text-secondary">{{ $store->store_code }}</span><span class="ml-2 text-xs text-muted">{{ $store->branch_code ?: 'Store utama' }}</span></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs font-bold {{ $store->isActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $store->status?->label() ?? 'Tidak diketahui' }}</span></td>
                            <td class="px-5 py-4 text-muted">{{ $store->managers()->where('role', 'manager')->count() }} manager</td>
                            <td class="px-5 py-4"><div class="flex justify-end gap-3"><a href="{{ route('owner.stores.edit', ['store' => $store->public_id]) }}" class="font-bold text-primary hover:underline">Edit</a>@if($store->isActive())<button type="button" wire:click="askToDeactivate('{{ $store->public_id }}')" class="font-bold text-muted hover:text-red-700">Nonaktifkan</button>@else<button type="button" wire:click="reactivate('{{ $store->public_id }}')" class="font-bold text-primary hover:underline">Aktifkan</button>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-muted">Belum ada store yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($confirmingPublicId)
        <div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/30 p-4" role="dialog" aria-modal="true" aria-labelledby="deactivate-title">
            <div class="w-full max-w-md rounded-xl border border-border bg-surface p-6 shadow-xl">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700">Konfirmasi</p>
                <h2 id="deactivate-title" class="mt-2 text-lg font-black text-foreground">Nonaktifkan store ini?</h2>
                <p class="mt-2 text-sm leading-6 text-muted">Store tidak lagi tersedia untuk konteks operasional baru. Riwayat transaksi tetap dipertahankan.</p>
                <div class="mt-6 flex justify-end gap-3"><button type="button" wire:click="$set('confirmingPublicId', null)" class="nl-focus rounded-lg border border-border px-4 py-2.5 text-sm font-bold text-secondary hover:bg-background">Batal</button><button type="button" wire:click="deactivate" class="nl-focus rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700">Nonaktifkan</button></div>
            </div>
        </div>
    @endif
</div>
