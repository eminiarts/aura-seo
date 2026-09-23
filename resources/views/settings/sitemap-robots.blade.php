@php($settingsData = app(\Aura\Seo\Services\SeoSettingsData::class)->current())

<div
    class="w-full px-2 pb-2"
    x-data="{
        siteUrl: $wire.entangle('form.fields.seo-canonical-base-url').live,
        allowIndexing: $wire.entangle('form.fields.seo-robots-index'),
        rules: $wire.entangle('form.fields.seo-robots-rules').live,
        preview() {
            const lines = ['User-agent: *', this.allowIndexing ? 'Allow: /' : 'Disallow: /'];
            if (this.rules) lines.push('', this.rules);
            if (this.siteUrl) lines.push('', `Sitemap: ${this.siteUrl.replace(/\/$/, '')}/sitemap.xml`);
            return lines.join('\n');
        },
    }"
>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ __('Public search files') }}</p>
        <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Sitemap & robots') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Control the files that help search engines discover and crawl this site.') }}
        </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="aura-card overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Sitemap') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">/sitemap.xml</h3>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $settingsData['profile']?->sitemapEnabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10' }}">
                    {{ $settingsData['profile']?->sitemapEnabled ? __('Enabled') : __('Disabled') }}
                </span>
            </div>
            <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                @include('aura-seo::settings.partials.field', ['slug' => 'seo-sitemap-enabled'])
            </div>
            <div class="grid grid-cols-2 gap-4 border-t border-gray-200 px-6 py-5 text-sm dark:border-white/10">
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Included records') }}</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($settingsData['totalRecords']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Content types') }}</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ count(array_filter($settingsData['resources'], static fn (array $resource): bool => $resource['sitemap'])) }}</p>
                </div>
            </div>
            @if($settingsData['sitemapUrl'])
                <div class="border-t border-gray-200 px-6 py-4 dark:border-white/10">
                    <x-aura::button.transparent :href="$settingsData['sitemapUrl']" size="sm" target="_blank">
                        {{ __('Open sitemap') }}
                    </x-aura::button.transparent>
                </div>
            @endif
        </section>

        <section class="aura-card overflow-hidden">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-white/10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Robots') }}</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">/robots.txt</h3>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $settingsData['profile']?->robotsEnabled ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10' }}">
                    {{ $settingsData['profile']?->robotsEnabled ? __('Enabled') : __('Disabled') }}
                </span>
            </div>
            <div class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                @include('aura-seo::settings.partials.field', ['slug' => 'seo-robots-route-enabled'])
                @include('aura-seo::settings.partials.field', ['slug' => 'seo-robots-rules'])
            </div>
            @if($settingsData['robotsUrl'])
                <div class="border-t border-gray-200 px-6 py-4 dark:border-white/10">
                    <x-aura::button.transparent :href="$settingsData['robotsUrl']" size="sm" target="_blank">
                        {{ __('Open robots.txt') }}
                    </x-aura::button.transparent>
                </div>
            @endif
        </section>
    </div>

    <section class="aura-card mt-6 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-5 dark:border-white/10">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Preview') }}</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Generated robots.txt') }}</h3>
        </div>
        <pre class="overflow-x-auto whitespace-pre-wrap bg-gray-950 px-6 py-5 text-sm leading-6 text-gray-100" x-text="preview()"></pre>
    </section>

    @if(is_file(public_path('robots.txt')))
        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('A public robots.txt file already exists and may be served instead of the generated file.') }}
        </div>
    @endif
</div>
