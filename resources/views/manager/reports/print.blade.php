@php
    $moneyKeys = ['gross', 'discount', 'refund', 'net', 'amount', 'opening', 'expected', 'actual', 'variance', 'revenue'];
    $money = fn (mixed $value): string => 'Rp '.number_format((int) $value, 0, ',', '.');
    $value = function (string $key, mixed $item) use ($moneyKeys, $money): string {
        if ($item instanceof \DateTimeInterface) return $item->format('d M Y H:i');
        if (in_array($key, $moneyKeys, true) && is_numeric($item)) return $money($item);
        return is_scalar($item) || $item === null ? (string) ($item ?? '-') : '-';
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data['title'] }} · {{ $store?->name ?? 'Store' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print { .screen-only { display: none !important; } body { background: #fff !important; } .print-report { border: 0 !important; box-shadow: none !important; } }
    </style>
</head>
<body class="bg-background p-5 text-foreground antialiased">
    <main class="print-report mx-auto max-w-5xl rounded-lg border border-border bg-surface p-6 shadow-sm">
        <header class="border-b border-border pb-5"><p class="text-xs font-bold uppercase tracking-wider text-muted">{{ $store?->name ?? 'Store' }}</p><h1 class="mt-2 text-2xl font-black">{{ $data['title'] }}</h1><p class="mt-1 text-sm text-muted">Periode: {{ $data['period']['label'] }} · Dibuat {{ now()->format('d M Y H:i') }}</p></header>
        <section class="grid gap-3 py-5 sm:grid-cols-3">@foreach ($data['summary'] as $key => $item)<div class="border border-border p-3"><p class="text-xs text-muted">{{ ucfirst(str_replace('_', ' ', $key)) }}</p><p class="mt-1 font-bold">{{ in_array($key, $moneyKeys, true) ? $money($item) : number_format((int) $item, 0, ',', '.') }}</p></div>@endforeach</section>
        <div class="overflow-x-auto"><table class="w-full min-w-[680px] text-left text-xs"><thead class="border-b-2 border-foreground"><tr>@foreach ($data['columns'] as $key => $label) @if (filled($label))<th class="px-2 py-2">{{ $label }}</th>@endif @endforeach</tr></thead><tbody>@foreach ($data['rows'] as $row)<tr class="border-b border-border">@foreach ($data['columns'] as $key => $label) @if (filled($label))<td class="px-2 py-2">{{ $value($key, $row[$key] ?? null) }}</td>@endif @endforeach</tr>@endforeach</tbody></table></div>
        <footer class="mt-6 border-t border-border pt-4 text-xs text-muted">NasLabs ERP · Laporan operasional store</footer>
    </main>
    <div class="screen-only mx-auto mt-4 max-w-5xl text-right"><button class="nl-focus inline-flex min-h-10 items-center gap-2 rounded-lg bg-primary px-4 text-sm font-bold text-white hover:bg-primary-hover" onclick="window.print()" type="button">Cetak laporan</button></div>
</body>
</html>
