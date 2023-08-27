<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/js/app.js'])
</head>

<body class="bg-gray-100 dark:bg-gray-950">
    @inertia
</body>

</html>