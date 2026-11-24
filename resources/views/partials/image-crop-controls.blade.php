<button type="button" data-crop-edit hidden class="w-fit rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">
    Adjust current crop
</button>
<div data-crop-editor hidden class="flex flex-col gap-4 rounded-md border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex flex-col gap-1">
        <p class="text-sm font-medium">Adjust crop</p>
        <p class="text-xs leading-5 text-zinc-600 dark:text-zinc-400">Use the sliders or enter a percentage to adjust the crop. Saving replaces the image; upload the original again to restore cropped-out areas.</p>
    </div>
    <div @class(['grid gap-4', 'sm:grid-cols-[8rem_minmax(0,1fr)] sm:items-center' => $cropType === 'profile'])>
        <canvas data-crop-canvas role="img" aria-label="Crop preview" @class(['w-full bg-zinc-200 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700', 'max-w-32 rounded-full' => $cropType === 'profile', 'max-w-32 rounded-md' => $cropType === 'mobile', 'rounded-md' => $cropType === 'desktop'])></canvas>
        <div class="flex min-w-0 flex-col gap-3">
            @foreach ([
                'zoom' => ['Zoom', 100, 300, 100],
                'horizontal' => ['Horizontal position', 0, 100, 50],
                'vertical' => ['Vertical position', 0, 100, 50],
            ] as $axis => [$label, $minimum, $maximum, $value])
                <div data-crop-setting>
                    <div class="flex items-center justify-between gap-2">
                        <label for="{{ $cropId }}-{{ $axis }}" class="text-sm font-medium">{{ $label }}</label>
                        <div class="flex shrink-0 items-center gap-1">
                            <input type="number" data-crop-percentage aria-label="{{ $label }} percentage" aria-describedby="{{ $cropId }}-{{ $axis }}-error" min="{{ $minimum }}" max="{{ $maximum }}" step="0.01" value="{{ $value }}" required disabled class="w-20 rounded-md border border-zinc-300 bg-white px-2 py-1 text-right text-xs tabular-nums text-zinc-900 outline-none focus:border-zinc-500 focus:ring-2 focus:ring-zinc-500/20 aria-[invalid=true]:border-red-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500" />
                            <span aria-hidden="true" class="text-xs text-zinc-500 dark:text-zinc-400">%</span>
                        </div>
                    </div>
                    <input id="{{ $cropId }}-{{ $axis }}" data-crop-{{ $axis }} type="range" min="{{ $axis === 'zoom' ? 1 : 0 }}" max="{{ $axis === 'zoom' ? 3 : 100 }}" step="any" value="{{ $axis === 'zoom' ? 1 : $value }}" aria-valuetext="{{ $value }}%" class="crop-range w-full" />
                    <p id="{{ $cropId }}-{{ $axis }}-error" data-crop-percentage-error role="status" hidden class="text-xs leading-5 text-red-600 dark:text-red-400">Enter a value from {{ $minimum }} to {{ $maximum }}, with up to two decimal places.</p>
                </div>
            @endforeach
        </div>
    </div>
    <button type="button" data-crop-cancel class="w-fit text-xs font-medium text-zinc-600 underline underline-offset-2 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">Cancel crop</button>
</div>
<p data-crop-error role="alert" hidden class="text-sm text-red-600 dark:text-red-400"></p>
<button type="button" data-crop-dismiss hidden class="w-fit text-xs font-medium text-zinc-600 underline underline-offset-2 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">Discard crop</button>
