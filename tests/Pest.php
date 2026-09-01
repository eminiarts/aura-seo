<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Seo\Tests\TeamsTestCase;
use Aura\Seo\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(TestCase::class)->in('Feature', 'Unit');
uses(TeamsTestCase::class)->in('FeatureWithTeams');

function clearSeoCurrentTeamCache(int $userId): void
{
    $key = method_exists(User::class, 'currentTeamCacheKey')
        ? User::currentTeamCacheKey($userId)
        : "user_{$userId}_current_team_id";

    Cache::forget($key);
}

function createSeoUserWithPermissions(array $permissions, ?int $teamId = null, bool $superAdmin = false): User
{
    $user = User::factory()->create();
    $team = config('aura.teams')
        ? ($teamId ? Team::withoutGlobalScopes()->findOrFail($teamId) : Team::factory()->createQuietly(['user_id' => $user->id]))
        : null;
    $role = Role::withoutGlobalScopes()->create(array_merge([
        'description' => 'Aura SEO test role.',
        'name' => 'Aura SEO '.Str::random(8),
        'permissions' => collect($permissions)->mapWithKeys(fn (string $permission): array => [$permission => true])->all(),
        'slug' => 'aura-seo-'.Str::lower(Str::random(8)),
        'super_admin' => $superAdmin,
        'type' => 'Role',
    ], Schema::hasColumn('roles', 'team_id') ? ['team_id' => $team?->id] : []));

    if ($team) {
        $user->forceFill(['current_team_id' => $team->id])->save();
        $user->roles()->attach($role->id, ['team_id' => $team->id]);
        clearSeoCurrentTeamCache($user->id);
    } else {
        $user->roles()->attach($role->id);
    }

    return $user->refresh();
}
