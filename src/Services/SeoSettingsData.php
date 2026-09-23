<?php

namespace Aura\Seo\Services;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\DiagnosticIssue;
use Aura\Seo\Data\SiteProfileData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

final class SeoSettingsData
{
    /** @var array<string, mixed>|null */
    private ?array $current = null;

    public function __construct(
        private readonly SiteProfileResolver $profiles,
        private readonly SeoDiagnostics $diagnostics,
        private readonly SeoRegistry $registry,
    ) {}

    /** @return array<string, mixed> */
    public function current(): array
    {
        return $this->current ??= $this->buildCurrent();
    }

    /** @return array<string, mixed> */
    private function buildCurrent(): array
    {
        $profile = $this->profiles->resolveForTeam($this->currentTeamId());
        $issues = $profile ? $this->diagnostics->scan($profile) : [];
        $resources = $this->resources($profile, $issues);

        return [
            'errorCount' => count(array_filter($issues, static fn (DiagnosticIssue $issue): bool => $issue->level === 'error')),
            'issues' => $issues,
            'profile' => $profile,
            'resourceCount' => count($resources),
            'resources' => $resources,
            'robotsUrl' => Route::has('aura.seo.robots') ? route('aura.seo.robots') : null,
            'sitemapUrl' => Route::has('aura.seo.sitemap.index') ? route('aura.seo.sitemap.index') : null,
            'totalRecords' => array_sum(array_column($resources, 'recordCount')),
            'warningCount' => count(array_filter($issues, static fn (DiagnosticIssue $issue): bool => $issue->level !== 'error')),
        ];
    }

    private function currentTeamId(): ?int
    {
        if (! config('aura.teams')) {
            return null;
        }

        $teamId = data_get(auth()->user(), 'current_team_id');

        return is_numeric($teamId) ? (int) $teamId : null;
    }

    /**
     * @param  array<int, DiagnosticIssue>  $issues
     * @return list<array<string, mixed>>
     */
    private function resources(?SiteProfileData $profile, array $issues): array
    {
        $rows = [];

        foreach ($this->registry->all() as $definition) {
            $defaults = $profile?->defaultsFor($definition);
            $query = $profile ? $definition->publicQuery($profile) : null;

            $rows[] = [
                'definition' => $definition,
                'descriptionField' => $definition->descriptionField(),
                'enabled' => $defaults?->enabled ?? false,
                'issueCount' => count(array_filter(
                    $issues,
                    static fn (DiagnosticIssue $issue): bool => $issue->source === $definition->key,
                )),
                'key' => $definition->key,
                'label' => Str::headline($definition->key),
                'recordCount' => $this->count($query),
                'sitemap' => $definition->includesSitemap() && ($defaults?->sitemap ?? false),
                'titleField' => $definition->titleField(),
            ];
        }

        return $rows;
    }

    private function count(?Builder $query): int
    {
        return $query?->count() ?? 0;
    }
}
