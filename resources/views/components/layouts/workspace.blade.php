<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title ?? 'NasLabs ERP' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <div class="min-h-screen lg:flex">
            <aside class="border-b border-slate-200 bg-white px-5 py-4 lg:min-h-screen lg:w-60 lg:border-b-0 lg:border-r dark:border-white/10 dark:bg-slate-900">
                <a class="font-black tracking-tight text-slate-950 dark:text-white" href="{{ $nav[0]['href'] ?? '/' }}">NasLabs ERP</a>
                <p class="mt-1 text-xs text-slate-500">{{ $role }} workspace</p>
                <nav class="mt-6 flex gap-2 overflow-x-auto lg:block lg:space-y-1">
                    @foreach ($nav as $item)<a class="block whitespace-nowrap rounded-lg bg-indigo-50 px-3 py-2 text-sm font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300" href="{{ $item['href'] }}">{{ $item['label'] }}</a>@endforeach
                </nav>
            </aside>
            <div class="min-w-0 flex-1">
                <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 lg:px-8 dark:border-white/10 dark:bg-slate-900"><div><p class="text-xs text-slate-500">Store aktif</p><p class="font-bold dark:text-white">{{ auth()->user()?->store?->name ?? 'Store' }}</p></div><div class="flex items-center gap-4"><span class="hidden text-sm font-semibold text-slate-600 sm:inline dark:text-slate-300">{{ auth()->user()?->name }}</span><form method="POST" action="{{ route('auth.logout') }}">@csrf<button class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-bold dark:border-white/15 dark:text-white" type="submit">Keluar</button></form></div></header>
                <main class="p-5 lg:p-8">{{ $slot }}</main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
