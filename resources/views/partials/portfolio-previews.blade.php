@php
    $previews = $student->preview_images ?? [];
    $pairs = collect(range(1, 3))->filter(fn ($index) => filled($previews['desktop_'.$index] ?? null) && filled($previews['mobile_'.$index] ?? null));
@endphp

<div data-carousel class="relative flex min-h-45 items-center justify-center overflow-hidden bg-gradient-to-br from-slate-100 via-white to-blue-100 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950 {{ $previewPadding }}">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,rgba(100,116,139,0.12)_1px,transparent_1px),linear-gradient(to_bottom,rgba(100,116,139,0.12)_1px,transparent_1px)] bg-size-[40px_40px] mask-radial-from-20% mask-radial-to-90% dark:opacity-40"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -left-20 -top-32 h-80 w-80 rounded-full bg-sky-300/30 blur-3xl dark:bg-blue-500/15"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -right-16 h-80 w-80 rounded-full bg-indigo-300/30 blur-3xl dark:bg-indigo-500/20"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-40 h-80 w-80 rounded-full border border-slate-400/20 dark:border-white/10"></div>
    @forelse ($pairs as $index)
        <div @class(['carousel-slide relative w-full', 'hidden' => ! $loop->first])>
            <div class="relative mx-auto max-w-4xl pb-[4%]">
                <div class="w-[89%] rounded-lg border border-white/70 bg-white p-1 shadow-2xl shadow-slate-900/20 dark:border-white/15 dark:bg-slate-800 dark:shadow-black/40 sm:p-1.5">
                    <img src="{{ asset('storage/'.$previews['desktop_'.$index]) }}" alt="Desktop preview {{ $index }} for {{ $student->name }}" class="aspect-video w-full rounded-sm object-cover" />
                </div>
                <div class="absolute bottom-0 right-0 w-[22%] rounded-[1.15rem] bg-gradient-to-br from-zinc-400 via-zinc-700 to-zinc-900 p-[3px] shadow-2xl shadow-slate-900/40 ring-1 ring-black/30 sm:rounded-[1.8rem] sm:p-1">
                    <div class="relative rounded-[0.95rem] bg-black p-[3px] sm:rounded-[1.55rem] sm:p-1">
                        <div class="relative overflow-hidden rounded-[0.7rem] bg-zinc-900 sm:rounded-[1.25rem]">
                            <img src="{{ asset('storage/'.$previews['mobile_'.$index]) }}" alt="Mobile preview {{ $index }} for {{ $student->name }}" class="aspect-[9/16] w-full object-cover" />
                            <div aria-hidden="true" class="absolute left-1/2 top-[2%] h-[3%] min-h-1 w-[32%] -translate-x-1/2 rounded-full bg-black"></div>
                            <div aria-hidden="true" class="absolute bottom-[2%] left-1/2 h-0.5 w-1/3 -translate-x-1/2 rounded-full bg-white/80"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <p class="relative text-sm text-zinc-600 dark:text-zinc-400">Portfolio previews not provided yet.</p>
    @endforelse

    @if ($pairs->count() > 1)
        <button type="button" onclick="changeSlide(this, -1)" aria-label="Previous portfolio preview" class="absolute left-2 top-1/2 z-20 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80">
            <span aria-hidden="true">&lsaquo;</span>
        </button>
        <button type="button" onclick="changeSlide(this, 1)" aria-label="Next portfolio preview" class="absolute right-2 top-1/2 z-20 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-full bg-black/60 text-white hover:bg-black/80">
            <span aria-hidden="true">&rsaquo;</span>
        </button>
    @endif
</div>
