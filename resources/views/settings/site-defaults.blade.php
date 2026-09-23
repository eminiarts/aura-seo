@php($settingsData = app(\Aura\Seo\Services\SeoSettingsData::class)->current())
@php($profile = $settingsData['profile'])

<div
    class="w-full px-2 pb-2"
    x-data="{
        siteName: $wire.entangle('form.fields.seo-site-name').live,
        siteUrl: $wire.entangle('form.fields.seo-canonical-base-url').live,
        separator: $wire.entangle('form.fields.seo-separator').live,
        pattern: $wire.entangle('form.fields.seo-title-pattern').live,
        description: $wire.entangle('form.fields.seo-default-description').live,
        resolvedTitle() {
            return (this.pattern || '[Post Title] [Separator] [Site Name]')
                .replaceAll('[Post Title]', @js(__('Example page')))
                .replaceAll('[Separator]', this.separator || '|')
                .replaceAll('[Site Name]', this.siteName || @js(config('app.name')));
        },
        previewUrl() {
            return `${(this.siteUrl || '').replace(/\/$/, '')}/example-page`;
        },
    }"
>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ __('Site defaults') }}</p>
        <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Search and social defaults') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Set the values used when a content type or record does not provide its own metadata.') }}
        </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(19rem,1fr)]">
        <div class="space-y-6">
            <section class="aura-card overflow-hidden">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Site identity') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Public site information') }}</h3>
                    </div>
                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $profile?->enabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10' }}">
                        {{ $profile?->enabled ? __('Output enabled') : __('Output disabled') }}
                    </span>
                </div>
                <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-enabled'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-site-name'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-locale'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-canonical-base-url'])
                </div>
            </section>

            <section class="aura-card overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Search defaults') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Titles and descriptions') }}</h3>
                </div>
                <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-title-pattern'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-separator'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-default-description'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-robots-index'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-robots-follow'])
                </div>
            </section>

            <section class="aura-card overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Social sharing') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Fallback images') }}</h3>
                </div>
                <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-default-open-graph-image'])
                    @include('aura-seo::settings.partials.field', ['slug' => 'seo-default-twitter-image'])
                </div>
            </section>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
            <section class="aura-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Resolved example') }}</p>
                    <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ __('Title pattern preview') }}</h3>
                </div>
                <div class="p-5">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <p class="truncate text-sm text-emerald-700 dark:text-emerald-400" x-text="previewUrl()"></p>
                        <p class="mt-1 text-xl leading-6 text-blue-700 dark:text-blue-400" x-text="resolvedTitle()"></p>
                        <p class="mt-2 text-sm leading-5 text-gray-600 dark:text-gray-300" x-text="description || @js(__('Add a default description to complete this preview.'))"></p>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('Content types and records can override these values.') }}</p>
                </div>
            </section>

            <section class="aura-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Public output') }}</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-700 dark:text-gray-200">{{ __('SEO metadata') }}</span>
                        <span class="font-semibold {{ $profile?->enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500' }}">{{ $profile?->enabled ? __('Enabled') : __('Disabled') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Sitemap') }}</span>
                        <span class="font-semibold {{ $profile?->sitemapEnabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500' }}">{{ $profile?->sitemapEnabled ? __('Enabled') : __('Disabled') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-gray-700 dark:text-gray-200">{{ __('Robots.txt') }}</span>
                        <span class="font-semibold {{ $profile?->robotsEnabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500' }}">{{ $profile?->robotsEnabled ? __('Enabled') : __('Disabled') }}</span>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</div>
