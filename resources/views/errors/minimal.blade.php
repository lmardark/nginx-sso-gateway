<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>@yield('title')</title>
        </x-inertia::head>
        <style>
            body {
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            }
        </style>
    </head>
    <body class="antialiased bg-[#FDFDFC]  dark:text-black dark:bg-[#101010]">
        <div class="relative flex items-top justify-center min-h-screen bg-[#FDFDFC]  dark:text-black dark:bg-[#101010] sm:items-center sm:pt-0" role="main">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="flex items-center pt-8 sm:justify-start sm:pt-0">
                    <h1 class="px-4 text-lg  dark:text-white border-r border-gray-400">
                        @yield('code')
                    </h1>

                    <div class="ml-4 text-lg dark:text-white">
                        @yield('message')
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
