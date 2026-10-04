<?php

namespace App\View\Components;

use App\Services\BrowserTimezone;
use DateTimeInterface;
use Illuminate\View\Component;
use Illuminate\View\View;

class LocalDateTime extends Component
{
    public function __construct(
        public ?DateTimeInterface $value = null,
        public string $format = 'd M Y H:i',
    ) {}

    public function render(): View
    {
        return view('components.local-date-time', [
            'formatted' => app(BrowserTimezone::class)->format($this->value, $this->format),
        ]);
    }
}
