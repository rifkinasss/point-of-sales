<x-filament-widgets::widget>
    <x-filament::section heading="Platform Security" compact>
        <div class="grid divide-y divide-gray-200 border-y border-gray-200 sm:grid-cols-4 sm:divide-x sm:divide-y-0 dark:divide-white/10 dark:border-white/10">
            @foreach ($metrics as $metric)
                <div class="flex items-center gap-3 px-3 py-3 sm:px-4">
                    <x-filament::icon :icon="$metric['icon']" class="h-4 w-4 shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" />
                    <div class="min-w-0">
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $metric['label'] }}</p>
                        <p class="mt-0.5 text-base font-semibold text-gray-950 dark:text-white">{{ number_format($metric['value']) }}</p>
                        @if ($metric['value'] === 0)
                            <p class="truncate text-[11px] text-gray-500 dark:text-gray-400">{{ $metric['empty'] }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
