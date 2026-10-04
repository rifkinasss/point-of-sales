<x-filament-panels::page>
    <form wire:submit="produce" class="max-w-3xl space-y-6">
        <x-filament::section>
            <x-slot name="heading">Catat produksi</x-slot>
            <x-slot name="description">Bahan baku pada resep akan dikurangi, lalu stok produk jadi ditambahkan dalam satu proses.</x-slot>
            <div class="grid gap-4 md:grid-cols-2"><select wire:model="productId" class="fi-input"><option value="">Pilih produk resep</option>@foreach ($this->products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select><input wire:model="quantity" type="number" min="1" class="fi-input" placeholder="Jumlah produksi" /></div>
            @if ($productId && ($product = $this->products->firstWhere('id', $productId)))<div class="mt-5 rounded-xl border p-4"><p class="font-semibold">Kebutuhan bahan per batch</p><ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">@foreach ($product->recipeIngredients as $line)<li>{{ $line->ingredient?->name }}: {{ $line->quantity_required * $quantity }} {{ $line->ingredient?->unit?->name }}</li>@endforeach</ul></div>@endif
            <textarea wire:model="note" class="fi-input mt-4 w-full" rows="3" placeholder="Catatan produksi (opsional)"></textarea>
            <div class="mt-4"><x-filament::button type="submit" icon="heroicon-o-wrench-screwdriver">Simpan produksi</x-filament::button></div>
        </x-filament::section>
    </form>
</x-filament-panels::page>
