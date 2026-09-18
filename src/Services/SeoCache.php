<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\SiteProfileData;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

final class SeoCache
{
    public function invalidate(?int $teamId = null, ?int $profileId = null): void
    {
        $this->bumpVersion('global');

        if ($teamId !== null) {
            $this->bumpVersion("team:{$teamId}");
        }

        if ($profileId !== null) {
            $this->bumpVersion("profile:{$profileId}");
        }
    }

    public function invalidateAll(): void
    {
        $this->bumpVersion('global');
    }

    public function remember(SiteProfileData $profile, string $segment, Closure $resolver): mixed
    {
        $ttl = (int) config('aura-seo.cache.ttl', 3600);

        if ($ttl <= 0) {
            return $resolver();
        }

        return $this->store()->remember($this->payloadKey($profile, $segment), now()->addSeconds($ttl), $resolver);
    }

    private function bumpVersion(string $scope): void
    {
        $this->store()->forever($this->versionKey($scope), $this->version($scope) + 1);
    }

    private function payloadKey(SiteProfileData $profile, string $segment): string
    {
        return 'aura-seo:'.sha1(json_encode([
            'global' => $this->version('global'),
            'host' => $profile->hostname,
            'profile' => $profile->id,
            'profile_hash' => sha1(json_encode($profile, JSON_THROW_ON_ERROR)),
            'profile_version' => $profile->id === null ? 0 : $this->version("profile:{$profile->id}"),
            'segment' => $segment,
            'team' => $profile->teamId,
            'team_version' => $profile->teamId === null ? 0 : $this->version("team:{$profile->teamId}"),
        ], JSON_THROW_ON_ERROR));
    }

    private function store(): Repository
    {
        $store = config('aura-seo.cache.store');

        return $store ? Cache::store((string) $store) : Cache::store();
    }

    private function version(string $scope): int
    {
        return (int) $this->store()->get($this->versionKey($scope), 1);
    }

    private function versionKey(string $scope): string
    {
        return "aura-seo:version:{$scope}";
    }
}
