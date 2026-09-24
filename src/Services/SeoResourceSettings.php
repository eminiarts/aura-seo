<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\SeoResourceDefinition;

final class SeoResourceSettings
{
    public static function slug(SeoResourceDefinition|string $resource, string $setting): string
    {
        $key = $resource instanceof SeoResourceDefinition ? $resource->key : $resource;

        return "seo-resource-{$key}-{$setting}";
    }
}
