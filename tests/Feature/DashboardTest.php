<?php

use App\Models\User;
use App\Services\ProfileImages;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;

function existingPortfolioPreviews(): array
{
    return [
        'desktop_1' => 'portfolio-previews/desktop-1.png',
        'desktop_2' => 'portfolio-previews/desktop-2.png',
        'desktop_3' => 'portfolio-previews/desktop-3.png',
        'mobile_1' => 'portfolio-previews/mobile-1.png',
        'mobile_2' => 'portfolio-previews/mobile-2.png',
        'mobile_3' => 'portfolio-previews/mobile-3.png',
    ];
}

function validPortfolioLinks(): array
{
    return [
        'portfolio_repository_url' => 'https://github.com/example/portfolio',
        'portfolio_site_url' => 'https://portfolio.example.com',
    ];
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Edit your profile')
        ->assertSee('Profile picture')
        ->assertSee('Portfolio previews')
        ->assertSee('Block')
        ->assertSee('BS Computer Science 2A')
        ->assertSee('Bio')
        ->assertSee('LinkedIn')
        ->assertSee('GitHub')
        ->assertSee('Facebook')
        ->assertSee('Instagram')
        ->assertSee('Email');
});

test('unverified users cannot view or update the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->post(route('dashboard.store'), ['name' => 'Changed'])->assertRedirect(route('verification.notice'));
    expect($user->fresh()->name)->toBe($user->name);
});

test('portfolio links are required and accept only web urls', function () {
    $user = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $details = ['name' => $user->name, 'block' => 'BSCS-2A', 'socials' => ['email' => '']];

    $this->actingAs($user)->post(route('dashboard.store'), $details)
        ->assertInvalid(['portfolio_repository_url', 'portfolio_site_url']);
    $this->post(route('dashboard.store'), [
        ...$details,
        'portfolio_repository_url' => 'javascript:alert(1)',
        'portfolio_site_url' => 'not-a-url',
    ])->assertInvalid(['portfolio_repository_url', 'portfolio_site_url']);
});

test('a profile update only affects the signed-in user', function () {
    $owner = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $other = User::factory()->create();

    $this->actingAs($owner)->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'id' => $other->id,
        'email' => $other->email,
        'name' => 'Changed owner',
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
    ])->assertSessionHasNoErrors();

    expect($owner->fresh()->name)->toBe('Changed owner')
        ->and($other->fresh()->name)->toBe($other->name);
});

test('users can update their profile and supported social links', function () {
    $user = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $user->socials()->create([
        'platform' => 'facebook',
        'url' => 'https://facebook.com/old-profile',
    ]);

    $response = $this->actingAs($user)->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => 'Updated Name',
        'block' => 'BSCS-2A',
        'bio' => 'I enjoy building software.',
        'socials' => [
            'linkedin' => 'https://linkedin.com/in/example',
            'github' => 'https://github.com/example',
            'facebook' => '',
            'instagram' => 'https://instagram.com/example',
            'email' => 'example@example.com',
        ],
    ]);

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHas('success', 'Profile updated.');

    expect($user->fresh()->name)->toBe('Updated Name');
    expect($user->fresh()->block)->toBe('BSCS-2A');
    expect($user->fresh()->bio)->toBe('I enjoy building software.');
    expect($user->fresh()->portfolio_repository_url)->toBe('https://github.com/example/portfolio');
    expect($user->fresh()->portfolio_site_url)->toBe('https://portfolio.example.com');

    $this->assertDatabaseHas('socials', [
        'user_id' => $user->id,
        'platform' => 'linkedin',
        'url' => 'https://linkedin.com/in/example',
    ]);
    $this->assertDatabaseHas('socials', [
        'user_id' => $user->id,
        'platform' => 'email',
        'url' => 'example@example.com',
    ]);
    $this->assertDatabaseMissing('socials', [
        'user_id' => $user->id,
        'platform' => 'facebook',
    ]);

    $user->update(['profile_photo_path' => 'profile-pictures/photo.png']);
    $this->get(route('home'))
        ->assertSee('Updated Name')
        ->assertSee('BSCS-2A')
        ->assertSee('I enjoy building software.')
        ->assertSee('https://linkedin.com/in/example')
        ->assertSee('https://github.com/example')
        ->assertSee('https://instagram.com/example')
        ->assertSee(asset('images/github-icon.svg'))
        ->assertSee(asset('images/instagram-icon.svg'))
        ->assertSee('mailto:example@example.com');

    $this->get(route('profile.student', $user))
        ->assertSee('Updated Name')
        ->assertSee('BSCS-2A')
        ->assertSee('I enjoy building software.')
        ->assertSee('https://github.com/example')
        ->assertSee('mailto:example@example.com');
});

