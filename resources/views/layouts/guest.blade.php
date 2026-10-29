<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Parañaque CSN Alert System') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/csn-logo.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                50: '#eef5fb',
                                100: '#d6e6f4',
                                700: '#005b96',
                                800: '#004a7a',
                                900: '#003559',
                            }
                        }
                    }
                }
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased bg-slate-100 min-h-screen flex flex-col justify-center items-center py-10 px-4 sm:px-6">
        <div class="w-full max-w-md flex flex-col items-center">
            {{-- Brand header --}}
            <div class="flex flex-col items-center mb-6">
                <a href="{{ route('login') }}" class="flex flex-col items-center gap-3 group focus:outline-none">
                    <x-application-logo class="w-24 h-24" />
                    <div class="text-center">
                        <h1 class="text-xl font-bold tracking-tight text-slate-800">Parañaque CSN</h1>
                        <p class="text-xs text-slate-500 font-semibold tracking-wide">Emergency Alert & Response System</p>
                    </div>
                </a>
            </div>

            {{-- Auth Card --}}
            <div class="w-full bg-white border border-slate-200/80 shadow-xl rounded-2xl p-6 sm:p-8">
                {{ $slot }}
            </div>

            {{-- Footer Note --}}
            <p class="mt-6 text-center text-xs text-slate-400">
                City of Parañaque &bull; Special Education & Crisis Response
            </p>
        </div>
    </body>
</html>
