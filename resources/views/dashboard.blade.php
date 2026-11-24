<x-layouts::app :title="__('Dashboard')">
    <main class="min-h-[calc(100vh-4rem)] bg-zinc-50 px-4 py-10 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">
            <header class="flex flex-col gap-1">
                <a href="{{ route('home') }}" class="mb-2 w-fit text-xs font-medium text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">
                    &larr; Back to Home
                </a>
                <h1 class="text-2xl font-semibold tracking-tight">Edit your profile</h1>
                <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Update the details and social links shown on your profile.</p>
            </header>

            @if (session('success'))
                <p role="status" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-200">
                    {{ session('success') }}
                </p>
            @endif

            <div role="note" class="rounded-md border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                If your password was provided to you or is based on your name, change it to a unique password.
                <a href="{{ route('security.edit') }}" class="font-semibold underline underline-offset-2">Change password</a>
            </div>

            <form method="POST" action="{{ route('dashboard.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf

                <section class="flex flex-col gap-5 rounded-md border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                    <div class="flex flex-col gap-1 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <h2 class="text-base font-semibold">Profile</h2>
                        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">To appear on the home page, add a bio, profile picture, all six previews, and both portfolio links.</p>
                    </div>

                    <div class="flex flex-col gap-2" data-image-crop data-crop-ratio="1:1" data-crop-source="{{ $user->profile_photo_path ? asset('storage/'.$user->profile_photo_path) : '' }}">
                        <label for="profile_photo" class="text-sm font-medium">Profile picture</label>
                        @if ($user->profile_photo_path)
                            <img data-crop-current src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="Current profile picture" class="h-20 w-20 rounded-full object-cover" />
                        @endif
                        <input id="profile_photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full min-w-0 rounded-md border border-zinc-300 bg-white p-1.5 text-xs text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-200 dark:hover:file:bg-zinc-700" />
                        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">JPEG, PNG or WebP, up to 2 MB. Leave empty to keep your current picture.</p>
                        @include('partials.image-crop-controls', ['cropId' => 'profile_photo', 'cropType' => 'profile'])
                        @error('profile_photo')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="flex min-w-0 flex-col gap-2">
                            <label for="name" class="text-sm font-medium">Name</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $user->name) }}"
                                autocomplete="name"
                                required
                                class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500"
                            />
                            @error('name')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex min-w-0 flex-col gap-2">
                            <label for="block" class="text-sm font-medium">Block</label>
                            <select id="block" name="block" required class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500">
                                <option value="">Select a block</option>
                                <option value="BSCS-2A" @selected(old('block', $user->block) === 'BSCS-2A')>BS Computer Science 2A</option>
                            </select>
                            @error('block')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="bio" class="text-sm font-medium">Bio</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="2000" class="w-full resize-none rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <section class="flex flex-col gap-5 rounded-md border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                    <div class="flex flex-col gap-1 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <h2 class="text-base font-semibold">Portfolio links</h2>
                        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Provide your portfolio repository and the URL of your deployed site.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach (['portfolio_repository_url' => 'Portfolio repository', 'portfolio_site_url' => 'Deployed portfolio'] as $field => $label)
                            <div class="flex min-w-0 flex-col gap-2">
                                <label for="{{ $field }}" class="text-sm font-medium">{{ $label }}</label>
                                <input id="{{ $field }}" name="{{ $field }}" type="url" required maxlength="2048" value="{{ old($field, $user->{$field}) }}" placeholder="https://..." class="w-full min-w-0 rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-2 focus:ring-zinc-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-500" />
                                @error($field)
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="flex flex-col gap-5 rounded-md border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                    <div class="flex flex-col gap-1 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <h2 class="text-base font-semibold">Portfolio previews</h2>
                        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Upload three desktop and three mobile previews. JPEG, PNG or WebP, up to 2 MB each. Adjust the crop to fit each screen.</p>
                        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Leave an existing preview empty to keep it.</p>
                    </div>

                    @foreach (range(1, 3) as $index)
                        <fieldset class="min-w-0 rounded-md border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                            <legend class="px-1 text-sm font-semibold">Preview {{ $index }}</legend>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach (['desktop' => 'Desktop (16:9)', 'mobile' => 'Mobile (9:16)'] as $type => $label)
                                    @php($slot = $type.'_'.$index)
                                    <div class="flex min-w-0 flex-col gap-2" data-image-crop data-crop-ratio="{{ $type === 'desktop' ? '16:9' : '9:16' }}" data-crop-source="{{ filled($user->preview_images[$slot] ?? null) ? asset('storage/'.$user->preview_images[$slot]) : '' }}">
                                        <label for="preview-{{ $slot }}" class="text-sm font-medium">{{ $label }}</label>
                                        @if (filled($user->preview_images[$slot] ?? null))
                                            <img data-crop-current src="{{ asset('storage/'.$user->preview_images[$slot]) }}" alt="Current {{ strtolower($label) }} preview {{ $index }}" @class(['w-full rounded-md object-cover', 'aspect-video' => $type === 'desktop', 'aspect-[9/16] max-w-24' => $type === 'mobile']) />
                                        @endif
                                        <input id="preview-{{ $slot }}" name="previews[{{ $slot }}]" type="file" accept="image/jpeg,image/png,image/webp" @required(blank($user->preview_images[$slot] ?? null)) class="w-full min-w-0 rounded-md border border-zinc-300 bg-white p-1.5 text-xs text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-400 dark:file:bg-zinc-800 dark:file:text-zinc-200 dark:hover:file:bg-zinc-700" />
                                        @include('partials.image-crop-controls', ['cropId' => 'preview-'.$slot, 'cropType' => $type])
                                        @error('previews.'.$slot)
                                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </section>

                <section class="flex flex-col gap-5 rounded-md border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                    <div class="flex flex-col gap-1 border-b border-zinc-100 pb-4 dark:border-zinc-800">
                        <h2 class="text-base font-semibold">Social links</h2>
                        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Add the links you want people to find. Leave a field blank to remove it.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ($socialPlatforms as $platform => $label)
                            @php($fieldId = 'social-'.$platform)
                            <div class="flex flex-col gap-2">
                                <label for="{{ $fieldId }}" class="text-sm font-medium">{{ $label }}</label>
                                <input
                                    id="{{ $fieldId }}"
                                    name="socials[{{ $platform }}]"
                                    type="{{ $platform === 'email' ? 'email' : 'url' }}"
                                    value="{{ old('socials.'.$platform, $socials[$platform] ?? '') }}"
                                    placeholder="{{ $platform === 'email' ? 'you@example.com' : 'https://...' }}"
                                    autocomplete="{{ $platform === 'email' ? 'email' : 'url' }}"
                                    class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition placeholder:text-zinc-400 focus:border-zinc-500 focus:ring-2 focus:ring-zinc-500/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-500"
                                />
                                @error('socials.'.$platform)
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white dark:focus:ring-offset-zinc-950">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </main>
</x-layouts::app>
