<x-filament-widgets::widget>
    <x-filament::section heading="Akses cepat">
        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" class="group flex items-center gap-3 py-3 transition hover:text-primary-700 dark:hover:text-primary-400">
                    <x-filament::icon :icon="$action['icon']" class="h-4 w-4 shrink-0 text-primary-600" aria-hidden="true" />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-gray-950 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-400">{{ $action['label'] }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $action['description'] }}</span>
                    </span>
                    <x-filament::icon icon="heroicon-o-chevron-right" class="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
