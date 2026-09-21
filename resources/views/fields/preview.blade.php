@php($definition = app(\Aura\Seo\Services\SeoRegistry::class)->findFor($this->model))
@php($profile = app(\Aura\Seo\Services\PreviewSiteProfileResolver::class)->resolveFor($this->model))
<x-aura::fields.wrapper :field="$field">
    @if(!$definition)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('SEO preview is not available for this content type yet.') }}
        </div>
    @elseif(!$profile)
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-white/10 dark:bg-white/[0.03] dark:text-gray-200">
            {{ __('Enable SEO and save the site settings to see the preview.') }}
        </div>
    @else
        @php($metadata = app(\Aura\Seo\Services\MetadataResolver::class)->resolve($this->model, $profile, $definition))
        @include('aura-seo::components.preview', ['metadata' => $metadata])
    @endif
</x-aura::fields.wrapper>
