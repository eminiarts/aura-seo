<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\SeoResourceDefinition;

final readonly class SitemapRegistry
{
    public function __construct(private SeoRegistry $registry) {}

    /** @return array<string, SeoResourceDefinition> */
    public function all(): array
    {
        return $this->registry->sitemapDefinitions();
    }

    public function get(string $key): ?SeoResourceDefinition
    {
        $definition = $this->registry->get($key);

        return $definition?->includesSitemap() ? $definition : null;
    }
}
