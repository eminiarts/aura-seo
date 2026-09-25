@php($prefix = trim((string) ($field['seo_prefix'] ?? 'seo'), '_- '))
@php($slug = static fn (string $value): string => $prefix === '' ? $value : $prefix.'_'.$value)
@php($definition = app(\Aura\Seo\Services\SeoRegistry::class)->findFor($this->model))
@php($titleField = $definition?->titleField() ?? 'title')
@php($descriptionField = $definition?->descriptionField())
@php($canManageSeo = \Illuminate\Support\Facades\Gate::allows('aura-seo.manage'))
@php($canConfigureAi = \Illuminate\Support\Facades\Gate::allows(\Aura\Base\Resources\User::GLOBAL_ADMIN_GATE))
@php($aiConfigured = app(\Aura\Base\Ai\AiStatus::class)->configured())

<div
    class="w-full px-4 pb-4"
    data-ai-suggestion-review
    x-data="{
        loading: false,
        error: null,
        suggestion: null,
        applyTitle: true,
        applyDescription: true,
        currentTitle: '',
        currentDescription: '',
        init() {
            this.readCurrentValues();
        },
        readCurrentValues() {
            const fields = $wire.get('form.fields') || {};
            this.currentTitle = fields[@js($slug('meta_title'))] || '';
            this.currentDescription = fields[@js($slug('meta_description'))] || '';
        },
        async generate() {
            this.loading = true;
            this.error = null;
            this.suggestion = null;

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
                        resource: @js($definition?->key),
                        record_id: @js((string) ($this->model?->getKey() ?? '')),
                        title: fields[@js($titleField)] || '',
                        content: @if ($descriptionField) fields[@js($descriptionField)] || '' @else fields.content || fields.body || fields.excerpt || '' @endif,
                        current_meta_title: fields[@js($slug('meta_title'))] || '',
                        current_meta_description: fields[@js($slug('meta_description'))] || '',
                    }),
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || @js(__('AI metadata generation failed.')));
                }

                this.readCurrentValues();
                this.suggestion = payload;
                this.applyTitle = true;
                this.applyDescription = true;
            } catch (error) {
                this.error = error.message || @js(__('AI metadata generation failed.'));
            } finally {
                this.loading = false;
            }
        },
        async applySelected() {
            if (!this.suggestion) return;

            if (this.applyTitle) {
                await $wire.set(@js('form.fields.'.$slug('meta_title')), this.suggestion.meta_title);
            }

            if (this.applyDescription) {
                await $wire.set(@js('form.fields.'.$slug('meta_description')), this.suggestion.meta_description);
            }

            this.readCurrentValues();
            this.suggestion = null;
        },
    }"
>
    @if($canManageSeo && $definition && $aiConfigured)
        <div class="flex flex-wrap items-center gap-3">
            <x-aura::button type="button" x-on:click="generate" x-bind:disabled="loading">
                <span x-show="loading" class="mr-2"><x-aura::icon.loading class="h-4 w-4" /></span>
                <span x-text="loading ? @js(__('Generating…')) : @js(__('Generate suggestion'))"></span>
            </x-aura::button>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Review the suggestion before applying it to the form.') }}
            </p>
        </div>
    @endif

    <p x-show="error" x-text="error" class="mt-3 text-sm text-red-600 dark:text-red-400"></p>

    @if($canConfigureAi)
        <div x-cloak x-show="error" class="mt-2">
            <x-aura::button.transparent :href="route('aura.settings.page', ['page' => 'ai'])" size="sm">
                {{ __('Review AI settings') }}
            </x-aura::button.transparent>
        </div>
    @endif

    <div x-cloak x-show="suggestion" class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-white/10">
        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Current values') }}</p>
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="currentTitle || @js(__('Uses the current default'))"></p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="currentDescription || @js(__('Uses the current default'))"></p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Suggested values') }}</p>
                <label class="mt-3 flex cursor-pointer items-start gap-3">
                    <input class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" x-model="applyTitle">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="suggestion?.meta_title"></span>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-text="`${suggestion?.meta_title?.length || 0} / 60`"></span>
                    </span>
                </label>
                <label class="mt-4 flex cursor-pointer items-start gap-3">
                    <input class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" type="checkbox" x-model="applyDescription">
                    <span>
                        <span class="block text-sm text-gray-700 dark:text-gray-200" x-text="suggestion?.meta_description"></span>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-text="`${suggestion?.meta_description?.length || 0} / 160`"></span>
                    </span>
                </label>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-gray-200 pt-4 dark:border-white/10">
            <x-aura::button.transparent type="button" x-on:click="suggestion = null">
                {{ __('Keep current values') }}
            </x-aura::button.transparent>
            <x-aura::button type="button" x-on:click="applySelected" x-bind:disabled="!applyTitle && !applyDescription">
                {{ __('Apply selected to form') }}
            </x-aura::button>
        </div>
    </div>
</div>
