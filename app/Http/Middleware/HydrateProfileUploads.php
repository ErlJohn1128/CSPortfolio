<?php

namespace App\Http\Middleware;

use App\Services\ProfileImages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HydrateProfileUploads
{
    public function __construct(private ProfileImages $images) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slots = ['profile_photo', 'desktop_1', 'desktop_2', 'desktop_3', 'mobile_1', 'mobile_2', 'mobile_3'];
        $validated = $request->validate([
            'staged' => ['nullable', 'array:'.implode(',', $slots)],
            'staged.*' => ['required', Rule::in(['1'])],
        ]);
        $staged = $validated['staged'] ?? [];
        if ($staged === []) {
            return $next($request);
        }

        abort_unless($this->images->usesDirectUploads(), 422);

        $temporaryFiles = [];
        $temporaryObjects = [];
        $stagedFiles = [];

        try {
            foreach (array_keys($staged) as $slot) {
                $upload = $request->session()->get('profile_uploads.'.$slot);
                $field = $slot === 'profile_photo' ? $slot : 'previews.'.$slot;
                if (! is_array($upload) || ($upload['user_id'] ?? null) !== $request->user()->getKey() || ($upload['expires_at'] ?? 0) < now()->timestamp) {
                    throw ValidationException::withMessages([$field => 'The uploaded image has expired. Please select it again.']);
                }

                $temporaryObjects[$slot] = $upload['path'];
                $contents = $this->images->downloadTemporary($upload['path'], $field);
                $path = tempnam(sys_get_temp_dir(), 'profile-upload-');
                if ($path === false) {
                    throw new RuntimeException('Unable to create a temporary image file.');
                }
                $temporaryFiles[] = $path;
                if (file_put_contents($path, $contents) === false) {
                    throw new RuntimeException('Unable to save a temporary image file.');
                }

                $extension = match ($upload['mime']) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                };
                $file = new UploadedFile($path, 'image.'.$extension, $upload['mime'], null, true);
                if ($slot === 'profile_photo') {
                    $stagedFiles['profile_photo'] = $file;
                } else {
                    $stagedFiles['previews'][$slot] = $file;
                }
            }

            $request->attributes->set('profile_staged_files', $stagedFiles);

            return $next($request);
        } finally {
            foreach ($temporaryFiles as $path) {
                if (is_file($path) && ! unlink($path)) {
                    Log::warning('Failed to remove a temporary profile image.');
                }
            }
            foreach ($temporaryObjects as $slot => $path) {
                $request->session()->forget('profile_uploads.'.$slot);
                try {
                    $this->images->deleteTemporary($path);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }
    }
}