test('users can upload and replace a profile picture', function () {
    Storage::fake('public');
    $user = User::factory()->create(['block' => 'BSCS-2A', 'bio' => 'Hello there', 'preview_images' => existingPortfolioPreviews()]);
    $details = [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'bio' => $user->bio,
        'socials' => ['email' => ''],
    ];

    $this->actingAs($user)->post(route('dashboard.store'), [
        ...$details,
        'profile_photo' => UploadedFile::fake()->image('first.png'),
    ])->assertRedirect(route('dashboard'))->assertSessionHas('success');

    $firstPhoto = $user->fresh()->profile_photo_path;
    expect($firstPhoto)->not->toBeNull();
    Storage::disk('public')->assertExists($firstPhoto);

    $this->get(route('home'))->assertSee(asset('storage/'.$firstPhoto));
    $this->get(route('dashboard'))->assertSee(asset('storage/'.$firstPhoto));
    $this->get(route('profile.student', $user))->assertSee(asset('storage/'.$firstPhoto));

    $this->post(route('dashboard.store'), $details)->assertRedirect(route('dashboard'));
    expect($user->fresh()->profile_photo_path)->toBe($firstPhoto);
    Storage::disk('public')->assertExists($firstPhoto);

    $this->post(route('dashboard.store'), [
        ...$details,
        'profile_photo' => UploadedFile::fake()->image('second.jpg'),
    ])->assertRedirect(route('dashboard'));

    $secondPhoto = $user->fresh()->profile_photo_path;
    expect($secondPhoto)->not->toBe($firstPhoto);
    Storage::disk('public')->assertExists($secondPhoto);
    Storage::disk('public')->assertMissing($firstPhoto);
});

