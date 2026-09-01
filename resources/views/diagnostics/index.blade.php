@php($errors = collect($issues)->filter->isError()->count())
@php($warnings = count($issues) - $errors)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aura SEO diagnostics</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-50 text-stone-900">
<main class="mx-auto max-w-6xl space-y-8 px-6 py-10">
    <section class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">Aura SEO</p>
                <h1 class="mt-2 text-3xl font-semibold">Diagnostics</h1>
                <p class="mt-2 text-sm text-stone-600">
                    Host: <span class="font-medium text-stone-900">{{ $host }}</span>
                    @if($profile)
                        <span class="mx-2 text-stone-300">/</span>
                        SiteProfile: <span class="font-medium text-stone-900">{{ $profile->title }}</span>
                    @endif
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-red-600">Errors</p>
                    <p class="mt-1 text-2xl font-semibold text-red-700">{{ $errors }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-amber-600">Warnings</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $warnings }}</p>
                </div>
            </div>
        </div>
        @if($availableHosts !== [])
            <div class="mt-6 flex flex-wrap gap-2 text-sm">
                @foreach($availableHosts as $availableHost)
                    <a href="?host={{ urlencode($availableHost) }}"
                       class="rounded-full border px-3 py-1 {{ $availableHost === $host ? 'border-stone-900 bg-stone-900 text-white' : 'border-stone-300 bg-white text-stone-700' }}">
                        {{ $availableHost }}
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-stone-200">
            <thead class="bg-stone-100 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
            <tr>
                <th class="px-4 py-3">Level</th>
                <th class="px-4 py-3">Source</th>
                <th class="px-4 py-3">Record</th>
                <th class="px-4 py-3">Message</th>
                <th class="px-4 py-3">Canonical</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 text-sm">
            @forelse($issues as $issue)
                <tr class="align-top">
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $issue->isError() ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ strtoupper($issue->level) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-stone-600">{{ $issue->source ?? 'site' }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-stone-600">{{ $issue->record ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $issue->message }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-stone-600 break-all">{{ $issue->canonical ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-emerald-700">No SEO issues were found for this SiteProfile.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
