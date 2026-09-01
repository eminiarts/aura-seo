<?php

namespace Aura\Seo\Services;

use Aura\Seo\Resources\SiteProfile;
use Illuminate\Database\Eloquent\Model;

final class SeoCacheInvalidationRegistrar
{
    /** @var array<string, bool> */
    private array $observed = [];

    public function __construct(private SeoCache $cache, private SeoRegistry $registry) {}

    public function register(): void
    {
        $this->registerResource(SiteProfile::class);

        foreach ($this->registry->all() as $definition) {
            $this->registerResource($definition->resourceClass);
        }
    }

    /** @param class-string<Model> $resourceClass */
    public function registerResource(string $resourceClass): void
    {
        if (isset($this->observed[$resourceClass])) {
            return;
        }

        $resourceClass::saved(fn (Model $model): bool => $this->invalidateAndContinue($model));
        $resourceClass::deleted(fn (Model $model): bool => $this->invalidateAndContinue($model));
        $this->observed[$resourceClass] = true;
    }

    private function invalidateModel(mixed $model): void
    {
        if (! $model instanceof Model) {
            return;
        }

        if ($model instanceof SiteProfile) {
            $this->cache->invalidate($this->teamId($model), is_numeric($model->getKey()) ? (int) $model->getKey() : null);

            return;
        }

        if ($this->registry->findFor($model) !== null) {
            $this->cache->invalidate($this->teamId($model));
        }
    }

    private function teamId(Model $model): ?int
    {
        $teamId = $model->getAttribute('team_id');

        return is_numeric($teamId) ? (int) $teamId : null;
    }

    private function invalidateAndContinue(Model $model): bool
    {
        $this->invalidateModel($model);

        return true;
    }
}
