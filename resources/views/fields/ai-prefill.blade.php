@php($prefix = trim((string) ($field['seo_prefix'] ?? 'seo'), '_- '))
@php($slug = static fn (string $value): string => $prefix === '' ? $value : $prefix.'_'.$value)
@php($definition = app(\Aura\Seo\Services\SeoRegistry::class)->findFor($this->model))
@php($titleField = $definition?->titleField() ?? 'title')
@php($descriptionField = $definition?->descriptionField())
@php($canManageSeo = \Illuminate\Support\Facades\Gate::allows('aura-seo.manage'))
@php($canConfigureAi = auth()->user() && method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin())

<div
    class="w-full"
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
    @if($canManageSeo && $definition)
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-primary-200 bg-primary-50/70 px-5 py-4 dark:border-primary-900 dark:bg-primary-950/20">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-900 dark:text-primary-200">AI</span>
                <div>
                    <p class="text-sm font-semibold text-primary-900 dark:text-primary-100">{{ __('Generate a title and description') }}</p>
                    <p class="mt-1 text-xs text-primary-700/80 dark:text-primary-300/80">{{ __('Review the suggestion before applying it to the form.') }}</p>
                </div>
            </div>
            <x-aura::button type="button" x-on:click="generate" x-bind:disabled="loading">
                <span x-show="loading" class="mr-2"><x-aura::icon.loading class="h-4 w-4" /></span>
                <span x-text="loading ? @js(__('Generating…')) : @js(__('Generate suggestion'))"></span>
            </x-aura::button>
        </div>
    @endif

    <p x-cloak x-show="error" x-text="error" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"></p>

    @if($canConfigureAi)
        <div x-cloak x-show="error" class="mt-2">
            <x-aura::button.transparent :href="route('aura.settings.page', ['page' => 'ai'])" size="sm">
                {{ __('Review AI settings') }}
            </x-aura::button.transparent>
        </div>
    @endif

    <div x-cloak x-show="suggestion" class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-white/10">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('AI suggestion review') }}</p>
            <h3 class="mt-1 font-semibold text-gray-900 dark:text-white">{{ __('Compare and apply') }}</h3>
        </div>
        <div class="grid gap-4 p-5 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Current values') }}</p>
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="currentTitle || @js(__('Uses the current default'))"></p>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300" x-text="currentDescription || @js(__('Uses the current default'))"></p>
            </div>

            <div class="rounded-lg border border-primary-200 bg-primary-50/40 p-4 dark:border-primary-900 dark:bg-primary-950/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ __('Suggested values') }}</p>
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
                        <span class="block text-sm leading-6 text-gray-700 dark:text-gray-200" x-text="suggestion?.meta_description"></span>
                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-text="`${suggestion?.meta_description?.length || 0} / 160`"></span>
                    </span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-white/10">
            <x-aura::button.transparent type="button" x-on:click="suggestion = null">
                {{ __('Keep current values') }}
            </x-aura::button.transparent>
            <x-aura::button type="button" x-on:click="applySelected" x-bind:disabled="!applyTitle && !applyDescription">
                {{ __('Apply selected to form') }}
            </x-aura::button>
        </div>
    </div>
</div>
