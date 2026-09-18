<?php

namespace Aura\Seo\Contracts;

use Aura\Seo\Data\SiteProfileData;

interface SiteProfileResolver
{
    /** @return list<string> */
    public function hosts(): array;

    public function resolve(string $hostname): ?SiteProfileData;

    public function resolveForTeam(?int $teamId): ?SiteProfileData;
}
