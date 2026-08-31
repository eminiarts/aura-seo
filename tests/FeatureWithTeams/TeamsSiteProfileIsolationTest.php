<?php

use Aura\Seo\Contracts\SiteProfileResolver;
use Aura\Seo\Resources\SiteProfile;
use Illuminate\Support\Facades\DB;

test('Teams-on resolution requires an explicit matching Team and cannot cross-resolve', function () {
    $user = DB::table('users')->insertGetId(['email' => 'owner@example.test', 'name' => 'Owner', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()]);
    $teamOne = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'One', 'created_at' => now(), 'updated_at' => now()]);
    $teamTwo = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'Two', 'created_at' => now(), 'updated_at' => now()]);

    $one = SiteProfile::withoutGlobalScopes()->create([
        'fields' => [
            'canonical_base_url' => 'https://one.test',
            'enabled' => true,
            'hostname' => 'one.test',
            'title_template' => '%s',
        ],
        'team_id' => $teamOne,
        'title' => 'One',
    ]);
    $two = SiteProfile::withoutGlobalScopes()->create([
        'fields' => [
            'canonical_base_url' => 'https://two.test',
            'enabled' => true,
            'hostname' => 'two.test',
            'title_template' => '%s',
        ],
        'team_id' => $teamTwo,
        'title' => 'Two',
    ]);

    config()->set('aura-seo.sites', [
        'one.test' => ['profile_id' => $one->getKey(), 'team_id' => $teamOne],
        'two.test' => ['profile_id' => $two->getKey(), 'team_id' => $teamOne],
    ]);

    expect(app(SiteProfileResolver::class)->resolve('one.test')?->team_id)->toBe($teamOne)
        ->and(app(SiteProfileResolver::class)->resolve('two.test'))->toBeNull();
});

test('Teams-on resolution refuses a mapping without Team identity', function () {
    $user = DB::table('users')->insertGetId(['email' => 'owner@example.test', 'name' => 'Owner', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()]);
    $team = DB::table('teams')->insertGetId(['user_id' => $user, 'name' => 'One', 'created_at' => now(), 'updated_at' => now()]);
    $profile = SiteProfile::withoutGlobalScopes()->create([
        'fields' => [
            'canonical_base_url' => 'https://one.test',
            'enabled' => true,
            'hostname' => 'one.test',
            'title_template' => '%s',
        ],
        'team_id' => $team,
        'title' => 'One',
    ]);
    config()->set('aura-seo.sites', ['one.test' => ['profile_id' => $profile->getKey()]]);

    expect(app(SiteProfileResolver::class)->resolve('one.test'))->toBeNull();
});
