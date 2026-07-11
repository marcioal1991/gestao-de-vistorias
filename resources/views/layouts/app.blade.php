<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#873a4e">
        <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

        <title>{{ config('app.name', 'Gestão de Vistorias') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col max-w-md mx-auto bg-gray-50 shadow-sm relative">
            <livewire:layout.navigation />

            @if (isset($header))
                <header class="bg-white border-b border-gray-100 px-4 py-3">
                    {{ $header }}
                </header>
            @endif

            <main class="flex-1 pb-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
