<?php

namespace App\Services;

use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;

class BrowserTimezone
{
    public function current(): string
    {
        $timezone = session('browser_timezone');

        return is_string($timezone) && $this->isValid($timezone)
            ? $timezone
            : (string) config('app.timezone', 'UTC');
    }

    public function isValid(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);

            return in_array($timezone, DateTimeZone::listIdentifiers(), true);
        } catch (\Exception) {
            return false;
        }
    }

    public function set(string $timezone): void
    {
        if ($this->isValid($timezone)) {
            session()->put('browser_timezone', $timezone);
        }
    }

    public function format(?DateTimeInterface $timestamp, string $format): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return Carbon::instance($timestamp)->copy()->timezone($this->current())->format($format);
    }
}
