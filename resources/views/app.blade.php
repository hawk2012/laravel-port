<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#07080d">
    <meta name="description" content="Kontur AI — платформа генерации изображений, видео, аудио и текста на Laravel + Vue 3.">

    <title inertia>{{ config('app.name', 'Kontur AI') }}</title>

    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%236366f1'/%3E%3Cpath d='M9 22V10h3v5.2l4.6-5.2h3.7l-5 5.5 5.3 6.5h-3.8l-3.6-4.6-1.2 1.3V22z' fill='white'/%3E%3C/svg%3E">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="min-h-screen bg-ink-950">
    @inertia
</body>
</html>
