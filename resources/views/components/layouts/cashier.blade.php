<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'NasLabs POS' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="nl-page min-h-screen antialiased">
        {{ $slot }}

        @livewireScripts
        <script>
            const syncBrowserTimezone = () => {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                const storageKey = 'naslabs.browser-timezone';

                if (! timezone || window.sessionStorage.getItem(storageKey) === timezone) {
                    return;
                }

                fetch(@js(route('cashier.browser-timezone')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ timezone }),
                })
                    .then((response) => response.ok ? response.json() : null)
                    .then((payload) => {
                        window.sessionStorage.setItem(storageKey, timezone);

                        if (payload?.updated) {
                            window.location.reload();
                        }
                    })
                    .catch(() => {});
            };

            document.addEventListener('livewire:init', syncBrowserTimezone);
            if (window.Livewire) {
                syncBrowserTimezone();
            }
        </script>
    </body>
</html>
