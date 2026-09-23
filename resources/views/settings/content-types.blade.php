@php($settingsData = app(\Aura\Seo\Services\SeoSettingsData::class)->current())
@php($profile = $settingsData['profile'])

<div class="w-full px-2 pb-2" x-data="{ selected: null }">
    <div data-seo-content-types-list x-show="selected === null">
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
                                    <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ $resource['titleField'] ?: __('Custom') }}</td>
                                    <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ $resource['descriptionField'] ?: __('Custom') }}</td>
                                    <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-gray-100">{{ number_format($resource['recordCount']) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <x-aura::button.transparent type="button" size="sm" x-on:click="selected = '{{ $resource['key'] }}'">
                                            {{ __('Configure') }}
                                        </x-aura::button.transparent>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    @foreach($settingsData['resources'] as $resource)
        @php($definition = $resource['definition'])
        @php($defaults = $profile?->defaultsFor($definition))
        @php($enabledSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'enabled'))
        @php($sitemapSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'sitemap'))
        @php($titleSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'title-pattern'))
        @php($descriptionSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'default-description'))
        @php($imageSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'default-social-image'))
        @php($indexSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'index'))
        @php($followSlug = \Aura\Seo\Services\SeoResourceSettings::slug($definition, 'follow'))
        @php($resourcePattern = trim((string) $defaults?->titlePattern))
        @php($sitePattern = $profile?->titleTemplate ?: '[Post Title] [Separator] [Site Name]')
        @php($example = $resource['example'])
        @php($exampleRecordTitle = $example['recordTitle'] ?? __('Example :type', ['type' => \Illuminate\Support\Str::singular($resource['label'])]))
        @php($resolvedTitle = $example['title'] ?? app(\Aura\Seo\Services\TitlePatternRenderer::class)->render($resourcePattern !== '' ? $resourcePattern : $sitePattern, $exampleRecordTitle, $profile?->separator ?: '|', $profile?->name ?: config('app.name')))
        @php($resolvedDescription = $example['description'] ?? $defaults?->defaultDescription ?? $profile?->defaultDescription)
        @php($exampleUrl = $example['url'] ?? rtrim((string) ($profile?->baseUrl ?: config('app.url')), '/').'/'.\Illuminate\Support\Str::slug($resource['key']).'/'.\Illuminate\Support\Str::slug($exampleRecordTitle))

        <div
            data-seo-content-type-editor="{{ $resource['key'] }}"
            x-cloak
            x-show="selected === @js($resource['key'])"
            x-transition.opacity
        >
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ __('Content types / :type', ['type' => $resource['label']]) }}</p>
                    <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __(':type SEO defaults', ['type' => $resource['label']]) }}</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Choose the default title, description, image, and search visibility for :type.', ['type' => $resource['label']]) }}
                    </p>
                </div>
                <x-aura::button.transparent type="button" x-on:click="selected = null">
                    <span class="flex items-center gap-2">
                        <x-aura::icon icon="arrow-left" size="xs" />
                        {{ __('Back to content types') }}
                    </span>
                </x-aura::button.transparent>
            </div>

            <div data-seo-workspace-layout="content-type-defaults" class="grid gap-6 sm:grid-cols-3">
                <section class="aura-card overflow-hidden sm:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Visibility') }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('SEO and sitemap') }}</h3>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                            {{ __('Available') }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                        @include('aura-seo::settings.partials.field', ['slug' => $enabledSlug])
                        @if($definition->includesSitemap())
                            @include('aura-seo::settings.partials.field', ['slug' => $sitemapSlug])
                        @endif
                    </div>

                    <div class="border-t border-gray-200 px-6 py-5 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Presentation') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __(':type defaults', ['type' => \Illuminate\Support\Str::singular($resource['label'])]) }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Leave a value blank to use the site default.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                        @include('aura-seo::settings.partials.field', ['slug' => $titleSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $descriptionSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $imageSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $indexSlug])
                        @include('aura-seo::settings.partials.field', ['slug' => $followSlug])
                    </div>
                </section>

                <aside class="space-y-6 self-start">
                    <section class="aura-card overflow-hidden">
                        <div class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-white/10">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Resolution example') }}</p>
                                <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $exampleRecordTitle }}</h3>
                            </div>
                            <span class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-200 dark:bg-primary-950/40 dark:text-primary-300 dark:ring-primary-800">
                                {{ \Illuminate\Support\Str::singular($resource['label']) }}
                            </span>
                        </div>
                        <ol class="space-y-3 p-5 text-sm">
                            <li class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/5 dark:text-gray-300">1</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Record value') }}</p>
                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ __('No manual meta title') }}</p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ __('Empty') }}</span>
                            </li>
                            <li class="flex items-center gap-3 rounded-lg border p-3 {{ $resourcePattern !== '' ? 'border-primary-200 bg-primary-50/60 dark:border-primary-900 dark:bg-primary-950/20' : 'border-gray-200 dark:border-white/10' }}">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $resourcePattern !== '' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' }} text-xs font-semibold">2</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ __(':type default', ['type' => \Illuminate\Support\Str::singular($resource['label'])]) }}</p>
                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $resourcePattern !== '' ? $resourcePattern : __('Not configured') }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $resourcePattern !== '' ? 'bg-primary-100 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' }}">{{ $resourcePattern !== '' ? __('Used') : __('Skipped') }}</span>
                            </li>
                            <li class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $resourcePattern === '' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' }} text-xs font-semibold">3</span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ __('Site default') }}</p>
                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $sitePattern }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $resourcePattern === '' ? 'bg-primary-100 text-primary-700 dark:bg-primary-950/40 dark:text-primary-300' : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300' }}">{{ $resourcePattern === '' ? __('Used') : __('Fallback') }}</span>
                            </li>
                        </ol>
                    </section>

                    <section class="aura-card overflow-hidden">
                        <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Resolved preview') }}</p>
                            <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ __('Search result') }}</h3>
                        </div>
                        <div class="p-5">
                            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                                <p class="truncate text-sm text-emerald-700 dark:text-emerald-400">{{ $exampleUrl }}</p>
                                <p class="mt-1 text-xl leading-6 text-blue-700 dark:text-blue-400">{{ $resolvedTitle }}</p>
                                <p class="mt-2 text-sm leading-5 text-gray-600 dark:text-gray-300">{{ $resolvedDescription ?: __('Add a default description to complete this preview.') }}</p>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    @endforeach
</div>
