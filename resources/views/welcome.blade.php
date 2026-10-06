<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Kelola transaksi, POS, stok, kas, pelanggan, dan cabang dalam satu platform operasional bisnis.">
        <link rel="canonical" href="{{ url('/') }}">
        <title>NasLabs ERP · Platform Operasional Bisnis</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .landing-grid { background-image: linear-gradient(to right, rgb(226 232 240 / .45) 1px, transparent 1px), linear-gradient(to bottom, rgb(226 232 240 / .45) 1px, transparent 1px); background-size: 48px 48px; mask-image: linear-gradient(to bottom, black, transparent 72%); }
            .landing-preview { box-shadow: 0 28px 70px rgb(15 23 42 / .15), 0 4px 12px rgb(15 23 42 / .06); }
            .landing-flow-line { background: linear-gradient(to right, var(--nl-primary), var(--nl-primary-soft-strong)); }
            .landing-nav-inner { transform-origin: top center; transition: transform .28s cubic-bezier(.22, 1, .36, 1); }
            .landing-nav.is-scrolled .landing-nav-inner { transform: scale(.985); }
            [data-scroll-target] { cursor: pointer; }
            [id] { scroll-margin-top: 5.5rem; }
            html.landing-js [data-reveal] { opacity: 0; transform: translateY(20px) scale(.985); transition: opacity .62s cubic-bezier(.22, 1, .36, 1), transform .62s cubic-bezier(.22, 1, .36, 1); }
            html.landing-js [data-reveal].is-visible { opacity: 1; transform: translateY(0) scale(1); }
            html.landing-js [data-reveal][data-reveal-delay="1"] { transition-delay: .08s; }
            html.landing-js [data-reveal][data-reveal-delay="2"] { transition-delay: .16s; }
            html.landing-js [data-reveal][data-reveal-delay="3"] { transition-delay: .24s; }
            @media (prefers-reduced-motion: reduce) {
                html.landing-js [data-reveal], html.landing-js [data-reveal].is-visible { opacity: 1; transform: none; transition: none; }
            }
        </style>
    </head>
    <body class="overflow-x-hidden bg-background font-sans text-foreground antialiased">
        <header class="landing-nav sticky top-0 z-40 border-b border-border bg-surface/95 backdrop-blur-sm">
            <div class="landing-nav-inner mx-auto flex h-16 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-3 font-black tracking-tight" aria-label="NasLabs ERP beranda"><span class="grid size-8 place-items-center rounded-md bg-primary text-sm text-white">N</span><span>NASLABS <span class="font-medium text-muted">ERP</span></span></a>
                <nav class="hidden items-center gap-7 text-sm font-semibold text-muted md:flex" aria-label="Navigasi utama"><button class="nl-focus transition hover:text-foreground" type="button" data-scroll-target="produk">Produk</button><button class="nl-focus transition hover:text-foreground" type="button" data-scroll-target="cara-kerja">Cara kerja</button><button class="nl-focus transition hover:text-foreground" type="button" data-scroll-target="workspace">Workspace</button></nav>
                <div class="flex items-center gap-3"><a href="{{ route('auth.login') }}" class="nl-focus inline-flex min-h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-bold text-white transition hover:bg-primary-hover">Masuk <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" aria-hidden="true" /></a><details class="relative md:hidden"><summary class="nl-focus grid size-10 cursor-pointer list-none place-items-center rounded-md border border-border text-secondary" aria-label="Buka menu"><x-filament::icon icon="heroicon-o-bars-3" class="size-5" aria-hidden="true" /></summary><nav class="absolute right-0 top-12 w-48 rounded-lg border border-border bg-surface p-2 shadow-xl" aria-label="Navigasi mobile"><button class="nl-focus block w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-secondary hover:bg-background" type="button" data-scroll-target="produk">Produk</button><button class="nl-focus block w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-secondary hover:bg-background" type="button" data-scroll-target="cara-kerja">Cara kerja</button><button class="nl-focus block w-full rounded-md px-3 py-2 text-left text-sm font-semibold text-secondary hover:bg-background" type="button" data-scroll-target="workspace">Workspace</button></nav></details></div>
            </div>
        </header>

        <main>
            <section class="relative isolate overflow-hidden border-b border-border"><div class="landing-grid pointer-events-none absolute inset-0 -z-10"></div><div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 sm:py-20 lg:grid-cols-[.88fr_1.12fr] lg:gap-16 lg:px-10 lg:py-24">
                <div class="max-w-xl" data-reveal data-reveal-delay="1"><p class="mb-5 text-xs font-bold uppercase tracking-[.2em] text-primary">NasLabs ERP · Platform operasional</p><h1 class="max-w-xl text-4xl font-black leading-[1.05] tracking-[-.04em] text-foreground sm:text-5xl lg:text-[4rem]">Operasional bisnis, dalam satu ruang kerja.</h1><p class="mt-6 max-w-lg text-lg leading-8 text-muted">POS, stok, transaksi, kas, dan pengelolaan cabang terhubung dalam satu sistem yang dirancang untuk pekerjaan operasional sehari-hari.</p><div class="mt-8 flex flex-wrap items-center gap-3"><a href="{{ route('auth.login') }}" class="nl-focus inline-flex min-h-12 items-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-white transition hover:-translate-y-px hover:bg-primary-hover">Masuk ke NasLabs ERP <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" aria-hidden="true" /></a><button type="button" data-scroll-target="cara-kerja" class="nl-focus inline-flex min-h-12 items-center rounded-md border border-border bg-surface px-5 text-sm font-bold text-secondary transition hover:border-primary hover:text-primary">Lihat cara kerjanya</button></div><p class="mt-5 text-xs text-muted">Satu sistem. Ruang kerja sesuai peran.</p></div>
                <div class="relative" data-reveal data-reveal-delay="3"><div class="absolute -inset-4 -z-10 rounded-[1.5rem] bg-primary-soft/70 blur-2xl"></div><div class="landing-preview overflow-hidden rounded-xl border border-slate-300 bg-surface"><div class="flex h-11 items-center justify-between border-b border-border bg-white px-4"><div class="flex items-center gap-2"><span class="grid size-6 place-items-center rounded bg-primary text-[10px] font-black text-white">N</span><span class="text-xs font-bold text-secondary">NasLabs POS</span></div><div class="flex items-center gap-2 text-[10px] text-muted"><span class="hidden sm:inline">NasLabs Market</span><span class="size-2 rounded-full bg-emerald-500"></span>Shift aktif</div></div><div class="grid min-h-[19rem] grid-cols-[52px_1fr] sm:grid-cols-[144px_1fr]"><aside class="border-r border-border bg-slate-50 p-3"><div class="mb-6 h-2 w-16 rounded bg-primary/20"></div><div class="space-y-3"><span class="block h-2 w-20 rounded bg-primary"></span><span class="block h-2 w-14 rounded bg-slate-200"></span><span class="block h-2 w-16 rounded bg-slate-200"></span><span class="block h-2 w-12 rounded bg-slate-200"></span></div></aside><div class="grid grid-cols-[1fr_42%] gap-4 bg-background p-4 sm:p-5"><div class="min-w-0"><div class="flex items-center justify-between"><div><p class="text-[9px] font-bold uppercase tracking-widest text-primary">Workspace kasir</p><p class="mt-1 text-sm font-black text-foreground">Pilih produk</p></div><span class="hidden rounded border border-border bg-white px-2 py-1 text-[9px] text-muted sm:inline">F2 Cari</span></div><div class="mt-4 flex h-8 items-center rounded border border-border bg-white px-3 text-[10px] text-muted"><x-filament::icon icon="heroicon-o-magnifying-glass" class="mr-2 size-3.5" aria-hidden="true" />Cari produk, SKU, atau barcode</div><div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3"><div class="rounded border border-border bg-white p-2"><div class="h-12 rounded bg-primary-soft"></div><p class="mt-2 truncate text-[10px] font-bold">Nasi Goreng Spesial</p><p class="mt-1 text-[9px] text-muted">Rp 25.000</p></div><div class="rounded border border-border bg-white p-2"><div class="h-12 rounded bg-slate-100"></div><p class="mt-2 truncate text-[10px] font-bold">Es Teh Manis</p><p class="mt-1 text-[9px] text-muted">Rp 7.000</p></div><div class="hidden rounded border border-border bg-white p-2 sm:block"><div class="h-12 rounded bg-slate-100"></div><p class="mt-2 truncate text-[10px] font-bold">Kopi Susu Gula Aren</p><p class="mt-1 text-[9px] text-muted">Rp 20.000</p></div></div></div><div class="flex min-w-0 flex-col rounded-lg border border-border bg-white p-3"><div class="flex items-center justify-between"><p class="text-[10px] font-black">Pesanan</p><span class="text-[9px] text-muted">3 item</span></div><div class="mt-4 space-y-3 text-[9px]"><div class="flex justify-between gap-2"><span class="truncate">Nasi Goreng Spesial ×1</span><b>25.000</b></div><div class="flex justify-between gap-2"><span class="truncate">Es Teh Manis ×2</span><b>14.000</b></div></div><div class="mt-auto border-t border-border pt-3"><div class="flex justify-between text-[9px] text-muted"><span>Total</span><b class="text-sm text-foreground">Rp 39.000</b></div><div class="mt-3 h-8 rounded bg-primary text-center text-[10px] font-bold leading-8 text-white">Bayar</div></div></div></div></div></div><p class="mt-3 text-center text-xs text-muted">Ruang kerja POS untuk transaksi yang benar-benar berjalan.</p></div>
            </div></section>

            <section class="border-b border-border bg-surface" aria-label="Kapabilitas platform"><div class="mx-auto grid max-w-7xl grid-cols-2 px-5 sm:grid-cols-3 sm:px-8 lg:grid-cols-5 lg:px-10">@foreach ([['heroicon-o-shopping-cart', 'POS'], ['heroicon-o-receipt-percent', 'Transaksi'], ['heroicon-o-cube', 'Inventori'], ['heroicon-o-banknotes', 'Rekonsiliasi kas'], ['heroicon-o-building-storefront', 'Multi-store']] as [$icon, $label])<div class="flex items-center gap-2 border-r border-border px-3 py-5 text-xs font-bold text-secondary first:pl-0 last:border-0 sm:px-5 lg:justify-center"><x-filament::icon icon="{{ $icon }}" class="size-4 text-primary" aria-hidden="true" />{{ $label }}</div>@endforeach</div></section>

            <section id="cara-kerja" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-28"><div class="grid gap-12 lg:grid-cols-[.78fr_1.22fr] lg:gap-24"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-primary">Cara kerja</p><h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Bisnis tidak berjalan dalam satu layar.</h2><p class="mt-5 leading-7 text-muted">Satu transaksi memengaruhi lebih dari kasir. NasLabs ERP menjaga alur operasional tetap terhubung dari penjualan sampai laporan.</p></div><div class="relative grid gap-3 sm:grid-cols-5 sm:gap-0">@foreach ([['Penjualan', 'heroicon-o-shopping-bag'], ['Pembayaran', 'heroicon-o-credit-card'], ['Persediaan', 'heroicon-o-cube'], ['Kas', 'heroicon-o-banknotes'], ['Laporan', 'heroicon-o-chart-bar-square']] as $index => [$label, $icon])<div class="relative flex items-center gap-3 border-b border-border py-4 sm:block sm:border-b-0 sm:py-0 sm:pr-5"><div class="relative z-10 grid size-10 shrink-0 place-items-center rounded-md border border-primary-soft-strong bg-primary-soft text-primary"><x-filament::icon icon="{{ $icon }}" class="size-5" aria-hidden="true" /></div><p class="mt-0 text-sm font-bold sm:mt-3">{{ $label }}</p>@if ($index < 4)<span class="hidden sm:absolute sm:left-10 sm:right-3 sm:top-5 sm:block sm:h-px landing-flow-line"></span>@endif</div>@endforeach</div></div></section>

            <section id="workspace" class="border-y border-border bg-slate-100/70"><div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-28"><div class="max-w-2xl"><p class="text-xs font-bold uppercase tracking-[.2em] text-primary">Satu sistem, ruang kerja berbeda</p><h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Setiap peran melihat pekerjaan yang relevan.</h2><p class="mt-5 leading-7 text-muted">Data tetap terhubung, tetapi pekerjaan tidak perlu bercampur. NasLabs ERP memisahkan ruang kerja berdasarkan tanggung jawab.</p></div><div class="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-4">@foreach ([['Kasir', 'Fokus pada transaksi.', 'Buka dan tutup shift · POS · pembayaran · receipt', 'heroicon-o-calculator'], ['Manager', 'Fokus pada operasional toko.', 'Transaksi · kas · produk · pelanggan · laporan', 'heroicon-o-clipboard-document-list'], ['Owner', 'Fokus pada bisnis dan cabang.', 'Multi-store · staff · stok · kas · laporan', 'heroicon-o-building-office-2'], ['Admin', 'Fokus pada platform.', 'Users · stores · subscriptions · audit · sessions', 'heroicon-o-shield-check']] as [$role, $description, $capabilities, $icon])<article class="rounded-lg border border-border bg-surface p-5 transition hover:-translate-y-0.5 hover:border-primary/50"><div class="flex items-center justify-between"><span class="grid size-9 place-items-center rounded-md bg-primary-soft text-primary"><x-filament::icon icon="{{ $icon }}" class="size-5" aria-hidden="true" /></span><span class="text-[10px] font-bold uppercase tracking-widest text-muted">Workspace</span></div><h3 class="mt-8 text-lg font-black">{{ $role }}</h3><p class="mt-1 text-sm font-semibold text-secondary">{{ $description }}</p><p class="mt-4 text-xs leading-6 text-muted">{{ $capabilities }}</p></article>@endforeach</div></div></section>

            <section id="produk" class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-28"><div class="grid items-center gap-14 lg:grid-cols-[1fr_.9fr]"><div class="order-2 lg:order-1"><div class="rounded-xl border border-border bg-surface p-5 shadow-[0_20px_50px_rgb(15_23_42/.08)]"><div class="flex items-center justify-between border-b border-border pb-4"><div><p class="text-[10px] font-bold uppercase tracking-widest text-primary">Pencatatan kas</p><h3 class="mt-1 text-lg font-black">Tutup shift dengan angka yang jelas.</h3></div><x-filament::icon icon="heroicon-o-calculator" class="size-6 text-primary" aria-hidden="true" /></div><div class="mt-5 space-y-4"><div class="flex justify-between text-sm"><span class="text-muted">Kas awal</span><b>Rp 500.000</b></div><div class="flex justify-between text-sm"><span class="text-muted">Penjualan tunai</span><b>+ Rp 1.250.000</b></div><div class="flex justify-between text-sm"><span class="text-muted">Cash in / cash out</span><b>+ Rp 100.000</b></div><div class="border-t border-border pt-4"><div class="flex justify-between"><span class="font-bold">Expected cash</span><strong class="text-xl text-primary">Rp 1.850.000</strong></div></div><div class="grid grid-cols-2 gap-3"><div class="rounded-md bg-background p-3"><p class="text-[10px] font-bold uppercase text-muted">Kas fisik</p><p class="mt-1 font-black">Rp 1.850.000</p></div><div class="rounded-md bg-emerald-50 p-3"><p class="text-[10px] font-bold uppercase text-emerald-700">Selisih</p><p class="mt-1 font-black text-emerald-700">Rp 0</p></div></div></div></div></div><div class="order-1 lg:order-2"><p class="text-xs font-bold uppercase tracking-[.2em] text-primary">Cash & reconciliation</p><h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Kasir selesai. Rekonsiliasi belum.</h2><p class="mt-5 leading-7 text-muted">Opening cash, penjualan tunai, cash in, cash out, refund, dan kas fisik bertemu dalam satu close shift yang dapat diperiksa.</p><div class="mt-7 space-y-3 text-sm font-semibold text-secondary"><p class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-primary-soft text-xs text-primary">1</span>Expected cash dihitung dari aktivitas shift.</p><p class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-primary-soft text-xs text-primary">2</span>Kas fisik dicatat oleh kasir.</p><p class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-primary-soft text-xs text-primary">3</span>Variance dijelaskan ketika diperlukan.</p></div></div></div></section>

            <section class="border-y border-border bg-foreground text-white"><div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 sm:px-8 lg:grid-cols-[.8fr_1.2fr] lg:px-10 lg:py-24"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-blue-300">Multi-store & inventory</p><h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Satu bisnis. Banyak cabang.</h2><p class="mt-5 leading-7 text-white/65">Owner melihat bisnis lintas store. Manager bekerja pada store yang ditugaskan. Cashier beroperasi pada store aktifnya.</p><a href="{{ route('auth.login') }}" class="nl-focus mt-8 inline-flex items-center gap-2 rounded-md border border-white/20 px-4 py-3 text-sm font-bold text-white transition hover:border-blue-300 hover:bg-white/5">Masuk untuk melihat workspace <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" aria-hidden="true" /></a></div><div class="grid gap-4 sm:grid-cols-2"><div class="rounded-lg border border-white/10 bg-white/5 p-5"><p class="text-xs font-bold uppercase tracking-widest text-blue-300">Store context</p><div class="mt-5 space-y-3 text-sm"><div class="flex justify-between border-b border-white/10 pb-3"><span>NasLabs Market</span><span class="text-emerald-300">Aktif</span></div><div class="flex justify-between border-b border-white/10 pb-3"><span>NasLabs Market Sepinggan</span><span class="text-white/50">Cabang</span></div><div class="flex justify-between"><span>NasLabs Market Samarinda</span><span class="text-white/50">Cabang</span></div></div></div><div class="rounded-lg border border-white/10 bg-white/5 p-5"><p class="text-xs font-bold uppercase tracking-widest text-blue-300">Inventory state</p><div class="mt-5 space-y-4"><div><div class="flex justify-between text-xs"><span>Nasi Goreng Spesial</span><span class="text-white/55">45 pcs</span></div><div class="mt-2 h-1.5 rounded-full bg-white/10"><div class="h-full w-4/5 rounded-full bg-blue-400"></div></div></div><div><div class="flex justify-between text-xs"><span>Es Teh Manis</span><span class="text-white/55">80 pcs</span></div><div class="mt-2 h-1.5 rounded-full bg-white/10"><div class="h-full w-11/12 rounded-full bg-blue-400"></div></div></div><p class="pt-2 text-xs leading-5 text-white/55">Produk, SKU, harga, dan stok tetap berada dalam konteks store.</p></div></div></div></div></section>

            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 lg:px-10 lg:py-28"><div class="grid gap-12 lg:grid-cols-2"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-primary">Platform administration</p><h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Operasional terpisah dari administrasi platform.</h2><p class="mt-5 max-w-lg leading-7 text-muted">Admin menjaga fondasi platform: users, stores, subscriptions, audit, active sessions, dan akses. Pemilik bisnis tetap fokus pada operasi sehari-hari.</p></div><div class="grid grid-cols-2 gap-x-8 gap-y-6 border-t border-border pt-6 sm:grid-cols-3">@foreach ([['Users & access', 'Role dan akses terkontrol.'], ['Audit activity', 'Perubahan penting tercatat.'], ['Store oversight', 'Status platform terlihat jelas.'], ['Subscriptions', 'Lifecycle store terpantau.'], ['Active sessions', 'Sesi dapat ditinjau.'], ['Role workspaces', 'Admin tetap terpisah.']] as [$title, $description])<div><p class="text-sm font-bold">{{ $title }}</p><p class="mt-1 text-xs leading-5 text-muted">{{ $description }}</p></div>@endforeach</div></div></section>

            <section class="border-t border-border bg-primary-soft"><div class="mx-auto flex max-w-7xl flex-col gap-7 px-5 py-16 sm:px-8 lg:flex-row lg:items-center lg:justify-between lg:px-10 lg:py-20"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-primary">NasLabs ERP</p><h2 class="mt-3 text-3xl font-black tracking-tight">Siap masuk ke ruang kerja Anda?</h2><p class="mt-3 max-w-xl text-muted">NasLabs ERP memisahkan pekerjaan setiap peran tanpa memisahkan datanya.</p></div><a href="{{ route('auth.login') }}" class="nl-focus inline-flex min-h-12 w-fit items-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-white transition hover:bg-primary-hover">Masuk ke NasLabs ERP <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" aria-hidden="true" /></a></div></section>
        </main>
        <footer class="border-t border-slate-800 bg-foreground text-white">
            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8 lg:px-10 lg:py-14">
                <div class="grid gap-10 lg:grid-cols-[1.25fr_1fr_1fr_0.8fr]">
                    <div class="max-w-sm">
                        <a href="{{ route('landing') }}" class="nl-focus inline-flex items-center gap-3 font-black tracking-tight" aria-label="Kembali ke beranda NasLabs ERP">
                            <span class="grid size-8 place-items-center rounded-md bg-primary text-sm text-white">N</span>
                            <span>NASLABS <span class="font-medium text-slate-400">ERP</span></span>
                        </a>
                        <p class="mt-5 text-sm font-semibold leading-6 text-slate-200">Platform operasional bisnis.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-400">Transaksi, stok, kas, dan pengelolaan cabang terhubung dalam satu sistem.</p>
                    </div>

                    <nav aria-label="Produk">
                        <h2 class="text-xs font-bold uppercase tracking-[.18em] text-slate-300">Produk</h2>
                        <div class="mt-4 grid gap-2.5 text-sm text-slate-400">
                            <button type="button" data-scroll-target="produk" class="nl-focus w-fit text-left transition hover:text-white">POS</button>
                            <button type="button" data-scroll-target="cara-kerja" class="nl-focus w-fit text-left transition hover:text-white">Transaksi</button>
                            <button type="button" data-scroll-target="produk" class="nl-focus w-fit text-left transition hover:text-white">Produk &amp; Stok</button>
                            <button type="button" data-scroll-target="produk" class="nl-focus w-fit text-left transition hover:text-white">Kas &amp; Rekonsiliasi</button>
                            <button type="button" data-scroll-target="workspace" class="nl-focus w-fit text-left transition hover:text-white">Multi-Cabang</button>
                        </div>
                    </nav>

                    <nav aria-label="Workspace">
                        <h2 class="text-xs font-bold uppercase tracking-[.18em] text-slate-300">Workspace</h2>
                        <div class="mt-4 grid gap-2.5 text-sm text-slate-400">
                            <button type="button" data-scroll-target="workspace" class="nl-focus w-fit text-left transition hover:text-white">Kasir</button>
                            <button type="button" data-scroll-target="workspace" class="nl-focus w-fit text-left transition hover:text-white">Manager</button>
                            <button type="button" data-scroll-target="workspace" class="nl-focus w-fit text-left transition hover:text-white">Owner</button>
                            <button type="button" data-scroll-target="workspace" class="nl-focus w-fit text-left transition hover:text-white">Admin</button>
                        </div>
                    </nav>

                    <nav aria-label="Akses">
                        <h2 class="text-xs font-bold uppercase tracking-[.18em] text-slate-300">Akses</h2>
                        <div class="mt-4 grid gap-2.5 text-sm text-slate-400">
                            <a href="{{ route('auth.login') }}" class="nl-focus inline-flex w-fit items-center gap-2 transition hover:text-white">Masuk ke NasLabs ERP <x-filament::icon icon="heroicon-o-arrow-right" class="size-4" aria-hidden="true" /></a>
                            <a href="{{ route('landing') }}" class="nl-focus w-fit transition hover:text-white">Kembali ke beranda</a>
                        </div>
                    </nav>
                </div>

                <div class="mt-10 flex flex-col gap-3 border-t border-slate-800 pt-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ now()->year }} NasLabs</p>
                    <p>NasLabs ERP <span class="px-1.5 text-slate-700">·</span> v0.1.0</p>
                </div>
            </div>
        </footer>
        <script>
            (() => {
                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const root = document.documentElement;

                if (window.location.hash) {
                    window.history.replaceState(null, '', window.location.pathname + window.location.search);
                }

                document.querySelectorAll('main > section, footer').forEach((section) => {
                    section.dataset.reveal = '';
                });

                document.querySelectorAll('#cara-kerja .relative.flex').forEach((item, index) => {
                    item.dataset.reveal = '';
                    item.dataset.revealDelay = String(Math.min(index, 3));
                });

                const reveal = (element) => element.classList.add('is-visible');
                const revealTargets = document.querySelectorAll('[data-reveal]');

                if (!('IntersectionObserver' in window)) {
                    revealTargets.forEach(reveal);
                } else {
                    const observer = new IntersectionObserver((entries, instance) => {
                        entries.forEach((entry) => {
                            if (!entry.isIntersecting) return;
                            reveal(entry.target);
                            instance.unobserve(entry.target);
                        });
                    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

                    revealTargets.forEach((element) => observer.observe(element));
                }

                root.classList.add('landing-js');

                const navbar = document.querySelector('.landing-nav');
                if (navbar && !reduceMotion) {
                    const updateNavbarState = () => navbar.classList.toggle('is-scrolled', window.scrollY > 12);
                    updateNavbarState();
                    window.addEventListener('scroll', updateNavbarState, { passive: true });
                }

                const scrollToSection = (targetId, trigger = null) => {
                    const target = document.getElementById(targetId);
                    if (!target) return;

                    target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
                    target.querySelectorAll('[data-reveal]').forEach(reveal);

                    const menu = trigger?.closest('details');
                    if (menu) menu.removeAttribute('open');
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-scroll-target]');
                    if (!trigger) return;

                    event.preventDefault();
                    scrollToSection(trigger.dataset.scrollTarget, trigger);
                });
            })();
        </script>
    </body>
</html>