test('profile pictures must be supported images no larger than two megabytes', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $details = [
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
    ];

    $this->actingAs($user)->post(route('dashboard.store'), [
        ...$details,
        'profile_photo' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
    ])->assertInvalid(['profile_photo']);

    $this->post(route('dashboard.store'), [
        ...$details,
        'profile_photo' => UploadedFile::fake()->image('large.jpg')->size(2049),
    ])->assertInvalid(['profile_photo']);

    expect($user->fresh()->profile_photo_path)->toBeNull();
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('guests cannot upload profile pictures', function () {
    Storage::fake('public');

    $this->post(route('dashboard.store'), [
        'name' => 'Guest',
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'profile_photo' => UploadedFile::fake()->image('guest.png'),
    ])->assertRedirect(route('login'));

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('users cannot submit unsupported social platforms', function () {
    $user = User::factory()->create();
    $originalName = $user->name;

    $response = $this->actingAs($user)->post(route('dashboard.store'), [
        'name' => 'Updated Name',
        'block' => 'BSCS-2A',
        'socials' => [
            'linkedin' => '',
            'github' => '',
            'facebook' => '',
            'instagram' => '',
            'email' => '',
            'mastodon' => 'https://mastodon.social/@example',
        ],
    ]);

    $response->assertInvalid(['socials']);

    expect($user->fresh()->name)->toBe($originalName);
    $this->assertDatabaseMissing('socials', [
        'user_id' => $user->id,
        'platform' => 'mastodon',
    ]);
});

test('users must provide a valid url for social links', function () {
    $user = User::factory()->create();
    $originalName = $user->name;

    $response = $this->actingAs($user)->post(route('dashboard.store'), [
        'name' => 'Updated Name',
        'block' => 'BSCS-2A',
        'socials' => [
            'linkedin' => 'not-a-url',
            'github' => '',
            'facebook' => '',
            'instagram' => '',
            'email' => '',
        ],
    ]);

    $response->assertInvalid(['socials.linkedin']);

    expect($user->fresh()->name)->toBe($originalName);
    $this->assertDatabaseMissing('socials', [
        'user_id' => $user->id,
        'platform' => 'linkedin',
    ]);
});

test('users can clear their bio and disappear from the home page', function () {
    $user = User::factory()->create([...validPortfolioLinks(), 'name' => 'Visible Student', 'block' => 'BSCS-2A', 'bio' => 'Hello there', 'profile_photo_path' => 'profile-pictures/photo.png', 'preview_images' => existingPortfolioPreviews()]);

    $this->get(route('home'))->assertSee('Visible Student');

    $this->actingAs($user)->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'bio' => '   ',
        'socials' => ['email' => ''],
    ])->assertRedirect(route('dashboard'));

    expect($user->fresh()->bio)->toBeNull();
    $this->get(route('home'))->assertDontSee('Visible Student');
    $this->get(route('profile.student', $user))->assertNotFound();
});

test('all six portfolio previews are required for an incomplete profile', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dashboard.store'), [
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
    ])->assertInvalid([
        'previews.desktop_1', 'previews.desktop_2', 'previews.desktop_3',
        'previews.mobile_1', 'previews.mobile_2', 'previews.mobile_3',
    ]);

    expect($user->fresh()->preview_images)->toBeNull();
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('users can upload and replace paired portfolio previews', function () {
    Storage::fake('public');
    $user = User::factory()->create(['bio' => 'A student bio.', 'profile_photo_path' => 'profile-pictures/photo.png']);
    $details = [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'bio' => $user->bio,
        'socials' => ['email' => ''],
    ];
    $uploads = [];

    foreach (['desktop', 'mobile'] as $type) {
        foreach (range(1, 3) as $index) {
            $uploads[$type.'_'.$index] = UploadedFile::fake()->image($type.'-'.$index.'.png');
        }
    }

    $this->actingAs($user)->post(route('dashboard.store'), [
        ...$details,
        'previews' => $uploads,
    ])->assertRedirect(route('dashboard'))->assertSessionHas('success');

    $savedPreviews = $user->fresh()->preview_images;
    expect($savedPreviews)->toHaveCount(6);
    foreach ($savedPreviews as $path) {
        Storage::disk('public')->assertExists($path);
    }

    $home = $this->get(route('home'))->assertOk();
    $profile = $this->get(route('profile.student', $user))->assertOk();
    foreach ($savedPreviews as $type => $path) {
        if (str_starts_with($type, 'desktop_')) {
            $home->assertSee(asset('storage/'.$path));
        } else {
            $home->assertDontSee(asset('storage/'.$path));
        }
        $profile->assertSee(asset('storage/'.$path));
    }
    $home->assertSee('Next portfolio preview');
    $profile->assertSee('Next portfolio preview');
    $home->assertSeeInOrder([
        asset('storage/'.$savedPreviews['desktop_1']),
        asset('storage/'.$savedPreviews['desktop_2']),
        asset('storage/'.$savedPreviews['desktop_3']),
    ]);
    $profile->assertSeeInOrder([
        asset('storage/'.$savedPreviews['desktop_1']),
        asset('storage/'.$savedPreviews['mobile_1']),
        asset('storage/'.$savedPreviews['desktop_2']),
        asset('storage/'.$savedPreviews['mobile_2']),
        asset('storage/'.$savedPreviews['desktop_3']),
        asset('storage/'.$savedPreviews['mobile_3']),
    ]);

    $this->post(route('dashboard.store'), [
        ...$details,
        'previews' => ['desktop_2' => UploadedFile::fake()->image('replacement.jpg')],
    ])->assertRedirect(route('dashboard'))->assertSessionHas('success');

    $updatedPreviews = $user->fresh()->preview_images;
    expect($updatedPreviews['desktop_2'])->not->toBe($savedPreviews['desktop_2']);
    expect($updatedPreviews['mobile_2'])->toBe($savedPreviews['mobile_2']);
    Storage::disk('public')->assertMissing($savedPreviews['desktop_2']);
    Storage::disk('public')->assertExists($updatedPreviews['desktop_2']);
    Storage::disk('public')->assertExists($updatedPreviews['mobile_2']);
});

