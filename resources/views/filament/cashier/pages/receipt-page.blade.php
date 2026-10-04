<x-filament-panels::page>
    @if ($sale)
        <div class="mx-auto max-w-md">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-lg font-bold">{{ $sale->store->name ?? 'NasLabs ERP' }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ $sale->completed_at?->format('d M Y H:i') }}</div>
                    <div class="mt-2 font-mono text-sm">{{ $sale->invoice_number }}</div>
                </div>

                <div class="my-5 space-y-3 border-y border-dashed border-gray-300 py-4 text-sm dark:border-white/20">
                    @foreach ($sale->items as $item)
                        <div>
                            <div class="flex justify-between gap-4">
                                <span>{{ $item->product_name }}</span>
                                <span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
                            </div>
                            <div class="text-gray-500">{{ $item->quantity }} × Rp {{ number_format($item->unit_price, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
                    @if ($sale->item_discount_total + $sale->transaction_discount_total > 0)
                        <div class="flex justify-between"><span>Diskon</span><span>-Rp {{ number_format($sale->item_discount_total + $sale->transaction_discount_total, 0, ',', '.') }}</span></div>
                    @endif
                    <div class="flex justify-between border-t border-gray-200 pt-3 text-base font-bold dark:border-white/10"><span>Total</span><span>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span>Pembayaran</span><span>{{ $sale->payments->pluck('method')->map(fn ($method) => ucfirst(str_replace('_', ' ', $method->value)))->unique()->implode(', ') }}</span></div>
                    @if ($sale->payments->sum('change_amount') > 0)
                        <div class="flex justify-between"><span>Kembalian</span><span>Rp {{ number_format($sale->payments->sum('change_amount'), 0, ',', '.') }}</span></div>
                    @endif
                </div>

                <div class="mt-6 text-center text-xs text-gray-500">Kasir: {{ $sale->cashier->name }}</div>
            </x-filament::section>

            @php
                $phone = preg_replace('/\D+/', '', (string) $sale->customer?->phone);
                $phone = str_starts_with($phone, '0') ? '62'.substr($phone, 1) : $phone;
                $message = rawurlencode("Terima kasih telah berbelanja di ".($sale->store->name ?? 'kami').". Invoice {$sale->invoice_number}, total Rp ".number_format($sale->grand_total, 0, ',', '.'));
            @endphp
            <div class="mt-4 grid grid-cols-2 gap-3 print:hidden">
                <x-filament::button color="gray" type="button" x-on:click="window.print()">Cetak struk</x-filament::button>
                <x-filament::button tag="a" href="/cashier/pos-checkout">Transaksi baru</x-filament::button>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 print:hidden">
                <x-filament::button :disabled="blank($phone)" color="success" tag="a" target="_blank" href="{{ filled($phone) ? 'https://wa.me/'.$phone.'?text='.$message : '#' }}">Kirim WhatsApp</x-filament::button>
                <form wire:submit="emailReceipt" class="flex gap-2"><x-filament::input.wrapper class="flex-1"><x-filament::input wire:model="receiptEmail" placeholder="Email pelanggan" type="email" /></x-filament::input.wrapper><x-filament::button type="submit" color="info">Email</x-filament::button></form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
