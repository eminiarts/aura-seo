<?php

namespace Aura\Seo\Contracts;

use Aura\Seo\Resources\SiteProfile;

interface SiteProfileResolver
{
    public function resolve(string $hostname): ?SiteProfile;
}
