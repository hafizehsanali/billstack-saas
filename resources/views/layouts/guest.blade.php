<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BillStack') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/png" href="{{ asset('favicon-64.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-8 bg-gray-100">
            <a href="/" class="mb-6 flex flex-col items-center text-center">
                <x-application-logo class="w-16 h-16 fill-current text-indigo-600" />
                <span class="mt-3 text-2xl font-semibold text-gray-900">BillStack</span>
                <span class="mt-1 text-sm text-gray-500">Inventory, billing, and account control for small businesses</span>
            </a>

            <div class="w-full sm:max-w-md px-6 py-5 bg-white shadow-md overflow-hidden rounded-lg border border-gray-100">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
