@php($teamId = config('aura.teams') ? data_get(auth()->user(), 'current_team_id') : null)
@php($profile = app(\Aura\Seo\Contracts\SiteProfileResolver::class)->resolveForTeam(is_numeric($teamId) ? (int) $teamId : null))
@php($issues = $profile ? app(\Aura\Seo\Services\SeoDiagnostics::class)->scan($profile) : [])
@php($errors = collect($issues)->filter->isError()->count())
@php($warnings = count($issues) - $errors)

<div class="w-full px-2 pb-2">
    <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
        {{ __('Diagnostics use the last saved SEO settings. Save this page before checking changed values.') }}
    </p>

    @if(!$profile)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
            {{ __('Enable SEO and save a valid canonical base URL to run diagnostics.') }}
        </div>
    @else
        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900 dark:bg-red-950/40">
                <p class="text-xs uppercase tracking-wide text-red-600 dark:text-red-300">{{ __('Errors') }}</p>
                <p class="mt-1 text-2xl font-semibold text-red-700 dark:text-red-200">{{ $errors }}</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900 dark:bg-amber-950/40">
                <p class="text-xs uppercase tracking-wide text-amber-600 dark:text-amber-300">{{ __('Warnings') }}</p>
                <p class="mt-1 text-2xl font-semibold text-amber-700 dark:text-amber-200">{{ $warnings }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/[0.03]">
                    <tr>
                        <th class="px-4 py-3">{{ __('Level') }}</th>
                        <th class="px-4 py-3">{{ __('Source') }}</th>
                        <th class="px-4 py-3">{{ __('Record') }}</th>
                        <th class="px-4 py-3">{{ __('Message') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($issues as $issue)
                        <tr class="align-top">
                            <td class="px-4 py-3 font-semibold {{ $issue->isError() ? 'text-red-600' : 'text-amber-600' }}">{{ strtoupper($issue->level) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $issue->source ?? 'site' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $issue->record ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $issue->message }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-emerald-700 dark:text-emerald-300">{{ __('No SEO issues were found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
