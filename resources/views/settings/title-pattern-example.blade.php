@php
    $values = $this->form['fields'] ?? [];
    $example = app(\Aura\Seo\Services\TitlePatternRenderer::class)->render(
        (string) ($values['seo-title-pattern'] ?? ''),
        __('Example page'),
        (string) ($values['seo-separator'] ?? '|'),
        (string) ($values['seo-site-name'] ?? config('app.name', 'Aura')),
    );
@endphp

<div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Example') }}</p>
    <p class="mt-2 text-base font-medium text-gray-900 dark:text-gray-100">{{ $example }}</p>
</div>
