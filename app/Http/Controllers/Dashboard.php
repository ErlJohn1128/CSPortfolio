<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class Dashboard extends Controller
{
    private const SOCIAL_PLATFORMS = [
        'linkedin' => 'LinkedIn',
        'github' => 'GitHub',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'email' => 'Email',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'socialPlatforms' => self::SOCIAL_PLATFORMS,
            'socials' => $user->socials()->pluck('url', 'platform')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $previousPreviews = $user->preview_images ?? [];
        $socialRules = [
            'socials' => ['required', 'array:'.implode(',', array_keys(self::SOCIAL_PLATFORMS))],
        ];
        $previewRules = [];

        foreach (['desktop', 'mobile'] as $type) {
            foreach (range(1, 3) as $index) {
                $slot = $type.'_'.$index;
                $previewRules['previews.'.$slot] = [
                    filled($previousPreviews[$slot] ?? null) ? 'nullable' : 'required',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ];
            }
        }

        foreach (array_keys(self::SOCIAL_PLATFORMS) as $platform) {
            $socialRules['socials.'.$platform] = $platform === 'email'
                ? ['nullable', 'email', 'max:2048']
                : ['nullable', 'url', 'max:2048'];
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'block' => ['required', Rule::in(['BSCS-2A'])],
            'bio' => ['nullable', 'string', 'max:2000'],
            'portfolio_repository_url' => ['required', 'url:http,https', 'max:2048'],
            'portfolio_site_url' => ['required', 'url:http,https', 'max:2048'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'previews' => ['nullable', 'array:desktop_1,desktop_2,desktop_3,mobile_1,mobile_2,mobile_3'],
            ...$previewRules,
            ...$socialRules,
        ]);

        $previousPhoto = $user->profile_photo_path;
        $newPhoto = null;
        $previews = $previousPreviews;
        $newFiles = [];
        $replacedFiles = [];

        try {
            if ($request->hasFile('profile_photo')) {
                $newPhoto = $request->file('profile_photo')->store('profile-pictures', 'public');

                if ($newPhoto === false) {
                    throw new RuntimeException('Failed to store profile picture.');
                }

                $newFiles[] = $newPhoto;

                if ($previousPhoto !== null) {
                    $replacedFiles[] = $previousPhoto;
                }
            }

            foreach (['desktop', 'mobile'] as $type) {
                foreach (range(1, 3) as $index) {
                    $slot = $type.'_'.$index;
                    $file = $request->file('previews.'.$slot);

                    if ($file === null) {
                        continue;
                    }

                    $path = $file->store('portfolio-previews', 'public');

                    if ($path === false) {
                        throw new RuntimeException('Failed to store portfolio preview.');
                    }

                    $newFiles[] = $path;

                    if (filled($previousPreviews[$slot] ?? null)) {
                        $replacedFiles[] = $previousPreviews[$slot];
                    }

                    $previews[$slot] = $path;
                }
            }

            DB::transaction(function () use ($user, $validated, $newPhoto, $previews): void {
                $user->update([
                    'name' => $validated['name'],
                    'block' => $validated['block'],
                    'bio' => $validated['bio'] ?? null,
                    'portfolio_repository_url' => $validated['portfolio_repository_url'],
                    'portfolio_site_url' => $validated['portfolio_site_url'],
                    'profile_photo_path' => $newPhoto ?? $user->profile_photo_path,
                    'preview_images' => $previews,
                ]);

                foreach (array_keys(self::SOCIAL_PLATFORMS) as $platform) {
                    $url = $validated['socials'][$platform] ?? null;

                    if (filled($url)) {
                        $user->socials()->updateOrCreate(
                            ['platform' => $platform],
                            ['url' => $url],
                        );

                        continue;
                    }

                    $user->socials()->where('platform', $platform)->delete();
                }
            });
        } catch (Throwable $exception) {
            foreach ($newFiles as $path) {
                if (! Storage::disk('public')->delete($path)) {
                    Log::warning('Failed to delete uploaded image after profile update failed.', ['path' => $path]);
                }
            }

            throw $exception;
        }

        foreach ($replacedFiles as $path) {
            if (! Storage::disk('public')->delete($path)) {
                Log::warning('Failed to delete replaced profile image.', ['path' => $path]);
            }
        }

        return redirect()->route('dashboard')->with('success', 'Profile updated.');
    }
}
