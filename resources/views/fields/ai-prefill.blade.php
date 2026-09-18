@php($prefix = trim((string) ($field['seo_prefix'] ?? 'seo'), '_- '))
@php($slug = static fn (string $value): string => $prefix === '' ? $value : $prefix.'_'.$value)
@php($definition = app(\Aura\Seo\Services\SeoRegistry::class)->findFor($this->model))
@php($titleField = $definition?->titleField() ?? 'title')
@php($descriptionField = $definition?->descriptionField())

<div
    class="w-full px-4 pb-4"
    x-data="{
        loading: false,
        error: null,
        async generate() {
            this.loading = true;
            this.error = null;

            try {
                const fields = $wire.get('form.fields') || {};
                const response = await fetch(@js(route('aura.seo.ai-metadata')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    },
                    body: JSON.stringify({
                        title: fields[@js($titleField)] || '',
                        content: @if ($descriptionField) fields[@js($descriptionField)] || '' @else fields.content || fields.body || fields.excerpt || '' @endif,
                    }),
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || @js(__('AI metadata generation failed.')));
                }

                await $wire.set(@js('form.fields.'.$slug('meta_title')), payload.meta_title);
                await $wire.set(@js('form.fields.'.$slug('meta_description')), payload.meta_description);
            } catch (error) {
                this.error = error.message || @js(__('AI metadata generation failed.'));
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <div class="flex flex-wrap items-center gap-3">
        <x-aura::button type="button" x-on:click="generate" x-bind:disabled="loading">
            <span x-show="loading" class="mr-2"><x-aura::icon.loading class="w-4 h-4" /></span>
            <span x-text="loading ? @js(__('Generating…')) : @js(__('Pre-fill with AI'))"></span>
        </x-aura::button>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('Uses the saved Aura AI connection and the current title and content.') }}
        </p>
    </div>

    <p x-show="error" x-text="error" class="mt-2 text-sm text-red-600 dark:text-red-400"></p>
</div>
