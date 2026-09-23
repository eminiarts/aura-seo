@php($prefix = trim((string) ($field['seo_prefix'] ?? 'seo'), '_- '))
@php($slug = static fn (string $value): string => $prefix === '' ? $value : $prefix.'_'.$value)
@php($definition = app(\Aura\Seo\Services\SeoRegistry::class)->findFor($this->model))
@php($aiField = $this->model->mappedFieldBySlug($slug('ai_prefill')))
@php($previewField = $this->model->mappedFieldBySlug($slug('preview')))

<div class="w-full px-2 pb-2" x-data="{ section: 'search' }">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">{{ $definition ? \Illuminate\Support\Str::headline($definition->key) : __('SEO') }}</p>
        <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Search metadata') }}</h2>
        <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Record values override content type and site defaults. Leave a field blank to keep its inherited value.') }}
        </p>
    </div>

    @if($aiField)
        @include('aura-seo::fields.ai-prefill', ['field' => $aiField])
    @endif

    <div data-seo-workspace-layout="record-metadata" class="mt-6 grid gap-6 sm:grid-cols-3">
        <section class="aura-card overflow-hidden sm:col-span-2">
            <div class="flex flex-wrap gap-2 border-b border-gray-200 px-5 pt-4 dark:border-white/10" role="tablist" aria-label="{{ __('SEO fields') }}">
                @foreach(['search' => __('Search'), 'social' => __('Social'), 'advanced' => __('Advanced')] as $section => $label)
                    <button
                        type="button"
                        role="tab"
                        x-on:click="section = @js($section)"
                        x-bind:aria-selected="section === @js($section)"
                        x-bind:class="section === @js($section) ? 'border-primary-600 text-primary-700 dark:border-primary-500 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="border-b-2 px-3 pb-3 text-sm font-semibold transition"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="section === 'search'" class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                @include('aura-seo::settings.partials.field', ['slug' => $slug('meta_title')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('meta_description')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('slug')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('canonical_url')])
            </div>

            <div x-cloak x-show="section === 'social'" class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                @include('aura-seo::settings.partials.field', ['slug' => $slug('og_title')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('og_description')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('og_image')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('twitter_title')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('twitter_description')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('twitter_image')])
            </div>

            <div x-cloak x-show="section === 'advanced'" class="flex flex-wrap items-start gap-y-5 px-2 py-6">
                @include('aura-seo::settings.partials.field', ['slug' => $slug('index')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('follow')])
                @include('aura-seo::settings.partials.field', ['slug' => $slug('twitter_card')])
            </div>
        </section>

        <aside class="aura-card self-start overflow-hidden xl:sticky xl:top-6">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Resolved output') }}</p>
                <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ __('Live preview') }}</h3>
            </div>
            <div class="p-1">
                @if($previewField)
                    @include('aura-seo::fields.preview', ['field' => $previewField])
                @endif
            </div>
        </aside>
    </div>
</div>
