@php($definition = app(\Aura\Seo\Services\SeoRegistry::class)->findFor($this->model))
@php($profile = app(\Aura\Seo\Services\PreviewSiteProfileResolver::class)->resolveFor($this->model))
<x-aura::fields.wrapper :field="$field">
    @if(!$definition)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Register this Resource in <code>aura-seo.resources</code> to preview resolved metadata.
        </div>
    @elseif(!$profile)
        <div class="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
            Enable and save the SEO settings for this site or team to preview the resolved title, description, canonical URL, and social card.
        </div>
    @else
        @php($metadata = app(\Aura\Seo\Services\MetadataResolver::class)->resolve($this->model, $profile, $definition))
        @include('aura-seo::components.preview', ['metadata' => $metadata])
    @endif
</x-aura::fields.wrapper>
