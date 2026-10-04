<x-filament-panels::page>
    @php($report = $this->report)

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                Dari tanggal
                <input type="date" wire:model.live="dateFrom" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-gray-800" />
            </label>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                Sampai tanggal
                <input type="date" wire:model.live="dateUntil" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-white/10 dark:bg-gray-800" />
            </label>
        </div>
        <div class="mt-4"><x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" wire:click="downloadCsv">Unduh CSV</x-filament::button></div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Omzet', $report['revenue'], 'Rp '], ['Transaksi lunas', $report['transactions'], ''], ['Item terjual', $report['items'], ''], ['Rata-rata transaksi', $report['average'], 'Rp ']] as [$label, $value, $prefix])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $prefix }}{{ $prefix ? number_format($value, 0, ',', '.') : number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-100 p-5 dark:border-white/10"><h3 class="font-semibold">Metode pembayaran</h3></div>
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($report['payments'] as $payment)
                    <div class="flex items-center justify-between p-4"><span class="capitalize">{{ str($payment->method->value)->replace('_', ' ') }}</span><span class="font-semibold">Rp {{ number_format($payment->total, 0, ',', '.') }}</span></div>
                @empty
                    <p class="p-5 text-sm text-gray-500">Belum ada transaksi pada periode ini.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="border-b border-gray-100 p-5 dark:border-white/10"><h3 class="font-semibold">10 produk terlaris</h3></div>
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($report['products'] as $product)
                    <div class="flex items-center justify-between gap-4 p-4"><div><p class="font-medium">{{ $product->product_name }}</p><p class="text-sm text-gray-500">{{ number_format($product->quantity_sold) }} item</p></div><span class="font-semibold">Rp {{ number_format($product->revenue, 0, ',', '.') }}</span></div>
                @empty
                    <p class="p-5 text-sm text-gray-500">Belum ada produk terjual pada periode ini.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-100 p-5 dark:border-white/10"><h3 class="font-semibold">Transaksi terbaru</h3></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 text-gray-500 dark:bg-white/5"><tr><th class="p-4">Invoice</th><th class="p-4">Kasir</th><th class="p-4">Pembayaran</th><th class="p-4 text-right">Total</th><th class="p-4">Waktu</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse ($report['sales'] as $sale)
                    <tr><td class="p-4 font-medium">{{ $sale->invoice_number }}</td><td class="p-4">{{ $sale->cashier?->name ?? '-' }}</td><td class="p-4 capitalize">{{ str($sale->payment?->method?->value ?? '-')->replace('_', ' ') }}</td><td class="p-4 text-right font-semibold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td><td class="p-4">{{ $sale->completed_at?->format('d M Y H:i') }}</td></tr>
                @empty
                    <tr><td colspan="5" class="p-5 text-center text-gray-500">Belum ada transaksi pada periode ini.</td></tr>
                @endforelse
            </tbody></table>
        </div>
    </section>
</x-filament-panels::page>
