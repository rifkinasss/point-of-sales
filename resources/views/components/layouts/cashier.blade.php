<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'NasLabs POS' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="nl-page min-h-screen antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
