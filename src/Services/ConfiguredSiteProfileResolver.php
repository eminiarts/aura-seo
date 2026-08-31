<?php

namespace Aura\Seo\Services;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Resources\SiteProfile;
use Illuminate\Support\Facades\Schema;

final class ConfiguredSiteProfileResolver implements SiteProfileResolver
{
    public function resolve(string $hostname): ?SiteProfile
    {
        $hostname = $this->normalizeHostname($hostname);

        if ($hostname === null) {
            return null;
        }

        $sites = (array) config('aura-seo.sites', []);
        $site = $sites[$hostname] ?? null;

        if (! is_array($site) || ! isset($site['profile_id']) || ! is_numeric($site['profile_id'])) {
            return null;
        }

        $teams = (bool) config('aura.teams');
        $teamId = $site['team_id'] ?? null;

        if ($teams && (! is_numeric($teamId) || (int) $teamId < 1)) {
            return null;
        }

        $query = SiteProfile::withoutGlobalScopes()->whereKey((int) $site['profile_id']);

        if (Schema::hasColumn((new SiteProfile)->getTable(), 'team_id')) {
            $teams ? $query->where('team_id', (int) $teamId) : $query->whereNull('team_id');
        }

        /** @var SiteProfile|null $profile */
        $profile = $query->first();

        $data = $profile?->toSeoData();

        if (! $profile || ! $data?->enabled || $this->normalizeHostname($data->hostname) !== $hostname) {
            return null;
        }

        return $profile;
    }

    private function normalizeHostname(string $hostname): ?string
    {
        $hostname = strtolower(rtrim(trim($hostname), '.'));

        if ($hostname === '' || str_contains($hostname, '/') || str_contains($hostname, ':')) {
            return null;
        }

        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $hostname : null;
    }
}
