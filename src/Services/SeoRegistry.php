<?php

namespace Aura\Seo\Services;

use Aura\Seo\Data\SeoResourceDefinition;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class SeoRegistry
{
    /** @var array<string, SeoResourceDefinition> */
    private array $definitions = [];

    public function register(SeoResourceDefinition $definition): self
    {
        if (preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $definition->key) !== 1
            || isset($this->definitions[$definition->key])) {
            throw new InvalidArgumentException('SEO Resource keys must be unique lowercase slug values.');
        }

        if (! is_a($definition->resourceClass, Model::class, true)) {
            throw new InvalidArgumentException('SEO Resources must be Eloquent model classes.');
        }

        if ($definition->includesSitemap() && (! $definition->hasUrlResolver() || ! $definition->hasPublicBoundary())) {
            throw new InvalidArgumentException('Sitemap Resources require a URL resolver and both public query and visibility gates.');
        }

        $this->definitions[$definition->key] = $definition;

        if (app()->bound(SeoCacheInvalidationRegistrar::class)) {
            app(SeoCacheInvalidationRegistrar::class)->registerResource($definition->resourceClass);
        }

        return $this;
    }

    /** @return array<string, SeoResourceDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    public function findFor(Model|string $resource): ?SeoResourceDefinition
    {
        $class = is_string($resource) ? $resource : $resource::class;

        foreach ($this->definitions as $definition) {
            if ($class === $definition->resourceClass || is_a($class, $definition->resourceClass, true)) {
                return $definition;
            }
        }

        return null;
    }

    public function get(string $key): ?SeoResourceDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /** @return array<string, SeoResourceDefinition> */
    public function sitemapDefinitions(): array
    {
        return array_filter($this->definitions, fn (SeoResourceDefinition $definition): bool => $definition->includesSitemap());
    }
}
