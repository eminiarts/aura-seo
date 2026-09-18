<?php

namespace Aura\Seo\Services;

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Data\SiteProfileData;
use Illuminate\Database\Eloquent\Model;

final readonly class PreviewSiteProfileResolver
{
    public function __construct(private SiteProfileResolver $profiles) {}

    public function resolveFor(Model $resource): ?SiteProfileData
    {
        $requestedHost = trim((string) request()->query('seo_host', ''));
        $teamId = $this->teamId($resource);

        if ($requestedHost !== '') {
            $profile = $this->profiles->resolve($requestedHost);

            if ($profile && (! config('aura.teams') || $teamId === null || $profile->teamId === $teamId)) {
                return $profile;
            }
        }

        if ($profile = $this->profiles->resolveForTeam($teamId)) {
            return $profile;
        }

        foreach ($this->profiles->hosts() as $host) {
            $profile = $this->profiles->resolve($host);

            if ($profile && (! config('aura.teams') || $teamId === null || $profile->teamId === $teamId)) {
                return $profile;
            }
        }

        return null;
    }

    private function teamId(Model $resource): ?int
    {
        $teamId = $resource->getAttribute('team_id');

        return is_numeric($teamId) ? (int) $teamId : null;
    }
}
