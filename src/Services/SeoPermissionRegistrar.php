<?php

namespace Aura\Seo\Services;

use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Team;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SeoPermissionRegistrar
{
    private bool $synchronized = false;

    /** @return array<string, array{name: string, description: string}> */
    public function definitions(): array
    {
        return [
            (string) config('aura-seo.permissions.manage', 'manage-aura-seo') => [
                'name' => 'Manage Aura SEO',
                'description' => 'Manage Aura SEO settings, profiles, and public metadata behavior.',
            ],
            (string) config('aura-seo.permissions.diagnose', 'diagnose-aura-seo') => [
                'name' => 'Diagnose Aura SEO',
                'description' => 'Inspect Aura SEO diagnostics, sitemap coverage, and public metadata issues.',
            ],
        ];
    }

    public function synchronize(?int $onlyTeamId = null): int
    {
        try {
            if (! Schema::hasTable('permissions')) {
                return 0;
            }

            $created = 0;

            foreach ($this->teamIds($onlyTeamId) as $teamId) {
                foreach ($this->definitions() as $slug => $definition) {
                    $identity = ['slug' => $slug];

                    if (config('aura.teams') && Schema::hasColumn('permissions', 'team_id')) {
                        $identity['team_id'] = $teamId;
                    }

                    $permission = Permission::withoutGlobalScopes()->firstOrCreate($identity, array_merge($definition, [
                        'group' => 'SEO',
                    ], Schema::hasColumn('permissions', 'user_id') ? ['user_id' => null] : []));

                    $created += $permission->wasRecentlyCreated ? 1 : 0;
                }
            }

            return $created;
        } catch (Throwable) {
            return 0;
        }
    }

    public function synchronizeOnce(): int
    {
        if ($this->synchronized || ! Schema::hasTable('permissions')) {
            return 0;
        }

        $created = $this->synchronize();
        $this->synchronized = true;

        return $created;
    }

    /** @return array<int, int|null> */
    private function teamIds(?int $onlyTeamId): array
    {
        if (! config('aura.teams') || ! Schema::hasColumn('permissions', 'team_id')) {
            return [null];
        }

        if ($onlyTeamId !== null) {
            return [$onlyTeamId];
        }

        if (! Schema::hasTable((new Team)->getTable())) {
            return [];
        }

        return Team::withoutGlobalScopes()
            ->pluck((new Team)->getKeyName())
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
