<div class="w-full px-2 pb-2">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Run checks when you want to review saved SEO settings and public content.') }}
        </p>

        @can('aura-seo.diagnose')
            <x-aura::button type="button" wire:click="runChecks" wire:loading.attr="disabled" wire:target="runChecks">
                <span wire:loading wire:target="runChecks" class="mr-2"><x-aura::icon.loading /></span>
                {{ __('Run checks') }}
            </x-aura::button>
        @endcan
    </div>

    @if(!$hasRun)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
            {{ __('Checks have not been run yet.') }}
        </div>
    @elseif($status)
        <div @class([
            'rounded-lg border px-4 py-3 text-sm',
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200' => $successful,
            'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200' => !$successful,
        ])>
            <p>{{ __($status) }}</p>
            <p class="mt-1 text-xs opacity-80">{{ __('Checked at :time', ['time' => $checkedAt]) }}</p>
        </div>
    @else
        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            <p>{{ trans_choice(':count item needs attention.|:count items need attention.', count($issues), ['count' => count($issues)]) }}</p>
            <p class="mt-1 text-xs opacity-80">{{ __('Checked at :time', ['time' => $checkedAt]) }}</p>
        </div>

        <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
            @foreach($issues as $issue)
                <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4" wire:key="seo-issue-{{ $loop->index }}">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $issue['message'] }}</p>
                        @if($issue['source'])
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::headline($issue['source']) }}
                            </p>
                        @endif
                    </div>

                    @if($issue['actionUrl'] && $issue['actionLabel'])
                        <x-aura::button.transparent :href="$issue['actionUrl']" size="sm">
                            {{ __($issue['actionLabel']) }}
                        </x-aura::button.transparent>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
