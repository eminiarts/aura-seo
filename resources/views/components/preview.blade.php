@php($preview = app(\Aura\Seo\Services\PreviewFactory::class)->make($metadata))
@php($previewClass = \Illuminate\Support\Arr::toCssClasses([
    'space-y-6',
    isset($attributes) && $attributes instanceof \Illuminate\View\ComponentAttributeBag ? $attributes->get('class') : (is_array($attributes ?? null) ? ($attributes['class'] ?? null) : null),
]))
<div class="{{ $previewClass }}" data-aura-seo-preview>
    <section aria-label="Search result preview" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Search preview</p>
        @if($preview->searchUrl)
            <p class="truncate text-sm text-emerald-700">{{ $preview->searchUrl }}</p>
        @endif
        @if($preview->searchTitle)
            <p class="mt-1 text-xl text-blue-700">{{ $preview->searchTitle }}</p>
        @endif
        @if($preview->searchDescription)
            <p class="mt-1 text-sm leading-5 text-gray-700">{{ $preview->searchDescription }}</p>
        @endif
    </section>

    <section aria-label="Social card preview" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @if($preview->socialImage)
            <img src="{{ $preview->socialImage }}" alt="" class="aspect-[1.91/1] w-full object-cover">
        @endif
        <div class="p-4">
            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500">Social preview</p>
            @if($preview->socialTitle)
                <p class="font-semibold text-gray-900">{{ $preview->socialTitle }}</p>
            @endif
            @if($preview->socialDescription)
                <p class="mt-1 text-sm text-gray-600">{{ $preview->socialDescription }}</p>
            @endif
        </div>
    </section>

    <p class="text-xs text-gray-500">Preview only. Search engines and social platforms may display metadata differently.</p>
</div>
