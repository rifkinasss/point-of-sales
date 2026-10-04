@php
    use Marcelodelgado\Announcements\Enums\AnnouncementType;

    $pollingInterval = $this->getPollingInterval();
    $announcements = $this->getAnnouncements();
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-announcements',
                'hidden' => $announcements->isEmpty(),
            ])
    "
>
    <x-filament::section>
        <ul
            class="fi-wi-announcements-list"
            style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.75rem;"
        >
            @foreach ($announcements as $announcement)
                    @php
                        $borderClass = match ($announcement->type) {
                            AnnouncementType::Danger => 'border-danger-600 dark:border-danger-500',
                            AnnouncementType::Warning => 'border-warning-600 dark:border-warning-500',
                            AnnouncementType::Info => 'border-info-600 dark:border-info-500',
                            AnnouncementType::Success => 'border-success-600 dark:border-success-500',
                        };

                        $icon = match ($announcement->type) {
                            AnnouncementType::Danger => 'heroicon-o-exclamation-circle',
                            AnnouncementType::Warning => 'heroicon-o-exclamation-triangle',
                            AnnouncementType::Info => 'heroicon-o-information-circle',
                            AnnouncementType::Success => 'heroicon-o-check-circle',
                        };

                        $iconColorCss = match ($announcement->type) {
                            AnnouncementType::Danger => 'light-dark(#dc2626, #f87171)',
                            AnnouncementType::Warning => 'light-dark(#d97706, #fbbf24)',
                            AnnouncementType::Info => 'light-dark(#0369a1, #38bdf8)',
                            AnnouncementType::Success => 'light-dark(#15803d, #4ade80)',
                        };
                    @endphp

                    <li
                        wire:key="announcement-{{ $announcement->id }}"
                        style="list-style: none;"
                        class="overflow-hidden rounded-xl bg-gray-50 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"
                    >
                        <div
                            class="border-s-4 {{ $borderClass }}"
                            style="display: flex; align-items: flex-start; gap: 0.75rem 1rem; padding-block: 0.75rem; padding-inline: 1rem;"
                        >
                            <div style="flex-shrink: 0; line-height: 0;">
                                <x-filament::icon
                                    :icon="$icon"
                                    style="width: 2.5rem; height: 2.5rem; color: {{ $iconColorCss }};"
                                />
                            </div>

                            <div style="min-width: 0; flex: 1 1 0%;" class="space-y-1">
                                <h3
                                    class="fi-wi-announcement-title"
                                    style="margin: 0; font-size: 1.25rem; font-weight: 700; line-height: 1.35; color: light-dark(#030712, #ffffff);"
                                >
                                    {{ $announcement->title }}
                                </h3>

                                <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-300">
                                    <p class="whitespace-pre-wrap">{{ $announcement->body }}</p>
                                </div>
                            </div>

                            @if ($announcement->is_dismissible)
                                <div style="flex-shrink: 0; margin-inline-start: auto;">
                                    <x-filament::icon-button
                                        color="gray"
                                        icon="heroicon-o-x-mark"
                                        :label="__('announcements::widget.dismiss')"
                                        wire:click="dismiss({{ $announcement->id }})"
                                    />
                                </div>
                            @endif
                        </div>
                    </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
