<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-[#111111] dark:text-zinc-100">
        <div class="flex min-h-svh flex-col lg:grid lg:grid-cols-2">
            <aside class="relative overflow-hidden bg-zinc-900 px-6 py-7 text-white sm:px-10 lg:flex lg:min-h-svh lg:flex-col lg:justify-between lg:p-12">
                <div aria-hidden="true" class="pointer-events-none absolute -right-32 -top-32 size-96 rounded-full bg-blue-500/20 blur-3xl"></div>
                <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white" wire:navigate>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-white text-zinc-900">
                        <x-app-logo-icon class="size-7" />
                    </span>
                    <span class="text-base font-semibold tracking-tight">CSPortfolio</span>
                </a>

                <div class="relative hidden max-w-lg lg:block">
                    <p class="mb-5 text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">{{ __('CSPortfolio') }}</p>
                    <h2 class="font-manrope text-4xl font-semibold leading-tight tracking-tight">{{ __('Need help?') }}</h2>
                    <p class="mt-5 max-w-md text-base leading-7 text-zinc-300">{{ __('If something goes wrong, please contact Earl John Bozar on Messenger or by email. Thank you!') }}</p>
                </div>

            </aside>

            <main class="flex flex-1 items-center justify-center px-6 py-12 sm:px-10 lg:px-16">
                <div class="w-full max-w-md">
                    <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-zinc-500 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white" wire:navigate>
                        <span aria-hidden="true">&larr;</span> {{ __('Back to profiles') }}
                    </a>

                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-800 dark:bg-zinc-900">
                        {{ $slot }}
                    </div>
                    <p class="mt-6 text-sm leading-6 text-zinc-600 dark:text-zinc-400 lg:hidden">
                        {{ __('If something goes wrong, please contact Earl John Bozar on Messenger or by email. Thank you!') }}
                    </p>
                </div>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
