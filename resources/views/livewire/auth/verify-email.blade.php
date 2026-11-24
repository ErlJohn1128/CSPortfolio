<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Verify your email')"
            :description="__('Verify your email address to edit your profile. Request a verification link below, then return here and refresh after verifying.')"
        />

        @if (config('mail.default') === 'log')
            <flux:text class="text-center !text-amber-700 dark:!text-amber-400">
                {{ __('Email delivery is not configured. Please contact the site administrator.') }}
            </flux:text>
        @endif

        @if (session('status') == 'verification-link-sent')
            <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
                {{ config('mail.default') === 'log' ? __('The verification message was logged, not emailed.') : __('A verification link has been sent to your email address.') }}
            </flux:text>
        @endif

        <div class="flex flex-col items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Send verification email') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Log out') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>