test('newly uploaded images are removed if the profile update fails', function () {
    Storage::fake('public');
    $previews = existingPortfolioPreviews();
    $user = User::factory()->create(['preview_images' => $previews]);
    Storage::disk('public')->put($previews['desktop_1'], 'existing image');
    Event::listen('eloquent.updating: '.User::class, function () {
        throw new RuntimeException('Update failed.');
    });

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'profile_photo' => UploadedFile::fake()->image('photo.png'),
        'previews' => ['desktop_1' => UploadedFile::fake()->image('replacement.png')],
    ]))->toThrow(RuntimeException::class, 'Update failed.');

    expect($user->fresh()->preview_images)->toBe($previews);
    expect(Storage::disk('public')->allFiles())->toBe([$previews['desktop_1']]);
});

test('invalid preview uploads do not replace existing previews', function () {
    Storage::fake('public');
    $previews = existingPortfolioPreviews();
    $user = User::factory()->create(['preview_images' => $previews]);
    Storage::disk('public')->put($previews['desktop_1'], 'existing image');

    $this->actingAs($user)->post(route('dashboard.store'), [
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'previews' => [
            'desktop_1' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
            'mobile_1' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ],
    ])->assertInvalid(['previews.desktop_1', 'previews.mobile_1']);

    expect($user->fresh()->preview_images)->toBe($previews);
    Storage::disk('public')->assertExists($previews['desktop_1']);
});

test('profile updates reject unsupported blocks and overly long bios', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dashboard.store'), [
        'name' => $user->name,
        'block' => 'BSCS-2B',
        'bio' => str_repeat('a', 2001),
        'socials' => ['email' => ''],
    ])->assertInvalid(['block', 'bio']);

    expect($user->fresh()->block)->toBeNull();
    expect($user->fresh()->bio)->toBeNull();
});

test('a block must be selected even when a program was previously saved', function () {
    $user = User::factory()->create([...validPortfolioLinks(), 'program' => 'BSCS', 'bio' => 'A student bio.', 'profile_photo_path' => 'profile-pictures/photo.png', 'preview_images' => existingPortfolioPreviews()]);

    $this->get(route('home', ['block' => '']))->assertSee('Block not provided');
    $this->actingAs($user)->post(route('dashboard.store'), [
        'name' => $user->name,
        'bio' => $user->bio,
        'socials' => ['email' => ''],
    ])->assertInvalid(['block']);

    expect($user->fresh()->program)->toBe('BSCS');
    expect($user->fresh()->block)->toBeNull();
});

test('only verified users can request a direct image upload', function () {
    config(['filesystems.profile_storage' => 'supabase']);

    $this->postJson(route('dashboard.upload-url'), ['slot' => 'profile_photo', 'mime' => 'image/png'])
        ->assertUnauthorized();

    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->postJson(route('dashboard.upload-url'), ['slot' => 'profile_photo', 'mime' => 'image/png'])
        ->assertForbidden();
});

