<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-white dark:bg-[#111111]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $student->name ?? 'Allen Bien Carlo T. Tatel' }} - {{ config('app.name', 'CSPortfolio') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.appearance')
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-zinc-900 dark:bg-[#111111] dark:text-zinc-100">
        <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-6 py-10">
            <a href="{{ route('home') }}" class="w-fit rounded-md text-sm font-medium text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">
                &larr; Back to profiles
            </a>

            <article class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-[#111111]">
        
                    @if (isset($student))
                        @include('partials.portfolio-previews', ['student' => $student, 'previewPadding' => 'p-6'])
                    @else
                        <figure class="relative min-h-64 bg-zinc-100 dark:bg-zinc-900">
                            <img
                                src="{{ asset('images/test-white-bg.png') }}"
                                alt="Portfolio preview for Allen Bien Carlo T. Tatel"
                                class="h-full min-h-64 w-full object-cover"
                            >
                            <figcaption class="absolute bottom-4 left-4 rounded-full bg-black/65 px-3 py-1 text-xs font-medium text-white">
                                Portfolio preview
                            </figcaption>
                        </figure>
                    @endif

                    <div class="p-5 sm:p-6">
                        <div class="flex items-center gap-4">
                            @if (isset($student) && $student->profile_photo_path)
                                <img src="{{ app(\App\Services\ProfileImages::class)->url($student->profile_photo_path) }}" alt="Profile picture of {{ $student->name }}" class="h-16 w-16 shrink-0 rounded-full object-cover" />
                            @endif
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-blue-500">{{ isset($student) ? ($student->block ?? 'Block not provided') : 'BSCS-2A' }}</p>
                                <h1 class="mt-1 break-words text-xl font-semibold leading-tight tracking-tight sm:text-2xl">
                                    {{ $student->name ?? 'Allen Bien Carlo T. Tatel' }}
                                </h1>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-800 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] sm:gap-6">
                            <div class="min-w-0">
                                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Email address</h2>
                                @if (isset($student) && $socials->has('email'))
                                    <a class="mt-1 block w-fit break-all text-sm leading-5 hover:underline" href="mailto:{{ $socials['email'] }}">{{ $socials['email'] }}</a>
                                @else
                                    <p class="mt-1 text-sm leading-5">Not provided</p>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Bio</h2>
                                <p class="mt-1 max-w-prose break-words text-sm leading-5 text-zinc-700 dark:text-zinc-300">
                                    {{ isset($student) ? $student->bio : 'Aspiring backend developer focused on Java, C++, and web applications. Passionate about building reliable systems and clean code.' }}
                                </p>
                            </div>
                        </div>
                    </div>
           
            </article>

            <div>
                <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-[#111111]">
                    <h2 class="text-lg font-semibold">Socials</h2>
                    <dl class="mt-5 flex flex-col gap-4">
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Facebook</dt>
                            <dd class="mt-1">
                                @if (isset($student) && $socials->has('facebook'))
                                    <a class="break-all hover:underline" href="{{ $socials['facebook'] }}">{{ $socials['facebook'] }}</a>
                                @else
                                    Not provided
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">LinkedIn</dt>
                            <dd class="mt-1">
                                @if (isset($student) && $socials->has('linkedin'))
                                    <a class="break-all hover:underline" href="{{ $socials['linkedin'] }}">{{ $socials['linkedin'] }}</a>
                                @else
                                    Not provided
                                @endif
                            </dd>
                        </div>
                        @if (isset($student))
                            @foreach (['github' => 'GitHub', 'instagram' => 'Instagram'] as $platform => $label)
                                @if ($socials->has($platform))
                                    <div>
                                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                                        <dd class="mt-1"><a class="break-all hover:underline" href="{{ $socials[$platform] }}">{{ $socials[$platform] }}</a></dd>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </dl>
                </section>
            </div>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-[#111111] sm:p-8">
                <h2 class="text-xl font-semibold">Portfolio links</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                        <h3 class="font-medium">Deployed portfolio</h3>
                        @if (isset($student) && filled($student->portfolio_site_url))
                            <a class="mt-2 block break-all text-sm text-zinc-600 hover:underline dark:text-zinc-400" href="{{ $student->portfolio_site_url }}">{{ $student->portfolio_site_url }}</a>
                        @else
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Link not provided yet.</p>
                        @endif
                    </div>
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                        <h3 class="font-medium">Repository</h3>
                        @if (isset($student) && filled($student->portfolio_repository_url))
                            <a class="mt-2 block break-all text-sm text-zinc-600 hover:underline dark:text-zinc-400" href="{{ $student->portfolio_repository_url }}">{{ $student->portfolio_repository_url }}</a>
                        @else
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Link not provided yet.</p>
                        @endif
                    </div>
                </div>
            </section>
        </main>
        @include('partials.footer')
    </body>
</html>