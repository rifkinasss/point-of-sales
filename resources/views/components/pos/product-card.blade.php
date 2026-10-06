@props(['product', 'price' => null])

@php($outOfStock = $product->product_type->tracksInventory() && ($product->inventory?->quantity ?? 0) < 1)

<button
    class="pos-product-card nl-focus group flex min-h-44 flex-col overflow-hidden rounded-lg border border-border bg-surface text-left transition duration-150 hover:border-primary hover:bg-primary-soft/30 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary"
    type="button" @disabled($outOfStock) wire:click="addProduct('{{ $product->public_id }}')">
    <div class="relative flex min-h-20 items-center justify-center bg-primary-soft p-3 dark:bg-primary/10">
        @if ($product->image_path)
            <img alt="{{ $product->name }}" class="h-24 w-full object-contain drop-shadow-sm"
                src="{{ asset('storage/' . $product->image_path) }}">
        @else
            <span
                class="grid size-12 place-items-center rounded-lg bg-surface text-primary ring-1 ring-primary-soft-strong dark:bg-white/10 dark:text-blue-300 dark:ring-white/10">
                <x-filament::icon icon="heroicon-o-cube" class="size-5" aria-hidden="true" />
            </span>
        @endif
        @if ($product->product_type->tracksInventory())
            <span
                class="absolute right-2.5 top-2.5 rounded-md bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Stok
                {{ $product->inventory?->quantity ?? 0 }}</span>
        @endif
        @if ($outOfStock)
            <span
                class="absolute inset-x-3 bottom-2.5 rounded-md bg-gray-900/80 px-2 py-1 text-center text-xs font-bold text-white">Stok
                habis</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3">
        <span class="block line-clamp-2 text-sm font-bold leading-5">{{ $product->name }}</span>
        @if ($product->relationLoaded('optionGroups') && $product->optionGroups->isNotEmpty())
            <span class="mt-1 inline-flex items-center gap-2 text-xs text-primary"><x-filament::icon
                    icon="heroicon-o-adjustments-horizontal" class="size-4 shrink-0" aria-hidden="true" /> Tersedia opsi
                & add-on</span>
        @endif

        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <span class="text-sm font-black text-primary">Rp
                {{ number_format($price ?? $product->selling_price, 0, ',', '.') }}</span>
            <span class="text-xs font-semibold text-muted transition group-hover:text-primary">Pilih produk</span>
        </div>
    </div>
</button>
