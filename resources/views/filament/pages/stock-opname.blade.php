<x-filament-panels::page>
    <form wire:submit="complete" class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Stok opname</x-slot>
            <x-slot name="description">Masukkan hasil hitung fisik. Selisih akan otomatis tercatat sebagai mutasi stok.</x-slot>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b"><th class="p-3 text-left">Produk</th><th class="p-3 text-right">Stok sistem</th><th class="p-3 text-right">Stok fisik</th><th class="p-3 text-right">Selisih</th></tr></thead><tbody>
                @foreach ($counts as $index => $count)<tr class="border-b"><td class="p-3 font-medium">{{ $count['name'] }}</td><td class="p-3 text-right">{{ $count['system'] }}</td><td class="p-3 text-right"><input type="number" min="0" wire:model="counts.{{ $index }}.actual_quantity" class="fi-input w-28 text-right" /></td><td class="p-3 text-right">{{ (int) ($count['actual_quantity'] ?? 0) - $count['system'] }}</td></tr>@endforeach
            </tbody></table></div>
            <textarea wire:model="note" class="fi-input mt-5 w-full" rows="3" placeholder="Catatan opname (opsional)"></textarea>
            <div class="mt-4"><x-filament::button type="submit" icon="heroicon-o-check">Selesaikan opname</x-filament::button></div>
        </x-filament::section>
    </form>
</x-filament-panels::page>
