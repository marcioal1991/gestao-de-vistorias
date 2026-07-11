<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#4f46e5">

        <title>{{ config('app.name', 'Gestão de Vistorias') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 bg-gray-100">
            <div class="flex flex-col items-center gap-2 mb-6">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 text-white text-xl font-bold">GV</span>
                <span class="text-lg font-semibold text-gray-800">{{ config('app.name') }}</span>
            </div>

            <div class="w-full max-w-sm px-6 py-6 bg-white shadow-md rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
