<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>LinkShield Ai | Sentinel Dashboard</title>
        @vite(['resources/js/main.ts'])
    </head>
    <body class="bg-[#0f172a] text-slate-300 font-sans antialiased overflow-x-hidden min-h-screen">
        <div id="app"></div>
    </body>
</html>