test('a direct upload URL is restricted to an allowed image slot and type', function () {
    $user = User::factory()->create();
    $this->mock(ProfileImages::class, function (MockInterface $images) use ($user) {
        $images->shouldReceive('usesDirectUploads')->andReturnTrue();
        $images->shouldReceive('cleanupTemporary')->once()->with($user->id);
        $images->shouldReceive('temporaryUploadUrl')->once()
            ->withArgs(fn (string $path, string $mime) => str_starts_with($path, $user->id.'/') && $mime === 'image/png')
            ->andReturn('https://uploads.example.com/signed');
    });

    $this->actingAs($user)->postJson(route('dashboard.upload-url'), ['slot' => 'profile_photo', 'mime' => 'image/png'])
        ->assertOk()
        ->assertJsonPath('url', 'https://uploads.example.com/signed');

    expect(session('profile_uploads.profile_photo.user_id'))->toBe($user->id);
    $this->postJson(route('dashboard.upload-url'), ['slot' => 'other', 'mime' => 'text/html'])
        ->assertUnprocessable()
        ->assertInvalid(['slot', 'mime']);
});

test('a staged image is validated and saved to the profile', function () {
    $user = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $image = UploadedFile::fake()->image('photo.png');
    $contents = file_get_contents($image->getRealPath());
    $path = $user->id.'/staged-image';

    $this->mock(ProfileImages::class, function (MockInterface $images) use ($path, $contents) {
        $images->shouldReceive('usesDirectUploads')->andReturnTrue();
        $images->shouldReceive('downloadTemporary')->once()->with($path, 'profile_photo')->andReturn($contents);
        $images->shouldReceive('store')->once()
            ->withArgs(fn (UploadedFile $file, string $directory) => $file->getMimeType() === 'image/png' && $directory === 'profile-pictures')
            ->andReturn('profile-pictures/saved.png');
        $images->shouldReceive('deleteTemporary')->once()->with($path);
    });

    $this->actingAs($user)->withSession(['profile_uploads' => [
        'profile_photo' => ['user_id' => $user->id, 'path' => $path, 'mime' => 'image/png', 'expires_at' => now()->addMinutes(10)->timestamp],
    ]])->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'staged' => ['profile_photo' => '1'],
    ])->assertRedirect(route('dashboard'))->assertSessionHas('success');

    expect($user->fresh()->profile_photo_path)->toBe('profile-pictures/saved.png');
    expect(session('profile_uploads.profile_photo'))->toBeNull();
});

test('all six staged portfolio previews can be saved together', function () {
    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('preview.png');
    $contents = file_get_contents($image->getRealPath());
    $staged = [];
    $uploads = [];
    foreach (['desktop', 'mobile'] as $type) {
        foreach (range(1, 3) as $index) {
            $slot = $type.'_'.$index;
            $staged[$slot] = '1';
            $uploads[$slot] = ['user_id' => $user->id, 'path' => $user->id.'/'.$slot, 'mime' => 'image/png', 'expires_at' => now()->addMinutes(10)->timestamp];
        }
    }

    $this->mock(ProfileImages::class, function (MockInterface $images) use ($contents) {
        $images->shouldReceive('usesDirectUploads')->andReturnTrue();
        $images->shouldReceive('downloadTemporary')->times(6)->andReturn($contents);
        $images->shouldReceive('store')->times(6)->andReturn(
            'portfolio-previews/1.png', 'portfolio-previews/2.png', 'portfolio-previews/3.png',
            'portfolio-previews/4.png', 'portfolio-previews/5.png', 'portfolio-previews/6.png',
        );
        $images->shouldReceive('deleteTemporary')->times(6);
    });

    $this->actingAs($user)->withSession(['profile_uploads' => $uploads])->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'staged' => $staged,
    ])->assertRedirect(route('dashboard'))->assertSessionHas('success');

    expect($user->fresh()->preview_images)->toHaveCount(6);
    expect(session('profile_uploads'))->toBeEmpty();
});

