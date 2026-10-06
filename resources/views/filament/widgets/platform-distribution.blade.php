<x-filament-widgets::widget>
    <x-filament::section heading="Distribusi platform" description="Ringkasan akun aktif dan status store dari data master platform.">
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Pengguna aktif berdasarkan role</h3>
                <dl class="mt-4 divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($roles as $role => $count)
                        <div class="flex items-center justify-between gap-4 py-2.5 text-sm">
                            <dt class="text-gray-600 dark:text-gray-400">{{ $role }}</dt>
                            <dd class="font-semibold text-gray-950 dark:text-white">{{ number_format($count) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Store berdasarkan status</h3>
                <dl class="mt-4 divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($stores as $status => $count)
                        <div class="flex items-center justify-between gap-4 py-2.5 text-sm">
                            <dt class="text-gray-600 dark:text-gray-400">{{ $status }}</dt>
                            <dd class="font-semibold text-gray-950 dark:text-white">{{ number_format($count) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
