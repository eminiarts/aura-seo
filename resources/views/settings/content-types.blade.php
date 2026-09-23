@php($settingsData = app(\Aura\Seo\Services\SeoSettingsData::class)->current())

<div class="w-full px-2 pb-2" x-data="{ selected: null }">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ __('Registered content') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Content types') }}</h2>
            <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                {{ __('Choose which registered content types publish SEO metadata and appear in the sitemap.') }}
            </p>
        </div>
        <span class="rounded-full bg-primary-50 px-3 py-1.5 text-sm font-semibold text-primary-700 ring-1 ring-primary-200 dark:bg-primary-950/40 dark:text-primary-300 dark:ring-primary-800">
            {{ trans_choice(':count content type|:count content types', $settingsData['resourceCount'], ['count' => $settingsData['resourceCount']]) }}
        </span>
    </div>

    @if($settingsData['resources'] === [])
        <div class="aura-card px-6 py-10 text-center">
            <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('No content types are registered') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Register a content type with Aura SEO before configuring its defaults.') }}</p>
        </div>
    @else
        <section class="aura-card overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Current site') }}</p>
                <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('SEO content coverage') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/[0.02] dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">{{ __('Content type') }}</th>
                            <th class="px-4 py-3">{{ __('SEO output') }}</th>
                            <th class="px-4 py-3">{{ __('Sitemap') }}</th>
                            <th class="px-4 py-3">{{ __('Title source') }}</th>
                            <th class="px-4 py-3">{{ __('Description source') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Records') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach($settingsData['resources'] as $resource)
                            <tr :class="selected === @js($resource['key']) ? 'bg-primary-50/50 dark:bg-primary-950/10' : ''">
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
                                <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ $resource['titleField'] ?: __('Custom') }}</td>
                                <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ $resource['descriptionField'] ?: __('Custom') }}</td>
                                <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($resource['recordCount']) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <x-aura::button.transparent type="button" size="sm" x-on:click="selected = selected === '{{ $resource['key'] }}' ? null : '{{ $resource['key'] }}'">
                                        <span x-text="selected === @js($resource['key']) ? @js(__('Close')) : @js(__('Configure'))"></span>
                                    </x-aura::button.transparent>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @foreach($settingsData['resources'] as $resource)
            @php($definition = $resource['definition'])
            @php($enabledSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'enabled'))
            @php($sitemapSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'sitemap'))
            @php($titleSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'title-pattern'))
            @php($descriptionSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'default-description'))
            @php($imageSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'default-social-image'))
            @php($indexSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'index'))
            @php($followSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'follow'))

            <div data-seo-workspace-layout="content-type-defaults" x-cloak x-show="selected === @js($resource['key'])" x-transition.opacity class="mt-6 grid gap-6 sm:grid-cols-3">
                <section class="aura-card overflow-hidden sm:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ $resource['label'] }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('SEO defaults') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Leave a value blank to use the site default.') }}</p>
                        </div>
                        @if($resource['issueCount'] > 0)
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">{{ trans_choice(':count issue|:count issues', $resource['issueCount'], ['count' => $resource['issueCount']]) }}</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                        @include('aura-seo::settings.partials.field', ['slug' => $enabledSlug])
                        @if($definition->includesSitemap())
                            @include('aura-seo::settings.partials.field', ['slug' => $sitemapSlug])
                        @endif
                        @include('aura-seo::settings.partials.field', ['slug' => $titleSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $descriptionSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $imageSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $indexSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $followSlug])
                    </div>
                </section>

                <aside class="aura-card self-start overflow-hidden">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Resolution example') }}</p>
                        <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ __('How defaults are chosen') }}</h3>
                    </div>
                    <ol class="space-y-3 p-5 text-sm">
                        <li class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">1. {{ __('Record value') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Used when an editor enters a value on the record.') }}</p>
                        </li>
                        <li class="rounded-lg border border-primary-200 bg-primary-50/60 p-3 dark:border-primary-900 dark:bg-primary-950/20">
                            <p class="font-semibold text-primary-800 dark:text-primary-200">2. {{ __('Content type default') }}</p>
                            <p class="mt-1 text-xs text-primary-700/80 dark:text-primary-300/80">{{ __('The values configured here.') }}</p>
                        </li>
                        <li class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                            <p class="font-semibold text-gray-900 dark:text-gray-100">3. {{ __('Site default') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Used when the first two values are empty.') }}</p>
                        </li>
                    </ol>
                </aside>
            </div>
        @endforeach
    @endif
</div>