test('another users staged image cannot be attached to a profile', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $this->mock(ProfileImages::class, function (MockInterface $images) {
        $images->shouldReceive('usesDirectUploads')->andReturnTrue();
        $images->shouldNotReceive('downloadTemporary');
        $images->shouldNotReceive('store');
    });

    $this->actingAs($user)->withSession(['profile_uploads' => [
        'profile_photo' => ['user_id' => $owner->id, 'path' => 'other/image', 'mime' => 'image/png', 'expires_at' => now()->addMinutes(10)->timestamp],
    ]])->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'staged' => ['profile_photo' => '1'],
    ])->assertInvalid(['profile_photo']);

    expect($user->fresh()->profile_photo_path)->toBeNull();
});

test('expired staged images cannot be attached to a profile', function () {
    $user = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $this->mock(ProfileImages::class, function (MockInterface $images) {
        $images->shouldReceive('usesDirectUploads')->andReturnTrue();
        $images->shouldNotReceive('downloadTemporary');
    });

    $this->actingAs($user)->withSession(['profile_uploads' => [
        'profile_photo' => ['user_id' => $user->id, 'path' => $user->id.'/expired', 'mime' => 'image/png', 'expires_at' => now()->subSecond()->timestamp],
    ]])->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'staged' => ['profile_photo' => '1'],
    ])->assertInvalid(['profile_photo']);

    expect($user->fresh()->profile_photo_path)->toBeNull();
});

test('invalid staged image contents are not published', function () {
    $user = User::factory()->create(['preview_images' => existingPortfolioPreviews()]);
    $path = $user->id.'/invalid-image';
    $this->mock(ProfileImages::class, function (MockInterface $images) use ($path) {
        $images->shouldReceive('usesDirectUploads')->andReturnTrue();
        $images->shouldReceive('downloadTemporary')->once()->with($path, 'profile_photo')->andReturn('<script>invalid image</script>');
        $images->shouldNotReceive('store');
        $images->shouldReceive('deleteTemporary')->once()->with($path);
    });

    $this->actingAs($user)->withSession(['profile_uploads' => [
        'profile_photo' => ['user_id' => $user->id, 'path' => $path, 'mime' => 'image/png', 'expires_at' => now()->addMinutes(10)->timestamp],
    ]])->post(route('dashboard.store'), [
        ...validPortfolioLinks(),
        'name' => $user->name,
        'block' => 'BSCS-2A',
        'socials' => ['email' => ''],
        'staged' => ['profile_photo' => '1'],
    ])->assertInvalid(['profile_photo']);

    expect($user->fresh()->profile_photo_path)->toBeNull();
    expect(session('profile_uploads.profile_photo'))->toBeNull();
});

test('profile images use their public Supabase URLs when configured', function () {
    config([
        'filesystems.profile_storage' => 'supabase',
        'filesystems.disks.s3.endpoint' => 'https://project.storage.supabase.co/storage/v1/s3',
        'filesystems.disks.s3.bucket' => 'portfolio-images',
        'filesystems.disks.s3.url' => null,
    ]);
    $user = User::factory()->create([
        ...validPortfolioLinks(),
        'name' => 'Visible Student',
        'block' => 'BSCS-2A',
        'bio' => 'Student bio.',
        'profile_photo_path' => 'profile-pictures/photo.png',
        'preview_images' => existingPortfolioPreviews(),
    ]);
    $photoUrl = 'https://project.storage.supabase.co/storage/v1/object/public/portfolio-images/profile-pictures/photo.png';
    $previewUrl = 'https://project.storage.supabase.co/storage/v1/object/public/portfolio-images/portfolio-previews/desktop-1.png';

    $this->get(route('home'))->assertOk()->assertSee($photoUrl)->assertSee($previewUrl);
    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($photoUrl)->assertSee($previewUrl);
    $this->get(route('profile.student', $user))->assertOk()->assertSee($photoUrl)->assertSee($previewUrl);
});
