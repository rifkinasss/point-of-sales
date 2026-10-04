<h2>{{ $sale->store->name ?? 'NasLabs ERP' }}</h2>
<p>Terima kasih atas pembelian Anda.</p>
<p><strong>Invoice:</strong> {{ $sale->invoice_number }}<br><strong>Tanggal:</strong> {{ $sale->completed_at?->format('d M Y H:i') }}</p>
<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse: collapse">
    @foreach ($sale->items as $item)
        <tr style="border-bottom: 1px solid #e5e7eb"><td>{{ $item->product_name }}<br><small>{{ $item->quantity }} × Rp {{ number_format($item->unit_price, 0, ',', '.') }}</small></td><td align="right">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td></tr>
    @endforeach
    <tr><td><strong>Total</strong></td><td align="right"><strong>Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</strong></td></tr>
</table>
