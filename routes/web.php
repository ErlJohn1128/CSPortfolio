<?php

use App\Http\Controllers\Dashboard;
use App\Http\Middleware\HydrateProfileUploads;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::get('/email/verification/complete/{user}/{hash}', function (User $user, string $hash) {
    abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

    if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
        event(new Verified($user));
    }

    return response()->view('email-verified')
        ->header('Cache-Control', 'no-store')
        ->header('Referrer-Policy', 'no-referrer');
})->middleware(['signed', 'throttle:6,1'])->name('verification.complete');

Route::get('/', function (Request $request) {
    $blocks = ['BSCS-2A' => 'BSCS2A'];
    $filters = $request->validate([
        'search' => ['nullable', 'string', 'max:255'],
        'block' => ['nullable', 'string', Rule::in(array_keys($blocks))],
    ]);
    $search = trim($filters['search'] ?? '');
    $block = array_key_exists('block', $filters) ? $filters['block'] : 'BSCS-2A';
    $users = User::query()
        ->select(['id', 'name', 'block', 'bio', 'profile_photo_path', 'preview_images', 'portfolio_repository_url', 'portfolio_site_url'])
        ->when($block !== null, fn ($query) => $query->where('block', $block))
        ->whereNotNull('bio')
        ->whereNotNull('profile_photo_path')
        ->whereNotNull('portfolio_repository_url')
        ->whereNotNull('portfolio_site_url')
        ->with('socials')
        ->orderBy('name')
        ->get()
        ->filter(function (User $user) use ($search): bool {
            if (blank($user->bio) || blank($user->profile_photo_path) || blank($user->portfolio_repository_url) || blank($user->portfolio_site_url)) {
                return false;
            }

            foreach (['desktop', 'mobile'] as $type) {
                foreach (range(1, 3) as $index) {
                    if (blank($user->preview_images[$type.'_'.$index] ?? null)) {
                        return false;
                    }
                }
            }

            return $search === '' || Str::contains(mb_strtolower($user->name.' '.$user->block.' '.$user->bio), mb_strtolower($search));
        });

    return view('landing-page', compact('users', 'search', 'block', 'blocks'));
})->name('home');
Route::view('/profile', 'profile')->name('profile.show');
Route::get('/profile/{user}', function (User $user) {
    abort_if(blank($user->bio), 404);

    return view('profile', [
        'student' => $user,
        'socials' => $user->socials()->pluck('url', 'platform'),
    ]);
})->name('profile.student');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [Dashboard::class, 'index'])->name('dashboard');
    Route::post('/dashboard/uploads', [Dashboard::class, 'uploadUrl'])->middleware('throttle:30,1')->name('dashboard.upload-url');
    Route::post('/dashboard', [Dashboard::class, 'store'])->middleware(HydrateProfileUploads::class)->name('dashboard.store');
});

require __DIR__.'/settings.php';
