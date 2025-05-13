<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-hidden scroll-smooth">
    <head>
        <meta charset="utf-8">
        <link rel="icon" href="/favicon.ico">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=0, interactive-widget=resizes-content">

        <title inertia>{{ config('app.name', 'Mia Agent') }}</title>

        @routes
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="h-full overflow-hidden font-sans text-gray-800 antialiased min-w-[320px] bg-gradient-to-br from-indigo-50 via-white to-purple-50 [&_button]:transition-all [&_button]:duration-200 [&_a]:transition-all [&_a]:duration-200 [&_input]:transition-all [&_input]:duration-200 [&_textarea]:transition-all [&_textarea]:duration-200">
        @inertia
    </body>
</html>
