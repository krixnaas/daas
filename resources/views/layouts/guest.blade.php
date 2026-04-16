<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>DAAS - Login</title>

        @fluxAppearance
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-zinc-50 dark:bg-zinc-950 pb-safe pt-safe">
        <div class="min-h-screen flex flex-col justify-center">
            {{ $slot }}
        </div>

        @fluxScripts
    </body>
</html>