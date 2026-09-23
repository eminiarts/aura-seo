@php($teamId = config('aura.teams') ? data_get(auth()->user(), 'current_team_id') : null)
@php($profile = app(\Aura\Seo\Contracts\SiteProfileResolver::class)->resolveForTeam(is_numeric($teamId) ? (int) $teamId : null))
@php($issues = $profile ? app(\Aura\Seo\Services\SeoDiagnostics::class)->scan($profile) : [])

<div class="w-full px-2 pb-2">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('Review items that may affect how your content appears in search results. Checks use saved settings.') }}
        </p>
        <x-aura::button type="button" wire:click="$refresh">
            {{ __('Run checks') }}
        </x-aura::button>
    </div>

    @if(!$profile)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('Enable SEO and save a valid site URL before running checks.') }}
        </div>
    @elseif($issues === [])
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
            <p>{{ __('No SEO issues were found.') }}</p>
            <p class="mt-1 text-xs opacity-80">{{ __('Checked at :time', ['time' => now()->format('H:i')]) }}</p>
        </div>
    @else
        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            <p>{{ trans_choice(':count item needs attention.|:count items need attention.', count($issues), ['count' => count($issues)]) }}</p>
            <p class="mt-1 text-xs opacity-80">{{ __('Checked at :time', ['time' => now()->format('H:i')]) }}</p>
        </div>

        <div class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
            @foreach($issues as $issue)
                <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $issue->message }}</p>
                        @if($issue->source)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::headline($issue->source) }}
                            </p>
                        @endif
                    </div>

                    @if($issue->actionUrl && $issue->actionLabel)
                        <x-aura::button.transparent :href="$issue->actionUrl" size="sm">
                            {{ __($issue->actionLabel) }}
                        </x-aura::button.transparent>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
