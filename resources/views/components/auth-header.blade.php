@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2">
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-400">{{ __('CSPortfolio') }}</p>
    <flux:heading size="xl" level="1" class="!text-2xl !font-semibold !tracking-tight">{{ $title }}</flux:heading>
    <flux:subheading class="!leading-6">{{ $description }}</flux:subheading>
</div>
