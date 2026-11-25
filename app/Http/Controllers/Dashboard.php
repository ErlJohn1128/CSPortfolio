<?php

namespace App\Http\Controllers;

use App\Services\ProfileImages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
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

    public function uploadUrl(Request $request, ProfileImages $images): JsonResponse
    {
        abort_unless($images->usesDirectUploads(), 404);

        $validated = $request->validate([
            'slot' => ['required', Rule::in(['profile_photo', 'desktop_1', 'desktop_2', 'desktop_3', 'mobile_1', 'mobile_2', 'mobile_3'])],
            'mime' => ['required', Rule::in(['image/jpeg', 'image/png', 'image/webp'])],
        ]);

        $user = $request->user();
        abort_if($user === null, 403);
        if ($request->session()->get('profile_upload_cleanup_at', 0) < now()->subHour()->timestamp) {
            $images->cleanupTemporary($user->getKey());
            $request->session()->put('profile_upload_cleanup_at', now()->timestamp);
        }

        $slot = $validated['slot'];
        $previous = $request->session()->get('profile_uploads.'.$slot);
        if ($previous && $previous['user_id'] === $user->getKey()) {
            $images->deleteTemporary($previous['path']);
        }

        $path = $user->getKey().'/'.Str::uuid();
        $url = $images->temporaryUploadUrl($path, $validated['mime']);
        $request->session()->put('profile_uploads.'.$slot, [
            'user_id' => $user->getKey(),
            'path' => $path,
            'mime' => $validated['mime'],
            'expires_at' => now()->addMinutes(15)->timestamp,
        ]);

        return response()->json(['url' => $url]);
    }

    public function store(Request $request, ProfileImages $images): RedirectResponse
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

        $stagedFiles = $request->attributes->get('profile_staged_files', []);
        $validated = Validator::make(array_replace_recursive($request->all(), $stagedFiles), [
            'name' => ['required', 'string', 'max:255'],
            'block' => ['required', Rule::in(['BSCS-2A'])],
            'bio' => ['nullable', 'string', 'max:2000'],
            'portfolio_repository_url' => ['required', 'url:http,https', 'max:2048'],
            'portfolio_site_url' => ['required', 'url:http,https', 'max:2048'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'previews' => ['nullable', 'array:desktop_1,desktop_2,desktop_3,mobile_1,mobile_2,mobile_3'],
            ...$previewRules,
            ...$socialRules,
        ])->validate();

        $previousPhoto = $user->profile_photo_path;
        $newPhoto = null;
        $previews = $previousPreviews;
        $newFiles = [];
        $replacedFiles = [];

        try {
            $photo = $stagedFiles['profile_photo'] ?? $request->file('profile_photo');
            if ($photo !== null) {
                $newPhoto = $images->store($photo, 'profile-pictures');

                $newFiles[] = $newPhoto;

                if ($previousPhoto !== null) {
                    $replacedFiles[] = $previousPhoto;
                }
            }

            foreach (['desktop', 'mobile'] as $type) {
                foreach (range(1, 3) as $index) {
                    $slot = $type.'_'.$index;
                    $file = $stagedFiles['previews'][$slot] ?? $request->file('previews.'.$slot);

                    if ($file === null) {
                        continue;
                    }

                    $path = $images->store($file, 'portfolio-previews');

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
                try {
                    if (! $images->delete($path)) {
                        Log::warning('Failed to delete uploaded image after profile update failed.', ['path' => $path]);
                    }
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            throw $exception;
        }

        foreach ($replacedFiles as $path) {
            try {
                if (! $images->delete($path)) {
                    Log::warning('Failed to delete replaced profile image.', ['path' => $path]);
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
        }

        return redirect()->route('dashboard')->with('success', 'Profile updated.');
    }
}
