<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-white dark:bg-[#111111]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        <title>{{ __('Email verified') }} - {{ config('app.name', 'CSPortfolio') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.appearance')
    </head>
    <body class="flex min-h-screen items-center justify-center px-6 font-sans text-zinc-900 dark:text-zinc-100">
        <main class="w-full max-w-md rounded-2xl border border-zinc-200 p-8 text-center shadow-sm dark:border-zinc-800">
            <div aria-hidden="true" class="mx-auto flex size-12 items-center justify-center rounded-full bg-blue-50 text-2xl text-blue-700 dark:bg-blue-950 dark:text-blue-300">&#10003;</div>
            <h1 class="mt-5 text-2xl font-semibold tracking-tight">{{ __('Email verified') }}</h1>
            <p class="mt-3 leading-6 text-zinc-600 dark:text-zinc-400">{{ __('Your email address has been verified. You can close this page, return to the app, and refresh it to continue.') }}</p>
        </main>
    </body>
</html>
