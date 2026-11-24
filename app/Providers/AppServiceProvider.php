<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        VerifyEmail::createUrlUsing(fn ($user): string => URL::temporarySignedRoute(
            'verification.complete',
            now()->addMinutes(config('auth.verification.expire', 60)),
            ['user' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        ));

        VerifyEmail::toMailUsing(fn (object $user, string $url): MailMessage => (new MailMessage)
            ->subject(__('Verify your email address'))
            ->greeting(__('Magandang Araw!'))
            ->line(__('Please verify your email address to access your portfolio.'))
            ->action(__('Verify email address'), $url)
            ->line(__('After verifying, return to the app and refresh the page.'))
            ->line(__('After mo iclick, balik ka sa app tapos refresh mo.'))
            ->line(__('If you did not request this email, please ignore.'))); // To ensure that no classmate wiill try to open other account, that's why i got this email verification
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
