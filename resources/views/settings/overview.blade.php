@php($settingsData = app(\Aura\Seo\Services\SeoSettingsData::class)->current())
@php($profile = $settingsData['profile'])
@php($issues = $settingsData['issues'])

<div class="w-full px-2 pb-2">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ __('SEO workspace') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __('SEO overview') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                {{ __('See what is published, what needs attention, and which content types are covered.') }}
            </p>
        </div>
        @can('aura-seo.diagnose')
            <x-aura::button type="button" wire:click="$refresh">
                {{ __('Run fresh checks') }}
            </x-aura::button>
        @endcan
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <section class="aura-card p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('SEO status') }}</p>
                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $profile?->enabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10' }}">
                    {{ $profile?->enabled ? __('Live') : __('Off') }}
                </span>
            </div>
            <p class="mt-4 text-3xl font-semibold {{ $issues === [] ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-900 dark:text-white' }}">
                {{ $issues === [] ? __('Ready') : __('Review') }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trans_choice(':count open item|:count open items', count($issues), ['count' => count($issues)]) }}</p>
        </section>

        <section class="aura-card p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Errors') }}</p>
            <p class="mt-4 text-3xl font-semibold {{ $settingsData['errorCount'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $settingsData['errorCount'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Blocking search output issues') }}</p>
        </section>

        <section class="aura-card p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Warnings') }}</p>
            <p class="mt-4 text-3xl font-semibold {{ $settingsData['warningCount'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">{{ $settingsData['warningCount'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Recommended improvements') }}</p>
        </section>

        <section class="aura-card p-5">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Indexed records') }}</p>
            <p class="mt-4 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($settingsData['totalRecords']) }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ trans_choice('Across :count content type|Across :count content types', $settingsData['resourceCount'], ['count' => $settingsData['resourceCount']]) }}</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(19rem,1fr)]">
        <div class="space-y-6">
            <section class="aura-card overflow-hidden">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Needs attention') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Recommended actions') }}</h3>
                    </div>
                    <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">{{ count($issues) }}</span>
                </div>

                @if($issues === [])
                    <div class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No SEO issues were found.') }}
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach(array_slice($issues, 0, 4) as $issue)
                            <div class="flex flex-wrap items-center gap-4 px-6 py-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-bold {{ $issue->level === 'error' ? 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">
                                    {{ $issue->level === 'error' ? '!' : '•' }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $issue->message }}</p>
                                    @if($issue->source)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::headline($issue->source) }}</p>
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

            <section class="aura-card overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Content coverage') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Registered content types') }}</h3>
                </div>
                @if($settingsData['resources'] === [])
                    <div class="px-6 py-8 text-sm text-gray-500 dark:text-gray-400">{{ __('No content types are registered yet.') }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">{{ __('Content type') }}</th>
                                    <th class="px-4 py-3">{{ __('SEO output') }}</th>
                                    <th class="px-4 py-3">{{ __('Sitemap') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Records') }}</th>
                                    <th class="px-6 py-3 text-right">{{ __('Issues') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach($settingsData['resources'] as $resource)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-sm font-semibold text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($resource['label'], 0, 1)) }}</span>
                                                <div>
                                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $resource['label'] }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $resource['key'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $resource['enabled'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' }}">{{ $resource['enabled'] ? __('Enabled') : __('Disabled') }}</span></td>
                                        <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $resource['sitemap'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' }}">{{ $resource['sitemap'] ? __('Included') : __('Excluded') }}</span></td>
                                        <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($resource['recordCount']) }}</td>
                                        <td class="px-6 py-4 text-right font-medium {{ $resource['issueCount'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $resource['issueCount'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="aura-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Site status') }}</p>
                    <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $profile?->name ?: config('app.name') }}</h3>
                </div>
                <div class="space-y-3 p-5 text-sm">
                    @foreach([
                        [__('Metadata output'), (bool) $profile?->enabled],
                        [__('Sitemap'), (bool) $profile?->sitemapEnabled],
                        [__('Robots.txt'), (bool) $profile?->robotsEnabled],
                        [__('Social image fallback'), (bool) ($profile?->defaultOpenGraphImage || $profile?->defaultSocialImage)],
                    ] as [$label, $ready])
                        <div class="flex items-center gap-3 text-gray-700 dark:text-gray-200">
                            <span class="h-2.5 w-2.5 rounded-full {{ $ready ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                            <span>{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="aura-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Public surface') }}</p>
                    <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ __('Search routes') }}</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @if($settingsData['sitemapUrl'])
                        <a class="flex items-center justify-between gap-3 px-5 py-4 text-sm hover:bg-gray-50 dark:hover:bg-white/[0.03]" href="{{ $settingsData['sitemapUrl'] }}" target="_blank">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">/sitemap.xml</span>
                            <span class="text-primary-600 dark:text-primary-400">{{ __('Open') }}</span>
                        </a>
                    @endif
                    @if($settingsData['robotsUrl'])
                        <a class="flex items-center justify-between gap-3 px-5 py-4 text-sm hover:bg-gray-50 dark:hover:bg-white/[0.03]" href="{{ $settingsData['robotsUrl'] }}" target="_blank">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">/robots.txt</span>
                            <span class="text-primary-600 dark:text-primary-400">{{ __('Open') }}</span>
                        </a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
