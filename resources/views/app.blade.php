<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" type="image/png" href="{{ url('/favicon.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/apple-touch-icon.png') }}">

        <meta name="description" content="{{ (string) (config('app.description') ?? env('APP_DESCRIPTION', 'HR documentation & incident tracking for PH SMEs.')) }}">
        <meta name="robots" content="index,follow">

        <meta property="og:site_name" content="{{ config('app.name', 'Crewly') }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name', 'Crewly') }}">
        <meta property="og:description" content="{{ (string) (config('app.description') ?? env('APP_DESCRIPTION', 'HR documentation & incident tracking for PH SMEs.')) }}">
        <meta property="og:image" content="{{ url('/storage-images/crewly_logo.png') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ config('app.name', 'Crewly') }}">
        <meta name="twitter:description" content="{{ (string) (config('app.description') ?? env('APP_DESCRIPTION', 'HR documentation & incident tracking for PH SMEs.')) }}">
        <meta name="twitter:image" content="{{ url('/storage-images/crewly_logo.png') }}">

        <title inertia>{{ config('app.name', 'Crewly') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
