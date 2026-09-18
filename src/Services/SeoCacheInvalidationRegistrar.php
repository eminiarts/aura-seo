<?php

namespace Aura\Seo\Services;

use Aura\Base\Resources\Option;
use Illuminate\Database\Eloquent\Model;

final class SeoCacheInvalidationRegistrar
{
    /** @var array<string, bool> */
    private array $observed = [];

    public function __construct(private SeoCache $cache, private SeoRegistry $registry) {}

    public function register(): void
    {
        Option::saved(fn (Option $option): bool => $this->invalidateSettingsAndContinue($option));
        Option::deleted(fn (Option $option): bool => $this->invalidateSettingsAndContinue($option));

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

    private function invalidateSettingsAndContinue(Option $option): bool
    {
        if ($this->isSettingsOption($option)) {
            $this->cache->invalidate($this->teamId($option));
        }

        return true;
    }

    private function isSettingsOption(Option $option): bool
    {
        $name = $option->getAttribute('name');

        return $name === 'settings' || (is_string($name) && preg_match('/^team\\.\\d+\\.settings$/', $name) === 1);
    }
}
