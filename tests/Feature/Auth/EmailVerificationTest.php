<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk()
        ->assertSee('Earl John Bozar')
        ->assertDontSee('Your work deserves a place to shine.');
});

test('unverified users are redirected to the email verification prompt', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertRedirect(route('verification.notice'));
});

test('users can request a verification email for their own account', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'), ['email' => $other->email])
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
    Notification::assertNotSentTo($other, VerifyEmail::class);
});

test('a verification link cannot verify a different signed-in account', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($other)->get($verificationUrl)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('home', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')],
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('already verified user visiting verification link is redirected without firing event again', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl)
        ->assertRedirect(route('home', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});

test('verification email link verifies the account without signing in', function () {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('Verify your email address');

    $this->get($mail->actionUrl)
        ->assertOk()
        ->assertSee('Email verified')
        ->assertSee('return to the app');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->assertGuest();
    Event::assertDispatched(Verified::class);

    $this->get($mail->actionUrl)->assertOk();
    Event::assertDispatchedTimes(Verified::class, 1);
});

test('verification link does not change the account signed in on another browser', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $url = (new VerifyEmail)->toMail($user)->actionUrl;

    $this->actingAs($other)->get($url)->assertOk();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
    $this->assertAuthenticatedAs($other);
});

test('verification rejects tampered expired and stale links', function () {
    $user = User::factory()->unverified()->create();
    $url = (new VerifyEmail)->toMail($user)->actionUrl;

    $this->get($url.'&extra=1')->assertForbidden();
    $this->travel(61)->minutes();
    $this->get($url)->assertForbidden();
    $this->travelBack();

    $user->forceFill(['email' => 'changed@example.com'])->save();
    $this->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
