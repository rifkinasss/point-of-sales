@props(['product', 'price' => null])

@php($outOfStock = $product->product_type->tracksInventory() && ($product->inventory?->quantity ?? 0) < 1)

<button
    class="pos-product-card group flex min-h-52 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white text-left shadow-sm transition duration-150 hover:-translate-y-1 hover:border-indigo-400 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/5 dark:hover:border-indigo-400"
    type="button"
    @disabled($outOfStock)
    wire:click="addProduct({{ $product->id }})"
>
    <div class="relative flex min-h-28 items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-4 dark:from-indigo-500/15 dark:via-gray-900 dark:to-violet-500/10">
        @if ($product->image_path)
            <img alt="{{ $product->name }}" class="h-24 w-full object-contain drop-shadow-sm" src="{{ asset('storage/'.$product->image_path) }}">
        @else
            <span class="grid size-14 place-items-center rounded-2xl bg-white text-indigo-600 shadow-sm ring-1 ring-indigo-100 dark:bg-white/10 dark:text-indigo-300 dark:ring-white/10">
                <x-filament::icon icon="heroicon-o-cube" class="size-7" />
            </span>
        @endif
        @if ($product->product_type->tracksInventory())
            <span class="absolute right-3 top-3 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 shadow-sm dark:bg-emerald-500/15 dark:text-emerald-300">Stok {{ $product->inventory?->quantity ?? 0 }}</span>
        @endif
        @if ($outOfStock)
            <span class="absolute inset-x-3 bottom-3 rounded-lg bg-gray-900/80 px-2 py-1 text-center text-xs font-bold text-white">Stok habis</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3.5">
        <span class="block line-clamp-2 text-sm font-bold leading-5">{{ $product->name }}</span>
        @if ($product->relationLoaded('optionGroups') && $product->optionGroups->isNotEmpty())
            <span class="mt-1 block text-xs text-indigo-600 dark:text-indigo-300">Tersedia opsi & add-on</span>
        @endif

        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <span class="text-sm font-black text-indigo-600 dark:text-indigo-300">Rp {{ number_format($price ?? $product->selling_price, 0, ',', '.') }}</span>
            <span class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-bold text-indigo-700 transition group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-500/15 dark:text-indigo-300">+ Tambah</span>
        </div>
    </div>
</button>
