@php($settingsData = app(\Aura\Seo\Services\SeoSettingsData::class)->current())
@php($profile = $settingsData['profile'])
@php($issues = $settingsData['issues'])

<div class="w-full px-2 pb-2">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ __('Current check') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __('SEO diagnostics') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                {{ __('Review the issues that can affect search previews, indexing, and sitemap coverage.') }}
            </p>
        </div>
        @can('aura-seo.diagnose')
            <x-aura::button type="button" wire:click="$refresh">
                {{ __('Run fresh checks') }}
            </x-aura::button>
        @endcan
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <section class="aura-card p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
            <p class="mt-4 text-2xl font-semibold {{ $issues === [] ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">{{ $issues === [] ? __('All clear') : __('Needs review') }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Based on current saved settings') }}</p>
        </section>
        <section class="aura-card p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Errors') }}</p>
            <p class="mt-4 text-3xl font-semibold {{ $settingsData['errorCount'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $settingsData['errorCount'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Fix these first') }}</p>
        </section>
        <section class="aura-card p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Warnings') }}</p>
            <p class="mt-4 text-3xl font-semibold {{ $settingsData['warningCount'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $settingsData['warningCount'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Recommended improvements') }}</p>
        </section>
    </div>

    @if(!$profile)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('Enable SEO and save a valid site URL before running checks.') }}
        </div>
    @else
        <section class="aura-card overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Open issues') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ trans_choice(':count finding|:count findings', count($issues), ['count' => count($issues)]) }}</h3>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Checked at :time', ['time' => now()->format('H:i')]) }}</span>
            </div>

            @if($issues === [])
                <div class="px-6 py-10 text-center">
                    <p class="font-semibold text-emerald-700 dark:text-emerald-300">{{ __('No SEO issues were found.') }}</p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Your current settings and public content passed the available checks.') }}</p>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach($issues as $issue)
                        <div class="flex flex-wrap items-center gap-4 px-6 py-4">
                            <span class="h-10 w-1 shrink-0 rounded-full {{ $issue->level === 'error' ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $issue->message }}</p>
                                @if($issue->source || $issue->record)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ collect([$issue->source ? \Illuminate\Support\Str::headline($issue->source) : null, $issue->record])->filter()->join(' · ') }}
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
        </section>
    @endif
</div>
