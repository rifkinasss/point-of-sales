@php
    $money = fn (int|float $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $timezone = app(\App\Services\BrowserTimezone::class);
    $metrics = $dashboard['metrics'];
    $performance = $dashboard['salesPerformance'];
    $paymentLabels = ['cash' => 'Tunai', 'qris' => 'QRIS', 'bank_transfer' => 'Transfer', 'e_wallet' => 'E-Wallet'];
@endphp

<div class="space-y-6">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary">Business overview</p>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-foreground lg:text-3xl">Dashboard Owner</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">Pantau kinerja bisnis dan operasional seluruh cabang dari satu konteks.</p>
        </div>
        <div class="rounded-lg border border-border bg-surface px-3 py-2 text-right">
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Konteks</p>
            <p class="mt-1 text-sm font-semibold text-foreground">{{ $dashboard['context']['all'] ? 'Semua Cabang' : $dashboard['context']['store']?->name }}</p>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6" aria-label="KPI utama">
        @foreach ([
            ['Omzet hari ini', $money($metrics['netSales']), 'Net setelah refund', 'heroicon-o-banknotes'],
            ['Transaksi', number_format($metrics['transactions'], 0, ',', '.'), 'Transaksi lunas', 'heroicon-o-receipt-percent'],
            ['Rata-rata transaksi', $money($metrics['averageTransaction']), 'Net sales / transaksi', 'heroicon-o-calculator'],
            ['Refund', $money($metrics['refunds']), 'Refund hari ini', 'heroicon-o-arrow-uturn-left'],
            ['Kas tunai', $money($metrics['cashSales']), 'Pembayaran tunai', 'heroicon-o-wallet'],
            ['Cabang aktif', number_format($metrics['activeStores'], 0, ',', '.'), 'Dalam konteks bisnis', 'heroicon-o-building-storefront'],
        ] as [$label, $value, $hint, $icon])
            <div class="rounded-lg border border-border bg-surface p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-muted">{{ $label }}</p>
                    <x-filament::icon icon="{{ $icon }}" class="size-4 text-primary" aria-hidden="true" />
                </div>
                <p class="mt-3 text-lg font-black tracking-tight text-foreground">{{ $value }}</p>
                <p class="mt-1 text-[11px] text-muted">{{ $hint }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
        <section class="rounded-lg border border-border bg-surface p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-primary">Kinerja penjualan</p>
                    <h2 class="mt-1 text-lg font-bold text-foreground">Hari ini</h2>
                </div>
                <x-filament::icon icon="heroicon-o-chart-bar-square" class="size-5 text-primary" aria-hidden="true" />
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-4">
                @foreach ([['Gross Sales', $performance['gross']], ['Diskon', $performance['discount']], ['Refund', $performance['refunds']], ['Net Sales', $performance['net']]] as [$label, $value])
                    <div class="border-l-2 border-primary-soft pl-3">
                        <p class="text-xs text-muted">{{ $label }}</p>
                        <p class="mt-1 font-bold text-foreground">{{ $money($value) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-border bg-surface p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-primary">Metode pembayaran</p>
                    <h2 class="mt-1 text-lg font-bold text-foreground">Hari ini</h2>
                </div>
                <x-filament::icon icon="heroicon-o-credit-card" class="size-5 text-primary" aria-hidden="true" />
            </div>
            <div class="mt-4 divide-y divide-border">
                @forelse ($paymentLabels as $method => $label)
                    <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <span class="text-secondary">{{ $label }}</span>
                        <span class="font-bold text-foreground">{{ $money($dashboard['paymentBreakdown'][$method] ?? 0) }}</span>
                    </div>
                @empty
                    <p class="py-3 text-sm text-muted">Belum ada pembayaran.</p>
                @endforelse
            </div>
        </section>
    </div>

    @if ($dashboard['context']['all'])
        <section class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="border-b border-border px-5 py-4">
                <p class="text-xs font-bold uppercase tracking-wider text-primary">Performa cabang</p>
                <h2 class="mt-1 text-lg font-bold text-foreground">Perbandingan hari ini</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-border bg-background text-xs uppercase tracking-wider text-muted">
                        <tr><th class="px-5 py-3">Cabang</th><th class="px-5 py-3 text-right">Transaksi</th><th class="px-5 py-3 text-right">Net Sales</th><th class="px-5 py-3 text-right">Refund</th><th class="px-5 py-3 text-right">Kas Tunai</th></tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($dashboard['storePerformance'] as $row)
                            <tr>
                                <td class="px-5 py-3"><p class="font-semibold text-foreground">{{ $row['store']->name }}</p><p class="mt-1 text-xs text-muted">{{ $row['store']->branch_code ?: 'Store utama' }}</p></td>
                                <td class="px-5 py-3 text-right text-secondary">{{ number_format($row['transactions'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right font-bold text-foreground">{{ $money($row['netSales']) }}</td>
                                <td class="px-5 py-3 text-right text-secondary">{{ $money($row['refunds']) }}</td>
                                <td class="px-5 py-3 text-right text-secondary">{{ $money($row['cashSales']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-muted">Belum ada cabang dalam konteks ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @else
        @php($storeSummary = $dashboard['storePerformance']->first())
        <section class="rounded-lg border border-border bg-surface p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-primary">Ringkasan cabang</p>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                <div><h2 class="text-lg font-bold text-foreground">{{ $storeSummary['store']->name ?? 'Store' }}</h2><p class="mt-1 text-sm text-muted">{{ $storeSummary['store']->branch_code ?? 'Store utama' }}</p></div>
                <div class="grid grid-cols-3 gap-5 text-right text-sm"><div><p class="text-xs text-muted">Transaksi</p><p class="mt-1 font-bold">{{ number_format($storeSummary['transactions'] ?? 0, 0, ',', '.') }}</p></div><div><p class="text-xs text-muted">Net Sales</p><p class="mt-1 font-bold">{{ $money($storeSummary['netSales'] ?? 0) }}</p></div><div><p class="text-xs text-muted">Kas tunai</p><p class="mt-1 font-bold">{{ $money($storeSummary['cashSales'] ?? 0) }}</p></div></div>
            </div>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[0.7fr_1.3fr]">
        <section class="rounded-lg border border-border bg-surface p-5">
            <div class="flex items-center gap-3"><span class="grid size-8 place-items-center rounded-md bg-primary-soft text-primary"><x-filament::icon icon="heroicon-o-exclamation-triangle" class="size-4" aria-hidden="true" /></span><div><p class="text-xs font-bold uppercase tracking-wider text-primary">Perlu perhatian</p><h2 class="mt-1 text-lg font-bold text-foreground">Sinyal operasional</h2></div></div>
            @if (count($dashboard['alerts']) > 0)
                <ul class="mt-4 space-y-3 text-sm">@foreach ($dashboard['alerts'] as $alert)<li class="flex gap-2 text-secondary"><span class="mt-1 size-1.5 shrink-0 rounded-full bg-amber-500"></span>{{ $alert['label'] }}</li>@endforeach</ul>
            @else
                <p class="mt-4 text-sm text-emerald-700">Semua operasional terlihat normal.</p>
            @endif
        </section>

        <section class="overflow-hidden rounded-lg border border-border bg-surface">
            <div class="border-b border-border px-5 py-4"><p class="text-xs font-bold uppercase tracking-wider text-primary">Aktivitas</p><h2 class="mt-1 text-lg font-bold text-foreground">Transaksi terbaru</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-border bg-background text-xs uppercase tracking-wider text-muted"><tr><th class="px-5 py-3">Invoice</th><th class="px-5 py-3">Cabang</th><th class="px-5 py-3">Kasir</th><th class="px-5 py-3">Waktu</th><th class="px-5 py-3 text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($dashboard['recentSales'] as $sale)
                            @php($payment = $sale->payments->where('status', \App\Enums\PaymentStatus::Completed)->map(fn ($item) => $paymentLabels[$item->method->value] ?? $item->method->value)->unique()->implode(' + '))
                            <tr><td class="px-5 py-3"><p class="font-semibold text-primary">{{ $sale->invoice_number }}</p><p class="mt-1 text-xs text-muted">{{ $payment ?: '—' }}</p></td><td class="px-5 py-3 text-secondary">{{ $sale->store?->name ?? '—' }}</td><td class="px-5 py-3 text-secondary">{{ $sale->cashier?->name ?? '—' }}</td><td class="px-5 py-3 text-xs text-muted">{{ $timezone->format($sale->completed_at ?? $sale->created_at, 'd M, H:i') }}</td><td class="px-5 py-3 text-right font-bold">{{ $money($sale->grand_total) }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-muted">Belum ada transaksi lunas hari ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
