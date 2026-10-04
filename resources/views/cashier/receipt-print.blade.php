<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Struk {{ $sale->invoice_number }}</title>
        <style>
            :root { color-scheme: light; }
            * { box-sizing: border-box; }
            body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-size: 12px; line-height: 1.35; }
            .receipt-preview { width: 80mm; max-width: calc(100vw - 32px); margin: 32px auto; }
            .receipt { width: 100%; padding: 18px 14px; background: #fff; }
            .center { text-align: center; }
            .receipt-logo { display: block; width: auto; max-width: 72px; height: auto; max-height: 52px; margin: 0 auto 6px; object-fit: contain; }
            .store-name, .receipt-title, .total { font-weight: 800; }
            .store-name { font-size: 15px; }
            .receipt-title { margin-top: 10px; font-size: 13px; letter-spacing: .08em; }
            .muted { color: #64748b; }
            .meta, .summary, .payment { margin-top: 12px; }
            .meta div, .row { display: flex; justify-content: space-between; gap: 12px; }
            .meta div { align-items: flex-start; }
            .row { padding: 2px 0; }
            .row > :last-child { flex-shrink: 0; text-align: right; }
            .rule { margin: 12px 0; border-top: 1px dashed #94a3b8; }
            .item { padding: 3px 0; }
            .item-name { font-weight: 700; }
            .modifier { padding-left: 10px; color: #64748b; font-size: 11px; }
            .total { margin-top: 6px; font-size: 14px; }
            .footer { margin-top: 18px; }
            .receipt-actions { display: flex; justify-content: center; gap: 8px; margin: 16px auto 32px; }
            .receipt-actions button { min-height: 40px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #0f172a; font: inherit; font-weight: 700; cursor: pointer; }
            .receipt-actions button:first-child { border-color: #3b52e2; background: #3b52e2; color: #fff; }
            @media print {
                @page { size: 80mm auto; margin: 0; }
                body { background: #fff; font-size: 11px; }
                .receipt-preview { width: 80mm; max-width: none; margin: 0; }
                .receipt { padding: 4mm 3mm; }
                .receipt-logo { max-width: 18mm; max-height: 14mm; }
                .receipt-actions { display: none; }
            }
        </style>
    </head>
    <body>
        <main class="receipt-preview">
            <article class="receipt" aria-label="Struk pembayaran {{ $sale->invoice_number }}">
                <header class="center">
                    @if ($logoUrl)
                        <img
                            class="receipt-logo"
                            src="{{ $logoUrl }}"
                            alt="Logo {{ $sale->store?->name ?? 'NasLabs POS' }}"
                            onerror="this.hidden = true"
                        >
                    @endif
                    <div class="store-name">{{ $sale->store?->name ?? 'NasLabs POS' }}</div>
                    @if ($sale->store?->address)<div class="muted">{{ $sale->store->address }}</div>@endif
                    @if ($sale->store?->phone)<div class="muted">{{ $sale->store->phone }}</div>@endif
                    <div class="receipt-title">STRUK PEMBAYARAN</div>
                </header>

                <section class="meta">
                    <div><span>No</span><strong>{{ $sale->invoice_number }}</strong></div>
                    <div><span>Tanggal</span><span><x-local-date-time :value="$sale->completed_at" format="d/m/Y H:i" /></span></div>
                    <div><span>Kasir</span><span>{{ $sale->cashier?->name ?? '—' }}</span></div>
                    <div><span>Pelanggan</span><span>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</span></div>
                </section>

                <div class="rule"></div>

                <section aria-label="Item transaksi">
                    @foreach ($sale->items as $item)
                        <div class="item">
                            <div class="row item-name"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span>Rp {{ number_format($item->line_total, 0, ',', '.') }}</span></div>
                            @foreach ($item->modifiers ?? [] as $modifier)
                                <div class="modifier">{{ $modifier['group'] ?? 'Opsi' }}: {{ $modifier['name'] ?? '—' }}</div>
                            @endforeach
                        </div>
                    @endforeach
                </section>

                <div class="rule"></div>

                <section class="summary">
                    <div class="row"><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
                    <div class="row"><span>Diskon item</span><span>− Rp {{ number_format($sale->item_discount_total, 0, ',', '.') }}</span></div>
                    <div class="row"><span>Diskon transaksi</span><span>− Rp {{ number_format($sale->transaction_discount_total, 0, ',', '.') }}</span></div>
                    <div class="row"><span>Diskon promosi</span><span>− Rp {{ number_format($sale->promotion_discount_total, 0, ',', '.') }}</span></div>
                    <div class="row total"><span>TOTAL</span><span>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</span></div>
                </section>

                <div class="rule"></div>

                <section class="payment">
                    <div class="row"><strong>Pembayaran</strong><span></span></div>
                    @foreach ($sale->payments->where('status', \App\Enums\PaymentStatus::Completed) as $payment)
                        <div class="row"><span>{{ $payment->method === \App\Enums\PaymentMethod::Cash ? 'Tunai' : ($payment->method === \App\Enums\PaymentMethod::Qris ? 'QRIS' : 'Transfer') }}</span><span>Rp {{ number_format($payment->amount_due, 0, ',', '.') }}</span></div>
                        @if ($payment->reference_number)<div class="modifier">Ref: {{ $payment->reference_number }}</div>@endif
                        @if ($payment->method === \App\Enums\PaymentMethod::Cash && $payment->amount_received > $payment->amount_due)
                            <div class="row"><span>Tunai diterima</span><span>Rp {{ number_format($payment->amount_received, 0, ',', '.') }}</span></div>
                            <div class="row"><span>Kembalian</span><span>Rp {{ number_format($payment->change_amount, 0, ',', '.') }}</span></div>
                        @endif
                    @endforeach
                </section>

                <footer class="center footer">{{ $sale->store?->receipt_footer ?? 'Terima kasih' }}</footer>
            </article>
            <div class="receipt-actions">
                <button type="button" onclick="window.print()">Cetak struk</button>
                <button type="button" onclick="window.close()">Tutup</button>
            </div>
        </main>
    </body>
</html>
