<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-white dark:bg-[#111111]">
    @php
        $profileImages = app(\App\Services\ProfileImages::class);
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Home') }} - {{ config('app.name', 'CSPortfolio') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.appearance')
        
    </head>
    <body class="relative isolate font-sans text-zinc-900 dark:text-zinc-100 flex flex-col items-center min-h-screen py-2 mx-2 lg:mx-18">
        <div aria-hidden="true" class="pointer-events-none sticky top-0 -z-10 -mb-[100vh] h-screen w-full shrink-0 overflow-hidden">
            <div class="absolute inset-y-0 right-[12%] w-[22%] border-x border-zinc-200/70 bg-zinc-100/60 dark:border-zinc-800 dark:bg-zinc-900/50"></div>
            <div class="absolute -right-24 top-12 size-64 rounded-full border border-sky-300/60 dark:border-sky-800/60 sm:size-96"></div>
            <div class="absolute -right-12 top-24 size-64 rounded-full border border-sky-300/60 dark:border-sky-800/60 sm:size-96"></div>
            <div class="absolute bottom-16 -left-20 size-48 rotate-45 border border-amber-300/60 dark:border-amber-800/60 sm:size-72"></div>
        </div>
        <header class="px-3 sm:px-6 py-2.5 flex items-center justify-between gap-2 w-full">
            <div class="font-bold text-md">
                <span></span>
                CSPortfolio
            </div>
            <nav class="flex items-center gap-3 py-2 sm:p-4">
                <div class="flex items-center justify-center">
                    <input 
                        type="checkbox" 
                        aria-label="Toggle dark mode"
                        class="lamp-toggle text-base mx-auto focus:outline-none focus-visible:ring-2 focus-visible:ring-[#fdea7b]" 
                        id="mode-toggle"
                    />
                </div>
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Log in</a>
                @endauth
            </nav>
        </header>

        <div class="flex min-w-0 flex-col w-full px-3 sm:px-6 mt-10 transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <header>
                <h1 class="text-3xl sm:text-4xl my-3 font-manrope font-semibold">
                    Meet CATSU Computer Science
                </h1>
                <p class="font-sans max-w-2xl text-gray-500 dark:text-zinc-400">
                    Browse profiles from Computer Science students at Catanduanes State University.
                </p>
            </header>

            <main class="border border-gray-300 rounded-md my-10 flex w-full flex-col min-h-180 bg-white/95 dark:border-zinc-700 dark:bg-[#111111]/95">
                <div class="flex flex-col gap-3 mt-2 py-2 border-b border-gray-300 dark:border-zinc-700 px-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <ul class="flex p-2 gap-6">
                        @foreach ($blocks as $value => $label)
                            <li>
                                <form method="GET" action="{{ route('home') }}">
                                    @if ($search !== '')
                                        <input type="hidden" name="search" value="{{ $search }}">
                                    @endif
                                    <button
                                        type="submit"
                                        name="block"
                                        value="{{ $block === $value ? '' : $value }}"
                                        aria-pressed="{{ $block === $value ? 'true' : 'false' }}"
                                        @class([
                                            'cursor-pointer border-b-2 px-1 pb-1 font-bold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-500',
                                            'border-black dark:border-white' => $block === $value,
                                            'border-transparent text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white' => $block !== $value,
                                        ])
                                    >{{ $label }}</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <form method="GET" action="{{ route('home') }}" role="search" class="flex min-w-0 items-center gap-2 rounded-md border border-gray-300 px-3 py-2 focus-within:ring-2 focus-within:ring-zinc-400 dark:border-zinc-700 sm:w-72">
                        <input type="hidden" name="block" value="{{ $block ?? '' }}">
                        <img 
                            src="{{ asset('images/search-gray.svg') }}"
                            alt="" class="w-4 h-4 shrink-0"
                        />
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            maxlength="255"
                            aria-label="Search profiles"
                            placeholder="Search profiles"
                            class="min-w-0 flex-1 bg-transparent text-zinc-900 focus:outline-none dark:text-zinc-100"
                        />
                        <button type="submit" class="font-medium text-zinc-700 hover:underline dark:text-zinc-300">Search</button>
                    </form>
                </div>

                <div class="p-4 sm:p-6 lg:p-8 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    
                    @forelse ($users as $student)
                    <!-- Card Container -->
                    <div class="group relative flex flex-col overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-[#111111] hover:border-zinc-400 dark:hover:border-zinc-600 transition duration-200">
                        <div class="relative">
                            @php
                                $previews = $student->preview_images ?? [];
                                $desktopPreviews = collect(range(1, 3))->filter(fn ($index) => filled($previews['desktop_'.$index] ?? null));
                            @endphp
                            <div data-carousel class="relative flex aspect-video items-center justify-center bg-zinc-100 dark:bg-zinc-900">
                                @forelse ($desktopPreviews as $index)
                                    <img src="{{ $profileImages->url($previews['desktop_'.$index]) }}" alt="Desktop preview {{ $index }} for {{ $student->name }}" @class(['carousel-slide aspect-video w-full object-cover', 'hidden' => ! $loop->first]) />
                                @empty
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Portfolio previews not provided yet.</p>
                                @endforelse

                                @if ($desktopPreviews->count() > 1)
                                    <button type="button" onclick="changeSlide(this, -1)" aria-label="Previous portfolio preview" class="absolute left-2 top-1/2 z-20 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80">
                                        <span aria-hidden="true">&lsaquo;</span>
                                    </button>
                                    <button type="button" onclick="changeSlide(this, 1)" aria-label="Next portfolio preview" class="absolute right-2 top-1/2 z-20 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80">
                                        <span aria-hidden="true">&rsaquo;</span>
                                    </button>
                                @endif
                            </div>
                            <div class="px-6">
                                <div aria-hidden="true" class="relative z-10 -mt-14 flex h-25 w-25 items-center justify-center rounded-full border-4 border-white bg-slate-200 text-xl font-semibold dark:border-[#111111] dark:bg-zinc-800">
                                    @if ($student->profile_photo_path)
                                        <img src="{{ $profileImages->url($student->profile_photo_path) }}" alt="" class="h-full w-full rounded-full object-cover" />
                                    @else
                                        {{ $student->initials() }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col flex-1 px-6 pb-4 pt-2">
                            <!-- Header -->
                            <div class="flex mb-2.5 flex-1 items-start justify-center flex-col">
                                <h3 class="break-words font-semibold text-lg text-zinc-900 dark:text-zinc-100">
                                    <a href="{{ route('profile.student', $student) }}" class="after:absolute after:inset-0 after:z-0 after:content-['']">
                                        {{ $student->name }}
                                    </a>
                                </h3>
                                <p class="text-blue-400 text-sm">
                                    {{ $student->block ?? 'Block not provided' }}
                                </p>
                            </div>

                            <!-- Bio -->
                            <div>
                                <p class="break-words text-sm text-justify text-zinc-600 dark:text-zinc-400 leading-relaxed line-clamp-3 pt-1">
                                    {{ $student->bio }}
                                </p>
                            </div>

                            <!-- Redirection -->
                            <div class="flex mt-3 justify-start items-center gap-2">
                                @foreach (['facebook' => 'facebook-icon.svg', 'linkedin' => 'linked-icon.svg', 'github' => 'github-icon.svg', 'instagram' => 'instagram-icon.svg', 'email' => 'email-icon.svg'] as $platform => $icon)
                                    @php($social = $student->socials->firstWhere('platform', $platform))
                                    @if ($social)
                                        <a href="{{ $platform === 'email' ? 'mailto:'.$social->url : $social->url }}" class="relative z-10 flex h-5 w-5 items-center justify-center motion-safe:transition-transform motion-safe:duration-200 motion-safe:hover:-translate-y-1 motion-safe:focus-visible:-translate-y-1">
                                            <img src="{{ asset('images/'.$icon) }}" alt="{{ match ($platform) { 'github' => 'GitHub', 'linkedin' => 'LinkedIn', default => ucfirst($platform) } }}" @class(['h-full w-full object-contain opacity-60 transition-[filter,opacity] dark:opacity-100 dark:invert', 'scale-125' => $platform === 'linkedin']) />
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                    </div>
                    @empty
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $search !== '' ? 'No matching profiles found.' : ($block !== null ? 'No complete profiles in this class yet.' : 'No complete profiles yet.') }}</p>
                    @endforelse
                </div>
            </main>
        </div>

        @include('partials.footer')
        @if (Route::has('register'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>
