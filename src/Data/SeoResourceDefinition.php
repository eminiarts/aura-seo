<?php

namespace Aura\Seo\Data;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SeoResourceDefinition
{
    /** @var Closure(Model, SiteProfileData): array<string, string>|null */
    private ?Closure $alternateResolver = null;

    private bool $allowsExternalCanonical = false;

    /** @var string|Closure(Model, SiteProfileData): mixed|null */
    private string|Closure|null $descriptionMapping = null;

    private string $fieldPrefix = 'seo';

    /** @var string|Closure(Model, SiteProfileData): mixed|null */
    private string|Closure|null $imageMapping = null;

    private bool $includedInSitemap = false;

    /** @var string|Closure(Model): mixed|null */
    private string|Closure|null $lastModifiedMapping = null;

    /** @var Closure(SiteProfileData): Builder|null */
    private ?Closure $publicQueryResolver = null;

    /** @var Closure(Model, SiteProfileData): bool|null */
    private ?Closure $publicVisibilityResolver = null;

    /** @var string|Closure(Model, SiteProfileData): mixed|null */
    private string|Closure|null $titleMapping = null;

    /** @var Closure(Model, SiteProfileData): string|null */
    private ?Closure $urlResolver = null;

    /** @param class-string<Model> $resourceClass */
    private function __construct(public readonly string $key, public readonly string $resourceClass) {}

    /** @param class-string<Model> $resourceClass */
    public static function make(string $key, string $resourceClass): self
    {
        return new self($key, $resourceClass);
    }

    /** @param Closure(Model, SiteProfileData): array<string, string> $resolver */
    public function alternates(Closure $resolver): self
    {
        $this->alternateResolver = $resolver;

        return $this;
    }

    public function allowExternalCanonical(bool $allow = true): self
    {
        $this->allowsExternalCanonical = $allow;

        return $this;
    }

    public function description(string|Closure $mapping): self
    {
        $this->descriptionMapping = $mapping;

        return $this;
    }

    public function descriptionField(): ?string
    {
        return is_string($this->descriptionMapping) && $this->descriptionMapping !== ''
            ? $this->descriptionMapping
            : null;
    }

    public function fields(string $prefix): self
    {
        $this->fieldPrefix = trim($prefix, '_- ');

        return $this;
    }

    public function image(string|Closure $mapping): self
    {
        $this->imageMapping = $mapping;

        return $this;
    }

    public function lastModified(string|Closure $mapping): self
    {
        $this->lastModifiedMapping = $mapping;

        return $this;
    }

    /**
     * Both callbacks are mandatory for public enumeration. The query defines
     * the publication boundary; the record predicate is a second policy gate.
     *
     * @param  Closure(SiteProfileData): Builder  $query
     * @param  Closure(Model, SiteProfileData): bool  $visible
     */
    public function publicIndex(Closure $query, Closure $visible): self
    {
        $this->publicQueryResolver = $query;
        $this->publicVisibilityResolver = $visible;

        return $this;
    }

    public function sitemap(bool $include = true): self
    {
        $this->includedInSitemap = $include;

        return $this;
    }

    public function title(string|Closure $mapping): self
    {
        $this->titleMapping = $mapping;

        return $this;
    }

    public function titleField(): ?string
    {
        return is_string($this->titleMapping) && $this->titleMapping !== ''
            ? $this->titleMapping
            : null;
    }

    /** @param Closure(Model, SiteProfileData): string $resolver */
    public function url(Closure $resolver): self
    {
        $this->urlResolver = $resolver;

        return $this;
    }

    /** @return array<string, string> */
    public function alternateUrls(Model $resource, SiteProfileData $profile): array
    {
        return $this->alternateResolver ? (array) ($this->alternateResolver)($resource, $profile) : [];
    }

    public function externalCanonicalAllowed(): bool
    {
        return $this->allowsExternalCanonical;
    }

    public function field(string $name): string
    {
        return $this->fieldPrefix === '' ? $name : $this->fieldPrefix.'_'.$name;
    }

    public function hasPublicBoundary(): bool
    {
        return $this->publicQueryResolver !== null && $this->publicVisibilityResolver !== null;
    }

    public function hasUrlResolver(): bool
    {
        return $this->urlResolver !== null;
    }

    public function includesSitemap(): bool
    {
        return $this->includedInSitemap;
    }

    public function isPublic(Model $resource, SiteProfileData $profile): bool
    {
        return $this->publicVisibilityResolver !== null
            && (bool) ($this->publicVisibilityResolver)($resource, $profile);
    }

    public function lastModifiedValue(Model $resource): mixed
    {
        return $this->resolveMapping($this->lastModifiedMapping, $resource, null);
    }

    public function mappedDescription(Model $resource, SiteProfileData $profile): mixed
    {
        return $this->resolveMapping($this->descriptionMapping, $resource, $profile);
    }

    public function mappedImage(Model $resource, SiteProfileData $profile): mixed
    {
        return $this->resolveMapping($this->imageMapping, $resource, $profile);
    }

    public function mappedTitle(Model $resource, SiteProfileData $profile): mixed
    {
        return $this->resolveMapping($this->titleMapping, $resource, $profile);
    }

    public function publicQuery(SiteProfileData $profile): ?Builder
    {
        return $this->publicQueryResolver ? ($this->publicQueryResolver)($profile) : null;
    }

    public function publicUrl(Model $resource, SiteProfileData $profile): ?string
    {
        return $this->urlResolver ? ($this->urlResolver)($resource, $profile) : null;
    }

    private function resolveMapping(string|Closure|null $mapping, Model $resource, ?SiteProfileData $profile): mixed
    {
        if ($mapping instanceof Closure) {
            return $profile === null ? $mapping($resource) : $mapping($resource, $profile);
        }

        if (! is_string($mapping) || $mapping === '') {
            return null;
        }

        return data_get($resource, $mapping);
    }
}
